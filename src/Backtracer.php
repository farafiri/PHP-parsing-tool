<?php

namespace ParserGenerator;

use ParserGenerator\GrammarNode\NodeInterface;
use ParserGenerator\GrammarNode\ErrorTrackDecorator;

/**
 * Class for debuging parsing process, it shows how parsing looked like around given point in string
 * Example:
   $parser = new \ParserGenerator\Parser('
        start:       => value.
        value:bool   => ("true"|"false")
             :string => string
             :number => /[+-]?\d+(\.\d+)?/
             :array  => "[" value**"," "]"
             :object => "{" objValue**"," "}".
        objValue:    => key ":" value.
        key:         => string.
        ', ['ignoreWhitespaces' => true,'defaultBranchType' => \ParserGenerator\GrammarNode\BranchFactory::PEG,'trackError' => true]); 
  
   $str = '{"_id": "a","index": {"b":eeee,       ';
   $parser->parse($str);
   echo \ParserGenerator\Backtracer::get($parser)->toString($str, 80, 20);
   
   will print:
   {"_id": "a","index": {"b":eeee,                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  
 0 ^ start                                                                                                                                                                                                                                                                                                               
 0 ^ value                                                                                                                                                                                                                                                                                                               
 1 -^ objValue**","                                                                                                                                                                                                                                                                                                      
12  -          ^ objValue                                                                                                                                                                                                                                                                                                
21             -        ^ value                                                                                                                                                                                                                                                                                          
22                      -^ objValue**","                                                                                                                                                                                                                                                                                 
22                       ^ objValue                                                                                                                                                                                                                                                                                      
26                       -   ^ value     
 */
class Backtracer
{
    public $index;
    protected  $allTraces = [];
    
    public function __construct($index = -1) {
        $this->index = $index;
    }
    
    /**
     * method for easy retriving backtrace from parser
     * 
     * @param \ParserGenerator\Parser $parser
     * @return \ParserGenerator\Backtracer
     */
    public static function get(Parser $parser): Backtracer
    {
        $error      = $parser->getException();
        $index      = (int) $error->getIndex();
        $backtracer = new static($index);
        $nParser    = new Parser($parser->grammarSource, ['parser' => null, 'backtracer' => $backtracer] + $parser->options);
        $nParser->parse($parser->lastParsed);
        return $backtracer;
    }
    
    /**
     * method collecting backtraces
     * 
     * @param array $backtrace
     */
    public function addBacktrace($backtrace)
    {
        $traces = [];
        foreach($backtrace as $singleBacktrace) {
            $object = $singleBacktrace['object'] ?? null;
            if ($object instanceof NodeInterface && !($object instanceof ErrorTrackDecorator)) {
                $traces[] = [
                    'node' => $object,
                    'index' => (int) $singleBacktrace['args'][1],
                ];
            }
            
            //we don't want negative check path
            if ($object instanceof Extension\ItemRestrictions\ItemRestrictionNot) {
                return ;
            }
        }
        
        $prevTrace = null;
        $result = [];
        $resultStr = '';
        foreach(array_reverse($traces) as $trace) {
            $node = $trace['node'];
            
            if (isset($prevTrace)) { 
                if ($trace === $prevTrace) {
                   continue;
                }
                
                $prevNode = $prevTrace['node'];
                
                if ($prevNode instanceof GrammarNode\Choice) {
                    $prevTrace = $trace;
                    continue;
                }

                //remove series autocreated branches
                if ($node instanceof GrammarNode\Branch) {
                    $parser = $node->getParser();
                    if (!isset($parser->grammar[$node->getNodeName()])) {
                        continue;
                    }
                }
                
                // remove consecutive unorders
                if ($prevNode instanceof GrammarNode\Unorder && $node instanceof GrammarNode\Unorder) {
                    continue;
                }

                //remove choce ("a"|"b") is better when splitted into separate lines "a" and "b"
                if ($node instanceof GrammarNode\Choice) {
                    continue;
                }
                
                //we don't want lookaround node
                if ($node instanceof GrammarNode\Lookahead) {
                    $prevTrace = $trace;
                    continue;
                }
                
                //we don't want negative lookaround path
                if ($prevNode instanceof GrammarNode\Lookahead && !$prevNode->isPositive() && $node === $prevNode->getLookaheadNode()) {
                    return ;
                }
            }
            
            //we don't want matching empty string path
            if ((string) $node === '""') {
                return ;
            } 
            
            $result[] = $trace + ['prevIndex' => isset($prevTrace) ? $prevTrace['index'] : 0];
            $resultStr .= chr(1) . $trace['node'] . chr(2) . str_pad($trace['index'], 10, "0", STR_PAD_LEFT);
            //echo $resultStr . " " . $node . " class: " . get_class($node). "\n" ;
            $prevTrace = $trace;
        }
        
        $this->allTraces[$resultStr] = $result;
    }
    
    public function getTraces($onlyFirstAtIndex, $foldSamePaths)
    {
        $allTraces = $this->allTraces;
        ksort($allTraces);
        
        if ($onlyFirstAtIndex) {
            foreach($allTraces as &$traces) {
                $remove = false;
                foreach($traces as $idx => $trace) {
                    if ($remove) {
                        unset($traces[$idx]);
                    } elseif ($trace['index'] === $this->index) {
                        $remove = true;
                    }
                }
            }
            unset($traces);
        }
        
        $traceList = [];
        if ($foldSamePaths) {
            $prevTraces = null;
            foreach($allTraces as $traces) {
                foreach($traces as $i => $trace) {
                    if (empty($prevTraces) || ($prevTraces[$i]['index'] !== $traces[$i]['index'] || (string) $prevTraces[$i]['node'] !== (string) $traces[$i]['node'])) {
                        $traceList[] = $trace;
                        /**
                         * set null for wierd scenario when $prevTraces and $traces realign, for example:
                         * $prevTraces = [index => 5 node => a, index => 10 node => b, index => 10 node => d]
                         * $prevTraces = [index => 5 node => a, index => 10 node => c, index => 10 node => d]
                         * without setting null trace 2 would be added and then node 3 omitted
                         */
                        $prevTraces = null; 
                    }
                }
                $prevTraces = $traces;
            }
        } else {
            foreach($allTraces as $traces) {
                foreach($traces as $trace) {
                    $traceList[] = $trace;
                }
            }
        }
        
        return $traceList;
    }
    
    protected function getHeadlineData($str, $length, $rlength)
    {
        $rstr = strrev(substr($str, 0, $this->index));
        $resultString = strrev(substr($rstr, 0, $length));
        $resultString .= substr($str, $this->index, $rlength);
        $resultString = str_replace(["\n", "\r"], [" ", " "], $resultString);
        $start = max($this->index - $length, 0);
        $range = range($start, min($this->index, $start + $length));
        return [$resultString, array_flip($range)];
    }
    
    public function toString($str, $length = 60, $rlength = 20, $showPreviousIndex = true, $onlyFirstAtIndex = true, $foldSamePaths = true)
    {
        $offset = strlen((string) $this->index);
        list($string, $indexes) = $this->getHeadlineData($str, $length - $offset - 1, $rlength);
        $result = "\n" . str_pad("", $offset + 1) . $string . "\n";
        foreach($this->getTraces($onlyFirstAtIndex, $foldSamePaths) as $i => $trace) {
            $index = $trace['index'];
            $prevIndex = $trace['prevIndex'];
            $result .= "\n" . str_pad((string) $index, $offset, ' ', STR_PAD_LEFT) . ' ';
            if (isset($indexes[$index])) {
                if ($prevIndex == $index || !$showPreviousIndex) {
                    $line = '';    
                } elseif (isset($indexes[$prevIndex])) {
                    $line = str_pad('-', $indexes[$prevIndex] + 1, ' ', STR_PAD_LEFT);
                } else {
                    $line = '<';
                }
                $line = str_pad($line, $indexes[$index], ' ') . '^';
                
                if ($indexes[$index] == 0) {
                    $line = '^';
                }
            } else {
                $line = '<<';
            }
            $result .= $line . ' ' . substr((string) $trace['node'], 0, $length + $rlength - 1 - strlen($line) - $offset);
        }
        
        return $result . "\n";
    }
}

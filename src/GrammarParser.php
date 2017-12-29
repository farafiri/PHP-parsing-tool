<?php declare(strict_types=1);

namespace ParserGenerator;

use ParserGenerator\Extension\Choice;
use ParserGenerator\Extension\Integer;
use ParserGenerator\Extension\ItemRestrictions;
use ParserGenerator\Extension\Lookahead;
use ParserGenerator\Extension\ParametrizedNode;
use ParserGenerator\Extension\Regex;
use ParserGenerator\Extension\RuleCondition;
use ParserGenerator\Extension\Series;
use ParserGenerator\Extension\StringObject;
use ParserGenerator\Extension\Text;
use ParserGenerator\Extension\TextNode;
use ParserGenerator\Extension\Time;
use ParserGenerator\Extension\Unorder;
use ParserGenerator\Extension\WhiteCharactersContext;
use ParserGenerator\GrammarNode\ErrorTrackDecorator;

class GrammarParser
{
    static public $defaultPlugins = [];
    static protected $instance = null;
    protected $plugins = [];
    protected $parserSchouldBeRefreshed = true;
    protected $parser = null;

    public function __construct()
    {
        $this->addDefaultPlugins();
        $this->loadPlugins();
    }

    public function addDefaultPlugins()
    {
        // Note: load order does matter
        static::$defaultPlugins[] = new ItemRestrictions();
        static::$defaultPlugins[] = new TextNode();
        static::$defaultPlugins[] = new Regex();
        static::$defaultPlugins[] = new StringObject();
        static::$defaultPlugins[] = new WhiteCharactersContext();
        static::$defaultPlugins[] = new Integer();
        static::$defaultPlugins[] = new RuleCondition();
        static::$defaultPlugins[] = new Lookahead();
        static::$defaultPlugins[] = new Time();
        static::$defaultPlugins[] = new Unorder();
        static::$defaultPlugins[] = new Series();
        static::$defaultPlugins[] = new Choice();
        static::$defaultPlugins[] = new Text();
        static::$defaultPlugins[] = new ParametrizedNode();
    }

    public function loadPlugins()
    {
        foreach (self::$defaultPlugins as $plugin) {
            $this->addPlugin($plugin);
        }
    }

    public function addPlugin($plugin)
    {
        $this->plugins[] = $plugin;
        $this->parserSchouldBeRefreshed = true;
    }

    static public function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function buildGrammar($grammarStr, $options = [])
    {
        $grammar = [];
        $parsedGrammar = $this->getParser()->parse($grammarStr);

        if ($parsedGrammar === false) {
            throw new Exception("Given grammar is incorrect:\n" . $this->getParser()->getErrorString($grammarStr));
        }
        $parsedGrammar->refreshOwners();

        $grammarBranches = $parsedGrammar->findAll('grammarBranch');

        foreach ($grammarBranches as $grammarBranch) {
            if ($grammarBranch->getDetailType() === 'standard') {
                $branchName = (string)$grammarBranch->findFirst('branchName');
                $branchTypeNodeStr = (string)$grammarBranch->findFirst('branchType');
                $branchType = $branchTypeNodeStr ? substr($branchTypeNodeStr, 1, -1) : $options['defaultBranchType'];
                $grammar[$branchName] = \ParserGenerator\GrammarNode\BranchFactory::createBranch($branchType,
                    $branchName);
            } else {
                foreach ($this->plugins as $plugin) {
                    $grammar = $plugin->createGrammarBranch($grammar, $grammarBranch, $this, $options);
                }
            }
        }

        foreach ($this->plugins as $plugin) {
            $grammar = $plugin->modifyBranches($grammar, $parsedGrammar, $this, $options);
        }

        foreach ($grammarBranches as $grammarBranch) {
            if ($grammarBranch->getDetailType() === 'standard') {
                $branchName = (string)$grammarBranch->findFirst('branchName');
                $rules = [];
                foreach ($grammarBranch->findAll('rule') as $rule) {
                    $buildRule = $this->buildRule($grammar, $rule, $options);
                    $ruleName = (string)$rule->findFirst('ruleName');
                    if ($ruleName) {
                        $rules[$ruleName] = $buildRule;
                    } else {
                        $rules[] = $buildRule;
                    }
                }

                $grammar[$branchName]->setNode($rules);
            } else {
                foreach ($this->plugins as $plugin) {
                    $grammar = $plugin->fillGrammarBranch($grammar, $grammarBranch, $this, $options);
                }
            }
        }

        if ($options['parser']) {
            foreach ($grammar as $node) {
                if ($node instanceof \ParserGenerator\ParserAwareInterface) {
                    $node->setParser($options['parser']);
                }
            }
        }

        return $grammar;
    }

    public function getParser()
    {
        if ($this->parserSchouldBeRefreshed) {
            $this->generateNewParser();
            $this->parserSchouldBeRefreshed = false;
        }

        return $this->parser;
    }

    protected function buildBranchNameNode()
    {
        $restrictedWords = ['or', 'and', 'contain', 'is', 'text', 'string'];

        $restrictedWordsGrammarNode = [];
        foreach ($restrictedWords as $restrictedWord) {
            $restrictedWordsGrammarNode[] = new \ParserGenerator\Extension\ItemRestrictions\Is(new \ParserGenerator\GrammarNode\TextS($restrictedWord));
        }

        $q = new \ParserGenerator\GrammarNode\ItemRestrictions(
            new \ParserGenerator\GrammarNode\Regex('/[A-Za-z_][0-9A-Za-z_]*/', true),
            new \ParserGenerator\Extension\ItemRestrictions\ItemRestrictionNot(
                new \ParserGenerator\Extension\ItemRestrictions\ItemRestrictionOr($restrictedWordsGrammarNode)
            ));

        return $q;
    }

    protected function generateNewParser()
    {
        $stdGrammarGrammar = [
            'start' => [[':grammarBranches']],
            'grammarBranches' => [
                'notLast' => [':grammarBranch', ':comments', ':/\./', ':grammarBranches'],
                'last' => [':grammarBranch', ':comments', ':/\.?/', ":comments"],
            ],
            'grammarBranch' => ['standard' => [':comments', ':branchName', ':branchType', ':rules']],
            'branchType' => [[''], ['(full)'], ['(naive)'], ['(PEG)']],
            'rules' => [
                'last' => [':rule'],
                'notLast' => [':rule', ':rules'],
            ],
            'rule' => ['standard' => [':comments', ':/:/', ':ruleName', ':/=>|:=/', ':sequence']],
            'ruleName' => [[':/([A-Za-z_][0-9A-Za-z_]*)?/']],
            'sequence' => [
                'last' => [':commentSequenceItem'],
                'notLast' => [':commentSequenceItem', ':sequence'],
            ],
            'comments' => [[':comment', ':comments'], ['']],
            'comment' => [[':/\/(\*+)[^*](\s|.)*?\2\//']],
            'commentSequenceItem' => [[':comments', ':sequenceItem']],
            'sequenceItem' => [ /* rule for branches is added after plugin initalizing because it should have lowest priority */],
        ];

        $grammarGrammar = $stdGrammarGrammar;
        foreach ($this->plugins as $plugin) {
            $grammarGrammar = $plugin->extendGrammar($grammarGrammar);
        }

        $grammarGrammar['branchName'] = [[$this->buildBranchNameNode()]];
        $grammarGrammar['sequenceItem']['branch'] = ':branchName';

        $this->parser = new \ParserGenerator\Parser($grammarGrammar);
    }

    public function buildRule(&$grammar, $rule, $options)
    {
        if ($rule->getDetailType() === 'standard') {
            $sequence = [];
            foreach ($rule->findAll('sequenceItem') as $sequenceItem) {
                $sequenceItemNode = $this->buildSequenceItem($grammar, $sequenceItem, $options);
                if (count($sequence)) {
                    $sequenceItemNode = new ErrorTrackDecorator($sequenceItemNode);
                }
                $sequence[] = $sequenceItemNode;
            }
            return $sequence;
        } else {
            $newSequence = null;

            foreach ($this->plugins as $plugin) {
                if ($newSequence = $plugin->buildSequence($grammar, $rule, $this, $options)) {
                    break;
                }
            }

            if ($newSequence) {
                return $newSequence;
            } else {
                throw new Exception('Rule type [' . $rule->getDetailType() . '] added but not supported');
            }
        }
    }

    public function buildSequenceItem(&$grammar, $sequenceItem, $options)
    {
        $newSequenceItem = null;
        foreach ($this->plugins as $plugin) {
            if ($newSequenceItem = $plugin->buildSequenceItem($grammar, $sequenceItem, $this, $options)) {
                break;
            }
        }

        if ($newSequenceItem) {
            return $newSequenceItem;
        } else {
            if ($sequenceItem->getDetailType() === 'branch') {
                $branchName = (string)$sequenceItem;
                if (empty($grammar[$branchName])) {
                    throw new Exception("Grammar definition error: Undefined branch [$branchName]");
                }

                return $grammar[$branchName];
            } else {
                throw new Exception('Sequence item type [' . $sequenceItem->getDetailType() . '] added but not supported');
            }
        }
    }
}

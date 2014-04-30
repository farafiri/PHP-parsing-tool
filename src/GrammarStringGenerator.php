<?php

class GrammarStringGenerator
{
    static $finishPath = array();

    static function generate($node, $length)
    {
        if ($node instanceOf \ParserGenerator\GrammarNode\Text) {
            return new \ParserGenerator\SyntaxTreeNode\Leaf($node->getString());
        } elseif ($node instanceOf \ParserGenerator\GrammarNode\Regex) {
            return new \ParserGenerator\SyntaxTreeNode\Leaf(\ParserGenerator\RegexUtil::getInstance()->generateString($node->getRegex()));
        } elseif ($node instanceOf \ParserGenerator\GrammarNode\PredefinedString) {
            return new \ParserGenerator\SyntaxTreeNode\PredefinedString("Lorem ipsum");
        } elseif ($node instanceof \ParserGenerator\GrammarNode\Branch) {
            return static::generateForBranch($node, $length);
        }
    }


    static function generateForBranch($inputNode, $length)
    {
        $nodeSets = array(array(), array(), array());
        foreach ($inputNode->getNode() as $sequenceIndex => $sequence) {
            $subBranches = 0;
            foreach ($sequence as $node) {
                if ($node instanceof \ParserGenerator\GrammarNode\Branch) {
                    $subBranches++;
                }
            }

            $group = min($subBranches, 2);

            //echo "<br/>\n" . $inputNode->getNodeName() . ' -> ' . static::getFinishPath($inputNode);
            if ($sequenceIndex == static::getFinishPath($inputNode)) {
                $group = 0;
            } elseif ($group == 0) {
                $group = 1;
            }

            $nodeSets[$group][$sequenceIndex] = $sequence;
        }

        $setRand = array(
            (max((12 - $length) * rand(40, 90), 1)) * count($nodeSets[0]),
            (($length > 2) ? rand(50, 100) * rand(2, 5) : 1) * count($nodeSets[1]),
            (($length > 5) ? rand(8, 16) * rand(1, 5) * $length : 1) * count($nodeSets[2]));

        $set = static::randIndex($setRand);

        $sequenceIndex = array_rand($nodeSets[$set]);
        $sequence = $nodeSets[$set][$sequenceIndex];

        $subnodes = array();
        $branches = array();
        foreach ($sequence as $index => $grammarNode) {
            if ($node instanceof GrammarNodeLeaf) {
                $node = $grammarNode->generate(rand(3, 12) * rand(1, 2) / 10 * $leafLength);
                $subnodes[$index] = $node;
                $length -= strlen((string)$node);
            } else {
                $branches[$index] = rand(10, 1000) * rand(10, 1000);
            }
        }

        $branches = static::normalizeArray($branches);
        $delta = 0;
        foreach ($branches as $index => $lengthMultipler) {
            $expectedLength = $length * $lengthMultipler + $delta;
            $node = static::generate($sequence[$index], $expectedLength);
            $delta = $expectedLength - strlen((string)$node);

            $subnodes[$index] = $node;
        }

        ksort($subnodes);

        return new \ParserGenerator\SyntaxTreeNode\Branch($inputNode->getNodeName(), $sequenceIndex, $subnodes);
    }


    static private function normalizeArray($values)
    {
        $sum = array_sum($values);
        return array_map(function ($value) use ($sum) {
            return $value / $sum;
        }, $values);
    }


    static private function randIndex($values)
    {
        $sum = array_sum($values);
        $rnd = rand(0, 1000000) / 1000000 * $sum;

        $sum = 0;
        foreach ($values as $index => $value) {
            $sum += $value;
            if ($sum >= $rnd) {
                return $index;
            }
        }
    }


    static protected function getFinishPath($inputNode)
    {
        if (!isset(self::$finishPath[spl_object_hash($inputNode)])) {
            $finishPath = array();
            $collectionNodes = static::getSubnodesCollection($inputNode);

            while (count($finishPath) < count($collectionNodes)) {
                foreach ($collectionNodes as $collectionNode) {
                    if (isset($finishPath[spl_object_hash($collectionNode)])) {
                        continue;
                    }

                    foreach ($collectionNode->getNode() as $sequenceIndex => $sequence) {
                        $isFinishingSequence = true;
                        foreach ($sequence as $node) {
                            if ($node instanceof \ParserGenerator\GrammarNode\Branch && !isset($finishPath[spl_object_hash($node)])) {
                                $isFinishingSequence = false;
                            }
                        }

                        if ($isFinishingSequence) {
                            $finishPath[spl_object_hash($collectionNode)] = $sequenceIndex;
                            break;
                        }
                    }
                }
            }

            self::$finishPath += $finishPath;
        }

        //foreach($finishPath as $index => $val) {
        //  echo "<br/>\n" . $collectionNodes[$index]->getNodeName() . ' -> ' . $val;
        //}

        //die();

        return self::$finishPath[spl_object_hash($inputNode)];
    }

    static protected function getSubnodesCollection($inputNode)
    {
        $collection = array();
        $collectionToAdd = array(spl_object_hash($inputNode) => $inputNode);
        while (count($collectionToAdd)) {
            $collection += $collectionToAdd;
            $collectionToIterate = $collectionToAdd;
            $collectionToAdd = array();

            foreach ($collectionToIterate as $item) {
                foreach ($item->getNode() as $sequence) {
                    foreach ($sequence as $node) {
                        if ($node instanceof \ParserGenerator\GrammarNode\Branch) {
                            if (!isset($collection[spl_object_hash($node)])) {
                                $collectionToAdd[spl_object_hash($node)] = $node;
                            }
                        }
                    }
                }
            }
        }

        return $collection;
    }
}
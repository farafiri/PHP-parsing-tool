<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 01.05.14
 * Time: 10:52
 */

namespace ParserGenerator\SyntaxTreeNode;


class LeafTime extends Leaf
{
    protected $timeData;

    public function __construct($content, $afterContent, $timeData)
    {
        $this->content = $content;
        $this->afterContent = $afterContent;
        $this->timeData = $timeData;
    }

    public function getValue()
    {
        $result = new \DateTime();
        $result->setDate($this->timeData['year'], $this->timeData['month'], $this->timeData['day']);
        $result->setTime($this->timeData['hour'] ?: 0, $this->timeData['minute'] ?: 0, $this->timeData['second'] ?: 0);

        return $result;
    }
} 
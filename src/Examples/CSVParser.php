<?php declare(strict_types=1);
/**
 * Created by JetBrains PhpStorm.
 * User: Rafał
 * Date: 13.05.13
 * Time: 20:20
 * To change this template use File | Settings | File Templates.
 */

namespace ParserGenerator\Examples;

use ParserGenerator\Exception;

class CSVParser extends \ParserGenerator\Parser
{
    public function __construct()
    {
        parent::__construct($this->getCSVDefinition());
    }

    protected function getCSVDefinition()
    {
        return '
            start:        => line*lineSeparator.
            lineSeparator:=> /(\r\n|\n\r|\r|\n)/.
            line:         => value*",".
            value:        => /[ \t]*/ string/simple /[ \t]*/
                         :=> /[^\r\n,"]*/.
        ';
    }

    public function parseCSV($string)
    {
        $csvRaw = $this->parse($string);

        if ($csvRaw) {
            $data = [];
            foreach ($csvRaw->getSubnode(0)->getMainNodes() as $csvLine) {
                $line = [];
                foreach ($csvLine->getSubnode(0)->getMainNodes() as $csvValue) {
                    if ($csvValue->getDetailType() == 0) {
                        $line[] = $csvValue->getSubnode(1)->getValue();
                    } else {
                        $line[] = (string)$csvValue;
                    }
                }

                $data[] = $line;
            }

            return $data;
        } else {
            throw new Exception('given string is not proper CSV format');
        }
    }
}

<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

use ParserGenerator\Exception;

class BranchFactory
{
    const NAIVE = 'naive';
    const FULL = 'full';
    const PEG = 'PEG';
    const PEG_NO_CACHE = 'PEGNC';

    public static function createBranch($branchType, $name): Branch
    {
        switch ($branchType) {
            case self::NAIVE:
                return new NaiveBranch($name);
            case self::FULL:
                return new Branch($name);
            case self::PEG:
                return new PEGBranch($name);
            case self::PEG_NO_CACHE:
                return new PEGNoCacheBranch($name);
            default:
                throw new Exception("Unknown branch type: $branchType");
        }
    }
    
    public static function getBranchType(Branch $branch): string
    {   
        if ($branch instanceof NaiveBranch) {
            return self::NAIVE;
        } elseif ($branch instanceof PEGBranch) {
            return self::PEG;
        } elseif ($branch instanceof PEGNoCacheBranch) {
            return self::PEG_NO_CACHE;
        } else {
            return self::FULL;
        }
    }
}

<?php

namespace Cyclomatic;

class Cyc
{
    public function both($a, $b)
    {
        if ($a && $b) {
            return 1;
        }
        return 0;
    }

    public function keyword($a, $b)
    {
        if ($a and $b) {
            return 1;
        }
        return 0;
    }

    public function orSymbol($a, $b)
    {
        if ($a || $b) {
            return 1;
        }
        return 0;
    }

    public function orKeyword($a, $b)
    {
        if ($a or $b) {
            return 1;
        }
        return 0;
    }

    public function withDefault($a)
    {
        switch ($a) {
            case 1:
                return 1;
            case 2:
                return 2;
            default:
                return 0;
        }
    }
}

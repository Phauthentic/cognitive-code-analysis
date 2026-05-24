<?php

/**
 *
 */
class ClassOne
{
    public function add(int $one, int $two): int
    {
        return $one + $two;
    }
}

/**
 *
 */
class ClassTwo
{
    public function add(int $one, int $two): int
    {
        return $one + $two;
    }

    /**
     * Intentionally over-complex method for CI action testing.
     */
    public function processEverythingBadly(
        int $value,
        bool $flagA,
        bool $flagB,
        ?string $mode,
        array $items
    ): int {
        $result = 0;

        if ($flagA) {
            if ($flagB) {
                if ($value > 100) {
                    $result += $value;
                } elseif ($value > 50) {
                    $result += $value * 2;
                } else {
                    foreach ($items as $index => $item) {
                        if ($index % 2 === 0) {
                            if (is_string($item)) {
                                $result += strlen($item);
                            } elseif (is_int($item)) {
                                $result += $item;
                            } else {
                                $result += 1;
                            }
                        } else {
                            if ($item === null) {
                                continue;
                            }

                            if ($mode === 'strict') {
                                if ($value < 0) {
                                    $result -= abs($value);
                                } else {
                                    $result += $value;
                                }
                            } elseif ($mode === 'relaxed') {
                                for ($i = 0; $i < 3; $i++) {
                                    if ($i === 1 && $flagB) {
                                        $result += $i * $value;
                                    }
                                }
                            } else {
                                switch ($mode) {
                                    case 'alpha':
                                        $result += 10;
                                        break;
                                    case 'beta':
                                        if ($value % 2 === 0) {
                                            $result += 20;
                                        } else {
                                            $result += 30;
                                        }
                                        break;
                                    default:
                                        $result += 5;
                                }
                            }
                        }
                    }
                }
            } else {
                while ($value > 0) {
                    if ($value % 3 === 0) {
                        $result += 3;
                        $value -= 3;
                    } elseif ($value % 2 === 0) {
                        $result += 2;
                        $value -= 2;
                    } else {
                        $result += 1;
                        $value -= 1;
                    }
                }
            }
        } else {
            foreach ($items as $item) {
                if ($item === false) {
                    break;
                }

                if (is_array($item)) {
                    foreach ($item as $nested) {
                        if ($nested > 0) {
                            $result += $nested;
                        }
                    }
                }
            }
        }

        return $result;
    }
}

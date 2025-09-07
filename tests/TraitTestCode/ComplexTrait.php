<?php

trait ComplexTrait
{
    public function complexMethod1(): int
    {
        $result = 0;
        $items = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        
        foreach ($items as $item) {
            if ($item % 2 === 0) {
                if ($item > 5) {
                    if ($item === 8) {
                        $result += $item * 2;
                    } else {
                        $result += $item;
                    }
                } else {
                    $result += $item / 2;
                }
            } else {
                if ($item < 5) {
                    $result += $item;
                } else {
                    $result += $item * 3;
                }
            }
        }
        
        return $result;
    }
    
    public function complexMethod2(): string
    {
        $result = '';
        $data = ['a', 'b', 'c', 'd', 'e'];
        
        for ($i = 0; $i < count($data); $i++) {
            switch ($data[$i]) {
                case 'a':
                    $result .= 'alpha';
                    break;
                case 'b':
                    $result .= 'beta';
                    break;
                case 'c':
                    $result .= 'gamma';
                    break;
                case 'd':
                    $result .= 'delta';
                    break;
                case 'e':
                    $result .= 'epsilon';
                    break;
                default:
                    $result .= 'unknown';
            }
            
            if ($i < count($data) - 1) {
                $result .= '-';
            }
        }
        
        return $result;
    }
    
    public function complexMethod3(): array
    {
        $result = [];
        $numbers = range(1, 20);
        
        while (!empty($numbers)) {
            $current = array_shift($numbers);
            
            try {
                if ($current % 3 === 0 && $current % 5 === 0) {
                    $result[] = 'fizzbuzz';
                } elseif ($current % 3 === 0) {
                    $result[] = 'fizz';
                } elseif ($current % 5 === 0) {
                    $result[] = 'buzz';
                } else {
                    $result[] = $current;
                }
            } catch (Exception $e) {
                $result[] = 'error';
            }
        }
        
        return $result;
    }
}

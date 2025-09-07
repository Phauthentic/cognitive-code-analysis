<?php

trait TestTrait
{
    public function traitMethod1(): void
    {
        if (true) {
            if (false) {
                echo "nested";
            }
        }
        
        for ($i = 0; $i < 10; $i++) {
            if ($i % 2 === 0) {
                echo "even";
            } else {
                echo "odd";
            }
        }
    }
    
    public function traitMethod2(): int
    {
        $result = 0;
        $items = [1, 2, 3, 4, 5];
        
        foreach ($items as $item) {
            if ($item > 3) {
                $result += $item;
            }
        }
        
        return $result;
    }
}

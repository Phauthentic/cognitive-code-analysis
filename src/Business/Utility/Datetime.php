<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Utility;

use DateTime as PHPDateTime;

/**
 *
 */
class Datetime extends PHPDateTime
{
    public static ?string $fixedDate = null;

    public function __construct()
    {
        if (self::$fixedDate !== null) {
            parent::__construct(self::$fixedDate);
            return;
        }

        parent::__construct();
    }
}

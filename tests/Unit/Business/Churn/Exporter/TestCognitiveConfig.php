<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Exporter;

/**
 * Test-specific config class for testing
 */
class TestCognitiveConfig
{
    public array $customExporters = [];

    public function __construct()
    {
        $this->customExporters = [];
    }
}

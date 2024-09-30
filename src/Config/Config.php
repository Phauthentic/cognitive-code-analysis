<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Config;

class Config
{
    public CognitiveConfig $cognitive;
    public HalsteadConfig $halstead;

    public function __construct(CognitiveConfig $cognitive, HalsteadConfig $halstead)
    {
        $this->cognitive = $cognitive;
        $this->halstead = $halstead;
    }
}

<?php

require 'vendor/autoload.php';

use Phauthentic\CodeQualityMetrics\Command\CognitiveMetricsCommand;
use Phauthentic\CodeQualityMetrics\Command\HalsteadMetricsCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new CognitiveMetricsCommand());
$application->add(new HalsteadMetricsCommand());

$application->run();

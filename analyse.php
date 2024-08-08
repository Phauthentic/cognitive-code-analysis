<?php

require 'vendor/autoload.php';

use Phauthentic\CodeQuality\Command\ParseMetricsCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new ParseMetricsCommand());

$application->run();

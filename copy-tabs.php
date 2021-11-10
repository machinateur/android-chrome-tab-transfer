<?php

require __DIR__.'/vendor/autoload.php';

use App\Command\CopyTabsCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add($command = new CopyTabsCommand());
$application->setDefaultCommand($command->getName());
$application->run();

<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CopyTabsCommand
 * @package App\Command
 */
class CopyTabsCommand extends Command
{
    protected static $defaultName = 'copy-tabs';

    protected function configure(): void
    {
        $this->setDescription('Outputs "Hello World"');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello World');

        //adb forward tcp:9222 localabstract:chrome_devtools_remote
        //wget wget -O tabs.json http://localhost:9222/json/list
        //node ./transfer-import.js

        return Command::SUCCESS;
    }
}

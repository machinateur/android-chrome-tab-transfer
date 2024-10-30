<?php
/*
 * MIT License
 *
 * Copyright (c) 2021-2024 machinateur
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace Machinateur\ChromeTabTransfer\Command;

use Machinateur\ChromeTabTransfer\Driver\AbstractDriver;
use Machinateur\ChromeTabTransfer\Driver\AndroidDebugBridge;
use Machinateur\ChromeTabTransfer\Exception\CopyTabsException;
use Machinateur\ChromeTabTransfer\File\AbstractFileTemplate;
use Machinateur\ChromeTabTransfer\Service\CopyTabsService;
use Machinateur\ChromeTabTransfer\Shared\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCopyTabsCommand extends Command
{
    public const DEFAULT_FILE    = 'tabs.json';
    public const DEFAULT_PORT    = AndroidDebugBridge::DEFAULT_PORT;
    public const DEFAULT_TIMEOUT = AndroidDebugBridge::DEFAULT_TIMEOUT;

    protected readonly \DateTimeInterface $date;

    protected readonly CopyTabsService $service;

    public function __construct(string $name)
    {
        $this->date = new \DateTimeImmutable();

        parent::__construct("copy-tabs:{$name}");

        $this->service = new CopyTabsService();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'The relative filepath to write. Only the filename is actually considered! The `--date` / `--no-date` flag applies as well.', self::DEFAULT_FILE)
            ->addOption('date', 'd', InputOption::VALUE_NEGATABLE, "Whether to add the date `{$this->date->format(AbstractFileTemplate::DATE_FORMAT)}` suffix to the filename. Active by Default.", true)
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'The port to forward requests through.', self::DEFAULT_PORT)
            ->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'The network timeout for the download request (at last 10 seconds).', self::DEFAULT_TIMEOUT)
            ->addOption('skip-check', null, InputOption::VALUE_NONE, 'Skip the check for required dependencies ony your system will be performed.')
        ;
    }

    /**
     * Call the {@see CheckEnvironment} command internally.
     *
     * @return int{0,2}
     */
    protected function performEnvironmentCheck(Console $console): int
    {
        $command = $this->getApplication()
            ->get(CheckEnvironment::NAME);

        if ( ! $command instanceof CheckEnvironment) {
            throw new \LogicException(\sprintf('The application must contain the %s command.', $console));
        }

        return $command->execute(new ArrayInput([
            // TODO: Set options if necessary.
        ]), $console);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = new Console($input, $output);

        if ( ! $this->getArgumentSkipCheck($console)) {
            $return = $this->performEnvironmentCheck($console);

            if (Command::SUCCESS !== $return) {
                return $return;
            }
        } else {
            $console->note('Skipping environment check.');
        }

        try {
            $tabs = $this->service->run(
                $this->getDriver($console)
                    ->setConsole($console)
                    ->setFileDate(
                        $this->getArgumentDate($console)
                    )
            );
        } catch (CopyTabsException $exception) {
            $console->error($exception->getMessage());

            return Command::FAILURE;
        }

        $numberOfTabsTransferred = \count($tabs);
        if (!$numberOfTabsTransferred) {
            $console->note('No tabs were copied from the device.');
        }

        $console->success(\sprintf('Successfully copied %d tabs from the device.', ));
        $console->newLine();

        // With `-vvv` parameter, print the fetched tabs to stdout. Only if there were tabs downloaded, else skip this step.
        if (0 < $numberOfTabsTransferred && $output->isDebug()) {
            $table = $console->createTable()
                ->setHeaders(['Title', 'Url']);

            foreach ($tabs as $tab) {
                $title = $tab['title'] ?: '--';
                $url   = $tab['url'];

                $table->addRow([$title, $url]);
            }
            unset($tab, $title, $url);

            $table->render();
            $console->newLine();
        }

        return Command::SUCCESS;
    }

    abstract public function getDriver(Console $console): AbstractDriver;

    protected function getArgumentFile(Console $console): string
    {
        return $console->input->getArgument('file');
    }

    protected function getArgumentDate(Console $console): ?\DateTimeInterface
    {
        return $console->input->getOption('date')
            ? $this->date
            : null;
    }

    protected function getArgumentPort(Console $console): int
    {
        $argumentPort = (int)$console->input->getOption('port');

        if (0 >= $argumentPort) {
            $argumentPort = self::DEFAULT_PORT;

            $console->warning("Invalid port given, default to {$argumentPort}.");
        }

        return $argumentPort;
    }

    protected function getArgumentDebug(Console $console): bool
    {
        return $console->output->isDebug();
    }

    protected function getArgumentTimeout(Console $console): int
    {
        $argumentTimeout = (int)$console->input->getOption('timeout');

        if (10 > $argumentTimeout) {
            $argumentTimeout = self::DEFAULT_TIMEOUT;

            $console->warning("Invalid timeout given, default to {$argumentTimeout}s.");
        }

        return $argumentTimeout;
    }

    protected function getArgumentSkipCheck(Console $console): bool
    {
        return (bool)$console->input->getOption('skip-check');
    }
}

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
use Machinateur\ChromeTabTransfer\Driver\RestoreTabsDriver;
use Machinateur\ChromeTabTransfer\Exception\CopyTabsException;
use Machinateur\ChromeTabTransfer\Exception\ReopenTabsException;
use Machinateur\ChromeTabTransfer\File\ReopenScriptFile;
use Machinateur\ChromeTabTransfer\Platform;
use Machinateur\ChromeTabTransfer\Shared\AccessibleInput;
use Machinateur\ChromeTabTransfer\Shared\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command providing tab restoration capabilities.
 *
 * The command aims to replace the `copy-tabs.sh` the reopen script (`reopen.cmd`/`reopen.sh`)
 *  and operates based on the `tabs.json`.
 *
 * @see ReopenScriptFile
 */
class ReopenTabs extends AbstractCopyTabsCommand
{
    public const NAME = 'reopen-tabs';

    public const DEFAULT_DRIVER = 'android';

    public function __construct()
    {
        parent::__construct('restore');
    }

    protected function configure(): void
    {
        parent::configure();

        $definition = $this->getDefinition();
        // Here we use some black magic to extract a reference to the `$description` property of the InputOption.
        $argumentPortDescription = & Platform::extractPropertyReference($definition->getArgument('file'), 'description');
        // Next we simply write to that reference - et-voilà.
        $argumentPortDescription = 'The relative filepath to read. This has to point directly to the `tabs.json` file.';

        // Some more magic to remove the "date" option entirely. I'm just lazy and there would be duplicate code.
        $definitionOptions = & Platform::extractPropertyReference($definition, 'options');
        unset($definitionOptions['date']);

        $this
            ->setName(self::NAME)
            ->setDescription('Restore tabs to your phone\'s Chrome browser.')
            ->addOption('driver', 'i', InputOption::VALUE_REQUIRED, 'The driver to use for restoration. As of now, only `android` is supported.', self::DEFAULT_DRIVER)
            // TODO: Add `--call` `-c` `some_script.php` option.
        ;

        // TODO: Validate supplying options like `--socket` here would even work. In theory, the ArgvInput/ArrayInput will maintain the initial tokens/parameters.
    }

    private ?AbstractCopyTabsCommand $command = null;

    protected function execute(InputInterface $input, OutputInterface $output, ?AbstractCopyTabsCommand $command = null): int
    {
        $console  = new Console($input, $output);

        $this->command = $command;

        // Run the inner logic. This is extracted to a separate method,
        //  to allow for easier changes in resulting output of the specific driver command.
        try {
            $tabs = $this->executeDriver($console);
        } catch (CopyTabsException $exception) {
            $console->error($exception->getMessage());

            return Command::FAILURE;
        }

        $numberOfTabsTransferred = \count($tabs);
        if (!$numberOfTabsTransferred) {
            $console->note('No tabs were transferred to the device.');
        }

        $console->success("Successfully transferred {$numberOfTabsTransferred} tabs to the device.");
        $console->newLine();

        // With `-vvv` parameter, print the fetched tabs to stdout. Only if there were tabs downloaded, else skip this step.
        if (0 < $numberOfTabsTransferred && $output->isDebug()) {
            $table = $console->createTable()
                ->setHeaders(['Title', 'URL', 'Transferred']);

            foreach ($tabs as $tab) {
                $title       = $tab['title'] ?: '--';
                $url         = $tab['url'];
                $transferred = $tab['transferred'] ?? false;

                $table->addRow([$title, $url, $transferred ? 'yes' : 'no']);
            }
            unset($tab, $title, $url, $transferred);

            $table->render();
            $console->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * @throws ReopenTabsException
     */
    public function getDriver(Console $console): AbstractDriver
    {
        $command = $this->getDriverCommand($console);

        return new RestoreTabsDriver(
            $this->getArgumentFile($console),
            $this->getArgumentPort($console),
            $this->getArgumentDebug($console),
            $this->getArgumentTimeout($console),
            $command->getDriver(
                $command->getDefaultConsole($console)
            ),
        );
    }

    /**
     * @throws ReopenTabsException
     */
    protected function getDriverCommand(Console $console): AbstractCopyTabsCommand
    {
        $command = $this->command ?? null;

        // When no `$command` is provided with the call (i.e. direct invocation via CLI).
        if ( ! $command instanceof AbstractCopyTabsCommand) {
            $driverName = $this->getArgumentDriver($console);

            $console->writeln("No command provided.", OutputInterface::VERBOSITY_VERY_VERBOSE);

            // Only check the specified driver, if the `--driver` (`-i`) option was specified.
            if (null !== $driverName) {
                $console->writeln("Driver name specified: {$driverName}.", OutputInterface::VERBOSITY_VERY_VERBOSE);

                if ($command instanceof CopyTabsFromIphone) {
                    $console->writeln("<fg=black;bg=yellow>The `iphone` driver is not yet supported.</>");

                    throw ReopenTabsException::forUnsupportedDriver($driverName);
                }

                $command = $this->findCommand($console, $driverName);
            }
        }

        if (null === $command) {
            // Should not happen, as a default value is set.
            throw ReopenTabsException::withNoDriverSpecified();
        }

        return $command;
    }

    public function checkCommandEnvironment(Console $console): bool
    {
        try {
            return $this->getDriverCommand($console)
                ->checkCommandEnvironment($console);
        } catch (ReopenTabsException $exception) {
            $console->error($exception->getMessage());
        }

        return false;
    }

    protected function findCommand(Console $console, string $driverName): ?AbstractCopyTabsCommand
    {
        $command = $this->getApplication()
            ->get(CheckEnvironment::NAME);

        if ( ! $command instanceof CheckEnvironment) {
            throw new \LogicException(\sprintf('The application must contain the %s command.', $command->getName()));
        }

        return $command->findCommand($console, $driverName);
    }

    protected function getArgumentDriver(Console $console): ?string
    {
        return $console->input->getOption('driver');
    }

}

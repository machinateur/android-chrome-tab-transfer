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

use Machinateur\ChromeTabTransfer\Driver\DriverEnvironmentCheckInterface;
use Machinateur\ChromeTabTransfer\Shared\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckEnvironment extends Command
{
    public const NAME = 'check-environment';

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Check environment for required dependencies ony your system. Implicitly executed before copying tabs.')
            ->addOption('driver', 'i', InputOption::VALUE_REQUIRED, 'The driver name to use for the check. If not given (default) check for all drivers.');
    }

    protected function execute(InputInterface $input, OutputInterface $output, ?AbstractCopyTabsCommand $command = null): int
    {
        $console    = new Console($input, $output);

        // When no `$command` is provided with the call (i.e. direct invocation via CLI).
        if ( ! $command instanceof AbstractCopyTabsCommand) {
            $driverName = $this->getArgumentDriver($console);

            // Only check the specified driver, if the `--driver` (`-i`) option was specified.
            if (null !== $driverName) {
                $command = $this->findCommand($console, $driverName);
            }
        }

        if ($command instanceof AbstractCopyTabsCommand) {
            return $this->check($console, $command)
                ? Command::SUCCESS
                : Command::FAILURE;
        }

        $result = true;

        // Perform checks on all of them, if no single one is specified (default).
        foreach ($this->getCommands() as $command) {
            if ( ! $command instanceof AbstractCopyTabsCommand) {
                continue;
            }

            $result = $result && $this->check($console, $command);
        }

        return $result
            ? Command::SUCCESS
            : Command::FAILURE;
    }

    protected function check(Console $console, AbstractCopyTabsCommand $command): bool
    {
        // TODO: Add verbose output.
        return $command->checkCommandEnvironment($console);
    }

    protected function findCommand(Console $console, string $driverName): ?AbstractCopyTabsCommand
    {
        // TODO: Add console output.

        $command = \array_reduce($this->getCommands(), static function (?Command $carry, Command $command) use ($driverName) {
            if ( ! $command instanceof AbstractCopyTabsCommand) {
                return $carry;
            }

            if (null === $carry && $command->getDriverName() === $driverName) {
                return $command;
            }

            return $carry;
        });

        if ( ! $command instanceof DriverEnvironmentCheckInterface) {
            if (null === $command) {
                // TODO: Not found error.
            }

            // TODO: No check possible error.
        }

        return $command;
    }

    /**
     * Load all the available commands inside the `copy-tabs` namespace from the application.
     *
     * @return array<Command>
     */
    private function getCommands(string $namespace = 'copy-tabs'): array
    {
        return $this->getApplication()
            ->all('copy-tabs');
    }

    protected function getArgumentDriver(Console $console): ?string
    {
        return $console->input->getOption('driver');
    }
}

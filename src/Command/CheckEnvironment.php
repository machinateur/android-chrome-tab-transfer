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
        $driverName = $this->getArgumentDriver($console);

        if ( ! $command) {
            // TODO: Make sure to not check the application of we get an instance directly as argument.
        }

        if (null === $command) {

        }

        // Load all the available commands inside the `copy-tabs` namespace.
        $commands = $this->getApplication()
            ->all('copy-tabs');

        if (null !== $driverName) {
            // Only check the specified driver.
            $command = \array_reduce($commands, static function (?Command $carry, Command $command) use ($driverName) {
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

            // TODO: Add verbose output.
            $command::checkEnvironment();
        } else {
            // Perform checks on all of them.
            foreach ($commands as $command) {
                // TODO: Add verbose output.
                if ( ! $command instanceof AbstractCopyTabsCommand) {
                    continue;
                }

                $command::checkEnvironment();
            }
        }

        return Command::SUCCESS;
    }

    protected function getArgumentDriver(Console $console): ?string
    {
        return $console->input->getOption('driver');
    }
}

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
use Machinateur\ChromeTabTransfer\Driver\IosWebkitDebugProxy;
use Machinateur\ChromeTabTransfer\Platform;
use Machinateur\ChromeTabTransfer\Shared\Console;
use Symfony\Component\Console\Input\InputOption;

/**
 * - uses {@see IosWebkitDebugProxy} driver
 *
 * - will not support reopen script (for now, until I find a way to open tabs on iOS)
 *   maybe using https://github.com/sirn-se/websocket-php will be an option to control the websocket command directly
 *     - The main issue here is that a script will not be possible then. the reopen.cmd/reopen.sh is only supported for legacy reasons with android users.
 *
 * @see https://github.com/google/ios-webkit-debug-proxy
 */
class CopyTabsFromIphone extends AbstractCopyTabsCommand
{
    public const DEFAULT_WAIT = IosWebkitDebugProxy::DEFAULT_DELAY;

    protected function configure(): void
    {
        parent::configure();

        $definition = $this->getDefinition();
        // Here we use some black magic to extract a reference to the `$description` property of the InputOption.
        $argumentPortDescription = & Platform::extractPropertyReference($definition->getOption('port'), 'description');
        // Next we simply write to that reference - et-voilà.
        $argumentPortDescription = 'The port to forward requests through `ios_webkit_debug_proxy`.';

        $this
            ->setDescription('Transfer tabs from your iPhone\'s Chrome browser.')
            ->addOption('wait', 'w', InputOption::VALUE_REQUIRED, 'The time to wait before starting the download request (between 0 and 60 seconds).', self::DEFAULT_WAIT)
        ;
    }

    /**
     * @noinspection DuplicatedCode
     */
    public function getDriver(Console $console): AbstractDriver
    {
        return new IosWebkitDebugProxy(
            $this->getArgumentFile($console),
            $this->getArgumentPort($console),
            $this->getArgumentDebug($console),
            $this->getArgumentTimeout($console),
            $this->getArgumentWait($console),
        );
    }

    protected function getArgumentWait(Console $console): int
    {
        $argumentWait = (int)$console->input->getOption('wait');

        if (0 >= $argumentWait || 60 < $argumentWait) {
            $argumentWait = self::DEFAULT_WAIT;

            $console->warning("Invalid wait given, default to {$argumentWait}s.");
        }

        return $argumentWait;
    }
}

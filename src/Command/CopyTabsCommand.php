<?php
/*
 * MIT License
 *
 * Copyright (c) 2021-2022 machinateur
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

namespace App\Command;

use JsonException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CopyTabsCommand
 * @package App\Command
 */
class CopyTabsCommand extends Command
{
    /**
     * @var int
     */
    private const DEFAULT_PORT = 9222;

    /**
     * @var string
     */
    private const DEFAULT_SOCKET = 'chrome_devtools_remote';

    /**
     * @var string
     */
    private const DEFAULT_FILE = 'tabs.json';

    /**
     * @var string
     */
    protected static $defaultName = 'copy-tabs';

    protected function configure(): void
    {
        $this
            ->setDescription('A tool to transfer tabs from your android phone to your computer using `adb`.')
            ->addArgument('file', InputArgument::OPTIONAL, 'The relative filepath to write.', self::DEFAULT_FILE)
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'The port to forward requests using `adb`.', self::DEFAULT_PORT)
            ->addOption('socket', 's', InputOption::VALUE_REQUIRED, 'The socket to forward requests using `adb`.', self::DEFAULT_SOCKET);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Check for `adb` command availability:

        if (!$this->isShellCommandAvailable('adb')) {
            $output->writeln('The adb executable is not available!');

            return Command::FAILURE;
        } else {
            $output->writeln('The adb executable is available.');
        }

        // Get `port` argument:

        $argumentPort = (int)$input->getOption('port');

        if (!is_numeric($argumentPort)) {
            $argumentPort = self::DEFAULT_PORT;

            $output->writeln("Invalid port given, default to {$argumentPort}.");
        }

        // Get `socket` argument:

        $argumentSocket = $input->getOption('socket');

        if (!is_string($argumentSocket)) {
            $argumentSocket = self::DEFAULT_SOCKET;

            $output->writeln("Invalid socket given, default to {$argumentSocket}.");
        }

        // Run `adb` command:

        $output->writeln('Running adb command...');

        $output->writeln("> adb -d forward tcp:{$argumentPort} localabstract:{$argumentSocket}");
        $output->write(
            shell_exec("adb -d forward tcp:{$argumentPort} localabstract:{$argumentSocket}")
        );

        // Download tabs:

        $url = "http://localhost:{$argumentPort}/json/list";

        $output->writeln('Downloading tabs from device...');
        $output->writeln("> {$url}");

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
            ],
        ]);

        $jsonString = @file_get_contents($url, false, $context) ?: null;

        unset($url);

        if (null === $jsonString) {
            $output->writeln('Unable to download tabs from device! Please check the device connection!');
            $output->writeln('Also make sure google chrome is running on the connected device.');

            return Command::FAILURE;
        } else {
            $output->writeln('Download successful!');
        }

        // Decode json string:

        $output->writeln('Decoding data now...');

        try {
            $jsonArray = @json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $jsonArray = null;
        }

        if (null === $jsonArray) {
            $output->writeln('Unable to decode json data!');

            return Command::FAILURE;
        } else {
            $output->writeln('Successfully decoded data.');
        }

        // Get `file` argument:

        $argumentFile = $input->getArgument('file');
        $argumentFile = pathinfo($argumentFile, PATHINFO_FILENAME);

        assert(is_string($argumentFile));

        // Write `json` file:

        $this->writeFileContent($output, $argumentFile, 'json', $jsonString);

        // Write `md` file:

        $bashString = '# Tabs'
            . PHP_EOL
            . PHP_EOL
            . join(
                PHP_EOL, array_map(function (array $entry): string {
                    $url = $entry['url'];
                    $title = $entry['title'] ?: $url;

                    return sprintf('* [%s](%s)', $title, $url);
                }, $jsonArray)
            )
            . PHP_EOL
            . PHP_EOL
            . 'Created using [machinateur/android-chrome-tab-transfer](https://github.com/machinateur/android-chrome-tab-transfer).'
            . PHP_EOL;

        $this->writeFileContent($output, "{$argumentFile}-gist", 'md', $bashString);

        // Write `sh` file:

        $bashString = '#!/bin/bash'
            . PHP_EOL
            . PHP_EOL
            . '# Created using machinateur/android-chrome-tab-transfer (https://github.com/machinateur/android-chrome-tab-transfer).'
            . PHP_EOL
            . PHP_EOL
            . join(
                PHP_EOL, array_map(function (array $entry) use ($argumentPort): string {
                    $url = $entry['url'];
                    $title = $entry['title'] ?: $url;
                    $url = rawurlencode($url);

                    return sprintf('# %s', $title)
                        . PHP_EOL
                        . sprintf("curl 'http://localhost:{$argumentPort}/json/new?%s'", $url)
                        . PHP_EOL;
                }, $jsonArray)
            );

        $this->writeFileContent($output, "{$argumentFile}-reopen", 'sh', $bashString);

        return Command::SUCCESS;
    }

    /**
     * @param string $shellCommand
     * @return bool
     */
    private function isShellCommandAvailable(string $shellCommand): bool
    {
        $isWindows = 0 === strpos(PHP_OS, 'WIN');

        $test = $isWindows
            ? 'cmd /c "where %s"'
            : 'command -v %s';

        return is_executable(
            trim(
                shell_exec(
                    sprintf($test, $shellCommand)
                )
            )
        );
    }

    /**
     * @param OutputInterface $output
     * @param string $file
     * @param string $fileExtension
     * @param string $fileContent
     */
    private function writeFileContent(OutputInterface $output, string $file, string $fileExtension, string $fileContent): void
    {
        $basePath = $this->getBasePath();
        $filePath = "{$basePath}/{$file}.{$fileExtension}";

        $output->writeln("Writing {$fileExtension} file...");
        $output->writeln("> {$filePath}");

        $bytesWritten = @file_put_contents($filePath, $fileContent) ?: null;

        if (null === $bytesWritten) {
            $message = "Failed to write {$fileExtension} file!";

            $output->writeln($message);

            throw new RuntimeException($message);
        } else {
            $fileExtension = ucfirst($fileExtension);

            $output->writeln("{$fileExtension} file written successfully!");
        }
    }

    /**
     * @return string
     */
    private function getBasePath(): string
    {
        if (extension_loaded('phar') && strlen($path = \Phar::running(false)) > 0) {
            return dirname($path);
        }

        return dirname(__DIR__, 2);
    }
}

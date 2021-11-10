<?php

namespace App\Command;

use JsonException;
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
    private const DEFAULT_FILE = 'tabs.json';

    protected static $defaultName = 'copy-tabs';

    protected function configure(): void
    {
        $this
            ->setDescription('A tool to transfer tabs from your android phone to your computer using `adb`.')
            ->addArgument('file', InputArgument::OPTIONAL, 'The relative filepath to write.', self::DEFAULT_FILE)
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'The port to forward requests using `adb`.', self::DEFAULT_PORT);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isShellCommandAvailable('adb')) {
            $output->writeln('The adb executable is not available!');

            return Command::FAILURE;
        } else {
            $output->writeln('The adb executable is available.');
        }

        $argumentPort = (int)$input->getOption('port');

        if (!is_numeric($argumentPort)) {
            $argumentPort = self::DEFAULT_PORT;

            $output->writeln("Invalid port given, default to {$argumentPort}.");
        }

        $output->writeln('Running adb command...');

        $output->writeln("> adb -d forward tcp:{$argumentPort} localabstract:chrome_devtools_remote");
        $output->write(
            shell_exec("adb -d forward tcp:{$argumentPort} localabstract:chrome_devtools_remote")
        );

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

        if (null === $jsonString) {
            $output->writeln('Unable to download tabs from device! Please check the device connection!');

            return Command::FAILURE;
        } else {
            $output->writeln('Download successful!');
        }

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

        $argumentFile = dirname(__DIR__, 2) . $input->getArgument('file');

        $output->writeln("Writing json file...");
        $output->writeln("> {$argumentFile}");

        $bytesWritten = @file_put_contents($argumentFile, $jsonString) ?: null;

        if (null === $bytesWritten) {
            $output->writeln('Failed to write json file!');

            return Command::FAILURE;
        } else {
            $output->writeln('Json file written successfully!');

            unset($bytesWritten);
        }

        $markdownString = '# Tabs'
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

        $argumentFile .= '.md';

        $output->writeln("Writing markdown file...");
        $output->writeln("> {$argumentFile}");

        $bytesWritten = @file_put_contents($argumentFile, $markdownString) ?: null;

        if (null === $bytesWritten) {
            $output->writeln('Failed to write markdown file!');

            return Command::FAILURE;
        } else {
            $output->writeln('Markdown file written successfully!');
        }

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
            ? 'where'
            : 'command -v';

        return is_executable(
            trim(
                shell_exec("$test $shellCommand")
            )
        );
    }
}

<?php

require_once __DIR__ . '/vendor/autoload.php';

// Gain insights into the expression from `\Machinateur\ChromeTabTransfer\Platform::isShellCommandAvailable()`.

$shellCommand = 'adb';
$test         = \Machinateur\ChromeTabTransfer\Platform::isWindows()
    ? 'cmd /c "where %s"'
    : 'command -v %s';

$result = \array_reduce(
    \explode("\n",
        (string)\shell_exec(
            \sprintf($test, $shellCommand)
        )
    ),
    static function (bool $carry, string $entry): bool {
        $entry = \trim($entry);
        #$entry = \str_replace('\\', '/', $entry);

        echo $entry, \PHP_EOL;

        $valid = $carry
            || (\file_exists($entry) && \is_executable($entry));

        \var_dump('is_file', \is_file($entry));
        \var_dump('file_exists', \file_exists($entry));
        \var_dump('is_executable', \is_executable($entry));
        echo \PHP_EOL;

        return $valid;
    },
    false
);

\var_dump($result);

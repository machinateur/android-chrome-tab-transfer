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

namespace Machinateur\ChromeTabTransfer;

final class Platform
{
    private function __construct()
    {}

    /**
     * Detect whether the application is running on a Windows or Mac/Linux system.
     */
    public static function isWindows(): bool
    {
        return 0 === \strpos(\PHP_OS, 'WIN');
    }

    /**
     * Extract a reference to a class-private property.
     *
     * This is a last-resort approach. Use with care. You have been warned.
     *
     * @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
     */
    public static function & extractPropertyReference(object $object, string $propertyName): mixed
    {
        $value = & \Closure::bind(function & () use ($propertyName) {
            return $this->{$propertyName};
        }, $object, $object)->__invoke();

        return $value;
    }

    /**
     * Check whether the application is currently running as `phar` archive.
     *
     * @see https://github.com/box-project/box/blob/main/doc/faq.md#detecting-that-you-are-inside-a-phar
     */
    public static function isPhar(): bool
    {
        return '' !== \Phar::running(false);
    }


    /**
     * Check if a given command is available in the shell environment context.
     *
     * If there are multiple binary executables available (checked ith `where` on Windows, `command` on Mac/Linux)
     *  all of them are checked for actual existence and if they're executable.
     */
    public static function isShellCommandAvailable(string $shellCommand): bool
    {
        $test = self::isWindows()
            ? 'cmd /c "where %s"'
            : 'command -v %s';

        return \array_reduce(
            \explode(PHP_EOL,
                (string)\shell_exec(
                    \sprintf($test, $shellCommand)
                )
            ),
            static function (bool $carry, string $entry): bool {
                $entry = \trim($entry);

                return $carry
                    || (\is_file($entry) && \is_executable($entry));
            }, false
        );
    }
}

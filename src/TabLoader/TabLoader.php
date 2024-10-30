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

namespace Machinateur\ChromeTabTransfer\TabLoader;

use Machinateur\ChromeTabTransfer\Exception\TabLoadingFailedException;

class TabLoader implements TabLoaderInterface
{
    private bool $debug = false;

    public function __construct(
        private readonly string $url,
        private readonly int $timeout,
    ) {
    }

    public function load(): array
    {
        $ch = \curl_init();

        \curl_setopt($ch, \CURLOPT_URL,            $this->url);
        \curl_setopt($ch, \CURLOPT_TIMEOUT,        $this->timeout);
        //\curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, 10);

        if ($this->debug) {
            \curl_setopt($ch, \CURLOPT_VERBOSE,    true);
        }

        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);

        $jsonString = \curl_exec($ch) ?: null;

        $capturedErrorCode = \curl_errno($ch);
        $capturedErrorMessage = null;
        if (0 < $capturedErrorCode) {
            $capturedErrorMessage = \sprintf('%s (code %d)', \curl_error($ch), $capturedErrorCode);
        }
        \curl_close($ch);

        unset($url, $ch);

        if (null === $jsonString) {
            if (null === $capturedErrorMessage) {
                throw TabLoadingFailedException::withoutErrorMessage($capturedErrorCode);
            }

            throw new TabLoadingFailedException($capturedErrorMessage);
        }

        try {
            $jsonArray = @\json_decode($jsonString, true, flags: \JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException $exception) {
            throw TabLoadingFailedException::fromJsonException($exception);
        }

        return $jsonArray;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function setDebug(bool $debug): TabLoader
    {
        $this->debug = $debug;
        return $this;
    }
}

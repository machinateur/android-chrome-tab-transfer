# android-chrome-tab-transfer

A tool to transfer tabs from your android phone to your computer using `adb`.

## Prerequisites

* [PHP 7.4 or newer](https://www.php.net/downloads.php)
* [Composer](https://getcomposer.org/download/)
* [Android Debug Bridge](https://developer.android.com/studio/command-line/adb)
  (platform tools including the `adb` executable)

Make sure to add the location of the platform tools to the `PATH` environment variable.

## Installation

```bash
git clone git@github.com:machinateur/android-chrome-tab-transfer.git
cd android-chrome-tab-transfer
composer install
php ./copy-tabs.php
```

## Usage

```bash
Description:
  A tool to transfer tabs from your android phone to your computer using `adb`.

Usage:
  copy-tabs [options] [--] [<file>]

Arguments:
  file                  The relative filepath to write. [default: "tabs.json"]

Options:
  -p, --port=PORT       The port to forward requests using `adb`. [default: 9222]
  -h, --help            Display help for the given command. When no command is given display help for the copy-tabs command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

```

## License

It's MIT.

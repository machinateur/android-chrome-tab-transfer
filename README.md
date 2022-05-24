# android-chrome-tab-transfer

A tool to transfer google chrome tabs from your android phone to your computer using `adb`.

## Prerequisites

* [PHP 7.4 or newer](https://www.php.net/downloads.php)
* [Composer](https://getcomposer.org/download/)
* [Android Debug Bridge](https://developer.android.com/studio/command-line/adb)
  (platform tools including the `adb` executable)
* [Google Chrome Browser](https://play.google.com/store/apps/details?id=com.android.chrome) on your android phone

Make sure to add the location of the platform tools to the `PATH` environment variable. Also make sure to activate the
usb debugging feature under developer options on your android phone and to connect it to your computer. The browser has
to be running for this tool to work properly.

## Installation

```bash
git clone git@github.com:machinateur/android-chrome-tab-transfer.git
cd android-chrome-tab-transfer
composer install
php ./copy-tabs.php
```

## Usage

The command will generate three new files:

* A `tabs.json` file containing the raw json of your tab's data.
* A `tabs-gist.md` file containing a markdown formatted list of all your tabs.
* A `tabs-reopen.sh` bash script containing a curl call for each of your tabs to reopen.

The filename and port can be changed using the command arguments and options described below.

```bash
Description:
  A tool to transfer tabs from your android phone to your computer using `adb`.

Usage:
  copy-tabs [options] [--] [<file>]

Arguments:
  file                  The relative filepath to write. [default: "tabs.json"]

Options:
  -p, --port=PORT       The port to forward requests using `adb`. [default: 9222]
  -s, --socket=SOCKET   The socket to forward requests using `adb`. [default: "chrome_devtools_remote"]
  -h, --help            Display help for the given command. When no command is given display help for the copy-tabs command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

```

To reopen the tabs on another device, connect it instead, allow usb debugging and start the google chrome browser. 

## Credit

The inspiration for this tool was [this android stackexchange answer](https://android.stackexchange.com/a/199496/363078).

## How it works

The `adb`, which is part of the android platform tools, is used to forward http calls via the usb connection. What http
calls, you ask? I present to you the [chrome devtools protocol](https://chromedevtools.github.io/devtools-protocol/).

> The Chrome DevTools Protocol allows for tools to instrument, inspect, debug and profile Chromium, Chrome and other
> Blink-based browsers.

It exposes endpoints to retrieve all currently open tabs and also one to open a new tab. The former is used to download
tab information, while the latter one can be used by the generated `sh` script to reopen the tabs on another device.

## License

It's MIT.

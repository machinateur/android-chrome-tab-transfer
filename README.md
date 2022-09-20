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

### Phar

```bash
git clone git@github.com:machinateur/android-chrome-tab-transfer.git
cd android-chrome-tab-transfer
php copy-tabs.phar
```

### Source

On Windows:

```bash
git clone git@github.com:machinateur/android-chrome-tab-transfer.git
cd android-chrome-tab-transfer
copy-tabs.cmd
```

On Mac/Linux:

```bash
git clone git@github.com:machinateur/android-chrome-tab-transfer.git
cd android-chrome-tab-transfer
./copy-tabs.sh
```

## Usage

```bash
php copy-tabs.php
```

The command will generate three new files:

* A `tabs.json` file containing the raw json of your tab's data.
* A `tabs-gist.md` file containing a markdown formatted list of all your tabs.
* A `tabs-reopen.sh` bash script containing a curl call for each of your tabs to reopen.

The filename, port and socket name can be changed using the command arguments and options described below.

```bash
Description:
  A tool to transfer tabs from your android phone to your computer using `adb`.

Usage:
  copy-tabs [options] [--] [<file>]

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
PS D:\Project\machinateur\android-tab-transfer> php .\copy-tabs.php -h
Description:
  A tool to transfer tabs from your android phone to your computer using `adb`.

Usage:
  copy-tabs [options] [--] [<file>]

Arguments:
  file                   The relative filepath to write. [default: "tabs.json"]

Options:
  -p, --port=PORT        The port to forward requests using `adb`. [default: 9222]
  -s, --socket=SOCKET    The socket to forward requests using `adb`. [default: "chrome_devtools_remote"]
  -t, --timeout=TIMEOUT  The network timeout for the download request. [default: 60]
  -h, --help             Display help for the given command. When no command is given display help for the copy-tabs command
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi|--no-ansi   Force (or disable --no-ansi) ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

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

## Chrome Beta/Canary support

This is an advanced use-case. For details on how to use this tool with the beta or canary channels of the Google Chrome
browser on Android, read [#2](https://github.com/machinateur/android-chrome-tab-transfer/issues/2). Extended technical
knowledge is advised.

## Detecting network errors

Time has shown that the communication between `adb` on the computer and the android device attached via cable can cause
errors or at least confusion. Thus, the possibility was introduced to get the error message and code of whatever
occurred during downloading the tabs from the device. Since that information is rather technical and will only be needed
in certain cases, it is only displayed when the download request fails and the command is run in debug mode.

To run the command with debug verbosity, append `-vvv` to the end of the command:

```bash
php copy-tabs.php -vvv
```

## Building phar from source

The phar can be built from source using [box](https://github.com/box-project/box). If you don't have it installed yet,
you can by running the following command:

```bash
composer global require humbug/box
```

Please keep in mind that global composer dependencies are discouraged, so this is the alternative:

```bash
mkdir --parents tools/box
echo "tools/box" >> .gitignore
composer require --working-dir=tools/box humbug/box
```

The configuration may be customized using the `box.json` file. To build, run the following command:

```bash
box compile
```

Or, in case of using `tools/box`:

```bash
./tools/box/vendor/bin/box compile
```

## License

It's MIT.

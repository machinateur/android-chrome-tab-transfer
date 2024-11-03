# tab-transfer

A tool to transfer google chrome tabs from your phone to your computer using developer tools.

This is one of the most reliable ways to transfer larger amounts of opened tabs when switching phones, as it comes with
 utilities to reopen the exported tabs.

## Prerequisites

* [PHP 8.1 or newer](https://www.php.net/downloads.php)
* [Composer](https://getcomposer.org/download/)
  (optional)
* [Android Debug Bridge](https://developer.android.com/studio/command-line/adb)
  (platform tools including the `adb` executable)
* [iOS WebKit Debug Proxy](https://github.com/google/ios-webkit-debug-proxy)
* [Google Chrome Browser](https://play.google.com/store/apps/details?id=com.android.chrome) on your android phone

TODO: Add iOS docs. --> https://developer.chrome.com/blog/debugging-chrome-on-ios/

Make sure to add the location of the platform tools to the `PATH` environment variable. Also make sure to activate the
 usb debugging feature under developer options on your android phone and to connect it to your computer. The browser has
 to be running for this tool to work properly.

Here's [how to enable USB debugging on your device](https://developer.android.com/studio/debug/dev-options.html#Enable-debugging).

## Installation

### *__See [INSTALL.md](INSTALL.md) for a detailed and less technical guide.__*

### Windows installer

TODO

### Source

On Windows:

```bash
git clone git@github.com:machinateur/tab-transfer.git
cd tab-transfer
composer install --no-dev
tab-transfer.cmd
```

On Mac/Linux:

```bash
git clone git@github.com:machinateur/tab-transfer.git
cd tab-transfer
composer install --no-dev
chmod +x ./tab-transfer.sh
./tab-transfer.sh
```

### Phar

As of now, the phar is no longer part of the repository, you'll have to build it from source,
 [as described down below](#building-phar-from-source). 

```bash
git clone git@github.com:machinateur/tab-transfer.git
cd tab-transfer
composer install --dev
composer run-script box-compile
php tab-transfer.phar
```

## Usage

```bash
php tab-transfer
# or on mac/linux
./tab-transfer
```

The command will generate three new files:

* A `tabs.json` file containing the raw json of your tab's data.
* A `tabs-gist.md` file containing a markdown formatted list of all your tabs.
* A `tabs-reopen.sh` bash script containing a `curl` call for each of your tabs to reopen.

The filename, port and socket name can be changed using the command arguments and options described below.

The timeout can also be changed using the `-t` argument, default is `10s`, which is also the minimum required value.

```bash
Description:
  A tool to transfer tabs from your android phone to your computer using `adb`.

Usage:
  copy-tabs [options] [--] [<file>]

Arguments:
  file                   The relative filepath to write. Only the filename is actually considered! [default: "tabs.json"]

Options:
  -d, --date|--no-date   Whether to add the date 'Y-m-d' suffix to the filename. On by Default.
  -p, --port=PORT        The port to forward requests using `adb`. [default: 9222]
  -s, --socket=SOCKET    The socket to forward requests using `adb`. [default: "chrome_devtools_remote"]
  -t, --timeout=TIMEOUT  The network timeout for the download request. [default: 10]
  -w, --wait=WAIT        The time to wait before starting the download request (in seconds). [default: 2]
      --skip-cleanup     Skip the `adb` cleanup command execution.
  -h, --help             Display help for the given command. When no command is given display help for the copy-tabs command
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi|--no-ansi   Force (or disable --no-ansi) ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

```

Alternatively, you can also run the command as composer script:

```bash
composer run-script tab-transfer
```

## Credit

The inspiration for this tool was [this android stackexchange answer](https://android.stackexchange.com/a/199496/363078).

## How it works

The `adb`, which is part of the android platform tools, is used to forward http calls via the usb connection. What http
 calls, you ask? I present to you the [chrome devtools protocol](https://chromedevtools.github.io/devtools-protocol/).

> The Chrome DevTools Protocol allows for tools to instrument, inspect, debug and profile Chromium, Chrome and other
>  Blink-based browsers.

It exposes endpoints to retrieve all currently open tabs and also one to open a new tab. The former is used to download
 tab information, while the latter one can be used by the generated `sh` script to reopen the tabs on another device.

## About the re-open script

To reopen the tabs on another device, connect it instead, allow usb debugging and start the google chrome browser. Then
 run the generated script file. It requires `curl` to send the commands to reopen all tabs, as well as `adb`.
 The generated script will include commands to manage the debug connection, so make sure your new phone is connected
 before running the shell script.

Please note, that in most cases there will be a dialog prompting you to allow the usb debugging access. It's advised you
 keep your phone unlocked during the process, to make sure the request doesn't time out.

The script to reopen all tabs will be output depending on your operating system.
 On Windows it's `tabs-reopen.cmd`, on Mac and Linux it's `tabs-reopen.sh`.

On Mac and Linux you will first have to make the script executable.

See [#18](https://github.com/machinateur/tab-transfer/issues/18) for more information on how to
 repurposethe re-open script for a windows use-case.

## Chrome Beta/Canary support

This is an advanced use-case. For details on how to use this tool with the beta or canary channels of the Google Chrome
 browser on Android, read [#2](https://github.com/machinateur/tab-transfer/issues/2).
 Extended technical knowledge is advised.

## Detecting network errors

Time has shown that the communication between `adb` on the computer and the android device attached via cable can cause
 errors or at least confusion. Thus, the possibility was introduced to get the error message and code of whatever
 occurred during downloading the tabs from the device. Since that information is rather technical and will only be
 needed in certain cases, it is only displayed when the download request fails and the command is run in debug mode.

The output of the curl request will also be set to verbose and print directly to `STDOUT`.

To run the command with debug verbosity, append `-vvv` to the end of the command:

```bash
php tab-transfer -vvv
```

Make sure to activate the usb debugging feature under developer options on your android phone and to connect it to your
 computer. The browser has to be running for this tool to work properly. If you are not sure on how to set it
 up, [here is a guide](https://developer.chrome.com/docs/devtools/remote-debugging/) on how to remotely debug an android
 phone.

It's possible to gain some more insight using the device inspection built into any chrome-based browser. For that,
 navigate to `chrome://inspect/#devices` in any chrome based browser, like for example google chrome, installed on your
 computer.

## Incomplete tab list

Even though it is rare, there have been cases where the tab list was missing a small number of tabs.
^
In most cases the list was complete when re-tried.

It was also reported in [#29](https://github.com/machinateur/tab-transfer/issues/29) that setting
 the `chrome://flags/#tab-group-parity-android` flag to **enabled**, this would also solve the issue.

## Tab order and groups

It is not guaranteed that the order of the tabs exported corresponds to the same order they were opened on your device.
It is also currently not possible to keep tab-group associations across devices. This is a technical limitation,
 as the endpoint does not provide those information.

## Building phar from source

The phar can be built from source using [box](https://github.com/box-project/box). If you don't have it installed yet,
 you can by running the following command:

```bash
composer global require humbug/box
```

Please keep in mind that global composer dependencies are discouraged, you should use the box version that's included as
 a dev-dependency.

The configuration may be customized using a `box.json` file. To build, run the following command:

```bash
box compile
```

Or, to use the dev-dependency, as recommended:

```bash
composer install --dev
composer run-script box-compile
```

## License

It's MIT.

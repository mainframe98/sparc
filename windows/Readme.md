# Sparc on Windows

## Build
### Requirements
* PHP 8.2 or newer
  * Be aware that arcanist may not support newer versions of PHP right away.
  * `php-phar`
  * `php-zip`
* `git`
  * Make sure that `git` is available in the command prompt.
* [spc for Windows](https://dl.static-php.dev/static-php-cli/spc-bin/nightly/spc-windows-x64.exe), version 2.4.4 or newer. Version 3 is not yet supported.
* [php-sdk-binary-tools](https://github.com/php/php-sdk-binary-tools)
* Visual Studio with the "Desktop development with C++" workload
  * The community release is sufficient.
  * Depending on the version of Visual Studio you may need to select a different MSVC version. [PHP's Windows build documentation](https://wiki.php.net/internals/windows/php-windows-builder#requirements) has a table you can reference.
* A directory with the contents of this repository, without spaces in the directory names. This document will call this the working directory going forward.

### Steps

1. Install Visual Studio with the "Desktop development with C++" workload.
   * Ensure the relevant MSVC version is installed, under "Installation details", look for `MSVC v14X - VS20XX` to install older versions alongside the regular MSVC Build Tools.
2. Download the windows release of [spc](https://dl.static-php.dev/static-php-cli/spc-bin/nightly/spc-windows-x64.exe) and put it your working directory.
3. Download the [php-sdk-binary-tools](https://github.com/php/php-sdk-binary-tools) and put them in your working directory.
4. Run `build-sparc-windows.bat` using the Visual Studio x64 Native Tools Command Prompt.

## Installer
This directory contains the installer for Arcanist on Windows. The installer uses InnoSetup. InnoSetup 6.4 or newer is required.

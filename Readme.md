# Sparc

[**S**tatic **P**HP](https://github.com/crazywhalecc/static-php-cli) **Arc**anist.

Sparc allows running [Arcanist](https://we.phorge.it/rARC) without installing PHP. [Simply download the binary](https://github.com/mainframe98/sparc/releases/latest), place it in `$PATH`, and use it!
For Windows users, an installer is available. This installer also adds Arcanist to `$PATH` and can optionally install a pre-compiled PHP binary.

For slightly more advanced usage, Sparc releases come with pre-compiled PHP binaries suitable for running Arcanist directly from source.

## License
This project is licensed under the Apache 2.0 license.

Sparc incorporates Arcanist. Arcanist is licensed under the Apache 2.0 license, except for certain dependencies. Consult its repository for more information.

In order to run Arcanist in a PHP Archive (phar), some modifications to Arcanist are required. These can be found in the directories `patches/` and `overrides/`.

## Limitations
- If a new Arcanist release changes the bash auto-completion, they must be re-installed by running `arc shell-complete --install`.
- `phage` is not officially supported.
- You cannot use Sparc to run the unit tests of Arcanist or Phorge, or any other project that depends on either. This affects `arc diff` in those repositories.
  - This is because of how Arcanist loads its classes. You can instead use the pre-built PHP binary available in the [releases](https://github.com/mainframe98/sparc/releases/latest) and checkout a local copy of the Arcanist or Phorge source code.
  - Alternatively, if you're willing to risk the wrath of the maintainers who will have to review the patches without test results, you can use `--nounit` when using `arc diff`.
- XHPAST is not supported. When running `arc liberate`, PHP-Parser will be used instead.
- Sparc cannot be used to replace the copy of Arcanist required by a [Phorge](https://we.phorge.it/rPHORGE) installation.
- Windows builds require a x86 64-bit operating system. ARM64 is not supported.
  - This is a PHP limitation.

## Reporting bugs
- Check if the bug occurs in Arcanist. Arcanist-only bugs should be reported to [the upstream](https://we.phorge.it/). You can use the pre-compiled PHP binary in the releases to test upstream Arcanist release.
- Run `arc version` and include this information in your bug report.

## Building
> Remember to check out the Arcanist submodule!
>
> You can do so with `git submodule update --init` if you didn't clone with `git clone --recurse-submodules` or `git clone --recursive`.

### (Linux) Building using Docker
Run `./build-sparc-docker-linux.sh`. Additional flags to `build-sparc.php` can be provided to this script.

This script caches the image as `sparc:latest` to speed up subsequent runs. Pass `--rebuild` to rebuild the image.

### Building on Windows
See the [Windows specific Readme file](windows/Readme.md).

### Manual

- Run `spc doctor`
- Run `build-sparc.php`

#### Requirements
- PHP 8.2 or newer
  - Be aware that arcanist may not support newer versions of PHP right away.
  - `php-phar`
  - `php-zip`
- `git`
- [static-php-cli](https://github.com/crazywhalecc/static-php-cli/tree/main#1-download-spc-binary) 2.4.4 or newer. Version 3 is not yet supported.
- `libtool`
- `bison`
- `flex`
- `automake`
- `autopoint`
- `patchelf`
- `unzip`
- `musl`
- `musl-tools`
- `cmake`
- `nasm`

## Creating a new release

* Pull latest changes from the stable branch of Arcanist.
* Update the version in `overrides/ArcanistVersionWorkflow.php`
* Commit the changes
* Tag using `git tag -a vX.X.X`
* Push the commit and tag using `git push --follow-tags`
* After the pipeline has completed, undraft the created release

<?php

final class ArcanistUpgradeWorkflow
  extends ArcanistArcWorkflow {

  const REPO = 'mainframe98/sparc';

  public function getWorkflowName() {
    return 'upgrade';
  }

  public function getWorkflowInformation() {
    $help = pht(<<<EOTEXT
Upgrade this program to the latest version.
EOTEXT
    );

    return $this->newWorkflowInformation()
      ->setSynopsis(pht('Upgrade this program to the latest version.'))
      ->addExample(pht('**upgrade**'))
      ->setHelp($help);
  }

  public function getWorkflowArguments() {
    return array();
  }

  public function runWorkflow() {
    $log = $this->getLogEngine();

    try {
      list($body, $headers) = $this->newGitHubApiFuture('releases/latest')
        ->resolvex();

      $data = phutil_json_decode($body);
    } catch (HTTPFutureResponseStatus $status) {
      if ($status->getStatusCode() === 410) {
        $log->writeError(
          pht('ERROR'),
          pht(
            'This client is too old to automatically check for new releases.'.
            'Download the installer from %s and update %s manually.',
            'https://github.com/'.self::REPO.'/releases/latest',
            PlatformSymbols::getPlatformClientName()));
        return 1;
      }

      throw $status;
    } catch (PhutilJSONParserException $ex) {
      $log->writeError(
        pht('ERROR'),
        pht(
          'Invalid JSON response from GitHub releases endpoint: %s.',
          $ex->getMessage()));

      return 1;
    }

    $newest_version = ltrim($data['name'], 'v');

    if (version_compare(ArcanistVersionWorkflow::VERSION, $newest_version, '>=')) {
      $log->writeSuccess(
        pht('DONE'),
        pht('This software is already up to date.'));

      return 0;
    }

    $assets = $data['assets'];

    switch (PHP_OS_FAMILY) {
      case 'Windows':
        return $this->updateWindows($assets);
      case 'Darwin':
        return $this->updateNix($assets, 'arc-macos');
      case 'Linux':
        return $this->updateNix($assets, 'arc-linux');
      case 'BSD':
      case 'Solaris':
        $log->writeWarning(
          pht('UNSUPPORTED OS'),
          pht(
            '%s is not supported.',
            PHP_OS_FAMILY));
        break;
      default:
        $log->writeError(
          pht('UNKNOWN OS'),
          pht('Unknown operating system.'));
        break;
    }

    return 1;
  }

  private function updateWindows(array $assets): int {
    $log = $this->getLogEngine();

    $binary = Phar::running(false);
    $install_dir = dirname($binary, 2);
    $use_installer = file_exists($install_dir.'/uninst000.exe');

    // Arcanist on Windows comes in two flavours:
    // - Using an installer
    // - Using the .exe directly
    // Depending on the flavour, we need to select a different asset.
    if ($use_installer) {
      $filename = 'arc-setup.exe';
    } else {
      $filename = 'arc.exe';
    }

    $asset_id = -1;
    foreach ($assets as $asset) {
      if ($asset['name'] === $filename) {
        $asset_id = $asset['id'];
      }
    }

    if ($asset_id === -1) {
      $log->writeError(
        pht('ERROR'),
        pht('Release is missing Windows binaries.'));
      return 1;
    }

    $log->writeStatus(
      pht('DOWNLOADING'),
      pht(
        'Preparing to download "%s"...',
        PlatformSymbols::getPlatformClientName()));

    $tempfile = new TempFile();
    // HTTPSFuture::setDownloadPath throws when a file exists.
    Filesystem::remove($tempfile);

    $this->newGitHubApiFuture('releases/assets/'.$asset_id, true)
      ->setDownloadPath($tempfile)
      ->setProgressSink(new PhutilConsoleProgressSink())
      ->resolvex();

    if (!$use_installer) {
      // Windows doesn't support overwriting with rename.
      Filesystem::remove($binary.'.old');
      Filesystem::rename($binary, $binary.'.old');
      Filesystem::rename($tempfile, $binary);

      $log->writeSuccess(
        pht('UPGRADED'),
        pht('This software is now up to date.'));

      return 0;
    }

    // Preserve the download, we exit before the installer completes
    // and the installer shouldn't be deleted before we can run it.
    $tempfile->setPreserveFile(true);

    // Launch the installer by delegating through "start".
    // This passes control back immediately,
    // and prevents locking the files the installer will overwrite.
    $handle = popen(csprintf('start /b %s', $tempfile), 'r');

    if (!$handle || pclose($handle) === -1) {
      // Ensure the download is removed when attempting to run it fails.
      // There is no guarantee it is usable.
      $tempfile->setPreserveFile(false);

      $log->writeError(
        pht('ERROR'),
        pht(
          'Could not launch the installer. Download the installer from %s '.
          'and update %s manually.',
          'https://github.com/'.self::REPO.'/releases/latest',
          PlatformSymbols::getPlatformClientName()));

      return 1;
    }

    return 0;
  }

  private function updateNix(array $assets, string $filename): int {
    $log = $this->getLogEngine();

    $uname = posix_uname();

    if (!$uname) {
      $log->writeError(
        pht('UNAME ERROR'),
        pht('Cannot determine system architecture.'));

      return 1;
    }

    $architecture = $uname['machine'];

    // Prefer the same architecture nomenclature as used by
    // static-php-cli.
    switch ($architecture) {
      case 'arm64':
      case 'aarch64':
        $architecture = 'aarch64';
        break;
      case 'amd64':
      case 'x86_64':
        $architecture = 'x86_64';
        break;
      default:
        $log->writeError(
          pht('UNSUPPORTED ARCHITECTURE'),
          pht(
            'Architecture "%s" is not supported.',
            $architecture));
        return 1;
    }

    $filename .= '-'.$architecture;

    $asset_id = -1;
    foreach ($assets as $asset) {
      if ($asset['name'] === $filename) {
        $asset_id = $asset['id'];
      }
    }

    if ($asset_id === -1) {
      $log->writeError(
        pht('ERROR'),
        pht('Release is missing binary for this platform.'));
      return 1;
    }

    $log->writeStatus(
      pht('DOWNLOADING'),
      pht(
        'Preparing to download "%s"...',
        PlatformSymbols::getPlatformClientName()));

    $tempfile = new TempFile();
    // HTTPSFuture::setDownloadPath throws when a file exists.
    Filesystem::remove($tempfile);

    $this->newGitHubApiFuture('releases/assets/'.$asset_id, true)
      ->setDownloadPath($tempfile)
      ->setProgressSink(new PhutilConsoleProgressSink())
      ->resolvex();

    // Temporary files are created with mode 0600.
    Filesystem::changePermissions($tempfile, 0700);

    $binary = Phar::running(false);

    // This will overwrite any existing .old file.
    Filesystem::rename($binary, $binary.'.old');
    Filesystem::rename($tempfile, $binary);

    $log->writeSuccess(
      pht('UPGRADED'),
      pht('This software is now up to date.'));

    return 0;
  }

  private function newGitHubApiFuture(string $path, bool $download = false) {
    $uri = 'https://api.github.com/repos/'.self::REPO.'/'.$path;
    if ($download) {
      $accept = 'application/octet-stream';
    } else {
      $accept = 'application/vnd.github+json';
    }

    return id(new HTTPSFuture($uri))
      ->addHeader('Accept', $accept)
      ->addHeader('X-GitHub-Api-Version', '2026-03-10');
  }

}

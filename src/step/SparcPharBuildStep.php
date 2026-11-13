<?php

final class SparcPharBuildStep extends SparcBuildStep {

  private const OVERRIDES = array(
    'ArcanistVersionWorkflow.php' => '/src/toolset/workflow/ArcanistVersionWorkflow.php',
    'ArcanistUpgradeWorkflow.php' => '/src/workflow/ArcanistUpgradeWorkflow.php',
  );

  public const ROOTS = array(
    'sparc' => __DIR__.'/../../',
    'arcanist' => __DIR__.'/../../arcanist/',
  );

  private const SHARED_FLAGS = FilesystemIterator::CURRENT_AS_FILEINFO |
      FilesystemIterator::KEY_AS_FILENAME |
      FilesystemIterator::UNIX_PATHS |
      FilesystemIterator::SKIP_DOTS;

  public function requires(): array {
    return array(
      'shell-complete',
      'patch',
      'liberate',
      'php-parser',
    );
  }

  public function getName(): string {
    return 'phar';
  }

  public function getStepDescription(): string {
    return pht('Building phar...');
  }

  public function doRequirementsCheck(PhutilArgumentParser $args): void {
    if (!extension_loaded('phar')) {
      throw new SparcBuildException(
        pht('The "phar" extension must be installed.'),
        pht('PHP EXTENSION MISSING'));
    }

    if (ini_get('phar.readonly') === '1') {
      throw new SparcBuildException(
        pht(
          'PHP config option "phar.readonly" must be set to "0" in order to '.
          'write phar archives.'),
        pht('PHP ERROR'));
    }

    $phar_path = $this->getPharTarget();

    if (Filesystem::pathExists($phar_path) && !$args->getArg('overwrite')) {
      throw new SparcBuildException(
        pht(
          'Path "%s" exists, but %s was not given.',
          $phar_path,
          '--overwrite'),
        pht('FILE EXISTS'));
    }
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    $phar_path = $this->getPharTarget();

    $output_dir = dirname($phar_path);

    // The Phar extension requires that the directory exists.
    if (!Filesystem::pathExists($output_dir)) {
      Filesystem::createDirectory($output_dir);
    }

    // Remove existing .phar or the existing phar will be appended.
    Filesystem::remove($phar_path);

    $phar = new Phar(
      $phar_path,
      self::SHARED_FLAGS,
      'arc.phar');

    $phar->startBuffering();

    $this->logStepAction(
      pht('copying %s...', PlatformSymbols::getPlatformClientName()));

    $phar->buildFromIterator(
      new RecursiveIteratorIterator($this->getIterator()),
      $this->getResourcePath('/arcanist'));

    $this->logStepAction(pht('setting stub...'));

    $phar->setStub(
      Filesystem::readFile($this->getResourcePath('/stub.php')));

    $this->logStepAction(pht('applying overrides...'));

    foreach (self::OVERRIDES as $override => $local_name) {
      $phar->addFile(
        $this->getResourcePath("/overrides/$override"),
        $local_name);
    }

    $phar->setMetadata($this->generateMetadata());
    $phar->compressFiles(Phar::GZ);

    $phar->stopBuffering();

    $this->logStepAction(pht('Built %s...', $phar_path));
  }

  private function generateMetadata(): array {
    $this->logStepAction(pht('generating metadata...'));

    $versions = array();

    foreach (self::ROOTS as $lib => $root) {
      $root = Filesystem::resolvePath($root);

      list($commit) = ArcanistWorkingCopy::newFromWorkingDirectory($root)
        ->getRepositoryAPI()
        ->execxLocal(
          'log -1 --format=%s',
          '%ct%x01%H');

      list($timestamp, $commit) = explode("\1", trim($commit));

      $versions[$lib.'-version'] = $commit;
      $versions[$lib.'-timestamp'] = date('j M Y', (int)$timestamp);
    }

    return $versions;
  }

  private function getIterator() {
    $iterator = new RecursiveDirectoryIterator(
      $this->getResourcePath('/arcanist'),
      self::SHARED_FLAGS);

    return new RecursiveCallbackFilterIterator(
      $iterator,
      $this->callback(...));
  }

  private function callback(SplFileInfo $current): bool {
    $skip = array(
      'LICENSE',
      'NOTICE',
      '__tests__',
    );

    $filename = $current->getFilename();

    if (
      $filename[0] === '.' ||
      str_starts_with(strtolower($filename), 'readme') ||
      str_contains(strtolower($filename), 'xhpast') ||
      in_array($filename, $skip, true)) {

      return false;
    }

    $base = str_replace(
      $this->getResourcePath('/arcanist'),
      '',
      $current->getPathname());

    $specific_files = array(
      '/bin',
      '/externals/includes',
      '/src/docs',
      '/src/extensions',
      '/support/hg/__pycache__',
      '/support/hg/arc-hg.pyc',
      '/support/php-parser/build-php-parser.php',
      '/support/xhpast',
      '/support/unit',
    );

    foreach (array('.zip', '.tar.gz') as $extension) {
      $specific_files[] = '/support/php-parser/php-parser-'.
        PhutilPHPParserLibrary::EXPECTED_VERSION_LEGACY.$extension;
      $specific_files[] = '/support/php-parser/php-parser-'.
        PhutilPHPParserLibrary::EXPECTED_VERSION.$extension;
    }

    // We supply our own copies of these files.
    $specific_files = array_merge(
      $specific_files,
      array_values(self::OVERRIDES));

    return !in_array($base, $specific_files, true);
  }

}

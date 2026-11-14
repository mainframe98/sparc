<?php

abstract class SparcSPCBuildStep extends SparcBuildStep {

  private function getUnixSpcBinaryName() {
    $uname = posix_uname();

    if (!$uname) {
      return null;
    }

    switch ($uname['machine']) {
      case 'arm64':
      case 'aarch64':
        $architecture = 'aarch64';
        break;
      case 'amd64':
      case 'x86_64':
        $architecture = 'x86_64';
        break;
      default:
        return null;
    }

    if (PHP_OS_FAMILY === 'Darwin') {
      $os = 'macos';
    } else {
      $os = 'linux';
    }

    return 'spc-'.$os.'-'.$architecture;
  }

  private function getSPCPath() {
    if (Filesystem::binaryExists('spc')) {
      return 'spc';
    }

    $options = array();

    if (phutil_is_windows()) {
      $options[] = 'spc.exe';
      $options[] = 'spc-windows-x64.exe';
      $options[] = 'spc/bin/spc';
    } else {
      $unix_binary = $this->getUnixSpcBinaryName();

      if ($unix_binary) {
        $options[] = $unix_binary;
      }

      $options[] = 'spc/bin/spc';
      $options[] = 'spc';
    }

    $root = Filesystem::resolvePath(__DIR__.'/../../');
    $working_directory = getcwd();

    foreach ($options as $option) {
      foreach (array($root, $working_directory) as $directory) {
        $option = $directory.'/'.$option;

        if (Filesystem::pathExists($option)) {
          return $option;
        }
      }
    }

    throw new SparcBuildException(
      pht('static-php-cli is required.'),
      pht('MISSING DEPENDENCY'));
  }

  protected function doRequirementsCheck(PhutilArgumentParser $args): void {
    $this->getSPCPath();
  }

  final protected function invokeSPC(string $command, ...$arguments) {
    $path = $this->getSPCPath();
    if (phutil_is_windows() && str_ends_with($path, 'spc/bin/spc')) {
      // Running spc from source under Windows requires invoking it through PHP.
      $future = new PhutilExecPassthru(
        'php -f %s -- %C',
        $path,
        vcsprintf($command, $arguments));
    } else {
      $future = new PhutilExecPassthru(
        '%s %C',
        $path,
        vcsprintf($command, $arguments));
    }

    if (getenv('GITHUB_TOKEN')) {
      $future->setEnv(
        array(
          'GITHUB_TOKEN' => getenv('GITHUB_TOKEN'),
        ));
    }

    return $future->resolve();
  }

  final protected function getExtensions() {
    if (phutil_is_windows()) {
      return array(
        'ctype',
        'curl',
        'dom',
        'fileinfo',
        'libxml',
        'mbstring',
        'openssl',
        'phar',
        'simplexml',
        'sockets',
        'xml',
        'xmlwriter',
        'zlib',
      );
    }

    return array(
      'ctype',
      'curl',
      'dom',
      'fileinfo',
      'libxml',
      'mbstring',
      'openssl',
      'pcntl',
      'phar',
      'posix',
      'readline',
      'simplexml',
      'sockets',
      'xml',
      'xmlwriter',
      'zlib',
    );
  }

}

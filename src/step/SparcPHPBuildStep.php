<?php

final class SparcPHPBuildStep extends SparcSPCBuildStep {

  public function requires(): array {
    return array(
      'download',
      'doctor',
    );
  }

  public function getName(): string {
    return 'build';
  }

  public function getStepDescription(): string {
    return pht('Building PHP...');
  }

  public function getStepArgumentSpecs(): array {
    return array(
      array(
        'name' => 'debug',
        'help' => pht(
          'Run "%s" in debug mode.',
          'spc build'),
      ),
    );
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    $extensions = implode(',', $this->getExtensions());
    $arguments = array(
      '--build-micro',
      '--build-cli',
    );

    if ($args->getArg('debug')) {
      $arguments[] = '--debug';
    }

    $code = $this->invokeSPC('build %Ls "%C"', $arguments, $extensions);

    if ($code !== 0) {
      throw new SparcBuildException(
        pht('Failure building PHP micro.'),
        pht('BUILD FAILURE'));
    }

    $build_dir = getcwd();
    $output_dir = dirname($this->getTarget());

    if (phutil_is_windows()) {
      Filesystem::copyFile(
        $build_dir.'/buildroot/bin/php.exe',
        $output_dir.'/php.exe');
    } else {
      Filesystem::copyFile(
        $build_dir.'/buildroot/bin/php',
        $output_dir.'/php');
    }
  }

}

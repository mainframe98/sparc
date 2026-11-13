<?php

final class SparcPHPDownloadBuildStep extends SparcSPCBuildStep {

  public const VERSION = '8.4';

  public function getName(): string {
    return 'download';
  }

  public function getStepDescription(): string {
    return pht('Downloading PHP %s sources and dependencies...', self::VERSION);
  }

  public function getStepArgumentSpecs(): array {
    return array(
      array(
        'name' => 'php',
        'param' => 'version',
        'default' => self::VERSION,
        'help' => pht('PHP version to compile. Default: %s', self::VERSION),
      ),
    );
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    $extensions = implode(',', $this->getExtensions());

    $this->invokeSPC(
      'download --with-php %s --prefer-pre-built --for-extensions "%C"',
      $args->getArg('php'),
      $extensions);
  }

}

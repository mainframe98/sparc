<?php

final class SparcCombineBuildStep extends SparcSPCBuildStep {

  public function requires(): array {
    return array('build');
  }

  public function getName(): string {
    return 'combine';
  }

  public function getStepDescription(): string {
    return pht(
      'Fusing PHP and the %s Phar...',
      PlatformSymbols::getPlatformClientName());
  }

  protected function doRequirementsCheck(PhutilArgumentParser $args): void {
    parent::doRequirementsCheck($args);

    Filesystem::assertExists($this->getPharTarget());
    $bin = $this->getTarget();

    if (Filesystem::pathExists($bin) && !$args->getArg('overwrite')) {
      throw new SparcBuildException(
        pht(
          'Path "%s" exists, but %s was not given.',
          $bin,
          '--overwrite'),
        pht('FILE EXISTS'));
    }
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    $code = $this->invokeSPC(
      'micro:combine %s --output %s',
      $this->getPharTarget(),
      $this->getTarget());

    if ($code !== 0) {
      throw new SparcBuildException(
        pht('Failure combining micro-php and arc.'),
        pht('COMBINE FAILURE'));
    }
  }

}

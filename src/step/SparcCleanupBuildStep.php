<?php

final class SparcCleanupBuildStep extends SparcBuildStep {

  public function requires(): array {
    return array('combine');
  }

  public function getName(): string {
    return 'cleanup';
  }

  public function getStepDescription(): string {
    return pht('Cleaning...');
  }

  public function getStepArgumentSpecs(): array {
    return array(
      array(
        'name' => 'clean-all',
        'help' => pht('Clean **all** step artifacts.'),
      ),
    );
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    if ($args->getArg('clean-all')) {
      $command = 'clean -d --quiet -x --force';
    } else {
      $command = 'restore :/';
    }

    ArcanistWorkingCopy::newFromWorkingDirectory($this->getResourcePath('/arcanist'))
      ->getRepositoryAPI()
      ->execxLocal($command);
  }

}

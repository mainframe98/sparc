<?php

final class SparcPatchBuildStep extends SparcBuildStep {

  public function requires(): array {
    return array('shell-complete', 'php-parser');
  }

  public function doRequirementsCheck(PhutilArgumentParser $args): void {
    if (!Filesystem::binaryExists('git')) {
      throw new SparcBuildException(
        pht('Git is required to apply patches.'),
        pht('MISSING DEPENDENCY'));
    }
  }

  public function getName(): string {
    return 'patch';
  }

  public function getStepDescription(): string {
    return pht(
      'Patching %s...',
      PlatformSymbols::getPlatformClientName());
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    $arc_repo_api = ArcanistWorkingCopy::newFromWorkingDirectory($this->getResourcePath('/arcanist'))
      ->getRepositoryAPI();

    $patches = glob($this->getResourcePath('/patches/*.patch'));

    if (!$patches) {
      throw new SparcBuildException(
        pht('Cannot find patches!'),
        pht('NO PATCHES'));
    }

    foreach ($patches as $patch) {
      $this->logStepAction(
        pht(
          'applying %s...',
          pathinfo($patch, PATHINFO_BASENAME)),
        pht('PATCHING'));

      $arc_repo_api->execxLocal('apply %s', $patch);
    }
  }

}

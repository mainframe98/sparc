<?php

final class SparcChmodStep extends SparcBuildStep {

  public function requires(): array {
    return array('phar', 'combine');
  }

  public function getName(): string {
    return 'chmod';
  }

  public function getStepDescription(): string {
    return pht('Marking as executable...');
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    if (phutil_is_windows()) {
      return;
    }

    $paths = array(
      $this->getPharTarget(),
      $this->getTarget(),
    );

    foreach ($paths as $path) {
      if (Filesystem::pathExists($path)) {
        $this->logStepAction(
          pht('Marking %s as executable..', $path),
          pht('CHMOD'));

        execx('chmod +x %s', $path);
      }
    }
  }

}

<?php

final class SparcShellCompleteBuildStep extends SparcBuildStep {

  public function getName(): string {
    return 'shell-complete';
  }

  public function getStepDescription(): string {
    return pht('Generating shell completion rules...');
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    $arc = $this->getResourcePath('/arcanist/bin/arc');
    if (phutil_is_windows()) {
      $arc .= '.bat';
    }

    execx('%R shell-complete --generate --', $arc);
  }

}

<?php

final class SparcSPCDoctorBuildStep extends SparcSPCBuildStep {

  public function getName(): string {
    return 'doctor';
  }

  public function getStepDescription(): string {
    return pht('Checking and fetching dependencies...');
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    $code = $this->invokeSPC('doctor --auto-fix');

    if ($code !== 0) {
      throw new SparcBuildException(
        pht('spc doctor failed'),
        pht('SPC FAILURE'));
    }
  }

}

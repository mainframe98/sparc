<?php

final class SparcPhpParserBuildStep extends SparcBuildStep {

  public function getName(): string {
    return 'php-parser';
  }

  public function getStepDescription(): string {
    return pht('Downloading PHP-Parser...');
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    if (!PhutilPHPParserLibrary::isAvailable()) {
      PhutilPHPParserLibrary::build();
    }
  }

}

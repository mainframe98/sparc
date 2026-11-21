<?php

/**
 * Display the current version of Arcanist.
 */
final class ArcanistVersionWorkflow extends ArcanistWorkflow {

  const VERSION = '1.0.1';

  public function supportsToolset(ArcanistToolset $toolset) {
    return true;
  }

  public function getWorkflowName() {
    return 'version';
  }

  public function getWorkflowInformation() {
    // TOOLSETS: Expand this help.

    $help = pht(<<<EOTEXT
Shows the current version.
EOTEXT
    );

    return $this->newWorkflowInformation()
      ->setSynopsis(pht('Show toolset version information.'))
      ->addExample(pht('**version**'))
      ->setHelp($help);
  }

  public function getWorkflowArguments() {
    return array();
  }

  protected function runWorkflow() {
    $metadata = id(new Phar(Phar::running(false)))
      ->getMetadata();

    $arcanist_version = sprintf(
      "arcanist %s (%s)\n",
      $metadata['arcanist-version'],
      $metadata['arcanist-timestamp']);
    $sparc_version = sprintf(
      "sparc %s (%s %s)\n",
      self::VERSION,
      $metadata['sparc-version'],
      $metadata['sparc-timestamp']);

    PhutilConsole::getConsole()
      ->writeOut($arcanist_version)
      ->writeOut($sparc_version);

    return 0;
  }

}

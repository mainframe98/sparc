<?php

final class SparcLiberateBuildStep extends SparcBuildStep {

  public function getName(): string {
    return 'liberate';
  }

  public function getStepDescription(): string {
    return pht(
      'Liberating %s...',
      PlatformSymbols::getPlatformClientName());
  }

  protected function doBuild(PhutilArgumentParser $args): void {
    $library_map_path = $this->getResourcePath(
      '/arcanist/src/__phutil_library_map__.php');
    $library_map = Filesystem::readFile($library_map_path);

    $lines = phutil_split_lines($library_map);
    foreach ($lines as $i => $line) {
      $parts = explode(' => ', $line);

      if (count($parts) < 2 || $parts[1] === 'array(') {
        continue;
      }

      $class_or_function = trim($parts[0], " '");
      $path_or_base_class = trim($parts[1], ",'");

      // Don't exclude base test cases that might be used by extensions.
      if (
        $class_or_function === 'PhutilTestCase' ||
        $class_or_function === 'ArcanistLinterTestCase' ||
        $class_or_function === 'ArcanistExternalLinterTestCase' ||
        $class_or_function === 'PhutilLibraryTestCase') {
        continue;
      }

      if (
        str_contains($path_or_base_class, 'xhpast') ||
        str_ends_with($class_or_function, 'TestCase')) {
        unset($lines[$i]);
      }
    }

    Filesystem::writeFile($library_map_path, implode("\n", $lines));
  }

}

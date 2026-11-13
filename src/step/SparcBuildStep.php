<?php

abstract class SparcBuildStep extends Phobject {

  private string $target;

  public function getTarget(): string {
    return $this->target;
  }

  public function setTarget(string $target) {
    $this->target = $target;
    return $this;
  }

  public function getPharTarget(): string {
    $target = $this->getTarget();

    if (str_ends_with($target, '.exe')) {
      $target = substr($target, 0, -4);
    }

    return $target.'.phar';
  }

  /**
   * Get the full path to a sparc-specific source.
   *
   * @param string $resource
   * @return string
   */
  final protected function getResourcePath(string $resource): string {
    return dirname(__DIR__, 2).$resource;
  }

  /**
   * Returns the list of build steps that should be executed before this step.
   *
   * This is not a guarantee that the step is actually executed!
   * Users are free to skip a step if that step was previously executed.
   *
   * Use @{method:checkRequirements} to verify that pre-requisites are
   * available, but keep in mind that in certain cases, it is desirable
   * to run without steps (e.g. build a version of sparc without patches).
   *
   * @return string[]
   */
  public function requires(): array {
    return array();
  }

  /**
   * Additional arguments that this step takes.
   *
   * @return array
   */
  public function getStepArgumentSpecs(): array {
    return array();
  }

  final public function checkRequirements(PhutilArgumentParser $args): void {
    $this->actWithCleanupOnError(
      fn () => $this->doRequirementsCheck($args),
      $args);
  }

  protected function doRequirementsCheck(PhutilArgumentParser $args): void {}

  abstract public function getName(): string;

  abstract public function getStepDescription(): string;

  abstract protected function doBuild(PhutilArgumentParser $args): void;

  final protected function log(
    string $message,
    string $type,
    string $severity = 'info'): void {

    switch ($severity) {
      default:
      case 'info':
        $color = 'blue';
        break;
      case 'success':
        $color = 'green';
        break;
      case 'error':
        $color = 'red';
        break;
      case 'warning':
        $color = 'yellow';
        break;
    }

    echo tsprintf(
      "<bg:$color>** %s **</bg> %s\n",
      $type,
      $message);
  }

  final protected function logStepAction(string $message, string $type = '') {
    echo tsprintf(
      "  <bg:cyan>** %s **</bg> %s\n",
      $type ?: pht('INFO'),
      $message);
  }

  final public function build(PhutilArgumentParser $args) {
    $this->log($this->getStepDescription(), pht('STEP'));

    $this->actWithCleanupOnError(fn () => $this->doBuild($args), $args);
  }

  private function actWithCleanupOnError(
    callable $action,
    PhutilArgumentParser $args): void {

    $throw = null;

    try {
      $action();

      return;
    } catch (SparcBuildException | CommandException $e) {
      echo $e->getMessage();
    } catch (FilesystemException $e) {
      $this->log(
        $e->getMessage(),
        pht('FILE ERROR'),
        'error');
    } catch (Throwable $t) {
      $throw = $t;
    }

    (new SparcCleanupBuildStep())->doBuild($args);

    if ($throw) {
      throw $throw;
    }

    exit(1);
  }

  /** @return static[] */
  public static function getAllSteps(): array {
    return id(new PhutilClassMapQuery())
      ->setAncestorClass(__CLASS__)
      ->setUniqueMethod('getName')
      ->execute();
  }

}

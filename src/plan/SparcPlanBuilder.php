<?php

final class SparcPlanBuilder extends Phobject {

  /** @var SparcBuildStep[] */
  private $steps;

  /** @var string[] */
  private $skip;

  public function __construct(array $steps) {
    assert_instances_of($steps, SparcBuildStep::class);

    $this->steps = mpull($steps, null, 'getName');
    $this->skip = array();
  }

  /**
   * @param string[] $step_names
   * @return $this
   */
  public function skipSteps(array $step_names) {
    $this->skip = $step_names;

    return $this;
  }

  /**
   * @return SparcBuildStep[]
   */
  public function buildPlan() {
    $plan = array();

    $unplanned_steps = $this->steps;

    foreach ($unplanned_steps as $name => $step) {
      $requirements = $step->requires();

      if (!$requirements) {
        $plan[$name] = $step;
        unset($unplanned_steps[$name]);
        continue;
      }

      $unknown_requirements = array_diff_key(
        array_fill_keys($step->requires(), true),
        $this->steps);

      if ($unknown_requirements) {
        $steps = implode(', ', $unknown_requirements);

        throw new Exception(
          pht(
            'Step %s requires unknown step(s) %s.',
            $name,
            $steps));
      }
    }

    $remaining_steps = count($unplanned_steps);

    while ($unplanned_steps) {
      foreach ($unplanned_steps as $name => $step) {
        $requirements = array_fill_keys($step->requires(), true);

        if (!array_diff_key($requirements, $plan)) {
          $plan[$name] = $step;
          unset($unplanned_steps[$name]);
        }
      }

      if ($remaining_steps === count($unplanned_steps)) {
        $steps = implode(', ', array_keys($unplanned_steps));

        throw new Exception(
          pht(
            'Planning failure. Cannot satisfy requirements for steps %s.',
            $steps));
      }

      $remaining_steps = count($unplanned_steps);
    }

    if ($this->skip) {
      return array_diff_key(
        $plan,
        array_fill_keys($this->skip, true));
    }

    return $plan;
  }

}

#!/usr/bin/env -S php -dphar.readonly=0
<?php

require_once __DIR__.'/arcanist/scripts/__init_script__.php';
phutil_load_library(__DIR__.'/src');

$steps = SparcBuildStep::getAllSteps();
$default_target = 'arc';

if (phutil_is_windows()) {
  $default_target .= '.exe';
}

$specs = array(
  array(
    'name' => 'target',
    'param' => 'filename',
    'default' => $default_target,
    'help' => pht('Target creation filename. Default: %s', $default_target),
  ),
  array(
    'name' => 'output-directory',
    'param' => 'directory',
    'default' => __DIR__.'/output',
    'help' => pht('Output directory. Default: %s', './output'),
  ),
  array(
    'name' => 'overwrite',
    'help' => pht('Overwrite existing target files. Default: false'),
  ),
  array(
    'name' => 'skip',
    'param' => 'step',
    'help' => pht('Skip these steps.'),
    'repeat' => true,
    'conflicts' => array(
      'only' => true,
    ),
  ),
  array(
    'name' => 'only',
    'param' => 'step',
    'help' => pht('Only perform this specific step.'),
    'conflicts' => array(
      'skip' => true,
    ),
  ),
  array(
    'name' => 'phar-only',
    'help' => pht('Only build the phar.'),
    'conflicts' => array(
      'skip' => true,
      'only' => true,
    ),
  ),
  array(
    'name' => 'show-plan',
    'help' => pht('Show the default build plan.'),
  ),
  array(
    'name' => 'github-token',
    'param' => 'token',
    'help' => pht(
      'Set the GitHub API token used by spc. '.
      'Useful when it cannot be passed as an environment variable.'),
  ),
);

$specs = array_merge(
  $specs,
  array_mergev(
    mpull($steps, 'getStepArgumentSpecs')));

$args = id(new PhutilArgumentParser($argv))
  ->parseStandardArguments()
  ->setSynopsis(
    pht(
      'Build %s as a single binary.',
      PlatformSymbols::getPlatformClientName()))
  ->parse($specs);

$only = $args->getArg('only');
$skip = $args->getArg('skip');

if ($args->getArg('phar-only')) {
  $skip = array( 'doctor', 'download', 'build', 'combine' );
}

if ($only || $skip) {
  $check = array_fill_keys($skip, true);

  if ($only) {
    $check[$only] = true;
  }

  $unknown = array_diff_key($check, $steps);

  if ($unknown) {
    $unknown = array_map(
      static fn ($step) => '"'.$step.'"',
      array_keys($unknown));

    if (count($unknown) > 1) {
      $error_text = pht(
        'Steps %s are not known steps.',
        implode(', ', $unknown));
    } else {
      $error_text = pht(
        'Step %s is not a known step.',
        implode(', ', $unknown));
    }

    echo tsprintf(
      "<bg:red>** %s **</bg> %s\n",
      pht('UNKNOWN STEP'),
      $error_text);

    exit(1);
  }
}

$github_token = $args->getArg('github-token');
if ($github_token) {
  putenv('GITHUB_TOKEN='.$github_token);
}

$target = $args->getArg('output-directory').'/'.$args->getArg('target');

foreach ($steps as $name => $step) {
  if ($only && $name !== $only) {
    $skip[] = $name;
  }

  $step->setTarget($target);
}

$plan_builder = id(new SparcPlanBuilder($steps))
  ->skipSteps($skip);

if ($args->getArg('show-plan')) {
  echo tsprintf(" %s\n", pht('The plan:'));

  $plan = $plan_builder->buildPlan();
  $i = 0;

  foreach ($plan as $step) {
    $i++;
    echo "  $i.  {$step->getName()}\n";
  }

  return;
}

foreach (SparcPharBuildStep::ROOTS as $lib => $root) {
  $root = Filesystem::resolvePath($root);
  $repo_api = ArcanistWorkingCopy::newFromWorkingDirectory($root)
    ->getRepositoryAPI();

  if (!($repo_api instanceof ArcanistGitAPI)) {
    echo "Library $lib is not a git repository! Sparc must be checked ".
      'out from source control.';
    exit(1);
  } else if ($repo_api->getDirtyExternalChanges()) {
    echo "Library $lib has uncommitted changes. Stash or remove them.";
    exit(1);
  }
}

foreach ($plan_builder->buildPlan() as $step) {
  $step->checkRequirements($args);

  $step->build($args);
}

echo tsprintf(
  "<bg:green>** %s **</bg>\n",
  pht('DONE'));

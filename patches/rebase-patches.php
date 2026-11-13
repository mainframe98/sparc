#!/usr/bin/env php
<?php

require_once __DIR__.'/../arcanist/scripts/__init_script__.php';

const LOCK_FILE = __DIR__.'/.rebase.lock';

/** @var ArcanistGitAPI $arc_repo_api */
$arc_repo_api = ArcanistWorkingCopy::newFromWorkingDirectory(
  __DIR__.'/../arcanist')
  ->getRepositoryAPI();

$patches = glob(__DIR__.'/*.patch');
sort($patches);

if (!$patches) {
  throw new Exception('Cannot find patches!');
}

checkWorkingCopy($arc_repo_api);

if (!Filesystem::pathExists(LOCK_FILE)) {
  foreach ($patches as $patch) {
    $arc_repo_api->execxLocal('apply %s', $patch);
    $patch_name = pathinfo($patch, PATHINFO_BASENAME);

    echo tsprintf(
      "<bg:blue>** %s **</bg> %s\n",
      pht('PATCHING'),
      pht('applying %s...', $patch_name));

    $arc_repo_api->execxLocal('commit --all -m %s', $patch_name);
  }

  Filesystem::writeFile(
    LOCK_FILE,
    $arc_repo_api->getHeadCommit());

  $return_code = $arc_repo_api->execPassthru('pull --rebase');

  if ($return_code !== 0) {
    exit($return_code);
  }
}

$patches = array_reverse($patches);

foreach ($patches as $patch) {
  $arc_repo_api->execxLocal('reset HEAD~1');
  $arc_repo_api->execxLocal('diff > %s', $patch);
  $arc_repo_api->execxLocal('restore ./');
}

Filesystem::remove(LOCK_FILE);

function checkWorkingCopy(ArcanistGitAPI $api) {
  $branch = $api->getBranchName();
  if ($branch !== 'master' && $branch !== 'stable') {
    throw new ArcanistUsageException(
      pht('You must be on the "master" or "stable" branch to use this utility.'));
  }

  $conflicts = $api->getMergeConflicts();
  if ($conflicts) {
    throw new ArcanistUsageException(
      sprintf(
        "%s\n\n%s\n    %s",
        pht(
          'There are merge conflicts in arcanist/. Resolve them '.
          'before proceeding.'),
        pht('Conflicts:'),
        implode("\n    ", $conflicts)));
  }

  $untracked = $api->getUntrackedChanges();
  if ($untracked) {
    throw new ArcanistUsageException(
      sprintf(
        "%s\n  %s\n%s",
        pht('There are untracked files in arcanist/.'),
        pht('Untracked changes:'),
        "    ".implode("\n    ", $untracked)));
  }

  $uncommitted = $api->getUncommittedChanges();
  $unstaged = $api->getUnstagedChanges();

  if ($unstaged || $uncommitted) {
    $lists = array();

    if ($unstaged) {
      $unstaged_list = "    ".implode("\n    ", $unstaged);
      $lists[] = sprintf(
        "  %s\n%s",
        pht('Unstaged changes:'),
        $unstaged_list);
    }

    if ($uncommitted) {
      $uncommitted_list = "    ".implode("\n    ", $uncommitted);
      $lists[] = sprintf(
        "  %s\n%s",
        pht('Uncommitted changes:'),
        $uncommitted_list);
    }

    throw new ArcanistUsageException(
      pht('There are uncommitted changes in arcanist/.').
      "\n".
      implode("\n\n", $lists));
  }
}

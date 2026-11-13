#!/usr/bin/env php
<?php

$_SERVER['PHUTIL_DISABLE_RUNTIME_EXTENSIONS'] = 'true';

if (function_exists('pcntl_async_signals')) {
  pcntl_async_signals(true);
} else {
  declare(ticks = 1);
}

Phar::mapPhar('arc.phar');
Phar::interceptFileFuncs();

$entrypoint_override = getenv('__SPARC_OVERRIDE_ENTRYPOINT');

if (
  $entrypoint_override &&
  !str_starts_with($entrypoint_override, '/') &&
  !str_starts_with($entrypoint_override, '.')) {
  $argv[0] .= '/'.$entrypoint_override;

  require_once 'phar://arc.phar/'.$entrypoint_override;
} else {
  if (str_ends_with($argv[0], '.phar')) {
    $argv[0] = substr($argv[0], 0, -5);
  }

  require_once 'phar://arc.phar/support/init/init-arcanist.php';
}

__HALT_COMPILER();
?>

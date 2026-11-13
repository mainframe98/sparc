<?php

final class SparcBuildException extends Exception {

  public function __construct(
    string $message,
    string $type,
    ?Throwable $previous = null) {
    parent::__construct(
      tsprintf("<bg:red>** %s **</bg> %s\n", $type, $message),
      0,
      $previous);
  }

}

@echo off

REM Bail if this isn't set, because proceeding without it leaves the build
REM in a broken state that is annoying to repair.
if not exist php-sdk-binary-tools\ (
  echo The PHP SDK binary tools repository is missing!
  goto end
)

php -dphar.readonly=0 -f "%~dp0build-sparc.php" -- --overwrite %*

:end

#!/bin/sh

set -e

if [ "$1" = "--rebuild" ]; then
  shift
  docker rmi sparc:latest
fi

if [ -z "$(docker images -q sparc:latest 2> /dev/null)" ]; then
  docker build \
    --tag sparc:latest \
    --file Dockerfile \
    .
fi

# Create the spc_cache directory as the current user.
# The docker daemon creates it as root.
mkdir --parents ./spc_cache

docker run \
  --tty \
  --rm \
  --user $(id -u):$(id -g) \
  --volume .:/sparc \
  --volume ./spc_cache:/spc_cache \
  --env GITHUB_TOKEN \
  sparc:latest \
  "$@"

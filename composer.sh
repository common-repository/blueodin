#!/bin/bash

#set -u
set -e

#VERSION=2.1.12
VERSION=2.2.13

export COMPOSER_HOME=$HOME/.config/composer
export COMPOSER_CACHE_DIR=$HOME/.cache/composer

docker="docker run --rm -u "$(id -u):$(id -g)" 
    --interactive 
    --tty 
    --env COMPOSER_HOME 
    --env COMPOSER_CACHE_DIR 
    --volume ${COMPOSER_HOME:-$HOME/.config/composer}:$COMPOSER_HOME 
    --volume ${COMPOSER_CACHE_DIR:-$HOME/.cache/composer}:$COMPOSER_CACHE_DIR 
    --volume $SSH_AUTH_SOCK:/ssh-auth.sock 
    --env SSH_AUTH_SOCK=/ssh-auth.sock 
     --volume $PWD:/app 
    composer:${VERSION}"

case $1 in

  update | install | outdated | require )
    $docker composer --ansi "$@" --ignore-platform-reqs
    ;;

  *)
      $docker composer --ansi "$@"
    ;;
esac


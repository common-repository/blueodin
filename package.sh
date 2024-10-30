#!/bin/bash

set -e
set -u

./composer.sh dump-autoload

packagename=blueodin-plugin

git archive HEAD --prefix=$packagename/ --format=zip -o ../$packagename.zip
(cd .. && zip $packagename.zip -r $packagename/vendor )

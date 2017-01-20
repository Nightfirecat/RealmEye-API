#!/bin/bash
git fetch
git clean -dfx -e 'config.ini*'
git reset origin/"${1}" --hard
git checkout HEAD -- "$(git rev-parse --show-toplevel)"
grep 'Id' index.php

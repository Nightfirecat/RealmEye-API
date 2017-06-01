#!/bin/bash
git fetch
git clean -dfx -e 'config.ini*'
git reset origin/"${1}" --hard
git log -1 --format='%h' > rev.txt

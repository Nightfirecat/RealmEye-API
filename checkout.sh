#!/bin/bash
git fetch
cat generated-files.txt | xargs git clean -dfx
git reset origin/"${1}" --hard
git log -1 --format='%h' > rev.txt

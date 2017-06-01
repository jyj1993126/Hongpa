#!/bin/bash

# copy git hooks
CUR_DIR=$(cd "$(dirname "$0")"; pwd)

cd $CUR_DIR/..

cp extra/git-hooks/* .git/hooks/

# alias dcm
file="/Users/`whoami`/.bashrc"
source $file

alias "dcm" > /dev/null 2>&1
if [ $? -gt 0 ]; then
    command="alias dcm=docker-compose"
    $command
    echo $command >> $file
fi

#!/bin/bash

NAME=$1
VERSION=$2

FILE=$HOME/bundles/$NAME-$VERSION.tgz

if [ ! -f $FILE ]; then
	echo "file does not exist"
	exit 1
fi



tar -xzf $HOME/bundles/$NAME-$VERSION.tgz -C ~/qa_test

echo "installed bundle: $NAME-v$VERSION to qa"

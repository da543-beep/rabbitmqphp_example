#!/bin/bash

NAME=$1

VERSION=$(mysql bundles -N -e "select version from versions where bundle_name = '$NAME' and status = 'passed' order by version desc limit 1;")

if [ -z "$VERSION" ]; then
	echo "no previous version with that bundle name"
	exit 1
fi

./install.sh $NAME $VERSION

echo "rolled back to bundle: $NAME v$VERSION" 

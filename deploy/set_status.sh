#!/bin/bash
set -e

NAME=$1
VERSION=$2
STATUS=$3

if [ "$STATUS" != passed ] && [ "$STATUS" != failed ]; then
	echo "status must be passed or failed"
    	exit 1
fi

mysql bundles -e "UPDATE versions set status = '$STATUS' where bundle_name = '$NAME' and version = $VERSION"

echo "set status of $NAME v$VERSION to $STATUS"

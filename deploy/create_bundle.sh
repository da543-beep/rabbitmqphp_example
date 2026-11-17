#!/bin/bash
set -e

NAME=$1
SOURCE=$2

VERSION=$(mysql bundles -N -e "select coalesce(max(version), 0) + 1 from versions where bundle_name = '$NAME';")

echo "next version of $NAME is $VERSION"

FILE=$HOME/bundles/$NAME-$VERSION.tgz

tar -czf "$FILE" -C "$(dirname "$SOURCE")" "$(basename "$SOURCE")"

echo "created file $FILE"

mysql bundles -e "INSERT INTO versions (bundle_name, version) VALUES ('$NAME', $VERSION);"

echo "recorded $NAME, v$VERSION as new"

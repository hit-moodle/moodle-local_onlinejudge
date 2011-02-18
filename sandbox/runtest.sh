#!/bin/sh

# The first argument is the expected return value, so skip it
skip=true
for arg ; do
    if [ $skip = true ] ; then
        skip=false
        continue
    fi
    args="$args $arg"
done

./sand $args
rval=$?

if [ $1 = $rval ] ; then
    exit 0
else
    exit $rval
fi

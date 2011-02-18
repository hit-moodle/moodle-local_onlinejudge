#!/bin/sh

echo Detecting syscalls...

SYSCALLS=`./detector -l memory=2000000000 223`

for syscall in $SYSCALLS ; do
    if [ $syscall != 223 ] ; then
        sequence="$sequence $syscall,"
    else
        init_syscalls=$sequence
        sequence=''
    fi
done
allowed_syscalls=$sequence

echo INIT_SYSCALLS:$init_syscalls
echo ALLOWED_SYSCALLS:$allowed_syscalls

echo Generate policy.c
sed "s/##INIT_SYSCALLS##/   $init_syscalls/g" policy.c.skel | sed "s/##ALLOWED_SYSCALLS##/   $allowed_syscalls/g" > policy.c

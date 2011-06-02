#!/bin/sh

echo Detecting syscalls...

SYSCALLS=`./detector -l memory=2000000000 223 < /dev/null`

for syscall in $SYSCALLS ; do
    if [ $syscall = IGNORE ] ; then
        continue
    fi
    if [ $syscall != 223 ] ; then
        sequence="$sequence $syscall,"
    else
        init_syscalls=$sequence
        sequence=''
    fi
done

allowed_syscalls=''
for syscall in $sequence ; do
    keep=true
    for had in $allowed_syscalls ; do
        if [ $had = $syscall ] ; then
            keep=false
        fi
    done
    if [ $keep = true ] ; then
        allowed_syscalls="$allowed_syscalls $syscall"
    fi
done


echo INIT_SYSCALLS:$init_syscalls
echo ALLOWED_SYSCALLS:$allowed_syscalls

sed "s/##INIT_SYSCALLS##/   $init_syscalls/g" policy.c.skel | sed "s/##ALLOWED_SYSCALLS##/   $allowed_syscalls/g" > policy.c

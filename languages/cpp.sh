#!/bin/sh

# C++ compile wrapper-script for onlinejudge.
# See that script for syntax and more info.

SOURCE="$1"
DEST="$2"

# -D_MOODLE_ONLINE_JUDGE_:  So the submission can do differently in onlinejudge
# -Wall:	Report all warnings
# -O2:		Level 2 optimizations (default for speed)
# -static:	Static link with all libraries
g++ -D_MOODLE_ONLINE_JUDGE_ -Wall -static -o $DEST $SOURCE
exit $?

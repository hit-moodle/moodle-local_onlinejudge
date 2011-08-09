################################################################################
# The Sandbox Libraries Makefile                                               #
#                                                                              #
# Copyright (C) 2010 onward SUN Zhigang, sunner@gmail.com                      #
# All rights reserved.                                                         #
#                                                                              #
# Redistribution and use in source and binary forms, with or without           #
# modification, are permitted provided that the following conditions are met:  #
#                                                                              #
# 1. Redistributions of source code must retain the above copyright notice,    #
#    this list of conditions and the following disclaimer.                     #
#                                                                              #
# 2. Redistributions in binary form must reproduce the above copyright notice, #
#    this list of conditions and the following disclaimer in the documentation #
#    and/or other materials provided with the distribution.                    #
#                                                                              #
# 3. Neither the name of the author(s) nor the names of its contributors may   #
#    be used to endorse or promote products derived from this software without #
#    specific prior written permission.                                        #
#                                                                              #
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"  #
# AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE    #
# IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE   #
# ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE     #
# LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR          #
# CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF         #
# SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS     #
# INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN      #
# CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)      #
# ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE   #
# POSSIBILITY OF SUCH DAMAGE.                                                  #
################################################################################

# initialization
CC = cc
CCFLAGS = -m32 -Wall -ansi -std=gnu99 -g0 -O3
CCINCS = -I ./libsandbox/src
CCDEFS = -D SANDBOX -D NDEBUG -D WITH_NATIVE_TRACER -D WITHOUT_TSC_COUNTER

all: test interclean

libsandbox: FORCE
	$(MAKE) -C libsandbox libsandbox.a

sand: policy.o sand.o libsandbox
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS)  -o sand sand.o policy.o ./libsandbox/libsandbox.a

policy.o: policy.c
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS) -c policy.c

policy.c: detect.sh detector 223 policy.c.skel
	./detect.sh

223: 223.c
	$(CC) $(CCFLAGS) --static -o $@ $+

detector: libsandbox policy_print.o sand.o
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS)  -o detector sand.o policy_print.o ./libsandbox/libsandbox.a

sand.o: sand.c
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS) -c sand.c

policy_print.o: policy_print.c
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS) -c policy_print.c

test: stdin stdout fork open memlimit cpulimit sigsegv return1
	@echo All testcases are OK

stdin: stdin_test sand
	./runtest.sh 1 $< < /dev/null

stdin_test: testcase/stdin.c
	$(CC) $(CCFLAGS) --static -o $@ $+

stdout: stdout_test sand
	./runtest.sh 1 $< > /dev/null

stdout_test: testcase/stdout.c
	$(CC) $(CCFLAGS) --static -o $@ $+

fork: fork_test sand
	./runtest.sh 2 $<

fork_test: testcase/fork.c
	$(CC) $(CCFLAGS) --static -o $@ $+

open: open_test sand
	./runtest.sh 2 $<

open_test: testcase/open.c
	$(CC) $(CCFLAGS) --static -o $@ $+

memlimit: memlimit_test sand
	./runtest.sh 3 -l memory=1048576 $<

memlimit_test: testcase/memlimit.c
	$(CC) $(CCFLAGS) --static -o $@ $+

cpulimit: cpulimit_test sand
	./runtest.sh 5 -l cpu=1000 $<

cpulimit_test: testcase/cpulimit.c
	$(CC) $(CCFLAGS) --static -o $@ $+

sigsegv: sigsegv_test sand
	./runtest.sh 6 $< > /dev/null

sigsegv_test: testcase/sigsegv.c
	$(CC) $(CCFLAGS) --static -o $@ $+

return1: return1_test sand
	./runtest.sh 7 $<

return1_test: testcase/return1.c
	$(CC) $(CCFLAGS) --static -o $@ $+

# Force make run into subdirectories even no changes on source
FORCE:

interclean:
	$(MAKE) -C libsandbox clean
	rm -f *.o *_test detector 223 policy.c

clean: interclean
	rm -f sand

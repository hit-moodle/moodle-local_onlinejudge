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
CCFLAGS = -Wall -ansi -std=gnu99 -g0 -O3
CCINCS = -I ./libsandbox/src
CCDEFS = -D SANDBOX -D NDEBUG -D WITH_NATIVE_TRACER -D WITHOUT_TSC_COUNTER

all: sand

libsandbox: FORCE
	$(MAKE) -C libsandbox libsandbox.a

sand: policy.o sand.o libsandbox
	$(CC) $(CCFLAGS) $(CCDEFS) -D POLICY3 $(CCINCS)  -o sand sand.o policy.o ./libsandbox/libsandbox.a

policy.o: policy.c
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS) -c policy.c

policy.c: detect.sh detector 223
	./detect.sh

223: 223.c
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS)  --static -o 223 223.c

detector: libsandbox policy_print.o sand.o
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS)  -o detector sand.o policy_print.o ./libsandbox/libsandbox.a

sand.o: sand.c
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS) -c sand.c
    
policy_print.o: policy_print.c
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS) -c policy_print.c

# Force make run into subdirectories even no changes on source
FORCE:

clean:
	$(MAKE) -C libsandbox clean
	rm -f *.o sand detector 223 policy.c

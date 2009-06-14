################################################################################
# The Sandbox Libraries Makefile                                               #
#                                                                              #
# Copyright (C) 2004-2007 LIU Yu, pineapple.liu@gmail.com                      #
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
CCFLAGS = -Wall -ansi -std=gnu99 -g0 -O3 -shared -fPIC -pthread 
CCINCS = -I src
CCDEFS = -D SANDBOX -D NDEBUG -D WITH_NATIVE_TRACER -D WITHOUT_TSC_COUNTER
AR = ar
ARFLAGS = rcvs
AWK = gawk
DOCTOOL = doxygen
LN = ln -fs
TAR = tar
TAREXT = tar.gz
MD = mkdir -p
INSTALL = install -D

# buid sources and targets
LIBRARY = sandbox
VERSION = 0.2.14
RELEASE = 2
$(LIBRARY)_hdr = src/platform.h src/sandbox.h src/symbols.h
$(LIBRARY)_src = $($(LIBRARY)_hdr:.h=.c)
$(LIBRARY)_obj = $($(LIBRARY)_src:.c=.o)
$(LIBRARY)_dir = src doc dist
$(LIBRARY)_spec = $(LIBRARY).spec
$(LIBRARY)_sdist = $($(LIBRARY)_hdr) $($(LIBRARY)_src:symbols.c=symbols.awk) \
$($(LIBRARY)_spec) Makefile Doxyfile CHANGES README

# build branches
.PHONY: all sdist docs clean install

all: lib$(LIBRARY).so lib$(LIBRARY).a

lib$(LIBRARY).so : lib$(LIBRARY).so.$(VERSION)
	$(LN) lib$(LIBRARY).so.$(VERSION) lib$(LIBRARY).so

lib$(LIBRARY).so.$(VERSION) : $($(LIBRARY)_obj)
	$(CC) $(CCFLAGS) $(CCDEFS) -o $(@) $($(LIBRARY)_obj)

lib$(LIBRARY).a: $($(LIBRARY)_obj)
	$(AR) $(ARFLAGS) $(@) $($(LIBRARY)_obj)

$($(LIBRARY)_obj): $($(LIBRARY)_hdr) $($(LIBRARY)_src)

$($(LIBRARY)_dir):
	$(MD) $(@)

clean:
	rm -rf doc
	rm -f $($(LIBRARY)_obj) lib$(LIBRARY).a lib$(LIBRARY).so \
	lib$(LIBRARY).so.$(VERSION)

sdist: dist $($(LIBRARY)_sdist)
	$(TAR) -czf dist/lib$(LIBRARY)-$(VERSION).$(TAREXT) $($(LIBRARY)_sdist)
	rm -rf dist/lib$(LIBRARY)-$(VERSION)
	$(MD) dist/lib$(LIBRARY)-$(VERSION)
	$(TAR) -C dist/lib$(LIBRARY)-$(VERSION) -xzf dist/lib$(LIBRARY)-$(VERSION).$(TAREXT)
	$(TAR) -C dist -czvf dist/lib$(LIBRARY)-$(VERSION).$(TAREXT) lib$(LIBRARY)-$(VERSION)
	rm -rf dist/lib$(LIBRARY)-$(VERSION)

install:
	$(INSTALL) -m0755 lib$(LIBRARY).so.$(VERSION) $(DESTDIR)/usr/lib/lib$(LIBRARY).so.$(VERSION)
	$(LN) /usr/lib/lib$(LIBRARY).so.$(VERSION) $(DESTDIR)/usr/lib/lib$(LIBRARY).so
	$(INSTALL) -m0644 src/sandbox.h $(DESTDIR)/usr/include/sandbox.h

docs: doc $($(LIBRARY)_sdist)
	$(DOCTOOL)

%.c: %.awk
	$(AWK) -f $(<) $($(LIBRARY)_hdr) > $(@)

%.o: %.c
	$(CC) $(CCFLAGS) $(CCDEFS) $(CCINCS) -c -o $(@) $(<)

default: all

all:
	$(MAKE) -C libsandbox libsandbox.a
	gcc -Wall -o sand sand.c libsandbox/libsandbox.a -I./libsandbox/src

clean:
	$(MAKE) -C libsandbox clean
	rm -f sand

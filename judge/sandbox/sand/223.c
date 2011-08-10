/* Using this program to detect a valid syscall sequence for normal program */

#include <unistd.h>
#include <stdlib.h>
#include <string.h>
#include <stdio.h>

#define HEAP_ALLOC_SIZE     (1024 * 1024 * 64)    /* 64MB */

int main(void)
{
    char *p;

    /* syscall no 223 is unused at least in Linux 2.6 kernel */
    syscall(223);

    /* Since stdin/stdout normally call read/write, no need to detect */
    getchar();
    printf("IGNORE ");

    /* Some programs may use heap */
    p = malloc(HEAP_ALLOC_SIZE);
    memset(p, 0, HEAP_ALLOC_SIZE);
    free(p);

    return 0;
}

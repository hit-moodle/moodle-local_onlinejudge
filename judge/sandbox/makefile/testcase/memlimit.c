#include <stdlib.h>

int main(void)
{
    int *p = malloc(1024*1024*1024);
    free(p);

    return 0;
}

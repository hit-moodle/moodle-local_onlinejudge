#include <fcntl.h>

int main(void)
{
    open("/etc/rc.local", O_RDONLY);
    return 0;
}

#include <sandbox.h>
#include <linux/unistd.h>

int allowed_syscall [] = {
    __NR_read,
    __NR_write,
    __NR_munmap,
    __NR_mmap2,
    __NR_fstat64,
    __NR_exit_group,
    0
};

int allow(const event_t * pevent)
{
    static initing = 1;

    if (initing) 
    {
        // set_thread_area is the last init syscall
        if (pevent->data._SYSCALL.scno == __NR_set_thread_area)
            initing = 0;
        return 1;
    }

    int i;

    for (i=0; allowed_syscall[i] && pevent->data._SYSCALL.scno != allowed_syscall[i]; i++)
        ;

    return allowed_syscall[i];
}

static void 
__sandbox_default_policy(const policy_t * ppolicy, const event_t * pevent, 
                         action_t * paction)
{
    assert(pevent && paction);
    switch (pevent->type)
    {
    case S_EVENT_SYSCALL:
        if (allow(pevent))
            *paction = (action_t){S_ACTION_CONT};
        else
            *paction = (action_t){S_ACTION_KILL, {{S_RESULT_RF}}};
        break;
    case S_EVENT_SYSRET:
        *paction = (action_t){S_ACTION_CONT};
        break;
    case S_EVENT_EXIT:
        switch (pevent->data._EXIT.code)
        {
        case EXIT_SUCCESS:
            *paction = (action_t){S_ACTION_FINI, {{S_RESULT_OK}}};
            break;
        default:
            *paction = (action_t){S_ACTION_FINI, {{S_RESULT_AT}}};
            break;
        }
        break;
    case S_EVENT_ERROR:
        *paction = (action_t){S_ACTION_KILL, {{S_RESULT_IE}}};
        break;
    case S_EVENT_SIGNAL:
        *paction = (action_t){S_ACTION_KILL, {{S_RESULT_RT}}};
        break;
    case S_EVENT_QUOTA:
        switch (pevent->data._QUOTA.type)
        {
        case S_QUOTA_WALLCLOCK:
        case S_QUOTA_CPU:
            *paction = (action_t){S_ACTION_KILL, {{S_RESULT_TL}}};
            break;
        case S_QUOTA_MEMORY:
            *paction = (action_t){S_ACTION_KILL, {{S_RESULT_ML}}};
            break;
        case S_QUOTA_DISK:
            *paction = (action_t){S_ACTION_KILL, {{S_RESULT_OL}}};
            break;
        }
        break;
    default:
        *paction = (action_t){S_ACTION_KILL, {{S_RESULT_IE}}};
        break;
    }
}

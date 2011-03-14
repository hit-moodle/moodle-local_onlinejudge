/*******************************************************************************
 * Copyright (C) 2004-2007 LIU Yu, pineapple.liu@gmail.com                     *
 * All rights reserved.                                                        *
 *                                                                             *
 * Redistribution and use in source and binary forms, with or without          *
 * modification, are permitted provided that the following conditions are met: *
 *                                                                             *
 * 1. Redistributions of source code must retain the above copyright notice,   *
 *    this list of conditions and the following disclaimer.                    *
 *                                                                             *
 * 2. Redistributions in binary form must reproduce the above copyright        *
 *    notice, this list of conditions and the following disclaimer in the      *
 *    documentation and/or other materials provided with the distribution.     *
 *                                                                             *
 * 3. Neither the name of the author(s) nor the names of its contributors may  *
 *    be used to endorse or promote products derived from this software        *
 *    without specific prior written permission.                               *
 *                                                                             *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" *
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE   *
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE  *
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE    *
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR         *
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF        *
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS    *
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN     *
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)     *
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE  *
 * POSSIBILITY OF SUCH DAMAGE.                                                 *
 ******************************************************************************/

#include "sandbox.h"
#include "symbols.h"
#include "platform.h"
#include <assert.h>             /* assert() */
#include <errno.h>              /* ECHILD, EINVAL */
#include <grp.h>                /* struct group, getgrgid() */
#include <pwd.h>                /* struct passwd, getpwuid() */
#include <sys/stat.h>           /* struct stat, stat(), fstat() */
#include <sys/time.h>           /* setitimer(), ITIMER_{REAL,PROF} */
#include <sys/wait.h>           /* wait4() */
#include <stdlib.h>             /* EXIT_{SUCCESS,FAILURE} */
#include <string.h>             /* str{cpy,cmp,str}(), mem{set,cpy}() */
#include <unistd.h>             /* fork(), access(), chroot(), getpid(),  
                                   {R,X}_OK, STD{IN,OUT,ERR}_FILENO */

/* Local functions prototype */
static void __sandbox_task_init(task_t *, const char * *);
static bool __sandbox_task_check(const task_t *);
static int  __sandbox_task_execute(task_t *);
static void __sandbox_task_fini(task_t *);

static void __sandbox_stat_init(stat_t *);
static void __sandbox_stat_fini(stat_t *);

static void __sandbox_ctrl_init(ctrl_t *, thread_func_t, thread_func_t);
static void __sandbox_ctrl_fini(ctrl_t *);

#ifdef WITH_NATIVE_TRACER
static void * __sandbox_tracer(sandbox_t *);
#else
#define __sandbox_tracer (NULL)
#endif /* WITH_NATIVE_TRACER */

#ifdef DELETED
#ifdef WITH_NATIVE_MONITOR
static void * __sandbox_monitor(sandbox_t *);
#else
#define __sandbox_monitor (NULL)
#endif /* WITH_NATIVE_MONITOR */
#endif /* DELETED */

int 
sandbox_init(sandbox_t * psbox, const char * argv[])
{
    FUNC_BEGIN("sandbox_init(%p,%p)", psbox, argv);
    
    assert(psbox);
    
    if (psbox == NULL)
    {
        WARNING("psbox: bad pointer");
        FUNC_RET(-1, "sandbox_init()");
    }
    
    pthread_mutex_init(&psbox->mutex, NULL);
    P(&psbox->mutex);
    pthread_cond_init(&psbox->update, NULL);
    __sandbox_task_init(&psbox->task, argv);
    __sandbox_stat_init(&psbox->stat);
    __sandbox_ctrl_init(&psbox->ctrl, (thread_func_t)__sandbox_tracer, 
                                      (thread_func_t)(NULL));
    V(&psbox->mutex);
    
    P(&psbox->mutex);
    psbox->result = S_RESULT_PD;
    psbox->status = S_STATUS_PRE;
    pthread_cond_broadcast(&psbox->update);
    V(&psbox->mutex);
    
    FUNC_RET(0, "sandbox_init()");
}

int 
sandbox_fini(sandbox_t * psbox)
{
    FUNC_BEGIN("sandbox_fini(%p)", psbox);
    
    assert(psbox);
    
    if (psbox == NULL)
    {
        WARNING("psbox: bad pointer");
        FUNC_RET(-1, "sandbox_fini()");
    }
    
    P(&psbox->mutex);
    psbox->result = S_RESULT_PD;
    psbox->status = S_STATUS_FIN;
    pthread_cond_broadcast(&psbox->update);
    V(&psbox->mutex);
    
    P(&psbox->mutex);
    __sandbox_task_fini(&psbox->task);
    __sandbox_stat_fini(&psbox->stat);
    __sandbox_ctrl_fini(&psbox->ctrl);
    pthread_cond_destroy(&psbox->update);
    V(&psbox->mutex);
    pthread_mutex_destroy(&psbox->mutex);
    
    FUNC_RET(0, "sandbox_fini()");
}

bool 
sandbox_check(sandbox_t * psbox)
{
    FUNC_BEGIN("sandbox_check(%p)", psbox);

    assert(psbox);
    
    if (psbox == NULL)
    {
        WARNING("psbox: bad pointer");
        FUNC_RET(false, "sandbox_check()");
    }
    
    P(&psbox->mutex);
    
    /* Don't change the state of a running sandbox */
    if (IS_RUNNING(psbox) || IS_BLOCKED(psbox))
    {
        V(&psbox->mutex);
        FUNC_RET(false, "sandbox_check()");
    }
    DBG("passed sandbox status check");
    
    /* Clear previous statistics and status */
    if (IS_FINISHED(psbox))
    {
        __sandbox_stat_fini(&psbox->stat);
        __sandbox_stat_init(&psbox->stat);
        psbox->result = S_RESULT_PD;
    }
    
    /* Update status to PRE */
    if (psbox->status != S_STATUS_PRE)
    {
        psbox->status = S_STATUS_PRE;
        pthread_cond_broadcast(&psbox->update);
    }
    
    if (!__sandbox_task_check(&psbox->task))
    {
        V(&psbox->mutex);
        FUNC_RET(false, "sandbox_check()");
    }
    DBG("passed task spec validation");
    
    if (psbox->ctrl.tracer == NULL)
    {
        V(&psbox->mutex);
        FUNC_RET(false, "sandbox_check()");
    }
    DBG("passed ctrl tracer validation");
    
    #ifdef DELETED
    if (psbox->ctrl.monitor == NULL)
    {
        V(&psbox->mutex);
        FUNC_RET(false, "sandbox_check()");
    }
    DBG("passed ctrl monitor validation");
    #endif /* DELETED */
    
    /* Update status to RDY */
    if (psbox->status != S_STATUS_RDY)
    {
        psbox->status = S_STATUS_RDY;
        pthread_cond_broadcast(&psbox->update);
    }
    V(&psbox->mutex);
    
    FUNC_RET(true, "sandbox_check()");
}

result_t * 
sandbox_execute(sandbox_t * psbox)
{
    FUNC_BEGIN("sandbox_execute(%p)", psbox);
    
    assert(psbox);

    if (psbox == NULL)
    {
        WARNING("psbox: bad pointer");
        FUNC_RET(NULL, "sandbox_execute()");
    }
    
    if (!sandbox_check(psbox))
    {
        WARNING("sandbox pre-execution state check failed");
        FUNC_RET(&psbox->result, "sandbox_execute()");
    }
    
    #ifdef DELETED
    pthread_t tid;

    if (pthread_create(&tid, NULL, psbox->ctrl.monitor, (void *)psbox) != 0)
    {
        WARNING("failed creating the monitor thread");
        FUNC_RET(&psbox->result, "sandbox_execute()");
    }
    DBG("created the monitor thread");
    #endif /* DELETED */
    
    /* Fork the prisoner process */
    psbox->ctrl.pid = fork();
    
    /* Execute the prisoner program */
    if (psbox->ctrl.pid == 0)
    {
        DBG("entering: the prisoner program");
        /* Start executing the prisoner program */
        _exit(__sandbox_task_execute(&psbox->task));
    }
    else
    {
        DBG("target program forked as pid %d", psbox->ctrl.pid);
        /* Start executing the tracing thread */
        psbox->ctrl.tracer(psbox);
    }
    
    #ifdef DELETED
    if (pthread_join(tid, NULL) != 0)
    {
        WARNING("failed joining the monitor thread");
        if (pthread_cancel(tid) != 0)
        {
            WARNING("failed canceling the monitor thread");
            FUNC_RET(NULL, "sandbox_execute()");
        }
    }
    DBG("joined the monitor thread");
    #endif /* DELETED */
    
    FUNC_RET(&psbox->result, "sandbox_execute()");
}

static void 
__sandbox_task_init(task_t * ptask, const char * argv[])
{ 
    PROC_BEGIN("__sandbox_task_init(%p)", ptask);
    
    assert(ptask);              /* argv could be NULL */
    
    memset(ptask, 0, sizeof(task_t));
    
    int argc = 0;
    size_t offset = 0;
    if (argv != NULL)
    {
        while (argv[argc] != NULL)
        {
            size_t delta  = strlen(argv[argc]) + 1;
            if (offset + delta >= sizeof(ptask->comm.buff))
            {
                break;
            }
            strcpy(ptask->comm.buff + offset, argv[argc]);
            ptask->comm.args[argc++] = offset;
            offset += delta;
        }
    }
    ptask->comm.buff[offset] = '\0';
    ptask->comm.args[argc] = -1;
    
    strcpy(ptask->jail, "/");
    ptask->uid = getuid();
    ptask->gid = getgid();
    ptask->ifd = STDIN_FILENO;
    ptask->ofd = STDOUT_FILENO;
    ptask->efd = STDERR_FILENO;
    ptask->quota[S_QUOTA_WALLCLOCK] = RES_INFINITY;
    ptask->quota[S_QUOTA_CPU] = RES_INFINITY;
    ptask->quota[S_QUOTA_MEMORY] = RES_INFINITY;
    ptask->quota[S_QUOTA_DISK] = RES_INFINITY;
    PROC_END("__sandbox_task_init()");
}

static void 
__sandbox_task_fini(task_t * ptask)
{
    PROC_BEGIN("__sandbox_task_fini(%p)", ptask);
    
    assert(ptask);
    
    /* TODO */

    PROC_END("__sandbox_task_fini()");
}

static bool
__sandbox_task_check(const task_t * ptask)
{
    FUNC_BEGIN("__sandbox_task_check(%p)", ptask);

    assert(ptask);
    
    struct stat s;
    
    /* 1. check comm field
     *   a) if program file is an existing regular file
     *   b) if program file is executable
     */
    if ((stat(ptask->comm.buff, &s) < 0) || !S_ISREG(s.st_mode))
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    
    if (access(ptask->comm.buff, X_OK | F_OK) < 0)
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    
    DBG("passed target program permission test");
    
    /* 2. check uid, gid fields
     *   a) if user exists
     *   b) if group exists
     *   c) current user's previlege to set{uid,gid}
     */
    struct passwd * pw = NULL;
    if ((pw = getpwuid(ptask->uid)) == NULL)
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    DBG("passed user identity test");
    
    struct group * gr = NULL;
    if ((gr = getgrgid(ptask->gid)) == NULL)
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    DBG("passed group identity test");
    
    if ((getuid() != (uid_t)0) && ((getuid() != ptask->uid) || 
                                   (getgid() != ptask->gid)))
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    DBG("passed set{uid,gid} previlege test");
    
    /* 3. check jail field (if jail is not "/")
     *   a) only super user can chroot!
     *   b) if jail path is accessable and readable
     *   c) if jail is a prefix to the target program command line
     */
    if (strcmp(ptask->jail, "/") != 0)
    {
        if (getuid() != (uid_t)0)
        {
            FUNC_RET(false, "__sandbox_task_check()");
        }
        if (access(ptask->jail, X_OK | R_OK) < 0)
        {
            FUNC_RET(false, "__sandbox_task_check()");
        }
        if ((stat(ptask->jail, &s) < 0) || !S_ISDIR(s.st_mode))
        {
            FUNC_RET(false, "__sandbox_task_check()");
        }
        if (strstr(ptask->comm.buff, ptask->jail) != ptask->comm.buff)
        {
            FUNC_RET(false, "__sandbox_task_check()");
        }
    }
    DBG("passed jail validity test");
    
    /* 4. check ifd, ofd, efd are existing file descriptors
     *   a) if ifd is readable by current user
     *   b) if ofd and efd are writable by current user
     */
    if ((fstat(ptask->ifd, &s) < 0) || !(S_ISCHR(s.st_mode) || 
         S_ISREG(s.st_mode) || S_ISFIFO(s.st_mode)))
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    if (!((S_IRUSR & s.st_mode) && (s.st_uid == getuid())) && 
        !((S_IRGRP & s.st_mode) && (s.st_gid == getgid())) && 
        !(S_IROTH & s.st_mode) && !(getuid() == (uid_t)0))
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    DBG("passed input channel validity test");
    
    if ((fstat(ptask->ofd, &s) < 0) || !(S_ISCHR(s.st_mode) ||
         S_ISREG(s.st_mode) || S_ISFIFO(s.st_mode)))
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    if (!((S_IWUSR & s.st_mode) && (s.st_uid == getuid())) && 
        !((S_IWGRP & s.st_mode) && (s.st_gid == getgid())) && 
        !(S_IWOTH & s.st_mode) && !(getuid() == (uid_t)0))
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    DBG("passed output channel validity test");
    
    if ((fstat(ptask->efd, &s) < 0) || !(S_ISCHR(s.st_mode) ||
         S_ISREG(s.st_mode) || S_ISFIFO(s.st_mode)))
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    if (!((S_IWUSR & s.st_mode) && (s.st_uid == getuid())) && 
        !((S_IWGRP & s.st_mode) && (s.st_gid == getgid())) && 
        !(S_IWOTH & s.st_mode) && !(getuid() == (uid_t)0))
    {
        FUNC_RET(false, "__sandbox_task_check()");
    }
    DBG("passed error channel validity test");
    
    FUNC_RET(true, "__sandbox_task_check()");
}

static int
__sandbox_task_execute(task_t * ptask)
{
    FUNC_BEGIN("sandbox_task_execute(%p)", ptask);
    
    assert(ptask);
    
    /* Run the prisoner program in a separate process group */
    if (setsid() < 0)
    {
        WARNING("failed setting session id");
        return EXIT_FAILURE;
    }
    
    /* Close fd's not used by the prisoner program */
    int fd;
    for (fd = 0; fd < FILENO_MAX; fd++)
    {
        if ((fd == ptask->ifd) || (fd == ptask->ofd) || (fd == ptask->efd))
        {
            continue;
        }
        close(fd);
    }
    
    /* Redirect I/O channel */
    
    if (dup2(ptask->efd, STDERR_FILENO) < 0)
    {
        WARNING("failed redirecting error channel");
        return EXIT_FAILURE;
    }
    DBG("dup2: %d->%d", ptask->efd, STDERR_FILENO);
    
    if (dup2(ptask->ofd, STDOUT_FILENO) < 0)
    {
        WARNING("failed redirecting output channel(s)");
        return EXIT_FAILURE;
    }
    DBG("dup2: %d->%d", ptask->ofd, STDOUT_FILENO);
    
    if (dup2(ptask->ifd, STDIN_FILENO) < 0)
    {
        WARNING("failed redirecting input channel(s)");
        return EXIT_FAILURE;
    }
    DBG("dup2: %d->%d", ptask->ifd, STDIN_FILENO);
    
    /* Apply security restrictions */
    
    if (strcmp(ptask->jail, "/") != 0)
    {
        if (chdir(ptask->jail) < 0)
        {
            WARNING("failed switching to jail directory");
            return EXIT_FAILURE;
        }
        if (chroot(ptask->jail) < 0)
        {
            WARNING("failed chroot to jail directory");
            return EXIT_FAILURE;
        }
        DBG("jail: \"%s\"", ptask->jail);
    }
    
    /* Change identity before executing the targeted program */
    
    if (setgid(ptask->gid) < 0)
    {
        WARNING("changing group identity");
        return EXIT_FAILURE;
    }
    DBG("setgid: %lu", (unsigned long)ptask->gid);
    
    if (setuid(ptask->uid) < 0)
    {
        WARNING("changing owner identity");
        return EXIT_FAILURE;
    }
    DBG("setuid: %lu", (unsigned long)ptask->uid);
    
    /* Prepare argument arrray to be passed to execve() */
    char * argv [ARG_MAX] = {NULL};
    int argc = 0;
    while ((argc + 1 < ARG_MAX) && (ptask->comm.args[argc] >= 0))
    {
        argv[argc] = ptask->comm.buff + ptask->comm.args[argc];
        argc++;
    }
    if (strcmp(ptask->jail, "/") != 0)
    {
        argv[0] += strlen(ptask->jail);
    }
    argv[argc] = NULL;
    
    #ifndef NDEBUG
    argc = 0;
    while (argv[argc] != NULL)
    {
        DBG("argv[%d]: \"%s\"", argc, argv[argc]);
        argc++;
    }
    #endif /* !defined NDEBUG */
    
    /* Most kinds of resource restrictions are applied through the *setrlimit* 
     * system call, with the exceptions of virtual memory limit and the cpu 
     * usage limit.
     * Because we might have already changed identity by this time, the hard 
     * limits should remain as they were. Thus we must invoke a *getrlimit* 
     * ahead of time to load original hard limit value.
     * Also note that, cpu usage limit should be set LAST to reduce overhead. */
    struct rlimit rlimval;
    
    /* Do NOT produce core dump files at all. */
    if (getrlimit(RLIMIT_CORE, &rlimval) < 0)
    {
        return EXIT_FAILURE;
    }
    rlimval.rlim_cur = 0;
    if (setrlimit(RLIMIT_CORE, &rlimval) < 0)
    {
        return EXIT_FAILURE;
    }
    DBG("RLIMIT_CORE: %ld", rlimval.rlim_cur);
     
    /* Disk quota */
    if (getrlimit(RLIMIT_FSIZE, &rlimval) < 0)
    {
        return EXIT_FAILURE;
    }
    rlimval.rlim_cur = ptask->quota[S_QUOTA_DISK];
    if (setrlimit(RLIMIT_FSIZE, &rlimval) < 0)
    {
        return EXIT_FAILURE;
    }
    DBG("RLIMIT_FSIZE: %ld", rlimval.rlim_cur);
    
    #ifdef DELETED
    /* Virtual memory usage can be inspected by the corresponding fields in 
     * /proc/#/stat, but if procfs is missing, we will have to depend on the 
     * setrlimit() system call to impose virtual memory quota limit. 
     * However, this may result in an unplesant side-effect that some of the 
     * stack overrun will be reported as SIGSEGV.*/
    /* Memory quota */
    if (getrlimit(RLIMIT_AS, &rlimval) < 0)
    {
        return EXIT_FAILURE;
    }
    rlimval.rlim_cur = ptask->quota[S_QUOTA_MEMORY];
    if (setrlimit(RLIMIT_AS, &rlimval) < 0)
    {
        return EXIT_FAILURE;
    }
    DBG("RLIMIT_AS: %ld", rlimval.rlim_cur);
    #endif /* DELETED */
    
    /* Time resource limits, these should be set last to recude overhead. Thus,
     * no debug informaion is produced on success. */
    struct itimerval timerval;
    
    /* Wallclock quota */
    timerval.it_interval.tv_sec = 0;
    timerval.it_interval.tv_usec = 0;
    timerval.it_value.tv_sec = ptask->quota[S_QUOTA_WALLCLOCK] / 1000;
    timerval.it_value.tv_usec = (ptask->quota[S_QUOTA_WALLCLOCK] % 1000) * 1000;
    if (setitimer(ITIMER_REAL, &timerval, NULL) < 0)
    {
        WARNING("setting ITIMER_REAL");
        return EXIT_FAILURE;
    }
    
    /* CPU quota */
    timerval.it_interval.tv_sec = 0;
    timerval.it_interval.tv_usec = 0;
    timerval.it_value.tv_sec = ptask->quota[S_QUOTA_CPU] / 1000;
    timerval.it_value.tv_usec = (ptask->quota[S_QUOTA_CPU] % 1000) * 1000;
    if (setitimer(ITIMER_PROF, &timerval, NULL) < 0)
    {
        WARNING("setting ITIMER_PROF");
        return EXIT_FAILURE;
    }
    
    /* Enter tracing mode */
    if (!trace_self())
    {
        WARNING("trace_self");
        return EXIT_FAILURE;
    }
    
    /* Execute the targeted program */
    if (execve(argv[0], argv, NULL) < 0)
    {
        WARNING("execve failed unexpectedly");
        return EXIT_FAILURE;
    }
    
    /* According to Linux manual, the execve() function will NEVER return on
     * success, thus we should not be able to reach to this line of code! */
    return EXIT_FAILURE;
}

static void 
__sandbox_stat_init(stat_t * pstat)
{
    PROC_BEGIN("__sandbox_stat_init(%p)", pstat);
    assert(pstat);
    memset(pstat, 0, sizeof(stat_t));
    PROC_END("__sandbox_stat_init()");
}

static void 
__sandbox_stat_fini(stat_t * pstat)
{
    PROC_BEGIN("__sandbox_stat_fini(%p)", pstat);
    assert(pstat);
    PROC_END("__sandbox_stat_fini()");
}

static void __sandbox_default_policy(const policy_t * ppolicy, 
                                     const event_t * pevent, 
                                     action_t * paction);

static void 
__sandbox_ctrl_init(ctrl_t * pctrl, thread_func_t tft, thread_func_t tfm)
{
    PROC_BEGIN("__sandbox_ctrl_init(%p,%p,%p)", pctrl, tft, tfm);
    assert(pctrl);
    memset(pctrl, 0, sizeof(ctrl_t));
    pthread_mutex_init(&pctrl->mutex, NULL);
    P(&pctrl->mutex);
    pthread_cond_init(&pctrl->sched, NULL);
    pctrl->policy.entry = (void *)__sandbox_default_policy;
    pctrl->policy.data = 0L;
    pctrl->tracer = tft;
    pctrl->monitor = tfm;
    V(&pctrl->mutex);
    P(&pctrl->mutex);
    pctrl->idle = true;
    pthread_cond_broadcast(&pctrl->sched);
    V(&pctrl->mutex);
    PROC_END("__sandbox_ctrl_init()");
}

static void 
__sandbox_ctrl_fini(ctrl_t * pctrl)
{
    PROC_BEGIN("__sandbox_ctrl_fini(%p)", pctrl);
    assert(pctrl);
    P(&pctrl->mutex);
    pctrl->idle = true;
    pthread_cond_broadcast(&pctrl->sched);
    V(&pctrl->mutex);
    P(&pctrl->mutex);
    pctrl->tracer = pctrl->monitor = NULL;
    pthread_cond_destroy(&pctrl->sched);
    V(&pctrl->mutex);
    pthread_mutex_destroy(&pctrl->mutex);
    PROC_END("__sandbox_ctrl_fini()");
}

#ifdef WITH_NATIVE_TRACER

static void *
__sandbox_tracer(sandbox_t * psbox)
{
    FUNC_BEGIN("sandbox_tracer(%p)", psbox);
    
    assert(psbox);
    
    #define UPDATE_RESULT(psbox,res) \
    {{{ \
        if (((psbox)->result) != (result_t)(res)) \
        { \
            ((psbox)->result) = (result_t)(res); \
        } \
        DBG("result: %s", s_result_name((result_t)(res))); \
    }}} /* UPDATE_RESULT */
    
    #define UPDATE_STATUS(psbox,sta) \
    {{{ \
        P(&((psbox)->mutex)); \
        if (((psbox)->status) != (status_t)(sta)) \
        { \
            ((psbox)->status) = (status_t)(sta); \
            pthread_cond_broadcast(&((psbox)->update)); \
        } \
        V(&((psbox)->mutex)); \
        DBG("status: %s", s_status_name((status_t)(sta))); \
    }}} /* UPDATE_STATUS */
    
    #define SIGMLE SIGUSR1
    
    #define TIMEVAL_INPLACE_SUBTRACT(x,y) \
    {{{ \
        if ((x).tv_usec < (y).tv_usec) \
        { \
            int nsec = ((y).tv_usec - (x).tv_usec) / 1000000 + 1; \
            (x).tv_sec -= nsec; \
            (x).tv_usec += 1000000 * nsec; \
        } \
        if ((x).tv_usec - (y).tv_usec >= 1000000) \
        { \
            int nsec = ((y).tv_usec - (x).tv_usec) / 1000000; \
            (x).tv_usec -= 1000000 * nsec; \
            (x).tv_sec += nsec; \
        } \
        (x).tv_sec -= (y).tv_sec; \
        (x).tv_usec -= (y).tv_usec; \
    }}} /* TIMEVAL_INPLACE_SUBTRACT */
    
    #define CLEAR_EVENT(pctrl) \
    {{{ \
        P(&((pctrl)->mutex)); \
        ((pctrl)->idle) = true; \
        pthread_cond_broadcast(&((pctrl)->sched)); \
        V(&((pctrl)->mutex)); \
    }}} /* CLEAR_EVENT */
    
    #define POST_EVENT(pctrl,type,x...) \
    {{{ \
        P(&((pctrl)->mutex)); \
        ((pctrl)->event) = (event_t){(S_EVENT ## type), {{x}}}; \
        ((pctrl)->idle) = false; \
        pthread_cond_broadcast(&((pctrl)->sched)); \
        V(&((pctrl)->mutex)); \
    }}} /* POST_EVENT */
    
    #define SINK_EVENT(pctrl) \
    {{{ \
        P(&((pctrl)->mutex)); \
        if (IS_IDLE(pctrl)) \
        { \
            DBG("no event detected"); \
            V(&((pctrl)->mutex)); \
            goto cont; \
        } \
        V(&((pctrl)->mutex)); \
        \
        DBG("detected event: %s {%d %d %d %d %d %d %d}", \
            s_event_type_name((pctrl)->event.type), \
            (pctrl)->event.data.__bitmap__.A, \
            (pctrl)->event.data.__bitmap__.B, \
            (pctrl)->event.data.__bitmap__.C, \
            (pctrl)->event.data.__bitmap__.D, \
            (pctrl)->event.data.__bitmap__.E, \
            (pctrl)->event.data.__bitmap__.F, \
            (pctrl)->event.data.__bitmap__.G); \
        \
        ((policy_entry_t)(pctrl)->policy.entry)(&(pctrl)->policy, \
                                                &(pctrl)->event, \
                                                &(pctrl)->action); \
        \
        DBG("decided action: %s {%d %d}", \
            s_action_type_name((pctrl)->action.type), \
            (pctrl)->action.data.__bitmap__.A, \
            (pctrl)->action.data.__bitmap__.B); \
        \
        P(&((pctrl)->mutex)); \
        ((pctrl)->idle) = true; \
        pthread_cond_broadcast(&((pctrl)->sched)); \
        V(&((pctrl)->mutex)); \
    }}} /* SINK_EVENT */
    
    DBG("entering: the tracing thread");
    
    /* The controller should contain pid of the prisoner process */
    pid_t pid = psbox->ctrl.pid;
    
    /* Check if the prisoner process was correctly forked */
    if (pid < 0)
    {
        WARNING("error forking the prisoner process");
        UPDATE_RESULT(psbox, S_RESULT_IE);
        UPDATE_STATUS(psbox, S_STATUS_FIN);
        FUNC_RET((void *)&psbox->result, "sandbox_tracer()");
    }
    
    /* Have signals kill the prisoner but not self (if possible).  */
    sighandler_t terminate_signal;
    sighandler_t interrupt_signal;
    sighandler_t quit_signal;
    
    terminate_signal = signal(SIGTERM, SIG_IGN);
    interrupt_signal = signal(SIGINT, SIG_IGN);
    quit_signal = signal(SIGQUIT, SIG_IGN);
    
    /* Get wallclock start time */
    gettimeofday(&psbox->stat.started, NULL);
    
    UPDATE_RESULT(psbox, S_RESULT_PD);
    UPDATE_STATUS(psbox, S_STATUS_EXE);
    
    /* Resume the control logic */
    CLEAR_EVENT(&psbox->ctrl);
    
    /* Temporary variables. */
    struct rusage initru;
    int waitstatus = 0;
    pid_t waitresult = 0;
    proc_t proc = {0};
    
    /* System call stack. The prisoner process initially stops at *SYSRET* mode
     * after making the first call to SYS_execve. Therefore, we initialize the 
     * stack as it is. */
    int sc_stack[16] = {0, SYS_execve};
    int sc_top = 1;
    
    /* Make an initial wait to verify the first system call as well as to 
     * collect resource usage overhead. */
    waitresult = wait4(pid, &waitstatus, 0, &psbox->stat.ru);
    
    UPDATE_STATUS(psbox, S_STATUS_BLK);
    
    /* Save the initial resource usage for further reference */
    memcpy(&initru, &psbox->stat.ru, sizeof(struct rusage));
    
    /* Clear cache in order to increase timing accuracy */
    flush_cache();
    flush_cache();
    
    #ifdef DELETED
    /* Investigate the first system call. In case the prisoner process exited 
     * before the first system call, report it as an internal error and skip 
     * the tracing loop. Caution that, the prisoner process stops in *SYSRET* 
     * mode rather than SYSCALL or SINGLESTEP mode regardless of the status of
     * WITH_TSC_COUNTER directive. */
    if (WIFSTOPPED(waitstatus) && (WSTOPSIG(waitstatus) == SIGTRAP))
    {
        /* Probe the prisoner process */
        if (!proc_probe(pid, PROBE_REGS, &proc))
        {
            WARNING("failed to probe the prisoner process");
            kill(-pid, SIGKILL);
            UPDATE_RESULT(psbox, S_RESULT_IE);
            goto done;
        }
        /* Detect memory usage */
        if (proc.vsize > psbox->task.quota[S_QUOTA_MEMORY])
        {
            kill(-pid, SIGMLE);
        }
        /* Update sandbox stat with the process runtime info */
        if (psbox->stat.vsize < proc.vsize)
        {
            psbox->stat.vsize = proc.vsize;
        }
        /* Check the type of pending system call */
        #ifdef __linux__
        if (THE_SYSCALL(&proc) != SYS_execve)
        #endif /* __linux__ */
        {
            WARNING("unexpected behavior of the prisoner process");
            kill(-pid, SIGKILL);
            UPDATE_RESULT(psbox, S_RESULT_IE);
            goto done;
        }
        DBG("initial SYS_execve() detected");
        /* Everything looks fine, schedule for next wait */
        goto next;
    }
    else if (WIFEXITED(waitstatus))
    {
        WARNING("the prisoner process exited during preparation phase");
        UPDATE_RESULT(psbox, S_RESULT_IE);
        goto done;
    }
    #endif /* DELETED */
    
    /* Entering the tracing loop */
    do
    {
        /* Trace state refresh of the prisoner program */
        UPDATE_STATUS(psbox, S_STATUS_BLK);
        
        /* In case nothing happened (possible when the 3rd argument of *wait4* 
         * contains the WNOHANG flag), we just go on with next wait(). */
        if (waitresult == 0)
        {
            DBG("wait: nothing happened");
            goto cont;
        }
        
        /* Figure *net* resource usage (eliminate initru) */
        TIMEVAL_INPLACE_SUBTRACT(psbox->stat.ru.ru_utime, initru.ru_utime);
        TIMEVAL_INPLACE_SUBTRACT(psbox->stat.ru.ru_stime, initru.ru_stime);
        psbox->stat.ru.ru_majflt -= initru.ru_majflt;
        psbox->stat.ru.ru_minflt -= initru.ru_minflt;
        psbox->stat.ru.ru_nswap -= initru.ru_nswap;
        
        DBG("ru.ru_utime.tv_sec  % 10ld", psbox->stat.ru.ru_utime.tv_sec);
        DBG("ru.ru_utime.tv_usec % 10ld", psbox->stat.ru.ru_utime.tv_usec);
        DBG("ru.ru_stime.tv_sec  % 10ld", psbox->stat.ru.ru_stime.tv_sec);
        DBG("ru.ru_stime.tv_usec % 10ld", psbox->stat.ru.ru_stime.tv_usec);
        DBG("ru.ru_majflt        % 10ld", psbox->stat.ru.ru_majflt);
        DBG("ru.ru_minflt        % 10ld", psbox->stat.ru.ru_minflt);
        DBG("ru.ru_nswap         % 10ld", psbox->stat.ru.ru_nswap);
        
        /* Raise appropriate events judging each wait status */
        if (WIFSTOPPED(waitstatus))
        {
            DBG("wait: stopped (%d)", WSTOPSIG(waitstatus));
            psbox->stat.signal = WSTOPSIG(waitstatus);
            /* Collect additional information of the prisoner process */ 
            if (!proc_probe(pid, PROBE_STAT, &proc))
            {
                WARNING("failed to probe the prisoner process");
                kill(-pid, SIGKILL);
                UPDATE_RESULT(psbox, S_RESULT_IE);
                goto done;
            }
            /* Raise appropriate event judging stop signal */
            switch (WSTOPSIG(waitstatus))
            {
            case SIGALRM:       /* real timer expired */
                POST_EVENT(&psbox->ctrl, _QUOTA, S_QUOTA_WALLCLOCK);
                break;
            case SIGXCPU:       /* CPU resource limit exceed */
            case SIGPROF:       /* profile timer expired */
            case SIGVTALRM:     /* virtual timer expired */
                POST_EVENT(&psbox->ctrl, _QUOTA, S_QUOTA_CPU);
                break;
            case SIGMLE:        /* SIGUSR1 used for reporting ML */
                POST_EVENT(&psbox->ctrl, _QUOTA, S_QUOTA_MEMORY);
                break;
            case SIGXFSZ:       /* Output file size exceeded */
                POST_EVENT(&psbox->ctrl, _QUOTA, S_QUOTA_DISK);
                break;
            case SIGTRAP:
                /* Update the tsc instructions counter */
                #ifdef WITH_TSC_COUNTER
                psbox->stat.tsc++;
                DBG("tsc                 %010llu", psbox->stat.tsc);
                #endif /* WITH_TSC_COUNTER */
                /* Collect additional information of prisoner process */
                if (!proc_probe(pid, PROBE_REGS, &proc))
                {
                    WARNING("failed to probe the prisoner process");
                    kill(-pid, SIGKILL);
                    UPDATE_RESULT(psbox, S_RESULT_IE);
                    goto done;
                }
                /* Detect memory usage */
                if (proc.vsize > psbox->task.quota[S_QUOTA_MEMORY])
                {
                    kill(-pid, SIGMLE);
                }
                /* Update sandbox stat with the process runtime info */
                if (psbox->stat.vsize < proc.vsize)
                {
                    psbox->stat.vsize = proc.vsize;
                }
                /* For `single step' tracing mode, we have to probe the current
                 * op code (i.e. INT80 for i386 platforms) to tell whether the 
                 * prisoner process is invoking a system call. For `system call'
                 * tracing mode, however, every *SIGTRAP* indicates a system 
                 * call currently being invoked or just returned. */
                #ifdef WITH_TSC_COUNTER
                if (IS_SYSCALL(&proc) || IS_SYSRET(&proc))
                #endif /* WITH_TSC_COUNTER */
                {
                    int scno = THE_SYSCALL(&proc);
                    if (scno != sc_stack[sc_top])
                    {
                        sc_stack[++sc_top] = scno;
                        psbox->stat.syscall = scno;
                        SET_IN_SYSCALL(&proc);
                        POST_EVENT(&psbox->ctrl, _SYSCALL, scno, 
                                                           SYSCALL_ARG1(&proc),
                                                           SYSCALL_ARG2(&proc), 
                                                           SYSCALL_ARG3(&proc), 
                                                           SYSCALL_ARG4(&proc),
                                                           SYSCALL_ARG5(&proc));
                    }
                    else
                    {
                        CLR_IN_SYSCALL(&proc);
                        sc_stack[sc_top--] = 0;
                        POST_EVENT(&psbox->ctrl, _SYSRET, scno, 
                                                          SYSRET_RETVAL(&proc));
                    }
                }
                #ifdef WITH_TSC_COUNTER
                else
                {
                    goto next;
                }
                #endif /* WITH_TSC_COUNTER */
                break;
            default:            /* Other runtime error */
                POST_EVENT(&psbox->ctrl, _SIGNAL, WSTOPSIG(waitstatus));
                break;
            }
        } /* stopped */
        else if (WIFSIGNALED(waitstatus))
        {
            DBG("wait: signaled (%d)", WTERMSIG(waitstatus));
            psbox->stat.signal = WTERMSIG(waitstatus);
            POST_EVENT(&psbox->ctrl, _SIGNAL, WTERMSIG(waitstatus));
        }
        else if (WIFEXITED(waitstatus))
        {
            DBG("wait: exited (%d)", WEXITSTATUS(waitstatus));
            psbox->stat.exitcode = WEXITSTATUS(waitstatus);
            POST_EVENT(&psbox->ctrl, _EXIT, WEXITSTATUS(waitstatus));
        }
        
        /* Wait for the policy to determine the next action */
        SINK_EVENT(&psbox->ctrl);
        
        /* Perform the desired action */
        switch (psbox->ctrl.action.type)
        {
        case S_ACTION_CONT:
    next:
            #ifdef WITH_TSC_COUNTER
            if (!trace_next(&proc, TRACE_SINGLE_STEP))
            #else
            if (!trace_next(&proc, TRACE_SYSTEM_CALL))
            #endif /* WITH_TSC_COUNTER */
            {
                WARNING("trace_next");
                kill(-pid, SIGKILL);
                UPDATE_RESULT(psbox, S_RESULT_IE);
                break;
            }
            /* There is no need to update state here! */
            goto cont;          /* Continue with next wait() */
        case S_ACTION_FINI:
            UPDATE_RESULT(psbox, psbox->ctrl.action.data._FINI.result);
            break;
        case S_ACTION_KILL:
            /* Using trace_kill can effectly prevent overrun of undesired 
             * behavior, i.e. illegal system call. */
            trace_kill(&proc, SIGKILL);
            UPDATE_RESULT(psbox, psbox->ctrl.action.data._KILL.result);
            break;
        }
        break;                  /* Exiting the tracing loop! */
        
    cont:
        /* Wait until the prisoner process is trapped  */
        UPDATE_STATUS(psbox, S_STATUS_EXE);
        DBG("----------------------------------------------------------------");
        DBG("wait4(%d,%p,%d,%p)", pid, &waitstatus, 0, &psbox->stat.ru);
        waitresult = waitstatus = 0;
    } while ((waitresult = wait4(pid, &waitstatus, 0, &psbox->stat.ru)) >= 0);
    
done:
    /* Get wallclock stop time (call a second time to compensate overhead) */
    gettimeofday(&psbox->stat.stopped, NULL);
    
    UPDATE_STATUS(psbox, S_STATUS_FIN);
    
    /* Resume the control logic */
    CLEAR_EVENT(&psbox->ctrl);
    
    DBG("leaving: the tracing thread");
    
    /* Restore signal handlers */
    signal(SIGTERM, interrupt_signal);
    signal(SIGINT, interrupt_signal);
    signal(SIGQUIT, quit_signal);
    
    FUNC_RET((void *)&psbox->result, "sandbox_tracer()");
}

#endif /* WITH_NATIVE_TRACER */

#ifdef DELETED
#ifdef WITH_NATIVE_MONITOR 

static void *
__sandbox_monitor(sandbox_t * psbox)
{
    FUNC_BEGIN("sandbox_monitor(%p)", psbox);
    
    assert(psbox);
    
    /* Temporary variables */
    ctrl_t * pctrl = &psbox->ctrl;
    
    /* Enter the main monitoring loop */
    P(&pctrl->mutex);
    DBG("entering: the monitoring thread");
    
    /* Wait for the sandbox to start */
    P(&psbox->mutex);
    while (NOT_STARTED(psbox))
    {
        pthread_cond_wait(&psbox->update, &psbox->mutex);
    }
    V(&psbox->mutex);
    
    /* Detect and handle events while the sandbox is running */
    P(&psbox->mutex);
    while (IS_RUNNING(psbox) || IS_BLOCKED(psbox))
    {
        V(&psbox->mutex);
        
        /* An event might have already been posted */
        if (pctrl->idle)
        {
            pthread_cond_wait(&pctrl->sched, &pctrl->mutex);
            if (pctrl->idle)
            {
                P(&psbox->mutex);
                continue;
            }
        }
        
        /* Start investigating the event */
        DBG("detected event: %s {%d %d %d %d %d %d %d}",
            s_event_type_name(pctrl->event.type),
            pctrl->event.data.__bitmap__.A,
            pctrl->event.data.__bitmap__.B,
            pctrl->event.data.__bitmap__.C,
            pctrl->event.data.__bitmap__.D,
            pctrl->event.data.__bitmap__.E,
            pctrl->event.data.__bitmap__.F,
            pctrl->event.data.__bitmap__.G);
        
        /* Consult the sandbox policy to determine next action */
        ((policy_entry_t)pctrl->policy.entry)(&pctrl->policy, &pctrl->event,
                                              &pctrl->action);
        
        DBG("decided action: %s {%d %d}",
            s_action_type_name(pctrl->action.type),
            pctrl->action.data.__bitmap__.A,
            pctrl->action.data.__bitmap__.B);
        
        P(&psbox->mutex);
        
        /* Notify the tracer to perform the decided action */
        pctrl->idle = true;
        pthread_cond_broadcast(&pctrl->sched);
    }
    V(&psbox->mutex);
    
    DBG("leaving: the monitoring thread");
    V(&pctrl->mutex);
    
    FUNC_RET((void *)&psbox->result, "sandbox_monitor()");
}

#endif /* WITH_NATIVE_MONITOR */
#endif /* DELETED */

static void 
__sandbox_default_policy(const policy_t * ppolicy, const event_t * pevent, 
                         action_t * paction)
{
    PROC_BEGIN("__sandbox_default_policy(%p,%p,%p)", ppolicy, pevent, paction);
    
    assert(pevent && paction);
    
    switch (pevent->type)
    {
    case S_EVENT_SYSCALL:
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
    
    PROC_END("__sandbox_default_policy()");
}


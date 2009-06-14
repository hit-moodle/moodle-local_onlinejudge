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
/** 
 * @file sandbox.h 
 * @brief Methods for creating and manipulating a sandbox object.
 */
#ifndef __OJS_SANDBOX_H__
#define __OJS_SANDBOX_H__

#include <stdio.h>             /* fprintf(), fflush(), stderr */
#include <errno.h>             /* errno, strerror() */
#include <limits.h>            /* ARG_MAX, PATH_MAX */
#include <pthread.h>           /* pthread_mutex_{lock,unlock}() */
#include <sys/resource.h>      /* struct rusage, RLIM_INFINITY */
#include <sys/time.h>          /* struct timeval */
#include <sys/types.h>         /* uid_t, gid_t */

#ifdef __cplusplus
extern "C"
{
#endif

#ifndef DBG
#ifdef NDEBUG
#define DBG(fmt,x...) ((void)0)
#else
#ifndef DBG_PREFIX
#define DBG_PREFIX "debug>"
#endif /* DBG_PREFIX */
#define DBG(fmt,x...) \
{{{ \
    fprintf(stderr, DBG_PREFIX fmt "\n", ##x); \
    fflush(stderr); \
}}}
#endif /* NDEBUG */
#endif /* !defined DBG */

#ifndef WARNING
#ifdef NDEBUG
#define WARNING(fmt,x...) ((void)0)
#else
#ifndef WARN_PREFIX
#define WARN_PREFIX ""
#endif /* WARN_PREFIX */
#define WARNING(fmt,x...) \
{{{ \
    fprintf(stderr, WARN_PREFIX fmt, ##x); \
    if (errno != 0) \
    { \
        fprintf(stderr, ": %s", strerror(errno)); \
    } \
    fprintf(stderr, "\n"); \
    fflush(stderr); \
}}}
#endif /* NDEBUG */
#endif /* WARNING */

#ifndef FUNC_BEGIN
#define FUNC_BEGIN(f,x...) \
{{{ \
    DBG("entering: " f, ##x); \
}}}
#endif /* !defined FUNC_BEGIN */

#ifndef FUNC_RET
#define FUNC_RET(r,fmt,x...) \
{{{ \
    DBG("leaving: " fmt, ##x); \
    return (r); \
}}}
#endif /* !defined FUNC_RET */

#ifndef PROC_BEGIN
#define PROC_BEGIN FUNC_BEGIN
#endif /* !defined PROC_BEGIN */

#ifndef PROC_END
#define PROC_END(fmt,x...) \
{{{ \
    DBG("leaving: " fmt, ##x); \
    return; \
}}}
#endif /* !defined PROC_END */

#ifndef __cplusplus
#ifndef HAS_BOOL
#define HAS_BOOL
/** 
 * @brief Emulation of the C++ bool type. 
 */
typedef enum 
{
    false,                     /*!< false */
    true                       /*!< true */
} bool;
#endif /* HAS_BOOL */
#endif /* __cplusplus */

#ifndef FILENO_MAX
#define FILENO_MAX 64
#endif /* FILENO_MAX */

#ifndef ARG_MAX
#define ARG_MAX 65536
#endif /* ARG_MAX */

#ifndef PATH_MAX
#define PATH_MAX 4096
#endif /* PATH_MAX */

/* Alias of a thread function pointer */
#ifndef thread_func_t
typedef void * (* thread_func_t)(void *);
#endif /* thread_func_t */

/* Macros for mutex manipulation */
#ifndef P
#define P(pm) \
{{{ \
    if (pthread_mutex_lock((pm)) != 0) \
    { \
        WARNING("mutex lock failed"); \
    } \
}}}
#endif /* P */

#ifndef V
#define V(pm) \
{{{ \
    if (pthread_mutex_unlock((pm)) != 0) \
    { \
        WARNING("mutex unlock failed"); \
    } \
}}}
#endif /* V */

/**
 * @brief Serialized representation of a command and its arguments.
 */
typedef struct
{
    char buff[ARG_MAX];        /**< command line buffer */
    int args[ARG_MAX];         /**< arguments offset */
} command_t;

/** 
 * @brief Task quota type.
 */
typedef enum
{
    S_QUOTA_WALLCLOCK  = 0,    /*!< Wall clock time (msec) */
    S_QUOTA_CPU        = 1,    /*!< CPU usage usr + sys (msec) */
    S_QUOTA_MEMORY     = 2,    /*!< Virtual memory size (bytes) */
    S_QUOTA_DISK       = 3,    /*!< Maximum output file size (bytes) */
} quota_type_t;

#define QUOTA_TOTAL 4

/**
 * @brief Quota size counter.
 */
typedef rlim_t res_t;

#define RES_INFINITY RLIM_INFINITY

/** 
 * @brief Static specification of a task.
 */
typedef struct
{
    command_t comm;            /**< command (with arguments) to be executed */
    char jail[PATH_MAX];       /**< chroot to this path before running cmd */ 
    uid_t uid;                 /**< run command as this user */
    gid_t gid;                 /**< run command as member of this group */
    int ifd;                   /**< file descriptor for task input */
    int ofd;                   /**< file descriptor for task output */
    int efd;                   /**< file descriptor for task error log */
    res_t quota[QUOTA_TOTAL];  /**< block the task program if quota exceeds */
} task_t;

/**
 * @brief Runtime / cumulative information about a task.
 */
typedef struct
{
    struct timeval started;    /**< start time since Epoch */
    struct timeval stopped;    /**< stop time since Epoch */
    struct rusage ru;          /**< resource usage */
    res_t vsize;               /**< peak virtual memory usage (bytes) */
    int syscall;               /**< last syscall */
    long signal;               /**< last signal */
    int exitcode;              /**< exit code */
    unsigned long long tsc;    /**< number of instructions executed */
} stat_t;

/** 
 * @brief Types of sandbox execution status. 
 */
typedef enum 
{
    S_STATUS_PRE       = 0,    /*!< Preparing (not ready to execute) */
    S_STATUS_RDY       = 1,    /*!< Ready (waiting for execution) */
    S_STATUS_EXE       = 2,    /*!< Executing (waiting for event) */
    S_STATUS_BLK       = 3,    /*!< Blocked (handling event) */
    S_STATUS_FIN       = 4,    /*!< Finished */
} status_t;

/** 
 * @brief Types of sandbox execution result. 
 */
typedef enum
{
    S_RESULT_PD        = 0,    /*!< Pending */
    S_RESULT_OK        = 1,    /*!< Okay */
    S_RESULT_RF        = 2,    /*!< Restricted Function */
    S_RESULT_ML        = 3,    /*!< Memory Limit Exceed */
    S_RESULT_OL        = 4,    /*!< Output Limit Exceed */
    S_RESULT_TL        = 5,    /*!< Time Limit Exceed */
    S_RESULT_RT        = 6,    /*!< Run Time Error (SIGSEGV, SIGFPE, ...) */
    S_RESULT_AT        = 7,    /*!< Abnormal Termination */
    S_RESULT_IE        = 8,    /*!< Internal Error (of sandbox executor) */
} result_t;

/**
 * @brief Types of sandbox internal events.
 */
typedef enum
{
    S_EVENT_ERROR      = 0,    /*!< sandbox encountered internal error */
    S_EVENT_EXIT       = 1,    /*!< target program exited */
    S_EVENT_SIGNAL     = 2,    /*!< target program was signaled unexpectedly */
    S_EVENT_SYSCALL    = 3,    /*!< target program issued a system call */
    S_EVENT_SYSRET     = 4,    /*!< target program returned from system call */
    S_EVENT_QUOTA      = 5,    /*!< target program exceeds resource quota */
} event_type_t;

/**
 * @brief Event specific data bindings.
 */
typedef union
{
    /* The *__bitmap__* field does not have corresponding event type, it is
     * there to ensure that an instance of this union can be properly 
     * instantiated with braced initializer. */
    struct
    {
        int A, B, C, D, E, F, G;
    } __bitmap__;
    struct
    {
        int code;
    } _ERROR;
    struct
    {
        int code;
    } _EXIT;
    struct
    {
        int signo;
    } _SIGNAL;
    struct
    {
        int scno;
        int a, b, c, d, e, f;
    } _SYSCALL;
    struct
    {
        int scno;
        int retval;
    } _SYSRET;
    struct
    {
        int type;
    } _QUOTA;
} event_data_t;

/** 
 * @brief Structure for holding events and event-associated data. 
 */
typedef struct
{
    event_type_t type;         /**< event type */
    event_data_t data;         /**< event data */
} event_t;

/** 
 * @brief Types of sandbox actions. 
 */
typedef enum
{
    S_ACTION_CONT      = 0,    /*!< Continute */
    S_ACTION_FINI      = 1,    /*!< Finish (safe exit) */
    S_ACTION_KILL      = 2,    /*!< Kill (instant exit) */
    /* TODO identify other action types */
} action_type_t;

/** 
 * @brief Action specific data bindings. 
 */
typedef union
{
    struct
    {
        int A, B;
    } __bitmap__;
    struct
    {
        int reserved;
    } _CONT;
    struct
    {
        int result;
    } _FINI;
    struct
    {
        int result;
    } _KILL;
    /* TODO add action-specific data structures */
} action_data_t;

/** 
 * @brief Structure for holding actions and action-associated data. 
 */
typedef struct
{
    action_type_t type;        /**< action type */
    action_data_t data;        /**< action data */
} action_t;

/**
 * @brief Structure for sandbox policy object entry and private data.
 */
typedef struct
{
    void * entry;              /**< policy object entry point */
    int data;                  /**< reserved data / control storage */    
} policy_t;

/**
 * @brief Entry function signature of sandbox policy object.
 */
typedef void (* policy_entry_t)(const policy_t *, const event_t *, action_t *);

/** 
 * @brief Configurable control logic of a sandbox object.
 */
typedef struct
{
    pthread_mutex_t mutex;     /**< mutex for scheduling thread functions */
    pthread_cond_t sched;      /**< working state changed */
    bool idle;                 /**< is controller idle or busy? */
    thread_func_t tracer;      /**< the sandbox tracer thread function */
    thread_func_t monitor;     /**< the sandbox monitor thread function */
    pid_t pid;                 /**< id of the process being traced */
    event_t event;             /**< the event to be handled */
    action_t action;           /**< the action to be suggested by the handler */
    policy_t policy;           /**< the policy to consult */
} ctrl_t;

#ifndef IS_IDLE
#define IS_IDLE(pctrl) \
    (((pctrl)->idle) == true)
#endif /* !defined IS_IDLE */

/** 
 * @brief Structure for collecting everything needed to run a sandbox. 
 */
typedef struct
{
    pthread_mutex_t mutex;     /**< mutex for updating sandbox state */
    pthread_cond_t update;     /**< sandbox state changed */
    status_t status;           /**< task status */
    result_t result;           /**< task result */
    task_t task;               /**< task initial specification */
    stat_t stat;               /**< task cumulative statistics */
    ctrl_t ctrl;               /**< configurable sandbox control logic */
} sandbox_t;

#ifndef NOT_STARTED
#define NOT_STARTED(psbox) \
    ((((psbox)->status) == S_STATUS_RDY) || (((psbox)->status) == S_STATUS_PRE))
#endif /* !defined NOT_STARTED */

#ifndef IS_BLOCKED
#define IS_BLOCKED(psbox) (((psbox)->status) == S_STATUS_BLK)
#endif /* !defined IS_BLOCKED */

#ifndef IS_RUNNING
#define IS_RUNNING(psbox) (((psbox)->status) == S_STATUS_EXE)
#endif /* !defined IS_RUNNING */

#ifndef IS_FINISHED
#define IS_FINISHED(psbox) (((psbox)->status) == S_STATUS_FIN)
#endif /* !defined IS_FINISHED */

/** 
 * @brief Initialize a \c sandbox_t object.
 * @param[in,out] psbox pointer to the \c sandbox_t object to be initialized
 * @param[in] argv command line argument array of the targeted program
 * @return 0 on success
 */
int sandbox_init(sandbox_t * psbox, const char * argv[]);

/** 
 * @brief Destroy a \c sandbox_t object.
 * @param[in,out] psbox pointer to the \c sandbox_t object to be destroied
 * @return 0 on success
 */
int sandbox_fini(sandbox_t * psbox);

/** 
 * @brief Check if sandbox is ready to (re)start, update status accordingly.
 * @param[in,out] psbox pointer to the \c sandbox_t object to be checked
 * @return true on success
 * In order to restart a finished sandbox, the caller of this function must
 *   a) properly stop previous monitor (i.e. unlock the mutex), and 
 *   b) rewind / reopen / reset I/O channels of task specification
 */
bool sandbox_check(sandbox_t * psbox);

/** 
 * @brief Start executing the task binded with the sandbox.
 * @param[in,out] psbox pointer to the \c sandbox_t object to be started
 * @return pointer to the \c result field of \c psbox, or NULL on failure
 */
result_t * sandbox_execute(sandbox_t * psbox);

#ifdef __cplusplus
}
#endif

#endif /* __OJS_SANDBOX_H__ */

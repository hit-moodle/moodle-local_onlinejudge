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
 * @file platform.h 
 * @brief Platform dependent features wrapper.
 */
#ifndef __OJS_PLATFORM_H__
#define __OJS_PLATFORM_H__

#include <signal.h>            /* sighandler_t */
#include <sys/times.h>         /* struct tms */
#include <sys/types.h>         /* pid_t */
#ifdef __linux__
#include <sys/ptrace.h>        /* ptrace(), PTRACE_* */
#include <sys/reg.h>           /* EAX, EBX, ... */
#include <sys/syscall.h>       /* SYS_*, __NR_* */
#include <sys/user.h>          /* struct user_regs_struct, user_fpregs_struct */
#endif /* __linux__ */
#ifdef __CYGWIN__
#include <sys/strace.h>        /* strace(), _STRACE_* */
#endif /* __CYGWIN__ */

#ifdef __cplusplus
extern "C"
{
#endif

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

#ifdef __i386__
#define OPCODE(op) ((unsigned int)(op) & (~((unsigned int)(~0u << 16))))
#define IS_INT80(op) (OPCODE(op) == 0x80cd)
#define IS_RET(op) (OPCODE(op) == 0x00c3)
#define IS_BREAKPOINT(op) (OPCODE(op) == 0xebcc)
#define IS_NOP(op) (OPCODE(op) == 0x90cc)
#else
#error "this platform is not supported"
#endif /* __i386__ */

/**
 * @brief Evict the existing blocks from the data caches.
 * This function was taken from Chapter 9 of Computer Systems A Programmer's
 * Perspective by Randal E. Bryant and David R. O'Hallaron
 */
void flush_cache(void);

/**
 * @brief Subprocess trace methods.
 */
typedef enum 
{
    #ifdef __linux__
    TRACE_SYSTEM_CALL = PTRACE_SYSCALL, 
    TRACE_SINGLE_STEP = PTRACE_SINGLESTEP, 
    #endif /* __linux__ */
    #ifdef __CYGWIN__
    TRACE_SYSTEM_CALL = _STRACE_SYSCALL, 
    #endif /* __CYGWIN__ */
} trace_type_t;

/** 
 * @brief Structure for collecting process runtime information. 
 */
typedef struct
{
    pid_t pid;                 /**< process id */
    pid_t ppid;                /**< parent process id */
    char state;                /**< state of the process */
    unsigned long flags;       /**< unknown */
    struct tms tm;             /**< process time measured in clock */
    unsigned long vsize;       /**< virtual memory size (bytes) */
    long rss;                  /**< resident set size (pages) */
    #ifdef __linux__
    struct user_regs_struct regs;
    struct user_fpregs_struct fpregs;
    #else
    #endif /* __linux__ */
    unsigned int tflags;       /**< trace flags, maintained by trace_*() */
    unsigned int op;           /**< current instruction */
} proc_t;

#define TFLAGS_SINGLE_STEP 0x00000002
#define TFLAGS_IN_SYSCALL  0x00000004

#define SET_IN_SYSCALL(pproc) ((pproc)->tflags |= TFLAGS_IN_SYSCALL)
#define CLR_IN_SYSCALL(pproc) ((pproc)->tflags &= ~TFLAGS_IN_SYSCALL)

#ifdef __linux__
#define THE_SYSCALL(pproc) \
    ((((pproc)->tflags & TFLAGS_SINGLE_STEP) && \
      !((pproc)->tflags & TFLAGS_IN_SYSCALL)) ? \
     ((pproc)->regs.eax) : \
     ((pproc)->regs.orig_eax))
#define IS_SYSCALL(pproc) \
    (((pproc)->tflags & TFLAGS_SINGLE_STEP) ? \
     (IS_INT80((pproc)->op)) : \
     (true))
#define IS_SYSRET(pproc) \
    (((pproc)->tflags & TFLAGS_SINGLE_STEP) ? \
     (!IS_INT80((pproc)->op) && ((pproc)->tflags & TFLAGS_IN_SYSCALL)) : \
     (true))
#define SYSCALL_ARG1(pproc) ((pproc)->regs.ebx)
#define SYSCALL_ARG2(pproc) ((pproc)->regs.ecx)
#define SYSCALL_ARG3(pproc) ((pproc)->regs.edx)
#define SYSCALL_ARG4(pproc) ((pproc)->regs.esi)
#define SYSCALL_ARG5(pproc) ((pproc)->regs.edi)
#define SYSRET_RETVAL(pproc) ((pproc)->regs.eax)
#else
#define THE_SYSCALL(pproc) 0
#warning "THE_SYSCALL is not implemented for this platform"
#define IS_SYSCALL(pproc) (true)
#warning "IS_SYSCALL is not implemented for this platform"
#define IS_SYSRET(pproc) (true)
#warning "IS_SYSRET is not implemented for this platform"
#define SYSCALL_ARG1(pproc) 0
#define SYSCALL_ARG2(pproc) 0
#define SYSCALL_ARG3(pproc) 0
#define SYSCALL_ARG4(pproc) 0
#define SYSCALL_ARG5(pproc) 0
#define SYSRET_RETVAL(pproc) 0
#endif /* __linux__ */

#define PROBE_STAT 0            /* probe procfs for process status */
#define PROBE_REGS 1            /* probe registers */
#define PROBE_FPREGS 2          /* probe floating point registers */

/**
 * @brief Probe runtime information of specified process.
 * @param[in] pid id of targeted process
 * @param[in] opt probe options (can be any combination of PROBE_{STAT,REGS,FPREGS))
 * @param[out] pproc process stat buffer
 * @return true on sucess, false otherwise
 */
bool proc_probe(pid_t pid, int opt, proc_t * const pproc);

/**
 * @brief Copies a 4-byte word from the specified address of targeted process.
 * @param[in] pproc process stat buffer with valid pid and state
 * @param[out] addr targeted address of the given process
 * @param[out] pword pointer to a buffer at least 4 bytes in length
 * @return true on success, false otherwise
 */
bool proc_dump(const proc_t * const pproc, const void * const addr, 
               long * const pword);

/**
 * @brief Let the current process enter traced state.
 * @return true on success
 */
bool trace_self(void);

/**
 * @brief Schedule next stop for a traced process.
 * @param[in,out] pproc target process stat buffer with valid pid
 * @param[in] type \c SYSTEM_CALL or \c SINGLE_STEP
 * @return true on success
 */
bool trace_next(proc_t * const pproc, trace_type_t type);

/**
 * @brief Kill a traced process, prevent any overrun.
 * @param[in] pproc target process stat buffer with valid pid
 * @param[in] signal type of signal to use
 * @return true on success
 */
bool trace_kill(const proc_t * const pproc, int signal);

#ifndef sighandler_t
typedef void (* sighandler_t)(int);
#endif /* sighandler_t */

#ifdef __cplusplus
}
#endif

#endif /* __OJS_PLATFORM_H__ */

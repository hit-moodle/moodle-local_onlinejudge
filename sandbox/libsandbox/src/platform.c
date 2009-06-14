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

#include "platform.h"
#include "sandbox.h"
#include <assert.h>            /* assert() */
#include <fcntl.h>             /* open(), close(), O_RDONLY */
#include <stdio.h>             /* read(), sscanf(), sprintf() */
#include <string.h>            /* memset(), strsep() */
#include <sys/vfs.h>           /* statfs() */
#include <sys/types.h>         /* off_t */
#include <unistd.h>            /* access(), lseek(), {R,F}_OK */

#ifndef PROCFS
#if defined(__linux__) || defined(__CYGWIN__)
#define PROCFS "/proc"
#endif /* __linux__ || __CYGWIN__ */
#endif /* PROCFS */

#ifdef PROCFS
#ifndef PROC_SUPER_MAGIC
#define PROC_SUPER_MAGIC 0x9fa0
#endif /* !defined PROC_SUPER_MAGIC */
#endif /* PROCFS */

#ifndef CACHE_SIZE
#define CACHE_SIZE (1 << 21)   /* assume 2MB cache */
#endif /* CACHE_SIZE */

static int cache[CACHE_SIZE / sizeof(int)];
volatile int sink;             /* variable used for clearing cache */

void 
flush_cache(void)
{
    PROC_BEGIN("flush_cache()");
    unsigned int i;
    unsigned int sum = 0;
    for (i = 0; i < CACHE_SIZE / sizeof(int); i++) cache[i] = 3;
    for (i = 0; i < CACHE_SIZE / sizeof(int); i++) sum += cache[i];
    sink = sum;
    PROC_END("flush_cache()");
}

bool 
proc_probe(pid_t pid, int opt, proc_t * const pproc)
{
    FUNC_BEGIN("proc_probe(%d,%d,%p)", pid, opt, pproc);
    
    assert(pproc);
    
    /* Validating procfs */
    struct statfs sb;
    if ((statfs(PROCFS, &sb) < 0) || (sb.f_type != PROC_SUPER_MAGIC))
    {
        WARNING("procfs missing or invalid");
        FUNC_RET(false, "proc_probe()");
    }
    
    /* Validating process stat */
    char buffer[4096];
    
    sprintf(buffer, PROCFS "/%d/stat", pid);
    if (access(buffer, R_OK | F_OK) < 0)
    {
        WARNING("procfs entries missing or invalid");
        FUNC_RET(false, "proc_probe()");
    }
    
    /* Grab stat information in one read */
    int fd = open(buffer, O_RDONLY);
    int len = read(fd, buffer, sizeof(buffer) - 1);
    close(fd);
    buffer[len] = '\0';
    
    /* Extract interested information */
    int offset = 0;
    char * token = buffer;
    do
    {
        switch (offset++)
        {
        case  0:           /* pid */
            sscanf(token, "%d", &pproc->pid);
            break;
        case  1:           /* comm */
            break;
        case  2:           /* state */
            sscanf(token, "%c", &pproc->state);
            break;
        case  3:           /* ppid */
            sscanf(token, "%d", &pproc->ppid);
            break;
        case  4:           /* pgrp */
        case  5:           /* session */
        case  6:           /* tty_nr */
        case  7:           /* tty_pgrp */
            break;
        case  8:           /* flags */
            sscanf(token, "%lu", &pproc->flags);
            break;
        case  9:           /* min_flt */
        case 10:           /* cmin_flt */
        case 11:           /* maj_flt */
        case 12:           /* cmaj_flt */
            break;
        case 13:           /* utime */
            sscanf(token, "%lu", &pproc->tm.tms_utime);
            break;
        case 14:           /* stime */
            sscanf(token, "%lu", &pproc->tm.tms_stime);
            break;
        case 15:           /* cutime */
            sscanf(token, "%ld", &pproc->tm.tms_cutime);
            break;
        case 16:           /* cstime */
            sscanf(token, "%ld", &pproc->tm.tms_cstime);
            break;
        case 17:           /* priority */
        case 18:           /* nice */
        case 19:           /* 0 */
        case 20:           /* it_real_value */
        case 21:           /* start_time */
            break;
        case 22:           /* vsize */
            sscanf(token, "%lu", &pproc->vsize);
            break;
        case 23:           /* rss */
            sscanf(token, "%ld", &pproc->rss);
            break;
        case 24:           /* rlim_rss */
        case 25:           /* start_code */
        case 26:           /* end_code */
        case 27:           /* start_stack */
        case 28:           /* esp */
        case 29:           /* eip */
        case 30:           /* pending_signal */
        case 31:           /* blocked_signal */
        case 32:           /* sigign */
        case 33:           /* sigcatch */
        case 34:           /* wchan */
        case 35:           /* nswap */
        case 36:           /* cnswap */
        case 37:           /* exit_signal */
        case 38:           /* processor */
        default:
            break;
        }
    } while (strsep(&token, " ") != NULL);
    DBG("proc.pid            % 10d", pproc->pid);
    DBG("proc.ppid           % 10d", pproc->ppid);
    DBG("proc.state                   %c", pproc->state);
    DBG("proc.flags          0x%08lx", pproc->flags);
    DBG("proc.utime          %010lu", pproc->tm.tms_utime);
    DBG("proc.stime          %010lu", pproc->tm.tms_stime);
    DBG("proc.cutime         % 10ld", pproc->tm.tms_cutime);
    DBG("proc.cstime         % 10ld", pproc->tm.tms_cstime);
    DBG("proc.vsize          %010lu", pproc->vsize);
    DBG("proc.rss            % 10ld", pproc->rss);
        
    /* Must be the parent process in order to probe registers and floating point
       registers; and the status of target process must be 'T' (aka traced) */
    if ((pproc->ppid != getpid()) || (pproc->state != 'T'))
    {
        FUNC_RET((opt == PROBE_STAT), "proc_probe()");
    }

    /* Inspect process registers */
    #ifdef __linux__
    if (opt & PROBE_REGS)
    {
        /* General purpose registers */
        if (ptrace(PTRACE_GETREGS, pid, NULL, (void *)&pproc->regs) < 0)
        {
            WARNING("ptrace:PTRACE_GETREGS");
            FUNC_RET(false, "proc_probe()");
        }
        DBG("regs.ebx            0x%08lx", pproc->regs.ebx);
        DBG("regs.ecx            0x%08lx", pproc->regs.ecx);
        DBG("regs.edx            0x%08lx", pproc->regs.edx);
        DBG("regs.esi            0x%08lx", pproc->regs.esi);
        DBG("regs.edi            0x%08lx", pproc->regs.edi);
        DBG("regs.ebp            0x%08lx", pproc->regs.ebp);
        DBG("regs.eax            0x%08lx", pproc->regs.eax);
        DBG("regs.xds            0x%08lx", pproc->regs.xds);
        DBG("regs.xes            0x%08lx", pproc->regs.xes);
        DBG("regs.xfs            0x%08lx", pproc->regs.xfs);
        DBG("regs.xgs            0x%08lx", pproc->regs.xgs);
        DBG("regs.orig_eax       0x%08lx", pproc->regs.orig_eax);
        DBG("regs.eip            0x%08lx", pproc->regs.eip);
        DBG("regs.xcs            0x%08lx", pproc->regs.xcs);
        DBG("regs.eflags         0x%08lx", pproc->regs.eflags);
        DBG("regs.esp            0x%08lx", pproc->regs.esp);
        DBG("regs.xss            0x%08lx", pproc->regs.xss);
        
        /* Current instruction */
        pproc->op = ptrace(PTRACE_PEEKDATA, pid, (void *)pproc->regs.eip, 
                           NULL);
        if (errno != 0)
        {
            WARNING("ptrace:PTRACE_PEEKDATA");
            FUNC_RET(false, "proc_probe()");
        }
        DBG("proc.op             0x%08x", pproc->op);
    }
    #endif /* __linux__ */

    /* Inspect floating point registers */
    #ifdef __linux__
    if (opt & PROBE_FPREGS)
    {
        /* Floating point registers */
        if (ptrace(PTRACE_GETFPREGS, pid, NULL, (void *)&pproc->fpregs) < 0)
        {
            WARNING("ptrace:PTRACE_GETFPREGS");
            FUNC_RET(false, "proc_probe()");
        }
        DBG("fpregs.cwd          0x%08lx", pproc->fpregs.cwd);
        DBG("fpregs.swd          0x%08lx", pproc->fpregs.swd);
        DBG("fpregs.twd          0x%08lx", pproc->fpregs.twd);
        DBG("fpregs.fip          0x%08lx", pproc->fpregs.fip);
        DBG("fpregs.fcs          0x%08lx", pproc->fpregs.fcs);
        DBG("fpregs.fos          0x%08lx", pproc->fpregs.fos);
        DBG("fpregs.st_space     0x%08lx 0x%08lx 0x%08lx 0x%08lx",
            pproc->fpregs.st_space[ 0], pproc->fpregs.st_space[ 1],
            pproc->fpregs.st_space[ 2], pproc->fpregs.st_space[ 3]);
        DBG("                    0x%08lx 0x%08lx 0x%08lx 0x%08lx",
            pproc->fpregs.st_space[ 4], pproc->fpregs.st_space[ 5],
            pproc->fpregs.st_space[ 6], pproc->fpregs.st_space[ 7]);
        DBG("                    0x%08lx 0x%08lx 0x%08lx 0x%08lx",
            pproc->fpregs.st_space[ 8], pproc->fpregs.st_space[ 9],
            pproc->fpregs.st_space[10], pproc->fpregs.st_space[11]);
        DBG("                    0x%08lx 0x%08lx 0x%08lx 0x%08lx", 
            pproc->fpregs.st_space[12], pproc->fpregs.st_space[13],
            pproc->fpregs.st_space[14], pproc->fpregs.st_space[15]);
        DBG("                    0x%08lx 0x%08lx 0x%08lx 0x%08lx", 
            pproc->fpregs.st_space[16], pproc->fpregs.st_space[17],
            pproc->fpregs.st_space[18], pproc->fpregs.st_space[19]);
    }
    #endif /* __linux__ */
    
    FUNC_RET(true, "proc_probe()");
}

bool
proc_dump(const proc_t * const pproc, const void * const addr, 
          long * const pword)
{
    FUNC_BEGIN("proc_dump(%p,%p,%p)", pproc, addr, pword);
    
    assert(pproc);
    assert(addr);
    assert(pword);

    /* Access the memory of targeted process via procfs */
    char buffer[4096];

    sprintf(buffer, PROCFS "/%d/mem", pproc->pid);
    if (access(buffer, R_OK | F_OK) < 0)
    {
        WARNING("procfs entries missing or invalid");
        FUNC_RET(false, "proc_dump()");
    }

    /* Copy a word from targeted address */
    int fd = open(buffer, O_RDONLY);
    if (lseek(fd, (long)addr, SEEK_SET) < 0)
    {
        WARNING("lseek");
        FUNC_RET(false, "proc_dump()");
    }
    if (read(fd, (void *)pword, sizeof(long)) < 0)
    {
        WARNING("read");
        FUNC_RET(false, "proc_dump()");
    }
    close(fd);
    
    DBG("data.0x%08lx     0x%08lx", (unsigned long)addr, 
                                    (unsigned long)*pword);

    FUNC_RET(true, "proc_dump()");
}


bool
trace_self(void)
{
    FUNC_BEGIN("trace_self()");
    bool res = false;
    #ifdef __linux__
    res = (ptrace(PTRACE_TRACEME, 0, NULL, NULL) == 0);
    #else
    #warning "trace_self is not implemented for this platform"
    #endif /* __linux__ */
    FUNC_RET(res, "trace_self()");
}

bool 
trace_next(proc_t * const pproc, trace_type_t type)
{
    FUNC_BEGIN("trace_next(%p,%d)", pproc, type);
    assert(pproc);
    bool res = false;
    #ifdef __linux__
    pproc->tflags &= ~TFLAGS_SINGLE_STEP;
    pproc->tflags |= (type == TRACE_SINGLE_STEP)?(TFLAGS_SINGLE_STEP):(0U);
    DBG("proc.tflags         0x%08x", pproc->tflags);
    res = (ptrace(type, pproc->pid, NULL, NULL) == 0);
    #else
    #warning "trace_next is not implemented for this platform"
    #endif /* __linux__ */
    FUNC_RET(res, "trace_next()");
}

bool
trace_kill(const proc_t * const pproc, int signal)
{
    FUNC_BEGIN("trace_kill(%p,%d)", pproc, signal);
    assert(pproc);
    #ifdef __linux__
    if (pproc->tflags & TFLAGS_SINGLE_STEP)
    {
        ptrace(PTRACE_POKEUSER, pproc->pid, EAX * sizeof(int), SYS_pause);
    }
    else
    {
        ptrace(PTRACE_POKEUSER, pproc->pid, ORIG_EAX * sizeof(int), SYS_pause);
    }
    #else
    #warning "trace_kill is not fully implemented for this platform"
    #endif /* __linux__ */
    kill(-pproc->pid, signal);
    FUNC_RET(true, "trace_kill()");
}

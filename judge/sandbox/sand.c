#include <sandbox.h>
#include <symbols.h>
#include <getopt.h>
#include <sys/time.h>
#include <string.h>
#include <stdlib.h>
#include <assert.h>
#include <pwd.h>
#include <grp.h>
#include <syslog.h>
#include <unistd.h>

#define MAX_NAME_LEN 20

char short_options[] = "hvi:o:e:l:u:g:j:";
struct option long_options[] = {
	{"help",    0, 0, 'h'},
	{"version", 0, 0, 'v'},
	{"stdin",   1, 0, 'i'},
	{"stdout",  1, 0, 'o'},
	{"LOG_USER | LOG_INFO",  1, 0, 'e'},
	{"limit",   1, 0, 'l'},
	{"user",    1, 0, 'u'},
	{"group",   1, 0, 'g'},
	{"jail",    1, 0, 'j'},
	{0,         0, 0,  0 }
};

extern void __sandbox_default_policy(const policy_t * ppolicy, const event_t * pevent, action_t * paction);
extern int last_rf_called;
extern const char * s_result_name(int result);

int options_counter(int argc, char *argv[]);
static void print (const sandbox_t * const sandbox);
void usage(char program[]);
int argvtoquota(char *string, char *division, rlim_t quota[]);
void print_deatails(sandbox_t * sandbox);

int main(int argc, char *argv[]) {
	char *program_name = argv[0];
	if (argc == 1) {
		usage(program_name);
		exit(0);
	}

	sandbox_t sandbox;
	int optionsnu;
	int next_option;
	char* division = "=";
	int counter = 1;
	int jail_len;
	optionsnu = options_counter(argc ,argv);
	
	sandbox_init(&sandbox, (const char**)argv+optionsnu);

	sandbox.task.quota[S_QUOTA_WALLCLOCK] = 1000*15;
	sandbox.task.quota[S_QUOTA_CPU]       = 1000*30;
    sandbox.task.quota[S_QUOTA_MEMORY]    = 4096*512;
    sandbox.task.quota[S_QUOTA_DISK]      = 1024*1024;	
	sandbox.task.uid = getuid();
	sandbox.task.gid = getgid();

	struct passwd *user;
	struct group *grp;
	char username[MAX_NAME_LEN];
	char grpname[MAX_NAME_LEN];
	int name_len;
	FILE *ifp, *ofp, *efp;
	char command[80];
	int command_len;
	bool flag = false;


	
	result_t *result;
	int returnvalue;

    optind = 0;	
	opterr = 0;
	while (((next_option = getopt_long(argc, argv, short_options, long_options, NULL)) != -1) && (next_option != '?')) {
		++counter;
		switch(next_option) {
			case 'h':
				usage(program_name);
				exit(0);
			case 'l':
				++counter;
				if (argvtoquota(optarg, division, sandbox.task.quota)) {
					usage(program_name);
					exit(-1);
				}
				break;
			case 'j':
				++counter;
				for (jail_len=0; optarg[jail_len] != '\0'; jail_len++) {
					sandbox.task.jail[jail_len] = optarg[jail_len];
				}
				sandbox.task.jail[jail_len] = '\0';
				break;
			case 'v':
				//syslog(LOG_USER | LOG_INFO, "sand version 1.0\n");
				break;
			case 'u':
				++counter;
				for (name_len = 0; optarg[name_len] != '\0'; name_len++) {
					username[name_len] = optarg[name_len];
				}
				username[name_len] = '\0';
			    user = getpwnam(username);	
				sandbox.task.uid = user->pw_uid;
				break;
			case 'g':
				++counter;
				for (name_len = 0; optarg[name_len] != '\0'; name_len++) {
					grpname[name_len] = optarg[name_len];
				}
				grpname[name_len] = '\0';
				grp = getgrnam(grpname);
				sandbox.task.gid = grp->gr_gid;
				break;
			case 'i':
				++counter;
			    for (command_len = 0; optarg[command_len] != '\0'; command_len++) {
					if (optarg[command_len] == '|') {
						if (flag == false)
						{
							flag = true;
							command[command_len] = ' ';
						} else
						{
							command[command_len] = optarg[command_len];
						}
					} else {
						command[command_len] = optarg[command_len];
					}
				}
				if (!flag) {
					if(!(ifp = fopen(command, "r"))) {
						syslog(LOG_USER | LOG_INFO, "ifp fopen error\n");
						exit(-1);
					}
				} else {
					if (!(ifp = popen(command, "r"))) {
						syslog(LOG_USER | LOG_INFO, "ifp popen error\n");
						exit(-1);
					}
				}
				sandbox.task.ifd = fileno(ifp);
				memset(command, 0, 80);
				flag = false;
				break;
			case 'o':
				++counter;
			    for (command_len = 0; optarg[command_len] != '\0'; command_len++) {
					if (optarg[command_len] == '|') {
						if (flag == false)
						{
							flag = true;
							command[command_len] = ' ';
						} else
						{
							command[command_len] = optarg[command_len];
						}
					} else {
						command[command_len] = optarg[command_len];
					}
				}
				if (!flag) {
					if(!(ofp = fopen(command, "w"))) {
						syslog(LOG_USER | LOG_INFO, "ofp fopen error\n");
						exit(-1);
					}
				} else {
					if (!(ofp = popen(command, "w"))) {
						syslog(LOG_USER | LOG_INFO, "ofp popen error\n");
						exit(-1);
					}
				}
				sandbox.task.ofd = fileno(ofp);
				flag = false;
				memset(command, 0, 80);
				break;
			case 'e':
				++counter;
			    for (command_len = 0; optarg[command_len] != '\0'; command_len++) {
					if (optarg[command_len] == '|') {
						if (flag == false)
						{
							flag = true;
							command[command_len] = ' ';
						} else
						{
							command[command_len] = optarg[command_len];
						}
					} else {
						command[command_len] = optarg[command_len];
					}
				}
				if (!flag) {
					if(!(efp = fopen(command, "w"))) {
						syslog(LOG_USER | LOG_INFO, "efp fopen error\n");
						exit(-1);
					}
				} else {
					if (!(efp = popen(command, "w"))) {
						syslog(LOG_USER | LOG_INFO, "ofp popen error\n");
						exit(-1);
					}
				}
				sandbox.task.efd = fileno(efp);
				flag = false;
				memset(command, 0, 80);
				break;
			default:
				usage(program_name);
				exit(0);
		}
	}
	//print_deatails(&sandbox);
    sandbox.ctrl.policy.entry = (void *)__sandbox_default_policy;
	if (!sandbox_check(&sandbox)) {
		syslog(LOG_USER | LOG_INFO, "check error \n");
		return(-1);
	}
	result = sandbox_execute(&sandbox);
   
	returnvalue = *result;
	
    if (returnvalue != S_RESULT_OK)
    {
        if (returnvalue == S_RESULT_RF) 
            syslog(LOG_USER | LOG_INFO, "%s:S_RESULT_%s(%d)", argv[optionsnu], s_result_name(returnvalue), last_rf_called);
        else
            syslog(LOG_USER | LOG_INFO, "%s:S_RESULT_%s", argv[optionsnu], s_result_name(returnvalue));
        print(&sandbox);
    }

	if (!sandbox_fini(&sandbox))
	{
		return returnvalue;
	} else
	{
		return -1;
	}
}


static void print (const sandbox_t * const sandbox) {
	assert(sandbox);
   	syslog(LOG_USER | LOG_INFO, "status:    % 10d\n", sandbox->status);
   	syslog(LOG_USER | LOG_INFO, "result:    % 10d\n", sandbox->result);
	syslog(LOG_USER | LOG_INFO, "elapsed:   % 10ld msec\n", sandbox->stat.stopped.tv_sec * 1000 -
			sandbox->stat.started.tv_sec * 1000 +
			sandbox->stat.stopped.tv_usec / 1000 -
			sandbox->stat.started.tv_usec / 1000);
	syslog(LOG_USER | LOG_INFO, "cpu.usr:   % 10ld msec\n",
			sandbox->stat.ru.ru_utime.tv_sec * 1000 +
			sandbox->stat.ru.ru_utime.tv_usec / 1000);
	syslog(LOG_USER | LOG_INFO, "cpu.sys:   % 10ld msec\n",
			sandbox->stat.ru.ru_stime.tv_sec * 1000 +
			sandbox->stat.ru.ru_stime.tv_usec / 1000);
	syslog(LOG_USER | LOG_INFO, "cpu.all:   % 10ld msec\n",
			sandbox->stat.ru.ru_utime.tv_sec * 1000 +    
			sandbox->stat.ru.ru_utime.tv_usec / 1000 +
			sandbox->stat.ru.ru_stime.tv_sec * 1000 +
			sandbox->stat.ru.ru_stime.tv_usec / 1000);
	syslog(LOG_USER | LOG_INFO, "cpu.tsc:   %10llu\n", sandbox->stat.tsc);
	syslog(LOG_USER | LOG_INFO, "mem.vsize: % 10ld kB\n", (long)sandbox->stat.vsize / 1024);
}

void usage(char program[]) {
	printf("usage:%s [option]\n",program);
	printf("[option]\n");
	printf("    -h --help                   help information\n");
	printf("    -v --version                show version\n");
	printf("    -i --stdin <channel>        direct <channel> to the target program\n");
	printf("    -o --stdout <channel>       direct the stdout of target program to <channel>\n");
	printf("    -e --LOG_INFO <channel>       direct the stderr of target program to <channel>\n");
	printf("    -l --Limit <type>=<value>   set quota limit on resource <type> to <value>\n");
	printf("    -j --jail <path>            uses <path> as the program jail\n");
	printf("Examples:\n");
	printf("$ sand -l cpu=1500 -l memory=2024000 -l disk=512000 /foo/bar.exe\n");
	printf("$ sand -j /foo /foo/bar.exe arg1 arg2 arg3\n");
	printf("$ sand -i\"|cat /etc/passwd\" -o\"|cat\" /usr/bin/grep \"root\"\n");
}

int argvtoquota(char *string, char* division, rlim_t quota[]) {
	int value;
	char *p1, *p2;
	p1 = strtok(string, division);
	p2 = strtok(NULL, division);
	value = atoi(p2);
	char *p[] = {"wallclock", "cpu", "memory", "disk"};
	if (!strcasecmp(p1, p[0])) { 
		quota[S_QUOTA_WALLCLOCK] = value; 
		return 0;
	}
	else if (!strcasecmp(p1, p[1])) { 
		quota[S_QUOTA_CPU] = value; 
		return 0;
	}
	else if (!strcasecmp(p1, p[2])) {
		quota[S_QUOTA_MEMORY] = value;
		return 0;
	}
	else if (!strcasecmp(p1, p[3])) {
		quota[S_QUOTA_DISK] = value;
		return 0;
	}
	else return 1;
}



int options_counter(int argc, char *argv[]) {
	int i = 1;
	int ch;
	while (((ch = getopt_long(argc, argv, short_options, long_options, NULL)) != -1) && (ch != '?')) {
		i++;
		switch(ch) {
			case 'j':
			case 'l':
			case 'i':
			case 'o':
			case 'e':
			case 'u':
			case 'g':
				i++;
				break;
			default:
				break;
		}
	}
	return i;
}
		
void print_deatails(sandbox_t *sandbox) {
	assert(sandbox);
	int i;
	syslog(LOG_USER | LOG_INFO, "jail: %s\n", sandbox->task.jail);
	syslog(LOG_USER | LOG_INFO, "uid: %d\n", sandbox->task.uid);
	syslog(LOG_USER | LOG_INFO, "gid: %d\n", sandbox->task.gid);
	syslog(LOG_USER | LOG_INFO, "ifd: %d\n", sandbox->task.ifd);
	syslog(LOG_USER | LOG_INFO, "ofd: %d\n", sandbox->task.ofd);
	syslog(LOG_USER | LOG_INFO, "efd: %d\n", sandbox->task.efd);
	for (i=0; i<4; i++) {
		syslog(LOG_USER | LOG_INFO, "quota[%d]: %d\n", i, (int)sandbox->task.quota[i]);
	}
}


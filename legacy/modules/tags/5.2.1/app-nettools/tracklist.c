/*
 
	tracklist.c
	
	/proc/net/ip_conntrack is a connection tracking list maintained
	by iptables. Tracklist reads the /proc/net/ip_conntrack file and
	lists various fields in a more readable format. It is a quick
	replacement for the "NETSTAT -M" or "IPCHAINS -M -L" commands
	which no longer function with the 2.4.0 kernel (and iptables).
	
	Tracklist was written by Earl C. Terwilliger who can be reached
	via email at the following address: earlt@agent-source.com
	
	Compile:    cc -o tracklist tracklist.c
	
	Syntax:    tracklist
	 or       tracklist -n
	
	Contains some additional hacks by W.H.Welch
	1.  rudimentary processing of GRE data
	2.  added -s option to display ESTABLISHED TCP sources only

 
*/

#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>
#include <string.h>
#include <ctype.h>
#include <errno.h>
#include <netdb.h>
#include <sys/types.h>
#include <netinet/in.h>

#define MAXLINE 512
#define MAXFIELDS 80
#define MAXDNSNAME 64
#define BLANK   ' '
#define LF      0x0A
#define CR      0x0D
#define TAB     0x09
#define SPACE   0x20

int  getparsedline(char *line);
char *extract_field(char start, char *fld);
char *get_host_name(char *ip);
int  strfind(char *ss,char *fs,int length);
int  get_port(char *str);
char *get_expire_time(char *fld);

#define CONNTRACK "/proc/net/ip_conntrack"

FILE *fp1;
int lookup = 1;
int source = 0;
static char line[MAXLINE+1];
static char *field[MAXFIELDS];

int main(int argc, char **argv)
{
    int c;
    int sport;
    int dport;
    char *et;
    char shost[MAXDNSNAME];
    char dhost[MAXDNSNAME];
    if (argc > 1) {
            if (argv[1][1] == 'n')
                lookup = 0;
            if (argv[1][1] == 's') {
                    lookup = 0;
                    source = 1;
                }
        }
    if ((fp1 = fopen(CONNTRACK,"rb")) == NULL) {
            printf("OPEN failed for file %s\n",CONNTRACK);
            exit(1);
        }
        
	if (source == 0)
    	printf("%-5s%-11s%-24s%-24s%-24s%s\n","PROT","  EXPIRE   ","     SOURCE","      DESTINATION","       STATUS    ","      PORTS");

    while (c = getparsedline(line)) {
            et = get_expire_time(field[2]);
            switch (field[0][0]) {
                case 'u':
                    sport = get_port(field[5]);
                    dport = get_port(field[6]);
                    strcpy(shost,get_host_name(extract_field('=',field[3])));
                    strcpy(dhost,get_host_name(extract_field('=',field[4])));
                    if (source == 0)
                        printf("UDP  %-11s %-24s %-24s %-24s   % 5d->%d\n",et,shost,dhost,(field[11][0] == '[' ? field[11] : ""),sport,dport);
                    break;
                case 't':
                    sport = get_port(field[6]);
                    dport = get_port(field[7]);
                    strcpy(shost,get_host_name(extract_field('=',field[4])));
                    strcpy(dhost,get_host_name(extract_field('=',field[5])));
                    if (source == 0)
                        printf("TCP  %-11s %-24s %-24s %-12s %-12s  % 5d->%d\n",et,shost,dhost,field[3],(field[8][0]=='[' ? field[8] : ""),sport,dport);
                    else
                    	if(strncmp(extract_field('E',field[3]),"STABLISHED",10) == 0)
                        	printf("%-24s \n",shost);
                    break;
                case 'g':
                    strcpy(shost,get_host_name(extract_field('=',field[5])));
                    strcpy(dhost,get_host_name(extract_field('=',field[6])));
                    if (source == 0)
                        printf("GRE  %-11s %-24s %-24s\n",et,shost,dhost);
                    break;
                default:
                    break;
                }
        }
    fclose(fp1);
    exit(0);
}
char *get_expire_time(char *str)
{
    /*
       time field in jiffies (i.e. a timer tick) 
       100 jiffies = 1 seconds 
    */
    static char buf[10];
    unsigned long t;
    int h,m,s,i;
    t = atol(str);
    h = t / 360000.;
    t -= h * 360000.;
    m = t / 6000.;
    t -= m * 6000.;
    s = t / 100.;
    t -= s * 100.;
    i = t;
    sprintf(buf,"%02d:%02d:%02d.%02d",h,m,s,i);
    return(buf);
}
char *get_host_name(char *ip)
{
    static struct hostent *h;
    static struct in_addr addr;
    if (lookup == 0)
        return(ip);
    if (inet_aton(ip,&addr) == 0)
        return("IP address error");
    if ((h=gethostbyaddr(&addr,sizeof(addr),AF_INET)) == NULL) {
            return(ip);
        }
    return(h->h_name);
}
char *extract_field(char start, char *fld)
{
    while(*fld != start)
        ++fld;
    ++fld;
    return(fld);
}
int getparsedline(line)
char *line;
{
    int c,d,f;
    char *str, prev;
    f = 0;
    str = line;
    prev = SPACE;
    for (d=0;d<MAXLINE;++d) {
            c = getc(fp1);
            if (c == EOF)
                break;
            if (c == CR)
                continue;
            if (c == LF) {
                    ++d;
                    break;
                }
            if (c == SPACE)
                *line = '\0';
            else {
                    if (prev == SPACE) {
                            field[f] = line;
                            ++f;
                        }
                    *line = c;
                }
            prev = c;
            ++line;
        }
    field[f] = NULL;
    *line = 0x00;
    return(d);
}
int strfind(char *ss, char *fs,int length)
{
    char *fss, *sss, *oss;
    fss = fs;
    sss = ss;
    oss = ss;
    while (length) {
            while ((toupper(*ss) == toupper(*fs)) && *fs) {
                    ++ss;
                    ++fs;
                }
            if (!(*fs))
                return(sss-oss);
            fs = fss;
            ss = ++sss;
            --length;
        }
    return(-1);
}
int get_port(char *str)
{
    while(*str) {
            if (*str == '=') {
                    ++str;
                    return(atoi(str));
                }
            ++str;
        }
    return(-1);
}

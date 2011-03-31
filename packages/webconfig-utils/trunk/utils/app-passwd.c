///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2000 Point Clark Networks.
//
// This software may be freely redistributed under the terms of the GNU
// public license.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
//
// General synopsis:
// -Accepts username password pair (seperated by white-space) from standard in.
// -Looks up username in shadow password file.
// -Extracts salt from password entry.
// -Crypts supplied password with extracted salt.
// -Returns 0 if crypted passwords are identical.
// -Returns 1 if anything goes wrong along the way.
//
// Compilation:
// # gcc -O2 -s -o cc-passwd -lcrypt cc-passwd.c
//
///////////////////////////////////////////////////////////////////////////////
// $Id: cc-passwd.c,v 1.1.1.1 2004/01/16 03:41:51 devel Exp $

#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <crypt.h>
#include <shadow.h>

static char salt[12], user[128], pass[128];

void die(void)
{
	memset(salt, '\0', 12);
	memset(user, '\0', 128);
	memset(pass, '\0', 128);
}

int main(int argc, char *argv[])
{
	struct spwd *passwd;

	atexit(die); die();

	if(fscanf(stdin, "%127s %127s", user, pass) != 2)
		return 1;

	if(!(passwd = getspnam(user)))
		return 1;

	strncpy(salt, passwd->sp_pwdp, 11);
	strncpy(pass, crypt(pass, salt), 127);

	if(!strncmp(pass, passwd->sp_pwdp, 127))
		return 0;

	return 1;
}

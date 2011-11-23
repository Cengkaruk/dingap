// ClearSync: system synchronization daemon.
// Copyright (C) 2011 ClearFoundation <http://www.clearfoundation.com>
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <string>
#include <stdexcept>
#include <vector>

#include <unistd.h>
#include <stdio.h>
#include <stdint.h>
#include <string.h>
#include <pthread.h>
#include <errno.h>
#include <regex.h>
#include <pwd.h>
#include <grp.h>

#define OPENSSL_THREAD_DEFINES
#include <openssl/opensslconf.h>
#ifndef OPENSSL_THREADS
#error "OpenSSL missing thread support"
#endif
#include <openssl/sha.h>

#include <clearsync/csexception.h>
#include <clearsync/csutil.h>

csCriticalSection *csCriticalSection::instance = NULL;
pthread_mutex_t *csCriticalSection::mutex = NULL;

csCriticalSection::csCriticalSection()
{
    if (instance != NULL)
        throw csException(EEXIST, "csCriticalSection");
    mutex = new pthread_mutex_t;
    pthread_mutex_init(mutex, NULL);
    instance = this;
}

csCriticalSection::~csCriticalSection()
{
    if (instance != this) return;
    pthread_mutex_destroy(mutex);
    delete mutex;
    mutex = NULL;
}

void csCriticalSection::Lock(void)
{
    if (instance && mutex)
        pthread_mutex_lock(mutex);
}

void csCriticalSection::Unlock(void)
{
    if (instance && mutex)
        pthread_mutex_unlock(mutex);
}

csRegEx::csRegEx(const char *expr, size_t nmatch, int flags)
    : match(NULL), nmatch(nmatch), matches(NULL)
{
    int rc;
    if (!nmatch) flags |= REG_NOSUB;
    if ((rc = regcomp(&regex, expr, flags)) != 0) {
        size_t errsize = regerror(rc, &regex, NULL, 0);
        if (errsize > 0) {
            char *buffer = new char[errsize + 1];
            regerror(rc, &regex, buffer, errsize);
            string errstr(buffer);
            delete buffer;
            throw csException(expr, errstr.c_str());
        }
        else
            throw csException("Unknown regex compilation error");
    }
    if (nmatch) {
        match = new regmatch_t[nmatch];
        matches = new char *[nmatch];
        for (size_t i = 0; i < nmatch; i++) matches[i] = NULL;
    }
}

csRegEx::~csRegEx()
{
    regfree(&regex);
    if (nmatch && match) delete [] match;
    for (size_t i = 0; i < nmatch; i++) {
        if (matches[i]) delete [] matches[i];
    }
    if (matches) delete [] matches;
}

int csRegEx::Execute(const char *subject)
{
    if (!subject)
        throw csException("Invalid regex subject");
    int rc = regexec(&regex, subject, nmatch, match, 0);
    for (size_t i = 0; i < nmatch; i++) {
        if (matches[i]) delete [] matches[i];
        matches[i] = NULL;
    }
    if (rc == 0) {
        for (size_t i = 0; i < nmatch; i++) {
            int len = match[i].rm_eo - match[i].rm_so;
            char *buffer = new char[len + 1];
            memset(buffer, 0, len + 1);
            memcpy(buffer, subject + match[i].rm_so, len);
            matches[i] = buffer;
        }
    }
    return rc;
}

const char *csRegEx::GetMatch(size_t match)
{
    if (match < 0 || match >= nmatch)
        throw csException("Invalid regex match offset");
    if (this->match[match].rm_so == -1) return NULL;
    return matches[match];
}

long csGetPageSize(void)
{
#ifdef HAVE_GETPAGESIZE
    // TODO: sysconf
    return getpagesize();
#else
    return 4096;
#endif
}

int csExecute(const string &command)
{
    long page_size = ::csGetPageSize();
    char buffer[page_size];
    FILE *ph = popen(command.c_str(), "r");
    if (ph == NULL) return errno;
    while (!feof(ph)) {
        if (!fgets(buffer, (int)page_size, ph)) break;
    }
    return pclose(ph);
}

int csExecute(const string &command, vector<string> &output)
{
    long page_size = ::csGetPageSize();
    char buffer[page_size];
    FILE *ph = popen(command.c_str(), "r");
    if (ph == NULL) return errno;
    while (!feof(ph)) {
        if (!fgets(buffer, (int)page_size, ph)) break;
        output.push_back(buffer);
    }
    return pclose(ph);
}

void csHexDump(FILE *fh, const void *data, uint32_t length)
{
    uint8_t c, *p = (uint8_t *)data;
    char bytestr[4] = { 0 };
    char addrstr[10] = { 0 };
    char hexstr[16 * 3 + 5] = { 0 };
    char charstr[16 * 1 + 5] = { 0 };

    for (uint32_t n = 1; n <= length; n++) {
        if (n % 16 == 1) {
            // Store address for this line
            snprintf(addrstr, sizeof(addrstr),
                "%.5x", (uint32_t)(p - (uint8_t *)data));
        }
            
        c = *p;
        if (isprint(c) == 0) c = '.';

        // Store hex str (for left side)
        snprintf(bytestr, sizeof(bytestr), "%02X ", *p);
        strncat(hexstr, bytestr, sizeof(hexstr) - strlen(hexstr) - 1);

        // Store char str (for right side)
        snprintf(bytestr, sizeof(bytestr), "%c", c);
        strncat(charstr, bytestr, sizeof(charstr) - strlen(charstr) - 1);

        if(n % 16 == 0) { 
            // Line completed
            fprintf(fh,
                "%5.5s:  %-49.49s %s\n", addrstr, hexstr, charstr);
            hexstr[0] = 0;
            charstr[0] = 0;
        } else if(n % 8 == 0) {
            // Half line: add whitespaces
            strncat(hexstr, " ", sizeof(hexstr) - strlen(hexstr) -1);
        }
        // Next byte...
        p++;
    }

    if (strlen(hexstr) > 0) {
        // Print rest of buffer if not empty
        fprintf(fh, "%5.5s:  %-49.49s %s\n", addrstr, hexstr, charstr);
    }
}

uid_t csGetUserId(const string &user)
{
    struct passwd pwd;
    struct passwd *result;
    char *buffer;
    long buffer_size;
    int rc;

    buffer_size = sysconf(_SC_GETPW_R_SIZE_MAX);
    if (buffer_size == -1) buffer_size = 16384;

    buffer = new char[buffer_size];
    rc = getpwnam_r(user.c_str(),
        &pwd, buffer, (size_t)buffer_size, &result);
    if (result == NULL) {
        delete [] buffer;
        if (rc == 0)
            throw csException("User not found", user.c_str());
        throw csException(rc, "getpwnam_r");
    }
    uid_t uid = pwd.pw_uid;
    delete [] buffer;

    return uid;
}

gid_t csGetGroupId(const string &group)
{
    struct group grp;
    struct group *result;
    char *buffer;
    long buffer_size;
    int rc;

    buffer_size = sysconf(_SC_GETGR_R_SIZE_MAX);
    if (buffer_size == -1) buffer_size = 16384;

    buffer = new char[buffer_size];
    rc = getgrnam_r(group.c_str(),
        &grp, buffer, (size_t)buffer_size, &result);
    if (result == NULL) {
        delete [] buffer;
        if (rc == 0)
            throw csException("Group not found", group.c_str());
        throw csException(rc, "getgrnam_r");
    }
    gid_t gid = grp.gr_gid;
    delete [] buffer;

    return gid;
}

void csSHA1(const string &filename, uint8_t *digest)
{
    SHA_CTX ctx;
    size_t bytes;
    long page_size = ::csGetPageSize();
    uint8_t buffer[page_size];
    FILE *fh = fopen(filename.c_str(), "r");
    if (fh == NULL)
        throw csException(errno, filename.c_str());
    if (SHA1_Init(&ctx) != 1)
        throw csException(EINVAL, "SHA1_Init");
    while (!feof(fh)) {
        if ((bytes = fread(buffer, 1, page_size, fh)) <= 0) break;
        SHA1_Update(&ctx, buffer, bytes);
    }
    fclose(fh);
    SHA1_Final(digest, &ctx);
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4

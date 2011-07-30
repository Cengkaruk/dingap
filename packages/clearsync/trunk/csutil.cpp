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

#include <unistd.h>
#include <stdio.h>
#include <string.h>
#include <pthread.h>
#include <errno.h>
#include <regex.h>

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

csRegEx::csRegEx(const char *expr, int nmatch, int flags)
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
        for (int i = 0; i < nmatch; i++) matches[i] = NULL;
    }
}

csRegEx::~csRegEx()
{
    regfree(&regex);
    if (nmatch && match) delete [] match;
    for (int i = 0; i < nmatch; i++) {
        if (matches[i]) delete [] matches[i];
    }
    if (matches) delete [] matches;
}

int csRegEx::Execute(const char *subject)
{
    if (!subject)
        throw csException("Invalid regex subject");
    int rc = regexec(&regex, subject, nmatch, match, 0);
    for (int i = 0; i < nmatch; i++) {
        if (matches[i]) delete [] matches[i];
        matches[i] = NULL;
    }
    if (rc == 0) {
        for (int i = 0; i < nmatch; i++) {
            int len = match[i].rm_eo - match[i].rm_so;
            char *buffer = new char[len + 1];
            memset(buffer, 0, len + 1);
            memcpy(buffer, subject + match[i].rm_so, len);
            matches[i] = buffer;
        }
    }
    return rc;
}

const char *csRegEx::GetMatch(int match)
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
    FILE *ph = popen(command.c_str(), "r");
    if (ph == NULL) return errno;
    char buffer[::csGetPageSize()];
    while (!feof(ph)) {
        if (!fgets(buffer, ::csGetPageSize(), ph)) break;
    }
    return pclose(ph);
}

csSHA1::csSHA1()
{
    Reset();
}

csSHA1::~csSHA1()
{
}

void csSHA1::Reset(void)
{
    length_low = 0;
    length_high = 0;
    message_block_index = 0;
    H[0] = 0x67452301;
    H[1] = 0xEFCDAB89;
    H[2] = 0x98BADCFE;
    H[3] = 0x10325476;
    H[4] = 0xC3D2E1F0;
    computed = false;
    corrupted = false;
}

bool csSHA1::Result(unsigned *message_digest_array)
{
    if (corrupted) return false;
    if (!computed) {
        PadMessage();
        computed = true;
    }

    for (int i = 0; i < 5; i++)
        message_digest_array[i] = H[i];

    return true;
}

void csSHA1::Input(const unsigned char *message_array, unsigned length)
{
    if (!length) return;
    if (computed || corrupted) {
        corrupted = true;
        return;
    }

    while (length-- && !corrupted) {
        message_block[message_block_index++] = (*message_array & 0xFF);

        length_low += 8;
        length_low &= 0xFFFFFFFF;
        if (length_low == 0) {
            length_high++;
            length_high &= 0xFFFFFFFF;
            if (length_high == 0)
                corrupted = true;
        }

        if (message_block_index == 64)
            ProcessMessageBlock();

        message_array++;
    }
}

void csSHA1::Input(const char *message_array, unsigned length)
{
    Input((unsigned char *)message_array, length);
}

void csSHA1::Input(unsigned char message_element)
{
    Input(&message_element, 1);
}

void csSHA1::Input(char message_element)
{
    Input((unsigned char *)&message_element, 1);
}

csSHA1& csSHA1::operator<<(const char *message_array)
{
    const char *p = message_array;

    while(*p) {
        Input(*p);
        p++;
    }

    return *this;
}

csSHA1& csSHA1::operator<<(const unsigned char *message_array)
{
    const unsigned char *p = message_array;

    while(*p) {
        Input(*p);
        p++;
    }

    return *this;
}

csSHA1& csSHA1::operator<<(const char message_element)
{
    Input((unsigned char *)&message_element, 1);
    return *this;
}

csSHA1& csSHA1::operator<<(const unsigned char message_element)
{
    Input(&message_element, 1);
    return *this;
}

void csSHA1::ProcessMessageBlock(void)
{
    int t;
    unsigned temp;
    unsigned W[80];
    unsigned A, B, C, D, E;
    const unsigned K[] = {
        0x5A827999,
        0x6ED9EBA1,
        0x8F1BBCDC,
        0xCA62C1D6
    };

    for (t = 0; t < 16; t++) {
        W[t] = ((unsigned)message_block[t * 4]) << 24;
        W[t] |= ((unsigned)message_block[t * 4 + 1]) << 16;
        W[t] |= ((unsigned)message_block[t * 4 + 2]) << 8;
        W[t] |= ((unsigned)message_block[t * 4 + 3]);
    }

    for (t = 16; t < 80; t++)
       W[t] = CircularShift(1, W[t - 3] ^ W[t - 8] ^ W[t - 14] ^ W[t - 16]);

    A = H[0]; B = H[1]; C = H[2]; D = H[3]; E = H[4];

    for (t = 0; t < 20; t++) {
        temp = CircularShift(5, A) + ((B & C) | ((~B) & D)) + E + W[t] + K[0];
        temp &= 0xFFFFFFFF;
        E = D;
        D = C;
        C = CircularShift(30, B);
        B = A;
        A = temp;
    }

    for (t = 20; t < 40; t++) {
        temp = CircularShift(5, A) + (B ^ C ^ D) + E + W[t] + K[1];
        temp &= 0xFFFFFFFF;
        E = D;
        D = C;
        C = CircularShift(30, B);
        B = A;
        A = temp;
    }

    for (t = 40; t < 60; t++) {
        temp = CircularShift(5, A) +
               ((B & C) | (B & D) | (C & D)) + E + W[t] + K[2];
        temp &= 0xFFFFFFFF;
        E = D;
        D = C;
        C = CircularShift(30, B);
        B = A;
        A = temp;
    }

    for(t = 60; t < 80; t++) {
        temp = CircularShift(5, A) + (B ^ C ^ D) + E + W[t] + K[3];
        temp &= 0xFFFFFFFF;
        E = D;
        D = C;
        C = CircularShift(30, B);
        B = A;
        A = temp;
    }

    H[0] = (H[0] + A) & 0xFFFFFFFF;
    H[1] = (H[1] + B) & 0xFFFFFFFF;
    H[2] = (H[2] + C) & 0xFFFFFFFF;
    H[3] = (H[3] + D) & 0xFFFFFFFF;
    H[4] = (H[4] + E) & 0xFFFFFFFF;

    message_block_index = 0;
}

void csSHA1::PadMessage()
{
    if (message_block_index > 55) {
        message_block[message_block_index++] = 0x80;
        while(message_block_index < 64)
            message_block[message_block_index++] = 0;

        ProcessMessageBlock();

        while(message_block_index < 56)
            message_block[message_block_index++] = 0;
    }
    else {
        message_block[message_block_index++] = 0x80;
        while(message_block_index < 56)
            message_block[message_block_index++] = 0;
    }

    message_block[56] = (length_high >> 24) & 0xFF;
    message_block[57] = (length_high >> 16) & 0xFF;
    message_block[58] = (length_high >> 8) & 0xFF;
    message_block[59] = (length_high) & 0xFF;
    message_block[60] = (length_low >> 24) & 0xFF;
    message_block[61] = (length_low >> 16) & 0xFF;
    message_block[62] = (length_low >> 8) & 0xFF;
    message_block[63] = (length_low) & 0xFF;

    ProcessMessageBlock();
}

unsigned csSHA1::CircularShift(int bits, unsigned word)
{
    return ((word << bits) & 0xFFFFFFFF) |
        ((word & 0xFFFFFFFF) >> (32 - bits));
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4

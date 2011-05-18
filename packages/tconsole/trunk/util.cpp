#include <sys/types.h>
#include <sys/stat.h>
#include <sys/mman.h>

#include <fcntl.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "util.h"
#include "exceptions.h"

ccRegEx::ccRegEx(const char *expr, int nmatch, int flags)
	: match(NULL), nmatch(nmatch), matches(NULL)
{
	if (!nmatch) flags |= REG_NOSUB;
	if (regcomp(&regex, expr, flags) != 0)
		throw ccException("Regular expression compilation error");
	if (nmatch) {
		match = new regmatch_t[nmatch];
		matches = new char *[nmatch];
		for (int i = 0; i < nmatch; i++) matches[i] = NULL;
	}
}

ccRegEx::~ccRegEx()
{
	regfree(&regex);
	if (nmatch && match) delete [] match;
	for (int i = 0; i < nmatch; i++) {
		if (matches[i]) delete [] matches[i];
	}
	if (matches) delete [] matches;
}

int ccRegEx::Execute(const char *subject)
{
	if (!subject)
		throw ccException("Invalid regular expression subject");
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

const char *ccRegEx::GetMatch(int match)
{
	if (match < 0 || match >= nmatch)
		throw ccException("Invalid regular expression match offset");
	if (this->match[match].rm_so == -1) return NULL;
	return matches[match];
}

ccFile::ccFile()
    : filename(NULL), fd(-1), buffer((char *)MAP_FAILED)
{
}

ccFile::ccFile(const char *filename)
    : filename(filename), fd(-1), buffer((char *)MAP_FAILED)
{
    Map(filename);
}

ccFile::~ccFile()
{
    Reset();
}

void ccFile::Reset(void)
{
    if (file_stat.st_size != -1 && buffer != MAP_FAILED)
        munmap((void *)buffer, file_stat.st_size);
    else if (file_stat.st_size == -1 && buffer != MAP_FAILED)
        free(buffer);
    if (fd != -1) close(fd);
    fd = -1;
    buffer = (char *)MAP_FAILED;
    filename = NULL;
    file_stat.st_size = -1;
}

const char *ccFile::Map(const char *filename)
{
    Reset();

    fd = open(filename, O_RDONLY);
    if (fd == -1)
        throw ccException("Error opening file");
    if (fstat(fd, &file_stat) == -1) {
        close(fd);
        throw ccException("Unable to stat file");
    }
    buffer = (char *)mmap(0, file_stat.st_size,
        PROT_READ, MAP_SHARED, fd, 0);
    if (buffer == MAP_FAILED) {
        close(fd);
        throw ccException("Error mapping file");
    }
    this->filename = filename;
    return (const char *)buffer;
}

const char *ccFile::Read(const char *filename)
{
    Reset();

    fd = open(filename, O_RDONLY);
    if (fd == -1)
        throw ccException("Error opening file");

    long pages = 1;
    long page_size = sysconf(_SC_PAGESIZE);
    if (page_size == -1) page_size = 4096;
    buffer = (char *)realloc(NULL, pages * page_size);

    void *ptr = (void *)buffer;
    ssize_t total = 0, bytes;
    for ( ;; ) {
        bytes = read(fd, ptr, page_size);
        if (bytes > 0) total += bytes;
        if (bytes != page_size) break;
        pages++;
        buffer = (char *)realloc((void *)buffer, pages * page_size);
        ptr = buffer + (pages * page_size);
    }
    buffer = (char *)realloc((void *)buffer, total + 1);
    ptr = buffer + total;
    (*(char *)ptr) = '\0';
    file_stat.st_size = -1;
    this->filename = filename;
    return (const char *)buffer;
}

// vi: ts=4

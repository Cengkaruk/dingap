#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <limits.h>

#ifndef PTHREAD_STACK_MIN
#warning "PTHREAD_STACK_MIN: not defined"
#endif

static size_t stack_align(size_t value)
{
	// posix_memalign
	// pthread_attr_setstack
	long page_size = getpagesize();
	if (value < PTHREAD_STACK_MIN) return PTHREAD_STACK_MIN;
	if (value % page_size) value += (value % page_size);
	return value;
}

int main(int argc, char *argv[])
{
	long page_size = getpagesize();
	size_t v1 = 100, v2 = 3, v3 = 1024, v4 = 32768;
	printf("v1: %ld, %ld\n", v1, stack_align(v1));
	printf("v2: %ld, %ld\n", v2, stack_align(v2));
	printf("v3: %ld, %ld\n", v3, stack_align(v3));
	printf("v4: %ld, %ld\n", v4, stack_align(v4));
}


dnl $Id$
dnl config.m4 for extension statvfs

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

dnl PHP_ARG_WITH(statvfs, for statvfs support,
dnl Make sure that the comment is aligned:
dnl [  --with-statvfs             Include statvfs support])

dnl Otherwise use enable:

PHP_ARG_ENABLE(statvfs, whether to enable statvfs support,
[  --enable-statvfs           Enable statvfs support])

PHP_NEW_EXTENSION(statvfs, statvfs.c, $ext_shared)

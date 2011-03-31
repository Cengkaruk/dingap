dnl $Id: config.m4,v 1.1 2004/02/18 22:39:39 devel Exp $
dnl config.m4 for extension ifconfig

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

dnl PHP_ARG_WITH(ifconfig, for ifconfig support,
dnl Make sure that the comment is aligned:
dnl [  --with-ifconfig             Include ifconfig support])

dnl Otherwise use enable:

PHP_ARG_ENABLE(ifconfig, whether to enable ifconfig support,
dnl Make sure that the comment is aligned:
[  --enable-ifconfig           Enable ifconfig support])

if test "$PHP_IFCONFIG" != "no"; then
  dnl Write more examples of tests here...

  dnl # --with-ifconfig -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/ifconfig.h"  # you most likely want to change this
  dnl if test -r $PHP_IFCONFIG/; then # path given as parameter
  dnl   IFCONFIG_DIR=$PHP_IFCONFIG
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for ifconfig files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       IFCONFIG_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$IFCONFIG_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the ifconfig distribution])
  dnl fi

  dnl # --with-ifconfig -> add include path
  dnl PHP_ADD_INCLUDE($IFCONFIG_DIR/include)

  dnl # --with-ifconfig -> check for lib and symbol presence
  dnl LIBNAME=ifconfig # you may want to change this
  dnl LIBSYMBOL=ifconfig # you most likely want to change this 

  dnl PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $IFCONFIG_DIR/lib, IFCONFIG_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_IFCONFIGLIB,1,[ ])
  dnl ],[
  dnl   AC_MSG_ERROR([wrong ifconfig lib version or lib not found])
  dnl ],[
  dnl   -L$IFCONFIG_DIR/lib -lm -ldl
  dnl ])
  dnl
  dnl PHP_SUBST(IFCONFIG_SHARED_LIBADD)

  PHP_NEW_EXTENSION(ifconfig, ifconfig.c, $ext_shared)
fi

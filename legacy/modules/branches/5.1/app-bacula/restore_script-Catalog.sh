#!/bin/sh
TYPE=mysql
HOST=localhost
NAME=bacula
USER=bacula
PASS=
PORT=3307
if ( [ "$USER" == "" ] && [ "$PASS" == "" ] ); then
  /opt/bacula/usr/bin/mysqladmin -h$HOST -P$PORT -f drop $NAME
  /opt/bacula/usr/bin/mysqladmin -h$HOST -P$PORT create $NAME
  /opt/bacula/usr/bin/mysql -h$HOST -P$PORT $NAME < /var/bacula/Catalog.sql
elif [ "$USER" != "" ]; then
  /opt/bacula/usr/bin/mysqladmin -h$HOST -P$PORT -f drop $NAME
  /opt/bacula/usr/bin/mysqladmin -h$HOST -P$PORT create $NAME
  /opt/bacula/usr/bin/mysql -h$HOST -P$PORT $NAME < /var/bacula/Catalog.sql
  /usr/bin/sudo /usr/bacula/pcnl_grant_privileges $TYPE $HOST $PORT $USER "" $NAME
  /opt/bacula/usr/bin/mysqladmin -h$HOST -P$PORT reload
else
  /opt/bacula/usr/bin/mysqladmin -h$HOST -P$PORT -f drop $NAME
  /opt/bacula/usr/bin/mysqladmin -h$HOST -P$PORT create $NAME
  /opt/bacula/usr/bin/mysql -h$HOST -P$PORT $NAME < /var/bacula/Catalog.sql
  /usr/bin/sudo /usr/bacula/pcnl_grant_privileges $TYPE $HOST $PORT $USER "$PASS" $NAME
  /opt/bacula/usr/bin/mysqladmin -h$HOST -P$PORT reload
fi

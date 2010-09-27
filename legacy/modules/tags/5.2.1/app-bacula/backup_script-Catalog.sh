#!/bin/sh
TYPE=mysql
HOST=localhost
NAME=bacula
USER=bacula
PASS=
PORT=3307
if ( [ "$USER" == "" ] && [ "$PASS" == "" ] ); then
  /opt/bacula/usr/bin/mysqldump -h$HOST -f --opt $NAME -P$PORT > /var/bacula/Catalog.sql
elif [ "$PASS" == "" ]; then
  /opt/bacula/usr/bin/mysqldump -h$HOST -u$USER -f --opt $NAME -P$PORT > /var/bacula/Catalog.sql
else
  /opt/bacula/usr/bin/mysqldump -h$HOST -u$USER -p$PASS -f --opt $NAME -P$PORT > /var/bacula/Catalog.sql
fi

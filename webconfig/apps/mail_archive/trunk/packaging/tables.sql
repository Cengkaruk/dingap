CREATE TABLE archive (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  sender varchar(255) default NULL,
  recipient varchar(255) default NULL,
  cc varchar(255) default NULL,
  bcc varchar(255) default NULL,
  subject varchar(255) default NULL,
  size INTEGER UNSIGNED default '0',
  sent datetime NOT NULL,
  header varchar(4096) default NULL,
  body mediumtext,
  created datetime NOT NULL,
  PRIMARY KEY (id)
); 
CREATE TABLE attachment (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  archive_id INTEGER UNSIGNED NOT NULL,
  type varchar(128) NOT NULL,
  encoding varchar(128) NOT NULL,
  cid varchar(128) default NULL,
  charset varchar(128) default null,
  disposition varchar(128) default null,
  filename varchar(512) default NULL,
  md5 varchar(32) default NULL,
  size INTEGER UNSIGNED default '0',
  data mediumtext,
  PRIMARY KEY (id),
  KEY aaid (archive_id) 
); 

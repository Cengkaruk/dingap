CREATE TABLE maddr (
  partition_tag integer   DEFAULT 0,
  id         bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email      varbinary(255) NOT NULL,
  domain     varchar(255)   NOT NULL,
  CONSTRAINT part_email UNIQUE (partition_tag,email)
) ENGINE=InnoDB;

CREATE TABLE msgs (
  partition_tag integer    DEFAULT 0,
  mail_id    varchar(12)   NOT NULL PRIMARY KEY,
  secret_id  varchar(12)   DEFAULT '',
  am_id      varchar(20)   NOT NULL,
  time_num   integer unsigned NOT NULL,
  time_iso   char(16)      NOT NULL,
  sid        bigint(20) unsigned NOT NULL,
  policy     varchar(255)  DEFAULT '',
  client_addr varchar(255) DEFAULT '',
  size       integer unsigned NOT NULL,
  content    char(1),
  quar_type  char(1),
  quar_loc   varchar(255)  DEFAULT '',
  dsn_sent   char(1),
  spam_level float,
  message_id varchar(255)  DEFAULT '',
  from_addr  varchar(255)  DEFAULT '',
  subject    varchar(255)  DEFAULT '',
  host       varchar(255)  NOT NULL,
  FOREIGN KEY (sid) REFERENCES maddr(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX msgs_idx_sid      ON msgs (sid);
CREATE INDEX msgs_idx_mess_id  ON msgs (message_id);
CREATE INDEX msgs_idx_time_num ON msgs (time_num);
CREATE INDEX msgs_idx_content ON msgs (content);

CREATE TABLE msgrcpt (
  partition_tag integer    DEFAULT 0,    
  mail_id    varchar(12)   NOT NULL,     
  rid        bigint(20) unsigned NOT NULL,  
  ds         char(1)       NOT NULL,     
  rs         char(1)       NOT NULL,     
  bl         char(1)       DEFAULT ' ',  
  wl         char(1)       DEFAULT ' ',  
  bspam_level float,                     
  smtp_resp  varchar(255)  DEFAULT '',   
  FOREIGN KEY (rid)     REFERENCES maddr(id)     ON DELETE RESTRICT,
  FOREIGN KEY (mail_id) REFERENCES msgs(mail_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX msgrcpt_idx_mail_id  ON msgrcpt (mail_id);
CREATE INDEX msgrcpt_idx_rid      ON msgrcpt (rid);

CREATE TABLE quarantine (
  partition_tag integer    DEFAULT 0,   
  mail_id    varchar(12)   NOT NULL,    
  chunk_ind  integer unsigned NOT NULL, 
  mail_text  blob          NOT NULL,    
  PRIMARY KEY (mail_id,chunk_ind),
  FOREIGN KEY (mail_id) REFERENCES msgs(mail_id) ON DELETE CASCADE
) ENGINE=InnoDB;


use NHibernate;

drop table if exists Users;

create table Users
(
   LogonId                      varchar(20)						not null default '0',
   Name							varchar(40)                     default null,
   Password                     varchar(20)						default null,
   EmailAddress                 varchar(40)						default null,
   LastLogon					datetime						default null,
   primary key (LogonId)
) TYPE=INNODB;

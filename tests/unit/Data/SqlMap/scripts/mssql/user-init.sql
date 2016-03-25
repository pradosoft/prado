-- Creating Table

use [NHibernate]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[Users]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	drop table [dbo].[Users]
END

CREATE TABLE [dbo].[Users] (
  LogonID nvarchar(20) NOT NULL default '0',
  Name nvarchar(40) default NULL,
  Password nvarchar(20) default NULL,
  EmailAddress nvarchar(40) default NULL,
  LastLogon datetime default NULL,
  PRIMARY KEY  (LogonID)
)

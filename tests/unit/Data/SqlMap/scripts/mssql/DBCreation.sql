-- MSQL DATABASE

IF EXISTS (SELECT name FROM master.dbo.sysdatabases WHERE name = N'IBatisNet')
	DROP DATABASE [IBatisNet]
GO

CREATE DATABASE [IBatisNet] 
 COLLATE Latin1_General_CI_AS
GO

exec sp_dboption N'IBatisNet', N'autoclose', N'true'
GO

exec sp_dboption N'IBatisNet', N'bulkcopy', N'false'
GO

exec sp_dboption N'IBatisNet', N'trunc. log', N'true'
GO

exec sp_dboption N'IBatisNet', N'torn page detection', N'true'
GO

exec sp_dboption N'IBatisNet', N'read only', N'false'
GO

exec sp_dboption N'IBatisNet', N'dbo use', N'false'
GO

exec sp_dboption N'IBatisNet', N'single', N'false'
GO

exec sp_dboption N'IBatisNet', N'autoshrink', N'true'
GO

exec sp_dboption N'IBatisNet', N'ANSI null default', N'false'
GO

exec sp_dboption N'IBatisNet', N'recursive triggers', N'false'
GO

exec sp_dboption N'IBatisNet', N'ANSI nulls', N'false'
GO

exec sp_dboption N'IBatisNet', N'concat null yields null', N'false'
GO

exec sp_dboption N'IBatisNet', N'cursor close on commit', N'false'
GO

exec sp_dboption N'IBatisNet', N'default to local cursor', N'false'
GO

exec sp_dboption N'IBatisNet', N'quoted identifier', N'false'
GO

exec sp_dboption N'IBatisNet', N'ANSI warnings', N'false'
GO

exec sp_dboption N'IBatisNet', N'auto create statistics', N'true'
GO

exec sp_dboption N'IBatisNet', N'auto update statistics', N'true'
GO

if( ( (@@microsoftversion / power(2, 24) = 8) and (@@microsoftversion & 0xffff >= 724) ) or ( (@@microsoftversion / power(2, 24) = 7) and (@@microsoftversion & 0xffff >= 1082) ) )
	exec sp_dboption N'IBatisNet', N'db chaining', N'false'
GO

if exists (select * from master.dbo.syslogins where loginname = N'IBatisNet')
	exec sp_droplogin N'IBatisNet'
GO

use [IBatisNet]
GO

if not exists (select * from master.dbo.syslogins where loginname = N'IBatisNet')
BEGIN
	declare @logindb nvarchar(132),  @loginpass nvarchar(132), @loginlang nvarchar(132) 
	select @logindb = N'IBatisNet', @loginpass=N'test', @loginlang = N'us_english'
	exec sp_addlogin N'IBatisNet', @loginpass, @logindb, @loginlang
END
GO

if not exists (select * from dbo.sysusers where name = N'IBatisNet' and uid < 16382)
	EXEC sp_grantdbaccess N'IBatisNet', N'IBatisNet'
GO

exec sp_addrolemember N'db_owner', N'IBatisNet'
GO
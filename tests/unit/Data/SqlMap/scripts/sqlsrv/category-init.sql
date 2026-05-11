-- Creating Table

use [IBatisNet]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[Categories]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
drop table [dbo].[Categories]

CREATE TABLE [dbo].[Categories] (
	[Category_Id] [int] IDENTITY (1, 1) NOT NULL ,
	[Category_Name] [varchar] (32)  NULL,
	[Category_Guid] [uniqueidentifier] NULL  
) ON [PRIMARY]

-- Store procedure

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[ps_InsertCategorie]') and OBJECTPROPERTY(id, N'IsProcedure') = 1)
drop procedure [dbo].[ps_InsertCategorie]

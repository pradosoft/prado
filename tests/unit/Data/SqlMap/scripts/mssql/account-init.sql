-- Creating Table

use [IBatisNet]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[Accounts]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[FK_Orders_Accounts]') and OBJECTPROPERTY(id, N'IsForeignKey') = 1)
	ALTER TABLE [dbo].[Orders] DROP CONSTRAINT FK_Orders_Accounts

	drop table [dbo].[Accounts]
END

CREATE TABLE [dbo].[Accounts] (
	[Account_ID] [int] NOT NULL ,
	[Account_FirstName] [varchar] (32)  NOT NULL ,
	[Account_LastName] [varchar] (32)  NOT NULL ,
	[Account_Email] [varchar] (128)  NULL,
	[Account_Banner_Option]  [varchar] (255),
	[Account_Cart_Option] [int]
) ON [PRIMARY]

ALTER TABLE [dbo].[Accounts] WITH NOCHECK ADD 
	CONSTRAINT [PK_Account] PRIMARY KEY  CLUSTERED 
	(
		[Account_ID]
	)  ON [PRIMARY] 

-- Creating Test Data

INSERT INTO [dbo].[Accounts] VALUES(1,'Joe', 'Dalton', 'Joe.Dalton@somewhere.com', 'Oui', 200);
INSERT INTO [dbo].[Accounts] VALUES(2,'Averel', 'Dalton', 'Averel.Dalton@somewhere.com', 'Oui', 200);
INSERT INTO [dbo].[Accounts] VALUES(3,'William', 'Dalton', null, 'Non', 100);
INSERT INTO [dbo].[Accounts] VALUES(4,'Jack', 'Dalton', 'Jack.Dalton@somewhere.com', 'Non', 100);
INSERT INTO [dbo].[Accounts] VALUES(5,'Gilles', 'Bayon', null, 'Oui', 100);

-- Store procedure

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[ps_InsertAccount]') and OBJECTPROPERTY(id, N'IsProcedure') = 1)
drop procedure [dbo].[ps_InsertAccount]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[ps_SelectAccount]') and OBJECTPROPERTY(id, N'IsProcedure') = 1)
drop procedure [dbo].[ps_SelectAccount]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[ps_swap_email_address]') and OBJECTPROPERTY(id, N'IsProcedure') = 1)
drop procedure [dbo].[ps_swap_email_address]



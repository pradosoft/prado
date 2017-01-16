-- Creating Table

use [IBatisNet]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[Documents]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[FK_LineItems_Orders]') and OBJECTPROPERTY(id, N'IsForeignKey') = 1)
	ALTER TABLE [dbo].[LineItems] DROP CONSTRAINT FK_LineItems_Orders

	drop table [dbo].[Documents]
END

CREATE TABLE [dbo].[Documents] (
	[Document_ID] [int] NOT NULL ,
	[Document_Title] [varchar] (32) NULL ,
	[Document_Type] [varchar] (32)  NULL ,
	[Document_PageNumber] [int] NULL  ,
	[Document_City] [varchar] (32)  NULL
) ON [PRIMARY]

ALTER TABLE [dbo].[Documents] WITH NOCHECK ADD 
	CONSTRAINT [PK_Documents] PRIMARY KEY  CLUSTERED 
	(
		[Document_ID]
	)  ON [PRIMARY] 

-- Creating Test Data 

INSERT INTO [dbo].[Documents] VALUES (1, 'The World of Null-A', 'Book', 55, null);
INSERT INTO [dbo].[Documents] VALUES (2, 'Le Progres de Lyon', 'Newspaper', null , 'Lyon');
INSERT INTO [dbo].[Documents] VALUES (3, 'Lord of the Rings', 'Book', 3587, null);
INSERT INTO [dbo].[Documents] VALUES (4, 'Le Canard enchaine', 'Tabloid', null , 'Paris');
INSERT INTO [dbo].[Documents] VALUES (5, 'Le Monde', 'Broadsheet', null , 'Paris');
INSERT INTO [dbo].[Documents] VALUES (6, 'Foundation', 'Monograph', 557, null);

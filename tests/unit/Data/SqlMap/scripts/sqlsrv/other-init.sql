-- Creating Table

use [IBatisNet]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[Others]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	drop table [dbo].[Others]
END

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[A]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	drop table [dbo].[A]
END
if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[B]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	drop table [dbo].[B]
END
if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[C]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	drop table [dbo].[C]
END
if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[D]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	drop table [dbo].[D]
END
if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[E]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	drop table [dbo].[E]
END
if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[F]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	drop table [dbo].[F]
END


CREATE TABLE [dbo].[Others] (
	[Other_Int] [int]  NULL ,
	[Other_Long] [BigInt] NULL,
	[Other_Bit] [Bit] NOT NULL DEFAULT (0), 
	[Other_String] [varchar] (32) NOT NULL
) ON [PRIMARY]

CREATE TABLE [dbo].[F] (
	[ID] [varchar] (50) NOT NULL ,
	[F_Libelle] [varchar] (50) NULL ,
	CONSTRAINT [PK_F] PRIMARY KEY  CLUSTERED 
	(
		[ID]
	)  ON [PRIMARY] 
) ON [PRIMARY]

CREATE TABLE [dbo].[E] (
	[ID] [varchar] (50) NOT NULL ,
	[E_Libelle] [varchar] (50) NULL ,
	CONSTRAINT [PK_E] PRIMARY KEY  CLUSTERED 
	(
		[ID]
	)  ON [PRIMARY] 
) ON [PRIMARY]

CREATE TABLE [dbo].[D] (
	[ID] [varchar] (50) NOT NULL ,
	[D_Libelle] [varchar] (50) NULL ,
	CONSTRAINT [PK_D] PRIMARY KEY  CLUSTERED 
	(
		[ID]
	)  ON [PRIMARY] 
) ON [PRIMARY]

CREATE TABLE [dbo].[C] (
	[ID] [varchar] (50) NOT NULL ,
	[C_Libelle] [varchar] (50) NULL ,
	CONSTRAINT [PK_C] PRIMARY KEY  CLUSTERED 
	(
		[ID]
	)  ON [PRIMARY] 
) ON [PRIMARY]


CREATE TABLE [dbo].[B] (
	[ID] [varchar] (50) NOT NULL ,
	[C_ID] [varchar] (50) NULL ,
	[D_ID] [varchar] (50) NULL ,
	[B_Libelle] [varchar] (50) NULL ,
	CONSTRAINT [PK_B] PRIMARY KEY  CLUSTERED 
	(
		[ID]
	)  ON [PRIMARY] ,
	CONSTRAINT [FK_B_C] FOREIGN KEY 
	(
		[C_ID]
	) REFERENCES [C] (
		[ID]
	),
	CONSTRAINT [FK_B_D] FOREIGN KEY 
	(
		[D_ID]
	) REFERENCES [D] (
		[ID]
	)
) ON [PRIMARY]


CREATE TABLE [dbo].[A] (
	[Id] [varchar] (50)  NOT NULL ,
	[B_ID] [varchar] (50)  NULL ,
	[E_ID] [varchar] (50)  NULL ,
	[F_ID] [varchar] (50)  NULL ,
	[A_Libelle] [varchar] (50)  NULL
	CONSTRAINT [PK_A] PRIMARY KEY  CLUSTERED 
	(
		[Id]
	)  ON [PRIMARY] ,
	CONSTRAINT [FK_A_B] FOREIGN KEY 
	(
		[B_ID]
	) REFERENCES [B] (
		[ID]
	),
	CONSTRAINT [FK_A_E] FOREIGN KEY 
	(
		[E_ID]
	) REFERENCES [E] (
		[ID]
	),
	CONSTRAINT [FK_A_F] FOREIGN KEY 
	(
		[F_ID]
	) REFERENCES [F] (
		[ID]
	)
) ON [PRIMARY]


-- Creating Test Data

INSERT INTO [dbo].[Others] VALUES(1, 8888888, 0, 'Oui');
INSERT INTO [dbo].[Others] VALUES(2, 9999999999, 1, 'Non');

INSERT INTO [dbo].[F] VALUES('f', 'fff');
INSERT INTO [dbo].[E] VALUES('e', 'eee');
INSERT INTO [dbo].[D] VALUES('d', 'ddd');
INSERT INTO [dbo].[C] VALUES('c', 'ccc');
INSERT INTO [dbo].[B] VALUES('b', 'c', null, 'bbb');
INSERT INTO [dbo].[A] VALUES('a', 'b', 'e', null, 'aaa');
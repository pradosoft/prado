-- MSSQL test database schema for prado unit tests
-- Run as sa or a user with dbcreator:
--   sqlcmd -S localhost\SQLEXPRESS -U sa -P yourpassword -i tests/initdb_mssql.sql
--
-- Or via SSMS using prado_unitest as the active database.

IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = N'prado_unitest')
	CREATE DATABASE prado_unitest;
GO

USE prado_unitest;
GO

IF OBJECT_ID('dbo.address',  'U') IS NOT NULL DROP TABLE dbo.address;
IF OBJECT_ID('dbo.table1',   'U') IS NOT NULL DROP TABLE dbo.table1;
GO

CREATE TABLE dbo.table1 (
	id          INT            NOT NULL IDENTITY(1,1),
	name        NVARCHAR(45)   NOT NULL,
	field1_tiny TINYINT        NOT NULL DEFAULT 0,
	field2_text NVARCHAR(MAX)  NULL,
	field3_date DATE           NULL,
	field4_float FLOAT         NOT NULL DEFAULT 10,
	field5_dec  DECIMAL(10,4)  NOT NULL DEFAULT 0,
	field6_int  INT            NOT NULL DEFAULT 0,
	field7_dt   DATETIME       NULL,
	field8_big  BIGINT         NOT NULL DEFAULT 0,
	field9_char CHAR(10)       NULL,
	field10_bit BIT            NOT NULL DEFAULT 0,
	field11_num NUMERIC(8,2)   NOT NULL DEFAULT 0,
	CONSTRAINT pk_table1 PRIMARY KEY (id)
);
GO

CREATE TABLE dbo.address (
	username    NVARCHAR(128)  NOT NULL,
	phone       NVARCHAR(40)   NOT NULL DEFAULT '',
	field1_bool BIT            NOT NULL DEFAULT 0,
	field2_date DATE           NOT NULL,
	field3_dbl  FLOAT          NOT NULL DEFAULT 0,
	field4_int  INT            NOT NULL DEFAULT 0 REFERENCES dbo.table1(id),
	field5_text NVARCHAR(MAX)  NULL,
	field6_time TIME           NOT NULL DEFAULT '00:00:00',
	field7_dt   DATETIME       NOT NULL DEFAULT GETDATE(),
	field8_dec  DECIMAL(19,4)  NOT NULL DEFAULT 0,
	field9_num  NUMERIC(10,4)  NOT NULL DEFAULT 0,
	int_fk1     INT            NOT NULL DEFAULT 0,
	int_fk2     INT            NOT NULL DEFAULT 0,
	CONSTRAINT pk_address PRIMARY KEY (username)
);
GO

INSERT INTO dbo.table1 (name) VALUES ('test');
INSERT INTO dbo.address (username, phone, field2_date) VALUES ('wei', '1111111', '2024-01-01');
GO

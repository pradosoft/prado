-- Technique for creating large sample test data from
-- http://www.sql-server-performance.com/jc_large_data_operations.asp

use [IBatisNet]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[ManyRecords]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
drop table [dbo].[ManyRecords]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[ManyRecordsTest]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
drop table [dbo].[ManyRecordsTest]



-- Create Data Storage Table
CREATE TABLE [dbo].[ManyRecords] (
	[Many_FirstID] [int] NOT NULL,
	[Many_SecondID] [int] NOT NULL,
	[Many_ThirdID] [int] NOT NULL,
	[Many_FourthID] [int] NOT NULL,
	[Many_FifthID] [int] NOT NULL,
	[Many_SequenceID] [int] NOT NULL,
	[Many_DistributedID] [int] NOT NULL,
	[Many_SampleCharValue] [char] (10) NOT NULL,
	[Many_SampleDecimal] [decimal] (9,4) NOT NULL,
	[Many_SampleMoney] [money] NOT NULL,
	[Many_SampleDate] [datetime] NOT NULL,
	[Many_SequenceDate] [datetime] NOT NULL )
ON [PRIMARY]             



-- Create Sample Data of 1 million records (increase if needed)
BEGIN TRANSACTION
	DECLARE @intIndex int, @rowCount int, @seqCount int, @distValue int
	SELECT @intIndex = 1, @rowCount = 1000000, @seqCount = 10000
	SELECT @distValue = @rowCount/10000

	WHILE @intIndex <= @rowCount
	BEGIN
	INSERT INTO [dbo].[ManyRecords] (
		[Many_FirstID], 
		[Many_SecondID], 
		[Many_ThirdID], 
		[Many_FourthID],  
		[Many_FifthID],  
		[Many_SequenceID],  
		[Many_DistributedID], 
		[Many_SampleCharValue],  
		[Many_SampleDecimal], 
		[Many_SampleMoney], 
		[Many_SampleDate], 
		[Many_SequenceDate] )
	VALUES ( 
		@intIndex, -- First
		@intIndex/2, -- Second
		@intIndex/4, -- Third
		@intIndex/10, -- Fourth
		@intIndex/20, -- Fifth
		(@intIndex-1)/@seqCount + 1, -- Sequential value
		(@intIndex-1)%(@distValue) + 1,  -- Distributed value
		CHAR(65 + 26*rand())+CHAR(65 + 26*rand())+CHAR(65 + 26*rand())+CONVERT(char(6),CONVERT(int,100000*(9.0*rand()+1.0)))+CHAR(65 + 26*rand()), -- Char Value
		10000*rand(), -- Decimal value
		10000*rand(), -- Money value
		DATEADD(hour,100000*rand(),'1990-01-01'), -- Date value
		DATEADD(hour,@intIndex/5,'1990-01-01') ) -- Sequential date value

	SET @intIndex = @intIndex + 1
	END
COMMIT TRANSACTION



-- Create Test table using storage table sample data
SELECT 
	[Many_FirstID], 
	[Many_SecondID], 
	[Many_ThirdID], 
	[Many_FourthID],  
	[Many_FifthID],  
	[Many_SequenceID],  
	[Many_DistributedID], 
	[Many_SampleCharValue],  
	[Many_SampleDecimal], 
	[Many_SampleMoney], 
	[Many_SampleDate], 
	[Many_SequenceDate]
INTO [dbo].[ManyRecordsTest]
FROM [dbo].[ManyRecords]



-- Create Test table indexes
CREATE INDEX [IDX_ManyRecordsTest_Seq] ON [dbo].[ManyRecordsTest] ([Many_SequenceID])  WITH SORT_IN_TEMPDB
CREATE INDEX [IDX_ManyRecordsTest_Dist] ON [dbo].[ManyRecordsTest] ([Many_DistributedID]) WITH SORT_IN_TEMPDB
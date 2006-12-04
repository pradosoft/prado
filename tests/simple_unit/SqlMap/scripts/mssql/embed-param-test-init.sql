-- Technique for creating large sample test data from
-- http://www.sql-server-performance.com/jc_large_data_operations.asp

use [IBatisNet]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[ManyRecordsTest]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
drop table [dbo].[ManyRecordsTest]



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
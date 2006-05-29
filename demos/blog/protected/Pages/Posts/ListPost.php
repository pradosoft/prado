<?php

class ListPost extends BlogPage
{
	const DEFAULT_LIMIT=10;

	public function getPosts()
	{
		$timeFilter='';
		$catFilter='';
		if(($time=TPropertyValue::ensureInteger($this->Request['time']))>0)
		{
			$year=(integer)($time/100);
			$month=$time%100;
			$startTime=mktime(0,0,0,$month,1,$year);
			if(++$month>12)
			{
				$month=1;
				$year++;
			}
			$endTime=mktime(0,0,0,$month,1,$year);
			$timeFilter="create_time>=$startTime AND create_time<$endTime";
		}
		if(($catID=$this->Request['cat'])!==null)
		{
			$catID=TPropertyValue::ensureInteger($catID);
			$catFilter="category_id=$catID";
		}
		if(($offset=TPropertyValue::ensureInteger($this->Request['offset']))<=0)
			$offset=0;
		if(($limit=TPropertyValue::ensureInteger($this->Request['limit']))<=0)
			$limit=self::DEFAULT_LIMIT;
		return $this->DataAccess->queryPosts('',$timeFilter,$catFilter,'ORDER BY create_time DESC',"LIMIT $offset,$limit");
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->PostList->DataSource=$this->getPosts();
		$this->PostList->dataBind();
	}
}

?>
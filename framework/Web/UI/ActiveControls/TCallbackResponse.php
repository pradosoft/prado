<?php
/*
 * Created on 29/04/2006
 */

// See TActivePageAdapter::renderResponse()
//TODO: How to render the response, it will contain 3 pieces of data
// 1) The arbituary data returned to the client-side callback handler
// 2) client-side function call statements
// 3) Content body, which may need to be partitioned

class TCallbackResponse extends THttpResponse
{
	private $_writers=array();
	
	public function createHtmlWriter($type=null)
	{
		$writer = new TCallbackResponseWriter();
		$this->_writers[] = $writer;
		if($type===null)
			$type=$this->getHtmlWriterType();
		return Prado::createComponent($type,$writer);
	}
	
	public function flush()
	{
		foreach($this->_writers as $writer)
			echo $writer->flush();
		parent::flush();
	}
}

class TCallbackResponseWriter extends TTextWriter
{
	private $_boundary;
	
	public function __construct()
	{
		$this->_boundary = sprintf('%x',crc32((string)$this));
	}
	
	public function getBoundary()
	{
		return $this->_boundary;
	}
	
	public function setBoundary($value)
	{
		$this->_boundary = $value;
	}
	
	public function flush()
	{
		$content = '<!--'.$this->getBoundary().'-->';
		$content .= parent::flush();
		$content .= '<!--//'.$this->getBoundary().'-->';
		return $content;
	}
}
?>

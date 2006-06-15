<?php

/**
 * 
 */
class TCallbackResponseAdapter extends THttpResponseAdapter
{
	private $_writers=array();
	
	private $_data;
	
	public function createNewHtmlWriter($type,$response)
	{
		$writer = new TCallbackResponseWriter();
		$this->_writers[] = $writer;
		return parent::createNewHtmlWriter($type,$writer);
	}
	
	public function flushContent()
	{
		foreach($this->_writers as $writer)
			echo $writer->flush();
		parent::flushContent();
	}
	
	public function setResponseData($data)
	{
		$this->_data = $data;
	}
	
	public function getResponseData()
	{
		return $this->_data;
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
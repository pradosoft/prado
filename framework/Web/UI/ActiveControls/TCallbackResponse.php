<?php

/**
 * 
 */
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
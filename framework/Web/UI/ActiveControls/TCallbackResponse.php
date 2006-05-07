<?php

/**
 * 
 */
class TCallbackResponse extends THttpResponse
{
	private $_writers=array();
	
	public function createHtmlWriter($type=null,$parameter=null)
	{
		$writer = new TCallbackResponseWriter($parameter);
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
	private $_response;
	
	public function __construct($response)
	{
		$this->_response = $response;
		$this->_boundary = sprintf('%x',crc32((string)$this));
	}
	
	public function getResponse()
	{
		return $this->_response;
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
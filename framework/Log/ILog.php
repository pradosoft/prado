<?php

interface ILog
{
	public function info($msg, $source='Prado', $category='core');
	public function debug($msg, $source='Prado', $category='core');
	public function notice($msg, $source='Prado', $category='core');
	public function warn($msg, $source='Prado', $category='core');
	public function error($msg, $source='Prado', $category='core');
	public function fatal($msg, $source='Prado', $category='core');
}

require_once(PRADO_DIR.'/Log/EventLog/log.php');
require_once(PRADO_DIR.'/Log/EventLog/exceptions/writer_exception.php');
require_once(PRADO_DIR.'/Log/EventLog/exceptions/file_exception.php');

/**
 * ${classname}
 *
 * ${description}
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package ${package}
 */
class TInternalLogger
{
	public $entries = array();

	public function info($msg, $source='Prado', $category='core')
	{
		$this->log($msg, ezcLog::INFO, 
			array('source'=>$source, 'category'=>$category));
	}
	public function debug($msg, $source='Prado', $category='core')
	{
		$this->log($msg, ezcLog::DEBUG, 
			array('source'=>$source, 'category'=>$category));	
	}
	public function notice($msg, $source='Prado', $category='core')
	{
		$this->log($msg, ezcLog::NOTICE, 
			array('source'=>$source, 'category'=>$category));
	}

	public function warn($msg, $source='Prado', $category='core')
	{
		$this->log($msg, ezcLog::WARNING, 
			array('source'=>$source, 'category'=>$category));
	}

	public function error($msg, $source='Prado', $category='core')
	{
		$this->log($msg, ezcLog::ERROR, 
			array('source'=>$source, 'category'=>$category));	
	}

	public function fatal($msg, $source='Prado', $category='core')
	{
		$this->log($msg, ezcLog::FATAL, 
			array('source'=>$source, 'category'=>$category));
	}

	protected function log($msg, $type, $info)
	{
		if($info['category']=='core')
		{
			$trace = debug_backtrace();
			$info['category'] = $trace[3]['class'];
		}
		$info['time'] = microtime(true);
		$this->entries[] = array($msg, $type, $info);
	}
}

?>
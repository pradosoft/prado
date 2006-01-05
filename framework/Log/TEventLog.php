<?php

Prado::using('System.Log.ILog');
Prado::using('System.Log.TLogManager');

/**
 * ${classname}
 *
 * ${description}
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package ${package}
 */
class TEventLog extends TLogManager implements ILog
{
	public function init($config)
	{
		parent::init($config);
		$this->collectInternalLog();
	}

	public function __destruct()
	{
		$this->collectInternalLog();
	}

	protected function collectInternalLog()
	{
		foreach(Prado::coreLog()->entries as $entry)
			$this->log($entry[0], $entry[1], $entry[2]);
		Prado::coreLog()->entries = array();
	}

	public function info($msg, $source='Prado', $category='main')
	{
		$this->log($msg, ezcLog::INFO, 
				array('source'=>$source, 'category'=>$category));
	}

	public function debug($msg, $source='Prado', $category='main')
	{
		$this->log($msg, ezcLog::DEBUG, 
				array('source'=>$source, 'category'=>$category));
	}

	public function notice($msg, $source='Prado', $category='main')
	{
		$this->log($msg, ezcLog::NOTICE, 
				array('source'=>$source, 'category'=>$category));
	}

	public function warn($msg, $source='Prado', $category='main')
	{
		$this->log($msg, ezcLog::WARNING, 
				array('source'=>$source, 'category'=>$category));
	}

	public function error($msg, $source='Prado', $category='main')
	{
		$this->log($msg, ezcLog::NOTICE, 
				array('source'=>$source, 'category'=>$category));
	
	}

	public function fatal($msg, $source='Prado', $category='main')
	{
		$this->log($msg, ezcLog::NOTICE, 
				array('source'=>$source, 'category'=>$category));
	}

	protected function log($msg, $code, $info)
	{
		ezcLog::getInstance()->log($msg, $code, $info);
	}
}

?>
<?php
/**
 * TLogger class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2011 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Util
 */

/**
 * TLogger class.
 *
 * TLogger records log messages in memory and implements the methods to
 * retrieve the messages with filter conditions, including log levels,
 * log categories, and by control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Util
 * @since 3.0
 */
class TLogger extends TComponent
{
	/**
	 * Log levels.
	 */
	const DEBUG=0x01;
	const INFO=0x02;
	const NOTICE=0x04;
	const WARNING=0x08;
	const ERROR=0x10;
	const ALERT=0x20;
	const FATAL=0x40;
	/**
	 * @var array log messages
	 */
	private $_logs=array();
	/**
	 * @var integer log levels (bits) to be filtered
	 */
	private $_levels;
	/**
	 * @var array list of categories to be filtered
	 */
	private $_categories;
	/**
	 * @var array list of control client ids to be filtered
	 */
	private $_controls;

	/**
	 * Logs a message.
	 * Messages logged by this method may be retrieved via {@link getLogs}.
	 * @param string message to be logged
	 * @param integer level of the message. Valid values include
	 * TLogger::DEBUG, TLogger::INFO, TLogger::NOTICE, TLogger::WARNING,
	 * TLogger::ERROR, TLogger::ALERT, TLogger::FATAL.
	 * @param string category of the message
	 * @param string|TControl control of the message
	 */
	public function log($message,$level,$category='Uncategorized', $ctl=null)
	{
		if($ctl) {
			if($ctl instanceof TControl)
				$ctl = $ctl->ClientId;
			else if(!is_string($ctl))
				$ctl = null;
		} else
			$ctl = null;
		$this->_logs[]=array($message,$level,$category,microtime(true),memory_get_usage(),$ctl);
	}

	/**
	 * Retrieves log messages.
	 * Messages may be filtered by log levels and/or categories and/or control client ids.
	 * A level filter is specified by an integer, whose bits indicate the levels interested.
	 * For example, (TLogger::INFO | TLogger::WARNING) specifies INFO and WARNING levels.
	 * A category filter is specified by an array of categories to filter. 
	 * A message whose category name starts with any filtering category
	 * will be returned. For example, a category filter array('System.Web','System.IO')
	 * will return messages under categories such as 'System.Web', 'System.IO',
	 * 'System.Web.UI', 'System.Web.UI.WebControls', etc.
	 * A control client id filter is specified by an array of control client id
	 * A message whose control client id starts with any filtering naming panels
	 * will be returned. For example, a category filter array('ctl0_body_header', 
	 * 'ctl0_body_content_sidebar')
	 * will return messages under categories such as 'ctl0_body_header', 'ctl0_body_content_sidebar',
	 * 'ctl0_body_header_title', 'ctl0_body_content_sidebar_savebutton', etc.
	 * Level filter, category filter, and control filter are combinational, i.e., only messages
	 * satisfying all filter conditions will they be returned.
	 * @param integer level filter
	 * @param array category filter
	 * @param array control filter
	 * @return array list of messages. Each array elements represents one message
	 * with the following structure:
	 * array(
	 *   [0] => message
	 *   [1] => level
	 *   [2] => category
	 *   [3] => timestamp (by microtime(), float number));
	 *   [4] => memory in bytes
	 *   [5] => control client id
	 */
	public function getLogs($levels=null,$categories=null,$controls=null)
	{
		$this->_levels=$levels;
		$this->_categories=$categories;
		$this->_controls=$controls;
		if(empty($levels) && empty($categories) && empty($controls))
			return $this->_logs;
		$logs = $this->_logs;
		if(!empty($levels))
			$logs = array_values(array_filter( array_filter($logs,array($this,'filterByLevels')) ));
		if(!empty($categories))
			$logs = array_values(array_filter( array_filter($logs,array($this,'filterByCategories')) ));
		if(!empty($controls))
			$logs = array_values(array_filter( array_filter($logs,array($this,'filterByControl')) ));
		return $logs;
	}

	/**
	 * Filter function used by {@link getLogs}.
	 * @param array element to be filtered
	 */
	private function filterByCategories($value)
	{
		foreach($this->_categories as $category)
		{
			// element 2 is the category
			if($value[2]===$category || strpos($value[2],$category.'.')===0)
				return $value;
		}
		return false;
	}

	/**
	 * Filter function used by {@link getLogs}
	 * @param array element to be filtered
	 */
	private function filterByLevels($value)
	{
		// element 1 are the levels
		if($value[1] & $this->_levels)
			return $value;
		else
			return false;
	}

	/**
	 * Filter function used by {@link getLogs}
	 * @param array element to be filtered
	 */
	private function filterByControl($ctl)
	{
		// element 5 are the control client ids
		foreach($this->_controls as $control)
		{
			if($value[5]===$control || strpos($value[5],$control)===0)
				return $value;
		}
		return false;
	}
}


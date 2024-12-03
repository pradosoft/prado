<?php

/**
 * TModule class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * TModule class.
 *
 * TModule implements the basic methods required by IModule and may be
 * used as the basic class for application modules.
 *
 * void dyPreInit($config) is raised after loading the module but before
 * init.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 * @method void dyPreInit(mixed $config)
 * @method void dyInit(mixed $config)
 */
abstract class TModule extends \Prado\TApplicationComponent implements IModule
{
	/**
	 * @var string module id
	 */
	private $_id;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * This raises dyInit($config) for behaviors.
	 * @param \Prado\Xml\TXmlElement $config module configuration
	 */
	public function init($config)
	{
		$this->dyInit($config);
	}

	/**
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string $value id of this module
	 */
	public function setID($value)
	{
		$this->_id = $value;
	}
}

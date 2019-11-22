<?php
/**
 * TActiveRecordManager class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord
 */

namespace Prado\Data\ActiveRecord;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TActiveRecordManager provides the default DB connection,
 * default active record gateway, and table meta data inspector.
 *
 * The default connection can be set as follows:
 * <code>
 * TActiveRecordManager::getInstance()->setDbConnection($conn);
 * </code>
 * All new active record created after setting the
 * {@link DbConnection setDbConnection()} will use that connection unless
 * the custom ActiveRecord class overrides the ActiveRecord::getDbConnection().
 *
 * Set the {@link setCache Cache} property to an ICache object to allow
 * the active record gateway to cache the table meta data information.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\ActiveRecord
 * @since 3.1
 */
class TActiveRecordManager extends \Prado\TComponent
{
	const DEFAULT_GATEWAY_CLASS = '\Prado\Data\ActiveRecord\TActiveRecordGateway';

	/**
	 * Defaults to {@link TActiveRecordManager::DEFAULT_GATEWAY_CLASS DEFAULT_GATEWAY_CLASS}
	 * @var string
	 */
	private $_gatewayClass = self::DEFAULT_GATEWAY_CLASS;

	private $_gateway;
	private $_meta = [];
	private $_connection;

	private $_cache;

	/**
	 * Defaults to '{@link TActiveRecordInvalidFinderResult::Null Null}'
	 *
	 * @var TActiveRecordInvalidFinderResult
	 * @since 3.1.5
	 */
	private $_invalidFinderResult = TActiveRecordInvalidFinderResult::Null;

	/**
	 * @return ICache application cache.
	 */
	public function getCache()
	{
		return $this->_cache;
	}

	/**
	 * @param ICache $value application cache
	 */
	public function setCache($value)
	{
		$this->_cache = $value;
	}

	/**
	 * @param TDbConnection $conn default database connection
	 */
	public function setDbConnection($conn)
	{
		$this->_connection = $conn;
	}

	/**
	 * @return TDbConnection default database connection
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * @param null|mixed $self
	 * @return TActiveRecordManager static instance of record manager.
	 */
	public static function getInstance($self = null)
	{
		static $instance;
		if ($self !== null) {
			$instance = $self;
		} elseif ($instance === null) {
			$instance = new self;
		}
		return $instance;
	}

	/**
	 * @return TActiveRecordGateway record gateway.
	 */
	public function getRecordGateway()
	{
		if ($this->_gateway === null) {
			$this->_gateway = $this->createRecordGateway();
		}
		return $this->_gateway;
	}

	/**
	 * @return TActiveRecordGateway default record gateway.
	 */
	protected function createRecordGateway()
	{
		return Prado::createComponent($this->getGatewayClass(), $this);
	}

	/**
	 * Set implementation class of ActiveRecordGateway
	 * @param string $value
	 */
	public function setGatewayClass($value)
	{
		$this->_gateway = null;
		$this->_gatewayClass = (string) $value;
	}

	/**
	 * @return string the implementation class of ActiveRecordGateway. Defaults to {@link TActiveRecordManager::DEFAULT_GATEWAY_CLASS DEFAULT_GATEWAY_CLASS}
	 */
	public function getGatewayClass()
	{
		return $this->_gatewayClass;
	}

	/**
	 * @return TActiveRecordInvalidFinderResult Defaults to '{@link TActiveRecordInvalidFinderResult::Null Null}'.
	 * @since 3.1.5
	 * @see setInvalidFinderResult
	 */
	public function getInvalidFinderResult()
	{
		return $this->_invalidFinderResult;
	}

	/**
	 * Define the way an active record finder react if an invalid magic-finder invoked
	 * @param TActiveRecordInvalidFinderResult $value * @since 3.1.5
	 * @see getInvalidFinderResult
	 */
	public function setInvalidFinderResult($value)
	{
		$this->_invalidFinderResult = TPropertyValue::ensureEnum($value, 'Prado\\Data\\ActiveRecord\\TActiveRecordInvalidFinderResult');
	}
}

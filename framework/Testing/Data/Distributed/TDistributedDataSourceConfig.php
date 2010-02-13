<?php
/**
 * TDistributedDataSourceConfig class file.
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Testing.Data.Distributed
 */

	Prado::using('System.Data.TDataSourceConfig');
	Prado::using('System.Testing.Data.Distributed.TDistributedDbConnection');

	/**
	 * IDistributedDataSourceConfig module interface provides <module> configuration for database connections.
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed
	 * @since 4.0
	 */
	interface IDistributedDataSourceConfig /*extends IDataSourceConfig*/ {
		/**
		 * @return string Database connection class name to be created for child connection.
		 */
		public function getDistributedConnectionClass();

		/**
		 * @param string Database connection class name to be created for child connection.
		 */
		public function setDistributedConnectionClass($value);
	}

	/**
	 * TDistributedDataSourceConfig module class provides <module> configuration for database connections.
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed
	 * @since 4.0
	 */
	class TDistributedDataSourceConfig extends TDataSourceConfig implements IDistributedDataSourceConfig
	{
		/**
		 * @var array
		 */
		private $_childConnectionAttributes = array();

		/**
		 * @var string
		 */
		private $_connDistributedClass = 'System.Data.TDbConnection';

		/**
		 * @var IDistributedDbConnection
		 */
		private $_connDistributed = null;

		/**
		 * @var boolean
		 */
		protected $bInitialized = false;

		/**
		 * @var boolean
		 */
		private $_hasDistributedConnectionData = false;

		/**
		 * Initalize the database connection properties from attributes in <database> tag.
		 * @param TXmlDocument xml configuration.
		 */
		public function init($xml)
		{
			parent::init($xml);
			$this->initChildConnectionData($xml);
			$this->bInitialized = true;
		}

		/**
		 * Initalize the database connection properties from attributes in $tagName tag.
		 * @param TXmlDocument xml configuration.
		 * @param string Tagnames to parse. Defaults to 'child'
		 */
		protected function initChildConnectionData($xml, $tagName='child')
		{
			$c = 0;
			foreach($xml->getElementsByTagName($tagName) as $item)
			{
				++$c;
				$this->_childConnectionAttributes[] = $item->getAttributes();
			}

			if($c===0)
				throw new TConfigurationException('distributeddatasource_child_required', get_class($this), $tagName);
		}

		/**
		 * @return string Database connection class name to be created for child connection.
		 */
		public function getDistributedConnectionClass()
		{
			return $this->_connDistributedClass;
		}

		/**
		 * @param string Database connection class name to be created for child connection.
		 */
		public function setDistributedConnectionClass($value)
		{
			$this->_connDistributedClass=$value;
		}

		/**
		 * @return IDistributedDbConnection
		 */
		public function getDistributedDbConnection()
		{
			$this->_hasDistributedConnectionData = false;
			if($this->_connDistributed===null)
				$this->_connDistributed = Prado::createComponent($this->getDistributedConnectionClass());

			if($this->_hasDistributedConnectionData)
				return $this->_connDistributed;

			$attribs = $this->getDistributedDbConnectionAttributes();

			if($attribs===null)
				return $this->_connDistributed;

			foreach($attribs as $name => $value)
				$this->_connDistributed->setSubproperty($name, $value);

			$this->_hasDistributedConnectionData = true;

			return $this->_connDistributed;
		}

		/**
		 * @return TMap
		 */
		protected function getDistributedDbConnectionAttributes()
		{
			$index = 0;
			$c = count($this->_childConnectionAttributes);

			if($c > 1) {
				$aSrc = array();
				$aTmp = array();

				foreach($this->_childConnectionAttributes as $k => $item)
				{
					$weight = 1;
					if( isset($item['Weight']) )
						$weight = $item['Weight'];
					$aSrc[$k] = $weight;
				}

				asort($aSrc);

				foreach($aSrc as $idx => $weight)
					$aTmp = array_merge($aTmp, array_pad(array(), $weight*5, $idx));

				$min	= 0;
				$max	= count($aTmp)-1;
				$factor = array_sum($aSrc) / count($aSrc);
				$wrand	= round($min + (pow(rand(0, $max) / $max, $factor) * ($max - $min)));
				$index	= $aTmp[$wrand];
			}

			$result = $this->_childConnectionAttributes[$index];

			if( isset($result['Weight']) )
				unset($result['Weight']);

			return $result;
		}
	}
?>
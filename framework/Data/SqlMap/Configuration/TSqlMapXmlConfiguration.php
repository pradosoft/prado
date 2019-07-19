<?php
/**
 * TSqlMapXmlConfigBuilder, TSqlMapXmlConfiguration, TSqlMapXmlMappingConfiguration classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

use Prado\Data\SqlMap\DataMapper\TSqlMapConfigurationException;

/**
 * TSqlMapXmlConfig class.
 *
 * Configures the TSqlMapManager using xml configuration file.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TSqlMapXmlConfiguration extends TSqlMapXmlConfigBuilder
{
	/**
	 * @var TSqlMapManager manager
	 */
	private $_manager;
	/**
	 * @var string configuration file.
	 */
	private $_configFile;
	/**
	 * @var array global properties.
	 */
	private $_properties = [];

	/**
	 * @param TSqlMapManager $manager manager instance.
	 */
	public function __construct($manager)
	{
		$this->_manager = $manager;
	}

	public function getManager()
	{
		return $this->_manager;
	}

	protected function getConfigFile()
	{
		return $this->_configFile;
	}

	/**
	 * Configure the TSqlMapManager using the given xml file.
	 * @param string $filename SqlMap configuration xml file.
	 */
	public function configure($filename = null)
	{
		$this->_configFile = $filename;
		$document = $this->loadXmlDocument($filename, $this);

		foreach ($document->xpath('//property') as $property) {
			$this->loadGlobalProperty($property);
		}

		foreach ($document->xpath('//typeHandler') as $handler) {
			$this->loadTypeHandler($handler);
		}

		foreach ($document->xpath('//connection[last()]') as $conn) {
			$this->loadDatabaseConnection($conn);
		}

		//try to load configuration in the current config file.
		$mapping = new TSqlMapXmlMappingConfiguration($this);
		$mapping->configure($filename);

		foreach ($document->xpath('//sqlMap') as $sqlmap) {
			$this->loadSqlMappingFiles($sqlmap);
		}

		$this->resolveResultMapping();
		$this->attachCacheModels();
	}

	/**
	 * Load global replacement property.
	 * @param SimpleXmlElement $node property node.
	 */
	protected function loadGlobalProperty($node)
	{
		$this->_properties[(string) $node['name']] = (string) $node['value'];
	}

	/**
	 * Load the type handler configurations.
	 * @param SimpleXmlElement $node type handler node
	 */
	protected function loadTypeHandler($node)
	{
		$handler = $this->createObjectFromNode($node);
		$this->_manager->getTypeHandlers()->registerTypeHandler($handler);
	}

	/**
	 * Load the database connection tag.
	 * @param SimpleXmlElement $node connection node.
	 */
	protected function loadDatabaseConnection($node)
	{
		$conn = $this->createObjectFromNode($node);
		$this->_manager->setDbConnection($conn);
	}

	/**
	 * Load SqlMap mapping configuration.
	 * @param unknown_type $node
	 */
	protected function loadSqlMappingFiles($node)
	{
		if (strlen($resource = (string) $node['resource']) > 0) {
			if (strpos($resource, '${') !== false) {
				$resource = $this->replaceProperties($resource);
			}

			$mapping = new TSqlMapXmlMappingConfiguration($this);
			$filename = $this->getAbsoluteFilePath($this->_configFile, $resource);
			$mapping->configure($filename);
		}
	}

	/**
	 * Resolve nest result mappings.
	 */
	protected function resolveResultMapping()
	{
		$maps = $this->_manager->getResultMaps();
		foreach ($maps as $entry) {
			foreach ($entry->getColumns() as $item) {
				$resultMap = $item->getResultMapping();
				if (strlen($resultMap) > 0) {
					if ($maps->contains($resultMap)) {
						$item->setNestedResultMap($maps[$resultMap]);
					} else {
						throw new TSqlMapConfigurationException(
							'sqlmap_unable_to_find_result_mapping',
							$resultMap,
							$this->_configFile,
							$entry->getID()
						);
					}
				}
			}
			if ($entry->getDiscriminator() !== null) {
				$entry->getDiscriminator()->initialize($this->_manager);
			}
		}
	}

	/**
	 * Set the cache for each statement having a cache model property.
	 */
	protected function attachCacheModels()
	{
		foreach ($this->_manager->getMappedStatements() as $mappedStatement) {
			if (strlen($model = $mappedStatement->getStatement()->getCacheModel()) > 0) {
				$cache = $this->_manager->getCacheModel($model);
				$mappedStatement->getStatement()->setCache($cache);
			}
		}
	}

	/**
	 * Replace the place holders ${name} in text with properties the
	 * corresponding global property value.
	 * @param string $string original string.
	 * @return string string with global property replacement.
	 */
	public function replaceProperties($string)
	{
		foreach ($this->_properties as $find => $replace) {
			$string = str_replace('${' . $find . '}', $replace, $string);
		}
		return $string;
	}
}

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
use Prado\Prado;

/**
 * TSqlMapXmlConfig class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 */
abstract class TSqlMapXmlConfigBuilder
{
	/**
	 * Create an instance of an object give by the attribute named 'class' in the
	 * node and set the properties on the object given by attribute names and values.
	 * @param SimpleXmlNode $node property node
	 * @return Object new instance of class with class name given by 'class' attribute value.
	 */
	protected function createObjectFromNode($node)
	{
		if (isset($node['class'])) {
			$obj = Prado::createComponent((string) $node['class']);
			$this->setObjectPropFromNode($obj, $node, ['class']);
			return $obj;
		}
		throw new TSqlMapConfigurationException(
			'sqlmap_node_class_undef',
			$node,
			$this->getConfigFile()
		);
	}
	/**
	 * For each attributes (excluding attribute named in $except) set the
	 * property of the $obj given by the name of the attribute with the value
	 * of the attribute.
	 * @param object $obj object instance
	 * @param SimpleXmlNode $node property node
	 * @param array $except exception property name
	 */
	protected function setObjectPropFromNode($obj, $node, $except = [])
	{
		foreach ($node->attributes() as $name => $value) {
			if (!in_array($name, $except)) {
				if ($obj->canSetProperty($name)) {
					$obj->{$name} = (string) $value;
				} else {
					throw new TSqlMapConfigurationException(
						'sqlmap_invalid_property',
						$name,
						get_class($obj),
						$node,
						$this->getConfigFile()
					);
				}
			}
		}
	}
	/**
	 * Gets the filename relative to the basefile.
	 * @param string $basefile base filename
	 * @param string $resource relative filename
	 * @return string absolute filename.
	 */
	protected function getAbsoluteFilePath($basefile, $resource)
	{
		$basedir = dirname($basefile);
		$file = realpath($basedir . DIRECTORY_SEPARATOR . $resource);
		if (!is_string($file) || !is_file($file)) {
			$file = realpath($resource);
		}
		if (is_string($file) && is_file($file)) {
			return $file;
		} else {
			throw new TSqlMapConfigurationException(
				'sqlmap_unable_to_find_resource',
				$resource
			);
		}
	}
	/**
	 * Load document using simple xml.
	 * @param string $filename
	 * @param TSqlMapXmlConfiguration $config
	 * @return SimpleXmlElement xml document.
	 */
	protected function loadXmlDocument($filename, TSqlMapXmlConfiguration $config)
	{
		if (strpos($filename, '${') !== false) {
			$filename = $config->replaceProperties($filename);
		}
		if (!is_file($filename)) {
			throw new TSqlMapConfigurationException(
				'sqlmap_unable_to_find_config',
				$filename
			);
		}
		return simplexml_load_string($config->replaceProperties(file_get_contents($filename)));
	}
	/**
	 * Get element node by ID value (try for attribute name ID as case insensitive).
	 * @param SimpleXmlDocument $document
	 * @param string $tag tag name.
	 * @param string $value id value.
	 * @return SimpleXmlElement node if found, null otherwise.
	 */
	protected function getElementByIdValue($document, $tag, $value)
	{
		//hack to allow upper case and lower case attribute names.
		foreach (['id', 'ID', 'Id', 'iD'] as $id) {
			$xpath = "//{$tag}[@{$id}='{$value}']";
			foreach ($document->xpath($xpath) as $node) {
				return $node;
			}
		}
	}
	/**
	 * @return string configuration file.
	 */
	abstract protected function getConfigFile();
}

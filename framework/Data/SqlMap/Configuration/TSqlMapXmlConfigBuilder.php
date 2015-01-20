<?php
/**
 * TSqlMapXmlConfigBuilder, TSqlMapXmlConfiguration, TSqlMapXmlMappingConfiguration classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Data.SqlMap.Configuration
 */

Prado::using('System.Data.SqlMap.Configuration.TSqlMapStatement');

/**
 * TSqlMapXmlConfig class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package System.Data.SqlMap.Configuration
 */
abstract class TSqlMapXmlConfigBuilder
{
	/**
	 * Create an instance of an object give by the attribute named 'class' in the
	 * node and set the properties on the object given by attribute names and values.
	 * @param SimpleXmlNode property node
	 * @return Object new instance of class with class name given by 'class' attribute value.
	 */
	protected function createObjectFromNode($node)
	{
		if(isset($node['class']))
		{
			$obj = Prado::createComponent((string)$node['class']);
			$this->setObjectPropFromNode($obj,$node,array('class'));
			return $obj;
		}
		throw new TSqlMapConfigurationException(
			'sqlmap_node_class_undef', $node, $this->getConfigFile());
	}

	/**
	 * For each attributes (excluding attribute named in $except) set the
	 * property of the $obj given by the name of the attribute with the value
	 * of the attribute.
	 * @param Object object instance
	 * @param SimpleXmlNode property node
	 * @param array exception property name
	 */
	protected function setObjectPropFromNode($obj,$node,$except=array())
	{
		foreach($node->attributes() as $name=>$value)
		{
			if(!in_array($name,$except))
			{
				if($obj->canSetProperty($name))
					$obj->{$name} = (string)$value;
				else
					throw new TSqlMapConfigurationException(
						'sqlmap_invalid_property', $name, get_class($obj),
						$node, $this->getConfigFile());
			}
		}
	}

	/**
	 * Gets the filename relative to the basefile.
	 * @param string base filename
	 * @param string relative filename
	 * @return string absolute filename.
	 */
	protected function getAbsoluteFilePath($basefile,$resource)
	{
		$basedir = dirname($basefile);
		$file = realpath($basedir.DIRECTORY_SEPARATOR.$resource);
		if(!is_string($file) || !is_file($file))
			$file = realpath($resource);
		if(is_string($file) && is_file($file))
			return $file;
		else
			throw new TSqlMapConfigurationException(
				'sqlmap_unable_to_find_resource', $resource);
	}

	/**
	 * Load document using simple xml.
	 * @param string filename.
	 * @return SimpleXmlElement xml document.
	 */
	protected function loadXmlDocument($filename,TSqlMapXmlConfiguration $config)
	{
		if( strpos($filename, '${') !== false)
			$filename = $config->replaceProperties($filename);

		if(!is_file($filename))
			throw new TSqlMapConfigurationException(
				'sqlmap_unable_to_find_config', $filename);
		return simplexml_load_string($config->replaceProperties(file_get_contents($filename)));
	}

	/**
	 * Get element node by ID value (try for attribute name ID as case insensitive).
	 * @param SimpleXmlDocument $document
	 * @param string tag name.
	 * @param string id value.
	 * @return SimpleXmlElement node if found, null otherwise.
	 */
	protected function getElementByIdValue($document, $tag, $value)
	{
		//hack to allow upper case and lower case attribute names.
		foreach(array('id','ID','Id', 'iD') as $id)
		{
			$xpath = "//{$tag}[@{$id}='{$value}
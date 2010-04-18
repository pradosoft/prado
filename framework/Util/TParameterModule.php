<?php
/**
 * TParameterModule class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Util
 */

/**
 * TParameterModule class
 *
 * TParameterModule enables loading application parameters from external
 * storage other than the application configuration.
 * To load parameters from an XML file, configure the module by setting
 * its {@link setParameterFile ParameterFile} property.
 * Note, the property only accepts a file path in namespace format with
 * file extension being '.xml'. The file format is as follows,  which is
 * similar to the parameter portion in an application configuration,
 * <code>
 * <parameters>
 *   <parameter id="param1" value="paramValue1" />
 *   <parameter id="param2" Property1="Value1" Property2="Value2" ... />
 *   <parameter id="param3" value="cannot be changed" final="true" />
 * </parameters>
 * </code>
 *
 * In addition, any content enclosed within the module tag is also treated
 * as parameters, e.g.,
 * <code>
 * <module class="System.Util.TParameterModule">
 *   <parameter id="param1" value="paramValue1" />
 *   <parameter id="param2" Property1="Value1" Property2="Value2" ... />
 *   <parameter id="param3" value="cannot be changed" final="true" />
 * </module>
 * </code>
 *
 * Setting the final attribute to true will cause that parameter to be unchangable
 * by any future mergeParameters.
 *
 * If a parameter is defined both in the external file and within the module
 * tag, the former takes precedence.
 *
 * the application parameters are processed first before the modules parameters 
 * are processed.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @version $Id$
 * @package System.Util
 * @since 3.0
 */
class TParameterModule extends TModule
{
	/**
	 * @deprecated since 3.2
	 */
	const PARAM_FILE_EXT='.xml';
	private $_initialized=false;
	private $_paramFile=null;

	/**
	 * Initializes the module by loading parameters.
	 * @param mixed content enclosed within the module tag
	 */
	public function init($config)
	{
		$this->loadParameters($config);
		if($this->_paramFile!==null)
		{
			$configFile = null;
			if($this->getApplication()->getConfigurationType()==TApplication::CONFIG_TYPE_XML && ($cache=$this->getApplication()->getCache())!==null)
			{
				$cacheKey='TParameterModule:'.$this->_paramFile;
				if(($configFile=$cache->get($cacheKey))===false)
				{
					$cacheFile=new TXmlDocument;
					$cacheFile->loadFromFile($this->_paramFile);
					$cache->set($cacheKey,$cacheFile,0,new TFileCacheDependency($this->_paramFile));
				}
			}
			else
			{
				if($this->getApplication()->getConfigurationType()==TApplication::CONFIG_TYPE_PHP)
				{
					$configFile = include $this->_paramFile;
				}
				else
				{
					$configFile=new TXmlDocument;
					$configFile->loadFromFile($this->_paramFile);
				}
			}
			$this->loadParameters($configFile);
		}
		$this->_initialized=true;
	}

	/**
	 * Loads parameters into application.
	 * @param mixed XML or PHP representation of the parameters
	 * @throws TConfigurationException if the parameter file format is invalid
	 */
	protected function loadParameters($config)
	{
		$parameters=array();
		if(is_array($config))
		{
			foreach($config as $id => $parameter)
			{
				if(is_array($parameter))
				{
					$final = TPropertyValue::ensureBoolean($parameter['final']);
					unset($parameter['final']);
					if(isset($parameter['class']))
					{
						$properties = isset($parameter['properties']) ? $parameter['properties'] : array();
						$properties['id'] = $id;
						$parameters[$id] = array('type'=>0, 'class' => $parameter['class'], 'properties' => $properties, 
									'final' => $final, 'value' => $parameter);
					} else {
						if(!isset($parameter['value'])) {
							$parameters[$id]=array('type'=>1, 'value' => $parameter, 'final' => $final);
						} else {
							$parameters[$id]=array('type'=>2, 'value' => $value, 'final' => $final);
						}
					}
				}
				else
				{
					$parameters[$id] = $parameter;
				}
			}
		}
		else if($config instanceof TXmlElement)
		{
			foreach($config->getElementsByTagName('parameter') as $node)
			{
				$properties=$node->getAttributes();
				if(($id=$properties->remove('id'))===null)
					throw new TConfigurationException('parametermodule_parameterid_required');
				$final = TPropertyValue::ensureBoolean($properties->remove('final'));
				if(($type=$properties->remove('class'))===null)
				{
					if(($value=$properties->remove('value'))===null)
						$parameters[$id]=array('type'=>1, 'value' => $node, 'final' => $final);
					else
						$parameters[$id]=array('type'=>2, 'value' => $value, 'final' => $final);
				}
				else {
					$parameters[$id]=array('type'=>0, 'class' => $type, 'properties' => $properties->toArray(), 
								'final' => $final, 'value' => $node);
				}
			}
		}
		
		$this->getApplication()->mergeParameters($parameters);
	}

	/**
	 * @return string the parameter file path
	 */
	public function getParameterFile()
	{
		return $this->_paramFile;
	}

	/**
	 * @param string the parameter file path. It must be in namespace format
	 * and the file extension is '.xml'.
	 * @throws TInvalidOperationException if the module is initialized
	 * @throws TConfigurationException if the file is invalid
	 */
	public function setParameterFile($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('parametermodule_parameterfile_unchangeable');
		else if(($this->_paramFile=Prado::getPathOfNamespace($value,$this->getApplication()->getConfigurationFileExt()))===null || !is_file($this->_paramFile))
			throw new TConfigurationException('parametermodule_parameterfile_invalid',$value);
	}
}


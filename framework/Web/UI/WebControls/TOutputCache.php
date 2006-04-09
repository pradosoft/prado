<?php
/**
 * TOutputCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TOutputCache class.
 *
 * TOutputCache enables caching a portion of a Web page, also known as
 * partial caching. The content being cached can be either static or
 * dynamic.
 *
 * To use TOutputCache, simply enclose the content to be cached
 * within the TOutputCache component tag on a template, e.g.,
 * <code>
 * <com:TOutputCache>
 *   content to be cached
 * </com:TOutputCache>
 * </code>
 * where content to be cached can be static text and/or component tags.
 *
 * The validity of the cached content is determined based on two factors:
 * the {@link setDuration Duration} and the {@link getCacheDependency CacheDependency}.
 * The former specifies the number of seconds that the data can remain
 * valid in cache (defaults to 60s), while the latter specifies a dependency
 * that the data depends on. If the dependency changes, the cached content
 * is invalidated. By default, TOutputCache doesn't specify a dependency.
 * Derived classes may override {@link getCacheDependency()} method to
 * enforce a dependency (such as system state change, etc.)
 *
 * The content fetched from cache may be variated with respect to
 * some parameters. It supports variation with respect to request parameters,
 * which is specified by {@link setVaryByParam VaryByParam} property.
 * If a specified request parameter is different, a different version of
 * cached content is used. This is extremely useful if a page's content
 * may be variated according to some GET parameters. To variate the cached
 * content by other factors, override {@link calculateCacheKey()} method.
 *
 * Output caches can be nested. An outer cache takes precedence over an
 * inner cache. This means, if the content cached by the inner cache expires
 * or is invalidated, while that by the outer cache not, the outer cached
 * content will be used.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TOutputCache extends TControl implements INamingContainer
{
	const CACHE_ID_PREFIX='prado:outputcache';
	private $_dataCached=false;
	private $_cacheAvailable=false;
	private $_cacheChecked=false;
	private $_cacheKey=null;
	private $_duration=60;
	private $_cache=null;
	private $_contents;
	private $_state;
	private $_actions=array();
	private $_varyByParam='';

	/**
	 * Returns a value indicating whether body contents are allowed for this control.
	 * This method overrides the parent implementation by checking if cached
	 * content is available or not. If yes, it returns false, otherwise true.
	 * @param boolean whether body contents are allowed for this control.
	 */
	public function getAllowChildControls()
	{
		$this->determineCacheability();
		return !$this->_dataCached;
	}

	private function determineCacheability()
	{
		if(!$this->_cacheChecked)
		{
			$this->_cacheChecked=true;
			if(!$this->getPage()->getIsPostBack() && ($this->_cache=$this->getApplication()->getCache())!==null && $this->_duration>0)
			{
				$this->_cacheAvailable=true;
				$data=$this->_cache->get($this->getCacheKey());
				if(($this->_dataCached=($data!==false)))
					list($this->_contents,$this->_state,$this->_actions)=$data;
			}
		}
	}

	/**
	 * Performs the Init step for the control and all its child controls.
	 * This method overrides the parent implementation by setting up
	 * the stack of the output cache in the page.
	 * Only framework developers should use this method.
	 * @param TControl the naming container control
	 */
	protected function initRecursive($namingContainer=null)
	{
		if($this->_cacheAvailable && !$this->_dataCached)
		{
			$stack=$this->getPage()->getCachingStack();
			$stack->push($this);
			parent::initRecursive($namingContainer);
			$stack->pop();
		}
		else
			parent::initRecursive($namingContainer);
	}

	/**
	 * Performs the Load step for the control and all its child controls.
	 * This method overrides the parent implementation by setting up
	 * the stack of the output cache in the page. If the data is restored
	 * from cache, it also recovers the actions associated with the cached data.
	 * Only framework developers should use this method.
	 * @param TControl the naming container control
	 */
	protected function loadRecursive()
	{
		if($this->_cacheAvailable && !$this->_dataCached)
		{
			$stack=$this->getPage()->getCachingStack();
			$stack->push($this);
			parent::loadRecursive();
			$stack->pop();
		}
		else
		{
			if($this->_dataCached)
				$this->performActions();
			parent::loadRecursive();
		}
	}

	private function performActions()
	{
		$page=$this->getPage();
		$cs=$page->getClientScript();
		foreach($this->_actions as $action)
		{
			if($action[0]==='Page.ClientScript')
				call_user_func_array(array($cs,$action[1]),$action[2]);
			else if($action[0]==='Page')
				call_user_func_array(array($page,$action[1]),$action[2]);
			else
				call_user_func_array(array($this->getSubProperty($action[0]),$action[1]),$action[2]);
		}
	}

	/**
	 * Performs the PreRender step for the control and all its child controls.
	 * This method overrides the parent implementation by setting up
	 * the stack of the output cache in the page.
	 * Only framework developers should use this method.
	 * @param TControl the naming container control
	 */
	protected function preRenderRecursive()
	{
		if($this->_cacheAvailable && !$this->_dataCached)
		{
			$stack=$this->getPage()->getCachingStack();
			$stack->push($this);
			parent::preRenderRecursive();
			$stack->pop();
		}
		else
			parent::preRenderRecursive();
	}

	/**
	 * Loads state (viewstate and controlstate) into a control and its children.
	 * This method overrides the parent implementation by loading
	 * cached state if available.
	 * This method should only be used by framework developers.
	 * @param array the collection of the state
	 * @param boolean whether the viewstate should be loaded
	 */
	protected function loadStateRecursive(&$state,$needViewState=true)
	{
		if($this->_dataCached)
			parent::loadStateRecursive($this->_state,$needViewState);
		else
			parent::loadStateRecursive($state,$needViewState);
	}

	/**
	 * Saves all control state (viewstate and controlstate) as a collection.
	 * This method overrides the parent implementation by saving state
	 * into cache if needed.
	 * This method should only be used by framework developers.
	 * @param boolean whether the viewstate should be saved
	 * @return array the collection of the control state (including its children's state).
	 */
	protected function &saveStateRecursive($needViewState=true)
	{
		if($this->_dataCached)
			return $this->_state;
		else if($this->_cacheAvailable)
		{
			$this->_state=parent::saveStateRecursive($needViewState);
			return $this->_state;
		}
		else
			return parent::saveStateRecursive($needViewState);
	}

	/**
	 * Registers an action associated with the content being cached.
	 * The registered action will be replayed if the content stored
	 * in the cache is served to end-users.
	 * @param string context of the action method. This is a property-path
	 * referring to the context object (e.g. Page, Page.ClientScript)
	 * @param string method name of the context object
	 * @param array list of parameters to be passed to the action method
	 */
	public function registerAction($context,$funcName,$funcParams)
	{
		$this->_actions[]=array($context,$funcName,$funcParams);
	}

	private function getCacheKey()
	{
		if($this->_cacheKey===null)
			$this->_cacheKey=$this->calculateCacheKey();
		return $this->_cacheKey;
	}

	/**
	 * Calculates the cache key.
	 * The key is calculated based on the unique ID of this control
	 * and the request parameters specified via {@link setVaryByParam VaryByParam}.
	 * This method may be overriden to support other variations in
	 * the calculated cache key.
	 * @return string cache key
	 */
	protected function calculateCacheKey()
	{
		if($this->_varyByParam!=='')
		{
			$params=array();
			$request=$this->getRequest();
			foreach(explode(',',$this->_varyByParam) as $name)
			{
				$name=trim($name);
				$params[$name]=$request->itemAt($name);
			}
			return self::CACHE_ID_PREFIX.$this->getUniqueID().serialize($params);
		}
		else
			return self::CACHE_ID_PREFIX.$this->getUniqueID();
	}

	/**
	 * Returns the dependency of the data to be cached.
	 * The default implementation simply returns null, meaning no specific dependency.
	 * This method may be overriden to associate the data to be cached
	 * with additional dependencies.
	 * @return ICacheDependency
	 */
	protected function getCacheDependency()
	{
		return null;
	}

	/**
	 * @return boolean whether content enclosed is cached or not
	 */
	public function getContentCached()
	{
		return $this->_dataCached;
	}

	/**
	 * @return integer number of seconds that the data can remain in cache. Defaults to 60 seconds.
	 * Note, if cache dependency changes or cache space is limited,
	 * the data may be purged out of cache earlier.
	 */
	public function getDuration()
	{
		return $this->_duration;
	}

	/**
	 * @param integer number of seconds that the data can remain in cache. If 0, it means data is not cached.
	 * @throws TInvalidDataValueException if the value is smaller than 0.
	 */
	public function setDuration($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			throw new TInvalidDataValueException('outputcache_duration_invalid',get_class($this));
		$this->_duration=$value;
	}

	/**
	 * @return string a semicolon-separated list of strings used to vary the output cache. Defaults to ''.
	 */
	public function getVaryByParam()
	{
		return $this->_varyByParam;
	}

	/**
	 * Sets the names of the request parameters that should be used in calculating the cache key.
	 * The names should be concatenated by semicolons.
	 * By setting this value, the output cache will use different cached data
	 * for each different set of request parameter values.
	 * @return string a semicolon-separated list of strings used to vary the output cache.
	 */
	public function setVaryByParam($value)
	{
		$this->_varyByParam=trim($value);
	}

	/**
	 * Renders the output cache control.
	 * This method overrides the parent implementation by capturing the output
	 * from its child controls and saving it into cache, if output cache is needed.
	 * @param THtmlWriter
	 */
	public function render($writer)
	{
		if($this->_dataCached)
			$writer->write($this->_contents);
		else if($this->_cacheAvailable)
		{
			$textWriter=new TTextWriter;

			$stack=$this->getPage()->getCachingStack();
			$stack->push($this);
			parent::render(new THtmlWriter($textWriter));
			$stack->pop();

			$content=$textWriter->flush();
			$data=array($content,$this->_state,$this->_actions);
			$this->_cache->set($this->getCacheKey(),$data,$this->getDuration(),$this->getCacheDependency());
			$writer->write($content);
		}
		else
			parent::render($writer);
	}
}

?>
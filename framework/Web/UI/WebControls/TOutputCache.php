<?php

class TOutputCache extends TControl implements INamingContainer
{
	const CACHE_ID_PREFIX='prado:outputcache';
	private $_useCache=false;
	private $_cacheReady=false;
	private $_cacheChecked=false;
	private $_expiry=60;
	private $_cache=null;
	private $_contents;
	private $_state;
	private $_enableCaching=true;
	private $_actions=array();

	protected function initRecursive($namingContainer=null)
	{
		if($this->_cacheReady && !$this->_useCache)
		{
			$stack=$this->getPage()->getCachingStack();
			$stack->push($this);
			parent::initRecursive($namingContainer);
			$stack->pop();
		}
		else
			parent::initRecursive($namingContainer);
	}

	protected function loadRecursive()
	{
		if($this->_cacheReady && !$this->_useCache)
		{
			$stack=$this->getPage()->getCachingStack();
			$stack->push($this);
			parent::loadRecursive();
			$stack->pop();
		}
		else
		{
			if($this->_useCache)
			{
				$cs=$this->getPage()->getClientScript();
				foreach($this->_actions as $action)
				{
					if($action[0]==='registerRequiresPostData')
						$this->getPage()->registerRequiresPostData($action[1]);
					else
						call_user_func_array(array($cs,$action[0]),$action[1]);
				}
			}
			parent::loadRecursive();
		}
	}

	protected function preRenderRecursive()
	{
		if($this->_cacheReady && !$this->_useCache)
		{
			$stack=$this->getPage()->getCachingStack();
			$stack->push($this);
			parent::preRenderRecursive();
			$stack->pop();
		}
		else
			parent::preRenderRecursive();
	}

	public function registerAction($funcName,$funcParams)
	{
		$this->_actions[]=array($funcName,$funcParams);
	}

	public function getAllowChildControls()
	{
		if(!$this->_cacheChecked)
		{
			$this->_cacheChecked=true;
			if(!$this->getPage()->getIsPostBack() && ($this->_cache=$this->getApplication()->getCache())!==null && $this->getEnableCaching())
			{
				$this->_cacheReady=true;
				$data=$this->_cache->get($this->getCacheKey());
				if(($this->_useCache=($data!==false)))
					list($this->_contents,$this->_state,$this->_actions)=$data;
			}
		}
		return !$this->_useCache;
	}

	public function getEnableCaching()
	{
		return $this->_enableCaching;
	}

	public function setEnableCaching($value)
	{
		$this->_enableCaching=TPropertyValue::ensureBoolean($value);
	}

	protected function loadStateRecursive(&$state,$needViewState=true)
	{
		if($this->_useCache)
			parent::loadStateRecursive($this->_state,$needViewState);
		else
			parent::loadStateRecursive($state,$needViewState);
	}

	protected function &saveStateRecursive($needViewState=true)
	{
		if($this->_useCache)
			return $this->_state;
		else if($this->_cacheReady)
		{
			$this->_state=parent::saveStateRecursive($needViewState);
			return $this->_state;
		}
		else
			return parent::saveStateRecursive($needViewState);
	}

	protected function getCacheKey()
	{
		return self::CACHE_ID_PREFIX.$this->getUniqueID();
	}

	public function getExpiry()
	{
		return $this->_expiry;
	}

	public function setExpiry($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			throw new TInvalidDataValueException('outputcache_expiry_invalid');
		$this->_expiry=$value;
	}

	public function render($writer)
	{
		if($this->_useCache)
			$writer->write($this->_contents);
		else if($this->_cacheReady)
		{
			$textWriter=new TTextWriter;

			$stack=$this->getPage()->getCachingStack();
			$stack->push($this);
			parent::render(new THtmlWriter($textWriter));
			$stack->pop();

			$content=$textWriter->flush();
			$data=array($content,$this->_state,$this->_actions);
			$this->_cache->set($this->getCacheKey(),$data,$this->getExpiry());
			$writer->write($content);
		}
		else
			parent::render($writer);
	}
}

?>
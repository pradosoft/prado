<?php
/**
 * THttpRequest, THttpCookie, THttpCookieCollection, TUri class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web
 */

/**
 * THttpCookieCollection class.
 *
 * THttpCookieCollection implements a collection class to store cookies.
 * Besides using all functionalities from {@link TList}, you can also
 * retrieve a cookie by its name using either {@link findCookieByName} or
 * simply:
 * <code>
 *   $cookie=$collection[$cookieName];
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web
 * @since 3.0
 */
class THttpCookieCollection extends TList
{
	/**
	 * @var mixed owner of this collection
	 */
	private $_o;

	/**
	 * Constructor.
	 * @param mixed owner of this collection.
	 */
	public function __construct($owner=null)
	{
		$this->_o=$owner;
	}

	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * operations for each newly added THttpCookie object.
	 * @param integer the specified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a THttpCookie object.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof THttpCookie)
		{
			parent::insertAt($index,$item);
			if($this->_o instanceof THttpResponse)
				$this->_o->addCookie($item);
		}
		else
			throw new TInvalidDataTypeException('httpcookiecollection_httpcookie_required');
	}

	/**
	 * Removes an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * cleanup work when removing a TCookie object.
	 * @param integer the index of the item to be removed.
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$item=parent::removeAt($index);
		if($this->_o instanceof THttpResponse)
			$this->_o->removeCookie($item);
		return $item;
	}

	/**
	 * @param integer|string index of the cookie in the collection or the cookie's name
	 * @return THttpCookie the cookie found
	 */
	public function itemAt($index)
	{
		if(is_integer($index))
			return parent::itemAt($index);
		else
			return $this->findCookieByName($index);
	}

	/**
	 * Finds the cookie with the specified name.
	 * @param string the name of the cookie to be looked for
	 * @return THttpCookie the cookie, null if not found
	 */
	public function findCookieByName($name)
	{
		foreach($this as $cookie)
			if($cookie->getName()===$name)
				return $cookie;
		return null;
	}
}
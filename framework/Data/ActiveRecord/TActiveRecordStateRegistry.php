<?php
/**
 * TActiveRecordStateRegistry class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord
 */

/**
 * Active record Unit of Work class and Identity Map.
 *
 * Maintains a list of objects affected by a business transaction and
 * coordinates the writing out of changes and the resolution of concurrency problems.
 *
 * This registry keeps track of everything you do during a business transaction
 * that can affect the database. When you're done, it figures out everything that
 * needs to be done to alter the database as a result of your work.
 *
 * The object can only be in one of the four states: "new", "clean", "dirty" or "removed".
 * A "new" object is one that is created not by loading the record from database.
 * A "clean" object is one that is created by loading the record from datase.
 * A "dirty" object is one that is marked as dirty or a "clean" object that has
 * its internal state altered (done by using == object comparision).
 * A "removed" object is one that is marked for deletion.
 *
 * See the "Active Record Object States.png" in the docs directory for state
 * transition diagram.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordStateRegistry
{
	private $_cleanObjects=array();
	private $_removedObjects;
	private $_cachedObjects=array();
	/**
	 * Initialize the registry.
	 */
	public function __construct()
	{
		$this->_removedObjects = new TList;
	}

	/**
	 * Get the cached object for given type and row data. The cached object
	 * must also be clean.
	 * @param mixed row data fetched
	 * @return TActiveRecord cached object if found, null otherwise.
	 */
	public function getCachedInstance($data,$mustBeClean=true)
	{
		$key = $this->getObjectDataKey($data);
		if(isset($this->_cachedObjects[$key]))
		{
			$obj = $this->_cachedObjects[$key];
			if(!($mustBeClean && !$this->getIsCleanObject($obj)))
				return $obj;
		}
	}

	/**
	 * Cache the object that corresponding to the fetched row data.
	 * @param mixed row data fetched.
	 * @param TActiveRecord object to be cached.
	 * @return TActiveRecord cached object.
	 */
	public function addCachedInstance($data,$obj)
	{
		$key = $this->getObjectDataKey($data);
		$this->registerClean($obj);
		$this->_cachedObjects[$key]=$obj;
		return $obj;
	}

	/**
	 * @return string hash of the data.
	 */
	protected function getObjectDataKey($data)
	{
		return sprintf('%x',crc32(serialize($data)));
	}

	/**
	 * Ensure that object is not null.
	 */
	protected function assertNotNull($obj)
	{
		if(is_null($obj))
			throw new TActiveRecordException('ar_object_must_not_be_null');
	}

	/**
	 * Register the object for deletion, when the object invokes its delete() method
	 * the corresponding row in the database is deleted.
	 * @param TActiveRecord existing active record.
	 * @throws TActiveRecordException if object is null.
	 */
	public function registerRemoved($obj)
	{
		$this->assertNotNull($obj);
		$found=false;
		foreach($this->_cleanObjects as $i=>$cache)
		{
			if($cache[0]===$obj)
			{
				unset($this->_cleanObjects[$i]);
				$found=true;
			}
		}
		if(!$found)
			throw new TActiveRecordException('ar_object_must_be_retrieved_before_delete');
		if(!$this->_removedObjects->contains($obj))
			$this->_removedObjects->add($obj);
	}

	/**
	 * Register a clean object attached to a specific data that was used to
	 * populate the object. This acts as an object cache.
	 * @param TActiveRecord new clean object.
	 */
	public function registerClean($obj)
	{
		$this->removeCleanOrDirty($obj);
		if($this->getIsRemovedObject($obj))
			throw new TActiveRecordException('ar_object_marked_for_removal');
		$this->_cleanObjects[] = array($obj, clone($obj));
	}

	/**
	 * Remove the object from dirty state.
	 * @param TActiveRecord object to remove.
	 */
	protected function removeDirty($obj)
	{
		$this->assertNotNull($obj);
		foreach($this->_cleanObjects as $i=>$cache)
			if($cache[0]===$obj && $obj != $cache[1])
				unset($this->_cleanObjects[$i]);
	}

	/**
	 * Remove object from clean state.
	 * @param TActiveRecord object to remove.
	 */
	protected function removeClean($obj)
	{
		$this->assertNotNull($obj);
		foreach($this->_cleanObjects as $i=>$cache)
			if($cache[0]===$obj && $obj == $cache[1])
				unset($this->_cleanObjects[$i]);
	}

	/**
	 * Remove object from dirty and clean state.
	 * @param TActiveRecord object to remove.
	 */
	protected function removeCleanOrDirty($obj)
	{
		$this->assertNotNull($obj);
		foreach($this->_cleanObjects as $i=>$cache)
			if($cache[0]===$obj)
				unset($this->_cleanObjects[$i]);
	}

	/**
	 * Remove object from removed state.
	 * @param TActiveRecord object to remove.
	 */
	protected function removeRemovedObject($obj)
	{
		$this->_removedObjects->remove($obj);
	}

	/**
	 * Test whether an object is dirty or has been modified.
	 * @param TActiveRecord object to test.
	 * @return boolean true if the object is dirty, false otherwise.
	 */
	public function getIsDirtyObject($obj)
	{
		foreach($this->_cleanObjects as $cache)
			if($cache[0] === $obj)
				return $obj != $cache[1];
		return false;
	}

	/**
	 * Test whether an object is in the clean state.
	 * @param TActiveRecord object to test.
	 * @return boolean true if object is clean, false otherwise.
	 */
	public function getIsCleanObject($obj)
	{
		foreach($this->_cleanObjects as $cache)
			if($cache[0] === $obj)
				return $obj == $cache[1];
		return false;
	}

	/**
	 * Test whether an object is a new instance.
	 * @param TActiveRecord object to test.
	 * @return boolean true if object is newly created, false otherwise.
	 */
	public function getIsNewObject($obj)
	{
		if($this->getIsRemovedObject($obj)) return false;
		foreach($this->_cleanObjects as $cache)
			if($cache[0] === $obj)
				return false;
		return true;
	}

	/**
	 * Test whether an object is marked for deletion.
	 * @param TActiveRecord object to test.
	 * @return boolean true if object is marked for deletion, false otherwise.
	 */
	public function getIsRemovedObject($obj)
	{
		return $this->_removedObjects->contains($obj);
	}

	/**
	 * Commit the object to database:
	 *   * a new record is inserted if the object is new, object becomes clean.
	 *   * the record is updated if the object is dirty, object becomes clean.
	 *   * the record is deleted if the object is marked for removal.
	 *
	 * @param TActiveRecord record object.
	 * @param TActiveRecordGateway database gateway
	 * @return boolean true if commit was successful, false otherwise.
	 */
	public function commit($record,$gateway)
	{
		$rowsAffected=false;

		if($this->getIsRemovedObject($record))
		{
			$rowsAffected = $gateway->delete($record);
			if($rowsAffected)
				$this->removeRemovedObject($record);
		}
		else
		{
			if($this->getIsDirtyObject($record))
				$rowsAffected = $gateway->update($record);
			else if($this->getIsNewObject($record))
				$rowsAffected = $gateway->insert($record);

			if($rowsAffected)
				$this->registerClean($record);
		}
		return (boolean)$rowsAffected;
	}
}

?>
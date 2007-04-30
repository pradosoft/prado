<?php
/**
 * TActiveRecordHasOne class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Relations
 */

/**
 * Loads base active record relationship class.
 */
Prado::using('System.Data.ActiveRecord.Relations.TActiveRecordRelation');

/**
 * TActiveRecordHasOne models the object relationship that a record (the source object)
 * property is an instance of foreign record object having a foreign key
 * related to the source object. 
 *
 *
 * 
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Relations
 * @since 3.1
 */
class TActiveRecordHasOne extends TActiveRecordRelation
{
	/**
	 * Get the foreign key index values from the results and make calls to the
	 * database to find the corresponding foreign objects.
	 * @param array original results.
	 */
	protected function collectForeignObjects(&$results)
	{
		$fkObject = $this->getContext()->getForeignRecordFinder();
		$fkeys = $this->findForeignKeys($fkObject, $this->getSourceRecord());

		$properties = array_values($fkeys);
		$fields = array_keys($fkeys);

		$indexValues = $this->getIndexValues($properties, $results);
		$fkObjects = $this->findForeignObjects($fields,$indexValues);
		$this->populateResult($results,$properties,$fkObjects,$fields);
	}

	/**
	 * Sets the foreign objects to the given property on the source object.
	 * @param TActiveRecord source object.
	 * @param array foreign objects.
	 */
	protected function setObjectProperty($source, $properties, &$collections)
	{
		$hash = $this->getObjectHash($source, $properties);
		$prop = $this->getContext()->getProperty();
		if(isset($collections[$hash]) && count($collections[$hash]) > 0)
		{
			if(count($collections[$hash]) > 1)
				throw new TActiveRecordException('ar_belongs_to_multiple_result');
			$source->{$prop} = $collections[$hash][0];
		}
	}
}

?>
<?php

Prado::using('System.Data.ActiveRecord.Relations.TActiveRecordRelation');

class TActiveRecordBelongsTo extends TActiveRecordRelation
{
	/**
	 * Get the foreign key index values from the results and make calls to the
	 * database to find the corresponding foreign objects.
	 * @param array original results.
	 */
	protected function collectForeignObjects(&$results)
	{
		$fkObject = $this->getContext()->getForeignRecordFinder();
		$fkeys = $this->findForeignKeys($this->getSourceRecord(),$fkObject);

		$properties = array_keys($fkeys);
		$fields = array_values($fkeys);

		$indexValues = $this->getIndexValues($properties, $results);
		$fkObjects = $this->findForeignObjects($fields, $indexValues);
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
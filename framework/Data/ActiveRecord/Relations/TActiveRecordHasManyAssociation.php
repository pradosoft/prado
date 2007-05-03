<?php
/**
 * TActiveRecordHasManyAssociation class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Relations
 */

/**
 * Loads base active record relations class.
 */
Prado::using('System.Data.ActiveRecord.Relations.TActiveRecordRelation');

/**
 * Implements the M-N (many to many) relationship via association table.
 * Consider the <b>entity</b> relationship between Articles and Categories
 * via the association table <tt>Article_Category</tt>.
 * <code>
 * +---------+            +------------------+            +----------+
 * | Article | * -----> * | Article_Category | * <----- * | Category |
 * +---------+            +------------------+            +----------+
 * </code>
 * Where one article may have 0 or more categories and each category may have 0
 * or more articles. We may model Article-Category <b>object</b> relationship
 * as active record as follows.
 * <code>
 * class ArticleRecord
 * {
 *     const TABLE='Article';
 *     public $article_id;
 *
 *     public $Categories=array(); //foreign object collection.
 *
 *     protected static $RELATIONS = array
 *     (
 *         'Categories' => array(self::HAS_MANY, 'CategoryRecord', 'Article_Category')
 *     );
 *
 *     public static function finder($className=__CLASS__)
 *     {
 *         return parent::finder($className);
 *     }
 * }
 * class CategoryRecord
 * {
 *     const TABLE='Category';
 *     public $category_id;
 *
 *     public $Articles=array();
 *
 *     protected static $RELATIONS = array
 *     (
 *         'Articles' => array(self::HAS_MANY, 'ArticleRecord', 'Article_Category')
 *     );
 *
 *     public static function finder($className=__CLASS__)
 *     {
 *         return parent::finder($className);
 *     }
 * }
 * </code>
 *
 * The static <tt>$RELATIONS</tt> property of ArticleRecord defines that the
 * property <tt>$Categories</tt> has many <tt>CategoryRecord</tt>s. Similar, the
 * static <tt>$RELATIONS</tt> property of CategoryRecord defines many ArticleRecords.
 *
 * The articles with categories list may be fetched as follows.
 * <code>
 * $articles = TeamRecord::finder()->withCategories()->findAll();
 * </code>
 * The method <tt>with_xxx()</tt> (where <tt>xxx</tt> is the relationship property
 * name, in this case, <tt>Categories</tt>) fetchs the corresponding CategoryRecords using
 * a second query (not by using a join). The <tt>with_xxx()</tt> accepts the same
 * arguments as other finder methods of TActiveRecord.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Relations
 * @since 3.1
 */
class TActiveRecordHasManyAssociation extends TActiveRecordRelation
{
	private $_association;
	private $_sourceTable;
	private $_foreignTable;

	/**
	* Get the foreign key index values from the results and make calls to the
	* database to find the corresponding foreign objects using association table.
	* @param array original results.
	*/
	protected function collectForeignObjects(&$results)
	{
		$association = $this->getAssociationTable();
		$sourceKeys = $this->findForeignKeys($association, $this->getSourceRecord());

		$properties = array_values($sourceKeys);

		$indexValues = $this->getIndexValues($properties, $results);

		$fkObject = $this->getContext()->getForeignRecordFinder();
		$foreignKeys = $this->findForeignKeys($association, $fkObject);

		$this->fetchForeignObjects($results, $foreignKeys,$indexValues,$sourceKeys);
	}

	/**
	 * @return TDbTableInfo association table information.
	 */
	protected function getAssociationTable()
	{
		if($this->_association===null)
		{
			$gateway = $this->getSourceRecord()->getRecordGateway();
			$conn = $this->getSourceRecord()->getDbConnection();
			$table = $this->getContext()->getAssociationTable();
			$this->_association = $gateway->getTableInfo($conn, $table);
		}
		return $this->_association;
	}

	/**
	 * @return TDbTableInfo source table information.
	 */
	protected function getSourceTable()
	{
		if($this->_sourceTable===null)
		{
			$gateway = $this->getSourceRecord()->getRecordGateway();
			$this->_sourceTable = $gateway->getRecordTableInfo($this->getSourceRecord());
		}
		return $this->_sourceTable;
	}

	/**
	 * @return TDbTableInfo foreign table information.
	 */
	protected function getForeignTable()
	{
		if($this->_foreignTable===null)
		{
			$gateway = $this->getSourceRecord()->getRecordGateway();
			$fkObject = $this->getContext()->getForeignRecordFinder();
			$this->_foreignTable = $gateway->getRecordTableInfo($fkObject);
		}
		return $this->_foreignTable;
	}

	/**
	 * @return TDbCommandBuilder
	 */
	protected function getCommandBuilder()
	{
		return $this->getSourceRecord()->getRecordGateway()->getCommand($this->getSourceRecord());
	}

	/**
	 * Fetches the foreign objects using TActiveRecord::findAllByIndex()
	 * @param array field names
	 * @param array foreign key index values.
	 */
	protected function fetchForeignObjects(&$results,$foreignKeys,$indexValues,$sourceKeys)
	{
		$criteria = $this->getContext()->getCriteria();
		$finder = $this->getContext()->getForeignRecordFinder();
		$registry = $finder->getRecordManager()->getObjectStateRegistry();
		$type = get_class($finder);
		$command = $this->createCommand($criteria, $foreignKeys,$indexValues,$sourceKeys);
		$srcProps = array_keys($sourceKeys);
		$collections=array();
		foreach($command->query() as $row)
		{
			$hash = $this->getObjectHash($row, $srcProps);
			foreach($srcProps as $column)
				unset($row[$column]);
			$obj = new $type($row);
			$collections[$hash][] = $obj;
			$registry->registerClean($obj);
		}

		$this->setResultCollection($results, $collections, array_values($sourceKeys));
	}

	/**
	 * @param TSqlCriteria
	 * @param TTableInfo association table info
	 * @param array field names
	 * @param array field values
	 */
	public function createCommand($criteria, $foreignKeys,$indexValues,$sourceKeys)
	{
		$innerJoin = $this->getAssociationJoin($foreignKeys,$indexValues,$sourceKeys);
		$fkTable = $this->getForeignTable()->getTableFullName();
		$srcColumns = $this->getSourceColumns($sourceKeys);
		if(($where=$criteria->getCondition())===null)
			$where='1=1';
		$sql = "SELECT {$fkTable}.*, {$srcColumns} FROM {$fkTable} {$innerJoin} WHERE {$where}";

		$parameters = $criteria->getParameters()->toArray();
		$ordering = $criteria->getOrdersBy();
		$limit = $criteria->getLimit();
		$offset = $criteria->getOffset();

		$builder = $this->getCommandBuilder()->getBuilder();
		$command = $builder->applyCriterias($sql,$parameters,$ordering,$limit,$offset);
		$this->getCommandBuilder()->onCreateCommand($command, $criteria);
		return $command;
	}

	/**
	 * @param array source table column names.
	 * @return string comma separated source column names.
	 */
	protected function getSourceColumns($sourceKeys)
	{
		$columns=array();
		$table = $this->getAssociationTable();
		$tableName = $table->getTableFullName();
		foreach($sourceKeys as $name=>$fkName)
			$columns[] = $tableName.'.'.$table->getColumn($name)->getColumnName();
		return implode(', ', $columns);
	}

	/**
	 * SQL inner join for M-N relationship via association table.
	 * @param array foreign table column key names.
	 * @param array source table index values.
	 * @param array source table column names.
	 * @return string inner join condition for M-N relationship via association table.
	 */
	protected function getAssociationJoin($foreignKeys,$indexValues,$sourceKeys)
	{
		$refInfo= $this->getAssociationTable();
		$fkInfo = $this->getForeignTable();

		$refTable = $refInfo->getTableFullName();
		$fkTable = $fkInfo->getTableFullName();

		$joins = array();
		foreach($foreignKeys as $ref=>$fk)
		{
			$refField = $refInfo->getColumn($ref)->getColumnName();
			$fkField = $fkInfo->getColumn($fk)->getColumnName();
			$joins[] = "{$fkTable}.{$fkField} = {$refTable}.{$refField}";
		}
		$joinCondition = implode(' AND ', $joins);
		$index = $this->getCommandBuilder()->getIndexKeyCondition($refInfo,array_keys($sourceKeys), $indexValues);
		return "INNER JOIN {$refTable} ON ({$joinCondition}) AND {$index}";
	}
}
?>
<?php
/**
 * TMssqlMetaData class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Mssql
 */

namespace Prado\Data\Common\Mssql;

/**
 * Load the base TDbMetaData class.
 */
use Prado\Data\Common\TDbMetaData;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TMssqlMetaData loads MSSQL database table and column information.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Mssql
 * @since 3.1
 */
class TMssqlMetaData extends TDbMetaData
{
	/**
	 * @return string TDbTableInfo class name.
	 */
	protected function getTableInfoClass()
	{
		return '\Prado\Data\Common\Mssql\TMssqlTableInfo';
	}

	/**
	 * Quotes a table name for use in a query.
	 * @param string $name $name table name
	 * @return string the properly quoted table name
	 */
	public function quoteTableName($name)
	{
		return parent::quoteTableName($name, '[', ']');
	}

	/**
	 * Quotes a column name for use in a query.
	 * @param string $name $name column name
	 * @return string the properly quoted column name
	 */
	public function quoteColumnName($name)
	{
		return parent::quoteColumnName($name, '[', ']');
	}

	/**
	 * Quotes a column alias for use in a query.
	 * @param string $name $name column alias
	 * @return string the properly quoted column alias
	 */
	public function quoteColumnAlias($name)
	{
		return parent::quoteColumnAlias($name, '"', '"');
	}

	/**
	 * Get the column definitions for given table.
	 * @param string $table table name.
	 * @return TMssqlTableInfo table information.
	 */
	protected function createTableInfo($table)
	{
		[$catalogName, $schemaName, $tableName] = $this->getCatalogSchemaTableName($table);
		$this->getDbConnection()->setActive(true);
		$sql = <<<EOD
				SELECT t.*,
												c.*,
					columnproperty(object_id(c.table_schema + '.' + c.table_name), c.column_name,'IsIdentity') as IsIdentity
										FROM INFORMATION_SCHEMA.TABLES t,
												INFORMATION_SCHEMA.COLUMNS c
									WHERE t.table_name = c.table_name
										AND t.table_name = :table
EOD;
		if ($schemaName !== null) {
			$sql .= ' AND t.table_schema = :schema';
		}
		if ($catalogName !== null) {
			$sql .= ' AND t.table_catalog = :catalog';
		}

		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		if ($schemaName !== null) {
			$command->bindValue(':schema', $schemaName);
		}
		if ($catalogName !== null) {
			$command->bindValue(':catalog', $catalogName);
		}

		$tableInfo = null;
		foreach ($command->query() as $col) {
			if ($tableInfo === null) {
				$tableInfo = $this->createNewTableInfo($col);
			}
			$this->processColumn($tableInfo, $col);
		}
		if ($tableInfo === null) {
			throw new TDbException('dbmetadata_invalid_table_view', $table);
		}
		return $tableInfo;
	}

	/**
	 * @param string $table table name
	 * @return array tuple($catalogName,$schemaName,$tableName)
	 */
	protected function getCatalogSchemaTableName($table)
	{
		//remove possible delimiters
		$result = explode('.', preg_replace('/\[|\]|"/', '', $table));
		if (count($result) === 1) {
			return [null, null, $result[0]];
		}
		if (count($result) === 2) {
			return [null, $result[0], $result[1]];
		}
		if (count($result) > 2) {
			return [$result[0], $result[1], $result[2]];
		}
	}

	/**
	 * @param TMssqlTableInfo $tableInfo table information.
	 * @param array $col column information.
	 */
	protected function processColumn($tableInfo, $col)
	{
		$columnId = $col['COLUMN_NAME'];

		$info['ColumnName'] = "[$columnId]"; //quote the column names!
		$info['ColumnId'] = $columnId;
		$info['ColumnIndex'] = (int) ($col['ORDINAL_POSITION']) - 1; //zero-based index
		if ($col['IS_NULLABLE'] !== 'NO') {
			$info['AllowNull'] = true;
		}
		if ($col['COLUMN_DEFAULT'] !== null) {
			$info['DefaultValue'] = $col['COLUMN_DEFAULT'];
		}

		if (in_array($columnId, $tableInfo->getPrimaryKeys())) {
			$info['IsPrimaryKey'] = true;
		}
		if ($this->isForeignKeyColumn($columnId, $tableInfo)) {
			$info['IsForeignKey'] = true;
		}

		if ($col['IsIdentity'] === '1') {
			$info['AutoIncrement'] = true;
		}
		$info['DbType'] = $col['DATA_TYPE'];
		if ($col['CHARACTER_MAXIMUM_LENGTH'] !== null) {
			$info['ColumnSize'] = (int) ($col['CHARACTER_MAXIMUM_LENGTH']);
		}
		if ($col['NUMERIC_PRECISION'] !== null) {
			$info['NumericPrecision'] = (int) ($col['NUMERIC_PRECISION']);
		}
		if ($col['NUMERIC_SCALE'] !== null) {
			$info['NumericScale'] = (int) ($col['NUMERIC_SCALE']);
		}
		$tableInfo->Columns[$columnId] = new TMssqlTableColumn($info);
	}

	/**
	 * @param array $col table informations
	 * @return TMssqlTableInfo
	 */
	protected function createNewTableInfo($col)
	{
		$info['CatalogName'] = $col['TABLE_CATALOG'];
		$info['SchemaName'] = $col['TABLE_SCHEMA'];
		$info['TableName'] = $col['TABLE_NAME'];
		if ($col['TABLE_TYPE'] === 'VIEW') {
			$info['IsView'] = true;
		}
		[$primary, $foreign] = $this->getConstraintKeys($col);
		$class = $this->getTableInfoClass();
		return new $class($info, $primary, $foreign);
	}

	/**
	 * Gets the primary and foreign key column details for the given table.
	 * @param array $col table informations
	 * @return array tuple ($primary, $foreign)
	 */
	protected function getConstraintKeys($col)
	{
		$sql = <<<EOD
		SELECT k.column_name field_name
				FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
				LEFT JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS c
					ON k.table_name = c.table_name
				AND k.constraint_name = c.constraint_name
			WHERE k.constraint_catalog = DB_NAME()
		AND
			c.constraint_type ='PRIMARY KEY'
				AND k.table_name = :table
EOD;
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $col['TABLE_NAME']);
		$primary = [];
		foreach ($command->query()->readAll() as $field) {
			$primary[] = $field['field_name'];
		}
		$foreign = $this->getForeignConstraints($col);
		return [$primary, $foreign];
	}

	/**
	 * Gets foreign relationship constraint keys and table name
	 * @param array $col table informations
	 * @return array foreign relationship table name and keys.
	 */
	protected function getForeignConstraints($col)
	{
		//From http://msdn2.microsoft.com/en-us/library/aa175805(SQL.80).aspx
		$sql = <<<EOD
		SELECT
				KCU1.CONSTRAINT_NAME AS 'FK_CONSTRAINT_NAME'
			, KCU1.TABLE_NAME AS 'FK_TABLE_NAME'
			, KCU1.COLUMN_NAME AS 'FK_COLUMN_NAME'
			, KCU1.ORDINAL_POSITION AS 'FK_ORDINAL_POSITION'
			, KCU2.CONSTRAINT_NAME AS 'UQ_CONSTRAINT_NAME'
			, KCU2.TABLE_NAME AS 'UQ_TABLE_NAME'
			, KCU2.COLUMN_NAME AS 'UQ_COLUMN_NAME'
			, KCU2.ORDINAL_POSITION AS 'UQ_ORDINAL_POSITION'
		FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS RC
		JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE KCU1
		ON KCU1.CONSTRAINT_CATALOG = RC.CONSTRAINT_CATALOG
			AND KCU1.CONSTRAINT_SCHEMA = RC.CONSTRAINT_SCHEMA
			AND KCU1.CONSTRAINT_NAME = RC.CONSTRAINT_NAME
		JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE KCU2
		ON KCU2.CONSTRAINT_CATALOG =
		RC.UNIQUE_CONSTRAINT_CATALOG
			AND KCU2.CONSTRAINT_SCHEMA =
		RC.UNIQUE_CONSTRAINT_SCHEMA
			AND KCU2.CONSTRAINT_NAME =
		RC.UNIQUE_CONSTRAINT_NAME
			AND KCU2.ORDINAL_POSITION = KCU1.ORDINAL_POSITION
		WHERE KCU1.TABLE_NAME = :table
EOD;
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $col['TABLE_NAME']);
		$fkeys = [];
		$catalogSchema = "[{$col['TABLE_CATALOG']}].[{$col['TABLE_SCHEMA']}]";
		foreach ($command->query() as $info) {
			$fkeys[$info['FK_CONSTRAINT_NAME']]['keys'][$info['FK_COLUMN_NAME']] = $info['UQ_COLUMN_NAME'];
			$fkeys[$info['FK_CONSTRAINT_NAME']]['table'] = $info['UQ_TABLE_NAME'];
		}
		return count($fkeys) > 0 ? array_values($fkeys) : $fkeys;
	}

	/**
	 * @param string $columnId column name.
	 * @param TMssqlTableInfo $tableInfo table information.
	 * @return bool true if column is a foreign key.
	 */
	protected function isForeignKeyColumn($columnId, $tableInfo)
	{
		foreach ($tableInfo->getForeignKeys() as $fk) {
			if (in_array($columnId, array_keys($fk['keys']))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns all table names in the database.
	 * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
	 * If not empty, the returned table names will be prefixed with the schema name.
	 * @return array all table names in the database.
	 */
	public function findTableNames($schema = 'dbo')
	{
		$condition = "TABLE_TYPE='BASE TABLE'";
		$sql = <<<EOD
SELECT TABLE_NAME, TABLE_SCHEMA FROM [INFORMATION_SCHEMA].[TABLES]
WHERE TABLE_SCHEMA=:schema AND $condition
EOD;
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindParam(":schema", $schema);
		$rows = $command->queryAll();
		$names = [];
		foreach ($rows as $row) {
			if ($schema == self::DEFAULT_SCHEMA) {
				$names[] = $row['TABLE_NAME'];
			} else {
				$names[] = $schema . '.' . $row['TABLE_SCHEMA'] . '.' . $row['TABLE_NAME'];
			}
		}

		return $names;
	}
}

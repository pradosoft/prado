<?php
/**
 * TOracleMetaData class file.
 *
 * @author Marcos Nobre <marconobre[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Oracle
 */

namespace Prado\Data\Common\Oracle;

/**
 * Load the base TDbMetaData class.
 */
use PDO;
use Prado\Data\Common\TDbMetaData;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TOracleMetaData loads Oracle database table and column information.
 *
 * @author Marcos Nobre <marconobre[at]gmail[dot]com>
 * @package Prado\Data\Common\Oracle
 * @since 3.1
 */
class TOracleMetaData extends TDbMetaData
{
	private $_defaultSchema = 'system';


	/**
	 * @return string TDbTableInfo class name.
	 */
	protected function getTableInfoClass()
	{
		return '\Prado\Data\Common\Oracle\TOracleTableInfo';
	}

	/**
	 * @param string $schema default schema.
	 */
	public function setDefaultSchema($schema)
	{
		$this->_defaultSchema = $schema;
	}

	/**
	 * @return string default schema.
	 */
	public function getDefaultSchema()
	{
		return $this->_defaultSchema;
	}

	/**
	 * @param string $table table name with optional schema name prefix, uses default schema name prefix is not provided.
	 * @return array tuple as ($schemaName,$tableName)
	 */
	protected function getSchemaTableName($table)
	{
		if (count($parts = explode('.', str_replace('"', '', $table))) > 1) {
			return [$parts[0], $parts[1]];
		} else {
			return [$this->getDefaultSchema(), $parts[0]];
		}
	}

	/**
	 * Get the column definitions for given table.
	 * @param string $table table name.
	 * @return TOracleTableInfo table information.
	 */
	protected function createTableInfo($table)
	{
		[$schemaName, $tableName] = $this->getSchemaTableName($table);

		// This query is made much more complex by the addition of the 'attisserial' field.
		// The subquery to get that field checks to see if there is an internally dependent
		// sequence on the field.
		$sql =
<<<EOD
		SELECT
			a.COLUMN_ID,
			LOWER(a.COLUMN_NAME) as attname,
			a.DATA_TYPE || DECODE( a.DATA_TYPE, 'NUMBER', '('||a.DATA_PRECISION||','||DATA_SCALE||')' , '') as type,
			a.DATA_LENGTH as atttypmod,
			DECODE(a.NULLABLE, 'Y', '0', '1') as attnotnull,
			DECODE(a.DEFAULT_LENGTH, NULL, '0', '1') as atthasdef,
			DATA_DEFAULT as adsrc,
			'0' AS attisserial
		FROM
			ALL_TAB_COLUMNS a
		WHERE
			TABLE_NAME = '{$tableName}'
			AND OWNER = '{$schemaName}'
		ORDER BY a.COLUMN_ID
EOD;
		$this->getDbConnection()->setActive(true);
		$this->getDbConnection()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
		$command = $this->getDbConnection()->createCommand($sql);
		//$command->bindValue(':table', $tableName);
		//$command->bindValue(':schema', $schemaName);
		$tableInfo = $this->createNewTableInfo($schemaName, $tableName);
		$index = 0;
		foreach ($command->query() as $col) {
			$col['index'] = $index++;
			$this->processColumn($tableInfo, $col);
		}
		if ($index === 0) {
			throw new TDbException('dbmetadata_invalid_table_view', $table);
		}
		return $tableInfo;
	}

	/**
	 * @param string $schemaName table schema name
	 * @param string $tableName table name.
	 * @return TOracleTableInfo
	 */
	protected function createNewTableInfo($schemaName, $tableName)
	{
		$info['SchemaName'] = $this->assertIdentifier($schemaName);
		$info['TableName'] = $this->assertIdentifier($tableName);
		$info['IsView'] = false;
		if ($this->getIsView($schemaName, $tableName)) {
			$info['IsView'] = true;
		}
		[$primary, $foreign] = $this->getConstraintKeys($schemaName, $tableName);
		$class = $this->getTableInfoClass();
		return new $class($info, $primary, $foreign);
	}

	/**
	 * @param string $name table name, schema name or column name.
	 * @throws TDbException when table name contains a double quote (").
	 * @return string a valid identifier.
	 */
	protected function assertIdentifier($name)
	{
		if (strpos($name, '"') !== false) {
			$ref = 'http://www.oracle.com';
			throw new TDbException('dbcommon_invalid_identifier_name', $name, $ref);
		}
		return $name;
	}

	/**
	 * @param string $schemaName table schema name
	 * @param string $tableName table name.
	 * @return int 1 if the table is a view.
	 */
	protected function getIsView($schemaName, $tableName)
	{
		$this->getDbConnection()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
		$sql =
<<<EOD
		select	OBJECT_TYPE
		from 	ALL_OBJECTS
		where	OBJECT_NAME = '{$tableName}'
		and 	OWNER = '{$schemaName}'
EOD;
		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		//$command->bindValue(':schema',$schemaName);
		//$command->bindValue(':table', $tableName);
		return (int) ($command->queryScalar() === 'VIEW');
	}

	/**
	 * @param TOracleTableInfo $tableInfo table information.
	 * @param array $col column information.
	 */
	protected function processColumn($tableInfo, $col)
	{
		$columnId = strtolower($col['attname']); //use column name as column Id

		//$info['ColumnName'] 	= '"'.$columnId.'"'; //quote the column names!
		$info['ColumnName'] = $columnId; //NOT quote the column names!
		$info['ColumnId'] = $columnId;
		$info['ColumnIndex'] = $col['index'];
		if (!(bool) $col['attnotnull']) {
			$info['AllowNull'] = true;
		}
		if (in_array($columnId, $tableInfo->getPrimaryKeys())) {
			$info['IsPrimaryKey'] = true;
		}
		if ($this->isForeignKeyColumn($columnId, $tableInfo)) {
			$info['IsForeignKey'] = true;
		}
		if ((int) $col['atttypmod'] > 0) {
			$info['ColumnSize'] = $col['atttypmod'];
		} // - 4;
		if ((bool) $col['atthasdef']) {
			$info['DefaultValue'] = $col['adsrc'];
		}
		//
		// For a while Oracle Tables has no  associated AutoIncrement Triggers
		//
		/*
		if( $col['attisserial'] )
		{
			if(($sequence = $this->getSequenceName($tableInfo, $col['adsrc']))!==null)
			{
				$info['SequenceName'] = $sequence;
				unset($info['DefaultValue']);
			}
		}
		*/
		$matches = [];
		if (preg_match('/\((\d+)(?:,(\d+))?+\)/', $col['type'], $matches)) {
			$info['DbType'] = preg_replace('/\(\d+(?:,\d+)?\)/', '', $col['type']);
			if ($this->isPrecisionType($info['DbType'])) {
				$info['NumericPrecision'] = (int) ($matches[1]);
				if (count($matches) > 2) {
					$info['NumericScale'] = (int) ($matches[2]);
				}
			} else {
				$info['ColumnSize'] = (int) ($matches[1]);
			}
		} else {
			$info['DbType'] = $col['type'];
		}
		$tableInfo->Columns[$columnId] = new TOracleTableColumn($info);
	}

	/**
	 * @param mixed $tableInfo
	 * @param mixed $src
	 * @return string serial name if found, null otherwise.
	 */
	protected function getSequenceName($tableInfo, $src)
	{
		$matches = [];
		if (preg_match('/nextval\([^\']*\'([^\']+)\'[^\)]*\)/i', $src, $matches)) {
			if (is_int(strpos($matches[1], '.'))) {
				return $matches[1];
			} else {
				return $tableInfo->getSchemaName() . '.' . $matches[1];
			}
		}
	}

	/**
	 * @param mixed $type
	 * @return bool true if column type if "numeric", "interval" or begins with "time".
	 */
	protected function isPrecisionType($type)
	{
		$type = strtolower(trim($type));
		return $type === 'number'; // || $type==='interval' || strpos($type, 'time')===0;
	}

	/**
	 * Gets the primary and foreign key column details for the given table.
	 * @param string $schemaName schema name
	 * @param string $tableName table name.
	 * @return array tuple ($primary, $foreign)
	 */
	protected function getConstraintKeys($schemaName, $tableName)
	{
		$this->getDbConnection()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
//		select decode( a.CONSTRAINT_TYPE, 'P', 'PRIMARY KEY (', 'FOREIGN KEY (' )||b.COLUMN_NAME||')' as consrc,
		$sql =
<<<EOD
		select b.COLUMN_NAME as consrc,
			   a.CONSTRAINT_TYPE as contype
		from ALL_CONSTRAINTS a, ALL_CONS_COLUMNS b
 		where (a.constraint_name = b.constraint_name AND a.table_name = b.table_name AND a.owner = b.owner)
		and	  a.TABLE_NAME = '{$tableName}'
		and   a.OWNER = '{$schemaName}'
		and   a.CONSTRAINT_TYPE in ('P','R')
EOD;
		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		//$command->bindValue(':table', $tableName);
		//$command->bindValue(':schema', $schemaName);
		$primary = [];
		$foreign = [];
		foreach ($command->query() as $row) {
			switch (strtolower($row['contype'])) {
				case 'p':
					$primary = array_merge($primary, [strtolower($row['consrc'])]);
					/*
					$arr = $this->getPrimaryKeys($row['consrc']);
					$primary = array_merge( $primary, array(strtolower( $arr[0] )) );
					*/
					break;
				case 'r':
					$foreign = array_merge($foreign, [strtolower($row['consrc'])]);
					/*
					// if(($fkey = $this->getForeignKeys($row['consrc']))!==null)
					$fkey = $this->getForeignKeys( $row['consrc'] );
					$foreign = array_merge( $foreign, array(strtolower( $fkey )) );
					*/
					break;
			}
		}
		return [$primary, $foreign];
	}

	/**
	 * Gets the primary key field names
	 * @param string $src Oracle primary key definition
	 * @return array primary key field names.
	 */
	protected function getPrimaryKeys($src)
	{
		$matches = [];
		if (preg_match('/PRIMARY\s+KEY\s+\(([^\)]+)\)/i', $src, $matches)) {
			return preg_split('/,\s+/', $matches[1]);
		}
		return [];
	}

	/**
	 * Gets foreign relationship constraint keys and table name
	 * @param string $src Oracle foreign key definition
	 * @return array foreign relationship table name and keys, null otherwise
	 */
	protected function getForeignKeys($src)
	{
		$matches = [];
		$brackets = '\(([^\)]+)\)';
		$find = "/FOREIGN\s+KEY\s+{$brackets}\s+REFERENCES\s+([^\(]+){$brackets}/i";
		if (preg_match($find, $src, $matches)) {
			$keys = preg_split('/,\s+/', $matches[1]);
			$fkeys = [];
			foreach (preg_split('/,\s+/', $matches[3]) as $i => $fkey) {
				$fkeys[$keys[$i]] = $fkey;
			}
			return ['table' => str_replace('"', '', $matches[2]), 'keys' => $fkeys];
		}
	}

	/**
	 * @param string $columnId column name.
	 * @param TOracleTableInfo $tableInfo table information.
	 * @return bool true if column is a foreign key.
	 */
	protected function isForeignKeyColumn($columnId, $tableInfo)
	{
		foreach ($tableInfo->getForeignKeys() as $fk) {
			if ($fk == $columnId) {
				//if(in_array($columnId, array_keys($fk['keys'])))
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
	public function findTableNames($schema = '')
	{
		if ($schema === '') {
			$sql = <<<EOD
SELECT table_name, '{$schema}' as table_schema FROM user_tables
EOD;
			$command = $this->getDbConnection()->createCommand($sql);
		} else {
			$sql = <<<EOD
SELECT object_name as table_name, owner as table_schema FROM all_objects
WHERE object_type = 'TABLE' AND owner=:schema
EOD;
			$command = $this->getDbConnection()->createCommand($sql);
			$command->bindParam(':schema', $schema);
		}

		$rows = $command->queryAll();
		$names = [];
		foreach ($rows as $row) {
			if ($schema === $this->getDefaultSchema() || $schema === '') {
				$names[] = $row['TABLE_NAME'];
			} else {
				$names[] = $row['TABLE_SCHEMA'] . '.' . $row['TABLE_NAME'];
			}
		}
		return $names;
	}
}

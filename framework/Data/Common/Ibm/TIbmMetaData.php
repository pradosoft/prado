<?php

/**
 * TIbmMetaData class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Ibm;

use PDO;
use Prado\Data\Common\TDbMetaData;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TIbmMetaData loads IBM DB2 database table and column information.
 *
 * Requires PDO IBM extension (pdo_ibm). Tested against DB2 LUW 9.7+.
 * Column metadata is retrieved from SYSCAT.COLUMNS; constraints from
 * SYSCAT.KEYCOLUSE, SYSCAT.TABCONST, and SYSCAT.REFERENCES.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TIbmMetaData extends TDbMetaData
{
	private $_defaultSchema = '';

	/**
	 * @return string TDbTableInfo class name.
	 */
	protected function getTableInfoClass()
	{
		return \Prado\Data\Common\Ibm\TIbmTableInfo::class;
	}

	/**
	 * Quotes a table name for use in a query.
	 * @param string $name table name
	 * @return string the properly quoted table name
	 */
	public function quoteTableName($name)
	{
		return parent::quoteTableName($name, '"', '"');
	}

	/**
	 * Quotes a column name for use in a query.
	 * @param string $name column name
	 * @return string the properly quoted column name
	 */
	public function quoteColumnName($name)
	{
		return parent::quoteColumnName($name, '"', '"');
	}

	/**
	 * Quotes a column alias for use in a query.
	 * @param string $name column alias
	 * @return string the properly quoted column alias
	 */
	public function quoteColumnAlias($name)
	{
		return parent::quoteColumnAlias($name, '"', '"');
	}

	/**
	 * @param string $schema default schema name (usually the DB2 user name).
	 */
	public function setDefaultSchema($schema)
	{
		$this->_defaultSchema = strtoupper($schema);
	}

	/**
	 * @return string default schema, resolved from CURRENT SCHEMA if not set.
	 */
	public function getDefaultSchema()
	{
		if ($this->_defaultSchema === '') {
			$this->getDbConnection()->setActive(true);
			$command = $this->getDbConnection()->createCommand('VALUES CURRENT SCHEMA');
			$this->_defaultSchema = strtoupper(trim((string) $command->queryScalar()));
		}
		return $this->_defaultSchema;
	}

	/**
	 * @param string $table table name with optional schema prefix.
	 * @return array tuple ($schemaName, $tableName), both uppercased.
	 */
	protected function getSchemaTableName($table)
	{
		$table = str_replace('"', '', $table);
		if (count($parts = explode('.', $table)) > 1) {
			return [strtoupper($parts[0]), strtoupper($parts[1])];
		}
		return [$this->getDefaultSchema(), strtoupper($parts[0])];
	}

	/**
	 * @param string $name identifier name.
	 * @throws TDbException when the name contains a double-quote character.
	 * @return string validated identifier.
	 */
	protected function assertIdentifier($name)
	{
		if (strpos($name, '"') !== false) {
			throw new TDbException('dbcommon_invalid_identifier_name', $name, 'https://www.ibm.com/docs/en/db2');
		}
		return $name;
	}

	/**
	 * Get the column definitions for given table.
	 * @param string $table table name.
	 * @return TIbmTableInfo table information.
	 */
	protected function createTableInfo($table)
	{
		[$schemaName, $tableName] = $this->getSchemaTableName($table);

		$sql = <<<EOD
			SELECT
				c.COLNO,
				c.COLNAME,
				c.TYPENAME,
				c.LENGTH,
				c.SCALE,
				c.NULLS,
				c.DEFAULT,
				c.IDENTITY,
				c.KEYSEQ
			FROM SYSCAT.COLUMNS c
			WHERE c.TABNAME = :table
				AND c.TABSCHEMA = :schema
			ORDER BY c.COLNO
			EOD;

		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		$command->bindValue(':schema', $schemaName);

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
	 * @param string $schemaName schema name
	 * @param string $tableName table name
	 * @return TIbmTableInfo
	 */
	protected function createNewTableInfo($schemaName, $tableName)
	{
		$info['SchemaName'] = $this->assertIdentifier($schemaName);
		$info['TableName'] = $this->assertIdentifier($tableName);
		$info['IsView'] = $this->getIsView($schemaName, $tableName);
		[$primary, $foreign] = $this->getConstraintKeys($schemaName, $tableName);
		$class = $this->getTableInfoClass();
		return new $class($info, $primary, $foreign);
	}

	/**
	 * @param string $schemaName schema name
	 * @param string $tableName table name
	 * @return bool true if the table is a view.
	 */
	protected function getIsView($schemaName, $tableName)
	{
		$sql = <<<EOD
			SELECT TYPE FROM SYSCAT.TABLES
			WHERE TABNAME = :table AND TABSCHEMA = :schema
			EOD;

		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		$command->bindValue(':schema', $schemaName);
		$type = $command->queryScalar();
		return $type === 'V';
	}

	/**
	 * @param TIbmTableInfo $tableInfo table information.
	 * @param array $col column information from SYSCAT.COLUMNS.
	 */
	protected function processColumn($tableInfo, $col)
	{
		$columnId = strtolower((string) $col['COLNAME']);

		$info['ColumnName'] = '"' . strtoupper($columnId) . '"';
		$info['ColumnId'] = $columnId;
		$info['ColumnIndex'] = (int) $col['index'];

		if ($col['NULLS'] === 'Y') {
			$info['AllowNull'] = true;
		}
		if (trim((string) $col['IDENTITY']) === 'Y') {
			$info['AutoIncrement'] = true;
		}
		if ($col['DEFAULT'] !== null && $col['DEFAULT'] !== '') {
			$info['DefaultValue'] = $col['DEFAULT'];
		}
		if (in_array($columnId, $tableInfo->getPrimaryKeys())) {
			$info['IsPrimaryKey'] = true;
		}
		if ($this->isForeignKeyColumn($columnId, $tableInfo)) {
			$info['IsForeignKey'] = true;
		}

		$info['DbType'] = strtolower((string) $col['TYPENAME']);

		if ($this->isPrecisionType($info['DbType'])) {
			if ($col['LENGTH'] > 0) {
				$info['NumericPrecision'] = (int) $col['LENGTH'];
			}
			if ($col['SCALE'] > 0) {
				$info['NumericScale'] = (int) $col['SCALE'];
			}
		} else {
			if ($col['LENGTH'] > 0) {
				$info['ColumnSize'] = (int) $col['LENGTH'];
			}
		}

		$class = $this->getTableInfoClass();
		$tableInfo->getColumns()[$columnId] = new TIbmTableColumn($info);
	}

	/**
	 * @param string $type DB2 column type name.
	 * @return bool true if this is a numeric precision type.
	 */
	protected function isPrecisionType($type)
	{
		$type = strtolower(trim($type));
		return in_array($type, ['decimal', 'numeric', 'decfloat', 'float', 'double', 'real']);
	}

	/**
	 * Gets the primary and foreign key constraints for the given table.
	 * @param string $schemaName schema name
	 * @param string $tableName table name
	 * @return array tuple ($primary, $foreign)
	 */
	protected function getConstraintKeys($schemaName, $tableName)
	{
		// Primary keys
		$sql = <<<EOD
			SELECT k.COLNAME
			FROM SYSCAT.KEYCOLUSE k
			JOIN SYSCAT.TABCONST t
				ON k.CONSTNAME = t.CONSTNAME
				AND k.TABNAME = t.TABNAME
				AND k.TABSCHEMA = t.TABSCHEMA
			WHERE t.TYPE = 'P'
				AND k.TABNAME = :table
				AND k.TABSCHEMA = :schema
			ORDER BY k.COLSEQ
			EOD;

		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		$command->bindValue(':schema', $schemaName);
		$primary = [];
		foreach ($command->query() as $row) {
			$primary[] = strtolower((string) $row['COLNAME']);
		}

		// Foreign keys
		$sql = <<<EOD
			SELECT
				r.CONSTNAME,
				k.COLNAME,
				r.REFTABNAME,
				rk.COLNAME AS REFCOLNAME
			FROM SYSCAT.REFERENCES r
			JOIN SYSCAT.KEYCOLUSE k
				ON r.CONSTNAME = k.CONSTNAME
				AND r.TABNAME = k.TABNAME
				AND r.TABSCHEMA = k.TABSCHEMA
			JOIN SYSCAT.KEYCOLUSE rk
				ON r.REFKEYNAME = rk.CONSTNAME
				AND r.REFTABSCHEMA = rk.TABSCHEMA
				AND k.COLSEQ = rk.COLSEQ
			WHERE r.TABNAME = :table
				AND r.TABSCHEMA = :schema
			ORDER BY r.CONSTNAME, k.COLSEQ
			EOD;

		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		$command->bindValue(':schema', $schemaName);
		$fkeys = [];
		foreach ($command->query() as $row) {
			$con = $row['CONSTNAME'];
			$fkeys[$con]['keys'][strtolower((string) $row['COLNAME'])] = strtolower((string) $row['REFCOLNAME']);
			$fkeys[$con]['table'] = strtolower((string) $row['REFTABNAME']);
		}
		return [$primary, count($fkeys) > 0 ? array_values($fkeys) : []];
	}

	/**
	 * @param string $columnId column name (lowercased).
	 * @param TIbmTableInfo $tableInfo table information.
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
	 * @param string $schema schema name; defaults to CURRENT SCHEMA.
	 * @return array all table names.
	 */
	public function findTableNames($schema = '')
	{
		if ($schema === '') {
			$schema = $this->getDefaultSchema();
		}
		$sql = <<<EOD
			SELECT TABNAME FROM SYSCAT.TABLES
			WHERE TABSCHEMA = :schema AND TYPE = 'T'
			ORDER BY TABNAME
			EOD;

		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':schema', strtoupper($schema));
		$names = [];
		foreach ($command->query() as $row) {
			if (strtoupper($schema) === $this->getDefaultSchema()) {
				$names[] = strtolower((string) $row['TABNAME']);
			} else {
				$names[] = strtolower($schema . '.' . (string) $row['TABNAME']);
			}
		}
		return $names;
	}
}

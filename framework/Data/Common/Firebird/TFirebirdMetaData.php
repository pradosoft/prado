<?php

/**
 * TFirebirdMetaData class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Firebird;

use PDO;
use Prado\Data\Common\TDbMetaData;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TFirebirdMetaData loads Firebird database table and column information.
 *
 * Requires PDO Firebird extension (pdo_firebird). Tested against Firebird 2.5+.
 * Column metadata is retrieved from RDB$ system tables.
 *
 * Firebird identifier handling: unquoted identifiers are stored and compared
 * as uppercase; double-quoted identifiers are case-sensitive.
 * This implementation normalises all unquoted names to uppercase for system
 * table queries and returns lowercase column/table IDs to PHP.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TFirebirdMetaData extends TDbMetaData
{
	/**
	 * Firebird RDB$FIELD_TYPE codes mapped to SQL type names.
	 * @var array
	 */
	private static $_fieldTypes = [
		7  => 'SMALLINT',
		8  => 'INTEGER',
		10 => 'FLOAT',
		12 => 'DATE',
		13 => 'TIME',
		14 => 'CHAR',
		16 => 'BIGINT',
		17 => 'BOOLEAN',
		18 => 'DECFLOAT(16)',
		19 => 'DECFLOAT(34)',
		23 => 'TIME WITH TIME ZONE',
		24 => 'TIMESTAMP WITH TIME ZONE',
		27 => 'DOUBLE PRECISION',
		35 => 'TIMESTAMP',
		37 => 'VARCHAR',
		261 => 'BLOB',
	];

	/**
	 * @return string TDbTableInfo class name.
	 */
	protected function getTableInfoClass()
	{
		return \Prado\Data\Common\Firebird\TFirebirdTableInfo::class;
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
	 * Firebird has no schema; table name is used directly (uppercased for system queries).
	 * @param string $table table name
	 * @return array tuple (null, $tableName uppercased)
	 */
	protected function getSchemaTableName($table)
	{
		$table = str_replace('"', '', $table);
		return [null, strtoupper($table)];
	}

	/**
	 * Get the column definitions for given table.
	 * @param string $table table name.
	 * @return TFirebirdTableInfo table information.
	 */
	protected function createTableInfo($table)
	{
		[, $tableName] = $this->getSchemaTableName($table);

		$sql = <<<EOD
			SELECT
				rf.RDB\$FIELD_POSITION       AS FIELD_POSITION,
				TRIM(rf.RDB\$FIELD_NAME)     AS FIELD_NAME,
				f.RDB\$FIELD_TYPE            AS FIELD_TYPE,
				f.RDB\$FIELD_LENGTH          AS FIELD_LENGTH,
				f.RDB\$FIELD_PRECISION       AS FIELD_PRECISION,
				f.RDB\$FIELD_SCALE           AS FIELD_SCALE,
				f.RDB\$FIELD_SUB_TYPE        AS FIELD_SUB_TYPE,
				rf.RDB\$NULL_FLAG            AS NULL_FLAG,
				rf.RDB\$DEFAULT_SOURCE       AS DEFAULT_SOURCE,
				rf.RDB\$IDENTITY_TYPE        AS IDENTITY_TYPE
			FROM RDB\$RELATION_FIELDS rf
			JOIN RDB\$FIELDS f ON TRIM(rf.RDB\$FIELD_SOURCE) = TRIM(f.RDB\$FIELD_NAME)
			WHERE TRIM(rf.RDB\$RELATION_NAME) = :table
			ORDER BY rf.RDB\$FIELD_POSITION
			EOD;

		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);

		$tableInfo = $this->createNewTableInfo($tableName);
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
	 * @param string $tableName table name (uppercased).
	 * @return TFirebirdTableInfo
	 */
	protected function createNewTableInfo($tableName)
	{
		$info['TableName'] = $tableName;
		$info['IsView'] = $this->getIsView($tableName);
		[$primary, $foreign] = $this->getConstraintKeys($tableName);
		$class = $this->getTableInfoClass();
		return new $class($info, $primary, $foreign);
	}

	/**
	 * @param string $tableName uppercased table name.
	 * @return bool true if the relation is a view.
	 */
	protected function getIsView($tableName)
	{
		$sql = <<<EOD
			SELECT COUNT(*)
			FROM RDB\$RELATIONS
			WHERE TRIM(RDB\$RELATION_NAME) = :table
				AND RDB\$VIEW_BLR IS NOT NULL
			EOD;

		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		return (int) $command->queryScalar() > 0;
	}

	/**
	 * @param TFirebirdTableInfo $tableInfo table information.
	 * @param array $col row from RDB$RELATION_FIELDS + RDB$FIELDS.
	 */
	protected function processColumn($tableInfo, $col)
	{
		$columnId = strtolower(trim((string) $col['FIELD_NAME']));

		$info['ColumnName'] = '"' . strtoupper($columnId) . '"';
		$info['ColumnId'] = $columnId;
		$info['ColumnIndex'] = (int) $col['index'];

		if (!$col['NULL_FLAG']) {
			$info['AllowNull'] = true;
		}
		// IDENTITY_TYPE: 0 = ALWAYS, 1 = BY DEFAULT (Firebird 3+)
		if ($col['IDENTITY_TYPE'] !== null) {
			$info['AutoIncrement'] = true;
		}
		if ($col['DEFAULT_SOURCE'] !== null) {
			// Strip leading "DEFAULT " keyword from source
			$info['DefaultValue'] = preg_replace('/^\s*DEFAULT\s+/i', '', trim($col['DEFAULT_SOURCE']));
		}
		if (in_array($columnId, $tableInfo->getPrimaryKeys())) {
			$info['IsPrimaryKey'] = true;
		}
		if ($this->isForeignKeyColumn($columnId, $tableInfo)) {
			$info['IsForeignKey'] = true;
		}

		$typeCode = (int) $col['FIELD_TYPE'];
		$subType  = (int) ($col['FIELD_SUB_TYPE'] ?? 0);

		// BLOB sub_type 1 = TEXT, sub_type 0 = binary
		if ($typeCode === 261 && $subType === 1) {
			$info['DbType'] = 'TEXT';
		} else {
			$info['DbType'] = self::$_fieldTypes[$typeCode] ?? 'UNKNOWN(' . $typeCode . ')';
		}

		$precision = (int) $col['FIELD_PRECISION'];
		$scale     = (int) ($col['FIELD_SCALE'] ?? 0);
		$length    = (int) ($col['FIELD_LENGTH'] ?? 0);

		if ($this->isPrecisionType($info['DbType'])) {
			if ($precision > 0) {
				$info['NumericPrecision'] = $precision;
			}
			if ($scale < 0) {
				$info['NumericScale'] = abs($scale); // Firebird stores scale as negative
			}
		} elseif ($length > 0) {
			$info['ColumnSize'] = $length;
		}

		$tableInfo->getColumns()[$columnId] = new TFirebirdTableColumn($info);
	}

	/**
	 * @param string $type SQL type name.
	 * @return bool true if this is a numeric precision type.
	 */
	protected function isPrecisionType($type)
	{
		$type = strtoupper(trim($type));
		return in_array($type, ['DECIMAL', 'NUMERIC', 'DECFLOAT(16)', 'DECFLOAT(34)', 'FLOAT', 'DOUBLE PRECISION']);
	}

	/**
	 * Gets the primary and foreign key constraints for the given table.
	 * @param string $tableName uppercased table name
	 * @return array tuple ($primary, $foreign)
	 */
	protected function getConstraintKeys($tableName)
	{
		// Primary keys
		$sql = <<<EOD
			SELECT TRIM(seg.RDB\$FIELD_NAME) AS FIELD_NAME
			FROM RDB\$RELATION_CONSTRAINTS rc
			JOIN RDB\$INDEX_SEGMENTS seg
				ON TRIM(rc.RDB\$INDEX_NAME) = TRIM(seg.RDB\$INDEX_NAME)
			WHERE rc.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'
				AND TRIM(rc.RDB\$RELATION_NAME) = :table
			ORDER BY seg.RDB\$FIELD_POSITION
			EOD;

		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		$primary = [];
		foreach ($command->query() as $row) {
			$primary[] = strtolower(trim((string) $row['FIELD_NAME']));
		}

		// Foreign keys
		$sql = <<<EOD
			SELECT
				TRIM(rc.RDB\$CONSTRAINT_NAME)  AS CONSTNAME,
				TRIM(seg.RDB\$FIELD_NAME)       AS COLNAME,
				TRIM(rc2.RDB\$RELATION_NAME)    AS REFTABNAME,
				TRIM(seg2.RDB\$FIELD_NAME)      AS REFCOLNAME
			FROM RDB\$RELATION_CONSTRAINTS rc
			JOIN RDB\$INDEX_SEGMENTS seg
				ON TRIM(rc.RDB\$INDEX_NAME) = TRIM(seg.RDB\$INDEX_NAME)
			JOIN RDB\$REF_CONSTRAINTS refcon
				ON TRIM(rc.RDB\$CONSTRAINT_NAME) = TRIM(refcon.RDB\$CONSTRAINT_NAME)
			JOIN RDB\$RELATION_CONSTRAINTS rc2
				ON TRIM(refcon.RDB\$CONST_NAME_UQ) = TRIM(rc2.RDB\$CONSTRAINT_NAME)
			JOIN RDB\$INDEX_SEGMENTS seg2
				ON TRIM(rc2.RDB\$INDEX_NAME) = TRIM(seg2.RDB\$INDEX_NAME)
				AND seg.RDB\$FIELD_POSITION = seg2.RDB\$FIELD_POSITION
			WHERE rc.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'
				AND TRIM(rc.RDB\$RELATION_NAME) = :table
			ORDER BY rc.RDB\$CONSTRAINT_NAME, seg.RDB\$FIELD_POSITION
			EOD;

		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
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
	 * @param TFirebirdTableInfo $tableInfo table information.
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
	 * Returns all user table names in the database.
	 * @param string $schema ignored (Firebird has no schema namespace).
	 * @return array all table names (lowercased).
	 */
	public function findTableNames($schema = '')
	{
		$sql = <<<EOD
			SELECT TRIM(RDB\$RELATION_NAME) AS RELATION_NAME
			FROM RDB\$RELATIONS
			WHERE RDB\$SYSTEM_FLAG = 0
				AND RDB\$VIEW_BLR IS NULL
			ORDER BY RDB\$RELATION_NAME
			EOD;

		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$names = [];
		foreach ($command->query() as $row) {
			$names[] = strtolower((string) $row['RELATION_NAME']);
		}
		return $names;
	}
}

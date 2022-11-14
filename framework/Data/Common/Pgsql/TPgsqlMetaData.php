<?php
/**
 * TPgsqlMetaData class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Pgsql;

/**
 * Load the base TDbMetaData class.
 */
use Prado\Data\Common\TDbMetaData;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TPgsqlMetaData loads PostgreSQL database table and column information.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TPgsqlMetaData extends TDbMetaData
{
	private $_defaultSchema = 'public';

	/**
	 * @return string TDbTableInfo class name.
	 */
	protected function getTableInfoClass()
	{
		return '\Prado\Data\Common\Pgsql\TPgsqlTableInfo';
	}

	/**
	 * Quotes a table name for use in a query.
	 * @param string $name $name table name
	 * @return string the properly quoted table name
	 */
	public function quoteTableName($name)
	{
		return parent::quoteTableName($name, '"', '"');
	}

	/**
	 * Quotes a column name for use in a query.
	 * @param string $name $name column name
	 * @return string the properly quoted column name
	 */
	public function quoteColumnName($name)
	{
		return parent::quoteColumnName($name, '"', '"');
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
	 * @return TPgsqlTableInfo table information.
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
			a.attname,
			pg_catalog.format_type(a.atttypid, a.atttypmod) as type,
			a.atttypmod,
			a.attnotnull, a.atthasdef, pg_get_expr(adef.adbin, adef.adrelid) AS adsrc,
			(
				SELECT 1 FROM pg_catalog.pg_depend pd, pg_catalog.pg_class pc
				WHERE pd.objid=pc.oid
				AND pd.classid=pc.tableoid
				AND pd.refclassid=pc.tableoid
				AND pd.refobjid=a.attrelid
				AND pd.refobjsubid=a.attnum
				AND pd.deptype='i'
				AND pc.relkind='S'
			) IS NOT NULL AS attisserial

		FROM
			pg_catalog.pg_attribute a LEFT JOIN pg_catalog.pg_attrdef adef
			ON a.attrelid=adef.adrelid
			AND a.attnum=adef.adnum
			LEFT JOIN pg_catalog.pg_type t ON a.atttypid=t.oid
		WHERE
			a.attrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname=:table
				AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE
				nspname = :schema))
			AND a.attnum > 0 AND NOT a.attisdropped
		ORDER BY a.attnum
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
	 * @param string $schemaName table schema name
	 * @param string $tableName table name.
	 * @return TPgsqlTableInfo
	 */
	protected function createNewTableInfo($schemaName, $tableName)
	{
		$info['SchemaName'] = $this->assertIdentifier($schemaName);
		$info['TableName'] = $this->assertIdentifier($tableName);
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
			$ref = 'http://www.postgresql.org/docs/7.4/static/sql-syntax.html#SQL-SYNTAX-IDENTIFIERS';
			throw new TDbException('dbcommon_invalid_identifier_name', $name, $ref);
		}
		return $name;
	}

	/**
	 * @param string $schemaName table schema name
	 * @param string $tableName table name.
	 * @return bool true if the table is a view.
	 */
	protected function getIsView($schemaName, $tableName)
	{
		$sql =
<<<EOD
		SELECT count(c.relname) FROM pg_catalog.pg_class c
		LEFT JOIN pg_catalog.pg_namespace n ON (n.oid = c.relnamespace)
		WHERE (n.nspname=:schema) AND (c.relkind = 'v'::"char") AND c.relname = :table
EOD;
		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':schema', $schemaName);
		$command->bindValue(':table', $tableName);
		return (int) ($command->queryScalar()) === 1;
	}

	/**
	 * @param TPgsqlTableInfo $tableInfo table information.
	 * @param array $col column information.
	 */
	protected function processColumn($tableInfo, $col)
	{
		$columnId = $col['attname']; //use column name as column Id

		$info['ColumnName'] = '"' . $columnId . '"'; //quote the column names!
		$info['ColumnId'] = $columnId;
		$info['ColumnIndex'] = $col['index'];
		if (!$col['attnotnull']) {
			$info['AllowNull'] = true;
		}
		if (in_array($columnId, $tableInfo->getPrimaryKeys())) {
			$info['IsPrimaryKey'] = true;
		}
		if ($this->isForeignKeyColumn($columnId, $tableInfo)) {
			$info['IsForeignKey'] = true;
		}

		if ($col['atttypmod'] > 0) {
			$info['ColumnSize'] = $col['atttypmod'] - 4;
		}
		if ($col['atthasdef']) {
			$info['DefaultValue'] = $col['adsrc'];
		}
		if ($col['attisserial'] || substr($col['adsrc'], 0, 8) === 'nextval(') {
			if (($sequence = $this->getSequenceName($tableInfo, $col['adsrc'])) !== null) {
				$info['SequenceName'] = $sequence;
				unset($info['DefaultValue']);
			}
		}
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

		$tableInfo->getColumns()[$columnId] = new TPgsqlTableColumn($info);
	}

	/**
	 * @param TPgsqlTableInfo $tableInfo
	 * @param mixed $src
	 * @return null|string serial name if found, null otherwise.
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
		return null;
	}

	/**
	 * @param mixed $type
	 * @return bool true if column type if "numeric", "interval" or begins with "time".
	 */
	protected function isPrecisionType($type)
	{
		$type = strtolower(trim($type));
		return $type === 'numeric' || $type === 'interval' || strpos($type, 'time') === 0;
	}

	/**
	 * Gets the primary and foreign key column details for the given table.
	 * @param string $schemaName schema name
	 * @param string $tableName table name.
	 * @return array tuple ($primary, $foreign)
	 */
	protected function getConstraintKeys($schemaName, $tableName)
	{
		$sql =
<<<EOD
	SELECT conname, consrc, contype, indkey, indisclustered FROM (
			SELECT
					conname,
					pg_catalog.pg_get_constraintdef(oid) AS consrc,
					CAST(contype AS CHAR),
					conrelid AS relid,
					NULL AS indkey,
					FALSE AS indisclustered
			FROM
					pg_catalog.pg_constraint
			WHERE
					contype IN ('f', 'c')
			UNION ALL
			SELECT
					pc.relname,
					NULL,
					CASE WHEN indisprimary THEN
							'p'
					ELSE
							'u'
					END,
					pi.indrelid,
					indkey,
					pi.indisclustered
			FROM
					pg_catalog.pg_class pc,
					pg_catalog.pg_index pi
			WHERE
					pc.oid=pi.indexrelid
					AND EXISTS (
							SELECT 1 FROM pg_catalog.pg_depend d JOIN pg_catalog.pg_constraint c
							ON (d.refclassid = c.tableoid AND d.refobjid = c.oid)
							WHERE d.classid = pc.tableoid AND d.objid = pc.oid AND d.deptype = 'i' AND c.contype IN ('u', 'p')
			)
	) AS sub
	WHERE relid = (SELECT oid FROM pg_catalog.pg_class WHERE relname=:table
					AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace
					WHERE nspname=:schema))
	ORDER BY
			1
EOD;
		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		$command->bindValue(':schema', $schemaName);
		$primary = [];
		$foreign = [];
		foreach ($command->query() as $row) {
			switch ($row['contype']) {
				case 'p':
					$primary = $this->getPrimaryKeys($tableName, $schemaName, $row['indkey']);
					break;
				case 'f':
					if (($fkey = $this->getForeignKeys($row['consrc'])) !== null) {
						$foreign[] = $fkey;
					}
					break;
			}
		}
		return [$primary, $foreign];
	}

	/**
	 * Gets the primary key field names
	 * @param string $tableName
	 * @param string $schemaName
	 * @param string $columnIndex
	 * @return array primary key field names.
	 */
	protected function getPrimaryKeys($tableName, $schemaName, $columnIndex)
	{
		$index = implode(', ', explode(' ', $columnIndex));
		$sql =
<<<EOD
		SELECT attnum, attname FROM pg_catalog.pg_attribute WHERE
		attrelid=(
			SELECT oid FROM pg_catalog.pg_class WHERE relname=:table AND relnamespace=(
				SELECT oid FROM pg_catalog.pg_namespace WHERE nspname=:schema
			)
		)
				AND attnum IN ({$index})
EOD;
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		$command->bindValue(':schema', $schemaName);
//		$command->bindValue(':columnIndex', join(', ', explode(' ', $columnIndex)));
		$primary = [];
		foreach ($command->query() as $row) {
			$primary[] = $row['attname'];
		}

		return $primary;
	}

	/**
	 * Gets foreign relationship constraint keys and table name
	 * @param string $src pgsql foreign key definition
	 * @return null|array foreign relationship table name and keys, null otherwise
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
		return null;
	}

	/**
	 * @param string $columnId column name.
	 * @param TPgsqlTableInfo $tableInfo table information.
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
	public function findTableNames($schema = 'public')
	{
		if ($schema === '') {
			$schema = $this->_defaultSchema;
		}
		$sql = <<<EOD
SELECT table_name, table_schema FROM information_schema.tables
WHERE table_schema=:schema AND table_type='BASE TABLE'
EOD;
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindParameter(':schema', $schema);
		$rows = $command->query();
		$names = [];
		foreach ($rows as $row) {
			if ($schema === $this->_defaultSchema) {
				$names[] = $row['table_name'];
			} else {
				$names[] = $row['table_schema'] . '.' . $row['table_name'];
			}
		}
		return $names;
	}
}

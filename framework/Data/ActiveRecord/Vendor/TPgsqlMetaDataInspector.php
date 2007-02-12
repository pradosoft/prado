<?php
/**
 * TPgsqlMetaDataInspector class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */

Prado::using('System.Data.ActiveRecord.Vendor.TDbMetaDataInspector');
Prado::using('System.Data.ActiveRecord.Vendor.TPgsqlColumnMetaData');
Prado::using('System.Data.ActiveRecord.Vendor.TPgsqlMetaData');

/**
 * Table meta data inspector for Postgres database 7.3 or later.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
class TPgsqlMetaDataInspector extends TDbMetaDataInspector
{
	private $_schema = 'public';

	/**
	 * @param string default schema.
	 */
	public function setDefaultSchema($schema)
	{
		$this->_schema=$schema;
	}

	/**
	 * @return string default schema.
	 */
	public function getDefaultSchema()
	{
		return $this->_schema;
	}

	/**
	 * Create a new instance of meta data.
	 * @param string table name
	 * @param array column meta data
	 * @param array primary key meta data
	 * @param array foreign key meta data.
	 * @return TDbMetaData table meta data.
	 */
	protected function createMetaData($table, $columns, $primary, $foreign)
	{
		foreach($primary as $column)
			$columns[$column]->setIsPrimaryKey(true);
		return new TPgsqlMetaData($table,$columns,$primary,$foreign,$this->getIsView($table));
	}

	protected function getIsView($table)
	{
		$sql =
<<<EOD
		SELECT count(c.relname) FROM pg_catalog.pg_class c
		LEFT JOIN pg_catalog.pg_namespace n ON (n.oid = c.relnamespace)
		WHERE (n.nspname=:schema) AND (c.relkind = 'v'::"char") AND c.relname = :table
EOD;
		$conn=$this->getDbConnection();
		$conn->setActive(true);
		$command=$conn->createCommand($sql);
		$command->bindValue(':schema',$this->getDefaultSchema());
		$command->bindValue(':table', $table);
		return intval($command->queryScalar()) === 1;
	}

	/**
	 * Get the column definitions for given table.
	 * @param string table name.
	 * @return array column name value pairs of column meta data.
	 */
	protected function getColumnDefinitions($table)
	{
		if(count($parts= explode('.', $table)) > 1)
		{
			$tablename = $parts[1];
			$schema = $parts[0];
		}
		else
		{
			$tablename = $parts[0];
			$schema = $this->getDefaultSchema();
		}
		// This query is made much more complex by the addition of the 'attisserial' field.
		// The subquery to get that field checks to see if there is an internally dependent
		// sequence on the field.
		$sql =
<<<EOD
		SELECT
			a.attname,
			pg_catalog.format_type(a.atttypid, a.atttypmod) as type,
			a.atttypmod,
			a.attnotnull, a.atthasdef, adef.adsrc,
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
		$conn = $this->getDbConnection();
		$conn->setActive(true);
		$command = $conn->createCommand($sql);
		$command->bindValue(':table', $tablename);
		$command->bindValue(':schema', $schema);
		$cols = array();
		foreach($command->query() as $col)
			$cols[strtolower($col['attname'])] = $this->getColumnMetaData($schema,$col);
		return $cols;
	}

	/**
	 * Returns the column details.
	 * @param string schema name.
	 * @param array column details.
	 * @return TPgsqlColumnMetaData column meta data.
	 */
	protected function getColumnMetaData($schema, $col)
	{
		$name = '"'.$col['attname'].'"'; //quote the column names!
		$type = $col['type'];

		// A specific constant in the 7.0 source, the length is offset by 4.
		$length = $col['atttypmod'] > 0 ? $col['atttypmod'] - 4 : null;
		$notNull = $col['attnotnull'];
		$nextval_serial = substr($col['adsrc'],0,8) === 'nextval(';
		$serial = $col['attisserial'] || $nextval_serial ? $this->getSerialName($schema,$col['adsrc']) : null;
		$default = $serial === null && $col['atthasdef'] ? $col['adsrc'] : null;
		return new TPgsqlColumnMetaData(strtolower($col['attname']),$name,
						$type,$length,$notNull,$serial,$default);
	}

	/**
	 * @return string serial name if found, null otherwise.
	 */
	protected function getSerialName($schema,$src)
	{
		$matches = array();
		if(preg_match('/nextval\([^\']*\'([^\']+)\'[^\)]*\)/i',$src,$matches))
		{
			if(is_int(strpos($matches[1], '.')))
				return $matches[1];
			else
				return $schema.'.'.$matches[1];
		}
	}

	/**
	 * Gets the primary and foreign key details for the given table.
	 * @param string table name.
	 * @return array key value pairs with keys 'primary' and 'foreign'.
	 */
	protected function getConstraintKeys($table)
	{
		if(count($parts= explode('.', $table)) > 1)
		{
			$tablename = $parts[1];
			$schema = $parts[0];
		}
		else
		{
			$tablename = $parts[0];
			$schema = $this->getDefaultSchema();
		}

		$sql = 'SELECT
				pg_catalog.pg_get_constraintdef(pc.oid, true) AS consrc,
				pc.contype
			FROM
				pg_catalog.pg_constraint pc
			WHERE
				pc.conrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname=:table
					AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace
					WHERE nspname=:schema))
		';
		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tablename);
		$command->bindValue(':schema', $schema);
		$keys['primary'] = array();
		$keys['foreign'] = array();
		foreach($command->query() as $row)
		{
			if($row['contype']==='p')
				$keys['primary'] = $this->getPrimaryKeys($row['consrc']);
			else if($row['contype'] === 'f')
			{
				$fkey = $this->getForeignKeys($row['consrc']);
				if($fkey!==null)
					$keys['foreign'][] = $fkey;
			}
		}
		return $keys;
	}

	/**
	 * Gets the primary key field names
	 * @param string pgsql primary key definition
	 * @return array primary key field names.
	 */
	protected function getPrimaryKeys($src)
	{
		$matches = array();
		if(preg_match('/PRIMARY\s+KEY\s+\(([^\)]+)\)/i', $src, $matches))
			return preg_split('/,\s+/',$matches[1]);
		return array();
	}

	/**
	 * Gets foreign relationship constraint keys and table name
	 * @param string pgsql foreign key definition
	 * @return array foreign relationship table name and keys, null otherwise
	 */
	protected function getForeignKeys($src)
	{
		$matches = array();
		$brackets = '\(([^\)]+)\)';
		$find = "/FOREIGN\s+KEY\s+{$brackets}\s+REFERENCES\s+([^\(]+){$brackets}/i";
		if(preg_match($find, $src, $matches))
		{
			$keys = preg_split('/,\s+/', $matches[1]);
			$fkeys = array();
			foreach(preg_split('/,\s+/', $matches[3]) as $i => $fkey)
				$fkeys[$keys[$i]] = $fkey;
			return array('table' => $matches[2], 'keys' => $fkeys);
		}
	}
}

?>
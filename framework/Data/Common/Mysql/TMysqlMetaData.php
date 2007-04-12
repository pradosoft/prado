<?php

/**
 * Load the base TDbMetaData class.
 */
Prado::using('System.Data.Common.TDbMetaData');
Prado::using('System.Data.Common.Mysql.TMysqlTableInfo');

class TMysqlMetaData extends TDbMetaData
{
	/**
	 * Get the column definitions for given table.
	 * @param string table name.
	 * @return TMysqlTableInfo table information.
	 */
	protected function createTableInfo($table)
	{
		$this->getDbConnection()->setActive(true);
		$sql = "SHOW FULL FIELDS FROM {$table}";
		$command = $this->getDbConnection()->createCommand($sql);
		$tableInfo = $this->createNewTableInfo($table);
		$index=0;
		foreach($command->query() as $col)
		{
			$col['index'] = $index++;
			$this->processColumn($tableInfo,$col);
		}
		return $tableInfo;
	}

	/**
	 * @param TMysqlTableInfo table information.
	 * @param array column information.
	 */
	protected function processColumn($tableInfo, $col)
	{
		$columnId = $col['Field'];

		$info['ColumnName'] = "`$columnId`"; //quote the column names!
		$info['ColumnIndex'] = $col['index'];
		if($col['Null']!=='NO')
			$info['AllowNull'] = true;
		if(is_int(strpos(strtolower($col['Extra']), 'auto_increment')))
			$info['AutoIncrement']=true;
		if($col['Default']!=="")
			$info['DefaultValue'] = $col['Default'];

		if($col['Key']==='PRI' || in_array($columnId, $tableInfo->getPrimaryKeys()))
			$info['IsPrimaryKey'] = true;
		if($this->isForeignKeyColumn($columnId, $tableInfo))
			$info['IsForeignKey'] = true;
		if(in_array($columnId, $tableInfo->getUniqueKeys()))
			$info['IsUnique'] = true;

		$info['DbType'] = $col['Type'];
		$match=array();
		//find SET/ENUM values, column size, precision, and scale
		if(preg_match('/\((.*)\)/', $col['Type'], $match))
		{
			$info['DbType']= preg_replace('/\(.*\)/', '', $col['Type']);

			//find SET/ENUM values
			if($this->isEnumSetType($info['DbType']))
				$info['DbTypeValues'] = preg_split('/\s*,\s*|\s+/', preg_replace('/\'|"/', '', $match[1]));

			//find column size, precision and scale
			$pscale = array();
			if(preg_match('/(\d+)(?:,(\d+))?+/', $match[1], $pscale))
			{
				if($this->isPrecisionType($info['DbType']))
				{
					$info['NumericPrecision'] = intval($pscale[1]);
					if(count($pscale) > 2)
						$info['NumericScale'] = intval($pscale[2]);
				}
				else
					$info['ColumnSize'] = intval($pscale[1]);
			}
		}

		$tableInfo->Columns[$columnId] = new TMysqlTableColumn($info);
	}

	/**
	 * @return boolean true if column type if "numeric", "interval" or begins with "time".
	 */
	protected function isPrecisionType($type)
	{
		$type = strtolower(trim($type));
		return $type==='decimal' || $type==='dec'
				|| $type==='float' || $type==='double'
				|| $type==='double precision' || $type==='real';
	}

	/**
	 * @return boolean true if column type if "enum" or "set".
	 */
	protected function isEnumSetType($type)
	{
		$type = strtolower(trim($type));
		return $type==='set' || $type==='enum';
	}


	/**
	 * @param string table name, may be quoted with back-ticks and may contain database name.
	 * @return array tuple ($schema,$table), $schema may be null.
	 * @throws TDbException when table name contains invalid identifier bytes.
	 */
	protected function getSchemaTableName($table)
	{
		//remove the back ticks and separate out the "database.table"
		$result = explode('.', str_replace('`', '', $table));
		foreach($result as $name)
		{
			if(!$this->isValidIdentifier($name))
			{
				$ref = 'http://dev.mysql.com/doc/refman/5.0/en/identifiers.html';
				throw new TDbException('dbcommon_invalid_identifier_name', $table, $ref);
			}
		}
		return count($result) > 1 ? $result : array(null, $result[0]);
	}

	/**
	 * http://dev.mysql.com/doc/refman/5.0/en/identifiers.html
	 * @param unknown_type $name
	 */
	protected function isValidIdentifier($name)
	{
		return !preg_match('#/|\\|.|\x00|\xFF#', $name);
	}

	/**
	 * @param string table schema name
	 * @param string table name.
	 * @return TMysqlTableInfo
	 */
	protected function createNewTableInfo($table)
	{
		list($schemaName,$tableName) = $this->getSchemaTableName($table);
		$info['SchemaName'] = $schemaName;
		$info['TableName'] = $tableName;
		$info['IsView'] = $this->getIsView($schemaName,$tableName);
		list($primary, $foreign, $unique) = $this->getConstraintKeys($schemaName, $tableName);
		return new TMysqlTableInfo($info,$primary,$foreign, $unique);
	}

	/**
	 * @param string database name, null to use default connection database.
	 * @param string table or view name.
	 * @return boolean true if is view, false otherwise.
	 * @throws TDbException if table or view does not exist.
	 */
	protected function getIsView($schemaName,$tableName)
	{
		if($schemaName!==null)
			$sql = "SHOW FULL TABLES FROM `{$schemaName}` LIKE :table";
		else
			$sql = 'SHOW FULL TABLES LIKE :table';

		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		try
		{
			return count($result = $command->queryRow()) > 0 && $result['Table_type']==='VIEW';
		}
		catch(TDbException $e)
		{
			$table = $schemaName===null?$tableName:$schemaName.'.'.$tableName;
			throw new TDbException('dbcommon_invalid_table_name',$table,$e->getMessage());
		}
	}

	/**
	 * Gets the primary, foreign key, and unique column details for the given table.
	 * @param string schema name
	 * @param string table name.
	 * @return array tuple ($primary, $foreign, $unique)
	 */
	protected function getConstraintKeys($schemaName, $tableName)
	{
		$table = $schemaName===null ? "`{$tableName}`" : "`{$schemaName}`.`{$tableName}`";
		$sql = "SHOW INDEX FROM {$table}";
		$command = $this->getDbConnection()->createCommand($sql);
		$primary = array();
		$foreign = $this->getForeignConstraints($schemaName,$tableName);
		$unique = array();
		foreach($command->query() as $row)
		{
			if($row['Key_name']==='PRIMARY')
				$primary[] = $row['Column_name'];
			else if(intval($row['Non_unique'])===0)
				$unique[] = $row['Column_name'];
		}
		return array($primary,$foreign,$unique);
	}

	/**
	 * Gets foreign relationship constraint keys and table name
	 * @param string database name
	 * @param string table name
	 * @return array foreign relationship table name and keys.
	 */
	protected function getForeignConstraints($schemaName, $tableName)
	{
		$andSchema = $schemaName !== null ? 'AND TABLE_SCHEMA = :schema' : '';
		$sql = <<<EOD
			SELECT
				CONSTRAINT_NAME as con,
				COLUMN_NAME as col,
				REFERENCED_TABLE_SCHEMA as fkschema,
				REFERENCED_TABLE_NAME as fktable,
				REFERENCED_COLUMN_NAME as fkcol
			FROM
				 `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE`
			WHERE
				REFERENCED_TABLE_NAME IS NOT NULL
				AND TABLE_NAME = :table
				$andSchema
EOD;
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		if($schemaName!==null)
			$command->bindValue(':schema', $schemaName);
		$fkeys=array();
		foreach($command->query() as $col)
		{
			$fkeys[$col['con']]['keys'][$col['col']] = $col['fkcol'];
			$fkeys[$col['con']]['table'] = "`{$col['fkschema']}`.`{$col['fktable']}`";
		}
		return array_values($fkeys);
	}

	/**
	 * @param string column name.
	 * @param TPgsqlTableInfo table information.
	 * @return boolean true if column is a foreign key.
	 */
	protected function isForeignKeyColumn($columnId, $tableInfo)
	{
		foreach($tableInfo->getForeignKeys() as $fk)
		{
			if(in_array($columnId, array_keys($fk['keys'])))
				return true;
		}
		return false;
	}
}

?>
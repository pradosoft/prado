<?php

/**
 * IDbMetaData interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

/**
 * IDbMetaData interface
 *
 * Discovery marker for metadata classes that perform SQL identifier quoting.
 * Driver-agnostic schema introspection lives on the parent {@see IDataMetaData}.
 * Implemented by {@see TDbMetaData}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbMetaData extends IDataMetaData
{
	/**
	 * @param string $name the table name.
	 * @return string the SQL-quoted table name.
	 */
	public function quoteTableName($name);

	/**
	 * @param string $name the column name.
	 * @return string the SQL-quoted column name.
	 */
	public function quoteColumnName($name);

	/**
	 * @param string $name the column alias.
	 * @return string the SQL-quoted column alias (`alias` in `AS alias`).
	 */
	public function quoteColumnAlias($name);
}

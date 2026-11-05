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
 * All driver-agnostic schema introspection lives on the parent
 * {@see IDataMetaData}; this sub-interface adds the quoting helpers needed by
 * code that composes SQL text.
 *
 * Implemented by {@see TDbMetaData}.  Mirrors the
 * {@see \Prado\Data\IDataConnection} / {@see \Prado\Data\IDbConnection}
 * discovery-marker pattern.
 *
 * Optional SQL/PDO discovery marker — see {@see \Prado\Data\IDataConnection} for the full driver-portability roadmap.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbMetaData extends IDataMetaData
{
	/**
	 * Quotes a table name for use in SQL queries.
	 * @param string $name the table name to quote.
	 * @return string the properly quoted table name.
	 */
	public function quoteTableName($name);

	/**
	 * Quotes a column name for use in SQL queries.
	 * @param string $name the column name to quote.
	 * @return string the properly quoted column name.
	 */
	public function quoteColumnName($name);

	/**
	 * Quotes a column alias for use in SQL queries (the `alias` in `AS alias`).
	 * @param string $name the column alias to quote.
	 * @return string the properly quoted column alias.
	 */
	public function quoteColumnAlias($name);
}

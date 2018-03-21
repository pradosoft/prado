<?php
/**
 * IMappedStatement interface file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Statements
 */

namespace Prado\Data\SqlMap\Statements;

/**
 * Interface for all mapping statements.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Statements
 * @since 3.1
 */
interface IMappedStatement
{
	/**
	 * @return string Name used to identify the MappedStatement amongst the others.
	 */
	public function getID();

	/**
	 * @return TSqlMapStatement The SQL statment used by this TMappedStatement.
	 */
	public function getStatement();

	/**
	 * @return TSqlMap The TSqlMap used by this TMappedStatement
	 */
	public function getManager();

	/**
	 * Executes the SQL and retuns all rows selected in a map that is keyed on
	 * the property named in the <tt>$keyProperty</tt> parameter.  The value at
	 * each key will be the value of the property specified  in the
	 * <tt>$valueProperty</tt> parameter.  If <tt>$valueProperty</tt> is
	 * <tt>null</tt>, the entire result object will be entered.
	 * @param IDbConnection $connection database connection to execute the query
	 * @param mixed $parameter The object used to set the parameters in the SQL.
	 * @param string $keyProperty The property of the result object to be used as the key.
	 * @param string $valueProperty The property of the result object to be used as the value (or null)
	 * @return TMap A map of object containing the rows keyed by <tt>$keyProperty</tt>.
	 */
	public function executeQueryForMap($connection, $parameter, $keyProperty, $valueProperty = null);


	/**
	 * Execute an update statement. Also used for delete statement. Return the
	 * number of row effected.
	 * @param IDbConnection $connection database connection to execute the query
	 * @param mixed $parameter The object used to set the parameters in the SQL.
	 * @return int The number of row effected.
	 */
	public function executeUpdate($connection, $parameter);


	/**
	 * Executes the SQL and retuns a subset of the rows selected.
	 * @param IDbConnection $connection database connection to execute the query
	 * @param mixed $parameter The object used to set the parameters in the SQL.
	 * @param null|TList $result A list to populate the result with.
	 * @param int $skip The number of rows to skip over.
	 * @param int $max The maximum number of rows to return.
	 * @return TList A TList of result objects.
	 */
	public function executeQueryForList($connection, $parameter, $result = null, $skip = -1, $max = -1);


	/**
	 * Executes an SQL statement that returns a single row as an object
	 * of the type of the <tt>$result</tt> passed in as a parameter.
	 * @param IDbConnection $connection database connection to execute the query
	 * @param mixed $parameter The object used to set the parameters in the SQL.
	 * @param object $result The result object.
	 * @return object result.
	 */
	public function executeQueryForObject($connection, $parameter, $result = null);
}

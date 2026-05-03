<?php

/**
 * TOracleDbCommand class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Oracle;

use Exception;
use PDO;
use Prado\Data\TDbCommand;
use Prado\Data\TDbDataReader;
use Prado\Exceptions\TDbException;

/**
 * TOracleDbCommand is a {@see TDbCommand} specialisation for Oracle (pdo_oci)
 * connections.
 *
 * PHP 8.2 pdo_oci has a known bug: calling {@see PDOStatement::prepare()} or
 * {@see PDOStatement::bindParam()} on an oci connection can trigger a
 * process-level segfault.  The workaround is to skip the prepared-statement
 * path entirely and substitute bound values directly into the SQL text via
 * {@see PDO::quote()} at execution time.
 *
 * This class accumulates parameters bound via {@see bindParameter()} /
 * {@see bindValue()} in an internal array ({@see $_ociParams}), then
 * {@see buildOciSql()} substitutes them at execution time using
 * {@see PDO::quote()} before delegating to {@see PDO::query()} /
 * {@see PDO::exec()}.
 *
 * {@see TDbConnection::createCommand()} returns an instance of this class
 * automatically for pdo_oci connections.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TOracleDbCommand extends TDbCommand
{
	/**
	 * @var array<int|string,mixed> Parameter values accumulated via
	 * {@see bindParameter} or {@see bindValue}.  Keys are either named
	 * placeholders (`:name`) or 1-based integer positions for `?` placeholders.
	 */
	private array $_ociParams = [];

	// -----------------------------------------------------------------------
	// Serialization — exclude runtime-only state
	// -----------------------------------------------------------------------

	/**
	 * Exclude the accumulated OCI parameters from serialization; they are
	 * always empty at the start of a new request.
	 */
	public function __sleep()
	{
		return array_diff(parent::__sleep(), ["\0TOracleDbCommand\0_ociParams"]);
	}

	// -----------------------------------------------------------------------
	// Lifecycle
	// -----------------------------------------------------------------------

	/**
	 * {@inheritdoc}
	 * Also resets accumulated OCI parameter bindings.
	 */
	public function cancel()
	{
		$this->_ociParams = [];
		parent::cancel();
	}

	// -----------------------------------------------------------------------
	// OCI SQL builder
	// -----------------------------------------------------------------------

	/**
	 * Builds the final SQL string by substituting bound values via
	 * {@see PDO::quote()}, bypassing the prepared-statement path entirely.
	 *
	 * Returns null when no parameters have been accumulated (i.e. the caller
	 * is executing a plain SQL string with no bound parameters), in which case
	 * the standard {@see PDO::query()} / {@see PDO::exec()} path is used
	 * without parameter substitution.
	 *
	 * Both positional (`?`) and named (`:name`) placeholders are supported.
	 * NULL values are rendered as the literal SQL NULL.
	 *
	 * @return null|string Substituted SQL ready for direct execution, or null
	 *                     if no parameters have been bound.
	 */
	private function buildOciSql(): ?string
	{
		if ($this->_ociParams === []) {
			return null;
		}
		$pdo = $this->getConnection()->getPdoInstance();
		$sql = $this->getText();
		$firstKey = array_key_first($this->_ociParams);
		if (is_int($firstKey)) {
			// Positional '?' placeholders — replace left-to-right.
			$values = array_values($this->_ociParams);
			$i = 0;
			$sql = preg_replace_callback('/\?/', static function () use ($pdo, $values, &$i) {
				$value = $values[$i++] ?? null;
				return $value === null ? 'NULL' : $pdo->quote((string) $value);
			}, $sql);
		} else {
			// Named placeholders (:name) — substitute by name.
			foreach ($this->_ociParams as $placeholder => $value) {
				$quoted = $value === null ? 'NULL' : $pdo->quote((string) $value);
				$sql = str_replace((string) $placeholder, $quoted, $sql);
			}
		}
		return $sql;
	}

	// -----------------------------------------------------------------------
	// Parameter binding — accumulate instead of preparing
	// -----------------------------------------------------------------------

	/**
	 * {@inheritdoc}
	 *
	 * For pdo_oci the value is captured at bind time and substituted into the
	 * SQL via {@see PDO::quote()} at execution time, avoiding the PHP 8.2
	 * pdo_oci segfault that occurs in the prepared-statement path.
	 */
	public function bindParameter($name, &$value, $dataType = null, $length = null)
	{
		$this->_ociParams[$name] = $value;
	}

	/**
	 * {@inheritdoc}
	 *
	 * For pdo_oci the value is captured here and substituted into the SQL via
	 * {@see PDO::quote()} at execution time.
	 */
	public function bindValue($name, $value, $dataType = null)
	{
		$this->_ociParams[$name] = $value;
	}

	// -----------------------------------------------------------------------
	// Execution
	// -----------------------------------------------------------------------

	/**
	 * {@inheritdoc}
	 *
	 * When parameters have been accumulated via {@see bindParameter} /
	 * {@see bindValue}, the SQL is built via {@see buildOciSql()} and executed
	 * with {@see PDO::exec()}.  Otherwise the base implementation is used.
	 */
	public function execute()
	{
		if (($ociSql = $this->buildOciSql()) !== null) {
			try {
				return $this->getConnection()->getPdoInstance()->exec($ociSql);
			} catch (Exception $e) {
				throw new TDbException('dbcommand_execute_failed', $e->getMessage(), $this->getDebugStatementText());
			}
		}
		return parent::execute();
	}

	/**
	 * {@inheritdoc}
	 *
	 * When parameters have been accumulated the SQL is built via
	 * {@see buildOciSql()} and executed with {@see PDO::query()}, assigning
	 * the resulting {@see PDOStatement} so that {@see TDbDataReader} can
	 * consume it.  Otherwise the base implementation is used.
	 */
	public function query(): TDbDataReader
	{
		if (($ociSql = $this->buildOciSql()) !== null) {
			try {
				$this->_statement = $this->getConnection()->getPdoInstance()->query($ociSql);
				return new TDbDataReader($this);
			} catch (Exception $e) {
				throw new TDbException('dbcommand_query_failed', $e->getMessage(), $this->getDebugStatementText());
			}
		}
		return parent::query();
	}

	/**
	 * {@inheritdoc}
	 *
	 * When parameters have been accumulated, builds the OCI SQL, executes it
	 * with {@see PDO::query()}, fetches the first row, and closes the cursor.
	 * Otherwise the base implementation is used.
	 */
	public function queryRow($fetchAssociative = true)
	{
		if (($ociSql = $this->buildOciSql()) !== null) {
			try {
				$stmt = $this->getConnection()->getPdoInstance()->query($ociSql);
				$result = $stmt->fetch($fetchAssociative ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
				$stmt->closeCursor();
				return $result;
			} catch (Exception $e) {
				throw new TDbException('dbcommand_query_failed', $e->getMessage(), $this->getDebugStatementText());
			}
		}
		return parent::queryRow($fetchAssociative);
	}

	/**
	 * {@inheritdoc}
	 *
	 * When parameters have been accumulated, builds the OCI SQL, executes it
	 * with {@see PDO::query()}, fetches the first column of the first row, and
	 * closes the cursor.  Otherwise the base implementation is used.
	 */
	public function queryScalar()
	{
		if (($ociSql = $this->buildOciSql()) !== null) {
			try {
				$stmt = $this->getConnection()->getPdoInstance()->query($ociSql);
				$result = $stmt->fetchColumn();
				$stmt->closeCursor();
				if (is_resource($result) && get_resource_type($result) === 'stream') {
					return stream_get_contents($result);
				}
				return $result;
			} catch (Exception $e) {
				throw new TDbException('dbcommand_query_failed', $e->getMessage(), $this->getDebugStatementText());
			}
		}
		return parent::queryScalar();
	}
}

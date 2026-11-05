<?php

/**
 * IDataCommandBuilder interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use Prado\Data\DataGateway\IDataCriteria;
use Prado\Data\IDataCommand;
use Prado\Data\IDataConnection;

/**
 * IDataCommandBuilder interface
 *
 * IDataCommandBuilder defines the contract for building CRUD commands against a
 * specific table. Implementations are returned by
 * {@see IDataMetaData::createCommandBuilder()} and used throughout the Active
 * Record and DataGateway layers.
 *
 * The interface is shaped after {@see TDbCommandBuilder}, which is the canonical
 * SQL implementation, but is intentionally decoupled from it so that application
 * code and third-party PRADO plugins can supply entirely custom builders (e.g.
 * for NoSQL or time-series data stores) without inheriting from the SQL class
 * hierarchy.
 *
 * All method signatures are SQL-centric: WHERE clause strings, column-name →
 * value parameter arrays, column-name → direction ordering arrays, integer
 * limit/offset values, and a column-select descriptor.  The portable criteria
 * methods, which take an {@see \Prado\Data\DataGateway\IDataCriteria}, are
 * inherited from {@see IDataCriteriaBuilder}.
 *
 * Concrete implementations: {@see TDbCommandBuilder} and its driver-specific
 * subclasses ({@see TMysqlCommandBuilder}, {@see TSqliteCommandBuilder},
 * {@see TPgsqlCommandBuilder}, {@see TSqlSrvCommandBuilder},
 * {@see TOracleCommandBuilder}, {@see TIbmCommandBuilder},
 * {@see TFirebirdCommandBuilder}).
 *
 * Required for portability — see {@see \Prado\Data\IDataConnection} for the full driver-portability roadmap.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataCommandBuilder extends IDataCriteriaBuilder
{
	// -----------------------------------------------------------------------
	// Accessors
	// -----------------------------------------------------------------------

	/**
	 * @return IDataConnection the connection this builder operates on.
	 */
	public function getDbConnection();

	/**
	 * @return IDataTableInfo the table metadata this builder targets.
	 */
	public function getTableInfo();

	/**
	 * Binds the table (collection) metadata this builder targets.  Called by
	 * the ActiveRecord gateway to re-point a cached builder at the current
	 * record's table.
	 * @param IDataTableInfo $value the table metadata.
	 * @since 4.4.0
	 */
	public function setTableInfo($value);

	/**
	 * Returns the last inserted ID for the table, using any sequence column
	 * defined in the table metadata.
	 *
	 * @return mixed the last inserted ID or sequence value, or null if the table
	 *   has no sequence column.
	 */
	public function getLastInsertID();

	// -----------------------------------------------------------------------
	// Command factories
	//
	// SQL-string composition helpers (applyLimitOffset, applyOrdering,
	// applyCriterias, getSearchExpression, getSelectFieldList) live on the
	// {@see IDbCommandBuilder} sub-interface.
	// -----------------------------------------------------------------------

	/**
	 * Creates a SELECT command for the table.
	 *
	 * @param string $where WHERE clause (without the keyword); defaults to '1=1'.
	 * @param array $parameters name-value pairs to bind.
	 * @param array $ordering column → direction pairs.
	 * @param int $limit maximum rows; negative means no limit.
	 * @param int $offset rows to skip; negative means no offset.
	 * @param string $select columns to select; '*' means all columns.
	 * @return IDataCommand the SELECT command.
	 */
	public function createFindCommand($where = '1=1', $parameters = [], $ordering = [], $limit = -1, $offset = -1, $select = '*');

	/**
	 * Creates a COUNT(*) command for the table.
	 *
	 * @param string $where WHERE clause; defaults to '1=1'.
	 * @param array $parameters name-value pairs to bind.
	 * @param array $ordering column → direction pairs.
	 * @param int $limit maximum rows; negative means no limit.
	 * @param int $offset rows to skip; negative means no offset.
	 * @return IDataCommand the COUNT command.
	 */
	public function createCountCommand($where = '1=1', $parameters = [], $ordering = [], $limit = -1, $offset = -1);

	/**
	 * Creates an INSERT command for the table.
	 *
	 * @param array $data column-name → value pairs to insert.
	 * @return IDataCommand the INSERT command.
	 */
	public function createInsertCommand($data);

	/**
	 * Creates an INSERT OR IGNORE command for the table.
	 *
	 * The base implementation throws {@see TDbException}; driver-specific
	 * subclasses that support this operation must override this method.
	 *
	 * @param array $data column-name → value pairs to insert.
	 * @return IDataCommand the INSERT OR IGNORE command.
	 */
	public function createInsertOrIgnoreCommand(array $data): IDataCommand;

	/**
	 * Creates an UPSERT (INSERT … ON CONFLICT … UPDATE) command for the table.
	 *
	 * The base implementation throws {@see TDbException}; driver-specific
	 * subclasses that support this operation must override this method.
	 *
	 * @param array $data column-name → value pairs to insert.
	 * @param null|array $updateData column → value pairs to use on conflict; null
	 *   means all non-primary-key columns from $data.
	 * @param null|array $conflictColumns columns that define the conflict target;
	 *   null means the table's primary key columns.
	 * @return IDataCommand the UPSERT command.
	 */
	public function createUpsertCommand(array $data, ?array $updateData = null, ?array $conflictColumns = null): IDataCommand;

	/**
	 * Creates an UPDATE command for the table.
	 *
	 * @param array $data column-name → value pairs to set.
	 * @param string $where WHERE clause identifying rows to update.
	 * @param array $parameters additional name-value pairs to bind for the WHERE clause.
	 * @return IDataCommand the UPDATE command.
	 */
	public function createUpdateCommand($data, $where, $parameters = []);

	/**
	 * Creates a DELETE command for the table.
	 *
	 * @param string $where WHERE clause identifying rows to delete.
	 * @param array $parameters name-value pairs to bind.
	 * @return IDataCommand the DELETE command.
	 */
	public function createDeleteCommand($where, $parameters = []);

	// -----------------------------------------------------------------------
	// Utilities
	// -----------------------------------------------------------------------

	/**
	 * Creates a raw SQL command on the underlying connection.
	 *
	 * @param string $sql the SQL statement.
	 * @return IDataCommand the new command.
	 */
	public function createCommand($sql);

	/**
	 * Binds column-name → value pairs to a command, using each column's native type.
	 *
	 * @param IDataCommand $command the command to bind into.
	 * @param array $values column-name → value pairs.
	 */
	public function bindColumnValues($command, $values);

	/**
	 * Binds an array of values (positional or named) to a command.
	 *
	 * @param IDataCommand $command the command to bind into.
	 * @param array $values positional values or name → value pairs.
	 */
	public function bindArrayValues($command, $values);

	// -----------------------------------------------------------------------
	// Driver-agnostic factories (4.3.3+).  Each driver consumes the portable
	// fields of {@see IDataCriteria} plus its own native predicate (SQL
	// condition, Mongo filter doc, etc.).  Used by DataGateway and
	// ActiveRecord so non-SQL drivers can substitute transparently.
	// -----------------------------------------------------------------------

	/**
	 * @return bool whether the builder's table (collection) exists in the
	 *   underlying store.
	 * @since 4.4.0
	 */
	public function tableExists(): bool;

}

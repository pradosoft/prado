<?php

/**
 * IDbHasSchema interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

/**
 * IDbHasSchema is a marker interface for database table-info classes whose
 * underlying database engine supports the concept of a schema (also called an
 * owner or namespace that groups tables within a database).
 *
 * Drivers that implement this interface: MySQL, PostgreSQL, MSSQL, IBM DB2, Oracle.
 * Drivers that do NOT: SQLite, Firebird (neither has a schema namespace).
 *
 * TDbTableInfo::getSchemaName() returns a non-null value only when the concrete
 * class implements this interface; for schema-less drivers it returns null.
 *
 * The interface is intentionally empty — it serves as a capability declaration
 * rather than a method contract, following the marker-interface pattern used
 * elsewhere in the framework (e.g. IDbModule).  Future NoSQL metadata classes
 * may introduce analogous markers (IDbHasKeyspace, IDbHasCollection, etc.)
 * following the same convention.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbHasSchema
{
}

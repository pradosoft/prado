<?php

/**
 * IDataSchemalessTableInfo interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

/**
 * IDataSchemalessTableInfo interface
 *
 * Optional capability of a table-info object from a data store without a fixed
 * column structure, such as a MongoDB collection.  When {@see isSchemaless()}
 * returns true, the column metadata of the table info is not authoritative, and
 * the record's own properties define the persisted fields.  The prado-activerecord
 * package's `TActiveRecordGateway` checks this capability when it builds insert and
 * update values.
 *
 * | Table info | Implements | Field set |
 * |---|---|---|
 * | SQL drivers ({@see TDbTableInfo}) | no | the table-info column list |
 * | Schemaless store reporting `isSchemaless()` true | yes | the record's own properties |
 *
 * This capability differs from {@see IDataHasSchema}: a table info without
 * `IDataHasSchema` (SQLite, Firebird) has fixed columns and only lacks a schema
 * namespace.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IDataSchemalessTableInfo
{
	/**
	 * Returns whether the table or collection has no authoritative column metadata.
	 * @return bool true when the record's own properties define the persisted fields.
	 */
	public function isSchemaless(): bool;
}

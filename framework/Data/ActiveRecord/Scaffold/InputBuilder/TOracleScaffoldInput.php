<?php

/**
 * TOracleScaffoldInput class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

/**
 * TOracleScaffoldInput class.
 *
 * Maps Oracle column types (as reported by ALL_TAB_COLUMNS.DATA_TYPE) to
 * appropriate Prado scaffold input controls.
 *
 * Oracle type notes:
 *  - NUMBER appears with a precision/scale suffix from the metadata query,
 *    e.g. 'NUMBER(10,2)' or 'NUMBER(38,0)'.  The prefix match handles all
 *    variants; integer-scale (,0) columns are mapped to integer controls and
 *    all others to float.
 *  - DATE in Oracle stores both date and time components (year, month, day,
 *    hour, minute, second); it is mapped to a datetime control.
 *  - TIMESTAMP variants (with/without time zone, with local time zone) all
 *    carry a datetime value and are mapped to datetime controls.
 *  - INTERVAL types have no generic scalar input and fall through to the
 *    default text control.
 *  - CLOB / NCLOB / LONG are mapped to multiline text controls.
 *  - BLOB / RAW / LONG RAW / BFILE are binary types; the default text control
 *    is used as a placeholder (binary data is not editable in a scaffold).
 *  - XMLTYPE falls through to the default text control.
 *  - ROWID / UROWID fall through to the default text control.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TOracleScaffoldInput extends TScaffoldInputCommon
{
	protected function createControl($container, $column, $record)
	{
		$type = strtoupper($column->getDbType());

		// NUMBER(p,0) or NUMBER(p) — integer-scale, treat as integer.
		// NUMBER(p,s) with s > 0, or bare NUMBER — treat as float.
		if (str_starts_with($type, 'NUMBER')) {
			if (preg_match('/NUMBER\s*\(\s*\d+\s*,\s*0\s*\)/', $type)
				|| preg_match('/NUMBER\s*\(\s*\d+\s*\)/', $type)) {
				return $this->createIntegerControl($container, $column, $record);
			}
			return $this->createFloatControl($container, $column, $record);
		}

		switch ($type) {
			// ---- integer types --------------------------------------------------
			case 'INTEGER':          // alias for NUMBER(38,0)
			case 'INT':
			case 'SMALLINT':
				return $this->createIntegerControl($container, $column, $record);

				// ---- float / decimal types ------------------------------------------
			case 'FLOAT':            // FLOAT(p) — binary-precision float
			case 'BINARY_FLOAT':     // 32-bit IEEE 754
			case 'BINARY_DOUBLE':    // 64-bit IEEE 754
			case 'REAL':             // alias for FLOAT(63)
			case 'DECIMAL':
			case 'NUMERIC':
				return $this->createFloatControl($container, $column, $record);

				// ---- date / time types ----------------------------------------------
				// Oracle DATE holds year-month-day + hour-minute-second.
			case 'DATE':
				return $this->createDateTimeControl($container, $column, $record);

				// TIMESTAMP, TIMESTAMP WITH TIME ZONE, TIMESTAMP WITH LOCAL TIME ZONE
				// — prefix match covers all three variants plus optional (p) suffix.
			default:
				if (str_starts_with($type, 'TIMESTAMP')) {
					return $this->createDateTimeControl($container, $column, $record);
				}
				// INTERVAL YEAR TO MONTH, INTERVAL DAY TO SECOND — fall through.
				// FLOAT(p) with explicit precision also reaches here via the switch
				// default; the prefix match below catches it.
				if (str_starts_with($type, 'FLOAT')) {
					return $this->createFloatControl($container, $column, $record);
				}
				break;
		}

		switch ($type) {
			// ---- character / large-object types ---------------------------------
			case 'CHAR':
			case 'NCHAR':
			case 'VARCHAR2':
			case 'NVARCHAR2':
			case 'VARCHAR':          // synonym for VARCHAR2
				return $this->createDefaultControl($container, $column, $record);

			case 'CLOB':
			case 'NCLOB':
			case 'LONG':
				return $this->createMultiLineControl($container, $column, $record);

				// ---- binary / opaque types — not editable in a scaffold -------------
			case 'BLOB':
			case 'RAW':
			case 'LONG RAW':
			case 'BFILE':
			case 'XMLTYPE':
			case 'ROWID':
			case 'UROWID':
			default:
				return $this->createDefaultControl($container, $column, $record);
		}
	}

	protected function getControlValue($container, $column, $record)
	{
		$type = strtoupper($column->getDbType());

		if (str_starts_with($type, 'TIMESTAMP') || $type === 'DATE') {
			return $this->getDateTimeValue($container, $column, $record);
		}

		return $this->getDefaultControlValue($container, $column, $record);
	}
}

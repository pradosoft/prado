<?php

/**
 * TTestLockCommand class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Util\Mutex;

use Prado\Data\IDataCommand;

/**
 * TTestLockCommand is an {@see IDataCommand} that records its SQL and bound values
 * and returns scripted results, for testing the database mutexes without a server.
 *
 * {@see queryScalar()} returns {@see $scalar}, {@see execute()} returns
 * {@see $executeResult}, and {@see bindParameter()} writes {@see $output} to the
 * bound variable when it is not null, as an output parameter would.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestLockCommand implements IDataCommand
{
	/** @var array<string, mixed> the bound values by parameter name. */
	public array $bound = [];

	/** @var array<string, mixed> the data type of each bound parameter. */
	public array $types = [];

	/** @var int how many times the command ran. */
	public int $runs = 0;

	/**
	 * @param string $sql the SQL of the command.
	 * @param mixed $scalar the result of queryScalar().
	 * @param mixed $output the value written to a bound output parameter; null writes none.
	 * @param int $executeResult the result of execute().
	 */
	public function __construct(
		public string $sql,
		public mixed $scalar = null,
		public mixed $output = null,
		public int $executeResult = 0
	) {
	}

	public function getConnection()
	{
		return null;
	}

	public function execute()
	{
		$this->runs++;
		return $this->executeResult;
	}

	public function query()
	{
		$this->runs++;
		return null;
	}

	public function queryRow($fetchAssociative = true)
	{
		$this->runs++;
		return false;
	}

	public function queryScalar()
	{
		$this->runs++;
		return $this->scalar;
	}

	public function queryColumn()
	{
		$this->runs++;
		return [];
	}

	public function queryAll()
	{
		$this->runs++;
		return [];
	}

	public function getColumnTypeFromValue($value)
	{
		return null;
	}

	public function bindValue($name, $value, $dataType = null)
	{
		$this->bound[$name] = $value;
		$this->types[$name] = $dataType;
	}

	public function bindParameter($name, &$value, $dataType = null, $length = null)
	{
		$this->bound[$name] = $value;
		$this->types[$name] = $dataType;
		if ($this->output !== null) {
			$value = $this->output;
		}
	}
}

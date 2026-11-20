<?php

namespace Prado\Test\Unit\Email\Store;

use Prado\Data\TDbConnection;
use Prado\Email\Store\TDbEmailStore;
use Prado\Util\Clock\TMockClock;

/**
 * A TDbEmailStore on an in-memory SQLite database with a clock the test sets.
 */
class TSqliteEmailStore extends TDbEmailStore
{
	/** @var TMockClock the clock of the store. */
	public TMockClock $clock;

	public function __construct(int $time = 1700000000)
	{
		parent::__construct();
		$this->clock = (new TMockClock())->setTime($time);
		$this->setClock($this->clock);
	}

	protected function getCustomDbConnection(): ?TDbConnection
	{
		return new TDbConnection('sqlite::memory:');
	}

	/**
	 * @return array the rows of a table, by its name without the prefix.
	 */
	public function rows(string $table): array
	{
		return $this->queryAll('SELECT * FROM ' . $this->getTableName($table));
	}
}

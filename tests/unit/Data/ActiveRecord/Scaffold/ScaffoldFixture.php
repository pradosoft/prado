<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Data\TDbConnection;

/**
 * In-memory SQLite database of the scaffold control tests.
 *
 * The connection lives for the whole test run, so the record gateway's table information
 * cache, keyed by connection string and table name, stays valid. {@see reset()} restores
 * the twelve `scaffold_items` rows before each test.
 */
class ScaffoldFixture
{
	public const ROWS = 12;

	private static ?TDbConnection $_connection = null;

	public static function getConnection(): TDbConnection
	{
		if (self::$_connection === null) {
			$connection = new TDbConnection('sqlite::memory:');
			$connection->setActive(true);
			$connection->createCommand(
				'CREATE TABLE scaffold_items (
					id INTEGER PRIMARY KEY,
					item_name VARCHAR(20) NOT NULL,
					notes TEXT,
					price DECIMAL(6,2)
				)'
			)->execute();
			self::$_connection = $connection;
		}
		return self::$_connection;
	}

	/**
	 * Restores the rows: id 1..12, item_name "item 01".."item 12", notes "note N" on even
	 * ids and null on odd ids, price N.50.
	 */
	public static function reset(): void
	{
		$connection = self::getConnection();
		$connection->createCommand('DELETE FROM scaffold_items')->execute();
		for ($i = 1; $i <= self::ROWS; $i++) {
			$command = $connection->createCommand('INSERT INTO scaffold_items (id, item_name, notes, price) VALUES (:id, :name, :notes, :price)');
			$command->bindValue(':id', $i);
			$command->bindValue(':name', sprintf('item %02d', $i));
			$command->bindValue(':notes', $i % 2 === 0 ? "note {$i}" : null);
			$command->bindValue(':price', $i . '.50');
			$command->execute();
		}
	}

	public static function names(string $condition = '1=1', string $order = 'id'): array
	{
		return self::getConnection()
			->createCommand("SELECT item_name FROM scaffold_items WHERE {$condition} ORDER BY {$order}")
			->queryColumn();
	}
}

<?php

namespace Prado\Test\Unit\Data\DbSpecific\Oracle\Common;

use Prado\Data\Common\Oracle\TOracleDbCommand;
use Prado\Data\TDbCommand;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDataReader;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

/**
 * TOracleDbCommand substitutes bound values into the SQL text with PDO::quote()
 * and runs it without a prepared statement. The substitution is driver independent,
 * so these tests run the command on an SQLite in-memory database.
 */
class TOracleDbCommandTest extends \PHPUnit\Framework\TestCase
{
	protected ?TDbConnection $conn = null;

	protected function setUp(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$this->conn = new TDbConnection('sqlite::memory:');
		$this->conn->setActive(true);
		$this->conn->createCommand('CREATE TABLE items (id INTEGER PRIMARY KEY, name VARCHAR(20), note VARCHAR(20))')->execute();
		$this->conn->createCommand("INSERT INTO items (id, name, note) VALUES (1, 'apple', NULL), (2, 'it''s', 'x')")->execute();
	}

	protected function tearDown(): void
	{
		$this->conn?->setActive(false);
		$this->conn = null;
	}

	protected function command(string $sql): TOracleDbCommand
	{
		return new TOracleDbCommand($this->conn, $sql);
	}

	public function test_bound_values_are_accumulated_without_a_prepared_statement()
	{
		$command = $this->command('SELECT name FROM items WHERE id = :id');
		$command->bindValue(':id', 2);

		$this->assertNull($command->getPdoStatement());
		$this->assertSame([':id' => 2], PradoUnit::getProp($command, '_ociParams'));
	}

	public function test_bind_parameter_captures_the_value_at_bind_time()
	{
		$command = $this->command('SELECT name FROM items WHERE id = :id');
		$id = 2;
		$command->bindParameter(':id', $id);
		$id = 1;

		$this->assertSame("it's", $command->queryScalar());
	}

	public function test_named_parameters_are_quoted_into_the_query()
	{
		$command = $this->command('SELECT id, name, :label AS label, :absent AS absent FROM items WHERE name = :name');
		$command->bindValue(':name', "it's");
		$command->bindValue(':label', 'tag');
		$command->bindValue(':absent', null);

		$this->assertSame(['id' => 2, 'name' => "it's", 'label' => 'tag', 'absent' => null], $command->queryRow());
	}

	public function test_positional_parameters_are_quoted_left_to_right()
	{
		$command = $this->command('SELECT id, ? AS tag FROM items WHERE name = ? OR id = ? ORDER BY id');
		$command->bindValue(1, 'p');
		$command->bindValue(2, 'apple');
		$command->bindValue(3, null);

		$reader = $command->query();

		$this->assertInstanceOf(TDbDataReader::class, $reader);
		$this->assertSame([['id' => 1, 'tag' => 'p']], $reader->readAll());
	}

	public function test_missing_positional_value_is_null()
	{
		$command = $this->command('SELECT ? AS a, ? AS b');
		$command->bindValue(1, 'only');

		$this->assertSame(['only', null], $command->queryRow(false));
	}

	public function test_execute_with_parameters_returns_affected_rows()
	{
		$command = $this->command('UPDATE items SET note = :note WHERE id < :max');
		$command->bindValue(':note', 'n');
		$command->bindValue(':max', 3);

		$this->assertSame(2, $command->execute());
		$this->assertSame('n', $this->conn->createCommand('SELECT note FROM items WHERE id = 1')->queryScalar());
	}

	public function test_query_scalar_with_parameters()
	{
		$command = $this->command('SELECT COUNT(*) FROM items WHERE id > :min');
		$command->bindValue(':min', 0);
		$this->assertEquals(2, $command->queryScalar());

		$command = $this->command('SELECT name FROM items WHERE id = :id');
		$command->bindValue(':id', 99);
		$this->assertFalse($command->queryScalar());
	}

	public function test_query_row_without_a_match_is_false()
	{
		$command = $this->command('SELECT name FROM items WHERE id = :id');
		$command->bindValue(':id', 99);

		$this->assertFalse($command->queryRow());
	}

	public function test_commands_without_parameters_use_the_base_implementation()
	{
		$this->assertSame(1, $this->command("UPDATE items SET note = 'z' WHERE id = 1")->execute());
		$this->assertSame('z', $this->command('SELECT note FROM items WHERE id = 1')->queryScalar());
		$this->assertSame(['id' => 2, 'name' => "it's"], $this->command('SELECT id, name FROM items WHERE id = 2')->queryRow());
		$this->assertSame([['id' => 1], ['id' => 2]], $this->command('SELECT id FROM items ORDER BY id')->query()->readAll());
	}

	public function test_cancel_and_set_text_clear_the_bound_values()
	{
		$command = $this->command('SELECT :a');
		$command->bindValue(':a', 'x');
		$command->cancel();
		$this->assertSame([], PradoUnit::getProp($command, '_ociParams'));

		$command->bindValue(':a', 'y');
		$command->setText('SELECT 7');
		$this->assertSame([], PradoUnit::getProp($command, '_ociParams'));
		$this->assertEquals(7, $command->queryScalar());
	}

	public function test_bound_values_are_excluded_from_serialization()
	{
		$command = $this->command('SELECT :a');
		$exprops = [];
		PradoUnit::reflectionMethod(TOracleDbCommand::class, '_getZappableSleepProps')->invokeArgs($command, [&$exprops]);

		$this->assertContains("\0" . TOracleDbCommand::class . "\0_ociParams", $exprops);
		$this->assertContains("\0" . TDbCommand::class . "\0_statement", $exprops);
	}

	public function test_execute_failure_with_parameters_throws()
	{
		$command = $this->command('UPDATE missing_table SET a = :a');
		$command->bindValue(':a', 1);

		$this->expectException(TDbException::class);
		$command->execute();
	}

	public function test_query_failure_with_parameters_throws()
	{
		$command = $this->command('SELECT a FROM missing_table WHERE a = :a');
		$command->bindValue(':a', 1);

		$this->expectException(TDbException::class);
		$command->query();
	}

	public function test_query_row_failure_with_parameters_throws()
	{
		$command = $this->command('SELECT a FROM missing_table WHERE a = :a');
		$command->bindValue(':a', 1);

		$this->expectException(TDbException::class);
		$command->queryRow();
	}

	public function test_query_scalar_failure_with_parameters_throws()
	{
		$command = $this->command('SELECT a FROM missing_table WHERE a = :a');
		$command->bindValue(':a', 1);

		$this->expectException(TDbException::class);
		$command->queryScalar();
	}

	public function test_named_placeholder_that_prefixes_another()
	{
		$command = $this->command('SELECT :id AS a, :id2 AS b');
		$command->bindValue(':id', 1);
		$command->bindValue(':id2', 2);

		$this->assertSame(['a' => '1', 'b' => '2'], $command->queryRow());
	}

	public function test_named_placeholder_without_colon_prefix()
	{
		$command = $this->command('SELECT name FROM items WHERE id = :id');
		$command->bindValue('id', 2);

		$this->assertSame("it's", $command->queryScalar());
	}

	public function test_bound_value_containing_a_placeholder_is_not_substituted_again()
	{
		$command = $this->command('SELECT :a AS a, :b AS b');
		$command->bindValue(':a', ':b');
		$command->bindValue(':b', 'x');

		$this->assertSame(['a' => ':b', 'b' => 'x'], $command->queryRow());
	}

	public function test_placeholders_inside_literals_are_kept()
	{
		$command = $this->command("SELECT ':id' AS a, '?' AS q, :id AS b, \"x:id\" AS c FROM (SELECT 1 AS \"x:id\")");
		$command->bindValue(':id', 5);

		$this->assertSame(['a' => ':id', 'q' => '?', 'b' => '5', 'c' => 1], $command->queryRow());
	}

	public function test_unbound_named_placeholder_is_left_for_the_driver()
	{
		$command = $this->command('SELECT :id AS a, :unbound AS b');
		$command->bindValue(':id', 3);

		$this->assertSame(['a' => '3', 'b' => null], $command->queryRow());
	}

	public function test_positional_placeholders_inside_literals_are_kept()
	{
		$command = $this->command("SELECT '?' AS q, ? AS v");
		$command->bindValue(1, 'v');

		$this->assertSame(['q' => '?', 'v' => 'v'], $command->queryRow());
	}
}

<?php

namespace Prado\Test\Unit\Data\SqlMap\Statements;

use Prado\Data\SqlMap\Configuration\TResultMap;
use Prado\Data\SqlMap\Configuration\TResultProperty;
use Prado\Data\SqlMap\Statements\TMappedStatement;
use Prado\Data\SqlMap\TSqlMapManager;

class TMappedStatementGroupByUser
{
	public $Id;
	public $Name;
	public $Roles = [];
}

class TMappedStatementGroupByRole
{
	public $Name;
	public $Level;
	public $Permissions = [];
}

class TMappedStatementGroupByPermission
{
	public $Name;
}

class TMappedStatementGroupByConnection
{
	public function setActive($value)
	{
	}
}

class TMappedStatementGroupByCommand
{
	public function __construct(private array $rows)
	{
	}

	public function query()
	{
		return $this->rows;
	}
}

class TMappedStatementGroupByTest extends \PHPUnit\Framework\TestCase
{
	private function manager(): TSqlMapManager
	{
		$manager = new TSqlMapManager();
		$manager->configureXml(__DIR__ . '/../maps/group-by.xml');
		return $manager;
	}

	private function query(array $rows): array
	{
		$statement = $this->manager()->getMappedStatement('GetUsersWithRoles');
		return $statement->runQueryForList(
			new TMappedStatementGroupByConnection(),
			null,
			new TMappedStatementGroupByCommand($rows),
			null
		);
	}

	private function roleNames(TMappedStatementGroupByUser $user): array
	{
		return array_map(fn ($role) => $role->Name, $user->Roles);
	}

	private function permissionNames(TMappedStatementGroupByRole $role): array
	{
		return array_map(fn ($permission) => $permission->Name, $role->Permissions);
	}

	private function hasRowData(TResultMap $resultMap, array $row): bool
	{
		$manager = new TSqlMapManager();
		$statement = new TMappedStatement($manager, new \Prado\Data\SqlMap\Configuration\TSqlMapSelect());
		$check = \Closure::bind(
			fn ($resultMap, $row) => $this->hasResultMapRowData($resultMap, $row),
			$statement,
			TMappedStatement::class
		);
		return $check($resultMap, $row);
	}

	private function resultMap(array $columns): TResultMap
	{
		$resultMap = new TResultMap();
		$resultMap->setID('map');
		foreach ($columns as $property => $column) {
			$result = new TResultProperty();
			$result->setProperty($property);
			if (is_int($column)) {
				$result->setColumnIndex($column);
				$result->setColumn('col' . $column);
			} elseif ($column !== null) {
				$result->setColumn($column);
			}
			$resultMap->addResultProperty($result);
		}
		return $resultMap;
	}

	public function testLeftJoinWithoutMatchGivesEmptyCollection()
	{
		$users = $this->query([
			['user_id' => 1, 'user_name' => 'ann', 'role_name' => 'admin', 'role_level' => 2, 'permission_name' => 'read'],
			['user_id' => 1, 'user_name' => 'ann', 'role_name' => 'user', 'role_level' => 1, 'permission_name' => 'read'],
			['user_id' => 2, 'user_name' => 'bob', 'role_name' => null, 'role_level' => null, 'permission_name' => null],
			['user_id' => 3, 'user_name' => 'cy', 'role_name' => 'user', 'role_level' => 1, 'permission_name' => 'read'],
		]);

		$this->assertSame(['ann', 'bob', 'cy'], array_map(fn ($user) => $user->Name, $users));
		$this->assertSame(['admin', 'user'], $this->roleNames($users[0]));
		$this->assertSame([], $users[1]->Roles);
		$this->assertSame(['user'], $this->roleNames($users[2]));
	}

	public function testLeftJoinWithoutAnyMatch()
	{
		$users = $this->query([
			['user_id' => 1, 'user_name' => 'ann', 'role_name' => null, 'role_level' => null, 'permission_name' => null],
			['user_id' => 2, 'user_name' => 'bob', 'role_name' => null, 'role_level' => null, 'permission_name' => null],
		]);

		$this->assertSame(['ann', 'bob'], array_map(fn ($user) => $user->Name, $users));
		$this->assertSame([], $users[0]->Roles);
		$this->assertSame([], $users[1]->Roles);
	}

	public function testNestedRowWithOneValueIsKept()
	{
		$users = $this->query([
			['user_id' => 1, 'user_name' => 'ann', 'role_name' => null, 'role_level' => 3, 'permission_name' => null],
		]);

		$this->assertCount(1, $users[0]->Roles);
		$this->assertNull($users[0]->Roles[0]->Name);
		$this->assertSame(3, $users[0]->Roles[0]->Level);
		$this->assertSame([], $users[0]->Roles[0]->Permissions);
	}

	public function testLeftJoinWithoutMatchAtSecondLevel()
	{
		$users = $this->query([
			['user_id' => 1, 'user_name' => 'ann', 'role_name' => 'admin', 'role_level' => 2, 'permission_name' => 'read'],
			['user_id' => 1, 'user_name' => 'ann', 'role_name' => 'admin', 'role_level' => 2, 'permission_name' => 'write'],
			['user_id' => 1, 'user_name' => 'ann', 'role_name' => 'guest', 'role_level' => 0, 'permission_name' => null],
			['user_id' => 2, 'user_name' => 'bob', 'role_name' => null, 'role_level' => null, 'permission_name' => null],
		]);

		$this->assertSame(['ann', 'bob'], array_map(fn ($user) => $user->Name, $users));
		$this->assertSame(['admin', 'guest'], $this->roleNames($users[0]));
		$this->assertSame(['read', 'write'], $this->permissionNames($users[0]->Roles[0]));
		$this->assertSame([], $users[0]->Roles[1]->Permissions);
		$this->assertSame([], $users[1]->Roles);
	}

	public function testSameNestedGroupValueUnderDifferentParents()
	{
		$users = $this->query([
			['user_id' => 1, 'user_name' => 'ann', 'role_name' => 'user', 'role_level' => 1, 'permission_name' => 'read'],
			['user_id' => 2, 'user_name' => 'bob', 'role_name' => 'user', 'role_level' => 1, 'permission_name' => 'read'],
			['user_id' => 2, 'user_name' => 'bob', 'role_name' => 'user', 'role_level' => 1, 'permission_name' => 'write'],
		]);

		$this->assertSame(['user'], $this->roleNames($users[0]));
		$this->assertSame(['user'], $this->roleNames($users[1]));
		$this->assertNotSame($users[0]->Roles[0], $users[1]->Roles[0]);
		$this->assertSame(['read'], $this->permissionNames($users[0]->Roles[0]));
		$this->assertSame(['read', 'write'], $this->permissionNames($users[1]->Roles[0]));
	}

	public function testColumnCaseFolding()
	{
		$users = $this->query([
			['USER_ID' => 1, 'USER_NAME' => 'ann', 'ROLE_NAME' => 'admin', 'ROLE_LEVEL' => 2, 'PERMISSION_NAME' => null],
			['USER_ID' => 2, 'USER_NAME' => 'bob', 'ROLE_NAME' => null, 'ROLE_LEVEL' => null, 'PERMISSION_NAME' => null],
		]);

		$this->assertSame(['admin'], $this->roleNames($users[0]));
		$this->assertSame([], $users[0]->Roles[0]->Permissions);
		$this->assertSame([], $users[1]->Roles);
	}

	public function testResultObjectPropertyCaseFolding()
	{
		$statement = new TMappedStatement(new TSqlMapManager(), new \Prado\Data\SqlMap\Configuration\TSqlMapSelect());
		$fill = \Closure::bind(
			fn ($row, $object) => $this->fillResultObjectProperty($row, $object),
			$statement,
			TMappedStatement::class
		);
		$user = $fill(['ID' => 7, 'name' => 'ann'], new TMappedStatementGroupByUser());
		$this->assertSame(7, $user->Id);
		$this->assertSame('ann', $user->Name);
		$this->assertFalse(property_exists($user, 'ID'));

		$exact = new class () {
			public $Id;
			public $id;
		};
		$exact = $fill(['id' => 3], $exact);
		$this->assertSame(3, $exact->id);
		$this->assertNull($exact->Id);
	}

	public function testHasResultMapRowDataByColumnName()
	{
		$map = $this->resultMap(['A' => 'col_a', 'B' => 'col_b']);

		$this->assertTrue($this->hasRowData($map, ['col_a' => null, 'col_b' => 0]));
		$this->assertTrue($this->hasRowData($map, ['col_a' => '', 'col_b' => null]));
		$this->assertFalse($this->hasRowData($map, ['col_a' => null, 'col_b' => null]));
		$this->assertFalse($this->hasRowData($map, ['other' => 1]));
	}

	public function testHasResultMapRowDataByColumnNameCaseInsensitive()
	{
		$map = $this->resultMap(['A' => 'Col_A']);

		$this->assertTrue($this->hasRowData($map, ['COL_A' => 1]));
		$this->assertTrue($this->hasRowData($map, ['col_a' => 'x']));
		$this->assertFalse($this->hasRowData($map, ['COL_A' => null]));
	}

	public function testHasResultMapRowDataByColumnIndex()
	{
		$map = $this->resultMap(['A' => 2]);

		$this->assertTrue($this->hasRowData($map, [1 => null, 2 => 'x']));
		$this->assertFalse($this->hasRowData($map, [1 => 'x', 2 => null]));
	}

	public function testHasResultMapRowDataWithoutColumnMappings()
	{
		$this->assertTrue($this->hasRowData($this->resultMap([]), ['col_a' => null]));
		$this->assertTrue($this->hasRowData($this->resultMap(['Nested' => null]), ['col_a' => null]));
		$this->assertTrue($this->hasRowData($this->resultMap(['Nested' => '']), ['col_a' => null]));
	}
}

<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Data\SqlMap\Statements\TMappedStatement;
use Prado\Data\SqlMap\Statements\TPostSelectBinding;
use Prado\Data\SqlMap\Statements\TPreparedStatement;
use Prado\Data\SqlMap\Statements\TResultSetListItemParameter;
use Prado\Data\SqlMap\Statements\TResultSetMapItemParameter;
use Prado\Data\SqlMap\Statements\TSimpleDynamicSql;
use Prado\Data\SqlMap\Statements\TStaticSql;

class TSqlMapStatementsTest extends PHPUnit\Framework\TestCase
{
	// -------  TPostSelectBinding  -------

	public function test_post_select_binding_defaults()
	{
		$binding = new TPostSelectBinding();
		$this->assertNull($binding->getStatement());
		$this->assertNull($binding->getResultProperty());
		$this->assertNull($binding->getResultObject());
		$this->assertNull($binding->getKeys());
		$this->assertSame(TMappedStatement::QUERY_FOR_LIST, $binding->getMethod());
	}

	public function test_post_select_binding_setters()
	{
		$binding = new TPostSelectBinding();
		$binding->setStatement('myStatement');
		$binding->setResultProperty('myProperty');
		$binding->setResultObject('myObject');
		$binding->setKeys([1, 2, 3]);
		$binding->setMethod(TMappedStatement::QUERY_FOR_OBJECT);

		$this->assertSame('myStatement', $binding->getStatement());
		$this->assertSame('myProperty', $binding->getResultProperty());
		$this->assertSame('myObject', $binding->getResultObject());
		$this->assertSame([1, 2, 3], $binding->getKeys());
		$this->assertSame(TMappedStatement::QUERY_FOR_OBJECT, $binding->getMethod());
	}

	// -------  TResultSetListItemParameter  -------

	public function test_list_item_parameter_constructor()
	{
		$result = 'resultObj';
		$parameter = 'paramObj';
		$list = [];
		$param = new TResultSetListItemParameter($result, $parameter, $list);

		$this->assertSame('resultObj', $param->getResult());
		$this->assertSame('paramObj', $param->getParameter());
	}

	public function test_list_item_parameter_list_by_reference()
	{
		$list = ['initial'];
		$param = new TResultSetListItemParameter('r', 'p', $list);

		$ref = &$param->getList();
		$ref[] = 'appended';

		$this->assertContains('appended', $list);
	}

	public function test_list_item_parameter_is_tcomponent()
	{
		$list = [];
		$param = new TResultSetListItemParameter('r', 'p', $list);
		$this->assertInstanceOf(\Prado\TComponent::class, $param);
	}

	// -------  TResultSetMapItemParameter  -------

	public function test_map_item_parameter_constructor()
	{
		$map = [];
		$param = new TResultSetMapItemParameter('key1', 'val1', 'paramObj', $map);

		$this->assertSame('key1', $param->getKey());
		$this->assertSame('val1', $param->getValue());
		$this->assertSame('paramObj', $param->getParameter());
	}

	public function test_map_item_parameter_map_by_reference()
	{
		$map = ['existing' => true];
		$param = new TResultSetMapItemParameter('k', 'v', 'p', $map);

		$ref = &$param->getMap();
		$ref['new_key'] = 'new_val';

		$this->assertArrayHasKey('new_key', $map);
	}

	public function test_map_item_parameter_is_tcomponent()
	{
		$map = [];
		$param = new TResultSetMapItemParameter('k', 'v', 'p', $map);
		$this->assertInstanceOf(\Prado\TComponent::class, $param);
	}

	// -------  TPreparedStatement  -------

	public function test_prepared_statement_defaults()
	{
		$stmt = new TPreparedStatement();
		$this->assertSame('', $stmt->getPreparedSql());
	}

	public function test_prepared_statement_set_sql()
	{
		$stmt = new TPreparedStatement();
		$stmt->setPreparedSql('SELECT * FROM users');
		$this->assertSame('SELECT * FROM users', $stmt->getPreparedSql());
	}

	public function test_prepared_statement_parameter_names_lazy()
	{
		$stmt = new TPreparedStatement();
		// Not needed — returns null
		$this->assertNull($stmt->getParameterNames(false));
		// Needed — creates TList
		$names = $stmt->getParameterNames(true);
		$this->assertInstanceOf(TList::class, $names);
	}

	public function test_prepared_statement_parameter_values_lazy()
	{
		$stmt = new TPreparedStatement();
		$this->assertNull($stmt->getParameterValues(false));
		$values = $stmt->getParameterValues(true);
		$this->assertInstanceOf(TMap::class, $values);
	}

	public function test_prepared_statement_set_parameter_names()
	{
		$stmt = new TPreparedStatement();
		$list = new TList(['name', 'age']);
		$stmt->setParameterNames($list);
		$this->assertSame($list, $stmt->getParameterNames());
	}

	public function test_prepared_statement_set_parameter_values()
	{
		$stmt = new TPreparedStatement();
		$map = new TMap(['name' => 'John']);
		$stmt->setParameterValues($map);
		$this->assertSame($map, $stmt->getParameterValues());
	}

	public function test_prepared_statement_zappable_empty_not_in_sleep()
	{
		$stmt = new TPreparedStatement();
		// Empty collections → excluded from __sleep()
		$sleepKeys = $stmt->__sleep();
		$cn = TPreparedStatement::class;
		$this->assertNotContains("\0$cn\0_parameterNames", $sleepKeys);
		$this->assertNotContains("\0$cn\0_parameterValues", $sleepKeys);
	}

	public function test_prepared_statement_zappable_non_empty_in_sleep()
	{
		$stmt = new TPreparedStatement();
		$names = $stmt->getParameterNames(true);
		$names->add('foo');
		$values = $stmt->getParameterValues(true);
		$values->add('key', 'val');

		// Non-empty collections → included in __sleep()
		$sleepKeys = $stmt->__sleep();
		$cn = TPreparedStatement::class;
		$this->assertContains("\0$cn\0_parameterNames", $sleepKeys);
		$this->assertContains("\0$cn\0_parameterValues", $sleepKeys);
	}

	public function test_prepared_statement_is_tcomponent()
	{
		$stmt = new TPreparedStatement();
		$this->assertInstanceOf(\Prado\TComponent::class, $stmt);
	}

	// -------  TSimpleDynamicSql  -------

	public function test_simple_dynamic_sql_replace_parameter()
	{
		// Create TSimpleDynamicSql with one mapping
		$sql = new TSimpleDynamicSql(['tableName']);
		// Provide a parameter object with the property
		$parameter = new stdClass();
		$parameter->tableName = 'users';

		// Build a prepared statement with the DYNAMIC_TOKEN placeholder
		$token = \Prado\Data\SqlMap\Configuration\TSimpleDynamicParser::DYNAMIC_TOKEN;
		$result = $sql->replaceDynamicParameter('SELECT * FROM ' . $token, $parameter);
		$this->assertSame('SELECT * FROM users', $result);
	}

	public function test_simple_dynamic_sql_replace_multiple_parameters()
	{
		$sql = new TSimpleDynamicSql(['schema', 'table']);
		$parameter = new stdClass();
		$parameter->schema = 'public';
		$parameter->table = 'orders';

		$token = \Prado\Data\SqlMap\Configuration\TSimpleDynamicParser::DYNAMIC_TOKEN;
		$input = "SELECT * FROM {$token}.{$token}";
		$result = $sql->replaceDynamicParameter($input, $parameter);
		$this->assertSame('SELECT * FROM public.orders', $result);
	}

	public function test_simple_dynamic_sql_value_with_dollar_sign_escaped()
	{
		// Dollar signs in the replacement value must be escaped so preg_replace
		// does not treat them as backreferences.
		$sql = new TSimpleDynamicSql(['expr']);
		$parameter = new stdClass();
		$parameter->expr = 'cost$10';  // literal dollar sign

		$token = \Prado\Data\SqlMap\Configuration\TSimpleDynamicParser::DYNAMIC_TOKEN;
		$result = $sql->replaceDynamicParameter('SELECT ' . $token, $parameter);
		$this->assertSame('SELECT cost$10', $result);
	}

	public function test_simple_dynamic_sql_array_parameter()
	{
		$sql = new TSimpleDynamicSql(['tableName']);
		$parameter = ['tableName' => 'products'];

		$token = \Prado\Data\SqlMap\Configuration\TSimpleDynamicParser::DYNAMIC_TOKEN;
		$result = $sql->replaceDynamicParameter('SELECT * FROM ' . $token, $parameter);
		$this->assertSame('SELECT * FROM products', $result);
	}
}

<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\SqlMap\Configuration\TInlineParameterMapParser;
use Prado\Data\SqlMap\Configuration\TParameterProperty;
use Prado\Data\SqlMap\DataMapper\TSqlMapUndefinedException;

class TInlineParameterMapParserTest extends PHPUnit\Framework\TestCase
{
	private TInlineParameterMapParser $parser;
	private array $scope;

	protected function setUp(): void
	{
		$this->parser = new TInlineParameterMapParser();
		$this->scope = ['file' => 'test.xml', 'node' => 'statement'];
	}

	public function test_no_inline_parameters_returns_original_sql()
	{
		$result = $this->parser->parse('SELECT * FROM users WHERE id = ?', $this->scope);
		$this->assertSame('SELECT * FROM users WHERE id = ?', $result['sql']);
		$this->assertSame([], $result['parameters']);
	}

	public function test_single_inline_param_replaced_with_question_mark()
	{
		$result = $this->parser->parse('SELECT * FROM users WHERE name = #username#', $this->scope);
		$this->assertSame('SELECT * FROM users WHERE name = ?', $result['sql']);
		$this->assertCount(1, $result['parameters']);
	}

	public function test_single_inline_param_creates_tparameter_property()
	{
		$result = $this->parser->parse('SELECT * FROM users WHERE name = #username#', $this->scope);
		$mapping = $result['parameters'][0];
		$this->assertInstanceOf(TParameterProperty::class, $mapping);
		$this->assertSame('username', $mapping->getProperty());
	}

	public function test_multiple_inline_params()
	{
		$result = $this->parser->parse('INSERT INTO t (a, b) VALUES (#propA#, #propB#)', $this->scope);
		$this->assertSame('INSERT INTO t (a, b) VALUES (?, ?)', $result['sql']);
		$this->assertCount(2, $result['parameters']);
		$this->assertSame('propA', $result['parameters'][0]->getProperty());
		$this->assertSame('propB', $result['parameters'][1]->getProperty());
	}

	public function test_inline_param_with_type_attribute()
	{
		$result = $this->parser->parse('#myProp,type=string#', $this->scope);
		$mapping = $result['parameters'][0];
		$this->assertSame('myProp', $mapping->getProperty());
		$this->assertSame('string', $mapping->getType());
	}

	public function test_inline_param_with_dbtype_attribute()
	{
		$result = $this->parser->parse('#myProp,dbType=Varchar#', $this->scope);
		$mapping = $result['parameters'][0];
		$this->assertSame('Varchar', $mapping->getDbType());
	}

	public function test_inline_param_with_nullvalue_attribute()
	{
		$result = $this->parser->parse('#myProp,nullValue=N/A#', $this->scope);
		$mapping = $result['parameters'][0];
		$this->assertSame('N/A', $mapping->getNullValue());
	}

	public function test_inline_param_with_handler_attribute()
	{
		$result = $this->parser->parse('#myProp,typeHandler=MyHandler#', $this->scope);
		$mapping = $result['parameters'][0];
		$this->assertSame('MyHandler', $mapping->getTypeHandler());
	}

	public function test_inline_param_with_column_attribute()
	{
		$result = $this->parser->parse('#myProp,column=my_col#', $this->scope);
		$mapping = $result['parameters'][0];
		$this->assertSame('my_col', $mapping->getColumn());
	}

	public function test_inline_param_multiple_attributes()
	{
		$result = $this->parser->parse('#myProp,type=string,dbType=Varchar,nullValue=N/A#', $this->scope);
		$mapping = $result['parameters'][0];
		$this->assertSame('myProp', $mapping->getProperty());
		$this->assertSame('string', $mapping->getType());
		$this->assertSame('Varchar', $mapping->getDbType());
		$this->assertSame('N/A', $mapping->getNullValue());
	}

	public function test_unknown_attribute_throws_undefined_exception()
	{
		$this->expectException(TSqlMapUndefinedException::class);
		$this->parser->parse('#myProp,unknownAttr=value#', $this->scope);
	}

	public function test_parameter_token_regexp_constant()
	{
		$this->assertSame('/#([^#]+)#/', TInlineParameterMapParser::PARAMETER_TOKEN_REGEXP);
	}

	public function test_empty_sql_returns_empty_result()
	{
		$result = $this->parser->parse('', $this->scope);
		$this->assertSame('', $result['sql']);
		$this->assertSame([], $result['parameters']);
	}

	public function test_result_has_sql_and_parameters_keys()
	{
		$result = $this->parser->parse('SELECT 1', $this->scope);
		$this->assertArrayHasKey('sql', $result);
		$this->assertArrayHasKey('parameters', $result);
	}
}

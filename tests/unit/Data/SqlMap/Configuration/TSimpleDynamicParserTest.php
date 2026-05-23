<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\SqlMap\Configuration\TSimpleDynamicParser;

class TSimpleDynamicParserTest extends PHPUnit\Framework\TestCase
{
	private TSimpleDynamicParser $parser;

	protected function setUp(): void
	{
		$this->parser = new TSimpleDynamicParser();
	}

	public function test_no_placeholders_returns_original_sql()
	{
		$result = $this->parser->parse('SELECT * FROM table1 WHERE id = 1');
		$this->assertSame('SELECT * FROM table1 WHERE id = 1', $result['sql']);
		$this->assertSame([], $result['parameters']);
	}

	public function test_single_placeholder_replaced()
	{
		$result = $this->parser->parse('SELECT * FROM $tableName$');
		$this->assertSame('SELECT * FROM ' . TSimpleDynamicParser::DYNAMIC_TOKEN, $result['sql']);
		$this->assertSame(['tableName'], $result['parameters']);
	}

	public function test_multiple_placeholders_replaced_in_order()
	{
		$result = $this->parser->parse('SELECT * FROM $table$ WHERE $col$ = 1');
		$this->assertSame(
			'SELECT * FROM ' . TSimpleDynamicParser::DYNAMIC_TOKEN . ' WHERE ' . TSimpleDynamicParser::DYNAMIC_TOKEN . ' = 1',
			$result['sql']
		);
		$this->assertSame(['table', 'col'], $result['parameters']);
	}

	public function test_dynamic_token_constant()
	{
		$this->assertSame('`!`', TSimpleDynamicParser::DYNAMIC_TOKEN);
	}

	public function test_parameter_token_regexp_constant()
	{
		$this->assertSame('/\$([^\$]+)\$/', TSimpleDynamicParser::PARAMETER_TOKEN_REGEXP);
	}

	public function test_result_has_sql_and_parameters_keys()
	{
		$result = $this->parser->parse('SELECT 1');
		$this->assertArrayHasKey('sql', $result);
		$this->assertArrayHasKey('parameters', $result);
	}

	public function test_empty_string_parsed()
	{
		$result = $this->parser->parse('');
		$this->assertSame('', $result['sql']);
		$this->assertSame([], $result['parameters']);
	}

	public function test_placeholder_with_underscores()
	{
		$result = $this->parser->parse('SELECT $my_table_name$');
		$this->assertSame(['my_table_name'], $result['parameters']);
	}

	public function test_placeholder_content_with_spaces()
	{
		// $name with spaces$ — the regex [^\$]+ matches spaces
		$result = $this->parser->parse('SELECT $some name$');
		$this->assertSame(['some name'], $result['parameters']);
	}

	public function test_parameter_regexp_matches_dollar_delimiters()
	{
		preg_match_all(TSimpleDynamicParser::PARAMETER_TOKEN_REGEXP, '$foo$', $matches);
		$this->assertSame(['foo'], $matches[1]);
	}

	public function test_adjacent_placeholders()
	{
		$result = $this->parser->parse('$a$$b$');
		$this->assertSame(['a', 'b'], $result['parameters']);
		// Both replaced with DYNAMIC_TOKEN
		$token = TSimpleDynamicParser::DYNAMIC_TOKEN;
		$this->assertSame($token . $token, $result['sql']);
	}
}

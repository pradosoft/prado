<?php

Prado::using('System.I18N.core.MessageSource_MySQL');
Prado::using('System.I18N.core.MessageFormat');

class MysqlMessageSourceTestCase extends UnitTestCase
{
	private $_source;

	function get_source()
	{
		if($this->_source===null)
		{
			$this->_source = new MessageSource_MySQL('mysq://prado:prado@localhost/i18n_test');
			$this->_source->setCulture('en_AU');
		}
		return $this->_source;
	}

/*
	function test_source()
	{
		$source = $this->get_source();
		$this->assertEqual(3, count($source->catalogues()));
	}

	function test_load_source()
	{
		$source = $this->get_source();
		$this->assertTrue($source->load());
	}

	function test_message_format()
	{
		$formatter = new MessageFormat($this->get_source());
		var_dump($formatter->format('Hello'));
		var_dump($formatter->format('Goodbye'));
		//$this->assertEqual($formatter->format('Hello'),'G\'day Mate!');

		//$this->assertEqual($formatter->format('Goodbye'), 'Goodbye');
	}
*/
}

?>
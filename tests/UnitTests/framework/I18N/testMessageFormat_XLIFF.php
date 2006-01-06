<?php

Prado::using('System.I18N.core.MessageFormat');

class testMessageFormat_XLIFF extends UnitTestCase
{
	protected $type = 'XLIFF';
	protected $source = './messages';
	protected $tmp;
	protected $dir;
	
	function testMessageFormat_XLIFF()
	{
		$this->UnitTestCase();
		$this->dir = dirname(__FILE__);
		$this->tmp = $this->dir.'/tmp/';
		$this->source = $this->dir.'/messages';

	}
	
	function test1()
	{
		$source = MessageSource::factory($this->type, $this->source);
		$source->setCulture('en_AU');
		$source->setCache(new MessageCache($this->tmp));

		$formatter = new MessageFormat($source);
		$this->assertEqual($formatter->format('Hello'),'G\'day Mate!');
	
		$this->assertEqual($formatter->format('Goodbye'), 'Goodbye');
		
		$formatter->setUntranslatedPS(array('[T]','[/T]'));	
		$this->assertEqual($formatter->format('Hi'), '[T]Hi[/T]');
		
		//clear cache
		$source->getCache()->clear();
		
	}
	
	function testSaveUpdateDelete()
	{
		$backup = $this->dir.'/messages/messages.en_AU.xml.bak';
		$xmlfile = $this->dir.'/messages/messages.en_AU.xml';
		
		//restore using the back file
		copy($backup,$xmlfile);
		
		//test that the back file doesn't contain the 'Testing123' string.
		$contents = file_get_contents($xmlfile);
		$this->assertNoUnwantedPattern('/Testing123/',$contents);
		
		$source = MessageSource::factory($this->type, $this->source);
		$source->setCulture('en_AU');
		$source->setCache(new MessageCache($this->tmp));
		
		$formatter = new MessageFormat($source);
		
		//add a untranslated string
		$this->assertEqual($formatter->format('Testing123'), 'Testing123');

		//save it
		$this->assertTrue($formatter->getSource()->save());
		
		//check the contents
		$contents = file_get_contents($xmlfile);		
		$this->assertWantedPattern('/Testing123/',$contents);
		
		//testing for update.		
		$this->assertTrue($formatter->getSource()->update(
						'Testing123', '123Test', 'update comments'));
						
		$contents = file_get_contents($xmlfile);		
		$this->assertWantedPattern('/123Test/',$contents);			
		$this->assertWantedPattern('/update comments/',$contents);					
		
		//var_dump(htmlspecialchars($contents));
				
		//now doing some delete		
		$this->assertFalse($formatter->getSource()->delete('Test123'));
		$this->assertTrue($formatter->getSource()->delete('Testing123'));
		
		$contents = file_get_contents($xmlfile);		
		$this->assertNoUnwantedPattern('/Testing123/',$contents);	
		
		//restore using the backup file.
		copy($backup,$xmlfile);

		$source->getCache()->clear();
	}
	
	function testCatalogueList()
	{
		$source = MessageSource::factory($this->type, $this->source);
		$result[] = array('messages',NULL);
		$result[] = array('messages', 'en');
		$result[] = array('messages','en_AU');
		$result[] = array('tests',NULL);
		$result[] = array('tests','en');
		$result[] = array('tests','en_AU');

		$this->assertEqual($result, $source->catalogues());
	}
	
	function testAltCatalogue()
	{
		$source = MessageSource::factory($this->type, $this->source);
		$source->setCulture('en_AU');
		$source->setCache(new MessageCache($this->tmp));	
		
		$formatter = new MessageFormat($source);
		$formatter->Catalogue = 'tests';
		
		//from a different catalogue
		$this->assertEqual($formatter->format('Hello'), 'Howdy!');	
		$this->assertEqual($formatter->format('Welcome'), 'Ho Ho!');	
		$this->assertEqual($formatter->format('Goodbye'), 'Sayonara');	
		
		//switch to 'messages' catalogue
		$this->assertEqual($formatter->format('Hello',null,'messages'),'G\'day Mate!');

		$source->getCache()->clear();
	}
	
	function testDirectoryTypeSaveUpdateDelete()
	{
		$backup = $this->dir.'/messages/en_AU/tests.xml.bak';
		$xmlfile = $this->dir.'/messages/en_AU/tests.xml';
		
		//restore using the back file
		copy($backup,$xmlfile);
		
		//test that the back file doesn't contain the 'Testing123' string.
		$contents = file_get_contents($xmlfile);
		$this->assertNoUnwantedPattern('/Testing123/',$contents);
		
		$source = MessageSource::factory($this->type, $this->source);
		$source->setCulture('en_AU');
		$source->setCache(new MessageCache($this->tmp));
		
		$formatter = new MessageFormat($source);

		//add a untranslated string, note, doesn't matter which catalogue
		$this->assertEqual($formatter->format('Testing123'), 'Testing123');
		
		//save it to the 'tests' catalgoue
		$this->assertTrue($formatter->getSource()->save('tests'));
		
		//check the contents
		$contents = file_get_contents($xmlfile);		
		$this->assertWantedPattern('/Testing123/',$contents);
		
		//testing for update. Update it to the 'tests' catalogue	
		$this->assertTrue($formatter->getSource()->update(
						'Testing123', '123Test', 'update comments','tests'));
						
		$contents = file_get_contents($xmlfile);		
		$this->assertWantedPattern('/123Test/',$contents);			
		$this->assertWantedPattern('/update comments/',$contents);					
		
		//now doing some delete	from the 'tests' catalogue
		$this->assertFalse($formatter->getSource()->delete('Test123','tests'));
		$this->assertTrue($formatter->getSource()->delete('Testing123','tests'));
		
		$contents = file_get_contents($xmlfile);		
		$this->assertNoUnwantedPattern('/Testing123/',$contents);	
		
		//restore using the backup file.
		copy($backup,$xmlfile);		

		$source->getCache()->clear();
	}
}

?>
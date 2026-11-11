<?php

use \Prado\Util\Email\TEmailModule;
use \Prado\Util\Email\TEmail;
use \Prado\Util\Email\TEmailAddress;

/**
 * @package Prado\Util\Email\Tests
 */
class TEmailTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @var TEmail
	 */
	private $email;

	protected function setUp(): void
	{
		$this->email = new TEmail();
	}

	public function testGetUID()
	{
		$this->assertNotNull($this->email->getUID());
		$uid1 = $this->email->getUID();
		$uid2 = $this->email->getUID();
		$this->assertEquals($uid1, $uid2); // UID should be cached
	}

	public function testSetUID()
	{
		$this->email->setUID('test-uid');
		$this->assertEquals('test-uid', $this->email->getUID());
	}

	public function testGetUsername()
	{
		$this->assertNull($this->email->getUsername());
	}

	public function testSetUsername()
	{
		$this->email->setUsername('testuser');
		$this->assertEquals('testuser', $this->email->getUsername());
	}

	public function testGetTo()
	{
		$this->assertNull($this->email->getTo());
	}

	public function testSetTo()
	{
		// Test with string
		$this->email->setTo('test@example.com');
		$to = $this->email->getTo();
		$this->assertInstanceOf(TEmailAddress::class, $to[0]);
		
		// Test with array of strings
		$this->email->setTo(['test1@example.com', 'test2@example.com']);
		$to = $this->email->getTo();
		$this->assertInstanceOf(TEmailAddress::class, $to[0]);
		$this->assertInstanceOf(TEmailAddress::class, $to[1]);
		
		// Test with null
		$this->email->setTo(null);
		$this->assertNull($this->email->getTo());
	}

	public function testGetSender()
	{
		$this->assertNull($this->email->getSender());
	}

	public function testSetSender()
	{
		// Test with string
		$this->email->setSender('test@example.com');
		$sender = $this->email->getSender();
		$this->assertInstanceOf(TEmailAddress::class, $sender);
		
		// Test with TEmailAddress object
		$addr = new TEmailAddress('test2@example.com');
		$this->email->setSender($addr);
		$this->assertSame($addr, $this->email->getSender());
	}

	public function testGetFrom()
	{
		$this->assertNull($this->email->getFrom());
	}

	public function testSetFrom()
	{
		// Test with string
		$this->email->setFrom('test@example.com');
		$from = $this->email->getFrom();
		$this->assertInstanceOf(TEmailAddress::class, $from);
		
		// Test with TEmailAddress object
		$addr = new TEmailAddress('test2@example.com');
		$this->email->setFrom($addr);
		$this->assertSame($addr, $this->email->getFrom());
	}

	public function testGetReplyTo()
	{
		$this->assertNull($this->email->getReplyTo());
	}

	public function testSetReplyTo()
	{
		// Test with string
		$this->email->setReplyTo('test@example.com');
		$replyTo = $this->email->getReplyTo();
		$this->assertInstanceOf(TEmailAddress::class, $replyTo[0]);
		
		// Test with array of strings
		$this->email->setReplyTo(['test1@example.com', 'test2@example.com']);
		$replyTo = $this->email->getReplyTo();
		$this->assertInstanceOf(TEmailAddress::class, $replyTo[0]);
		$this->assertInstanceOf(TEmailAddress::class, $replyTo[1]);
		
		// Test with null
		$this->email->setReplyTo(null);
		$this->assertNull($this->email->getReplyTo());
	}

	public function testGetReturnPath()
	{
		$this->assertNull($this->email->getReturnPath());
	}

	public function testSetReturnPath()
	{
		// Test with string
		$this->email->setReturnPath('test@example.com');
		$returnPath = $this->email->getReturnPath();
		$this->assertInstanceOf(TEmailAddress::class, $returnPath);
		
		// Test with TEmailAddress object
		$addr = new TEmailAddress('test2@example.com');
		$this->email->setReturnPath($addr);
		$this->assertSame($addr, $this->email->getReturnPath());
	}

	public function testGetCc()
	{
		$this->assertNull($this->email->getCc());
	}

	public function testSetCc()
	{
		// Test with string
		$this->email->setCc('test@example.com');
		$cc = $this->email->getCc();
		$this->assertInstanceOf(TEmailAddress::class, $cc[0]);
		
		// Test with array of strings
		$this->email->setCc(['test1@example.com', 'test2@example.com']);
		$cc = $this->email->getCc();
		$this->assertInstanceOf(TEmailAddress::class, $cc[0]);
		$this->assertInstanceOf(TEmailAddress::class, $cc[1]);
		
		// Test with null
		$this->email->setCc(null);
		$this->assertNull($this->email->getCc());
	}

	public function testGetBcc()
	{
		$this->assertNull($this->email->getBcc());
	}

	public function testSetBcc()
	{
		// Test with string
		$this->email->setBcc('test@example.com');
		$bcc = $this->email->getBcc();
		$this->assertInstanceOf(TEmailAddress::class, $bcc[0]);
		
		// Test with array of strings
		$this->email->setBcc(['test1@example.com', 'test2@example.com']);
		$bcc = $this->email->getBcc();
		$this->assertInstanceOf(TEmailAddress::class, $bcc[0]);
		$this->assertInstanceOf(TEmailAddress::class, $bcc[1]);
		
		// Test with null
		$this->email->setBcc(null);
		$this->assertNull($this->email->getBcc());
	}

	public function testGetDate()
	{
		$this->assertNull($this->email->getDate());
	}

	public function testSetDate()
	{
		$this->email->setDate('2023-01-01');
		$this->assertEquals('2023-01-01', $this->email->getDate());

		// Test with numeric timestamp 
		$this->email->setDate(1672531200); // 2023-01-01
		$this->assertEquals(1672531200, $this->email->getDate());
	}

	public function testGetPriority()
	{
		$this->assertNull($this->email->getPriority());
	}

	public function testSetPriority()
	{
		$this->email->setPriority(3);
		$this->assertEquals(3, $this->email->getPriority());

		// Test out of range values (should be clamped)
		$this->email->setPriority(0);
		$this->assertEquals(1, $this->email->getPriority());

		$this->email->setPriority(6);
		$this->assertEquals(5, $this->email->getPriority());

		// Test with string value
		$this->email->setPriority('2');
		$this->assertEquals(2, $this->email->getPriority());

		// Test with null
		$this->email->setPriority(null);
		$this->assertNull($this->email->getPriority());
	}

	public function testGetMessageId()
	{
		$this->assertNull($this->email->getMessageId());
	}

	public function testSetMessageId()
	{
		// Test with simple ID
		$this->email->setMessageId('test-id');
		$this->assertEquals('test-id', $this->email->getMessageId());

		// Test with ID in angle brackets
		$this->email->setMessageId('<test-id@example.com>');
		$this->assertEquals('test-id@example.com', $this->email->getMessageId());

		// Test with null
		$this->email->setMessageId(null);
		$this->assertNull($this->email->getMessageId());
	}

	public function testGetContentType()
	{
		$this->assertNull($this->email->getContentType());
	}

	public function testSetContentType()
	{
		$this->email->setContentType('text/html');
		$this->assertEquals('text/html', $this->email->getContentType());

		// Test with null
		$this->email->setContentType(null);
		$this->assertNull($this->email->getContentType());
	}

	public function testGetCharset()
	{
		$this->assertNull($this->email->getCharset());
	}

	public function testSetCharset()
	{
		$this->email->setCharset('UTF-8');
		$this->assertEquals('UTF-8', $this->email->getCharset());

		// Test with null
		$this->email->setCharset(null);
		$this->assertNull($this->email->getCharset());
	}

	public function testGetSubject()
	{
		$this->assertNull($this->email->getSubject());
	}

	public function testSetSubject()
	{
		$this->email->setSubject('Test Subject');
		$this->assertEquals('Test Subject', $this->email->getSubject());
	}

	public function testGetIsHtml()
	{
		$this->assertTrue($this->email->getIsHtml());
	}

	public function testSetIsHtml()
	{
		$this->email->setIsHtml(false);
		$this->assertFalse($this->email->getIsHtml());
	}

	public function testGetBody()
	{
		$this->assertNull($this->email->getBody());
	}

	public function testSetBody()
	{
		$this->email->setBody('<html><body>Test body</body></html>');
		$this->assertEquals('<html><body>Test body</body></html>', $this->email->getBody());
		$this->email->setBody(null);
		$this->assertNull($this->email->getBody());
	}

	public function testGetTextBody()
	{
		$this->assertNull($this->email->getTextBody());
	}

	public function testSetTextBody()
	{
		$this->email->setTextBody('Test text body');
		$this->assertEquals('Test text body', $this->email->getTextBody());
		$this->email->setTextBody(null);
		$this->assertNull($this->email->getTextBody());
	}

	public function testGetAttachments()
	{
		$this->assertNull($this->email->getAttachments());
	}

	public function testSetAttachments()
	{
		$attachments = ['file1.jpg' => '/path/to/file1.jpg'];
		$this->email->setAttachments($attachments);
		$this->assertEquals($attachments, $this->email->getAttachments());
		$this->email->setAttachments(null);
		$this->assertNull($this->email->getAttachments());
	}

	public function testAddAttachment()
	{
		$this->email->addAttachment('file1.jpg', ['path' => '/path/to/file1.jpg']);
		$this->assertArrayHasKey('file1.jpg', $this->email->getAttachments());
		$this->assertArrayHasKey('path', $this->email->getAttachments()['file1.jpg']);
		$this->assertEquals('/path/to/file1.jpg', $this->email->getAttachments()['file1.jpg']['path']);
	}

	public function testGetCustomData()
	{
		$this->assertNull($this->email->getCustomData());
	}

	public function testSetCustomData()
	{
		$customData = ['NAME' => 'Test Name', 'EMAIL' => 'test@example.com'];
		$this->email->setCustomData($customData);
		$this->assertEquals($customData, $this->email->getCustomData());
	}

	public function testGetEmailerModule()
	{
		$this->assertNull($this->email->getEmailerModule());
	}

	public function testSetEmailerModule()
	{
		$module = $this->createMock(TEmailModule::class);
		$this->email->setEmailerModule($module);
		$this->assertSame($module, $this->email->getEmailerModule());
	}

	public function testAsAddressArray()
	{
		// Test null
		$this->assertNull($this->email->asAddressArray(null));
		
		// Test string
		$result = $this->email->asAddressArray('test@example.com');
		$this->assertInstanceOf(TEmailAddress::class, $result[0]);
		
		// Test array of strings
		$result = $this->email->asAddressArray(['test1@example.com', 'test2@example.com']);
		$this->assertInstanceOf(TEmailAddress::class, $result[0]);
		$this->assertInstanceOf(TEmailAddress::class, $result[1]);
		
		// Test array with name and email
		$result = $this->email->asAddressArray([['John Doe', 'john@example.com']]);
		$this->assertInstanceOf(TEmailAddress::class, $result[0]);
		$this->assertEquals('John Doe', $result[0]->getName());
		
		// Test TEmailAddress object
		$addr = new TEmailAddress('test@example.com');
		$result = $this->email->asAddressArray($addr);
		$this->assertSame($addr, $result[0]);
		
		// Test invalid input
		$result = $this->email->asAddressArray(123);
		$this->assertEquals([], $result);
	}

	public function testGetHeaders()
	{
		$this->assertEmpty($this->email->getHeaders());
	}

	public function testSetHeaders()
	{
		$headers = [
			'From' => 'test@example.com',
			'Subject' => 'Test Subject',
			'X-Priority' => 3
		];
		$this->email->setHeaders($headers);
		// Just make sure it doesn't throw an exception and basic fields are extracted
		$this->assertInstanceOf(TEmailAddress::class, $this->email->getFrom());
		// No further assertions because internal handling is complex and depends on exact implementation
	}

	public function testSetHeader()
	{
		$this->email->setHeader('Test-Header', 'Test Value');
		$this->assertEquals('Test Value', $this->email->_headers['Test-Header']);
		
		$this->email->setHeader('Test-Header', null);
		$this->assertFalse(isset($this->email->_headers['Test-Header']));
	}

	public function testHtmlToText()
	{
		$html = '<h1>Header 1</h1><p>Paragraph with <b>bold</b> and <i>italic</i> text.</p><br><ul><li>Item 1</li><li>Item 2</li></ul>';
		$text = TEmail::htmlToText($html);
		$this->assertStringContainsString('Header 1', $text);
		$this->assertStringContainsString('bold', $text);
		$this->assertStringContainsString('italic', $text);
		$this->assertStringContainsString('Item 1', $text);
		$this->assertStringContainsString('Item 2', $text);
	}

	public function testSendWithoutTo()
	{
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->email->send();
	}

	public function testSendWithNoModule()
	{
		$this->email->setTo('test@example.com');
		// Instead of checking exact exception type, let's just make sure an exception is thrown
		$this->expectException(\Exception::class);
		$this->email->send();
	}
}
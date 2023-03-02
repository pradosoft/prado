<?php

use Prado\IO\TTextWriter;
use Prado\Web\UI\WebControls\TDataSize;

class TDataSizeTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TDataSize();
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		self::assertInstanceOf('\\Prado\\Web\\UI\\WebControls\\TDataSize', $this->obj);
	}
	
	
	public function testSize()
	{
		self::assertEquals(0, $this->obj->getSize());
		
		$this->obj->setSize(1);
		self::assertEquals(1, $this->obj->getSize());
		
		$this->obj->setSize(1000);
		self::assertEquals(1000, $this->obj->getSize());
		
		$rand = rand();
		$this->obj->setSize($rand);
		self::assertEquals($rand, $this->obj->getSize());
		
		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$this->obj->setSize(-1);
	}
	
	public function testUseMarketingSize()
	{	
		$this->obj->setUseMarketingSize(true);
		self::assertTrue($this->obj->getUseMarketingSize());
		
		$this->obj->setUseMarketingSize(false);
		self::assertFalse($this->obj->getUseMarketingSize());
		
		$this->obj->setUseMarketingSize(true);
		self::assertTrue($this->obj->getUseMarketingSize());
	}
	
	public function testAbbreviate()
	{	
		$this->obj->setAbbreviate(false);
		self::assertFalse($this->obj->getAbbreviate());
		
		$this->obj->setAbbreviate(true);
		self::assertTrue($this->obj->getAbbreviate());
		
		$this->obj->setAbbreviate(false);
		self::assertFalse($this->obj->getAbbreviate());
	}
	
	public function testRenderContents()
	{
		$writer = new TTextWriter;
		
		{
			$this->obj->setAbbreviate(true);
			$this->obj->setUseMarketingSize(true);
			
			$this->obj->setSize(0);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/0/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]B/i', $output));
			
			$this->obj->setSize(994);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/994/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]B/i', $output));
			
			$this->obj->setSize(pow(1000, 1));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kB/i', $output));
			
			$this->obj->setSize(1100);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kB/i', $output));
			
			$this->obj->setSize(pow(1000, 2));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]MB/i', $output));
			
			$this->obj->setSize(pow(1000, 3));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]GB/i', $output));
			
			$this->obj->setSize(pow(1000, 4));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]TB/i', $output));
			
			$this->obj->setSize(pow(1000, 5));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]PB/i', $output));
			
			$this->obj->setSize(pow(1000, 6));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]EB/i', $output));
			
			$this->obj->setSize(pow(1000, 7));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]ZB/i', $output));
			
			$this->obj->setSize(pow(1000, 8));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]YB/i', $output));
			
			$this->obj->setSize(pow(1000, 9));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1000[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]YB/i', $output));
		}
		{
			$this->obj->setAbbreviate(true);
			$this->obj->setUseMarketingSize(false);
			
			$this->obj->setSize(0);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/0/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]B/i', $output));
			
			$this->obj->setSize(1023);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1023[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]B/i', $output));
			
			$this->obj->setSize(pow(1024, 1));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]KiB/i', $output));
			
			$this->obj->setSize(1100 * 1.024);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]KiB/i', $output));
			
			$this->obj->setSize(pow(1024, 2));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]MiB/i', $output));
			
			$this->obj->setSize(pow(1024, 3));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]GiB/i', $output));
			
			$this->obj->setSize(pow(1024, 4));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]TiB/i', $output));
			
			$this->obj->setSize(pow(1024, 5));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]PiB/i', $output));
			
			$this->obj->setSize(pow(1024, 6));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]EiB/i', $output));
			
			$this->obj->setSize(pow(1024, 7));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]ZiB/i', $output));
			
			$this->obj->setSize(pow(1024, 8));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]YiB/i', $output));
			
			$this->obj->setSize(pow(1024, 9));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1024[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]YiB/i', $output));
		}
		
		{
			$this->obj->setAbbreviate(false);
			$this->obj->setUseMarketingSize(true);
			
			$this->obj->setSize(0);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/0/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]bytes/i', $output));
			
			$this->obj->setSize(1);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]byte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(994);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/994/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]bytes/i', $output));
			
			$this->obj->setSize(pow(1000, 1));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kilobyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 1));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kilobytes/i', $output));
			
			$this->obj->setSize(pow(1000, 2));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]megabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 2));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]megabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 3));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]gigabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 3));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]gigabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 4));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]terabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 4));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]terabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 5));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]petabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 5));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]petabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 6));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]exabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 6));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]exabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 7));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]zettabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 7));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]zettabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 8));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]yottabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 8));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]yottabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 9));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1000[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]yottabytes/i', $output));
		}
		{
			$this->obj->setAbbreviate(false);
			$this->obj->setUseMarketingSize(false);
			
			$this->obj->setSize(0);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/0/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]bytes/i', $output));
			
			$this->obj->setSize(1);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]byte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(994);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/994/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]bytes/i', $output));
			
			$this->obj->setSize(1023);
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1023/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]bytes/i', $output));
			
			$this->obj->setSize(pow(1024, 1));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kibibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 1));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kibibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 2));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]mebibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 2));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]mebibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 3));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]gibibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 3));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]gibibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 4));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]tebibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 4));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]tebibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 5));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]pebibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 5));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]pebibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 6));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]exbibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 6));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]exbibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 7));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]zebibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 7));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]zebibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 8));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]yobibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 8));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]yobibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 9));
			$this->obj->renderContents($writer);
			self::assertEquals(1, preg_match('/1024[^\d\.]/', $output = $writer->flush()));
			self::assertEquals(1, preg_match('/[^a-zA-Z]yobibyte[^a-zA-Z]*/i', $output));
		}
	}
}

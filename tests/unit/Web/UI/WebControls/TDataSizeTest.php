<?php

use Prado\IO\TTextWriter;
use Prado\Web\UI\WebControls\TDataSize;
use Prado\Exceptions\TInvalidDataValueException;

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
		self::assertInstanceOf(TDataSize::class, $this->obj);
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
		
		$this->expectException(TInvalidDataValueException::class);
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
	
	public function testMarketingSizeAbbreviated()
	{
		$writer = new TTextWriter;
		
		// Set culture to English for explicit string assertions
		$this->obj->setCulture('en');
		
		$this->obj->setAbbreviate(true);
		$this->obj->setUseMarketingSize(true);
		
		// Test various sizes using pow(1000, magnitude) instead of full integers
		$this->obj->setSize(0);
		$this->obj->renderContents($writer);
		self::assertEquals('0 B', $writer->flush());
		
		$this->obj->setSize(1);
		$this->obj->renderContents($writer);
		self::assertEquals('1 B', $writer->flush());
		
		$this->obj->setSize(999);
		$this->obj->renderContents($writer);
		self::assertEquals('999 B', $writer->flush());
		
		$this->obj->setSize(pow(1000, 1));
		$this->obj->renderContents($writer);
		self::assertEquals('1 KB', $writer->flush());
		
		$this->obj->setSize(1023);
		$this->obj->renderContents($writer);
		self::assertEquals('1.02 KB', $writer->flush());
		
		$this->obj->setSize(pow(1000, 2));
		$this->obj->renderContents($writer);
		self::assertEquals('1 MB', $writer->flush());
		
		$this->obj->setSize(pow(1000, 3));
		$this->obj->renderContents($writer);
		self::assertEquals('1 GB', $writer->flush());
		
		$this->obj->setSize(pow(1000, 4));
		$this->obj->renderContents($writer);
		self::assertEquals('1 TB', $writer->flush());
		
		$this->obj->setSize(pow(1000, 5));
		$this->obj->renderContents($writer);
		self::assertEquals('1 PB', $writer->flush());
		
		$this->obj->setSize(pow(1000, 6));
		$this->obj->renderContents($writer);
		self::assertEquals('1 EB', $writer->flush());
		
		$this->obj->setSize(pow(1000, 7));
		$this->obj->renderContents($writer);
		self::assertEquals('1 ZB', $writer->flush());
		
		$this->obj->setSize(pow(1000, 8));
		$this->obj->renderContents($writer);
		self::assertEquals('1 YB', $writer->flush());
		
		$this->obj->setSize(pow(1000, 9));
		$this->obj->renderContents($writer);
		self::assertEquals('1 RB', $writer->flush());
		
		$this->obj->setSize(pow(1000, 10));
		$this->obj->renderContents($writer);
		self::assertEquals('1 QB', $writer->flush());
	}
	
	public function testMarketingSizeNonAbbreviated()
	{
		$writer = new TTextWriter;
		
		// Set culture to English for explicit string assertions
		$this->obj->setCulture('en');
		
		$this->obj->setAbbreviate(false);
		$this->obj->setUseMarketingSize(true);
		
		$this->obj->setSize(0);
		$this->obj->renderContents($writer);
		self::assertEquals('0 bytes', $writer->flush());
		
		$this->obj->setSize(1);
		$this->obj->renderContents($writer);
		self::assertEquals('1 byte', $writer->flush());
		
		$this->obj->setSize(2);
		$this->obj->renderContents($writer);
		self::assertEquals('2 bytes', $writer->flush());
		
		$this->obj->setSize(pow(1000, 1));
		$this->obj->renderContents($writer);
		self::assertEquals('1 kilobyte', $writer->flush());
		
		$this->obj->setSize(1023);
		$this->obj->renderContents($writer);
		self::assertEquals('1.02 kilobytes', $writer->flush());
		
		$this->obj->setSize(pow(1000, 2));
		$this->obj->renderContents($writer);
		self::assertEquals('1 megabyte', $writer->flush());
		
		$this->obj->setSize(pow(1000, 3));
		$this->obj->renderContents($writer);
		self::assertEquals('1 gigabyte', $writer->flush());
		
		$this->obj->setSize(pow(1000, 4));
		$this->obj->renderContents($writer);
		self::assertEquals('1 terabyte', $writer->flush());
		
		$this->obj->setSize(pow(1000, 5));
		$this->obj->renderContents($writer);
		self::assertEquals('1 petabyte', $writer->flush());
		
		$this->obj->setSize(pow(1000, 6));
		$this->obj->renderContents($writer);
		self::assertEquals('1 exabyte', $writer->flush());
		
		$this->obj->setSize(pow(1000, 7));
		$this->obj->renderContents($writer);
		self::assertEquals('1 zettabyte', $writer->flush());
		
		// Test yottabyte (10^24)
		$this->obj->setSize(pow(1000, 8));
		$this->obj->renderContents($writer);
		self::assertEquals('1 yottabyte', $writer->flush());
		
		$this->obj->setSize(pow(1000, 9));
		$this->obj->renderContents($writer);
		self::assertEquals('1 ronnabyte', $writer->flush());
		
		$this->obj->setSize(pow(1000, 10));
		$this->obj->renderContents($writer);
		self::assertEquals('1 quettabyte', $writer->flush());
	}
	
	public function testNonMarketingSizeAbbreviated()
	{
		$writer = new TTextWriter;
		
		// Set culture to English for explicit string assertions
		$this->obj->setCulture('en');
		
		$this->obj->setAbbreviate(true);
		$this->obj->setUseMarketingSize(false);
		
		
		$this->obj->setSize(0);
		$this->obj->renderContents($writer);
		self::assertEquals('0 B', $writer->flush());
		
		$this->obj->setSize(1);
		$this->obj->renderContents($writer);
		self::assertEquals('1 B', $writer->flush());
		
		$this->obj->setSize(1023);
		$this->obj->renderContents($writer);
		self::assertEquals('1,023 B', $writer->flush());
		
		$this->obj->setSize(pow(1024, 1));
		$this->obj->renderContents($writer);
		self::assertEquals('1 KiB', $writer->flush());
		
		$this->obj->setSize(1100);
		$this->obj->renderContents($writer);
		self::assertEquals('1.07 KiB', $writer->flush());
		
		$this->obj->setSize(pow(1024, 2));
		$this->obj->renderContents($writer);
		self::assertEquals('1 MiB', $writer->flush());
		
		$this->obj->setSize(pow(1024, 3));
		$this->obj->renderContents($writer);
		self::assertEquals('1 GiB', $writer->flush());
		
		$this->obj->setSize(pow(1024, 4));
		$this->obj->renderContents($writer);
		self::assertEquals('1 TiB', $writer->flush());
		
		$this->obj->setSize(pow(1024, 5));
		$this->obj->renderContents($writer);
		self::assertEquals('1 PiB', $writer->flush());
		
		$this->obj->setSize(pow(1024, 6));
		$this->obj->renderContents($writer);
		self::assertEquals('1 EiB', $writer->flush());
		
		$this->obj->setSize(pow(1024, 7));
		$this->obj->renderContents($writer);
		self::assertEquals('1 ZiB', $writer->flush());
		
		// Test yobibyte (1024^8)
		$this->obj->setSize(pow(1024, 8));
		$this->obj->renderContents($writer);
		self::assertEquals('1 YiB', $writer->flush());
		
		$this->obj->setSize(pow(1024, 9));
		$this->obj->renderContents($writer);
		self::assertEquals('1 RiB', $writer->flush());
		
		$this->obj->setSize(pow(1024, 10));
		$this->obj->renderContents($writer);
		self::assertEquals('1 QiB', $writer->flush());
	}
	
	public function testNonMarketingSizeNonAbbreviated()
	{
		$writer = new TTextWriter;
		
		// Set culture to English for explicit string assertions
		$this->obj->setCulture('en');
		
		$this->obj->setAbbreviate(false);
		$this->obj->setUseMarketingSize(false);
		
		
		$this->obj->setSize(0);
		$this->obj->renderContents($writer);
		self::assertEquals('0 bytes', $writer->flush());
		
		$this->obj->setSize(1);
		$this->obj->renderContents($writer);
		self::assertEquals('1 byte', $writer->flush());
		
		$this->obj->setSize(2);
		$this->obj->renderContents($writer);
		self::assertEquals('2 bytes', $writer->flush());
		
		$this->obj->setSize(pow(1024, 1));
		$this->obj->renderContents($writer);
		self::assertEquals('1 kibibyte', $writer->flush());
		
		$this->obj->setSize(1100);
		$this->obj->renderContents($writer);
		self::assertEquals('1.07 kibibytes', $writer->flush());
		
		$this->obj->setSize(pow(1024, 2));
		$this->obj->renderContents($writer);
		self::assertEquals('1 mebibyte', $writer->flush());
		
		$this->obj->setSize(pow(1024, 3));
		$this->obj->renderContents($writer);
		self::assertEquals('1 gibibyte', $writer->flush());
		
		$this->obj->setSize(pow(1024, 4));
		$this->obj->renderContents($writer);
		self::assertEquals('1 tebibyte', $writer->flush());
		
		$this->obj->setSize(pow(1024, 5));
		$this->obj->renderContents($writer);
		self::assertEquals('1 pebibyte', $writer->flush());
		
		$this->obj->setSize(pow(1024, 6));
		$this->obj->renderContents($writer);
		self::assertEquals('1 exbibyte', $writer->flush());
		
		$this->obj->setSize(pow(1024, 7));
		$this->obj->renderContents($writer);
		self::assertEquals('1 zebibyte', $writer->flush());
		
		// Test yobibyte (1024^8)
		$this->obj->setSize(pow(1024, 8));
		$this->obj->renderContents($writer);
		self::assertEquals('1 yobibyte', $writer->flush());
		
		$this->obj->setSize(pow(1024, 9));
		$this->obj->renderContents($writer);
		self::assertEquals('1 robibyte', $writer->flush());
		
		$this->obj->setSize(pow(1024, 10));
		$this->obj->renderContents($writer);
		self::assertEquals('1 quebibyte', $writer->flush());
	}
	
	/**
	 * Test plural forms for non-abbreviated decimal sizes
	 */
	public function testMarketingSizePluralForms()
	{
		$writer = new TTextWriter;
		
		// Set culture to English for explicit string assertions
		$this->obj->setCulture('en');
		
		$this->obj->setAbbreviate(false);
		$this->obj->setUseMarketingSize(true);
		
		// Test plural forms with values > 1
		$this->obj->setSize(2 * pow(1000, 1));
		$this->obj->renderContents($writer);
		self::assertEquals('2 kilobytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1000, 2));
		$this->obj->renderContents($writer);
		self::assertEquals('2 megabytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1000, 3));
		$this->obj->renderContents($writer);
		self::assertEquals('2 gigabytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1000, 4));
		$this->obj->renderContents($writer);
		self::assertEquals('2 terabytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1000, 5));
		$this->obj->renderContents($writer);
		self::assertEquals('2 petabytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1000, 6));
		$this->obj->renderContents($writer);
		self::assertEquals('2 exabytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1000, 7));
		$this->obj->renderContents($writer);
		self::assertEquals('2 zettabytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1000, 8));
		$this->obj->renderContents($writer);
		self::assertEquals('2 yottabytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1000, 9));
		$this->obj->renderContents($writer);
		self::assertEquals('2 ronnabytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1000, 10));
		$this->obj->renderContents($writer);
		self::assertEquals('2 quettabytes', $writer->flush());
	}
	
	/**
	 * Test plural forms for non-abbreviated binary sizes
	 */
	public function testNonMarketingSizePluralForms()
	{
		$writer = new TTextWriter;
		
		// Set culture to English for explicit string assertions
		$this->obj->setCulture('en');
		
		$this->obj->setAbbreviate(false);
		$this->obj->setUseMarketingSize(false);
		
		// Test plural forms with values > 1
		$this->obj->setSize(2 * pow(1024, 1));
		$this->obj->renderContents($writer);
		self::assertEquals('2 kibibytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1024, 2));
		$this->obj->renderContents($writer);
		self::assertEquals('2 mebibytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1024, 3));
		$this->obj->renderContents($writer);
		self::assertEquals('2 gibibytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1024, 4));
		$this->obj->renderContents($writer);
		self::assertEquals('2 tebibytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1024, 5));
		$this->obj->renderContents($writer);
		self::assertEquals('2 pebibytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1024, 6));
		$this->obj->renderContents($writer);
		self::assertEquals('2 exbibytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1024, 7));
		$this->obj->renderContents($writer);
		self::assertEquals('2 zebibytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1024, 8));
		$this->obj->renderContents($writer);
		self::assertEquals('2 yobibytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1024, 9));
		$this->obj->renderContents($writer);
		self::assertEquals('2 robibytes', $writer->flush());
		
		$this->obj->setSize(2 * pow(1024, 10));
		$this->obj->renderContents($writer);
		self::assertEquals('2 quebibytes', $writer->flush());
	}
	
	/**
	 * Test decimal place limiting for large numbers
	 */
	public function testDecimalPlaceLimiting()
	{
		$writer = new TTextWriter;
		
		// Set culture to English for explicit string assertions
		$this->obj->setCulture('en');
		
		$this->obj->setAbbreviate(true);
		$this->obj->setUseMarketingSize(true);
		
		// Test with numbers that would have more than 4 digits
		// This test ensures that numbers are properly truncated to appropriate decimal places
		$this->obj->setSize(12345600000000000000000000000001); // 10^30 + x
		$this->obj->renderContents($writer);
		$output = $writer->flush();
		
		self::assertEquals('12.3 QB', $output);
		
		$this->obj->setUseMarketingSize(false);
		
		$this->obj->setSize(16.326 * 1024000000000000000000000000000); // 1024^8 + 1
		$this->obj->renderContents($writer);
		$output = $writer->flush();
		// Should be formatted as "1 QiB"
		self::assertEquals('13.2 QiB', $output);
	}
	
	/**
	 * Test sizes larger than quettabytes and quebibytes
	 */
	public function testSizesBeyondQuettabytes()
	{
		$writer = new TTextWriter;
		
		// Set culture to English for explicit string assertions
		$this->obj->setCulture('en');
		
		$this->obj->setAbbreviate(true);
		$this->obj->setUseMarketingSize(true);
		
		// Test values larger than what the system supports (should still format properly)
		$this->obj->setSize(pow(1000, 10) * 1100);
		$this->obj->renderContents($writer);
		$output = $writer->flush();
		// Should format with the highest available unit (QB for marketing) in this implementation
		self::assertEquals('1,100 QB', $output);
		
		$this->obj->setUseMarketingSize(false);
		
		$this->obj->setSize(pow(1024, 10) * 1100); 
		$this->obj->renderContents($writer);
		$output = $writer->flush();
		// Should format with the highest available unit (QiB for binary) in this implementation
		self::assertEquals('1,100 QiB', $output);
		
		$this->obj->setAbbreviate(false);
		$this->obj->setUseMarketingSize(true);
		
		// Test values larger than what the system supports (should still format properly)
		$this->obj->setSize(pow(1000, 10) * 1100);
		$this->obj->renderContents($writer);
		$output = $writer->flush();
		// Should format with the highest available unit (QB for marketing) in this implementation
		self::assertEquals('1,100 quettabytes', $output);
		
		$this->obj->setUseMarketingSize(false);
		
		$this->obj->setSize(pow(1024, 10) * 1100); 
		$this->obj->renderContents($writer);
		$output = $writer->flush();
		// Should format with the highest available unit (QiB for binary) in this implementation
		self::assertEquals('1,100 quebibytes', $output);
	}
	
	public function testRenderContents()
	{
		$writer = new TTextWriter;
		
		// First test with abbreviated marketing sizes (powers of 1000)
		{
			$this->obj->setAbbreviate(true);
			$this->obj->setUseMarketingSize(true);
			
			$this->obj->setSize(0);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/0/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]B/i', $output));
			
			$this->obj->setSize(994);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/994/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]B/i', $output));
			
			$this->obj->setSize(pow(1000, 1));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kB/i', $output));
			
			$this->obj->setSize(1100);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kB/i', $output));
			
			$this->obj->setSize(pow(1000, 2));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]MB/i', $output));
			
			$this->obj->setSize(pow(1000, 3));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]GB/i', $output));
			
			$this->obj->setSize(pow(1000, 4));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]TB/i', $output));
			
			$this->obj->setSize(pow(1000, 5));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]PB/i', $output));
			
			$this->obj->setSize(pow(1000, 6));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]EB/i', $output));
			
			$this->obj->setSize(pow(1000, 7));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]ZB/i', $output));
			
			$this->obj->setSize(pow(1000, 8));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]YB/i', $output));
			
			$this->obj->setSize(pow(1000, 9));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]RB/i', $output));
			
			$this->obj->setSize(pow(1000, 10));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]QB/i', $output));
			
			// Test for maximum supported size (quettabytes 1000^10)
			$this->obj->setSize(1010 * pow(1000, 10));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1,010[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]QB/i', $output));
		}
		
		// Second test with abbreviated binary sizes (powers of 1024)
		{
			$this->obj->setAbbreviate(true);
			$this->obj->setUseMarketingSize(false);
			
			$this->obj->setSize(0);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/0/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]B/i', $output));
			
			$this->obj->setSize(1023);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1,023[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]B/i', $output));
			
			$this->obj->setSize(pow(1024, 1));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]KiB/i', $output));
			
			$this->obj->setSize(1100 * 1.024);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]KiB/i', $output));
			
			$this->obj->setSize(pow(1024, 2));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]MiB/i', $output));
			
			$this->obj->setSize(pow(1024, 3));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]GiB/i', $output));
			
			$this->obj->setSize(pow(1024, 4));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]TiB/i', $output));
			
			$this->obj->setSize(pow(1024, 5));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]PiB/i', $output));
			
			$this->obj->setSize(pow(1024, 6));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]EiB/i', $output));
			
			$this->obj->setSize(pow(1024, 7));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]ZiB/i', $output));
			
			$this->obj->setSize(pow(1024, 8));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]YiB/i', $output));
			
			$this->obj->setSize(pow(1024, 9));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]RiB/i', $output));
			
			$this->obj->setSize(pow(1024, 10));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]QiB/i', $output));
			
			// Test for maximum supported size (quebibytes 1024^10)
			$this->obj->setSize(1010 * pow(1024, 10));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1,010[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]QiB/i', $output));
		}
		
		// Third test with non-abbreviated marketing sizes
		{
			$this->obj->setAbbreviate(false);
			$this->obj->setUseMarketingSize(true);
			
			$this->obj->setSize(0);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/0/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]bytes/i', $output));
			
			$this->obj->setSize(1);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]byte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(994);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/994/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]bytes/i', $output));
			
			$this->obj->setSize(pow(1000, 1));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kilobyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 1));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kilobytes/i', $output));
			
			$this->obj->setSize(pow(1000, 2));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]megabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 2));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]megabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 3));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]gigabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 3));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]gigabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 4));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]terabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 4));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]terabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 5));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]petabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 5));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]petabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 6));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]exabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 6));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]exabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 7));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]zettabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 7));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]zettabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 8));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]yottabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 8));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]yottabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 9));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]ronnabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 9));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]ronnabytes/i', $output));
			
			$this->obj->setSize(pow(1000, 10));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]quettabyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1000, 10));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]quettabytes/i', $output));
			
			// Test for maximum supported size (quettabytes 1000^10)
			$this->obj->setSize(1010 * pow(1000, 10));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1,010[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]quettabytes/i', $output));
		}
		// Fourth test with non-abbreviated binary sizes
		{
			$this->obj->setAbbreviate(false);
			$this->obj->setUseMarketingSize(false);
			
			$this->obj->setSize(0);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/0/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]bytes/i', $output));
			
			$this->obj->setSize(1);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]byte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(994);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/994/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]bytes/i', $output));
			
			$this->obj->setSize(1023);
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1,023/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]bytes/i', $output));
			
			$this->obj->setSize(pow(1024, 1));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kibibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 1));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]kibibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 2));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]mebibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 2));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]mebibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 3));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]gibibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 3));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]gibibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 4));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]tebibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 4));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]tebibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 5));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]pebibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 5));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]pebibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 6));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]exbibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 6));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]exbibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 7));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]zebibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 7));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]zebibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 8));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]yobibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 8));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]yobibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 9));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]robibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 9));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]robibytes/i', $output));
			
			$this->obj->setSize(pow(1024, 10));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]quebibyte[^a-zA-Z]*/i', $output));
			
			$this->obj->setSize(1.1 * pow(1024, 10));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1.1[^\d]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]quebibytes/i', $output));
			
			// Test for maximum supported size (yobibyte)
			$this->obj->setSize(1010 * pow(1024, 10));
			$this->obj->renderContents($writer);
			$output = $writer->flush();
			self::assertEquals(1, preg_match('/1,010[^\d\.]/', $output));
			self::assertEquals(1, preg_match('/[^a-zA-Z]quebibytes[^a-zA-Z]*/i', $output));
		}
	}
}

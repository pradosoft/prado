<?php



class TGravatarTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TGravatar();
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		self::assertInstanceOf('\\Prado\\Web\\UI\\WebControls\\TGravatar', $this->obj);
	}

	public function testDefault()
	{
		self::assertNull($this->obj->getDefault());
		self::assertTrue(0 === preg_match('/d=/', $this->obj->getImageUrl()));
		
		$this->obj->setDefault('Mp'); //Mystery Person
		self::assertEquals('mp', $this->obj->getDefault());
		self::assertTrue(1 === preg_match('/d=mp/', $this->obj->getImageUrl()));
		
		$this->obj->setDefault('identicon'); //Mystery Person
		self::assertEquals('identicon', $this->obj->getDefault());
		self::assertTrue(1 === preg_match('/d=identicon/', $this->obj->getImageUrl()));
		
		$this->obj->setDefault('monsterid'); //Mystery Person
		self::assertEquals('monsterid', $this->obj->getDefault());
		self::assertTrue(1 === preg_match('/d=monsterid/', $this->obj->getImageUrl()));
		
		$this->obj->setDefault('wavatar'); //Mystery Person
		self::assertEquals('wavatar', $this->obj->getDefault());
		self::assertTrue(1 === preg_match('/d=wavatar/', $this->obj->getImageUrl()));
		
		$this->obj->setDefault('retro'); //Mystery Person
		self::assertEquals('retro', $this->obj->getDefault());
		self::assertTrue(1 === preg_match('/d=retro/', $this->obj->getImageUrl()));
		
		$this->obj->setDefault('robohash'); //Mystery Person
		self::assertEquals('robohash', $this->obj->getDefault());
		self::assertTrue(1 === preg_match('/d=robohash/', $this->obj->getImageUrl()));
		
		$this->obj->setDefault('Blank'); //Mystery Person
		self::assertEquals('blank', $this->obj->getDefault());
		self::assertTrue(1 === preg_match('/d=blank/', $this->obj->getImageUrl()));
		
		$this->obj->setDefault('404'); //Mystery Person
		self::assertEquals('404', $this->obj->getDefault());
		self::assertTrue(1 === preg_match('/d=404/', $this->obj->getImageUrl()));
		
		$this->obj->setDefault(''); //nothing
		self::assertNull($this->obj->getDefault());
		self::assertTrue(0 === preg_match('/d=/', $this->obj->getImageUrl()));
		
		$this->obj->setDefault(null); //nothing
		self::assertNull($this->obj->getDefault());
		self::assertTrue(0 === preg_match('/d=/', $this->obj->getImageUrl()));
		
		try {
			$this->obj->setDefault('incorrect'); //nothing
			$this->fail('TGravatar Default should  have been raised on bad Default');
		} catch(Prado\Exceptions\TInvalidDataValueException $e) {
		}
		
		$url = 'http://GitHub.com/#tag?value=1 2';
		$this->obj->setDefault($url);
		self::assertEquals($url, $this->obj->getDefault());
		$search = '/d=' . addslashes(rawurlencode($url)) . '/';
		self::assertTrue(1 === preg_match($search, $this->obj->getImageUrl()));
		
		$url = 'https://GitHub.com/#tag?value=1 2';
		$this->obj->setDefault($url);
		self::assertEquals($url, $this->obj->getDefault());
		$search = '/d=' . addslashes(rawurlencode($url)) . '/';
		self::assertTrue(1 === preg_match('/d=' . addslashes(rawurlencode($url)) . '/', $this->obj->getImageUrl()));
	}
	
	public function testSize()
	{
		self::assertNull($this->obj->getSize());
		self::assertTrue(0 === preg_match('/s=/', $this->obj->getImageUrl()));
		
		$this->obj->setSize(1);
		self::assertEquals(1, $this->obj->getSize());
		self::assertTrue(1 === preg_match('/s=1/', $this->obj->getImageUrl()));
		
		$this->obj->setSize(512);
		self::assertEquals(512, $this->obj->getSize());
		self::assertTrue(1 === preg_match('/s=512/', $this->obj->getImageUrl()));
		
		$this->obj->setSize(null);
		self::assertNull($this->obj->getSize());
		self::assertTrue(0 === preg_match('/s=/', $this->obj->getImageUrl()));
		
		try {
			$this->obj->setSize(0);
			$this->fail('TGravatar size did not throw TInvalidDataValueException when size is 0');
		} catch(Prado\Exceptions\TInvalidDataValueException $e) {
		}
		try {
			$this->obj->setDefault(513);
			$this->fail('TGravatar size did not throw TInvalidDataValueException when size is 513');
		} catch(Prado\Exceptions\TInvalidDataValueException $e) {
		}
	}
	
	public function testRating()
	{
		self::assertNull($this->obj->getRating());
		self::assertTrue(0 === preg_match('/r=/', $this->obj->getImageUrl()));
		
		$this->obj->setRating('G');
		self::assertEquals('g', $this->obj->getRating());
		self::assertTrue(1 === preg_match('/r=g/', $this->obj->getImageUrl()));
		
		$this->obj->setRating('pg');
		self::assertEquals('pg', $this->obj->getRating());
		self::assertTrue(1 === preg_match('/r=pg/', $this->obj->getImageUrl()));
		
		$this->obj->setRating('r');
		self::assertEquals('r', $this->obj->getRating());
		self::assertTrue(1 === preg_match('/r=r/', $this->obj->getImageUrl()));
		
		$this->obj->setRating('x');
		self::assertEquals('x', $this->obj->getRating());
		self::assertTrue(1 === preg_match('/r=x/', $this->obj->getImageUrl()));
		
		$this->obj->setRating('');
		self::assertNull($this->obj->getRating());
		self::assertTrue(0 === preg_match('/r=/', $this->obj->getImageUrl()));
		
		$this->obj->setRating(null);
		self::assertNull($this->obj->getRating());
		self::assertTrue(0 === preg_match('/r=/', $this->obj->getImageUrl()));
	}
	
	public function testEmail()
	{
		
		self::assertEquals('', $this->obj->getEmail());
		self::assertTrue(1 === preg_match('/' . md5('') . '/', $this->obj->getImageUrl()));
		
		$email = 'Belisoful@iCloud.Com ';
		$this->obj->setEmail($email);
		self::assertEquals($email, $this->obj->getEmail());
		$email = 'belisoful@icloud.com';
		self::assertTrue(1 === preg_match('/' . md5($email) . '/', $this->obj->getImageUrl()));
	}
	
	public function testUseSecureUrl()
	{
		self::assertEquals(Prado::getApplication()->getRequest()->getIsSecureConnection(), $this->obj->getUseSecureUrl());
		
		$this->obj->setUseSecureUrl(true);
		self::assertTrue($this->obj->getUseSecureUrl());
		self::assertTrue(1 === preg_match('/^https.*gravatar.com\/avatar\//', $this->obj->getImageUrl()));
		self::assertTrue(0 === preg_match('/^http[^s].*gravatar.com\/avatar\//', $this->obj->getImageUrl()));
		
		$this->obj->setUseSecureUrl(false);
		self::assertFalse($this->obj->getUseSecureUrl());
		self::assertTrue(0 === preg_match('/^https.*gravatar.com\/avatar\//', $this->obj->getImageUrl()));
		self::assertTrue(1 === preg_match('/^http[^s].*gravatar.com\/avatar\//', $this->obj->getImageUrl()));
	}
}

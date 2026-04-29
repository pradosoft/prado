<?php

use Prado\Web\UI\WebControls\TGravatar;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use Prado\Exceptions\TInvalidDataValueException;
use PHPUnit\Framework\TestCase;

class TGravatarTest extends TestCase
{
	private TGravatar $gravatar;

	protected function setUp(): void
	{
		$this->gravatar = new TGravatar();
	}

	private function render($control)
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		return $tw->flush();
	}

	// ================================================================================
	// Constructor and Default State Tests
	// ================================================================================

	public function testDefaultState()
	{
		$gravatar = new TGravatar();
		$this->assertEquals('', $gravatar->getEmail());
		$this->assertNull($gravatar->getSize());
		$this->assertNull($gravatar->getRating());
		$this->assertNull($gravatar->getDefaultImageStyle());
	}

	// ================================================================================
	// Email Tests
	// ================================================================================

	public function testGetEmailEmpty()
	{
		$this->assertEquals('', $this->gravatar->getEmail());
	}

	public function testSetEmail()
	{
		$this->gravatar->setEmail('Test@example.com');
		$this->assertEquals('Test@example.com', $this->gravatar->getEmail());
	}

	public function testSetEmailTrimsAndLowersInUrl()
	{
		$this->gravatar->setEmail('Test@Example.COM ');
		$this->assertEquals('Test@Example.COM ', $this->gravatar->getEmail());

		$url = $this->gravatar->getImageUrl();
		$expectedHash = md5(strtolower(trim('Test@Example.COM')));
		$this->assertStringContainsString($expectedHash, $url);
	}

	public function testEmailHashInUrl()
	{
		$this->gravatar->setEmail('test@example.com');
		$url = $this->gravatar->getImageUrl();
		$this->assertStringContainsString(md5('test@example.com'), $url);
	}

	public function testEmailCaseNormalization()
	{
		$this->gravatar->setEmail('Test@Example.Com');
		$url = $this->gravatar->getImageUrl();
		$expectedHash = md5(strtolower('test@example.com'));
		$this->assertStringContainsString($expectedHash, $url);
	}

	// ================================================================================
	// Size Tests
	// ================================================================================

	public function testSizeDefaultsNull()
	{
		$this->assertNull($this->gravatar->getSize());
	}

	public function testSetSizeValidValues()
	{
		$this->gravatar->setSize(1);
		$this->assertEquals(1, $this->gravatar->getSize());

		$this->gravatar->setSize(80);
		$this->assertEquals(80, $this->gravatar->getSize());

		$this->gravatar->setSize(512);
		$this->assertEquals(512, $this->gravatar->getSize());
	}

	public function testSetSizeZeroThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->gravatar->setSize(0);
	}

	public function testSetSize513Throws()
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->gravatar->setSize(513);
	}

	public function testSetSizeNegativeThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->gravatar->setSize(-1);
	}

	public function testSetSizeNull()
	{
		$this->gravatar->setSize(100);
		$this->assertEquals(100, $this->gravatar->getSize());

		$this->gravatar->setSize(null);
		$this->assertNull($this->gravatar->getSize());
	}

	public function testSetSizeEmptyString()
	{
		$this->gravatar->setSize(100);
		$this->assertEquals(100, $this->gravatar->getSize());

		$this->gravatar->setSize('');
		$this->assertNull($this->gravatar->getSize());
	}

	public function testSizeInUrl()
	{
		$this->gravatar->setSize(120);
		$url = $this->gravatar->getImageUrl();
		$this->assertStringContainsString('s=120', $url);
	}

	public function testSizeNotInUrlWhenNull()
	{
		$url = $this->gravatar->getImageUrl();
		$this->assertStringNotContainsString('s=', $url);
	}

	// ================================================================================
	// Rating Tests
	// ================================================================================

	public function testRatingDefaultsNull()
	{
		$this->assertNull($this->gravatar->getRating());
	}

	public function testSetRatingValidValues()
	{
		$this->gravatar->setRating('g');
		$this->assertEquals('g', $this->gravatar->getRating());

		$this->gravatar->setRating('pg');
		$this->assertEquals('pg', $this->gravatar->getRating());

		$this->gravatar->setRating('r');
		$this->assertEquals('r', $this->gravatar->getRating());

		$this->gravatar->setRating('x');
		$this->assertEquals('x', $this->gravatar->getRating());
	}

	public function testRatingCaseInsensitive()
	{
		$this->gravatar->setRating('G');
		$this->assertEquals('g', $this->gravatar->getRating());

		$this->gravatar->setRating('PG');
		$this->assertEquals('pg', $this->gravatar->getRating());
	}

	public function testRatingEmptyString()
	{
		$this->gravatar->setRating('g');
		$this->gravatar->setRating('');
		$this->assertNull($this->gravatar->getRating());
	}

	public function testRatingNull()
	{
		$this->gravatar->setRating('g');
		$this->gravatar->setRating(null);
		$this->assertNull($this->gravatar->getRating());
	}

	public function testRatingInUrl()
	{
		$this->gravatar->setRating('pg');
		$url = $this->gravatar->getImageUrl();
		$this->assertStringContainsString('r=pg', $url);
	}

	public function testRatingNotInUrlWhenNull()
	{
		$url = $this->gravatar->getImageUrl();
		$this->assertStringNotContainsString('r=', $url);
	}

	// ================================================================================
	// DefaultImageStyle Tests
	// ================================================================================

	public function testDefaultImageStyleDefaultsNull()
	{
		$this->assertNull($this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyleMp()
	{
		$this->gravatar->setDefaultImageStyle('mp');
		$this->assertEquals('mp', $this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyleIdenticon()
	{
		$this->gravatar->setDefaultImageStyle('identicon');
		$this->assertEquals('identicon', $this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyleMonsterid()
	{
		$this->gravatar->setDefaultImageStyle('monsterid');
		$this->assertEquals('monsterid', $this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyleWavatar()
	{
		$this->gravatar->setDefaultImageStyle('wavatar');
		$this->assertEquals('wavatar', $this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyleRetro()
	{
		$this->gravatar->setDefaultImageStyle('retro');
		$this->assertEquals('retro', $this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyleRobohash()
	{
		$this->gravatar->setDefaultImageStyle('robohash');
		$this->assertEquals('robohash', $this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyleBlank()
	{
		$this->gravatar->setDefaultImageStyle('blank');
		$this->assertEquals('blank', $this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyle404()
	{
		$this->gravatar->setDefaultImageStyle('404');
		$this->assertEquals('404', $this->gravatar->getDefaultImageStyle());
	}

	public function testDefaultImageStyleCaseInsensitive()
	{
		$this->gravatar->setDefaultImageStyle('MP');
		$this->assertEquals('mp', $this->gravatar->getDefaultImageStyle());

		$this->gravatar->setDefaultImageStyle('Identicon');
		$this->assertEquals('identicon', $this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyleEmptyString()
	{
		$this->gravatar->setDefaultImageStyle('mp');
		$this->gravatar->setDefaultImageStyle('');
		$this->assertNull($this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyleNull()
	{
		$this->gravatar->setDefaultImageStyle('mp');
		$this->gravatar->setDefaultImageStyle(null);
		$this->assertNull($this->gravatar->getDefaultImageStyle());
	}

	public function testSetDefaultImageStyleInvalidThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->gravatar->setDefaultImageStyle('invalid_style');
	}

	public function testDefaultImageStyleInUrl()
	{
		$this->gravatar->setDefaultImageStyle('mp');
		$url = $this->gravatar->getImageUrl();
		$this->assertStringContainsString('d=mp', $url);
	}

	public function testDefaultImageStyleNotInUrlWhenNull()
	{
		$url = $this->gravatar->getImageUrl();
		$this->assertStringNotContainsString('d=', $url);
	}

	public function testSetDefaultImageStyleWithHttpUrl()
	{
		$url = 'http://example.com/default.png';
		$this->gravatar->setDefaultImageStyle($url);
		$this->assertEquals($url, $this->gravatar->getDefaultImageStyle());

		$imageUrl = $this->gravatar->getImageUrl();
		$this->assertStringContainsString('d=' . rawurlencode($url), $imageUrl);
	}

	public function testSetDefaultImageStyleWithHttpsUrl()
	{
		$url = 'https://example.com/default.png';
		$this->gravatar->setDefaultImageStyle($url);
		$this->assertEquals($url, $this->gravatar->getDefaultImageStyle());

		$imageUrl = $this->gravatar->getImageUrl();
		$this->assertStringContainsString('d=' . rawurlencode($url), $imageUrl);
	}

	public function testSetDefaultImageStyleWithUrlWithSpaces()
	{
		$url = 'http://example.com/image.png?tag=my tag';
		$this->gravatar->setDefaultImageStyle($url);
		$imageUrl = $this->gravatar->getImageUrl();
		$this->assertStringContainsString('d=' . rawurlencode($url), $imageUrl);
	}

	// ================================================================================
	// UseSecureUrl Tests
	// ================================================================================

	public function testUseSecureUrlDefaultsToRequestConnection()
	{
		$gravatar = new TGravatar();
		$this->assertIsBool($gravatar->getUseSecureUrl());
	}

	public function testSetUseSecureUrlTrue()
	{
		$this->gravatar->setUseSecureUrl(true);
		$this->assertTrue($this->gravatar->getUseSecureUrl());
	}

	public function testSetUseSecureUrlFalse()
	{
		$this->gravatar->setUseSecureUrl(false);
		$this->assertFalse($this->gravatar->getUseSecureUrl());
	}

	public function testHttpUrlInImageUrl()
	{
		$this->gravatar->setUseSecureUrl(false);
		$url = $this->gravatar->getImageUrl();
		$this->assertStringStartsWith('http://www.gravatar.com/avatar/', $url);
	}

	public function testHttpsUrlInImageUrl()
	{
		$this->gravatar->setUseSecureUrl(true);
		$url = $this->gravatar->getImageUrl();
		$this->assertStringStartsWith('https://secure.gravatar.com/avatar/', $url);
	}

	// ================================================================================
	// ImageUrl Generation Tests
	// ================================================================================

	public function testImageUrlWithAllParameters()
	{
		$this->gravatar->setEmail('test@example.com');
		$this->gravatar->setSize(100);
		$this->gravatar->setRating('pg');
		$this->gravatar->setDefaultImageStyle('identicon');
		$this->gravatar->setUseSecureUrl(true);

		$url = $this->gravatar->getImageUrl();

		$this->assertStringContainsString(md5('test@example.com'), $url);
		$this->assertStringContainsString('s=100', $url);
		$this->assertStringContainsString('r=pg', $url);
		$this->assertStringContainsString('d=identicon', $url);
		$this->assertStringStartsWith('https://secure.gravatar.com/avatar/', $url);
	}

	public function testImageUrlWithEmptyEmail()
	{
		$url = $this->gravatar->getImageUrl();
		$this->assertStringContainsString(md5(''), $url);
	}

	public function testImageUrlParameterOrder()
	{
		$this->gravatar->setEmail('test@example.com');
		$this->gravatar->setSize(80);
		$this->gravatar->setRating('g');
		$this->gravatar->setDefaultImageStyle('mp');

		$url = $this->gravatar->getImageUrl();

		$posS = strpos($url, 's=80');
		$posR = strpos($url, 'r=g');
		$posD = strpos($url, 'd=mp');

		$this->assertNotFalse($posS);
		$this->assertNotFalse($posR);
		$this->assertNotFalse($posD);

		$this->assertTrue($posS < $posR);
		$this->assertTrue($posR < $posD);
	}

	public function testImageUrlNoExtraParameters()
	{
		$this->gravatar->setEmail('test@example.com');
		$this->gravatar->setSize(80);
		$this->gravatar->setRating('pg');
		$this->gravatar->setDefaultImageStyle('mp');
		$url = $this->gravatar->getImageUrl();

		$queryString = parse_url($url, PHP_URL_QUERY);
		parse_str($queryString, $params);

		$this->assertArrayHasKey('s', $params);
		$this->assertArrayHasKey('r', $params);
		$this->assertArrayHasKey('d', $params);
		$this->assertCount(3, $params);
	}

	// ================================================================================
	// TGravatar Constants Tests
	// ================================================================================

	public function testHttpUrlConstant()
	{
		$this->assertEquals('http://www.gravatar.com/avatar/', TGravatar::HTTP_URL);
	}

	public function testHttpsUrlConstant()
	{
		$this->assertEquals('https://secure.gravatar.com/avatar/', TGravatar::HTTPS_URL);
	}

	// ================================================================================
	// Rendering Tests
	// ================================================================================

	public function testRenderSimpleImage()
	{
		$this->gravatar->setID('gravatar');
		$this->gravatar->setEmail('test@example.com');

		$output = $this->render($this->gravatar);
		$this->assertStringContainsString('<img', $output);
		$this->assertStringContainsString('id="gravatar"', $output);
		$this->assertStringContainsString('src="', $output);
	}

	public function testRenderWithAltText()
	{
		$this->gravatar->setID('gravatar');
		$this->gravatar->setEmail('test@example.com');
		$this->gravatar->setAlternateText('User Gravatar');

		$output = $this->render($this->gravatar);
		$this->assertStringContainsString('alt="User Gravatar"', $output);
	}

	public function testRenderWithCssClass()
	{
		$this->gravatar->setID('gravatar');
		$this->gravatar->setEmail('test@example.com');
		$this->gravatar->setCssClass('gravatar-img');

		$output = $this->render($this->gravatar);
		$this->assertStringContainsString('class="gravatar-img"', $output);
	}

	// ================================================================================
	// Edge Cases
	// ================================================================================

	public function testAllDefaultImageStylesInUrl()
	{
		$styles = ['mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank', '404'];

		foreach ($styles as $style) {
			$this->gravatar->setDefaultImageStyle($style);
			$url = $this->gravatar->getImageUrl();
			$this->assertStringContainsString("d={$style}", $url, "Style '$style' should be in URL");
		}
	}

	public function testAllRatingsInUrl()
	{
		$ratings = ['g', 'pg', 'r', 'x'];

		foreach ($ratings as $rating) {
			$this->gravatar->setRating($rating);
			$url = $this->gravatar->getImageUrl();
			$this->assertStringContainsString("r={$rating}", $url, "Rating '$rating' should be in URL");
		}
	}

	public function testChainedPropertySetters()
	{
		$this->gravatar->setEmail('test@example.com');
		$this->gravatar->setSize(100);
		$this->gravatar->setRating('g');
		$this->gravatar->setDefaultImageStyle('mp');
		$this->gravatar->setUseSecureUrl(true);

		$this->assertEquals('test@example.com', $this->gravatar->getEmail());
		$this->assertEquals(100, $this->gravatar->getSize());
		$this->assertEquals('g', $this->gravatar->getRating());
		$this->assertEquals('mp', $this->gravatar->getDefaultImageStyle());
		$this->assertTrue($this->gravatar->getUseSecureUrl());
	}

	public function testEmailWithSpecialCharacters()
	{
		$this->gravatar->setEmail('test+special@example.com');
		$url = $this->gravatar->getImageUrl();
		$expectedHash = md5(strtolower(trim('test+special@example.com')));
		$this->assertStringContainsString($expectedHash, $url);
	}

	public function testSizeBoundaryValues()
	{
		$this->gravatar->setSize(1);
		$this->assertEquals(1, $this->gravatar->getSize());

		$this->gravatar->setSize(512);
		$this->assertEquals(512, $this->gravatar->getSize());
	}

	public function testSizeStringConversion()
	{
		$this->gravatar->setSize('80');
		$this->assertEquals(80, $this->gravatar->getSize());
	}

	public function testImageUrlRfc3986Encoding()
	{
		$this->gravatar->setEmail('test@example.com');
		$this->gravatar->setDefaultImageStyle('mp');

		$url = $this->gravatar->getImageUrl();
		$this->assertStringContainsString('d=mp', $url);

		$this->gravatar->setDefaultImageStyle('http://example.com/image with space.png');
		$url = $this->gravatar->getImageUrl();
		$this->assertStringContainsString('d=http%3A%2F%2Fexample.com%2Fimage%20with%20space.png', $url);
	}
}

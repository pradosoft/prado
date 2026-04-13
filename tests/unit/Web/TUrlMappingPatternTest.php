<?php

use Prado\Web\THttpRequest;
use Prado\Web\THttpRequestUrlFormat;
use Prado\Web\TUrlManager;
use Prado\Web\TUrlMappingPattern;
use Prado\Web\TUrlMappingPatternSecureConnection;
use Prado\TApplication;
use Prado\Collections\TAttributeCollection;

/**
 * Test class for TUrlMappingPattern.
 * 
 * @coversDefaultClass Prado\Web\TUrlMappingPattern
 */
class TUrlMappingPatternTest extends PHPUnit\Framework\TestCase
{
	protected static $app = null;
	private $urlManager;

	protected function setUp(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['REQUEST_URI'] = '/index.php?page=Home';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['QUERY_STRING'] = 'page=Home';
		$_SERVER['SCRIPT_FILENAME'] = __FILE__;
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['REQUEST_METHOD'] = 'GET';

		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/app');
		}
		
		$this->urlManager = new TUrlManager();
		$this->urlManager->init(null);
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		$_GET = [];
		$_POST = [];
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['QUERY_STRING'] = '';
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	private function createRequest($pathInfo = '', $method = 'GET')
	{
		$_SERVER['PATH_INFO'] = $pathInfo;
		$_SERVER['REQUEST_METHOD'] = $method;
		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		return $request;
	}

	public function testConstructor()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertInstanceOf(TUrlMappingPattern::class, $pattern);
		$this->assertSame($this->urlManager, $pattern->getManager());
	}

	public function testInitWithServiceParameter()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->init(null);
		$this->assertFalse($pattern->getIsWildCardPattern());
	}

	public function testInitWithWildcardServiceParameter()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.*');
		$pattern->setPattern('test');
		$pattern->init(null);
		$this->assertTrue($pattern->getIsWildCardPattern());
	}

	public function testInitThrowsException()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setPattern('test');
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$pattern->init(null);
	}

	public function testGetSetServiceParameter()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$this->assertEquals('Test.Page', $pattern->getServiceParameter());
	}

	public function testGetSetServiceID()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertEquals('page', $pattern->getServiceID());
		$pattern->setServiceID('custom');
		$this->assertEquals('custom', $pattern->getServiceID());
	}

	public function testGetSetPattern()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertEquals('', $pattern->getPattern());
		$pattern->setPattern('test/{id}');
		$this->assertEquals('test/{id}', $pattern->getPattern());
	}

	public function testGetSetParameters()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$params = $pattern->getParameters();
		$this->assertInstanceOf(TAttributeCollection::class, $params);
	}

	public function testGetSetConstants()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$constants = $pattern->getConstants();
		$this->assertInstanceOf(TAttributeCollection::class, $constants);
	}

	public function testGetSetRegularExpression()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertEquals('', $pattern->getRegularExpression());
		$pattern->setRegularExpression('#^test/(?P<id>\d+)$#');
		$this->assertEquals('#^test/(?P<id>\d+)$#', $pattern->getRegularExpression());
	}

	public function testGetSetCaseSensitive()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertTrue($pattern->getCaseSensitive());
		$pattern->setCaseSensitive(false);
		$this->assertFalse($pattern->getCaseSensitive());
	}

	public function testGetSetEnableCustomUrl()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertTrue($pattern->getEnableCustomUrl());
		$pattern->setEnableCustomUrl(false);
		$this->assertFalse($pattern->getEnableCustomUrl());
	}

	public function testGetSetUrlFormat()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertEquals(THttpRequestUrlFormat::Get, $pattern->getUrlFormat());
		$pattern->setUrlFormat(THttpRequestUrlFormat::Path);
		$this->assertEquals(THttpRequestUrlFormat::Path, $pattern->getUrlFormat());
	}

	public function testGetSetUrlParamSeparator()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertEquals('/', $pattern->getUrlParamSeparator());
		$pattern->setUrlParamSeparator('-');
		$this->assertEquals('-', $pattern->getUrlParamSeparator());
	}

	public function testSetUrlParamSeparatorInvalid()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$pattern->setUrlParamSeparator('too-long');
	}

	public function testGetSetSecureConnection()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertEquals(TUrlMappingPatternSecureConnection::Automatic, $pattern->getSecureConnection());
		$pattern->setSecureConnection(TUrlMappingPatternSecureConnection::Enable);
		$this->assertEquals(TUrlMappingPatternSecureConnection::Enable, $pattern->getSecureConnection());
	}

	// ===== Verb Tests =====

	public function testGetSetVerbsNull()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertNull($pattern->getVerbs());
		$pattern->setVerbs(null);
		$this->assertNull($pattern->getVerbs());
	}

	public function testGetSetVerbsEmptyString()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setVerbs('');
		$this->assertNull($pattern->getVerbs());
	}

	public function testGetSetVerbsString()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setVerbs('GET, POST, PUT');
		$this->assertEquals(['GET', 'POST', 'PUT'], $pattern->getVerbs());
	}

	public function testGetSetVerbsArray()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setVerbs(['GET', 'POST']);
		$this->assertEquals(['GET', 'POST'], $pattern->getVerbs());
	}

	public function testGetSetVerbsWithNegationTilde()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setVerbs('~GET,POST');
		$this->assertEquals(['~GET', 'POST'], $pattern->getVerbs());
	}

	public function testGetSetVerbsWithNegationExclamation()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setVerbs('!GET,POST');
		$this->assertEquals(['!GET', 'POST'], $pattern->getVerbs());
	}

	public function testGetSetVerbsEmptyArray()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setVerbs([]);
		$this->assertNull($pattern->getVerbs());
	}

	public function testGetIsWildCardPattern()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertFalse($pattern->getIsWildCardPattern());
		$pattern->setServiceParameter('Test.*');
		$pattern->setPattern('test');
		$pattern->init(null);
		$this->assertTrue($pattern->getIsWildCardPattern());
	}

	// ===== getPatternMatches tests - All Branches =====

	public function testGetPatternMatchesVerbsNullAllowsAnyVerb()
	{
		// Branch: $verbs === null - skip verb check, path doesn't match
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs(null);
		
		$request = $this->createRequest('/other');
		$result = $pattern->getPatternMatches($request);
		
		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesVerbNotInList()
	{
		// Branch: !in_array(requestVerb, verbs) - return []
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs(['POST']);
		
		$request = $this->createRequest('/test', 'GET');
		$result = $pattern->getPatternMatches($request);
		
		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesVerbExcludedWithTilde()
	{
		// Branch: in_array('~verb', verbs) - return []
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs(['~GET']);
		
		$request = $this->createRequest('/test', 'GET');
		$result = $pattern->getPatternMatches($request);
		
		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesVerbExcludedWithExclamation()
	{
		// Branch: in_array('!verb', verbs) - return []
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs(['!GET']);
		
		$request = $this->createRequest('/test', 'GET');
		$result = $pattern->getPatternMatches($request);
		
		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesNoRegexNoMatch()
	{
		// Branch: no regexp, no pattern match
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test/{id}');
		$pattern->getParameters()->add('id', '\d+');
		
		$request = $this->createRequest('/other/123');
		$result = $pattern->getPatternMatches($request);
		
		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesEmptyPathInfo()
	{
		// Branch: empty path info
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		
		$request = $this->createRequest('');
		$result = $pattern->getPatternMatches($request);
		
		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesWildcardNoMatch()
	{
		// Branch: wildcard but no match for serviceID key
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.SubPage');
		$pattern->setPattern('test/{*}');
		$pattern->setServiceID('page');
		$pattern->init(null);
		
		$request = $this->createRequest('/other/value');
		$result = $pattern->getPatternMatches($request);
		
		// No match for serviceID, returns empty
		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesUrlParamsWithUrlFormat()
	{
		// Branch: urlparams with Path format, separator = /
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setUrlFormat(THttpRequestUrlFormat::Path);
		
		$request = $this->createRequest('/test/foo/bar');
		$result = $pattern->getPatternMatches($request);
		
		$this->assertArrayHasKey('foo', $result);
		$this->assertEquals('bar', $result['foo']);
	}

	public function testGetPatternMatchesUrlParamsCustomSeparator()
	{
		// Branch: urlparams with custom separator
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setUrlFormat(THttpRequestUrlFormat::Path);
		$pattern->setUrlParamSeparator('-');
		
		$request = $this->createRequest('/test/foo-bar');
		$result = $pattern->getPatternMatches($request);
		
		$this->assertArrayHasKey('foo', $result);
		$this->assertEquals('bar', $result['foo']);
	}

	public function testGetPatternMatchesConstantsWithNoPatternMatch()
	{
		// Branch: constants but no pattern match - constants not added
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->getConstants()->add('type', 'detailed');
		
		$request = $this->createRequest('/other');
		$result = $pattern->getPatternMatches($request);
		
		$this->assertArrayNotHasKey('type', $result);
	}

	public function testGetPatternMatchesVerbInListAllowsMatch()
	{
		// Branch: verb in list, pattern matches
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs(['GET']);
		
		$request = $this->createRequest('/test', 'GET');
		$result = $pattern->getPatternMatches($request);
		
		// Result has 'page' key because of ServiceID default
		$this->assertNotEquals([], $result);
	}

	// ===== supportCustomUrl tests =====

	public function testSupportCustomUrlNoParams()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->setEnableCustomUrl(true);
		
		$this->assertFalse($pattern->supportCustomUrl([]));
	}

	public function testSupportCustomUrlWithParams()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->setEnableCustomUrl(true);
		
		$this->assertTrue($pattern->supportCustomUrl(['id' => '123']));
	}

	public function testSupportCustomUrlDisabled()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setEnableCustomUrl(false);
		
		$this->assertFalse($pattern->supportCustomUrl(['id' => '123']));
	}

	public function testSupportCustomUrlNoPattern()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setEnableCustomUrl(true);
		
		$this->assertFalse($pattern->supportCustomUrl([]));
	}

	public function testSupportCustomUrlWithConstantsMatch()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->getConstants()->add('type', 'detailed');
		$pattern->setEnableCustomUrl(true);
		
		$this->assertTrue($pattern->supportCustomUrl(['type' => 'detailed']));
	}

	public function testSupportCustomUrlWithConstantsMismatch()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->getConstants()->add('type', 'detailed');
		$pattern->setEnableCustomUrl(true);
		
		$this->assertFalse($pattern->supportCustomUrl(['type' => 'other']));
	}
}
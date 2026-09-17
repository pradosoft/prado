<?php

namespace Prado\Test\Unit\Web;

use Prado\Web\THttpRequest;
use Prado\Web\THttpRequestUrlFormat;
use Prado\Web\TUrlManager;
use Prado\Web\TUrlMappingPattern;
use Prado\Web\TUrlMappingPatternSecureConnection;
use Prado\Web\TUrlMappingPatternUrlMatchMode;
use Prado\Collections\TAttributeCollection;
use Prado\Test\Unit\Harness\TTestApplication;

/**
 * Test class for TUrlMappingPattern.
 *
 * @coversDefaultClass Prado\Web\TUrlMappingPattern
 */
class TUrlMappingPatternTest extends \PHPUnit\Framework\TestCase
{
	protected ?TTestApplication $app = null;
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

		$this->app = new TTestApplication(__DIR__ . '/app');

		$this->urlManager = new TUrlManager();
		$this->urlManager->init(null);
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
		$_GET = [];
		$_POST = [];
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['QUERY_STRING'] = '';
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	private function createRequest($pathInfo = '', $method = 'GET', $queryString = '')
	{
		$_SERVER['PATH_INFO'] = $pathInfo;
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['QUERY_STRING'] = $queryString;
		$_GET = [];
		if ($queryString !== '') {
			parse_str($queryString, $_GET);
		}
		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		// init() forces the request method to GET when the script runs in command line
		$_SERVER['REQUEST_METHOD'] = $method;
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

	// ===== UrlMatchMode Tests =====

	public function testGetSetUrlMatchMode()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertEquals(TUrlMappingPatternUrlMatchMode::PathInfo, $pattern->getUrlMatchMode());
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);
		$this->assertEquals(TUrlMappingPatternUrlMatchMode::Full, $pattern->getUrlMatchMode());
	}

	public function testSetUrlMatchModeInvalid()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$pattern->setUrlMatchMode('NotAMode');
	}

	public function testGetPatternMatchesPathInfoModeIgnoresQueryString()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');

		$request = $this->createRequest('/test', 'GET', 'mode=edit');
		$result = $pattern->getPatternMatches($request);

		$this->assertNotEquals([], $result);
	}

	public function testGetPatternMatchesFullModeLiteralQuery()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}?mode=edit');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);

		$request = $this->createRequest('/post/123', 'GET', 'mode=edit');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals('123', $result['id']);
	}

	public function testGetPatternMatchesFullModeQueryMismatch()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}?mode=edit');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);

		$request = $this->createRequest('/post/123', 'GET', 'mode=view');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesFullModeQueryParameter()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}?mode={mode}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getParameters()->add('mode', 'edit|view');
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);

		$request = $this->createRequest('/post/123', 'GET', 'mode=view');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals('123', $result['id']);
		$this->assertEquals('view', $result['mode']);
	}

	public function testGetPatternMatchesFullModeWithoutQueryString()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);

		$request = $this->createRequest('/test');
		$result = $pattern->getPatternMatches($request);

		$this->assertNotEquals([], $result);
	}

	public function testGetPatternMatchesFullModeRejectsUnexpectedQueryString()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);

		$request = $this->createRequest('/test', 'GET', 'mode=edit');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesFullModeWithPathUrlFormat()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test?mode=edit');
		$pattern->setUrlFormat(THttpRequestUrlFormat::Path);
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);

		$request = $this->createRequest('/test/foo/bar', 'GET', 'mode=edit');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals('bar', $result['foo']);
	}

	public function testGetPatternMatchesFullModeRegularExpression()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->setRegularExpression('/^\/post\/(?P<id>\d+)\?mode=edit$/u');
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);

		$request = $this->createRequest('/post/123', 'GET', 'mode=edit');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals('123', $result['id']);
	}

	public function testGetPatternMatchesFullModeCaseInsensitive()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post?mode=edit');
		$pattern->setCaseSensitive(false);
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);

		$request = $this->createRequest('/Post', 'GET', 'mode=EDIT');
		$result = $pattern->getPatternMatches($request);

		$this->assertNotEquals([], $result);
	}

	// ===== Query Constraint Tests =====

	public function testGetSetQuery()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$this->assertInstanceOf(TAttributeCollection::class, $pattern->getQuery());
		$this->assertTrue($pattern->getQuery()->getCaseSensitive());

		$collection = new TAttributeCollection();
		$collection->add('mode', 'edit');
		$pattern->setQuery($collection);
		$this->assertSame($collection, $pattern->getQuery());
	}

	public function testGetPatternMatchesQueryConstraint()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getQuery()->add('mode', 'edit|preview');

		$request = $this->createRequest('/post/123', 'GET', 'mode=edit');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals('123', $result['id']);
	}

	public function testGetPatternMatchesQueryConstraintOrderIndependent()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getQuery()->add('mode', 'edit|preview');

		$request = $this->createRequest('/post/123', 'GET', 'ref=list&mode=preview');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals('123', $result['id']);
	}

	public function testGetPatternMatchesQueryConstraintMissingVariable()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getQuery()->add('mode', 'edit|preview');

		$request = $this->createRequest('/post/123');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesQueryConstraintValueMismatch()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getQuery()->add('mode', 'edit|preview');

		$request = $this->createRequest('/post/123', 'GET', 'mode=delete');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesQueryConstraintMatchesWholeValue()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getQuery()->add('mode', 'edit');

		$request = $this->createRequest('/post/123', 'GET', 'mode=editor');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesQueryConstraintArrayValue()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getQuery()->add('mode', 'edit');

		$request = $this->createRequest('/post/123', 'GET', 'mode[]=edit');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals([], $result);
	}

	public function testGetPatternMatchesQueryConstraintCaseInsensitive()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getQuery()->add('mode', 'edit');
		$pattern->setCaseSensitive(false);

		$request = $this->createRequest('/post/123', 'GET', 'mode=EDIT');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals('123', $result['id']);
	}

	public function testSupportCustomUrlWithQueryConstraintMatch()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getQuery()->add('mode', 'edit|preview');
		$pattern->setEnableCustomUrl(true);

		$this->assertTrue($pattern->supportCustomUrl(['id' => '123', 'mode' => 'edit']));
	}

	public function testSupportCustomUrlWithQueryConstraintMismatch()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getQuery()->add('mode', 'edit|preview');
		$pattern->setEnableCustomUrl(true);

		$this->assertFalse($pattern->supportCustomUrl(['id' => '123', 'mode' => 'delete']));
	}

	public function testSupportCustomUrlWithQueryConstraintMissing()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getQuery()->add('mode', 'edit|preview');
		$pattern->setEnableCustomUrl(true);

		$this->assertFalse($pattern->supportCustomUrl(['id' => '123']));
	}

	public function testGetPatternMatchesFullModeWithQueryConstraint()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}?mode={mode}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getParameters()->add('mode', '\w+');
		$pattern->getQuery()->add('mode', 'edit|preview');
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);

		$request = $this->createRequest('/post/123', 'GET', 'mode=preview');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals('123', $result['id']);
	}

	public function testGetPatternMatchesFullModeQueryConstraintNarrows()
	{
		// the pattern matches the query string, the constraint rejects the value
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('post/{id}?mode={mode}');
		$pattern->getParameters()->add('id', '\d+');
		$pattern->getParameters()->add('mode', '\w+');
		$pattern->getQuery()->add('mode', 'edit|preview');
		$pattern->setUrlMatchMode(TUrlMappingPatternUrlMatchMode::Full);

		$request = $this->createRequest('/post/123', 'GET', 'mode=delete');
		$result = $pattern->getPatternMatches($request);

		$this->assertEquals([], $result);
	}

	// ===== Verb Negation Tests =====

	public function testGetPatternMatchesVerbNegationAllowsOtherVerbs()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs('!DELETE');

		$this->assertNotEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'GET')));
		$this->assertNotEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'POST')));
		$this->assertEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'DELETE')));
	}

	public function testGetPatternMatchesMultipleVerbNegations()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs('!PUT, ~DELETE');

		$this->assertNotEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'GET')));
		$this->assertEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'PUT')));
		$this->assertEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'DELETE')));
	}

	public function testGetPatternMatchesVerbInclusionAndNegation()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs('GET,POST,!POST');

		$this->assertNotEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'GET')));
		$this->assertEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'POST')));
		$this->assertEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'PUT')));
	}

	public function testGetPatternMatchesVerbIgnoresCase()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs('get');

		$this->assertNotEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'GET')));
		$this->assertEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'POST')));
	}

	public function testGetPatternMatchesVerbNegationIgnoresCase()
	{
		$pattern = new TUrlMappingPattern($this->urlManager);
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs('~delete');

		$this->assertNotEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'GET')));
		$this->assertEquals([], $pattern->getPatternMatches($this->createRequest('/test', 'DELETE')));
	}
}

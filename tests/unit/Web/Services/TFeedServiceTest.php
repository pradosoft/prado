<?php

/**
 * TFeedServiceTest
 *
 * Unit tests for {@see \Prado\Web\Services\TFeedService}.
 *
 * Covers the order the service applies a feed provider's configured properties
 * in, from a PHP application configuration.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;
use Prado\Test\Unit\Harness\Traits\TNestedPathTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\IFeedContentProvider;
use Prado\Web\Services\TFeedService;
use Prado\Xml\TXmlDocument;

/**
 * A feed provider carrying a nested 'Cfg' property, serving empty content so
 * the service's response handling stays trivial.
 */
class TNestedPathFeedProvider extends TComponent implements IFeedContentProvider
{
	use TNestedPathTrait;

	/** @var ?TNestedPathFeedProvider the provider the service built last. */
	public static $last = null;

	public function __construct()
	{
		parent::__construct();
		self::$last = $this;
	}

	/** @var mixed the config init() received. */
	public $initConfig = false;

	public function init($config)
	{
		$this->initConfig = $config;
	}
	public function getFeedContent()
	{
		return 'the-feed-content';
	}
	public function getContentType()
	{
		return 'application/rss+xml';
	}
}

/**
 * A provider class that does not implement IFeedContentProvider, which the
 * service refuses.
 */
class TNotAFeedProvider extends TComponent
{
}

class TFeedServiceTest extends \PHPUnit\Framework\TestCase
{
	private $_configurationType;
	private $_serviceParameter;

	protected function setUp(): void
	{
		$this->_configurationType = Prado::getApplication()->getConfigurationType();
		$this->_serviceParameter = Prado::getApplication()->getRequest()->getServiceParameter();
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->setConfigurationType($this->_configurationType);
		Prado::getApplication()->getRequest()->setServiceParameter($this->_serviceParameter);
	}

	/**
	 * A feed provider's configured properties apply parent path first, so a
	 * nested value survives whatever order the configuration declares them in.
	 */
	public function testRunAppliesParentPathBeforeNestedPath()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		Prado::getApplication()->getRequest()->setServiceParameter('nested');

		$obLevel = ob_get_level();
		try {
			foreach ([
				['Cfg.Size' => 'child', 'Cfg' => 'value'],
				['Cfg' => 'value', 'Cfg.Size' => 'child'],
			] as $properties) {
				$service = new TFeedService();
				PradoUnit::setProp($service, '_feeds', ['nested' => [
					'class' => TNestedPathFeedProvider::class,
					'properties' => $properties,
				]]);
				TNestedPathFeedProvider::$last = null;
				$service->run();

				$this->assertNotNull(TNestedPathFeedProvider::$last);
				$this->assertEquals('child', TNestedPathFeedProvider::$last->getCfg()->getSize(), 'declared: ' . implode(', ', array_keys($properties)));
			}
		} finally {
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
		}
	}

	/**
	 * Builds a service from a PHP configuration and runs it for the given ID.
	 * @param array $feeds the feed definitions, placed under the `feeds` key.
	 * @param string $id the feed to request.
	 */
	private function runPhp(array $feeds, string $id): void
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		Prado::getApplication()->getRequest()->setServiceParameter($id);
		$service = new TFeedService();
		$service->init(['class' => TFeedService::class, 'feeds' => $feeds]);
		$service->run();
	}

	/**
	 * Initializes a service from a PHP configuration and returns its registered feeds.
	 * @param mixed $config the service configuration.
	 * @return array the registered feeds.
	 */
	private function phpFeeds(mixed $config): array
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$service = new TFeedService();
		$service->init($config);
		return PradoUnit::getProp($service, '_feeds');
	}

	/**
	 * Builds a service from an XML configuration and runs it for the given ID.
	 * @param string $elements the `<feed>` elements as written.
	 * @param string $id the feed to request.
	 */
	private function runXml(string $elements, string $id): void
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		Prado::getApplication()->getRequest()->setServiceParameter($id);
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<service>' . $elements . '</service>');
		$service = new TFeedService();
		$service->init($config);
		$service->run();
	}

	/**
	 * Runs the given callable with the response output buffer restored after.
	 * @param callable $run the work to run.
	 */
	private function withResponse(callable $run): void
	{
		$obLevel = ob_get_level();
		ob_start();
		try {
			$run();
		} finally {
			// discards both this buffer and any the response opened
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
		}
	}

	// -------------------------------------------------------------------------
	// init() — configuration branches
	// -------------------------------------------------------------------------

	/**
	 * A PHP configuration registers each entry of `feeds` under its ID.
	 */
	public function testPhpConfigurationRegistersTheFeeds()
	{
		$feeds = $this->phpFeeds(['class' => TFeedService::class, 'feeds' => ['a' => ['class' => TNestedPathFeedProvider::class]]]);
		$this->assertSame(['a' => ['class' => TNestedPathFeedProvider::class]], $feeds);
	}

	/**
	 * A string entry of `feeds` is the feed class; values that are neither arrays nor strings are skipped.
	 */
	public function testPhpConfigurationStringFeedIsTheClass()
	{
		$feeds = $this->phpFeeds(['feeds' => ['a' => TNestedPathFeedProvider::class, 'b' => null, 'c' => 5]]);
		$this->assertSame(['a' => ['class' => TNestedPathFeedProvider::class]], $feeds);
	}

	/**
	 * Entries of `feeds` register without checking their class; run() validates it.
	 */
	public function testPhpConfigurationFeedsAreNotFiltered()
	{
		$feeds = $this->phpFeeds(['feeds' => ['a' => ['properties' => []], 'b' => TNotAFeedProvider::class]]);
		$this->assertSame(['a', 'b'], array_keys($feeds));
	}

	/**
	 * A PHP configuration that is not an array registers nothing.
	 */
	public function testPhpConfigurationThatIsNotAnArrayRegistersNothing()
	{
		$this->assertSame([], $this->phpFeeds(null));
	}

	// -------------------------------------------------------------------------
	// init() — 4.3 feeds beside the service class (deprecated)
	// -------------------------------------------------------------------------

	/**
	 * A 4.3 feed beside the service `class` key registers when its class is a feed provider.
	 */
	public function testLegacyPhpConfigurationRegistersFeedProviders()
	{
		$feeds = $this->phpFeeds([
			'class' => TFeedService::class,
			'a' => ['class' => TNestedPathFeedProvider::class, 'properties' => ['Cfg' => 'value']],
			'b' => TNestedPathFeedProvider::class,
		]);
		$this->assertSame([
			'a' => ['class' => TNestedPathFeedProvider::class, 'properties' => ['Cfg' => 'value']],
			'b' => ['class' => TNestedPathFeedProvider::class],
		], $feeds);
	}

	/**
	 * A 4.3 entry is skipped without error when its key is reserved or its class is not a feed provider.
	 */
	public function testLegacyPhpConfigurationSkipsReservedKeysAndOtherClasses()
	{
		$feeds = $this->phpFeeds([
			'class' => TFeedService::class,
			'properties' => ['class' => TNestedPathFeedProvider::class],
			'parameters' => ['p' => 'v'],
			'modules' => ['m' => ['class' => TNestedPathFeedProvider::class]],
			'paths' => TNestedPathFeedProvider::class,
			'includes' => [],
			'application' => ['class' => TNestedPathFeedProvider::class],
			'errormessages' => [],
			'services' => [],
			'noClass' => ['properties' => []],
			'notProvider' => ['class' => TNotAFeedProvider::class],
			'notProviderString' => TNotAFeedProvider::class,
			'unknownClass' => 'No\\Such\\FeedClass',
			'scalar' => 5,
			'null' => null,
		]);
		$this->assertSame([], $feeds);
	}

	/**
	 * A feed under `feeds` takes precedence over a 4.3 feed of the same ID.
	 */
	public function testLegacyPhpConfigurationDoesNotReplaceFeeds()
	{
		$feeds = $this->phpFeeds([
			'feeds' => ['a' => ['class' => TNestedPathFeedProvider::class, 'properties' => ['Cfg' => 'new']]],
			'a' => ['class' => TNestedPathFeedProvider::class, 'properties' => ['Cfg' => 'old']],
			'b' => TNestedPathFeedProvider::class,
		]);
		$this->assertSame('new', $feeds['a']['properties']['Cfg']);
		$this->assertSame(['class' => TNestedPathFeedProvider::class], $feeds['b']);
	}

	/**
	 * Requesting the service `class` key as a feed ID is a 404.
	 */
	public function testRunWithTheServiceClassKeyIsNotFound()
	{
		$this->expectException(THttpException::class);
		$this->runPhp(['a' => ['class' => TNestedPathFeedProvider::class]], 'class');
	}

	/**
	 * Requesting a service-level configuration key as a feed ID is a 404.
	 */
	public function testRunWithAServiceConfigurationKeyIsNotFound()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		Prado::getApplication()->getRequest()->setServiceParameter('modules');
		$service = new TFeedService();
		$service->init(['class' => TFeedService::class, 'modules' => ['m' => ['class' => TNestedPathFeedProvider::class]]]);
		$this->expectException(THttpException::class);
		$service->run();
	}

	/**
	 * An XML configuration registers each `<feed>` element under its id attribute.
	 */
	public function testXmlConfigurationRegistersTheFeeds()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<service><feed id="a" class="' . TNestedPathFeedProvider::class . '"/></service>');
		$service = new TFeedService();
		$service->init($config);
		$this->assertArrayHasKey('a', PradoUnit::getProp($service, '_feeds'));
	}

	/**
	 * An XML-configured service without a configuration element registers no feed.
	 */
	public function testXmlConfigurationThatIsNullRegistersNothing()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$service = new TFeedService();
		$service->init(null);
		$this->assertSame([], PradoUnit::getProp($service, '_feeds'));
	}

	/**
	 * A `<feed>` element without an id attribute is refused.
	 */
	public function testXmlConfigurationWithoutAnIdIsRefused()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<service><feed class="' . TNestedPathFeedProvider::class . '"/></service>');
		$service = new TFeedService();
		$this->expectException(TConfigurationException::class);
		$service->init($config);
	}

	// -------------------------------------------------------------------------
	// determineRequestedFeedPath()
	// -------------------------------------------------------------------------

	/**
	 * The requested feed path is the request's service parameter.
	 */
	public function testDetermineRequestedFeedPathReadsTheServiceParameter()
	{
		Prado::getApplication()->getRequest()->setServiceParameter('the-feed');
		$service = new TFeedService();
		$this->assertSame('the-feed', PradoUnit::invoke($service, 'determineRequestedFeedPath'));
	}

	// -------------------------------------------------------------------------
	// run() — dispatch
	// -------------------------------------------------------------------------

	/**
	 * A PHP configuration builds the named provider, applies its properties, and
	 * serves its content.
	 */
	public function testRunFromPhpConfigurationServesTheFeed()
	{
		$this->withResponse(function () {
			$this->runPhp(['a' => [
				'class' => TNestedPathFeedProvider::class,
				'properties' => ['Cfg' => 'value'],
			]], 'a');
		});

		$this->assertNotNull(TNestedPathFeedProvider::$last);
		$this->assertSame('parent:value', TNestedPathFeedProvider::$last->getCfg()->getSize());
		$this->assertIsArray(TNestedPathFeedProvider::$last->initConfig);
	}

	/**
	 * A PHP feed definition without properties is accepted.
	 */
	public function testRunFromPhpConfigurationWithoutPropertiesIsAccepted()
	{
		$this->withResponse(function () {
			$this->runPhp(['a' => ['class' => TNestedPathFeedProvider::class]], 'a');
		});
		$this->assertSame('unset', TNestedPathFeedProvider::$last->getCfg()->getSize());
	}

	/**
	 * A PHP feed definition without a class is refused.
	 */
	public function testRunFromPhpConfigurationWithoutAClassIsRefused()
	{
		try {
			$this->runPhp(['a' => ['properties' => []]], 'a');
			$this->fail('TConfigurationException was not raised.');
		} catch (TConfigurationException $e) {
			$this->assertSame('feedservice_class_required', $e->getErrorCode());
		}
	}

	/**
	 * A PHP feed class that is not an IFeedContentProvider is refused.
	 */
	public function testRunFromPhpConfigurationWithAnInvalidProviderIsRefused()
	{
		try {
			$this->runPhp(['a' => ['class' => TNotAFeedProvider::class]], 'a');
			$this->fail('TConfigurationException was not raised.');
		} catch (TConfigurationException $e) {
			$this->assertSame('feedservice_feedtype_invalid', $e->getErrorCode());
		}
	}

	/**
	 * An XML configuration builds the named provider and applies its attributes.
	 */
	public function testRunFromXmlConfigurationServesTheFeed()
	{
		$this->withResponse(function () {
			$this->runXml('<feed id="a" class="' . TNestedPathFeedProvider::class . '" Cfg="value"/>', 'a');
		});

		$this->assertNotNull(TNestedPathFeedProvider::$last);
		$this->assertSame('parent:value', TNestedPathFeedProvider::$last->getCfg()->getSize());
	}

	/**
	 * An XML feed definition without a class attribute is refused.
	 */
	public function testRunFromXmlConfigurationWithoutAClassIsRefused()
	{
		$this->expectException(TConfigurationException::class);
		$this->runXml('<feed id="a"/>', 'a');
	}

	/**
	 * An XML feed class that is not an IFeedContentProvider is refused.
	 */
	public function testRunFromXmlConfigurationWithAnInvalidProviderIsRefused()
	{
		$this->expectException(TConfigurationException::class);
		$this->runXml('<feed id="a" class="' . TNotAFeedProvider::class . '"/>', 'a');
	}

	/**
	 * An unregistered feed ID is a 404.
	 */
	public function testRunWithAnUnknownFeedIdIsNotFound()
	{
		$this->expectException(THttpException::class);
		$this->runPhp(['a' => ['class' => TNestedPathFeedProvider::class]], 'nosuchfeed');
	}
}

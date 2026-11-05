<?php

/**
 * TFeedService and TFeed class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

use Prado\Prado;
use Prado\TApplication;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Xml\TXmlElement;

/**
 * TFeedService class
 *
 * TFeedService provides to end-users feed content.
 *
 * TFeedService manages a set of feeds. The service parameter, referring
 * to the ID of the feed, specifies which feed content to be provided to end-users.
 *
 * XML configuration style:
 * ```xml
 *  <service id="feed" class="Prado\Web\Services\TFeedService">
 *    <feed id="ch1" class="Path\To\FeedClass1" .../>
 *    <feed id="ch2" class="Path\To\FeedClass2" .../>
 *    <feed id="ch3" class="Path\To\FeedClass3" .../>
 *  </service>
 * ```
 * where each &lt;feed&gt; element specifies a feed identified by its "id" value (case-sensitive).
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'services' => [
 *         'feed' => [
 *             'class' => 'Prado\Web\Services\TFeedService',
 *             'feeds' => [
 *                 'ch1' => [
 *                     'class' => 'Path\To\FeedClass1',
 *                     'properties' => [...],
 *                 ],
 *                 'ch2' => ['class' => 'Path\To\FeedClass2'],
 *                 'ch3' => 'Path\To\FeedClass3',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 * where each entry of `feeds` is a feed indexed by its ID. A string feed entry
 * is the feed class, as "ch3" shows. A feed beside the `class` key, the 4.3
 * form, is deprecated; see {@see loadLegacyPhpFeeds()}.
 *
 * The class attribute indicates which PHP class will provide the actual feed
 * content. Note, the class must implement {@see \Prado\Web\Services\IFeedContentProvider} interface.
 * Other initial properties for the feed class may also be specified in the
 * corresponding &lt;feed&gt; element.
 *
 * To retrieve the feed content identified by "ch2", use the URL
 * ```
 * /path/to/index.php?feed=ch2
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @since 3.1
 */
class TFeedService extends \Prado\TService
{
	private $_feeds = [];

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * A PHP configuration registers each array entry of `feeds` as a feed, registers a
	 * string entry as the feed class, and skips other values. An XML configuration
	 * registers each `<feed>` element. A `null` configuration registers no feed.
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration for this module, can be null
	 */
	public function init($config)
	{
		if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
			if (is_array($config)) {
				foreach ($config['feeds'] ?? [] as $id => $feed) {
					if (is_string($feed)) {
						$feed = ['class' => $feed];
					}
					if (is_array($feed)) {
						$this->_feeds[$id] = $feed;
					}
				}
				$this->loadLegacyPhpFeeds($config);
			}
		} elseif ($config instanceof TXmlElement) {
			foreach ($config->getElementsByTagName('feed') as $feed) {
				if (($id = $feed->getAttributes()->remove('id')) !== null) {
					$this->_feeds[$id] = $feed;
				} else {
					throw new TConfigurationException('feedservice_id_required');
				}
			}
		}
		parent::init($config);
	}

	/**
	 * Registers the feeds of a 4.3 PHP configuration, which places them beside the
	 * service `class` key. An entry registers when:
	 * - its key is not a reserved service or configuration key, and
	 * - its class, the string entry or the array entry's `class`, implements {@see IFeedContentProvider}.
	 *
	 * Other entries are skipped without error. A feed under `feeds` takes precedence.
	 * This method and its call in {@see init()} support the 4.3 form and are removed in a later release.
	 * @param array $config the service configuration.
	 * @deprecated since 4.4.0. Place feeds under the `feeds` key.
	 */
	private function loadLegacyPhpFeeds(array $config): void
	{
		static $reserved = ['class', 'feeds', 'properties', 'application', 'errormessages', 'includes', 'modules', 'parameters', 'paths', 'services'];
		foreach ($config as $id => $feed) {
			if (in_array($id, $reserved, true) || isset($this->_feeds[$id])) {
				continue;
			}
			$class = is_array($feed) ? ($feed['class'] ?? null) : $feed;
			if (!is_string($class) || !is_string($class = Prado::usingClass($class)) || !is_a($class, IFeedContentProvider::class, true)) {
				continue;
			}
			$this->_feeds[$id] = is_array($feed) ? $feed : ['class' => $feed];
		}
	}

	/**
	 * @return string the requested feed path
	 */
	protected function determineRequestedFeedPath()
	{
		return $this->getRequest()->getServiceParameter();
	}

	/**
	 * Runs the service.
	 * This method is invoked by application automatically.
	 */
	public function run()
	{
		$this->ensureCsrfToken();
		$id = $this->getRequest()->getServiceParameter();
		if (isset($this->_feeds[$id])) {
			$feedConfig = $this->_feeds[$id];
			$properties = [];
			$feed = null;
			if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
				if (isset($feedConfig['class'])) {
					$feed = Prado::createComponent($feedConfig['class']);
					if ($feed instanceof IFeedContentProvider) {
						$properties = $feedConfig['properties'] ?? [];
					} else {
						throw new TConfigurationException('feedservice_feedtype_invalid', $id);
					}
				} else {
					throw new TConfigurationException('feedservice_class_required', $id);
				}
			} else {
				$properties = $feedConfig->getAttributes();
				if (($class = $properties->remove('class')) !== null) {
					$feed = Prado::createComponent($class);
					if (!($feed instanceof IFeedContentProvider)) {
						throw new TConfigurationException('feedservice_feedtype_invalid', $id);
					}
				} else {
					throw new TConfigurationException('feedservice_class_required', $id);
				}
			}

			// init feed properties; the provider interface does not carry the
			// property API, so the component behind it applies them
			/** @var \Prado\TComponent $component */
			// @phpstan-ignore varTag.nativeType
			$component = $feed;
			$component->setSubProperties($properties);
			$feed->init($feedConfig);

			$content = $feed->getFeedContent();
			//$this->getResponse()->setContentType('application/rss+xml');
			$this->getResponse()->setContentType($feed->getContentType());
			$this->getResponse()->write($content);
		} else {
			throw new THttpException(404, 'feedservice_feed_unknown', $id);
		}
	}
}

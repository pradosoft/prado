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
 * where each entry of `feeds` is a feed indexed by its ID, the form Prado 4.4 reads.
 * A string feed entry is the feed class, as "ch3" shows. A feed array beside the
 * `class` key, the 4.3 form, still loads and is deprecated; Prado 4.4 loads it only
 * when its class implements {@see IFeedContentProvider}.
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
	 * A PHP configuration registers each entry of `feeds`, an array entry as the feed and a
	 * string entry as the feed class. It then registers each array entry beside the service
	 * `class` key, the deprecated 4.3 form; a feed under `feeds` takes precedence.
	 * A `feeds` array with a string `class` is a 4.3 feed whose ID is "feeds".
	 * @param mixed $config configuration for this module, can be null
	 */
	public function init($config)
	{
		if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
			if (is_array($config)) {
				$feeds = $config['feeds'] ?? null;
				$isFeedList = is_array($feeds) && !is_string($feeds['class'] ?? null);
				if ($isFeedList) {
					foreach ($feeds as $id => $feed) {
						if (is_string($feed)) {
							$feed = ['class' => $feed];
						}
						if (is_array($feed)) {
							$this->_feeds[$id] = $feed;
						}
					}
				}
				foreach ($config as $id => $feed) {
					if ($id === 'class' || ($id === 'feeds' && $isFeedList) || !is_array($feed) || isset($this->_feeds[$id])) {
						continue;
					}
					$this->_feeds[$id] = $feed;
				}
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

			// init feed properties
			foreach ($properties as $name => $value) {
				/** @var \Prado\TComponent $feed */
				// @phpstan-ignore varTag.nativeType
				$feed->setSubproperty($name, $value);
			}
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

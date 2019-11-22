<?php
/**
 * TFeedService and TFeed class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\Services
 */

namespace Prado\Web\Services;

use Prado\Prado;
use Prado\TApplication;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;

/**
 * TFeedService class
 *
 * TFeedService provides to end-users feed content.
 *
 * TFeedService manages a set of feeds. The service parameter, referring
 * to the ID of the feed, specifies which feed content to be provided to end-users.
 *
 * To use TFeedService, configure it in application configuration as follows,
 * <code>
 *  <service id="feed" class="Prado\Web\Services\TFeedService">
 *    <feed id="ch1" class="Path\To\FeedClass1" .../>
 *    <feed id="ch2" class="Path\To\FeedClass2" .../>
 *    <feed id="ch3" class="Path\To\FeedClass3" .../>
 *  </service>
 * </code>
 * where each &lt;feed&gt; element specifies a feed identified by its "id" value (case-sensitive).
 *
 * PHP configuration style:
 * <code>
 * array(
 *   'feed' => array(
 *	   'ch1' => array(
 *       'class' => 'Path\To\FeedClass1',
 *       'properties' => array(
 *          ...
 *        ),
 *   ),
 * )
 * </code>
 *
 * The class attribute indicates which PHP class will provide the actual feed
 * content. Note, the class must implement {@link IFeedContentProvider} interface.
 * Other initial properties for the feed class may also be specified in the
 * corresponding &lt;feed&gt; element.
 *
 * To retrieve the feed content identified by "ch2", use the URL
 * <code>/path/to/index.php?feed=ch2</code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @package Prado\Web\Services
 * @since 3.1
 */
class TFeedService extends \Prado\TService
{
	private $_feeds = [];

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param mixed $config configuration for this module, can be null
	 */
	public function init($config)
	{
		if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
			if (is_array($config)) {
				foreach ($config as $id => $feed) {
					$this->_feeds[$id] = $feed;
				}
			}
		} else {
			foreach ($config->getElementsByTagName('feed') as $feed) {
				if (($id = $feed->getAttributes()->remove('id')) !== null) {
					$this->_feeds[$id] = $feed;
				} else {
					throw new TConfigurationException('feedservice_id_required');
				}
			}
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
		$id = $this->getRequest()->getServiceParameter();
		if (isset($this->_feeds[$id])) {
			$feedConfig = $this->_feeds[$id];
			$properties = [];
			$feed = null;
			if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
				if (isset($feedConfig['class'])) {
					$feed = Prado::createComponent($feedConfig['class']);
					if ($service instanceof IFeedContentProvider) {
						$properties = $feedConfig['properties'] ?? [];
					} else {
						throw new TConfigurationException('jsonservice_response_type_invalid', $id);
					}
				} else {
					throw new TConfigurationException('jsonservice_class_required', $id);
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

<?php

/**
 * TCompressionConfigTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

use Prado\Prado;
use Prado\TApplication;

/**
 * TCompressionConfigTrait class.
 *
 * Gives a component a {@see TCompressionConfig} of its own, so everything that compresses
 * carries the same settings under the same names.  A class using the trait declares
 * {@see ICompressionConfigurable}, so code compressing on its behalf finds the settings.
 * A component built without a configuration, such as
 * {@see \Prado\Web\UI\TPageStatePersister}, takes its settings as sub-properties.  A
 * module reads the nested `<compression>` element of its configuration by calling
 * {@see applyCompressionConfig()} from its `init()`:
 *
 * ```php
 * public function init($config)
 * {
 *     $this->applyCompressionConfig($config);
 *     parent::init($config);
 * }
 * ```
 *
 * An XML configuration then carries the settings as attributes of that element, and a PHP
 * configuration as a `compression` array:
 *
 * ```xml
 * <module id="export" class="MyApp\TExportModule">
 *   <compression Enabled="true" Method="gzip" Level="6" />
 * </module>
 * ```
 *
 * ```php
 * 'export' => [
 *     'class' => 'MyApp\TExportModule',
 *     'compression' => ['Enabled' => 'true', 'Method' => 'gzip', 'Level' => '6'],
 * ],
 * ```
 *
 * Module properties are applied through {@see \Prado\TComponent::setSubProperty()}, so
 * `Compression.Enabled="true"` on the module tag reaches the same object without the
 * nested element.  A module whose default differs from {@see TCompressionConfig} overrides
 * {@see newCompression()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TCompressionConfigTrait
{
	/** @var ?TCompressionConfig the compression settings of this module */
	private ?TCompressionConfig $_compression = null;

	/**
	 * @return TCompressionConfig a new compression configuration carrying this module's defaults.
	 */
	protected function newCompression(): TCompressionConfig
	{
		return new TCompressionConfig();
	}

	/**
	 * @return TCompressionConfig the compression settings of this module, created on first read.
	 */
	public function getCompression(): TCompressionConfig
	{
		if ($this->_compression === null) {
			$this->_compression = $this->newCompression();
		}
		return $this->_compression;
	}

	/**
	 * @param TCompressionConfig $value the compression settings of this module.
	 */
	public function setCompression(TCompressionConfig $value): void
	{
		$this->_compression = $value;
	}

	/**
	 * Applies the `<compression>` element of an XML configuration, or the `compression`
	 * array of a PHP configuration, to {@see getCompression() Compression}.  A module
	 * calls this from its `init()`; a configuration without the element leaves the
	 * settings as they are.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration.
	 */
	protected function applyCompressionConfig($config): void
	{
		if ($config === null) {
			return;
		}
		if (Prado::getApplication()?->getConfigurationType() === TApplication::CONFIG_TYPE_PHP) {
			$properties = (is_array($config) && is_array($config['compression'] ?? null)) ? $config['compression'] : null;
		} else {
			$properties = is_object($config) ? $config->getElementByTagName('compression')?->getAttributes() : null;
		}
		if ($properties === null) {
			return;
		}
		$this->getCompression()->setSubProperties($properties);
	}
}

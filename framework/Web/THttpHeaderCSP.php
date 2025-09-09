<?php

/**
 * THttpHeaderCSP class
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Prado;

/**
 * THttpHeaderCSP class.
 *
 * THttpHeaderCSP adds a Content-security-policy header to all responses.
 * If you want to use this header, load your manager class
 * {@see \Prado\Web\THttpHeadersManager THttpHeadersManager},
 * add this class as an header and define the needed policies, eg.:
 *
 * ```xml
 * <module id="headers" class="THttpHeadersManager">
 *   <header Name="Strict-Transport-Security" Value="max-age=31536000" />
 *   <header Name="X-Content-Type-Options" Value="nosniff" />
 *   <header Name="X-Frame-Options" Value="DENY" />
 *   <header class="THttpHeaderCSP">
 *     <policy Name="default-src">'self' 'unsafe-inline' www.gstatic.com NONCE</policy>
 *     <policy Name="frame-src">'self' www.google.com</policy>
 *   </header>
 * </module>
 * <module id="response" class="THttpResponse" HeadersManager="headers" />
 * ```
 *
 * The special value {@see \Prado\Web\THttpHeaderCSP::NONCE NONCE} will get
 * automatically replaced with a valid Content-security-policy nonce.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 4.3.2
 */
class THttpHeaderCSP extends \Prado\Web\THttpHeader
{
	/**
	 * @var string[] list of key:value policies.
	 */
	protected $_policies = [];

	public const NONCE = 'NONCE';

	/**
	 * Initializes the CSP header.
	 * @param \Prado\Xml\TXmlElement $config configuration for this module.
	 */
	public function init($config)
	{
		parent::init($config);
		$this->loadPolicies($config);
	}

	/**
	 * Load and configure each header.
	 * @param mixed $config configuration node
	 */
	protected function loadPolicies($config)
	{
		if (is_array($config)) {
			if (isset($config['policies']) && is_array($config['policies'])) {
				foreach ($config['policies'] as $policy) {
					$name = $policy['name'];
					$this->_policies[$name] = $policy['value'];
				}
			}
		} else {
			foreach ($config->getElementsByTagName('policy') as $header) {
				$properties = $header->getAttributes();
				$name = $properties->remove('Name');
				$this->_policies[$name] = $header->getValue();
			}
		}
	}

	/**
	 * @return string the textual name of the header.
	 */
	public function getName()
	{
		return 'Content-Security-Policy';
	}

	/**
	 * @return string the textual value of the header.
	 */
	public function getValue()
	{
		$nonce = '\'nonce-' . Prado::getApplication()->getSecurityManager()->getCSPNonce() . '\'';
		$ret = '';
		foreach ($this->_policies as $name => $value) {
			$ret .= $name . ' ' . str_replace(self::NONCE, $nonce, $value) . '; ';
		}
		return $ret;
	}
}

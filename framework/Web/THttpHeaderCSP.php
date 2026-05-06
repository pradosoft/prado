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
use Prado\Web\Javascripts\TJavaScript;

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
 *      <policy Name="default-src">'self' 'unsafe-inline' www.gstatic.com NONCE</policy>
 *      <policy Name="frame-src">'self' www.google.com</policy>
 *   </header>
 * </module>
 * <module id="response" class="THttpResponse" HeadersManager="headers" />
 * ```
 *
 * The special value {@see \Prado\Web\THttpHeaderCSP::NONCE NONCE} will get
 * automatically replaced with a valid Content-security-policy nonce.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 4.3.3
 * @see HTTP CSP Guide: https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CSP
 * @see CSP Browser policies: https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy
 */
class THttpHeaderCSP extends \Prado\Web\THttpHeader
{
	/**
	 * @var string[] list of key:value policies.
	 */
	protected $_policies = [];

	public const NONCE = 'NONCE';

	/**
	 * Loads policies and, if any reference {@see NONCE}, fetches the per-request
	 * nonce from `TSecurityManager` and stores it in {@see TJavaScript::setScriptNonce()}
	 * so every inline script tag rendered during this request carries the same nonce.
	 * @param array|\Prado\Xml\TXmlElement $config configuration for this module.
	 */
	public function init($config)
	{
		parent::init($config);
		$this->loadPolicies($config);

		foreach ($this->_policies as $value) {
			if (str_contains($value, self::NONCE)) {
				$nonce = Prado::getApplication()->getSecurityManager()->getCSPNonce();
				TJavaScript::setScriptNonce($nonce);
				break;
			}
		}
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
		$nonce = TJavaScript::getScriptNonce();
		$nonceDirective = $nonce !== null ? '\'nonce-' . $nonce . '\'' : null;
		$ret = '';
		foreach ($this->_policies as $name => $value) {
			if ($nonceDirective !== null) {
				$value = str_replace(self::NONCE, $nonceDirective, $value);
			}
			$ret .= $name . ' ' . $value . '; ';
		}
		return $ret;
	}
}

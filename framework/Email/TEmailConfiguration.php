<?php

/**
 * TEmailConfiguration class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

use Prado\Exceptions\TConfigurationException;
use Prado\Xml\TXmlElement;

/**
 * TEmailConfiguration class.
 *
 * TEmailConfiguration holds the initial property values that {@see TEmailModule}
 * applies to the {@see UI\TEmailBody} of an email it creates.  The values come from
 * the `<emails>` element of the module:
 *
 * <code>
 * <emails MasterClass="Application.Layouts.EmailLayout" Email.From="shop@example.com">
 *   <email id="Help/*" Email.From="help@example.com" />
 *   <email id="Order.Shipped" Email.List="orders" />
 * </emails>
 * </code>
 *
 * The attributes of `<emails>` apply to every email.  An `<email>` applies to the
 * email paths its id matches, where "*" stands for any text and "/" and "." both
 * separate path segments.  A later rule overrides an earlier one.
 *
 * In a PHP configuration the element is the array
 * `['properties' => [...], 'rules' => ['Help/*' => [...], ...]]`.
 *
 * A property name is a sub-property path of the body: "MasterClass" is the body's
 * own property, and "Email.From" is the From of its {@see TEmail}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailConfiguration extends \Prado\TComponent
{
	/** @var array<string, string> the property values of every email. */
	private array $_properties = [];

	/** @var array<array{0: string, 1: array<string, string>}> the rules, each an id pattern and its property values. */
	private array $_rules = [];

	/**
	 * Loads the `<emails>` configuration.
	 * @param null|array|TXmlElement $config the `<emails>` element, or its PHP array.
	 * @throws TConfigurationException when an `<email>` has no id.
	 */
	public function load($config): void
	{
		if ($config instanceof TXmlElement) {
			$this->_properties = array_merge($this->_properties, $config->getAttributes()->toArray());
			foreach ($config->getElementsByTagName('email') as $element) {
				$properties = $element->getAttributes()->toArray();
				$this->addRule((string) ($properties['id'] ?? ''), array_diff_key($properties, ['id' => true]));
			}
		} elseif (is_array($config)) {
			$this->_properties = array_merge($this->_properties, $config['properties'] ?? []);
			foreach ($config['rules'] ?? [] as $id => $properties) {
				$this->addRule((string) $id, (array) $properties);
			}
		}
	}

	/**
	 * Adds a rule after the existing ones.
	 * @param string $id the id pattern of the email paths.
	 * @param array<string, string> $properties the property values.
	 * @throws TConfigurationException when the id is empty.
	 */
	public function addRule(string $id, array $properties): void
	{
		if ($id === '') {
			throw new TConfigurationException('emailconfiguration_id_required');
		}
		$this->_rules[] = [$id, $properties];
	}

	/**
	 * Returns the property values of an email path: the common values, then those of
	 * each matching rule in order.
	 * @param string $emailPath the email path, e.g. "Help.Welcome".
	 * @return array<string, string> the property values by sub-property path.
	 */
	public function getEmailProperties(string $emailPath): array
	{
		$properties = $this->_properties;
		foreach ($this->_rules as [$id, $values]) {
			if (static::matches($id, $emailPath)) {
				$properties = array_merge($properties, $values);
			}
		}
		return $properties;
	}

	/**
	 * @param string $id the id pattern, with "*" for any text.
	 * @param string $emailPath the email path.
	 * @return bool whether the pattern matches the path; "/" and "." are the same separator, and case is ignored.
	 */
	public static function matches(string $id, string $emailPath): bool
	{
		$pattern = str_replace('\*', '.*', preg_quote(strtr($id, '/', '.'), '/'));
		return preg_match('/^' . $pattern . '$/i', strtr($emailPath, '/', '.')) === 1;
	}
}

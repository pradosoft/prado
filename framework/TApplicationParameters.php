<?php

/**
 * TApplicationParameters class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidOperationException;

/**
 * TApplicationParameters class
 *
 * TApplicationParameters is the map of application parameters returned by
 * {@see TApplication::getParameters()}. A parameter marked final keeps its value
 * for the rest of the request. Every later add, set, or removal of a final key
 * throws a {@see TInvalidOperationException}.
 *
 * A configuration finalizes a parameter with the `final` attribute:
 * ```xml
 * <parameter id="DeveloperMode" value="0" final="true" />
 * ```
 * The PHP configuration equivalent is
 * `'DeveloperMode' => ['value' => '0', 'final' => true]`.
 * Code finalizes a parameter with {@see finalize()}.
 *
 * A final parameter in `application.xml` locks the value against folder
 * `config.xml` files, service configurations, included configurations, modules,
 * and parameter loaders such as {@see \Prado\Util\TParameterModule}.
 *
 * | Operation on a final key | Result |
 * |---|---|
 * | {@see itemAt()}, `$params[$key]` | returns the value; null for an absent key, without `dyNoItem` |
 * | {@see add()}, `$params[$key] = $v`, {@see mergeWith()} | throws |
 * | {@see remove()}, `unset($params[$key])`, {@see clear()}, {@see copyFrom()} | throws |
 * | {@see finalize()} | no change |
 *
 * Behaviors on the map follow the final keys:
 * - `dyAddItem` and `dyRemoveItem` are not raised for a rejected change, so
 *   {@see \Prado\Util\Behaviors\TMapRouteBehavior} handlers receive no message.
 * - `dyNoItem` is not raised for a final absent key, so
 *   {@see \Prado\Util\Behaviors\TMapLazyLoadBehavior} loaders are not consulted.
 * - A property set from a final parameter by
 *   {@see \Prado\Util\Behaviors\TParameterizeBehavior} holds a copy; code may still set
 *   that property directly.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TApplicationParameters extends TMap
{
	/**
	 * @var array<int|string, true> the final parameter keys
	 */
	private array $_final = [];

	/**
	 * Finalizes a parameter so its key cannot be added, set, or removed again.
	 * A key without a value may be finalized; the key then stays absent.
	 * @param mixed $key the parameter key; null finalizes the empty-string key.
	 */
	public function finalize($key): void
	{
		$this->_final[$key ?? ''] = true;
	}

	/**
	 * @param mixed $key the parameter key; null checks the empty-string key.
	 * @return bool whether the parameter key is final.
	 */
	public function getIsFinal($key): bool
	{
		return isset($this->_final[$key ?? '']);
	}

	/**
	 * @return array<int|string> the final parameter keys, in finalization order.
	 */
	public function getFinalKeys(): array
	{
		return array_keys($this->_final);
	}

	/**
	 * Returns a parameter.
	 * A final absent key returns null without raising `dyNoItem`, so lazy loaders
	 * cannot supply it.
	 * @param mixed $key the parameter key; null looks up the empty-string key.
	 * @return mixed the parameter value, null if no value is found.
	 */
	public function itemAt($key)
	{
		if ($this->getIsFinal($key) && !$this->contains($key)) {
			return null;
		}
		return parent::itemAt($key);
	}

	/**
	 * Adds a parameter.
	 * @param mixed $key the parameter key; null appends with the next integer key.
	 * @param mixed $value the parameter value.
	 * @throws TInvalidOperationException if the key is final or the map is read-only.
	 * @return mixed the key of the parameter.
	 */
	public function add($key, $value): mixed
	{
		if ($key !== null) {
			$this->ensureNotFinal($key);
		}
		return parent::add($key, $value);
	}

	/**
	 * Removes a parameter.
	 * @param mixed $key the parameter key; null removes the empty-string key.
	 * @throws TInvalidOperationException if the key is final or the map is read-only.
	 * @return mixed the removed value, null if no such key exists.
	 */
	public function remove($key)
	{
		$this->ensureNotFinal($key ?? '');
		return parent::remove($key);
	}

	/**
	 * Guards a key against change.
	 * @param int|string $key the parameter key.
	 * @throws TInvalidOperationException if the key is final.
	 */
	protected function ensureNotFinal($key): void
	{
		if ($this->getIsFinal($key)) {
			throw new TInvalidOperationException('appparameters_final', $key);
		}
	}

	/**
	 * Omits the empty final-key list from serialization.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if (empty($this->_final)) {
			$exprops[] = "\0" . __CLASS__ . "\0_final";
		}
	}
}

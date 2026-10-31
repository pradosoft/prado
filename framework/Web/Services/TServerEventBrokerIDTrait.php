<?php

/**
 * TServerEventBrokerIDTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TServerEventBrokerIDTrait class.
 *
 * TServerEventBrokerIDTrait gives a class a {@see setBrokerID BrokerID} property and
 * resolves the {@see IServerEventBroker} module it names. An empty BrokerID resolves the
 * first application module that implements IServerEventBroker.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TServerEventBrokerIDTrait
{
	/** @var string The ID of the broker module; empty for the first broker module. */
	private string $_brokerID = '';

	/**
	 * @return string The ID of the broker module. Defaults to '', the first IServerEventBroker module.
	 */
	public function getBrokerID(): string
	{
		return $this->_brokerID;
	}

	/**
	 * @param string $value The ID of the broker module. Empty uses the first IServerEventBroker module.
	 */
	public function setBrokerID($value): void
	{
		$this->_brokerID = TPropertyValue::ensureString($value);
	}

	/**
	 * Resolves the broker named by {@see getBrokerID BrokerID}.
	 * @throws TConfigurationException When BrokerID names no IServerEventBroker module, or
	 *   when BrokerID is empty and the application has no broker module.
	 * @return IServerEventBroker The broker.
	 */
	protected function resolveBroker(): IServerEventBroker
	{
		$application = Prado::getApplication();
		if (($id = $this->getBrokerID()) !== '') {
			$broker = $application->getModule($id);
			if (!($broker instanceof IServerEventBroker)) {
				throw new TConfigurationException('servereventbroker_id_invalid', static::class, $id);
			}
			return $broker;
		}
		foreach ($application->getModulesByType(IServerEventBroker::class) as $id => $broker) {
			$broker ??= $application->getModule($id);
			if ($broker instanceof IServerEventBroker) {
				return $broker;
			}
		}
		throw new TConfigurationException('servereventbroker_required', static::class);
	}
}

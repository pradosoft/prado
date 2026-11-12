<?php

/**
 * TCronMonitorResponse class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Web\Services\TJsonResponse;

/**
 * TCronMonitorResponse class.
 *
 * TCronMonitorResponse is the monitoring endpoint of a {@see \Prado\Util\Cron\TCronModule}: a provider of
 * {@see \Prado\Web\Services\TJsonService} that answers with the module's
 * {@see \Prado\Util\Cron\TCronModule::getMonitorReport() monitor report} as JSON, or with its
 * {@see \Prado\Util\Cron\TCronModule::getMonitorMetrics() Prometheus metrics} as text.
 * ```xml
 * <service id="json" class="Prado\Web\Services\TJsonService">
 *   <json id="cron" class="Prado\Util\Cron\TCronMonitorResponse" />
 *   <json id="cronmetrics" class="Prado\Util\Cron\TCronMonitorResponse" Format="prometheus" />
 * </service>
 * ```
 * `index.php?json=cron` is the report and `index.php?json=cronmetrics` the metrics.  The report has the
 * tasks the requesting user may read ({@see \Prado\Util\Cron\TCronModule::PERM_CRON_READ_TASKS}); restrict
 * the service to monitoring users through the application's authorization rules.
 * {@see getCronModuleID CronModuleID} names the module, default the first {@see \Prado\Util\Cron\TCronModule}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronMonitorResponse extends TJsonResponse
{
	/** The JSON report format. */
	public const FORMAT_JSON = 'json';

	/** The Prometheus text exposition format. */
	public const FORMAT_PROMETHEUS = 'prometheus';

	/** The content type of the Prometheus text exposition format. */
	public const PROMETHEUS_CONTENT_TYPE = 'text/plain; version=0.0.4';

	/** @var ?string the ID of the cron module, or null for the first */
	private ?string $_cronModuleID = null;

	/** @var string the format of the answer */
	private string $_format = self::FORMAT_JSON;

	/**
	 * Returns the monitor report for JSON, or writes the Prometheus metrics and returns null.
	 * @throws TConfigurationException when the cron module is not found
	 * @return ?object the report, or null when the metrics were written
	 */
	public function getJsonContent()
	{
		$module = $this->getCronModule();
		if ($this->_format === self::FORMAT_PROMETHEUS) {
			$response = $this->getResponse();
			$response->setContentType(self::PROMETHEUS_CONTENT_TYPE);
			$response->setCharset('UTF-8');
			$response->write($module->getMonitorMetrics());
			return null;
		}
		return (object) $module->getMonitorReport();
	}

	/**
	 * @throws TConfigurationException when the cron module is not found
	 * @return TCronModule the cron module of {@see getCronModuleID CronModuleID}, or the first of the application
	 */
	public function getCronModule(): TCronModule
	{
		$app = $this->getApplication();
		if ($this->_cronModuleID !== null) {
			$module = $app->getModule($this->_cronModuleID);
		} else {
			$module = null;
			foreach (array_keys($app->getModulesByType(TCronModule::class, false)) as $id) {
				if (($module = $app->getModule($id)) instanceof TCronModule) {
					break;
				}
			}
		}
		if (!($module instanceof TCronModule)) {
			throw new TConfigurationException('cronmonitor_module_invalid', (string) $this->_cronModuleID);
		}
		return $module;
	}

	/**
	 * @return ?string the ID of the cron module, or null for the first TCronModule
	 */
	public function getCronModuleID(): ?string
	{
		return $this->_cronModuleID;
	}

	/**
	 * @param ?string $value the ID of the cron module, or null for the first TCronModule
	 */
	public function setCronModuleID($value): void
	{
		$this->_cronModuleID = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the format of the answer: {@see FORMAT_JSON} (default) or {@see FORMAT_PROMETHEUS}
	 */
	public function getFormat(): string
	{
		return $this->_format;
	}

	/**
	 * @param string $value the format of the answer: 'json' or 'prometheus'
	 * @throws \Prado\Exceptions\TInvalidDataValueException when the format is neither
	 */
	public function setFormat($value): void
	{
		$this->_format = TPropertyValue::ensureEnum(strtolower(TPropertyValue::ensureString($value)), [self::FORMAT_JSON, self::FORMAT_PROMETHEUS]);
	}
}

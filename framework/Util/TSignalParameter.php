<?php

/**
 * TSignalParameter classes
 *
 * @author Brad Anderson <beisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * TSignalParameter class.
 *
 * This is the parameter for signal events.  The {@see \Prado\TEventParameter::getParameter()}
 * property is the array of $signalInfo from the Signal handler {@see \Prado\Util\TSignalsDispatcher::__invoke()};
 * when available on the PHP system.
 *
 * There is also the Signal {@see self::getSignal()}, whether to exit {@see self::getIsExit()},
 * the exit code when exiting {@see self::getExitCode()}, and the alarm time {@see self::getAlarmTime()}
 * if the signal is SIGALRM.
 *
 * When there is Signal Info in the Parameter property, there are inspection methods
 * {@see self::getParameterErrorNumber()} for accessing the Signal Error Number,
 * {@see self::getParameterCode()} for accessing the Signal Code, {@see self::getParameterStatus()}
 * for accessing the Signal Status, {@see self::getParameterPID()} for accessing
 * the Signal PID, and {@see self::getParameterUID()} for accessing the Signal PID UID.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
class TSignalParameter extends \Prado\TEventParameter
{
	/** @var int Signal being sent. */
	private int $_signal;

	/** @var bool Should the Signal exit. */
	private bool $_isExit;

	/** @var int The exit code when exiting. */
	private int $_exitCode;

	/** @var ?int The time of the alarm signal. */
	private ?int $_alarmTime;

	/**
	 * Constructor.
	 * @param mixed $parameter parameter of the event
	 * @param bool $isExiting
	 * @param int $exitCode
	 * @param int $signal
	 */
	public function __construct(int $signal = 0, bool $isExiting = false, int $exitCode = 0, mixed $parameter = null)
	{
		$this->_signal = $signal;
		$this->_isExit = $isExiting;
		$this->_exitCode = $exitCode;
		parent::__construct($parameter);
	}

	/**
	 * @return int The signal being raised.
	 */
	public function getSignal(): int
	{
		return $this->_signal;
	}

	/**
	 * @param int $value The signal being raised.
	 * @return static The current object.
	 */
	public function setSignal(int $value): static
	{
		$this->_signal = $value;

		return $this;
	}

	/**
	 * @return bool Should the signal exit.
	 */
	public function getIsExiting(): bool
	{
		return $this->_isExit;
	}

	/**
	 * @param bool $value Should the signal exit.
	 * @return static The current object.
	 */
	public function setIsExiting(bool $value): static
	{
		$this->_isExit = $value;

		return $this;
	}

	/**
	 * @return int The exit code when exiting.
	 */
	public function getExitCode(): int
	{
		return $this->_exitCode;
	}

	/**
	 * @param int $value The exit code when exiting.
	 * @return static The current object.
	 */
	public function setExitCode(int $value): static
	{
		$this->_exitCode = $value;

		return $this;
	}

	/**
	 * @return ?int The alarm time.
	 */
	public function getAlarmTime(): ?int
	{
		return $this->_alarmTime;
	}

	/**
	 * @param ?int $value The alarm time.
	 * @return static The current object.
	 */
	public function setAlarmTime(?int $value): static
	{
		$this->_alarmTime = $value;

		return $this;
	}

	/**
	 * @return ?int The Parameter Error Number.
	 */
	public function getParameterErrorNumber(): ?int
	{
		$param = $this->getParameter();

		return $param['errno'] ?? null;
	}

	/**
	 * @return ?int The Parameter Code.
	 */
	public function getParameterCode(): ?int
	{
		$param = $this->getParameter();

		return $param['code'] ?? null;
	}

	/**
	 * @return ?int The Parameter Status.
	 */
	public function getParameterStatus(): ?int
	{
		$param = $this->getParameter();

		return $param['status'] ?? null;
	}

	/**
	 * @return ?int The Parameter PID.
	 */
	public function getParameterPID(): ?int
	{
		$param = $this->getParameter();

		return $param['pid'] ?? null;
	}

	/**
	 * @return ?int The Parameter UID.
	 */
	public function getParameterUID(): ?int
	{
		$param = $this->getParameter();

		return $param['uid'] ?? null;
	}
}

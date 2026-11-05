<?php

/**
 * TFormGuard class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Caching\TCacheModuleIDTrait;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\Clock\TApplicationClockAwareTrait;
use Prado\Web\THttpUtility;
use Prado\Web\UI\IPostBackDataHandler;

/**
 * TFormGuard class.
 *
 * TFormGuard is a validator that rejects automated form submissions without asking
 * the user to do anything. It validates the form it is placed in and ignores
 * {@see setControlToValidate ControlToValidate}. Checks, in order of the reported
 * {@see getFailureReason FailureReason}:
 *
 * - Honeypot: a text field hidden from people and assistive technology must stay empty.
 *   {@see setHoneypotName HoneypotName} sets the field name bots see.
 * - Stamp: the signed render time must come back unaltered. The stamp is signed with the
 *   {@see \Prado\Security\TSecurityManager} validation key and bound to this guard.
 * - TooFast: at least {@see setMinFillTime MinFillTime} seconds pass between the first
 *   render and the submission. 0 disables the check.
 * - Expired: at most {@see setMaxFillTime MaxFillTime} seconds pass. 0 disables the check.
 * - RateLimited: at most {@see setRateLimit RateLimit} submissions per client in each
 *   {@see setRateWindow RateWindow} seconds. 0 disables the check.
 *
 * A failed postback renders the stamp it received, so the fill time counts from the first render.
 *
 * The rate limit counts submissions in the cache named by {@see setCacheModuleID CacheModuleID},
 * the application's primary cache by default. A RateLimit without a cache throws a
 * {@see \Prado\Exceptions\TConfigurationException}. The count is exact on caches with an atomic
 * add and approximate under concurrent requests otherwise. The client is identified by
 * {@see getRateLimitKey()}, the remote address; a subclass behind a trusted proxy overrides it.
 *
 * Client-side validation is off; the checks run on the server.
 *
 * ```php
 * <com:TFormGuard ID="Guard" MinFillTime="3" RateLimit="5" RateWindow="60"
 *     ErrorMessage="Your submission could not be accepted. Please try again." />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFormGuard extends TBaseValidator implements IPostBackDataHandler
{
	use TApplicationClockAwareTrait;
	use TCacheModuleIDTrait;

	/** Cache key prefix of submission counters. */
	public const CACHE_KEY_PREFIX = 'prado:formguard:';
	/** Post field suffix of the signed stamp. */
	public const STAMP_SUFFIX = '$ts';

	/** @var ?string the honeypot value posted in this request. */
	private ?string $_honeypot = null;
	/** @var ?string the stamp posted in this request. */
	private ?string $_stamp = null;
	/** @var ?int the render time recovered from a valid posted stamp. */
	private ?int $_stampTime = null;
	/** @var ?bool the result of this request's evaluation. */
	private ?bool $_result = null;
	/** @var ?string the reason this request's evaluation failed. */
	private ?string $_failureReason = null;

	/**
	 * @return string the name of the honeypot field. Defaults to 'website'.
	 */
	public function getHoneypotName()
	{
		return $this->getViewState('HoneypotName', 'website');
	}

	/**
	 * @param string $value the name of the honeypot field. Bots fill fields with plausible names.
	 * @throws TInvalidDataValueException if the name is empty or has characters other than letters, digits, '_', and '-'.
	 */
	public function setHoneypotName($value)
	{
		$value = TPropertyValue::ensureString($value);
		if (!preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
			throw new TInvalidDataValueException('formguard_honeypotname_invalid', $value);
		}
		$this->setViewState('HoneypotName', $value, 'website');
	}

	/**
	 * @return int the minimum seconds between render and submission. Defaults to 3; 0 disables the check.
	 */
	public function getMinFillTime()
	{
		return $this->getViewState('MinFillTime', 3);
	}

	/**
	 * @param int $value the minimum seconds between render and submission; 0 disables the check.
	 */
	public function setMinFillTime($value)
	{
		$this->setViewState('MinFillTime', max(0, TPropertyValue::ensureInteger($value)), 3);
	}

	/**
	 * @return int the maximum seconds between render and submission. Defaults to 0, no maximum.
	 */
	public function getMaxFillTime()
	{
		return $this->getViewState('MaxFillTime', 0);
	}

	/**
	 * @param int $value the maximum seconds between render and submission; 0 disables the check.
	 */
	public function setMaxFillTime($value)
	{
		$this->setViewState('MaxFillTime', max(0, TPropertyValue::ensureInteger($value)), 0);
	}

	/**
	 * @return int the submissions allowed per client in each rate window. Defaults to 0, no limit.
	 */
	public function getRateLimit()
	{
		return $this->getViewState('RateLimit', 0);
	}

	/**
	 * @param int $value the submissions allowed per client in each rate window; 0 disables the limit.
	 */
	public function setRateLimit($value)
	{
		$this->setViewState('RateLimit', max(0, TPropertyValue::ensureInteger($value)), 0);
	}

	/**
	 * @return int the length of the rate window in seconds. Defaults to 60.
	 */
	public function getRateWindow()
	{
		return $this->getViewState('RateWindow', 60);
	}

	/**
	 * @param int $value the length of the rate window in seconds; values below 1 become 1.
	 */
	public function setRateWindow($value)
	{
		$this->setViewState('RateWindow', max(1, TPropertyValue::ensureInteger($value)), 60);
	}

	/**
	 * @return ?string the {@see TFormGuardFailure} of the last failed evaluation, or null.
	 */
	public function getFailureReason(): ?string
	{
		return $this->_failureReason;
	}

	/**
	 * @return bool false; the guard validates on the server only.
	 */
	public function getEnableClientScript()
	{
		return false;
	}

	/**
	 * @return null|\Prado\Web\UI\TControl null; the guard validates its own fields and has no target control.
	 */
	public function getValidationTarget()
	{
		return null;
	}

	/**
	 * @return string the client class name; unused because client validation is off.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TBaseValidator';
	}

	/**
	 * Reads the honeypot and stamp fields.
	 * @param string $key the unique ID of this guard.
	 * @param array $values the post data.
	 * @return bool false; the guard raises no change event.
	 */
	public function loadPostData($key, $values)
	{
		$this->_honeypot = (string) ($values[$key . '$' . $this->getHoneypotName()] ?? '');
		$this->_stamp = (string) ($values[$key . self::STAMP_SUFFIX] ?? '');
		return false;
	}

	/**
	 * The guard raises no change event.
	 */
	public function raisePostDataChangedEvent()
	{
	}

	/**
	 * @return bool false; the guard's fields have no change event.
	 */
	public function getDataChanged()
	{
		return false;
	}

	/**
	 * Registers the guard to read its fields on the next postback.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->getPage()->registerRequiresPostData($this);
	}

	/**
	 * Renders the error message, the honeypot, and the signed stamp.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	public function render($writer)
	{
		parent::render($writer);
		$this->renderGuardFields($writer);
	}

	/**
	 * Renders the honeypot inside a container hidden from view and from assistive technology,
	 * then the signed stamp.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	protected function renderGuardFields($writer)
	{
		$id = $this->getClientID() . '_hp';
		$writer->addAttribute('aria-hidden', 'true');
		$writer->addStyleAttribute('position', 'absolute');
		$writer->addStyleAttribute('left', '-10000px');
		$writer->addStyleAttribute('width', '1px');
		$writer->addStyleAttribute('height', '1px');
		$writer->addStyleAttribute('overflow', 'hidden');
		$writer->renderBeginTag('span');
		$writer->addAttribute('for', $id);
		$writer->renderBeginTag('label');
		$writer->write(THttpUtility::htmlEncode(Prado::localize('Leave this field empty')));
		$writer->renderEndTag();
		$writer->addAttribute('type', 'text');
		$writer->addAttribute('id', $id);
		$writer->addAttribute('name', $this->getUniqueID() . '$' . $this->getHoneypotName());
		$writer->addAttribute('value', '');
		$writer->addAttribute('tabindex', '-1');
		$writer->addAttribute('autocomplete', 'off');
		$writer->renderBeginTag('input');
		$writer->renderEndTag();
		$writer->renderEndTag();

		$writer->addAttribute('type', 'hidden');
		$writer->addAttribute('name', $this->getUniqueID() . self::STAMP_SUFFIX);
		$writer->addAttribute('value', $this->createStamp());
		$writer->renderBeginTag('input');
		$writer->renderEndTag();
	}

	/**
	 * @return string the signed stamp: the first render time and this guard's unique ID.
	 */
	protected function createStamp(): string
	{
		$time = $this->readStampTime() ?? $this->getClock()->time();
		return $this->getApplication()->getSecurityManager()->hashData($time . ':' . $this->getUniqueID());
	}

	/**
	 * @return ?int the render time in the posted stamp, or null when the stamp is missing, altered, or from another guard.
	 */
	protected function readStampTime(): ?int
	{
		if ($this->_stampTime === null && $this->_stamp !== null && $this->_stamp !== '') {
			$data = $this->getApplication()->getSecurityManager()->validateData($this->_stamp);
			if (is_string($data) && preg_match('/^(\d+):(.+)$/s', $data, $matches) && $matches[2] === $this->getUniqueID()) {
				$this->_stampTime = (int) $matches[1];
			}
		}
		return $this->_stampTime;
	}

	/**
	 * Evaluates the checks once per request; later calls return the first result.
	 * @return bool whether the submission passes every check.
	 */
	protected function evaluateIsValid()
	{
		if ($this->_result === null) {
			$this->_failureReason = $this->evaluateFailure();
			$this->_result = $this->_failureReason === null;
		}
		return $this->_result;
	}

	/**
	 * Counts the submission, then runs the checks in order.
	 * @return ?string the first {@see TFormGuardFailure}, or null when every check passes.
	 */
	protected function evaluateFailure(): ?string
	{
		$withinRate = $this->countSubmission();
		if ($this->_honeypot !== null && $this->_honeypot !== '') {
			return TFormGuardFailure::Honeypot;
		}
		if (($time = $this->readStampTime()) === null) {
			return TFormGuardFailure::Stamp;
		}
		$elapsed = $this->getClock()->time() - $time;
		if ($elapsed < $this->getMinFillTime()) {
			return TFormGuardFailure::TooFast;
		}
		if (($max = $this->getMaxFillTime()) > 0 && $elapsed > $max) {
			return TFormGuardFailure::Expired;
		}
		if (!$withinRate) {
			return TFormGuardFailure::RateLimited;
		}
		return null;
	}

	/**
	 * Adds this submission to the client's count in the current rate window.
	 * @throws \Prado\Exceptions\TConfigurationException when RateLimit is set and no cache is available.
	 * @return bool whether the count is within {@see getRateLimit RateLimit}; true when the limit is off.
	 */
	protected function countSubmission(): bool
	{
		if (($limit = $this->getRateLimit()) < 1) {
			return true;
		}
		$cache = $this->resolveCacheModule(true);
		$window = $this->getRateWindow();
		$now = $this->getClock()->time();
		$ttl = $window - $now % $window;
		$key = self::CACHE_KEY_PREFIX . hash('sha256', $this->getPage()->getPagePath() . '|' . $this->getUniqueID() . '|' . $this->getRateLimitKey()) . ':' . intdiv($now, $window);
		if ($cache->add($key, 1, $ttl)) {
			$count = 1;
		} else {
			$count = (int) $cache->get($key) + 1;
			$cache->set($key, $count, $ttl);
		}
		return $count <= $limit;
	}

	/**
	 * @return string the client identity the rate limit counts by: the remote address.
	 */
	protected function getRateLimitKey(): string
	{
		return (string) $this->getRequest()->getUserHostAddress();
	}
}

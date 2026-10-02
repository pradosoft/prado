<?php

/**
 * TProofOfWork class file
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
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\THttpUtility;
use Prado\Web\UI\IPostBackDataHandler;
use Prado\Web\UI\IValidatable;

/**
 * TProofOfWork class.
 *
 * TProofOfWork makes the browser spend CPU time before a form submits. It asks nothing of
 * the user, so it has no visual or cognitive challenge. A bot pays the same cost for every
 * submission, which makes bulk submission expensive whatever model reads the page.
 *
 * Each render issues a challenge: a random salt carrying an expiry time, and the SHA-256 of
 * the salt followed by a secret number in [0, {@see setComplexity Complexity}]. The challenge
 * is signed with HMAC-SHA256 under the {@see \Prado\Security\TSecurityManager} validation key
 * and bound to this control. The browser searches for the number in a Web Worker and posts it
 * with the challenge. The search takes Complexity / 2 hashes on average.
 *
 * {@see validate()} accepts a solution once: it checks the signature, the expiry, and the hash,
 * then claims the challenge in the cache named by {@see setCacheModuleID CacheModuleID}, the
 * application's primary cache by default. TProofOfWork requires a cache and throws a
 * {@see \Prado\Exceptions\TConfigurationException} at render without one.
 *
 * {@see setStartMode StartMode} sets when solving starts. A postback, or a callback that causes
 * validation, waits for the solution. Each callback response sends a new challenge; when the
 * callback did not validate this control, the client keeps its unused solution instead.
 * A browser without JavaScript cannot pass and sees {@see getNoScriptText NoScriptText}.
 * A Content-Security-Policy needs 'self' in worker-src, or script-src when worker-src is absent;
 * when the worker is blocked, the page solves on the main thread.
 *
 * The control renders a role="status" live region whose text reports progress.
 *
 * ```php
 * <com:TProofOfWork ID="Work" />
 * <com:TProofOfWorkValidator ControlToValidate="Work"
 *     ErrorMessage="Verification did not finish. Please submit again." />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TProofOfWork extends TWebControl implements IPostBackDataHandler, IValidatable
{
	use TApplicationClockAwareTrait;
	use TCacheModuleIDTrait;

	/** Cache key prefix of claimed challenges. */
	public const CACHE_KEY_PREFIX = 'prado:proofofwork:';
	/** The hash algorithm of challenges, named as the client sees it. */
	public const ALGORITHM = 'SHA-256';
	public const MIN_COMPLEXITY = 1000;
	public const MAX_COMPLEXITY = 10000000;
	/** The JavaScript package of the client class. */
	public const SCRIPT_PACKAGE = 'proofofwork';
	/** The path of the solver script inside the published Prado scripts. */
	public const SOLVER_SCRIPT = '/controls/proofofwork-solver.js';

	/** @var string the solution posted in this request. */
	private string $_solution = '';
	/** @var bool whether the last validation succeeded. */
	private bool $_isValid = true;
	/** @var ?bool the result of this request's verification. */
	private ?bool $_verified = null;

	/**
	 * @return string the tag name of the control, 'div'.
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * @return int the largest secret number; the search takes half as many hashes on average. Defaults to 500000.
	 */
	public function getComplexity()
	{
		return $this->getViewState('Complexity', 500000);
	}

	/**
	 * @param int $value the largest secret number, between 1000 and 10000000.
	 * @throws TInvalidDataValueException if the value is out of range.
	 */
	public function setComplexity($value)
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < self::MIN_COMPLEXITY || $value > self::MAX_COMPLEXITY) {
			throw new TInvalidDataValueException('proofofwork_complexity_invalid', self::MIN_COMPLEXITY, self::MAX_COMPLEXITY);
		}
		$this->setViewState('Complexity', $value, 500000);
	}

	/**
	 * @return int the seconds a challenge stays valid after render. Defaults to 1800.
	 */
	public function getChallengeExpiry()
	{
		return $this->getViewState('ChallengeExpiry', 1800);
	}

	/**
	 * @param int $value the seconds a challenge stays valid after render; values below 60 become 60.
	 */
	public function setChallengeExpiry($value)
	{
		$this->setViewState('ChallengeExpiry', max(60, TPropertyValue::ensureInteger($value)), 1800);
	}

	/**
	 * @return string the {@see TProofOfWorkStartMode} that starts solving. Defaults to Focus.
	 */
	public function getStartMode()
	{
		return $this->getViewState('StartMode', TProofOfWorkStartMode::Focus);
	}

	/**
	 * @param string $value the {@see TProofOfWorkStartMode} that starts solving.
	 */
	public function setStartMode($value)
	{
		$this->setViewState('StartMode', TPropertyValue::ensureEnum($value, TProofOfWorkStartMode::class), TProofOfWorkStartMode::Focus);
	}

	/**
	 * @return string the status text while solving. Defaults to a localized 'Verifying…'.
	 */
	public function getVerifyingText()
	{
		return $this->getViewState('VerifyingText', '') ?: Prado::localize('Verifying…');
	}

	/**
	 * @param string $value the status text while solving.
	 */
	public function setVerifyingText($value)
	{
		$this->setViewState('VerifyingText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the status text once solved. Defaults to a localized 'Verified'.
	 */
	public function getVerifiedText()
	{
		return $this->getViewState('VerifiedText', '') ?: Prado::localize('Verified');
	}

	/**
	 * @param string $value the status text once solved.
	 */
	public function setVerifiedText($value)
	{
		$this->setViewState('VerifiedText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the status text when solving fails. Defaults to a localized message.
	 */
	public function getFailedText()
	{
		return $this->getViewState('FailedText', '') ?: Prado::localize('Verification failed. Reload the page and try again.');
	}

	/**
	 * @param string $value the status text when solving fails.
	 */
	public function setFailedText($value)
	{
		$this->setViewState('FailedText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the text shown without JavaScript. Defaults to a localized message.
	 */
	public function getNoScriptText()
	{
		return $this->getViewState('NoScriptText', '') ?: Prado::localize('This form needs JavaScript to verify your submission.');
	}

	/**
	 * @param string $value the text shown without JavaScript.
	 */
	public function setNoScriptText($value)
	{
		$this->setViewState('NoScriptText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the posted solution, a JSON object.
	 */
	public function getValidationPropertyValue()
	{
		return $this->_solution;
	}

	/**
	 * @return bool whether the last validation succeeded. Defaults to true.
	 */
	public function getIsValid()
	{
		return $this->_isValid;
	}

	/**
	 * @param bool $value whether the last validation succeeded.
	 */
	public function setIsValid($value)
	{
		$this->_isValid = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Reads the posted solution.
	 * @param string $key the unique ID of this control.
	 * @param array $values the post data.
	 * @return bool false; the control raises no change event.
	 */
	public function loadPostData($key, $values)
	{
		$this->_solution = (string) ($values[$key] ?? '');
		return false;
	}

	/**
	 * The control raises no change event.
	 */
	public function raisePostDataChangedEvent()
	{
	}

	/**
	 * @return bool false; the solution has no change event.
	 */
	public function getDataChanged()
	{
		return false;
	}

	/**
	 * Verifies the posted solution and claims its challenge.
	 * Repeated calls in the same request return the first result.
	 * @return bool whether the solution is valid and unused.
	 */
	public function validate()
	{
		if ($this->_verified === null) {
			$this->_verified = $this->verifySolution($this->_solution);
		}
		return $this->_verified;
	}

	/**
	 * Checks a solution's signature, expiry, and hash, then claims its challenge.
	 * @param string $solution the posted JSON solution.
	 * @return bool whether the solution is valid and this call claimed it.
	 */
	protected function verifySolution(string $solution): bool
	{
		$data = json_decode($solution, true);
		if (!is_array($data) || !is_string($challenge = $data['challenge'] ?? null) || !is_string($salt = $data['salt'] ?? null)
			|| !is_string($signature = $data['signature'] ?? null) || !is_int($number = $data['number'] ?? null) || $number < 0) {
			return false;
		}
		if (!hash_equals($this->signChallenge($challenge), $signature)) {
			return false;
		}
		if (!preg_match('/[?&]expires=(\d+)/', $salt, $matches) || ($ttl = (int) $matches[1] - $this->getClock()->time()) < 0) {
			return false;
		}
		if (!hash_equals($challenge, hash('sha256', $salt . $number))) {
			return false;
		}
		return $this->claimCacheKey($this->resolveCacheModule(true), self::CACHE_KEY_PREFIX . $challenge, $ttl);
	}

	/**
	 * Creates a challenge for this render.
	 * @return array{algorithm: string, challenge: string, salt: string, maxnumber: int, signature: string} the challenge.
	 */
	protected function createChallenge(): array
	{
		$complexity = $this->getComplexity();
		$salt = bin2hex(random_bytes(12)) . '?expires=' . ($this->getClock()->time() + $this->getChallengeExpiry());
		$challenge = hash('sha256', $salt . random_int(0, $complexity));
		return [
			'algorithm' => self::ALGORITHM,
			'challenge' => $challenge,
			'salt' => $salt,
			'maxnumber' => $complexity,
			'signature' => $this->signChallenge($challenge),
		];
	}

	/**
	 * @param string $challenge the challenge hash.
	 * @return string the HMAC-SHA256 of the challenge bound to this control's unique ID.
	 */
	protected function signChallenge(string $challenge): string
	{
		$key = $this->getApplication()->getSecurityManager()->getValidationKey();
		return hash_hmac('sha256', $challenge . '|' . $this->getUniqueID(), $key);
	}

	/**
	 * Checks for the cache, registers for post data, and registers the client script.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->resolveCacheModule(true);
		$this->getPage()->registerRequiresPostData($this);
		$this->registerClientScript();
	}

	/**
	 * Registers the client class with a new challenge.
	 */
	protected function registerClientScript()
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript(self::SCRIPT_PACKAGE);
		$options = TJavaScript::encode($this->getClientOptions());
		$cs->registerEndScript('prado:' . $this->getClientID(), 'new Prado.WebUI.TProofOfWork(' . $options . ');');
	}

	/**
	 * @return array the client options: the challenge, whether to keep an unused solution,
	 *   the start mode, the solver URL, and the status texts.
	 */
	protected function getClientOptions(): array
	{
		$cs = $this->getPage()->getClientScript();
		return [
			'ID' => $this->getClientID(),
			'Challenge' => $this->createChallenge(),
			'KeepSolved' => $this->getKeepSolved(),
			'StartMode' => $this->getStartMode(),
			'WorkerUrl' => $cs->getPradoScriptAssetUrl() . self::SOLVER_SCRIPT,
			'VerifyingText' => $this->getVerifyingText(),
			'VerifiedText' => $this->getVerifiedText(),
			'FailedText' => $this->getFailedText(),
		];
	}

	/**
	 * @return bool whether the client keeps its solution: true in a callback that did not validate this control.
	 */
	protected function getKeepSolved(): bool
	{
		return $this->getPage()->getIsCallback() && $this->_verified === null;
	}

	/**
	 * Adds the live region attributes.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());
		$writer->addAttribute('role', 'status');
		$writer->addAttribute('aria-busy', 'false');
	}

	/**
	 * Renders the status text, the solution field, and the no-script text.
	 * The status holds a no-break space before solving, so later text does not shift the content below it.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	public function renderContents($writer)
	{
		$writer->addAttribute('class', 'pow-status');
		$writer->renderBeginTag('span');
		$writer->write('&nbsp;');
		$writer->renderEndTag();
		$writer->addAttribute('type', 'hidden');
		$writer->addAttribute('id', $this->getClientID() . '_solution');
		$writer->addAttribute('name', $this->getUniqueID());
		$writer->addAttribute('value', '');
		$writer->renderBeginTag('input');
		$writer->renderEndTag();
		$writer->renderBeginTag('noscript');
		$writer->write(THttpUtility::htmlEncode($this->getNoScriptText()));
		$writer->renderEndTag();
	}
}

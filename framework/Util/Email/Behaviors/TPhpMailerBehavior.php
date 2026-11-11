<?php

/**
 * TPhpMailerBehavior class
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Behaviors;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Prado\TPropertyValue;
use Prado\Util\TBehavior;
use Prado\Util\Email\Behaviors\ISMTPEmailerBehavior;
use Prado\Exceptions\TConfigurationException;

/**
 * TPhpMailerBehavior class.
 *
 * This reroutes emails to phpmailer and stops further processing of the email
 *
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @since 4.3.3
 */
class TPhpMailerBehavior extends TBehavior implements ISMTPEmailerBehavior
{
	private $_phpmailer;
	private $_dns;
	private $_scheme;
	private $_host;
	private $_port;
	private $_username;
	private $_password;
	private $_parameters;
	private $_debug = 0;
	private $_skipSslVerification;
	private $_wrapup;

	/**
	 * Declares events and the corresponding event handler methods.
	 * The events are defined by the {@link owner} component, while the handler
	 * methods by the behavior class. The handlers will be attached to the corresponding
	 * events when the behavior is attached to the {@link owner} component; and they
	 * will be detached from the events when the behavior is detached from the component.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events()
	{
		return ['onSendEmail' => 'phpMailerRerouter'];
	}

	/**
	 * This is the main function for converting a TEmail into PHPMailer format.
	 * @param $sender \Prado\Util\Email\TEmailModule
	 * @param $param \Prado\Util\Email\TEmailEventParameter
	 */
	public function phpMailerRerouter($sender, $param)
	{
		if (!is_object($param) || !$this->getEnabled()) {
			return null;
		}

		$email = $param->getEmail();
		$many = $param->getMany();

		if (!($date = $email->getDate())) { //before getHeaders
			$email->setDate($date = time());
		}

		$mail = $this->_phpmailer;
		if (!$mail) {
			$mail = $this->_phpmailer = new PHPMailer();
			if ($debug = $this->getDebug()) {
				$mail->SMTPDebug = $debug;
			}
			$mail->isSMTP();
			$mail->Host = $this->getHost();
			$mail->SMTPAuth = true;
			$mail->SMTPSecure = $this->getSecurity();
			$mail->Port = $this->getPort();
			$mail->Username = $this->getUsername();
			$mail->Password = $this->getPassword();

			if ($this->getSkipSSLVerification()) {
				$mail->SMTPOptions = ['ssl' => [
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true]];
			}
		}
		foreach ($email->getTo(false) as $to) {
			$mail->addAddress($to, '');
		}
		if ($sender = $email->getSender()) {
			$mail->addCustomHeader('Sender', $sender);
		}
		$mail->setFrom($email->getFrom(), '');
		foreach ($email->getReplyTo(false) as $replyto) {
			$mail->addReplyTo($replyto, '');
		}
		if ($returnPath = $email->getReturnPath()) {
			$mail->addCustomHeader('Return-Path', $returnPath);
		}
		foreach ($email->getCc(false) as $cc) {
			$mail->addCC($cc, '');
		}
		foreach ($email->getBcc(false) as $bcc) {
			$mail->addBCC($bcc, '');
		}
		foreach ($headers = $email->getHeaders() as $head => $value) {
			$mail->addCustomHeader($head, $value);
		}
		$mail->ContentType = $email->getContentType();
		$mail->CharSet = $email->getCharset();
		$mail->Priority = $email->getPriority();
		$mail->SMTPKeepAlive = $many;
		$mail->MessageId = $email->getMessageId();
		$mail->MessageDate = $email->getDate(true);
		$mail->Subject = $email->getSubject();
		$mail->Body = $email->getBody();
		$mail->isHTML($email->getIsHtml());
		$mail->AltBody = $email->getTextBody();

		foreach ($email->getAttachments() ?? [] as $name => $data) {
			if (is_array($data)) {
				$path = $data['path'] ?? null;
				$mime = $data['mime'] ?? null;
				$data = $data['data'] ?? null;
				$load = !$data;
				$name = $data['name'] ?? $name;
				if ($data) {
					$mail->addStringAttachment($data, $name);
				} else {
					$mail->addAttachment($path, $name);
				}
			} elseif (is_file($data)) {
				$mail->addAttachment($data, $name);
			} else {
				$mail->addStringAttachment($data, $name);
			}
		}

		$result = 0 ? $mail->send() : false;

		if (!$many && !$this->_wrapup) {
			$this->_phpmailer = null;
		} elseif (!$this->_wrapup) {
			$this->_wrapup = true;
			$owner = $this->getOwner();
			$app = $owner->getApplication();
			$app->attachEventHandler('OnEndRequest', [$owner, 'closeConnection'], 18);
		}
		if ($this->_phpmailer) {
			$mail->clearAllRecipients();
			$mail->clearAttachments();
			$mail->clearCustomHeaders();
		}

		return $result;
	}

	/**
	 * Closes the SMTP connection if there is one.
	 * @param mixed $callchain
	 */
	public function dyCloseConnection($callchain)
	{
		if ($mailer = $this->_phpmailer) {
			$mailer->smtpClose();
			$this->_phpmailer = null;
			$this->_wrapup = false;
		}
		return $callchain->dyCloseConnection();
	}

	public function getDNS()
	{
		if ($this->_dns) {
			return $this->_dns;
		}
		$scheme = $this->setScheme();
		$username = urlencode($this->getUsername());
		$password = urlencode($this->getPassword());
		$host = $this->getHost();
		$port = $this->getPort();
		$params = $this->getParameters();

		$dns = $scheme;
		if ($dns != '') {
			$dns .= '+';
		}
		$dns .= 'smtp://';
		$dns .= ($username) ? $username : '';
		$dns .= ($password) ? ':' . $password : '';
		$dns .= ($username || $password) ? '@' . $password : '';
		$dns .= $host;
		$dns .= ($port != 25) ? ':' . $port : '';
		$dns .= ($params) ? '?' . http_build_query($params) : '';
	}

	/**
	 * URL encoded smtp dns connection string.
	 * "smtp://user:pass@smtp.example.com:25"
	 * "ssl+smtps://smtp.example.com"
	 * "tls+smtp://user:pass@smtp.example.com:25"
	 * @param mixed $dns
	 */
	public function setDNS($dns)
	{
		if (($dns = parse_url($dns)) !== false) {
			$scheme = explode('+', $dns['scheme']);
			$this->setScheme($dns['scheme']);
			if (count($scheme) == 1) {
				array_unshift($scheme, '');
			}
			if ($scheme[1] === 'smtps') {
				$port = 587;
			} else {
				$port = 25;
			}
			if ($scheme[0] != null) {
				$port = 587;
			}
			if (isset($dns['port'])) {
				$port = $dns['port'];
			}
			$this->setHost($dns['host']);
			$this->setPort($port);
			if (isset($dns['user'])) {
				$this->setUsername(urldecode($dns['user']));
			}
			if (isset($dns['pass'])) {
				$this->setPassword(urldecode($dns['pass']));
			}
			if (isset($dns['query'])) {
				parse_str($dns['query'], $params);
				$this->setParameters($params);
			}
			$this->_dns = $dns;
		} else {
			throw new TConfigurationException('phpmailer_invalid_dns');
		}
	}
	public function getScheme()
	{
		return $this->_security;
	}
	public function setScheme($scheme)
	{
		$this->_dns = null;
		$this->_scheme = TPropertyValue::ensureString($scheme);
	}
	public function getHost()
	{
		return $this->_host;
	}
	public function setHost($host)
	{
		$this->_dns = null;
		$this->_host = TPropertyValue::ensureString($host);
	}
	public function getPort()
	{
		if ($this->_port == null) {
			$security = $this->getSecurity();
			if ($security != 'tls') {
				$this->_port = 587;
			} else {
				$this->_port = 25;
			}
		}
		return $this->_port;
	}
	public function setPort($port)
	{
		$this->_dns = null;
		$this->_port = TPropertyValue::ensureInteger($port);
	}
	public function getUsername()
	{
		return $this->_username;
	}
	public function setUsername($username)
	{
		$this->_dns = null;
		$this->_username = TPropertyValue::ensureString($username);
	}
	public function getPassword()
	{
		return $this->_password;
	}
	public function setPassword($password)
	{
		$this->_dns = null;
		$this->_password = TPropertyValue::ensureString($password);
	}
	public function getParameters()
	{
		return $this->_parameters;
	}
	public function setParameters($params)
	{
		$this->_dns = null;
		$this->_parameters = $params;
	}

	/**
	 * @return int 0..4, inclusive. The level of debugging.
	 */
	public function getDebug()
	{
		return $this->_debug;
	}
	/**
	 * @param int 0..4, inclusive, use 3 and 4 if phpmailer is not connecting
	 * @param mixed $debug
	 */
	public function setDebug($debug)
	{
		$this->_debug = TPropertyValue::ensureInteger($debug);
	}
	public function getSkipSSLVerification()
	{
		return $this->_skipSslVerification;
	}
	public function setSkipSSLVerification($skip)
	{
		$this->_skipSslVerification = ($skip === null) ? null : TPropertyValue::ensureBoolean($skip);
	}
}

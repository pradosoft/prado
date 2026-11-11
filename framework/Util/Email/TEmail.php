<?php

/**
 * TEmail class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email;

use Prado\TComponent;
use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Util\Email\TEmailAddress;
use Prado\Util\Email\TEmailModule;

/**
 * TEmail class
 *
 * This is a subclass of TPage without any dependency upon Request, Response,
 * Session, etc.  It renders the message body from its TTemplateControl. There
 * is a field for just the TextBody without HTML.
 *
 * If there is no text version of the email, the HTML is rendered into text.
 *
 * All the fields for an email are present: To, From, Sender, ReplyTo, ReturnPath,
 * CC, BCC, date, priority, charset, Subject, and attachments.
 *
 * An email can have 3 different file publish styles: URL, Attachment, and Embed.
 *    * URL publish style publish assets like normal web pages, but an AbsoluteUrl
 * 		is required for the asset URL to work properly
 *    * Attachment publish style attaches all page assets to the email as file
 *		Attachments.
 *	  * Embed publish style encodes the asset data into the webpage.
 *
 * The ".email" can have directives specifying its Subject.  The associated ".text"
 * file is the text only version of the email.
 *
 * All emails are localized like TPage for globalization.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TEmail extends TComponent
{
	/** @var string the unique ID of the email */
	protected $_uid;

	/** @var null|array the set headers */
	protected $_headers;

	/** @var null|string the set headers */
	protected $_username;

	/** @var array[TEmailAddress]|string To address */
	protected $_to;

	/** @var string Sender address, who the email is physically from */
	protected $_sender;

	/** @var string From address */
	protected $_from;

	/** @var array[TEmailAddress]|string ReplyTo address */
	protected $_replyTo;

	/** @var string returnPath address */
	protected $_returnPath;

	/** @var array[TEmailAddress]|string CC address */
	protected $_cc;

	/** @var array[TEmailAddress]|string BCC address */
	protected $_bcc;

	/** @var null|int|string date of the email */
	protected $_date;

	/** @var null|int Priority of the email 1..5, inclusive */
	protected $_priority;

	/** @var null|string unique ID of the email */
	private $_messageId;

	/** @var string Content Type of the message */
	private $_contentType;

	/** @var string Charset of the message */
	private $_charset;

	/** @var string Subject */
	private $_subject;

	/** @var bool is the body HTML */
	private $_isHtml = true;

	/** @var null|string cached rendered body of the message */
	private $_body;

	/** @var null|string text body of the message */
	private $_textBody;

	/** @var null|array the attachments for the email */
	private $_attachments;

	/** @var null|array the custom data */
	private $_customData;

	/** @var \Prado\Util\Email\TEmailModule for email tasks */
	private $_module;


	protected function asAddressArray($data)
	{
		if ($data === null) {
			return null;
		} elseif (is_string($data)) {
			$data = explode(',', $data);
		} elseif ($data instanceof TEmailAddress) {
			return [$data];
		} elseif (!is_array($data)) {
			return [];
		}

		return array_map(function ($a) {
			$b = null;
			if (is_array($a)) {
				$b = $a[1] ?? null;
				$a = $a[0] ?? null;
			}
			return ($a instanceof TEmailAddress) ? $a : new TEmailAddress($a, $b);
		}, $data);
	}

	/**
	 * Sends the TEmail through the TEmailModule.  It customizes the email
	 * with array $customData in the format ['REPLACEMENT' => 'VALUE'] where
	 * any text in the email matching the key with brackets, eg. '{REPLACEMENT}',
	 * would be replaced by the array value, eg. 'VALUE'.
	 * @param null|array text replacement ["$key" => $value]
	 * @param null|array $customData
	 * @param mixed $many
	 * @return bool successful sent.
	 */
	public function send($customData = null, $many = false)
	{
		if (!$this->getTo()) {
			throw new TConfigurationException('email_send_without_to');
		}
		$this->_customData = $customData ?? $this->_customData;

		if (!($module = $this->getEmailerModule())) {
			$app = $this->getApplication();
			if ($app->hasMethod('sendEmail')) {
				$module = $app;
			}
		}
		if (!$module) {
			throw new TConfigurationException('email_no_emailermodule');
		}
		$return = $module->sendEmail($this, $customData, $many);
		return $return;
	}

	/**
	 * This parses out a Email headers and populates the TEmail properties.
	 * @param array $headers headers for the email
	 */
	public function setHeaders($headers)
	{
		foreach ($headers as $header => $value) {
			$lowerHeader = strtolower($header);
			//Received References In-Reply-To X-Failed-Recipients
			if ($lowerHeader == 'from') {
				$this->setFrom($value);
				unset($headers[$header]);
			} elseif ($lowerHeader == 'sender') {
				$this->setSender($value);
				unset($headers[$header]);
			} elseif ($lowerHeader == 'reply-to') {
				$this->setReplyTo($value);
				unset($headers[$header]);
			} elseif ($lowerHeader == 'return-path') {
				$this->setReturnPath($value);
				unset($headers[$header]);
			} elseif ($lowerHeader == 'cc') {
				$this->setCc($value);
				unset($headers[$header]);
			} elseif ($lowerHeader == 'bcc') {
				$this->setBcc($value);
				unset($headers[$header]);
			} elseif ($lowerHeader == 'date') {
				$this->setDate($value);
				unset($headers[$header]);
			} elseif ($lowerHeader == 'x-priority') {
				$this->setPriority($value);
				unset($headers[$header]);
			} elseif ($lowerHeader == 'message-id') {
				$this->setMessageId($value);
				unset($headers[$header]);
			} elseif ($lowerHeader == 'content-type') {
				if (preg_match('/^\s*([^\s;]*)(;\s*charset=([^\s;]*))?$/', $value, $matches)) {
					$this->setContentType($matches[1]);
					$this->setCharset($matches[2]);
					unset($headers[$header]);
				}
			}
		}
		$this->_headers = $headers;
	}


	public function getHeaders()
	{
		$headers = [];
		if ($this->_headers) {
			$headers = $this->_headers;
		}
		if ($from = $this->getFrom()) {
			$headers['From'] = (string) $from;
		}
		if ($sender = $this->getSender()) {
			$headers['Sender'] = (string) $sender;
		}
		if ($replyto = $this->getReplyTo(true)) {
			$headers['Reply-To'] = $replyto;
		}
		if ($returnPath = $this->getReturnPath()) {
			$headers['Return-Path'] = (string) $returnPath;
		}
		if ($cc = $this->getCc(true)) {
			$headers['Cc'] = $cc;
		}
		if ($bcc = $this->getBcc(true)) {
			$headers['Bcc'] = $bcc;
		}
		if ($date = $this->getDate(true)) {
			$headers['Date'] = $date;
		}
		if ($priority = $this->getPriority()) {
			$headers['X-Priority'] = (string) $priority;
		}
		if ($mid = $this->getMessageId()) {
			$emailBase = $returnPath ?? $replyto ?? $from;
			$emailBase = substr($emailBase, strpos($emailBase, '@'));
			$headers['Message-ID'] = '<' . $mid . $emailBase . '>';
		}
		return $headers;
	}

	/**
	 * This parses out a Email headers and populates the TEmail properties.
	 * @param array $headers headers for the email
	 * @param mixed $header
	 * @param null|mixed $value
	 */
	public function setHeader($header, $value = null)
	{
		if ($value === null) {
			unset($this->_headers[$header]);
		} else {
			$this->_headers[$header] = $value;
		}
	}

	/**
	 * If it is not set, this generates a new ID.  This is the internal
	 * tracking unique ID of the email and should be used internally.
	 * @return string unique id of the email
	 */
	public function getUID()
	{
		if ($this->_uid === null) {
			$this->_uid = sprintf('%08x%08x%08x%08x', mt_rand(), mt_rand(), mt_rand(), mt_rand());
		}
		return $this->_uid;
	}

	/**
	 * @param string unique id of the email
	 * @param mixed $uid
	 */
	public function setUID($uid)
	{
		$this->_uid = $uid;
	}

	/**
	 * If it is not set, this generates a new ID.  This is the external
	 * public email tracking id.
	 * @return string unique id of the email
	 */
	public function getTrackingId()
	{
		if ($this->_trackingId === null) {
			$this->_trackingId = sprintf('%08x%08x%08x%08x', mt_rand(), mt_rand(), mt_rand(), mt_rand());
		}
		return $this->_trackingId;
	}

	/**
	 * @param string unique id of the email
	 * @param mixed $id
	 */
	public function setTrackingId($id)
	{
		$this->_trackingId = $id;
	}

	/**
	 * @return string username of the email
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 * @param string $username
	 */
	public function setUsername($username)
	{
		$this->_username = $username;
	}

	/**
	 * @param null|bool $style return type: null=any, true=string, false=array
	 * @return string|string[] the To of the email
	 */
	public function getTo($style = null)
	{
		if ($style === true) {
			return implode(', ', $this->_to ?? []);
		}
		return $this->_to;
	}

	/**
	 * @param string|string[] $address $to the To of the email
	 */
	public function setTo($address)
	{
		if ($address === null || is_string($address) || is_array($address) || ($address instanceof TEmailAddress)) {
			$this->_to = $this->asAddressArray($address);
		} else {
			throw new TConfigurationException('email_bad_to');
		}
	}

	/**
	 * @return string the Sender of the email
	 */
	public function getSender()
	{
		return $this->_sender;
	}

	/**
	 * @param string $address the Sender of the email
	 */
	public function setSender($address)
	{
		if ($address instanceof TEmailAddress) {
			$this->_sender = $address;
		} else {
			$this->_sender = new TEmailAddress(TPropertyValue::ensureString($address));
		}
	}

	/**
	 * @return string the From of the email
	 */
	public function getFrom()
	{
		return $this->_from;
	}

	/**
	 * @param string $address the From of the email, may be set in template directives
	 */
	public function setFrom($address)
	{
		if ($address instanceof TEmailAddress) {
			$this->_from = $address;
		} else {
			$this->_from = new TEmailAddress(TPropertyValue::ensureString($address));
		}
	}

	/**
	 * @param null|bool $style return type: null=any, true=string, false=array
	 * @return string the ReplyTo of the email
	 */
	public function getReplyTo($style = null)
	{
		if ($style === true) {
			return implode(', ', $this->_replyTo ?? []);
		}
		return $this->_replyTo;
	}

	/**
	 * @param string $address the ReplyTo of the email, may be set in template directives
	 */
	public function setReplyTo($address)
	{
		if ($address === null || is_string($address) || is_array($address) || ($address instanceof TEmailAddress)) {
			$this->_replyTo = $this->asAddressArray($address);
		} else {
			throw new TConfigurationException('email_bad_replyto');
		}
	}

	/**
	 * When an email fails to deliver, this is the return email address.
	 * @return string the Return-Path of the email
	 */
	public function getReturnPath()
	{
		return $this->_returnPath;
	}

	/**
	 * When an email fails to deliver, this is the return email address.
	 * @param string $address the Return-Path of the email, may be set in template directives
	 */
	public function setReturnPath($address)
	{
		if ($address instanceof TEmailAddress) {
			$this->_returnPath = $address;
		} else {
			$this->_returnPath = new TEmailAddress(TPropertyValue::ensureString($address));
		}
	}

	/**
	 * May be comma separated for multiple addresses.
	 * @param null|bool $style return type: null=any, true=string, false=array
	 * @return string the CC of the email
	 */
	public function getCc($style = null)
	{
		if ($style === true) {
			return implode(', ', $this->_cc ?? []);
		}
		return $this->_cc;
	}

	/**
	 * @param string $address the Cc of the email, may be set in template directives
	 */
	public function setCc($address)
	{
		if ($address === null || is_string($address) || is_array($address) || ($address instanceof TEmailAddress)) {
			$this->_cc = $this->asAddressArray($address);
		} else {
			throw new TConfigurationException('email_bad_cc');
		}
	}

	/**
	 * @param null|bool $style return type: null=any, true=string, false=array
	 * @return string the BCC of the email
	 */
	public function getBcc($style = null)
	{
		if ($style === true) {
			return implode(', ', $this->_bcc ?? []);
		}
		return $this->_bcc;
	}

	/**
	 * May be comma separated for multiple addresses.
	 * @param string $address the Bcc of the email, may be set in template directives
	 */
	public function setBcc($address)
	{
		if ($address === null || is_string($address) || is_array($address) || ($address instanceof TEmailAddress)) {
			$this->_bcc = $this->asAddressArray($address);
		} else {
			throw new TConfigurationException('email_bad_cc');
		}
	}

	/**
	 * @param null|bool $style return type: null=any, true=email format, false=numeric
	 * @return string the Date of the email
	 */
	public function getDate($style = null)
	{
		$date = $this->_date;
		if ($style === true && is_numeric($date)) {
			$date = date("D, d M Y H:i:s O", $date);
		} elseif ($style === false && $date && !is_numeric($date) && $date !== null) {
			$date = strtotime($date);
		}
		return $date;
	}

	/**
	 * @param string $date the Date of the email
	 */
	public function setDate($date)
	{
		$this->_date = $date;
	}

	/**
	 * @return int priority of the email 1..5, inclusive.  1 is Highest Priority, 5 is Lowest.
	 */
	public function getPriority()
	{
		return $this->_priority;
	}

	/**
	 * @param mixed $priority
	 * @return int $priority priority of the email 1..5, inclusive.  1 is Highest Priority, 5 is Lowest.
	 */
	public function setPriority($priority)
	{
		$this->_priority = ($priority !== null && $priority !== '') ? max(1, min(5, TPropertyValue::ensureInteger($priority))) : null;
	}

	/**
	 * @return string the unique id of the email.
	 */
	public function getMessageId()
	{
		return $this->_messageId;
	}

	/**
	 * @param string $id the unique id of the email
	 */
	public function setMessageId($id)
	{
		if ($id === null) {
			$this->_messageId = null;
		} else {
			$id = TPropertyValue::ensureString($id);
			if (preg_match('/^(?:.*<\s*)?([^\s>]+)(?:\s*>?\s*)$/', $id, $matches)) {
				$this->_messageId = $matches[1];
			} else {
				$this->_messageId = $id;
			}
		}
	}

	/**
	 * @return string Content-Type of the main email message body.
	 */
	public function getContentType()
	{
		return $this->_contentType;
	}

	/**
	 * @param mixed $type
	 * @return string $type Content-Type of the main email message body.
	 */
	public function setContentType($type)
	{
		$this->_contentType = $type !== null ? TPropertyValue::ensureString($type) : null;
	}

	/**
	 * @return string charset of the main email message body.
	 */
	public function getCharset()
	{
		return $this->_charset;
	}

	/**
	 * @param mixed $charset
	 * @return string $charset charset of the main email message body.
	 */
	public function setCharset($charset)
	{
		$this->_charset = $charset !== null ? TPropertyValue::ensureString($charset) : null;
	}

	/**
	 * @return string the Subject of the email
	 */
	public function getSubject()
	{
		return $this->_subject;
	}

	/**
	 * @param string $subject the Subject of the email, may be set in template directives
	 */
	public function setSubject($subject)
	{
		$this->_subject = TPropertyValue::ensureString($subject);
	}

	/**
	 * @return bool is the body of the email in HTML format
	 */
	public function getIsHtml()
	{
		return $this->_isHtml;
	}

	/**
	 * @param bool $isHtml is the body of the email in HTML format
	 */
	public function setIsHtml($isHtml)
	{
		$this->_isHtml = TPropertyValue::ensureBoolean($isHtml);
	}

	/**
	 * @param null|mixed $customData
	 * @return string the body rendered from the template
	 */
	public function getBody($customData = null)
	{
		$customData ??= $this->_customData;
		$body = $this->_body;
		if ($customData) {
			$keys = array_map(function ($a) {
				return '/{{\s*' . $a . '\s*}}/';
			}, array_keys($customData));
			$body = preg_replace($keys, $values = array_values($customData), $body ?? '');
		}
		return $body;
	}

	/**
	 * @param string $body replaces the template body.
	 */
	public function setBody($body)
	{
		$this->_body = ($body == null) ? null : $body;
	}

	/**
	 * @param null|mixed $customData
	 * @return string the Subject of the email
	 */
	public function getTextBody($customData = null)
	{
		$customData ??= $this->_customData;
		$text = $this->_textBody;
		if ($customData) {
			$keys = array_map(function ($a) {
				return '/{{\s*' . $a . '\s*}}/';
			}, array_keys($customData));
			$text = preg_replace($keys, $values = array_values($customData), $text ?? '');
		}
		return $text;
	}

	/**
	 * @param string $cacheBody cache the template Body of the email, may be set in template directives
	 * @param mixed $textBody
	 */
	public function setTextBody($textBody)
	{
		$this->_textBody = ($textBody === null) ? null : TPropertyValue::ensureString($textBody);
	}

	/**
	 * @return string the file Attachments of the email
	 */
	public function getAttachments()
	{
		return $this->_attachments;
	}

	/**
	 * @param string $attachments the file Attachments of the email
	 */
	public function setAttachments($attachments)
	{
		$this->_attachments = $attachments;
	}

	/**
	 * The attachment can be a file path, the data itself, or an array in the format
	 * of ['name' => 'myimage.jpg', 'path' => '/path/to/app/image.jpg', 'mime' =>
	 * 'image/jpeg', 'data' => "binarydata"].  $file['name'] overrides $filename.
	 * $file['data'] overrides $file['path']. If $file['mime'] is not set, this
	 * calls mime_content_type to discover the MIME type of the file path.
	 * @param string $filename name of the attachment file, the cid
	 * @param array|string $attachment the file Attachment
	 */
	public function addAttachment($filename, $attachment)
	{
		$this->_attachments[$filename] = $attachment;
	}

	/**
	 * @return array the Custom Data of the Email specific to the sending email.
	 */
	public function getCustomData()
	{
		return $this->_customData;
	}

	/**
	 * @param array $data the Custom Data of the Email specific to the sending email.
	 */
	public function setCustomData($data)
	{
		$this->_customData = $data;
	}

	/**
	 * @return \Prado\Util\TEmailModule The manager that created the Email.
	 */
	public function getEmailerModule()
	{
		return $this->_module;
	}

	/**
	 * @param \Prado\Util\TEmailModule $module TEmailerBehavior The manager that created the Email
	 */
	public function setEmailerModule(TEmailModule $module)
	{
		$this->_module = $module;
	}

	/**
	 * Converts an HTML string into text.
	 * @param string $html
	 * @return string text version of the HTML
	 */
	public static function htmlToText($html)
	{
		$return = '--{{%%return%%}}--';
		$html = preg_replace(['/<\w+\s.*?alt\s*=\s*([\'"])(.*?)\1.*?\/?>/i', '/<br\W*?\/?>/i', '/<\/(p|tr|li|ul)\W*?>/i', '/<\/td\W*?>/i', '/<\/?i\W*?\/?>/i', '/<\/?b\W*?\/?>/i', '/<h1\W*?\/?>/i', '/<\/h1\W*?\/?>/i', '/<(h2|h3)\W*?\/?>/i', '/<\/(h2|h3)\W*?\/?>/i', '/<(h4|h5|h6)\W*?\/?>/i', '/<\/(h4|h5|h6)\W*?\/?>/i'], ['$2', $return, $return, ' ', '_', '*', '### ', ' ###', '## ', ' ##', '# ', ' #'], $html);
		return html_entity_decode(preg_replace(["/\n/", "/\r/", "/[\s\t]+/", '/' . $return . '/'], ['', '', ' ', "\n"], strip_tags($html)), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
	}



	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next load.
	 * Reimplement in derived classes to add new variables, but remember to also to call the parent
	 * implementation first.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);

		$exprops[] = "\0Prado\Util\Email\TEmail\0_module";

		if ($this->_username == null) {
			$exprops[] = "\0Prado\Util\Email\TEmail\0_username";
		}
		if ($this->_headers == null) {
			$exprops[] = "\0Prado\Util\Email\TEmail\0_headers";
		}
		if ($this->_replyTo == null) {
			$exprops[] = "\0Prado\Util\Email\TEmail\0_replyTo";
		}
		if ($this->_returnPath == null) {
			$exprops[] = "\0Prado\Util\Email\TEmail\0_returnPath";
		}
		if ($this->_cc == null) {
			$exprops[] = "\0Prado\Util\Email\TEmail\0_cc";
		}
		if ($this->_bcc == null) {
			$exprops[] = "\0Prado\Util\Email\TEmail\0_bcc";
		}
		if ($this->_date == null) {
			$exprops[] = "\0Prado\Util\Email\TEmail\0_date";
		}
		if ($this->_priority == null) {
			$exprops[] = "\0Prado\Util\Email\TEmail\0_priority";
		}
		if ($this->_messageId == null) {
			$exprops[] = "\0Prado\Util\Email\UI\TEmail\0_messageId";
		}
		if ($this->_contentType == null) {
			$exprops[] = "\0Prado\Util\Email\UI\TEmail\0_contentType";
		}
		if ($this->_charset == null) {
			$exprops[] = "\0Prado\Util\Email\UI\TEmail\0_charset";
		}
		if ($this->_subject === true) {
			$exprops[] = "\0Prado\Util\Email\UI\TEmail\0_subject";
		}
		if ($this->_isHtml === true) {
			$exprops[] = "\0Prado\Util\Email\UI\TEmail\0_isHtml";
		}
		if ($this->_body == null) {
			$exprops[] = "\0Prado\Util\Email\UI\TEmail\0_body";
		}
		if ($this->_textBody == null) {
			$exprops[] = "\0Prado\Util\Email\UI\TEmail\0_textBody";
		}
		if ($this->_attachments == null) {
			$exprops[] = "\0Prado\Util\Email\UI\TEmail\0_attachments";
		}
		if ($this->_customData == null) {
			$exprops[] = "\0Prado\Util\Email\UI\TEmail\0_customData";
		}
	}
}

<?php

/**
 * TNativeMailBehavior class
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Behaviors;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\TBehavior;

/**
 * TNativeMailBehavior interface.
 *
 * This processes emails to the default php mail() function.  This class is
 * automatically added by TEmailModule to itself to process and send emails
 * when there are no other email handlers.  This handler is added at a priority
 * of 20 so it's after any default priority mail handlers.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @since 4.3.3
 */
class TNativeMailBehavior extends TBehavior
{
	/**
	 * Attaches an event to the owner with a specific priority level.
	 * The behavior events() cannot handle priorities.
	 * @param mixed $owner
	 */
	public function attach($owner)
	{
		$owner->attachEventHandler('onSendEmail', [$this, 'nativeEmailHandler'], 20);

		parent::attach($owner);
	}

	/**
	 * Native handler for sending emails by php mail()
	 * @param TEmailModule $sender The Emailer Module sending the email.
	 * @param TEmailEventParameter $param
	 */
	public function nativeEmailHandler($sender, $param)
	{
		if (!is_object($param) || !$this->getEnabled()) {
			return null;
		}
		$email = $param->getEmail();

		if (!($date = $email->getDate())) { //before getHeaders
			$email->setDate($date = time());
		}

		$html = $email->getBody();
		$message = $email->getTextBody();
		$headers = $email->getHeaders();
		$to = $email->getTo(true);
		$subject = $email->getSubject();
		$attachments = $email->getAttachments();

		$contentTypeHeader = 'Content-Type';
		$type = ($type = $email->getContentType()) ? $type : ($html ? 'text/html' : 'text/plain');
		$charset = ($charset = $email->getCharset()) ? $charset : 'UTF-8';
		$contentType = $type . '; charset=' . $charset;
		$headers[$contentTypeHeader] = $contentType;
		//$headers['MIME-Version'] = '1.0';

		$headers['X-Mailer'] = 'PHP/' . phpversion() . ' PRADO/' . Prado::getVersion();

		$hasAttachments = count($attachments ?? []);
		$htmlAndText = $html && $message;
		$isMultipart = $hasAttachments || $htmlAndText;
		$crln = "\r\n";

		$maxColumns = 76;
		$debug = true;
		if ($isMultipart) {
			$boundary = md5(time());
			$boundaryPre = '--' . $boundary;
			$contentType = $headers[$contentTypeHeader];

			$headers[$contentTypeHeader] = 'multipart/mixed; boundary="' . $boundary . '"';
			$content = $boundaryPre . $crln . $crln;

			$contentBoundary = $boundary;
			$contentBoundaryPre = '--' . $contentBoundary;
			if ($hasAttachments && $htmlAndText) {
				$contentBoundary = 'Message-' . $boundary;
				$contentBoundaryPre = '--' . $contentBoundary;
				$content .= "Content-Type: multipart/alternative; boundary=\"{$contentBoundary}\"" . $crln . $crln;
				$content .= $contentBoundaryPre . $crln;
			}
			if ($message) {
				$content .= $contentTypeHeader . ": text/plain; charset=" . $charset . $crln;
				$content .= $debug ? '' : 'Content-Transfer-Encoding: quoted-printable' . $crln;
				$content .= $crln;

				$content .= ($debug ? wordwrap($message, $maxColumns, "\n") : quoted_printable_encode($message)) . $crln . $crln;
			}
			if ($html) {
				if ($message) {
					$content .= $contentBoundaryPre . $crln . $crln;
				}
				$content .= $contentTypeHeader . ': ' . $contentType . $crln;
				$content .= 'MIME-Version: 1.0' . $crln;
				if (!$debug) {
					$content .= 'Content-Transfer-Encoding: base64' . $crln;
				}
				$content .= $crln;
				if (!$debug) {
					$content .= chunk_split(base64_encode($html));
				} else {
					$content .= wordwrap($html, $maxColumns, "\n");
				}
				$content .= $crln . $crln;
			}
			if ($contentBoundary != $boundary) {
				$content .= $contentBoundaryPre . '--' . $crln . $crln;
			}

			foreach ($attachments ?? [] as $attchmentName => $data) {
				$content .= $boundaryPre . $crln . $crln;
				$mime = null;
				if (is_array($data)) {
					$path = $data['path'] ?? null;
					$mime = $data['mime'] ?? null;
					$data = $data['data'] ?? null;
					$load = !$data;
					$attchmentName = $data['name'] ?? $attchmentName;
				} else {
					$path = $data;
					$load = true;
				}
				if ($load && file_exists($path)) {
					$mime ??= mime_content_type($path);
					$data = file_get_contents($path);
				}
				if (!$mime) {
					$mime = 'application/octet-stream';
				}

				$dataSize = strlen($data);

				$content .= "Content-Type: {$mime} name=\"{$attchmentName}\"" . $crln;
				$content .= "Content-Transfer-Encoding: base64" . $crln;
				$content .= 'MIME-Version: 1.0' . $crln;
				$content .= "Content-Disposition: attachment; filename=\"{$attchmentName}\"; size={$dataSize}" . $crln . $crln;
				$content .= chunk_split(base64_encode($data)) . $crln . $crln;
			}
			$content .= $boundaryPre . "--" . $crln . $crln;
		} else {
			if (!$debug) {
				$headers['Content-Transfer-Encoding'] = 'quoted-printable';
			}
			$message = ($message != null ? $message : null) ?? $html;
			$content = ($debug ? wordwrap($message, $maxColumns, "\n") : quoted_printable_encode($message));
		}
		$debugDisplay = false;
		if ($debugDisplay) {
			echo("to: {$to}\n");
			foreach ($headers as $key => $value) {
				echo($key . ": " . $value . "\n");
			}
			echo("Subject: " . $subject . "\n");
			echo($content . "\n\n");
		}
		return mail($to, $subject, $content, $headers, $sender->getMailAdditionalParameters());
	}
}

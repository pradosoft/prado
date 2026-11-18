<?php

/**
 * TEmailBounceParser class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Bounce;

/**
 * TEmailBounceParser class.
 *
 * TEmailBounceParser reads the failed and delayed recipients from a bounce message.
 *
 * - A delivery status notification (RFC 3464, `multipart/report`) gives one
 *   {@see TEmailBounce} per recipient whose Action is "failed" or "delayed".
 * - A message without a DSN part that has an X-Failed-Recipients header gives one
 *   bounce per listed address.  The status is the first enhanced status code in the
 *   body, or 5.0.0.
 * - Any other message gives no bounces.
 *
 * The original Message-ID comes from, in order: the DSN's X-Original-Message-ID, the
 * Message-ID of the returned headers (`text/rfc822-headers` or `message/rfc822`), and
 * the bounce's In-Reply-To or References header.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailBounceParser
{
	/** DSN actions that are bounces. */
	public const BOUNCE_ACTIONS = ['failed', 'delayed'];

	/**
	 * Parses a raw message.
	 * @param string $message the raw RFC 5322 message, headers and body.
	 * @return TEmailBounce[] the bounced recipients; empty when the message is not a bounce.
	 */
	public function parse(string $message): array
	{
		$message = str_replace(["\r\n", "\r"], "\n", $message);
		[$headers, $body] = static::splitMessage($message);
		$parts = $this->collectParts($headers, $body);
		$status = null;
		$returnedHeaders = null;
		foreach ($parts as [$type, $partHeaders, $content]) {
			if ($status === null && in_array($type, ['message/delivery-status', 'message/global-delivery-status'], true)) {
				$status = $content;
			} elseif ($returnedHeaders === null && in_array($type, ['text/rfc822-headers', 'message/rfc822', 'message/global', 'message/global-headers'], true)) {
				$returnedHeaders = static::splitMessage($content)[0];
			}
		}
		$originalId = (string) ($returnedHeaders['message-id'][0] ?? '');
		if ($originalId === '') {
			$originalId = $this->getReplyMessageId($headers);
		}
		if ($status !== null) {
			return $this->parseDeliveryStatus($status, $originalId);
		}
		return $this->parseFailedRecipients($headers, $body, $originalId);
	}

	/**
	 * Reads the recipient blocks of a message/delivery-status part.
	 * @param string $status the decoded delivery-status content.
	 * @param string $originalId the Message-ID found outside the DSN.
	 * @return TEmailBounce[] the failed and delayed recipients.
	 */
	protected function parseDeliveryStatus(string $status, string $originalId): array
	{
		$blocks = preg_split('/\n\s*\n/', trim($status));
		$perMessage = static::parseHeaders($blocks[0] ?? '');
		if (!isset($perMessage['action']) && !isset($perMessage['final-recipient'])) {
			array_shift($blocks);
		}
		$originalId = (string) ($perMessage['x-original-message-id'][0] ?? $originalId);
		$bounces = [];
		foreach ($blocks as $block) {
			$fields = static::parseHeaders($block);
			$action = strtolower(trim((string) ($fields['action'][0] ?? '')));
			if (!in_array($action, self::BOUNCE_ACTIONS, true)) {
				continue;
			}
			$recipient = static::stripAddressType((string) ($fields['final-recipient'][0] ?? $fields['original-recipient'][0] ?? ''));
			if ($recipient === '') {
				continue;
			}
			$code = preg_match('/\b([245]\.\d{1,3}\.\d{1,3})\b/', (string) ($fields['status'][0] ?? ''), $m) ? $m[1] : ($action === 'delayed' ? '4.0.0' : '5.0.0');
			$bounces[] = new TEmailBounce(
				$recipient,
				$action,
				$code,
				static::stripAddressType((string) ($fields['diagnostic-code'][0] ?? '')),
				$originalId,
				static::stripAddressType((string) ($fields['remote-mta'][0] ?? ''))
			);
		}
		return $bounces;
	}

	/**
	 * Reads a non-DSN bounce that lists its recipients in X-Failed-Recipients.
	 * @param array $headers the message headers.
	 * @param string $body the message body.
	 * @param string $originalId the Message-ID found in the message.
	 * @return TEmailBounce[] the failed recipients.
	 */
	protected function parseFailedRecipients(array $headers, string $body, string $originalId): array
	{
		$list = implode(',', $headers['x-failed-recipients'] ?? []);
		$recipients = array_filter(array_map('trim', explode(',', $list)));
		if (!$recipients) {
			return [];
		}
		$code = preg_match('/\b([45]\.\d{1,3}\.\d{1,3})\b/', $body, $m) ? $m[1] : '5.0.0';
		$diagnostic = preg_match('/^.*\b' . preg_quote($code, '/') . '\b.*$/m', $body, $m) ? trim($m[0]) : '';
		if ($originalId === '' && preg_match('/^Message-ID:\s*(<[^>]+>)/mi', $body, $m)) {
			$originalId = $m[1];
		}
		$bounces = [];
		foreach ($recipients as $recipient) {
			$bounces[] = new TEmailBounce($recipient, $code[0] === '4' ? 'delayed' : 'failed', $code, $diagnostic, $originalId);
		}
		return $bounces;
	}

	/**
	 * @param array $headers the message headers.
	 * @return string the In-Reply-To Message-ID, or the last References Message-ID, or empty.
	 */
	protected function getReplyMessageId(array $headers): string
	{
		if (($id = trim((string) ($headers['in-reply-to'][0] ?? ''))) !== '') {
			return $id;
		}
		if (preg_match_all('/<[^>]+>/', (string) ($headers['references'][0] ?? ''), $m)) {
			return end($m[0]);
		}
		return '';
	}

	/**
	 * Flattens a MIME tree into its leaf parts, decoded.
	 * @param array $headers the headers of the entity.
	 * @param string $body the body of the entity.
	 * @param int $depth the nesting depth, limited to 10.
	 * @return array list of [content type, headers, decoded content].
	 */
	protected function collectParts(array $headers, string $body, int $depth = 0): array
	{
		$contentType = (string) ($headers['content-type'][0] ?? 'text/plain');
		$type = strtolower(trim(explode(';', $contentType)[0]));
		$boundary = static::getHeaderParameter($contentType, 'boundary');
		if (str_starts_with($type, 'multipart/') && $boundary !== null && $depth < 10) {
			$parts = [];
			foreach (static::splitMultipart($body, $boundary) as $part) {
				[$partHeaders, $partBody] = static::splitMessage($part);
				array_push($parts, ...$this->collectParts($partHeaders, $partBody, $depth + 1));
			}
			return $parts;
		}
		return [[$type, $headers, static::decodeContent($body, (string) ($headers['content-transfer-encoding'][0] ?? ''))]];
	}

	/**
	 * Splits a message into headers and body at the first blank line.
	 * @param string $message the message with "\n" line endings.
	 * @return array [headers, body]; headers are lowercased names to lists of values.
	 */
	public static function splitMessage(string $message): array
	{
		$message = ltrim($message, "\n");
		$pos = strpos($message, "\n\n");
		if ($pos === false) {
			return [static::parseHeaders($message), ''];
		}
		return [static::parseHeaders(substr($message, 0, $pos)), substr($message, $pos + 2)];
	}

	/**
	 * Parses a header block, joining folded lines.
	 * @param string $block the header lines.
	 * @return array lowercased header names to lists of values.
	 */
	public static function parseHeaders(string $block): array
	{
		$block = preg_replace('/\n[ \t]+/', ' ', str_replace(["\r\n", "\r"], "\n", $block));
		$headers = [];
		foreach (explode("\n", $block) as $line) {
			if (($colon = strpos($line, ':')) !== false && $colon > 0) {
				$headers[strtolower(trim(substr($line, 0, $colon)))][] = trim(substr($line, $colon + 1));
			}
		}
		return $headers;
	}

	/**
	 * Splits a multipart body into its parts.
	 * @param string $body the multipart body.
	 * @param string $boundary the boundary parameter.
	 * @return string[] the parts, headers and body each.
	 */
	public static function splitMultipart(string $body, string $boundary): array
	{
		$delimiter = '--' . $boundary;
		$parts = [];
		$sections = explode("\n" . $delimiter, "\n" . $body);
		array_shift($sections);
		foreach ($sections as $section) {
			if (str_starts_with($section, '--')) {
				break;
			}
			$parts[] = (string) substr($section, (int) strpos($section . "\n", "\n") + 1);
		}
		return $parts;
	}

	/**
	 * @param string $value the header value, e.g. 'multipart/report; boundary="b"'.
	 * @param string $name the parameter name.
	 * @return ?string the parameter value, null when absent.
	 */
	public static function getHeaderParameter(string $value, string $name): ?string
	{
		if (preg_match('/;\s*' . preg_quote($name, '/') . '\s*=\s*(?:"([^"]*)"|([^\s;]+))/i', $value, $m)) {
			return $m[1] !== '' ? $m[1] : ($m[2] ?? '');
		}
		return null;
	}

	/**
	 * @param string $content the encoded content.
	 * @param string $encoding the Content-Transfer-Encoding.
	 * @return string the decoded content.
	 */
	public static function decodeContent(string $content, string $encoding): string
	{
		return match (strtolower(trim($encoding))) {
			'base64' => (string) base64_decode(preg_replace('/\s+/', '', $content)),
			'quoted-printable' => quoted_printable_decode($content),
			default => $content,
		};
	}

	/**
	 * Removes the address or diagnostic type of a DSN field, e.g. "rfc822; a@b.c" becomes "a@b.c".
	 * @param string $value the field value.
	 * @return string the value without its type.
	 */
	public static function stripAddressType(string $value): string
	{
		$value = trim($value);
		if (preg_match('/^[\w-]+\s*;\s*(.*)$/s', $value, $m)) {
			$value = trim($m[1]);
		}
		return trim($value, '<> ');
	}
}

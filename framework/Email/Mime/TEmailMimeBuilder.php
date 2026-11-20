<?php

/**
 * TEmailMimeBuilder class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Mime;

use Prado\Email\TEmail;
use Prado\Email\TEmailAddress;
use Prado\Email\TEmailFile;

/**
 * TEmailMimeBuilder class.
 *
 * TEmailMimeBuilder writes a {@see TEmail} as an Internet message (RFC 5322) with
 * MIME content (RFC 2045, 2046) and encoded headers (RFC 2047, 2231).  Every
 * transport builds its message with it.
 *
 * | Email has | Content |
 * |---|---|
 * | Text | `text/plain` |
 * | Html | `text/html` |
 * | Text and Html | `multipart/alternative` of the two |
 * | Html and inline files | the `text/html` becomes `multipart/related` with the files |
 * | attachments | `multipart/mixed` of the content and the attachments |
 *
 * Text is quoted-printable and files are base64, in lines of at most 76
 * characters ending in CRLF.  Header values outside printable ASCII become base64
 * encoded-words, and headers fold at 78 characters.
 *
 * {@see build} returns the whole message.  A transport that passes the headers
 * apart from the body, such as PHP mail(), uses {@see getHeaders} and {@see getContent}.
 *
 * The Return-Path is the envelope sender of the transport and is not written as a
 * header.  The Bcc header is written by {@see getHeaders} and left out by {@see build}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailMimeBuilder extends \Prado\TComponent
{
	/** The line ending of a message. */
	public const EOL = "\r\n";

	/** The maximum length of an encoded content line. */
	public const LINE_LENGTH = 76;

	/** The length at which a header line folds. */
	public const HEADER_LENGTH = 78;

	/** The maximum length of an encoded-word (RFC 2047). */
	public const ENCODED_WORD_LENGTH = 75;

	/**
	 * Builds the whole message: the headers, an empty line, and the content.
	 * @param TEmail $email the email.
	 * @param string[] $without the names of the headers to leave out. Defaults to the Bcc.
	 * @return string the message, with CRLF line endings.
	 */
	public function build(TEmail $email, array $without = ['Bcc']): string
	{
		[$contentHeaders, $content] = $this->getContent($email);
		$headers = array_merge($this->getHeaders($email), $contentHeaders);
		foreach ($without as $name) {
			unset($headers[$name]);
		}
		return $this->formatHeaders($headers) . self::EOL . $content;
	}

	/**
	 * Returns the message headers other than the content headers, each value encoded
	 * and on one line.
	 * @param TEmail $email the email.
	 * @return array<string, string> the header values by name, in the order they are written.
	 */
	public function getHeaders(TEmail $email): array
	{
		$charset = $email->getCharset();
		$headers = ['Date' => date('D, d M Y H:i:s O', $email->getDate() ?? time())];
		$from = $email->getFrom();
		if ($from !== null) {
			$headers['From'] = $this->formatAddress($from, $charset);
		}
		$sender = $email->getSender();
		if ($sender !== null && ($from === null || $sender->getAddress() !== $from->getAddress())) {
			$headers['Sender'] = $this->formatAddress($sender, $charset);
		}
		foreach (['Reply-To' => $email->getReplyTo(), 'To' => $email->getTo(), 'Cc' => $email->getCc(), 'Bcc' => $email->getBcc()] as $name => $addresses) {
			if ($addresses) {
				$headers[$name] = $this->formatAddressList($addresses, $charset);
			}
		}
		$headers['Subject'] = $this->encodeText($email->getSubject(), $charset);
		if (($id = $email->getMessageId()) !== null) {
			$headers['Message-ID'] = '<' . $id . '>';
		}
		if (($priority = $email->getPriority()) !== null) {
			$headers['X-Priority'] = (string) $priority;
		}
		$headers['MIME-Version'] = '1.0';
		foreach ($email->getHeaders() as $name => $value) {
			$headers[$name] = $this->encodeText($value, $charset);
		}
		return $headers;
	}

	/**
	 * Builds the content of the message.
	 * @param TEmail $email the email.
	 * @return array{0: array<string, string>, 1: string} the content headers by name, and the encoded content.
	 */
	public function getContent(TEmail $email): array
	{
		$charset = $email->getCharset();
		$text = $email->getText();
		$html = $email->getHtml();
		$inline = $email->getInlineFiles();
		$attachments = $email->getAttachments();
		$parts = [];
		if ($text !== null || $html === null) {
			$parts[] = $this->textPart('text/plain', $text ?? '', $charset);
		}
		if ($html !== null) {
			$part = $this->textPart('text/html', $html, $charset);
			if ($inline) {
				$part = $this->multipart('related', array_merge([$part], array_map(fn (TEmailFile $file) => $this->filePart($file, $charset), $inline)));
			}
			$parts[] = $part;
		} else {
			$attachments = array_merge($inline, $attachments);
		}
		$content = count($parts) === 1 ? $parts[0] : $this->multipart('alternative', $parts);
		if ($attachments) {
			$content = $this->multipart('mixed', array_merge([$content], array_map(fn (TEmailFile $file) => $this->filePart($file, $charset), $attachments)));
		}
		return $content;
	}

	/**
	 * Builds a text part in quoted-printable.
	 * @param string $type the MIME type, "text/plain" or "text/html".
	 * @param string $text the text, with any line endings.
	 * @param string $charset the character set of the text.
	 * @return array{0: array<string, string>, 1: string} the part headers and the encoded content.
	 */
	protected function textPart(string $type, string $text, string $charset): array
	{
		$text = preg_replace('/\r\n|\r|\n/', self::EOL, $text);
		return [
			['Content-Type' => $type . '; charset=' . $charset, 'Content-Transfer-Encoding' => 'quoted-printable'],
			quoted_printable_encode($text),
		];
	}

	/**
	 * Builds the part of a file in base64.
	 * @param TEmailFile $file the attachment or inline file.
	 * @param string $charset the character set of the file name.
	 * @return array{0: array<string, string>, 1: string} the part headers and the encoded content.
	 */
	protected function filePart(TEmailFile $file, string $charset): array
	{
		$name = $file->getName();
		$ascii = !preg_match('/[^\x20-\x7E]/', $name);
		$headers = [
			'Content-Type' => $file->getMimeType() . '; name="' . ($ascii ? $name : $this->encodeText($name, $charset)) . '"',
			'Content-Transfer-Encoding' => 'base64',
			'Content-Disposition' => ($file->getIsInline() ? 'inline' : 'attachment') . '; '
				. ($ascii ? 'filename="' . $name . '"' : "filename*=" . $charset . "''" . rawurlencode($name)),
		];
		if ($file->getIsInline()) {
			$headers['Content-ID'] = '<' . $file->getContentId() . '>';
		}
		return [$headers, rtrim(chunk_split(base64_encode($file->getContent()), self::LINE_LENGTH, self::EOL), self::EOL)];
	}

	/**
	 * Joins parts into a multipart with a new boundary.
	 * @param string $subtype the multipart subtype: "alternative", "related", or "mixed".
	 * @param array<array{0: array<string, string>, 1: string}> $parts the parts.
	 * @return array{0: array<string, string>, 1: string} the multipart headers and content.
	 */
	protected function multipart(string $subtype, array $parts): array
	{
		$boundary = $this->createBoundary();
		$content = '';
		foreach ($parts as [$headers, $body]) {
			$content .= '--' . $boundary . self::EOL . $this->formatHeaders($headers) . self::EOL . $body . self::EOL;
		}
		$content .= '--' . $boundary . '--' . self::EOL;
		return [['Content-Type' => 'multipart/' . $subtype . '; boundary="' . $boundary . '"'], $content];
	}

	/**
	 * @return string a boundary of 96 random bits that base64 and quoted-printable content cannot hold.
	 */
	protected function createBoundary(): string
	{
		return '=_' . bin2hex(random_bytes(12));
	}

	/**
	 * Writes headers as folded lines.
	 * @param array<string, string> $headers the header values by name.
	 * @return string the header lines, each ending in CRLF.
	 */
	public function formatHeaders(array $headers): string
	{
		$lines = '';
		foreach ($headers as $name => $value) {
			$lines .= $this->foldHeader($name, $value) . self::EOL;
		}
		return $lines;
	}

	/**
	 * Folds a header at the spaces of its value so its lines stay within {@see HEADER_LENGTH}.
	 * A word longer than a line is not broken.  The first word moves to its own line
	 * when it fits there and does not fit beside the name.
	 * @param string $name the header name.
	 * @param string $value the header value on one line.
	 * @return string "Name: value", with CRLF and a space at each fold.
	 */
	public function foldHeader(string $name, string $value): string
	{
		$folded = '';
		$line = $name . ':';
		foreach (explode(' ', $value) as $index => $word) {
			$fits = strlen($line) + 1 + strlen($word) <= self::HEADER_LENGTH;
			if (!$fits && ($index > 0 || strlen($word) < self::HEADER_LENGTH)) {
				$folded .= $line . self::EOL;
				$line = '';
			}
			$line .= ' ' . $word;
		}
		return $folded . $line;
	}

	/**
	 * Encodes header text.  Printable ASCII is returned as is; other text becomes
	 * base64 encoded-words separated by spaces.  A UTF-8 character is never split
	 * between two words.
	 * @param string $text the text.
	 * @param string $charset the character set of the text.
	 * @return string the encoded text.
	 */
	public function encodeText(string $text, string $charset = 'UTF-8'): string
	{
		if (!preg_match('/[^\x20-\x7E]/', $text)) {
			return $text;
		}
		$bytes = intdiv(self::ENCODED_WORD_LENGTH - 7 - strlen($charset), 4) * 3;
		$words = [];
		foreach ($this->splitText($text, max(3, $bytes), $charset) as $chunk) {
			$words[] = '=?' . $charset . '?B?' . base64_encode($chunk) . '?=';
		}
		return implode(' ', $words);
	}

	/**
	 * Splits text into chunks of a maximum byte length, on character boundaries for UTF-8.
	 * @param string $text the text.
	 * @param int $bytes the maximum bytes of a chunk.
	 * @param string $charset the character set of the text.
	 * @return string[] the chunks.
	 */
	protected function splitText(string $text, int $bytes, string $charset): array
	{
		$characters = strcasecmp($charset, 'UTF-8') === 0 ? preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) : false;
		if ($characters === false) {
			return str_split($text, $bytes);
		}
		$chunks = [];
		$chunk = '';
		foreach ($characters as $character) {
			if ($chunk !== '' && strlen($chunk) + strlen($character) > $bytes) {
				$chunks[] = $chunk;
				$chunk = '';
			}
			$chunk .= $character;
		}
		$chunks[] = $chunk;
		return $chunks;
	}

	/**
	 * Formats a mailbox for a header.
	 * @param TEmailAddress $address the address.
	 * @param string $charset the character set of the display name.
	 * @return string "address", or "name <address>" with the name quoted or encoded as it needs.
	 */
	public function formatAddress(TEmailAddress $address, string $charset = 'UTF-8'): string
	{
		$name = $address->getName();
		if ($name === '') {
			return $address->getAddress();
		}
		if (preg_match('/[^\x20-\x7E]/', $name)) {
			$name = $this->encodeText($name, $charset);
		} elseif (!preg_match('/^[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ \-]+$/', $name)) {
			$name = '"' . addcslashes($name, '"\\') . '"';
		}
		return $name . ' <' . $address->getAddress() . '>';
	}

	/**
	 * Formats mailboxes for a header.
	 * @param TEmailAddress[] $addresses the addresses.
	 * @param string $charset the character set of the display names.
	 * @return string the mailboxes separated by ", ".
	 */
	public function formatAddressList(array $addresses, string $charset = 'UTF-8'): string
	{
		return implode(', ', array_map(fn (TEmailAddress $address) => $this->formatAddress($address, $charset), $addresses));
	}
}

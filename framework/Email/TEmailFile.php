<?php

/**
 * TEmailFile class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TIOException;
use Prado\Web\TMediaType;

/**
 * TEmailFile class.
 *
 * TEmailFile is an immutable file of an email: an attachment, or an inline file
 * that the HTML references as "cid:<ContentId>".  The content is a file path read
 * when the message is built, or data held in memory.
 *
 * <code>
 * $email->addFile(TEmailFile::fromPath('/reports/may.pdf'));
 * $email->addFile(TEmailFile::fromData($csv, 'export.csv', 'text/csv'));
 * $email->addFile(TEmailFile::fromPath('/images/logo.png')->withContentId('logo'));
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailFile
{
	/** The MIME type of a file whose type is unknown. */
	public const DEFAULT_MIME_TYPE = 'application/octet-stream';

	/**
	 * @param ?string $_path the path of the file, null when the content is data.
	 * @param ?string $_data the content, null when the content is a file.
	 * @param string $_name the file name shown to the recipient.
	 * @param ?string $_mimeType the MIME type, null to derive it from the name.
	 * @param ?string $_contentId the content ID of an inline file, null for an attachment.
	 * @throws TInvalidDataValueException when the name, MIME type, or content ID is invalid, or there is no content.
	 */
	final public function __construct(
		private ?string $_path,
		private ?string $_data,
		private string $_name,
		private ?string $_mimeType = null,
		private ?string $_contentId = null
	) {
		if (($_path === null) === ($_data === null)) {
			throw new TInvalidDataValueException('emailfile_content_invalid', $_name);
		}
		if ($_name === '' || preg_match('/[\x00-\x1F\x7F\/\\\\"]/', $_name)) {
			throw new TInvalidDataValueException('emailfile_name_invalid', $_name);
		}
		if ($_mimeType !== null && !preg_match('/^[\w.+-]+\/[\w.+-]+$/', $_mimeType)) {
			throw new TInvalidDataValueException('emailfile_mimetype_invalid', $_mimeType);
		}
		if ($_contentId !== null && !preg_match('/^[\w.+-]+(@[\w.-]+)?$/', $_contentId)) {
			throw new TInvalidDataValueException('emailfile_contentid_invalid', $_contentId);
		}
	}

	/**
	 * Creates a file whose content is read from a path when the message is built.
	 * @param string $path the path of the file.
	 * @param ?string $name the file name shown to the recipient, null for the base name of the path.
	 * @param ?string $mimeType the MIME type, null to derive it from the name.
	 * @return static the file.
	 */
	public static function fromPath(string $path, ?string $name = null, ?string $mimeType = null): static
	{
		return new static($path, null, $name ?? basename($path), $mimeType);
	}

	/**
	 * Creates a file from data held in memory.
	 * @param string $data the content.
	 * @param string $name the file name shown to the recipient.
	 * @param ?string $mimeType the MIME type, null to derive it from the name.
	 * @return static the file.
	 */
	public static function fromData(string $data, string $name, ?string $mimeType = null): static
	{
		return new static(null, $data, $name, $mimeType);
	}

	/**
	 * Restores a file from the array of {@see toArray}.
	 * @param array $array the stored file.
	 * @return static the file.
	 */
	public static function fromArray(array $array): static
	{
		$data = isset($array['data']) ? base64_decode((string) $array['data'], true) : null;
		return new static($array['path'] ?? null, $data === false ? '' : $data, (string) ($array['name'] ?? ''), $array['mime'] ?? null, $array['cid'] ?? null);
	}

	/**
	 * Converts the file to a JSON-safe array.  A path is kept as a path; data is base64 encoded.
	 * @return array the keys "name", "path" or "data", "mime", and "cid".
	 */
	public function toArray(): array
	{
		return array_filter([
			'name' => $this->_name,
			'path' => $this->_path,
			'data' => $this->_data === null ? null : base64_encode($this->_data),
			'mime' => $this->_mimeType,
			'cid' => $this->_contentId,
		], fn ($value) => $value !== null);
	}

	/**
	 * Returns a copy that is an inline file with a content ID.
	 * @param ?string $contentId the content ID, null for an attachment.
	 * @return static the copy.
	 */
	public function withContentId(?string $contentId): static
	{
		return new static($this->_path, $this->_data, $this->_name, $this->_mimeType, $contentId);
	}

	/**
	 * @return ?string the path of the file, null when the content is data.
	 */
	public function getPath(): ?string
	{
		return $this->_path;
	}

	/**
	 * @return string the file name shown to the recipient.
	 */
	public function getName(): string
	{
		return $this->_name;
	}

	/**
	 * @return string the MIME type: the set type, the type of the name's extension, or {@see DEFAULT_MIME_TYPE}.
	 */
	public function getMimeType(): string
	{
		return $this->_mimeType ?? TMediaType::mimeTypeFromFilename($this->_name) ?? self::DEFAULT_MIME_TYPE;
	}

	/**
	 * @return ?string the content ID of an inline file, null for an attachment.
	 */
	public function getContentId(): ?string
	{
		return $this->_contentId;
	}

	/**
	 * @return bool whether the file is inline, referenced by the HTML as "cid:<ContentId>".
	 */
	public function getIsInline(): bool
	{
		return $this->_contentId !== null;
	}

	/**
	 * Returns the content, reading the file when the content is a path.
	 * @throws TIOException when the file cannot be read.
	 * @return string the content.
	 */
	public function getContent(): string
	{
		if ($this->_data !== null) {
			return $this->_data;
		}
		$content = is_file($this->_path) ? @file_get_contents($this->_path) : false;
		if ($content === false) {
			throw new TIOException('emailfile_unreadable', $this->_path);
		}
		return $content;
	}
}

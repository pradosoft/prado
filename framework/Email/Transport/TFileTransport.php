<?php

/**
 * TFileTransport class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Transport;

use Prado\Email\TEmail;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TFileTransport class.
 *
 * TFileTransport writes each email as an ".eml" file instead of delivering it.
 * It is the transport for development and tests: a mail client opens the file as
 * the recipient would see the email.
 *
 * <code>
 * <transport class="Prado\Email\Transport\TFileTransport" Path="Application.runtime.emails" />
 * </code>
 *
 * The file is named by the time and the tracking ID of the email.  It holds the
 * whole message, the Bcc header included.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFileTransport extends TEmailTransport
{
	/** The directory under the runtime path that holds the files by default. */
	public const DEFAULT_DIRECTORY = 'emails';

	/** @var ?string the directory that receives the files. */
	private ?string $_path = null;

	/** @var ?string the path of the last file written. */
	private ?string $_lastFile = null;

	/**
	 * Writes an email as an ".eml" file.
	 * @param TEmail $email the final email.
	 * @throws TEmailTransportException when the email has no sender or recipient, or the file cannot be written.
	 */
	public function send(TEmail $email): void
	{
		$this->getEnvelopeSender($email);
		$this->getEnvelopeRecipients($email);
		$directory = $this->getPath();
		if (!is_dir($directory) && !@mkdir($directory, Prado::getDefaultDirPermissions(), true) && !is_dir($directory)) {
			throw new TEmailTransportException('filetransport_path_unwritable', $directory);
		}
		$file = $directory . DIRECTORY_SEPARATOR . gmdate('Ymd-His') . '-' . $email->getTrackingId() . '.eml';
		if (@file_put_contents($file, $this->getMimeBuilder()->build($email, []), LOCK_EX) === false) {
			throw new TEmailTransportException('filetransport_path_unwritable', $directory);
		}
		$this->_lastFile = $file;
	}

	/**
	 * @return ?string the path of the last file written, null when none was.
	 */
	public function getLastFile(): ?string
	{
		return $this->_lastFile;
	}

	/**
	 * @throws TConfigurationException when no path is set and there is no application.
	 * @return string the directory that receives the files. Defaults to "emails" in the application runtime path.
	 */
	public function getPath(): string
	{
		if ($this->_path === null) {
			if (($app = $this->getApplication()) === null) {
				throw new TConfigurationException('filetransport_path_required');
			}
			$this->_path = $app->getRuntimePath() . DIRECTORY_SEPARATOR . self::DEFAULT_DIRECTORY;
		}
		return $this->_path;
	}

	/**
	 * @param string $value the directory that receives the files: a path, or a namespace such as "Application.runtime.emails".
	 * @throws TConfigurationException when a namespace does not resolve.
	 */
	public function setPath($value)
	{
		$value = TPropertyValue::ensureString($value);
		if (!str_contains($value, '/') && !str_contains($value, '\\')) {
			if (($path = Prado::getPathOfNamespace($value)) === null) {
				throw new TConfigurationException('filetransport_path_invalid', $value);
			}
			$value = $path;
		}
		$this->_path = rtrim($value, '/\\');
	}
}

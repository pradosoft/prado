<?php

class HttpFileDownloadTest extends \Prado\Web\UI\TPage
{
	private const CONTENT = "Hello from PRADO writeFile!\nThis is line 2 of the test file.";
	private const FILENAME = 'prado-test.txt';

	public function onLoad($param)
	{
		parent::onLoad($param);

		$action = $this->Request->itemAt('action') ?? '';

		switch ($action) {
			case 'download-text':
				// Force-download: Content-Disposition: attachment
				// Pass null for $headers so writeFile() sets Content-Type, Pragma, etc.
				// itself and marks _contentTypeHeaderSent=true (prevents PRADO from
				// overriding Content-Type to text/html when the response is flushed).
				$this->Response->writeFile(
					self::FILENAME,
					self::CONTENT,
					'text/plain',
					null,
					true,
					self::FILENAME,
					strlen(self::CONTENT)
				);
				break;

			case 'download-inline':
				// Inline display: Content-Disposition: inline
				$this->Response->writeFile(
					self::FILENAME,
					self::CONTENT,
					'text/plain',
					null,
					false,
					self::FILENAME,
					strlen(self::CONTENT)
				);
				break;
		}
	}
}

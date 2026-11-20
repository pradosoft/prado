<?php

namespace Prado\Test\Unit\Email\Mime;

use PHPUnit\Framework\TestCase;
use Prado\Email\Mime\TEmailMimeBuilder;
use Prado\Email\TEmail;
use Prado\Email\TEmailAddress;
use Prado\Email\TEmailFile;

class TEmailMimeBuilderTest extends TestCase
{
	private function email(): TEmail
	{
		$email = new TEmail();
		$email->setFrom('Shop <shop@example.com>');
		$email->setTo('ann@example.com');
		$email->setSubject('Hello');
		$email->setDate(1700000000);
		$email->setMessageId('abc@example.com');
		return $email;
	}

	/**
	 * Splits a message or a part into its unfolded headers, by lower-case name, and its body.
	 */
	private function parse(string $message): array
	{
		$this->assertStringNotContainsString("\n", str_replace("\r\n", '', $message), 'every line ends in CRLF');
		[$head, $body] = explode("\r\n\r\n", $message, 2) + [1 => ''];
		$headers = [];
		foreach (explode("\r\n", preg_replace('/\r\n[ \t]+/', ' ', $head)) as $line) {
			[$name, $value] = explode(':', $line, 2);
			$headers[strtolower($name)] = trim($value);
		}
		return [$headers, $body];
	}

	/**
	 * Splits a multipart body into its parts, each parsed into headers and body.
	 */
	private function parts(array $headers, string $body): array
	{
		$this->assertSame(1, preg_match('/boundary="([^"]+)"/', $headers['content-type'], $match));
		$sections = explode('--' . $match[1], $body);
		$this->assertSame("--\r\n", array_pop($sections));
		array_shift($sections);
		return array_map(fn ($section) => $this->parse(substr($section, 2, -2)), $sections);
	}

	private function decodeWords(string $value): string
	{
		return preg_replace_callback('/=\?UTF-8\?B\?([^?]+)\?=( (?==\?))?/', fn ($m) => base64_decode($m[1]), $value);
	}

	public function testTextOnly()
	{
		$email = $this->email();
		$email->setText("Line one\nLine two");
		[$headers, $body] = $this->parse((new TEmailMimeBuilder())->build($email));
		$this->assertSame('Tue, 14 Nov 2023 22:13:20 +0000', gmdate('D, d M Y H:i:s O', strtotime($headers['date'])));
		$this->assertSame('Shop <shop@example.com>', $headers['from']);
		$this->assertSame('ann@example.com', $headers['to']);
		$this->assertSame('Hello', $headers['subject']);
		$this->assertSame('<abc@example.com>', $headers['message-id']);
		$this->assertSame('1.0', $headers['mime-version']);
		$this->assertSame('text/plain; charset=UTF-8', $headers['content-type']);
		$this->assertSame('quoted-printable', $headers['content-transfer-encoding']);
		$this->assertSame("Line one\r\nLine two", quoted_printable_decode($body));
		$this->assertArrayNotHasKey('sender', $headers);
		$this->assertArrayNotHasKey('x-priority', $headers);
	}

	public function testEmptyEmailHasAnEmptyTextPart()
	{
		[$headers, $body] = $this->parse((new TEmailMimeBuilder())->build(new TEmail()));
		$this->assertSame('text/plain; charset=UTF-8', $headers['content-type']);
		$this->assertSame('', $body);
		$this->assertSame('', $headers['subject']);
		$this->assertArrayNotHasKey('from', $headers);
		$this->assertArrayNotHasKey('to', $headers);
		$this->assertArrayNotHasKey('message-id', $headers);
	}

	public function testHtmlOnly()
	{
		$email = $this->email();
		$email->setHtml('<p>Hi</p>');
		[$headers, $body] = $this->parse((new TEmailMimeBuilder())->build($email));
		$this->assertSame('text/html; charset=UTF-8', $headers['content-type']);
		$this->assertSame('<p>Hi</p>', quoted_printable_decode($body));
	}

	public function testAlternative()
	{
		$email = $this->email();
		$email->setText('Hi');
		$email->setHtml('<p>Hi</p>');
		[$headers, $body] = $this->parse((new TEmailMimeBuilder())->build($email));
		$this->assertStringStartsWith('multipart/alternative; boundary="', $headers['content-type']);
		$this->assertArrayNotHasKey('content-transfer-encoding', $headers);
		$parts = $this->parts($headers, $body);
		$this->assertCount(2, $parts);
		$this->assertSame('text/plain; charset=UTF-8', $parts[0][0]['content-type']);
		$this->assertSame('Hi', quoted_printable_decode($parts[0][1]));
		$this->assertSame('text/html; charset=UTF-8', $parts[1][0]['content-type']);
		$this->assertSame('<p>Hi</p>', quoted_printable_decode($parts[1][1]));
	}

	public function testFullStructure()
	{
		$email = $this->email();
		$email->setText('Hi');
		$email->setHtml('<p>Hi <img src="cid:logo"></p>');
		$email->addFile(TEmailFile::fromData("\x89PNG\x00\xFF", 'logo.png')->withContentId('logo'));
		$binary = random_bytes(200);
		$email->attachData($binary, 'report.pdf');
		[$headers, $body] = $this->parse((new TEmailMimeBuilder())->build($email));
		$this->assertStringStartsWith('multipart/mixed; boundary="', $headers['content-type']);
		$mixed = $this->parts($headers, $body);
		$this->assertCount(2, $mixed);

		$this->assertStringStartsWith('multipart/alternative; boundary="', $mixed[0][0]['content-type']);
		$alternative = $this->parts(...$mixed[0]);
		$this->assertCount(2, $alternative);
		$this->assertSame('text/plain; charset=UTF-8', $alternative[0][0]['content-type']);
		$this->assertStringStartsWith('multipart/related; boundary="', $alternative[1][0]['content-type']);
		$related = $this->parts(...$alternative[1]);
		$this->assertCount(2, $related);
		$this->assertSame('text/html; charset=UTF-8', $related[0][0]['content-type']);
		$this->assertSame('image/png; name="logo.png"', $related[1][0]['content-type']);
		$this->assertSame('inline; filename="logo.png"', $related[1][0]['content-disposition']);
		$this->assertSame('<logo>', $related[1][0]['content-id']);
		$this->assertSame("\x89PNG\x00\xFF", base64_decode($related[1][1]));

		$this->assertSame('application/pdf; name="report.pdf"', $mixed[1][0]['content-type']);
		$this->assertSame('base64', $mixed[1][0]['content-transfer-encoding']);
		$this->assertSame('attachment; filename="report.pdf"', $mixed[1][0]['content-disposition']);
		$this->assertArrayNotHasKey('content-id', $mixed[1][0]);
		$this->assertSame($binary, base64_decode($mixed[1][1]));
		foreach (explode("\r\n", $mixed[1][1]) as $line) {
			$this->assertLessThanOrEqual(76, strlen($line));
		}
	}

	public function testBoundariesAreUniqueAndRandom()
	{
		$email = $this->email();
		$email->setText('Hi');
		$email->setHtml('<p>Hi</p>');
		$email->attachData('x', 'a.txt');
		$builder = new TEmailMimeBuilder();
		preg_match_all('/boundary="([^"]+)"/', $builder->build($email) . $builder->build($email), $matches);
		$this->assertCount(4, $matches[1]);
		$this->assertCount(4, array_unique($matches[1]));
	}

	public function testInlineFileWithoutHtmlIsAttachedToTheMessage()
	{
		$email = $this->email();
		$email->setText('Hi');
		$email->addFile(TEmailFile::fromData('x', 'logo.png')->withContentId('logo'));
		[$headers, $body] = $this->parse((new TEmailMimeBuilder())->build($email));
		$this->assertStringStartsWith('multipart/mixed', $headers['content-type']);
		$parts = $this->parts($headers, $body);
		$this->assertSame('<logo>', $parts[1][0]['content-id']);
	}

	public function testQuotedPrintableContent()
	{
		$text = "Zoë says: 1 + 1 = 2\r\ntrailing space \nmixed\rendings\n" . str_repeat('long line ', 30) . "\n.\nFrom here";
		$email = $this->email();
		$email->setText($text);
		[, $body] = $this->parse((new TEmailMimeBuilder())->build($email));
		$this->assertDoesNotMatchRegularExpression('/[^\x20-\x7E\r\n]/', $body);
		foreach (explode("\r\n", $body) as $line) {
			$this->assertLessThanOrEqual(76, strlen($line));
			$this->assertDoesNotMatchRegularExpression('/[ \t]$/', $line);
		}
		$this->assertSame(preg_replace('/\r\n|\r|\n/', "\r\n", $text), quoted_printable_decode($body));
	}

	public function testAddressHeaders()
	{
		$email = $this->email();
		$email->setFrom(new TEmailAddress('shop@example.com', 'Shop, Inc.'));
		$email->setSender('Bot <bot@example.com>');
		$email->setReplyTo('help@example.com, "Zoë Müller" <zoe@example.com>');
		$email->setTo('"Ann \"Boss\" Lee" <ann@example.com>, bob@example.com');
		$email->setCc('Cat <cat@example.com>');
		$email->setBcc('eve@example.com');
		$email->setReturnPath('bounce@example.com');
		$email->setPriority(1);
		$builder = new TEmailMimeBuilder();
		$headers = $builder->getHeaders($email);
		$this->assertSame(['Date', 'From', 'Sender', 'Reply-To', 'To', 'Cc', 'Bcc', 'Subject', 'Message-ID', 'X-Priority', 'MIME-Version'], array_keys($headers));
		$this->assertSame('"Shop, Inc." <shop@example.com>', $headers['From']);
		$this->assertSame('Bot <bot@example.com>', $headers['Sender']);
		$this->assertSame('help@example.com, =?UTF-8?B?' . base64_encode('Zoë Müller') . '?= <zoe@example.com>', $headers['Reply-To']);
		$this->assertSame('"Ann \"Boss\" Lee" <ann@example.com>, bob@example.com', $headers['To']);
		$this->assertSame('eve@example.com', $headers['Bcc']);
		$this->assertSame('1', $headers['X-Priority']);

		$message = $builder->build($email);
		$this->assertStringNotContainsString('eve@example.com', $message);
		$this->assertStringNotContainsString('bounce@example.com', $message);
		$this->assertStringContainsString("Bcc: eve@example.com\r\n", $builder->build($email, []));
	}

	public function testSenderEqualToFromIsOmitted()
	{
		$email = $this->email();
		$email->setSender('shop@example.com');
		$this->assertArrayNotHasKey('Sender', (new TEmailMimeBuilder())->getHeaders($email));
	}

	public function testEncodedSubject()
	{
		$subject = 'Größe für Zoë: ' . str_repeat('日本語のテキスト ', 8) . '✓';
		$email = $this->email();
		$email->setSubject($subject);
		$builder = new TEmailMimeBuilder();
		$encoded = $builder->getHeaders($email)['Subject'];
		$this->assertDoesNotMatchRegularExpression('/[^\x20-\x7E]/', $encoded);
		foreach (explode(' ', $encoded) as $word) {
			$this->assertLessThanOrEqual(75, strlen($word));
			$this->assertSame(1, preg_match('/^=\?UTF-8\?B\?([A-Za-z0-9+\/=]+)\?=$/', $word, $match));
			$this->assertSame(1, preg_match('//u', base64_decode($match[1])), 'a word holds whole characters');
		}
		$this->assertSame($subject, $this->decodeWords($encoded));
		[$headers] = $this->parse($builder->build($email));
		$this->assertSame($subject, $this->decodeWords($headers['subject']));
	}

	public function testEncodeTextCharsets()
	{
		$builder = new TEmailMimeBuilder();
		$this->assertSame('Plain text', $builder->encodeText('Plain text'));
		$this->assertSame('=?ISO-8859-1?B?' . base64_encode("Gr\xF6\xDFe") . '?=', $builder->encodeText("Gr\xF6\xDFe", 'ISO-8859-1'));
		$this->assertSame('=?UTF-8?B?' . base64_encode("tab\there") . '?=', $builder->encodeText("tab\there"));
	}

	public function testHeadersFold()
	{
		$email = $this->email();
		$email->setTo(implode(', ', array_map(fn ($i) => "Recipient Number {$i} <recipient{$i}@example.com>", range(1, 12))));
		$email->setSubject(str_repeat('word ', 40));
		$message = (new TEmailMimeBuilder())->build($email);
		[$head] = explode("\r\n\r\n", $message, 2);
		foreach (explode("\r\n", $head) as $line) {
			$this->assertLessThanOrEqual(78, strlen($line));
		}
		[$headers] = $this->parse($message);
		$this->assertSame(12, substr_count($headers['to'], '@'));
		$this->assertSame(rtrim(str_repeat('word ', 40)), $headers['subject']);
	}

	public function testFoldHeaderKeepsALongWordWhole()
	{
		$builder = new TEmailMimeBuilder();
		$long = str_repeat('x', 100);
		$this->assertSame("X-Long: {$long}", $builder->foldHeader('X-Long', $long));
		$this->assertSame("X-Long: a\r\n {$long}\r\n b", $builder->foldHeader('X-Long', "a {$long} b"));
		$this->assertSame('X-Empty: ', $builder->foldHeader('X-Empty', ''));
		$word = str_repeat('y', 72);
		$this->assertSame("Subject:\r\n {$word} z", $builder->foldHeader('Subject', "{$word} z"));
	}

	public function testCustomHeaders()
	{
		$email = $this->email();
		$email->setHeader('X-Campaign', 'Frühling');
		$email->setListUnsubscribe('https://example.com/u?id=1');
		$headers = (new TEmailMimeBuilder())->getHeaders($email);
		$this->assertSame('=?UTF-8?B?' . base64_encode('Frühling') . '?=', $headers['X-Campaign']);
		$this->assertSame('<https://example.com/u?id=1>', $headers['List-Unsubscribe']);
		$this->assertSame('List-Unsubscribe=One-Click', $headers['List-Unsubscribe-Post']);
	}

	public function testNonAsciiFileName()
	{
		$email = $this->email();
		$email->setText('Hi');
		$email->attachData('x', 'Größe.txt');
		[$headers, $body] = $this->parse((new TEmailMimeBuilder())->build($email));
		$file = $this->parts($headers, $body)[1][0];
		$this->assertSame('text/plain; name="=?UTF-8?B?' . base64_encode('Größe.txt') . '?="', $file['content-type']);
		$this->assertSame("attachment; filename*=UTF-8''" . rawurlencode('Größe.txt'), $file['content-disposition']);
	}

	public function testDateDefaultsToNow()
	{
		$headers = (new TEmailMimeBuilder())->getHeaders(new TEmail());
		$this->assertEqualsWithDelta(time(), strtotime($headers['Date']), 5);
	}
}

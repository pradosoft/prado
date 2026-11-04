<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\HttpClient\THttpClientResponse;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TPasswordStrength;
use Prado\Web\UI\WebControls\TPasswordStrengthValidator;
use Prado\Web\UI\WebControls\TTextBox;

/**
 * Records the requests of the compromised password check and answers them
 * with a fixed response or a transport failure.
 */
class TPasswordStrengthValidatorTestHttpClient extends THttpClient
{
	public array $requests = [];

	public function __construct(private ?THttpClientResponse $response = null)
	{
		parent::__construct();
	}

	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		$this->requests[] = [$method, $url, $headers];
		if ($this->response === null) {
			throw new THttpClientException('httpclient_transport_error', 7, 'Connection refused');
		}
		return $this->response;
	}
}

class TPasswordStrengthValidatorTest extends TestCase
{
	/** A Medium password; its SHA-1 starts 32CA9, the prefix sent to the range API. */
	private const PASSWORD = 'Password1!';

	private TTextBox $input;
	private TPasswordStrengthValidator $validator;

	protected function setUp(): void
	{
		$page = new TPage();
		$this->input = new TTextBox();
		$this->input->setID('input');
		$this->validator = new TPasswordStrengthValidator();
		$this->validator->setControlToValidate('input');
		$page->getControls()->add($this->input);
		$page->getControls()->add($this->validator);
	}

	private function valid(string $value): bool
	{
		$this->input->setText($value);
		return PradoUnit::invoke($this->validator, 'evaluateIsValid');
	}

	/**
	 * The cases shared with the client validator test, tests/js/validator/passwordstrengthvalidator.test.js.
	 */
	public static function caseProvider(): array
	{
		$cases = json_decode(file_get_contents(__DIR__ . '/../../../../js/validator/password-cases.json'), true);
		$data = [];
		foreach ($cases['cases'] as $i => $case) {
			$data["#$i " . json_encode($case[0], JSON_UNESCAPED_UNICODE)] = $case;
		}
		return $data;
	}

	/**
	 * @dataProvider caseProvider
	 */
	public function testSharedCases(string $password, float|int $entropy, string $strength)
	{
		$this->assertEqualsWithDelta($entropy, TPasswordStrengthValidator::estimateEntropy($password), 0.0001);
		$this->assertSame($strength, TPasswordStrengthValidator::estimateStrength($password));
	}

	public function testEntropyByHand()
	{
		// 7 distinct lower case letters of 26, and one repeated "s" among 7 distinct.
		$this->assertEqualsWithDelta(7 * log(26, 2) + log(7, 2), TPasswordStrengthValidator::estimateEntropy('password'), 1e-9);
		// Repeating one character adds no entropy.
		$this->assertEqualsWithDelta(log(10, 2), TPasswordStrengthValidator::estimateEntropy('0000'), 1e-9);
		// Each non-ASCII code point is one character of the 100 class.
		$this->assertEqualsWithDelta(2 * log(100, 2) + 4, TPasswordStrengthValidator::estimateEntropy('密码密码密码'), 1e-9);
	}

	public function testInvalidUtf8CountsBytes()
	{
		$this->assertEqualsWithDelta(2 * log(100, 2), TPasswordStrengthValidator::estimateEntropy("\xff\xfe"), 1e-9);
	}

	public function testExtendsBaseValidator()
	{
		$this->assertInstanceOf(TBaseValidator::class, $this->validator);
		$this->assertSame('Prado.WebUI.TPasswordStrengthValidator', PradoUnit::invoke($this->validator, 'getClientClassName'));
	}

	public function testDefaults()
	{
		$this->assertSame(TPasswordStrength::Medium, $this->validator->getMinStrength());
		$this->assertFalse($this->validator->getCheckCompromised());
		$this->assertSame(1, $this->validator->getCompromisedThreshold());
		$this->assertSame(TPasswordStrengthValidator::PWNED_PASSWORDS_URL, $this->validator->getCompromisedApiUrl());
		$this->assertSame(5, $this->validator->getCompromisedTimeout());
	}

	public function testMinStrength()
	{
		$this->assertTrue($this->valid(''));
		$this->assertFalse($this->valid('Password1'));
		$this->assertTrue($this->valid('Password1!'));

		$this->validator->setMinStrength('weak');
		$this->assertSame(TPasswordStrength::Weak, $this->validator->getMinStrength());
		$this->assertTrue($this->valid('Password1'));
		$this->assertFalse($this->valid('password'));

		$this->validator->setMinStrength(TPasswordStrength::VeryStrong);
		$this->assertFalse($this->valid('kX9#mQ2$vL7!pZ'));
		$this->assertTrue($this->valid('correct horse battery staple'));

		$this->validator->setMinStrength(TPasswordStrength::VeryWeak);
		$this->assertTrue($this->valid('a'));
	}

	public function testMinStrengthRejectsUnknownLevel()
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->validator->setMinStrength('Excellent');
	}

	public function testCompromisedThresholdIsAtLeastOne()
	{
		$this->validator->setCompromisedThreshold(0);
		$this->assertSame(1, $this->validator->getCompromisedThreshold());
		$this->validator->setCompromisedThreshold('10');
		$this->assertSame(10, $this->validator->getCompromisedThreshold());
	}

	public function testCompromisedSendsOnlyTheHashPrefix()
	{
		$hash = strtoupper(sha1(self::PASSWORD));
		$client = new TPasswordStrengthValidatorTestHttpClient(new THttpClientResponse(200, [], "0000000000000000000000000000000000A:0\r\n" . substr($hash, 5) . ":42\r\n"));
		$this->validator->setHttpClient($client);
		$this->validator->setCheckCompromised(true);

		$this->assertFalse($this->valid(self::PASSWORD));
		$this->assertCount(1, $client->requests);
		[$method, $url, $headers] = $client->requests[0];
		$this->assertSame('GET', $method);
		$this->assertSame(TPasswordStrengthValidator::PWNED_PASSWORDS_URL . substr($hash, 0, 5), $url);
		$this->assertSame('true', $headers['Add-Padding']);
		$this->assertStringStartsWith('PRADO/', $headers['User-Agent']);
		$this->assertStringNotContainsString(substr($hash, 5), $url);
	}

	public function testCompromisedThreshold()
	{
		$hash = strtoupper(sha1(self::PASSWORD));
		$this->validator->setHttpClient(new TPasswordStrengthValidatorTestHttpClient(new THttpClientResponse(200, [], strtolower(substr($hash, 5)) . ":3\n")));
		$this->validator->setCheckCompromised(true);
		$this->assertFalse($this->valid(self::PASSWORD));
		$this->validator->setCompromisedThreshold(4);
		$this->assertTrue($this->valid(self::PASSWORD));
	}

	public function testPaddingEntryDoesNotFail()
	{
		$hash = strtoupper(sha1(self::PASSWORD));
		$this->validator->setHttpClient(new TPasswordStrengthValidatorTestHttpClient(new THttpClientResponse(200, [], substr($hash, 5) . ":0\n")));
		$this->validator->setCheckCompromised(true);
		$this->assertTrue($this->valid(self::PASSWORD));
	}

	public function testNotFoundPasses()
	{
		$this->validator->setHttpClient(new TPasswordStrengthValidatorTestHttpClient(new THttpClientResponse(200, [], "0123456789ABCDEF0123456789ABCDEF012:9\n")));
		$this->validator->setCheckCompromised(true);
		$this->assertTrue($this->valid(self::PASSWORD));
	}

	public function testWeakPasswordSkipsTheRequest()
	{
		$client = new TPasswordStrengthValidatorTestHttpClient(new THttpClientResponse(200, [], ''));
		$this->validator->setHttpClient($client);
		$this->validator->setCheckCompromised(true);
		$this->assertFalse($this->valid('password'));
		$this->assertTrue($this->valid(''));
		$this->assertSame([], $client->requests);
	}

	public function testTransportFailurePasses()
	{
		$this->validator->setHttpClient(new TPasswordStrengthValidatorTestHttpClient(null));
		$this->validator->setCheckCompromised(true);
		$this->assertTrue($this->valid(self::PASSWORD));
	}

	public function testErrorStatusPasses()
	{
		$this->validator->setHttpClient(new TPasswordStrengthValidatorTestHttpClient(new THttpClientResponse(503, [], 'unavailable')));
		$this->validator->setCheckCompromised(true);
		$this->assertTrue($this->valid(self::PASSWORD));
	}

	public function testCustomApiUrl()
	{
		$client = new TPasswordStrengthValidatorTestHttpClient(new THttpClientResponse(200, [], ''));
		$this->validator->setHttpClient($client);
		$this->validator->setCompromisedApiUrl('https://pwned.example/range/');
		$this->assertFalse($this->validator->isCompromised(self::PASSWORD));
		$this->assertStringStartsWith('https://pwned.example/range/', $client->requests[0][1]);
	}

	public function testDefaultHttpClientUsesTimeout()
	{
		$this->validator->setCompromisedTimeout(2);
		$client = $this->validator->getHttpClient();
		$this->assertInstanceOf(THttpClient::class, $client);
		$this->assertSame(2, $client->getTimeout());
		$this->assertSame($client, $this->validator->getHttpClient());
	}

	public function testClientScriptOptions()
	{
		$this->validator->getPage()->setForm(new TForm());
		$this->validator->setMinStrength(TPasswordStrength::Strong);
		$this->validator->setCheckCompromised(true);
		$options = PradoUnit::invoke($this->validator, 'getClientScriptOptions');
		$this->assertSame(80, $options['MinEntropy']);
		$this->assertArrayNotHasKey('CheckCompromised', $options);
		$this->assertArrayNotHasKey('CompromisedApiUrl', $options);
	}
}

<?php

use Prado\Web\Services\Rest\TRestException;

/**
 * Tests for TRestException.
 */
class TRestExceptionTest extends PHPUnit\Framework\TestCase
{
	// ── Constructor and basic accessors ────────────────────────────────────────

	public function testConstructorSetsStatusCode(): void
	{
		$e = new TRestException(404);
		$this->assertSame(404, $e->getStatusCode());
	}

	public function testConstructorDefaultsTitleToHttpReason(): void
	{
		$e = new TRestException(404);
		$this->assertSame('Not Found', $e->getTitle());

		$e2 = new TRestException(422);
		$this->assertSame('Unprocessable Entity', $e2->getTitle());

		$e3 = new TRestException(500);
		$this->assertSame('Internal Server Error', $e3->getTitle());
	}

	public function testConstructorUsesCustomTitle(): void
	{
		$e = new TRestException(404, 'Custom Title');
		$this->assertSame('Custom Title', $e->getTitle());
	}

	public function testConstructorUnknownStatusCodeFallsBackToError(): void
	{
		$e = new TRestException(418);
		$this->assertSame('Error', $e->getTitle());
	}

	public function testConstructorSetsDetail(): void
	{
		$e = new TRestException(404, '', 'Resource not found.');
		$this->assertSame('Resource not found.', $e->getDetail());
	}

	public function testConstructorSetsErrors(): void
	{
		$errors = ['email' => ['Required.'], 'name' => ['Too long.']];
		$e = new TRestException(422, '', '', $errors);
		$this->assertSame($errors, $e->getErrors());
	}

	public function testConstructorDefaultsDetailAndErrorsToEmpty(): void
	{
		$e = new TRestException(400);
		$this->assertSame('', $e->getDetail());
		$this->assertSame([], $e->getErrors());
	}

	public function testIsThrowable(): void
	{
		$this->expectException(TRestException::class);
		throw new TRestException(500);
	}

	// ── toArray ────────────────────────────────────────────────────────────────

	public function testToArrayAlwaysIncludesStatusAndTitle(): void
	{
		$arr = (new TRestException(403))->toArray();
		$this->assertArrayHasKey('status', $arr);
		$this->assertArrayHasKey('title', $arr);
		$this->assertSame(403, $arr['status']);
		$this->assertSame('Forbidden', $arr['title']);
	}

	public function testToArrayOmitsDetailWhenEmpty(): void
	{
		$arr = (new TRestException(404))->toArray();
		$this->assertArrayNotHasKey('detail', $arr);
	}

	public function testToArrayIncludesDetailWhenSet(): void
	{
		$arr = (new TRestException(404, '', 'Not here.'))->toArray();
		$this->assertSame('Not here.', $arr['detail']);
	}

	public function testToArrayOmitsErrorsWhenEmpty(): void
	{
		$arr = (new TRestException(422))->toArray();
		$this->assertArrayNotHasKey('errors', $arr);
	}

	public function testToArrayIncludesErrorsWhenSet(): void
	{
		$errors = ['field' => ['msg']];
		$arr = (new TRestException(422, '', '', $errors))->toArray();
		$this->assertSame($errors, $arr['errors']);
	}

	// ── Static factory methods ─────────────────────────────────────────────────

	public function testBadRequest(): void
	{
		$e = TRestException::badRequest('Bad input.');
		$this->assertSame(400, $e->getStatusCode());
		$this->assertSame('Bad Request', $e->getTitle());
		$this->assertSame('Bad input.', $e->getDetail());
	}

	public function testUnauthorized(): void
	{
		$e = TRestException::unauthorized('Please log in.');
		$this->assertSame(401, $e->getStatusCode());
		$this->assertSame('Unauthorized', $e->getTitle());
	}

	public function testForbidden(): void
	{
		$e = TRestException::forbidden();
		$this->assertSame(403, $e->getStatusCode());
		$this->assertSame('Forbidden', $e->getTitle());
		$this->assertSame('', $e->getDetail());
	}

	public function testNotFound(): void
	{
		$e = TRestException::notFound('User 42 not found.');
		$this->assertSame(404, $e->getStatusCode());
		$this->assertSame('Not Found', $e->getTitle());
		$this->assertSame('User 42 not found.', $e->getDetail());
	}

	public function testMethodNotAllowed(): void
	{
		$e = TRestException::methodNotAllowed();
		$this->assertSame(405, $e->getStatusCode());
	}

	public function testConflict(): void
	{
		$e = TRestException::conflict('Duplicate email.');
		$this->assertSame(409, $e->getStatusCode());
		$this->assertSame('Duplicate email.', $e->getDetail());
	}

	public function testUnsupportedMediaType(): void
	{
		$e = TRestException::unsupportedMediaType();
		$this->assertSame(415, $e->getStatusCode());
	}

	public function testUnprocessable(): void
	{
		$errors = ['email' => ['Invalid.']];
		$e = TRestException::unprocessable($errors, 'Validation failed.');
		$this->assertSame(422, $e->getStatusCode());
		$this->assertSame('Unprocessable Entity', $e->getTitle());
		$this->assertSame('Validation failed.', $e->getDetail());
		$this->assertSame($errors, $e->getErrors());
	}

	public function testUnprocessableWithNoDetail(): void
	{
		$e = TRestException::unprocessable(['x' => ['y']]);
		$this->assertSame(422, $e->getStatusCode());
		$this->assertSame('', $e->getDetail());
	}

	public function testTooManyRequests(): void
	{
		$e = TRestException::tooManyRequests();
		$this->assertSame(429, $e->getStatusCode());
	}

	public function testInternalError(): void
	{
		$e = TRestException::internalError('Oops.');
		$this->assertSame(500, $e->getStatusCode());
		$this->assertSame('Oops.', $e->getDetail());
	}

	public function testFactoriesReturnInstanceOfTRestException(): void
	{
		$this->assertInstanceOf(TRestException::class, TRestException::badRequest());
		$this->assertInstanceOf(TRestException::class, TRestException::notFound());
		$this->assertInstanceOf(TRestException::class, TRestException::unprocessable([]));
	}

	public function testGetCodeEqualsStatusCode(): void
	{
		$e = TRestException::notFound();
		$this->assertSame(404, $e->getCode());
		$this->assertSame($e->getStatusCode(), $e->getCode());
	}

	public function testToArrayWithDetailButNoErrors(): void
	{
		$e = new TRestException(400, '', 'Bad input');
		$arr = $e->toArray();
		$this->assertSame(400, $arr['status']);
		$this->assertSame('Bad input', $arr['detail']);
		$this->assertArrayNotHasKey('errors', $arr);
	}

	public function testToArrayWithErrorsButNoDetail(): void
	{
		$e = TRestException::unprocessable(['email' => ['required']]);
		$arr = $e->toArray();
		$this->assertArrayNotHasKey('detail', $arr);
		$this->assertSame(['email' => ['required']], $arr['errors']);
	}

	public function testToArrayWithBothDetailAndErrors(): void
	{
		$e = TRestException::unprocessable(['x' => ['bad']], 'invalid');
		$arr = $e->toArray();
		$this->assertSame('invalid', $arr['detail']);
		$this->assertSame(['x' => ['bad']], $arr['errors']);
	}

	// ── Factory status-to-title mapping ────────────────────────────────────────

	/**
	 * @dataProvider factoryTitleProvider
	 */
	public function testFactoriesMapToExpectedStatusAndTitle(callable $factory, int $status, string $title): void
	{
		$e = $factory();
		$this->assertSame($status, $e->getStatusCode());
		$this->assertSame($title, $e->getTitle());
	}

	public static function factoryTitleProvider(): array
	{
		return [
			'badRequest' => [fn () => TRestException::badRequest(), 400, 'Bad Request'],
			'unauthorized' => [fn () => TRestException::unauthorized(), 401, 'Unauthorized'],
			'forbidden' => [fn () => TRestException::forbidden(), 403, 'Forbidden'],
			'notFound' => [fn () => TRestException::notFound(), 404, 'Not Found'],
			'methodNotAllowed' => [fn () => TRestException::methodNotAllowed(), 405, 'Method Not Allowed'],
			'conflict' => [fn () => TRestException::conflict(), 409, 'Conflict'],
			'unsupportedMediaType' => [fn () => TRestException::unsupportedMediaType(), 415, 'Unsupported Media Type'],
			'unprocessable' => [fn () => TRestException::unprocessable(), 422, 'Unprocessable Entity'],
			'tooManyRequests' => [fn () => TRestException::tooManyRequests(), 429, 'Too Many Requests'],
			'internalError' => [fn () => TRestException::internalError(), 500, 'Internal Server Error'],
		];
	}

	public function testServiceUnavailableTitle(): void
	{
		$e = new TRestException(503);
		$this->assertSame('Service Unavailable', $e->getTitle());
	}

	// ── toArray boundary: status + title only ──────────────────────────────────

	public function testToArrayWithOnlyStatusAndTitleHasNoDetailOrErrors(): void
	{
		$arr = (new TRestException(404))->toArray();
		$this->assertSame(['status' => 404, 'title' => 'Not Found'], $arr);
	}

	public function testUnprocessableWithDefaultEmptyErrorsOmitsErrorsKey(): void
	{
		$arr = TRestException::unprocessable()->toArray();
		$this->assertArrayNotHasKey('errors', $arr);
	}

	// ── Unknown / boundary status codes ────────────────────────────────────────

	public function testUnknownStatusCodeFallsBackToGenericTitle(): void
	{
		$e = new TRestException(499);
		$this->assertSame('Error', $e->getTitle());
		$this->assertSame(499, $e->getStatusCode());
	}

	public function testCodeEqualsStatusForMultipleCodes(): void
	{
		foreach ([400, 404, 422, 429, 500] as $code) {
			$this->assertSame($code, (new TRestException($code))->getCode());
		}
	}

	public function testWhitespaceOnlyTitleIsPreservedNotTreatedAsEmpty(): void
	{
		// Only an empty string triggers the reason-phrase fallback; ' ' is kept.
		$e = new TRestException(404, ' ');
		$this->assertSame(' ', $e->getTitle());
	}

	public function testUnicodeTitleAndDetailRoundTrip(): void
	{
		$e = new TRestException(400, 'Erreur héllo', 'détail café');
		$arr = $e->toArray();
		$this->assertSame('Erreur héllo', $arr['title']);
		$this->assertSame('détail café', $arr['detail']);
	}

	public function testThrowabilityPropagatesMessageAndCode(): void
	{
		try {
			throw TRestException::notFound('gone');
		} catch (TRestException $e) {
			$this->assertSame(404, $e->getStatusCode());
			$this->assertSame(404, $e->getCode());
			$this->assertSame('gone', $e->getDetail());
		}
	}
}

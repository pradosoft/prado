<?php

use Prado\Web\THttpHeaderName;

class THttpHeaderNameTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Inheritance
	// -----------------------------------------------------------------------

	public function testExtendsEnumerable()
	{
		self::assertInstanceOf(\Prado\TEnumerable::class, new THttpHeaderName());
	}

	// -----------------------------------------------------------------------
	// Security headers
	// -----------------------------------------------------------------------

	public function testContentSecurityPolicy()
	{
		self::assertSame('Content-Security-Policy', THttpHeaderName::ContentSecurityPolicy);
	}

	public function testContentSecurityPolicyReportOnly()
	{
		self::assertSame('Content-Security-Policy-Report-Only', THttpHeaderName::ContentSecurityPolicyReportOnly);
	}

	public function testStrictTransportSecurity()
	{
		self::assertSame('Strict-Transport-Security', THttpHeaderName::StrictTransportSecurity);
	}

	public function testXFrameOptions()
	{
		self::assertSame('X-Frame-Options', THttpHeaderName::XFrameOptions);
	}

	public function testXContentTypeOptions()
	{
		self::assertSame('X-Content-Type-Options', THttpHeaderName::XContentTypeOptions);
	}

	public function testReferrerPolicy()
	{
		self::assertSame('Referrer-Policy', THttpHeaderName::ReferrerPolicy);
	}

	public function testPermissionsPolicy()
	{
		self::assertSame('Permissions-Policy', THttpHeaderName::PermissionsPolicy);
	}

	public function testCrossOriginEmbedderPolicy()
	{
		self::assertSame('Cross-Origin-Embedder-Policy', THttpHeaderName::CrossOriginEmbedderPolicy);
	}

	public function testCrossOriginOpenerPolicy()
	{
		self::assertSame('Cross-Origin-Opener-Policy', THttpHeaderName::CrossOriginOpenerPolicy);
	}

	public function testCrossOriginResourcePolicy()
	{
		self::assertSame('Cross-Origin-Resource-Policy', THttpHeaderName::CrossOriginResourcePolicy);
	}

	// -----------------------------------------------------------------------
	// CORS headers
	// -----------------------------------------------------------------------

	public function testAccessControlAllowOrigin()
	{
		self::assertSame('Access-Control-Allow-Origin', THttpHeaderName::AccessControlAllowOrigin);
	}

	public function testAccessControlAllowMethods()
	{
		self::assertSame('Access-Control-Allow-Methods', THttpHeaderName::AccessControlAllowMethods);
	}

	public function testAccessControlAllowHeaders()
	{
		self::assertSame('Access-Control-Allow-Headers', THttpHeaderName::AccessControlAllowHeaders);
	}

	public function testAccessControlAllowCredentials()
	{
		self::assertSame('Access-Control-Allow-Credentials', THttpHeaderName::AccessControlAllowCredentials);
	}

	public function testAccessControlExposeHeaders()
	{
		self::assertSame('Access-Control-Expose-Headers', THttpHeaderName::AccessControlExposeHeaders);
	}

	public function testAccessControlMaxAge()
	{
		self::assertSame('Access-Control-Max-Age', THttpHeaderName::AccessControlMaxAge);
	}

	// -----------------------------------------------------------------------
	// Reporting headers
	// -----------------------------------------------------------------------

	public function testReportingEndpoints()
	{
		self::assertSame('Reporting-Endpoints', THttpHeaderName::ReportingEndpoints);
	}

	public function testReportTo()
	{
		self::assertSame('Report-To', THttpHeaderName::ReportTo);
	}

	public function testNEL()
	{
		self::assertSame('NEL', THttpHeaderName::NEL);
	}

	// -----------------------------------------------------------------------
	// Caching headers
	// -----------------------------------------------------------------------

	public function testCacheControl()
	{
		self::assertSame('Cache-Control', THttpHeaderName::CacheControl);
	}

	public function testETag()
	{
		self::assertSame('ETag', THttpHeaderName::ETag);
	}

	public function testLastModified()
	{
		self::assertSame('Last-Modified', THttpHeaderName::LastModified);
	}

	public function testExpires()
	{
		self::assertSame('Expires', THttpHeaderName::Expires);
	}

	public function testVary()
	{
		self::assertSame('Vary', THttpHeaderName::Vary);
	}

	public function testAge()
	{
		self::assertSame('Age', THttpHeaderName::Age);
	}

	// -----------------------------------------------------------------------
	// Content headers
	// -----------------------------------------------------------------------

	public function testContentType()
	{
		self::assertSame('Content-Type', THttpHeaderName::ContentType);
	}

	public function testContentEncoding()
	{
		self::assertSame('Content-Encoding', THttpHeaderName::ContentEncoding);
	}

	public function testContentLength()
	{
		self::assertSame('Content-Length', THttpHeaderName::ContentLength);
	}

	public function testContentLanguage()
	{
		self::assertSame('Content-Language', THttpHeaderName::ContentLanguage);
	}

	public function testContentDisposition()
	{
		self::assertSame('Content-Disposition', THttpHeaderName::ContentDisposition);
	}

	// -----------------------------------------------------------------------
	// Range headers (RFC 7233)
	// -----------------------------------------------------------------------

	public function testAcceptRanges()
	{
		self::assertSame('Accept-Ranges', THttpHeaderName::AcceptRanges);
	}

	public function testRange()
	{
		self::assertSame('Range', THttpHeaderName::Range);
	}

	public function testContentRange()
	{
		self::assertSame('Content-Range', THttpHeaderName::ContentRange);
	}

	public function testIfRange()
	{
		self::assertSame('If-Range', THttpHeaderName::IfRange);
	}

	// -----------------------------------------------------------------------
	// Request headers
	// -----------------------------------------------------------------------

	public function testAccept()
	{
		self::assertSame('Accept', THttpHeaderName::Accept);
	}

	public function testAcceptEncoding()
	{
		self::assertSame('Accept-Encoding', THttpHeaderName::AcceptEncoding);
	}

	public function testAcceptLanguage()
	{
		self::assertSame('Accept-Language', THttpHeaderName::AcceptLanguage);
	}

	public function testAuthorization()
	{
		self::assertSame('Authorization', THttpHeaderName::Authorization);
	}

	public function testCookie()
	{
		self::assertSame('Cookie', THttpHeaderName::Cookie);
	}

	public function testHost()
	{
		self::assertSame('Host', THttpHeaderName::Host);
	}

	public function testOrigin()
	{
		self::assertSame('Origin', THttpHeaderName::Origin);
	}

	public function testReferer()
	{
		self::assertSame('Referer', THttpHeaderName::Referer);
	}

	public function testUserAgent()
	{
		self::assertSame('User-Agent', THttpHeaderName::UserAgent);
	}

	public function testXRequestedWith()
	{
		self::assertSame('X-Requested-With', THttpHeaderName::XRequestedWith);
	}

	public function testForwarded()
	{
		self::assertSame('Forwarded', THttpHeaderName::Forwarded);
	}

	public function testXForwardedFor()
	{
		self::assertSame('X-Forwarded-For', THttpHeaderName::XForwardedFor);
	}

	public function testXForwardedProto()
	{
		self::assertSame('X-Forwarded-Proto', THttpHeaderName::XForwardedProto);
	}

	public function testXForwardedHost()
	{
		self::assertSame('X-Forwarded-Host', THttpHeaderName::XForwardedHost);
	}

	public function testIfMatch()
	{
		self::assertSame('If-Match', THttpHeaderName::IfMatch);
	}

	public function testIfNoneMatch()
	{
		self::assertSame('If-None-Match', THttpHeaderName::IfNoneMatch);
	}

	public function testIfModifiedSince()
	{
		self::assertSame('If-Modified-Since', THttpHeaderName::IfModifiedSince);
	}

	public function testIfUnmodifiedSince()
	{
		self::assertSame('If-Unmodified-Since', THttpHeaderName::IfUnmodifiedSince);
	}

	public function testProxyAuthorization()
	{
		self::assertSame('Proxy-Authorization', THttpHeaderName::ProxyAuthorization);
	}

	// -----------------------------------------------------------------------
	// Response headers
	// -----------------------------------------------------------------------

	public function testAllow()
	{
		self::assertSame('Allow', THttpHeaderName::Allow);
	}

	public function testDate()
	{
		self::assertSame('Date', THttpHeaderName::Date);
	}

	public function testServer()
	{
		self::assertSame('Server', THttpHeaderName::Server);
	}

	public function testSetCookie()
	{
		self::assertSame('Set-Cookie', THttpHeaderName::SetCookie);
	}

	public function testLocation()
	{
		self::assertSame('Location', THttpHeaderName::Location);
	}

	public function testContentLocation()
	{
		self::assertSame('Content-Location', THttpHeaderName::ContentLocation);
	}

	public function testWWWAuthenticate()
	{
		self::assertSame('WWW-Authenticate', THttpHeaderName::WWWAuthenticate);
	}

	public function testProxyAuthenticate()
	{
		self::assertSame('Proxy-Authenticate', THttpHeaderName::ProxyAuthenticate);
	}

	public function testRetryAfter()
	{
		self::assertSame('Retry-After', THttpHeaderName::RetryAfter);
	}

	public function testLink()
	{
		self::assertSame('Link', THttpHeaderName::Link);
	}

	public function testTransferEncoding()
	{
		self::assertSame('Transfer-Encoding', THttpHeaderName::TransferEncoding);
	}

	public function testServerTiming()
	{
		self::assertSame('Server-Timing', THttpHeaderName::ServerTiming);
	}

	// -----------------------------------------------------------------------
	// WebDAV headers (RFC 4918)
	// -----------------------------------------------------------------------

	public function testDAV()
	{
		self::assertSame('DAV', THttpHeaderName::DAV);
	}

	public function testDepth()
	{
		self::assertSame('Depth', THttpHeaderName::Depth);
	}

	public function testDestination()
	{
		self::assertSame('Destination', THttpHeaderName::Destination);
	}

	/**
	 * The WebDAV `If` header is stored under the constant name `DavIf`
	 * because `if` is a reserved PHP keyword.
	 */
	public function testDavIf()
	{
		self::assertSame('If', THttpHeaderName::DavIf);
	}

	public function testLockToken()
	{
		self::assertSame('Lock-Token', THttpHeaderName::LockToken);
	}

	public function testOverwrite()
	{
		self::assertSame('Overwrite', THttpHeaderName::Overwrite);
	}

	public function testTimeout()
	{
		self::assertSame('Timeout', THttpHeaderName::Timeout);
	}

	// -----------------------------------------------------------------------
	// Deprecated headers
	// -----------------------------------------------------------------------

	public function testAcceptCharset()
	{
		self::assertSame('Accept-Charset', THttpHeaderName::AcceptCharset);
	}

	public function testFeaturePolicy()
	{
		self::assertSame('Feature-Policy', THttpHeaderName::FeaturePolicy);
	}

	public function testXXSSProtection()
	{
		self::assertSame('X-XSS-Protection', THttpHeaderName::XXSSProtection);
	}

	public function testPragma()
	{
		self::assertSame('Pragma', THttpHeaderName::Pragma);
	}

	// -----------------------------------------------------------------------
	// TConstantReflectionTrait — hasConstant / valueOfConstant / constantOfValue
	// -----------------------------------------------------------------------

	public function testHasConstantReturnsTrueForKnownConstant()
	{
		self::assertTrue(THttpHeaderName::hasConstant('ContentSecurityPolicy'));
		self::assertTrue(THttpHeaderName::hasConstant('StrictTransportSecurity'));
		self::assertTrue(THttpHeaderName::hasConstant('CacheControl'));
	}

	public function testHasConstantReturnsFalseForUnknownConstant()
	{
		self::assertFalse(THttpHeaderName::hasConstant('nonexistent'));
		self::assertFalse(THttpHeaderName::hasConstant('Content-Security-Policy')); // value, not name
	}

	public function testValueOfConstantReturnsHeaderString()
	{
		self::assertSame('Content-Security-Policy', THttpHeaderName::valueOfConstant('ContentSecurityPolicy'));
		self::assertSame('Strict-Transport-Security', THttpHeaderName::valueOfConstant('StrictTransportSecurity'));
		self::assertSame('Cache-Control', THttpHeaderName::valueOfConstant('CacheControl'));
	}

	public function testConstantOfValueReturnsConstantName()
	{
		self::assertSame('ContentSecurityPolicy', THttpHeaderName::constantOfValue('Content-Security-Policy'));
		self::assertSame('StrictTransportSecurity', THttpHeaderName::constantOfValue('Strict-Transport-Security'));
		self::assertSame('CacheControl', THttpHeaderName::constantOfValue('Cache-Control'));
	}

	/**
	 * DavIf uses the constant name 'DavIf' but its value is the string 'If'.
	 * Verify the reflection trait handles this correctly in both directions.
	 */
	public function testDavIfReflectionRoundTrip()
	{
		self::assertTrue(THttpHeaderName::hasConstant('DavIf'));
		self::assertSame('If', THttpHeaderName::valueOfConstant('DavIf'));
		self::assertSame('DavIf', THttpHeaderName::constantOfValue('If'));
	}

	// -----------------------------------------------------------------------
	// Iterator — all expected headers are reachable
	// -----------------------------------------------------------------------

	public function testIteratorCoversAllHeaders()
	{
		$expected = [
			// Security
			'Content-Security-Policy', 'Content-Security-Policy-Report-Only',
			'Strict-Transport-Security', 'X-Frame-Options', 'X-Content-Type-Options',
			'Referrer-Policy', 'Permissions-Policy',
			'Cross-Origin-Embedder-Policy', 'Cross-Origin-Opener-Policy',
			'Cross-Origin-Resource-Policy',
			// CORS
			'Access-Control-Allow-Origin', 'Access-Control-Allow-Methods',
			'Access-Control-Allow-Headers', 'Access-Control-Allow-Credentials',
			'Access-Control-Expose-Headers', 'Access-Control-Max-Age',
			// Reporting
			'Reporting-Endpoints', 'Report-To', 'NEL',
			// Caching
			'Cache-Control', 'ETag', 'Last-Modified', 'Expires', 'Vary', 'Age',
			// Content
			'Content-Type', 'Content-Encoding', 'Content-Length',
			'Content-Language', 'Content-Disposition',
			// Range
			'Accept-Ranges', 'Range', 'Content-Range', 'If-Range',
			// Request
			'Accept', 'Accept-Encoding', 'Accept-Language', 'Authorization',
			'Cookie', 'Host', 'Origin', 'Referer',
			'User-Agent', 'X-Requested-With',
			'Forwarded', 'X-Forwarded-For', 'X-Forwarded-Proto', 'X-Forwarded-Host',
			'If-Match', 'If-None-Match', 'If-Modified-Since', 'If-Unmodified-Since',
			'Proxy-Authorization',
			// Response
			'Allow', 'Date', 'Server',
			'Set-Cookie', 'Location', 'Content-Location',
			'WWW-Authenticate', 'Proxy-Authenticate',
			'Retry-After', 'Link', 'Transfer-Encoding', 'Server-Timing',
			// WebDAV
			'DAV', 'Depth', 'Destination', 'If', 'Lock-Token', 'Overwrite', 'Timeout',
			// Connection upgrade (WebSocket)
			'Connection', 'Upgrade', 'Sec-WebSocket-Key', 'Sec-WebSocket-Accept',
			'Sec-WebSocket-Version', 'Sec-WebSocket-Protocol', 'Sec-WebSocket-Extensions',
			// Deprecated
			'Accept-Charset', 'Feature-Policy', 'X-XSS-Protection', 'Pragma',
		];

		$values = [];
		foreach (new THttpHeaderName() as $value) {
			$values[] = $value;
		}

		foreach ($expected as $header) {
			self::assertContains($header, $values, "Missing header: $header");
		}

		self::assertCount(count($expected), $values, 'Unexpected number of header names');
	}

	// -----------------------------------------------------------------------
	// Usability — constant values are valid HTTP header name strings
	// -----------------------------------------------------------------------

	public function testConstantsAreValidHttpHeaderNames()
	{
		$headers = [
			THttpHeaderName::ContentSecurityPolicy,
			THttpHeaderName::StrictTransportSecurity,
			THttpHeaderName::XFrameOptions,
			THttpHeaderName::CacheControl,
			THttpHeaderName::ContentType,
			THttpHeaderName::Authorization,
		];

		foreach ($headers as $header) {
			self::assertIsString($header);
			self::assertNotEmpty($header);
			// HTTP header names: printable ASCII, no control chars, no separators
			self::assertMatchesRegularExpression('/^[A-Za-z][A-Za-z0-9\-]*$/', $header, "Invalid header name format: $header");
		}
	}

	// -----------------------------------------------------------------------
	// Cross-class relationships documented in constants
	// -----------------------------------------------------------------------

	public function testCspAndReportOnlyAreDistinct()
	{
		self::assertNotEquals(
			THttpHeaderName::ContentSecurityPolicy,
			THttpHeaderName::ContentSecurityPolicyReportOnly
		);
	}

	public function testReportingEndpointsAndReportToAreDistinct()
	{
		// Reporting-Endpoints (modern) vs Report-To (legacy) must be different strings.
		self::assertNotEquals(
			THttpHeaderName::ReportingEndpoints,
			THttpHeaderName::ReportTo
		);
	}

	public function testForwardedAndXForwardedForAreDistinct()
	{
		// RFC 7239 Forwarded vs the legacy X-Forwarded-For must differ.
		self::assertNotEquals(
			THttpHeaderName::Forwarded,
			THttpHeaderName::XForwardedFor
		);
	}

	public function testIfMatchAndIfNoneMatchAreDistinct()
	{
		self::assertNotEquals(
			THttpHeaderName::IfMatch,
			THttpHeaderName::IfNoneMatch
		);
	}

	public function testIfModifiedSinceAndIfUnmodifiedSinceAreDistinct()
	{
		self::assertNotEquals(
			THttpHeaderName::IfModifiedSince,
			THttpHeaderName::IfUnmodifiedSince
		);
	}

	public function testAuthorizationAndProxyAuthorizationAreDistinct()
	{
		self::assertNotEquals(
			THttpHeaderName::Authorization,
			THttpHeaderName::ProxyAuthorization
		);
	}

	public function testWWWAuthenticateAndProxyAuthenticateAreDistinct()
	{
		self::assertNotEquals(
			THttpHeaderName::WWWAuthenticate,
			THttpHeaderName::ProxyAuthenticate
		);
	}

	public function testAcceptRangesAndRangeAreDistinct()
	{
		self::assertNotEquals(
			THttpHeaderName::AcceptRanges,
			THttpHeaderName::Range
		);
	}

	public function testPermissionsPolicyAndFeaturePolicyAreDistinct()
	{
		// Permissions-Policy (current) vs Feature-Policy (deprecated) must differ.
		self::assertNotEquals(
			THttpHeaderName::PermissionsPolicy,
			THttpHeaderName::FeaturePolicy
		);
	}
}

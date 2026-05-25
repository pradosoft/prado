<?php

use Prado\Web\HttpHeaders\TCspDirective;

class TCspDirectiveTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Inheritance
	// -----------------------------------------------------------------------

	public function testExtendsEnumerable()
	{
		self::assertInstanceOf(\Prado\TEnumerable::class, new TCspDirective());
	}

	// -----------------------------------------------------------------------
	// Fetch directives — constant values match the HTTP header token exactly
	// -----------------------------------------------------------------------

	public function testDefaultSrc()
	{
		self::assertSame('default-src', TCspDirective::DefaultSrc);
	}

	public function testChildSrc()
	{
		self::assertSame('child-src', TCspDirective::ChildSrc);
	}

	public function testConnectSrc()
	{
		self::assertSame('connect-src', TCspDirective::ConnectSrc);
	}

	public function testFencedFrameSrc()
	{
		self::assertSame('fenced-frame-src', TCspDirective::FencedFrameSrc);
	}

	public function testFontSrc()
	{
		self::assertSame('font-src', TCspDirective::FontSrc);
	}

	public function testFrameSrc()
	{
		self::assertSame('frame-src', TCspDirective::FrameSrc);
	}

	public function testImgSrc()
	{
		self::assertSame('img-src', TCspDirective::ImgSrc);
	}

	public function testManifestSrc()
	{
		self::assertSame('manifest-src', TCspDirective::ManifestSrc);
	}

	public function testMediaSrc()
	{
		self::assertSame('media-src', TCspDirective::MediaSrc);
	}

	public function testObjectSrc()
	{
		self::assertSame('object-src', TCspDirective::ObjectSrc);
	}

	public function testScriptSrc()
	{
		self::assertSame('script-src', TCspDirective::ScriptSrc);
	}

	public function testScriptSrcElem()
	{
		self::assertSame('script-src-elem', TCspDirective::ScriptSrcElem);
	}

	public function testScriptSrcAttr()
	{
		self::assertSame('script-src-attr', TCspDirective::ScriptSrcAttr);
	}

	public function testStyleSrc()
	{
		self::assertSame('style-src', TCspDirective::StyleSrc);
	}

	public function testStyleSrcElem()
	{
		self::assertSame('style-src-elem', TCspDirective::StyleSrcElem);
	}

	public function testStyleSrcAttr()
	{
		self::assertSame('style-src-attr', TCspDirective::StyleSrcAttr);
	}

	public function testWorkerSrc()
	{
		self::assertSame('worker-src', TCspDirective::WorkerSrc);
	}

	// -----------------------------------------------------------------------
	// Document directives
	// -----------------------------------------------------------------------

	public function testBaseUri()
	{
		self::assertSame('base-uri', TCspDirective::BaseUri);
	}

	public function testSandbox()
	{
		self::assertSame('sandbox', TCspDirective::Sandbox);
	}

	// -----------------------------------------------------------------------
	// Navigation directives
	// -----------------------------------------------------------------------

	public function testFormAction()
	{
		self::assertSame('form-action', TCspDirective::FormAction);
	}

	public function testFrameAncestors()
	{
		self::assertSame('frame-ancestors', TCspDirective::FrameAncestors);
	}

	// -----------------------------------------------------------------------
	// Reporting directives
	// -----------------------------------------------------------------------

	public function testReportTo()
	{
		self::assertSame('report-to', TCspDirective::ReportTo);
	}

	// -----------------------------------------------------------------------
	// Other directives
	// -----------------------------------------------------------------------

	public function testRequireTrustedTypesFor()
	{
		self::assertSame('require-trusted-types-for', TCspDirective::RequireTrustedTypesFor);
	}

	public function testTrustedTypes()
	{
		self::assertSame('trusted-types', TCspDirective::TrustedTypes);
	}

	public function testUpgradeInsecureRequests()
	{
		self::assertSame('upgrade-insecure-requests', TCspDirective::UpgradeInsecureRequests);
	}

	// -----------------------------------------------------------------------
	// Deprecated directives
	// -----------------------------------------------------------------------

	public function testBlockAllMixedContent()
	{
		self::assertSame('block-all-mixed-content', TCspDirective::BlockAllMixedContent);
	}

	public function testPrefetchSrc()
	{
		self::assertSame('prefetch-src', TCspDirective::PrefetchSrc);
	}

	public function testReportUri()
	{
		self::assertSame('report-uri', TCspDirective::ReportUri);
	}

	// -----------------------------------------------------------------------
	// TConstantReflectionTrait — hasConstant / valueOfConstant / constantOfValue
	// -----------------------------------------------------------------------

	public function testHasConstantReturnsTrueForKnownConstant()
	{
		self::assertTrue(TCspDirective::hasConstant('DefaultSrc'));
		self::assertTrue(TCspDirective::hasConstant('ScriptSrc'));
		self::assertTrue(TCspDirective::hasConstant('ReportTo'));
	}

	public function testHasConstantReturnsFalseForUnknownConstant()
	{
		self::assertFalse(TCspDirective::hasConstant('nonexistent'));
		self::assertFalse(TCspDirective::hasConstant('default-src')); // value, not name
	}

	public function testValueOfConstantReturnsDirectiveString()
	{
		self::assertSame('default-src', TCspDirective::valueOfConstant('DefaultSrc'));
		self::assertSame('frame-ancestors', TCspDirective::valueOfConstant('FrameAncestors'));
		self::assertSame('report-to', TCspDirective::valueOfConstant('ReportTo'));
	}

	public function testConstantOfValueReturnsConstantName()
	{
		self::assertSame('DefaultSrc', TCspDirective::constantOfValue('default-src'));
		self::assertSame('FrameAncestors', TCspDirective::constantOfValue('frame-ancestors'));
		self::assertSame('ReportTo', TCspDirective::constantOfValue('report-to'));
	}

	// -----------------------------------------------------------------------
	// Iterator — all expected directives are reachable
	// -----------------------------------------------------------------------

	public function testIteratorCoversAllDirectives()
	{
		$expected = [
			// Fetch directives
			'default-src', 'child-src', 'connect-src', 'fenced-frame-src',
			'font-src', 'frame-src', 'img-src', 'manifest-src', 'media-src',
			'object-src', 'script-src', 'script-src-elem', 'script-src-attr',
			'style-src', 'style-src-elem', 'style-src-attr', 'worker-src',
			// Document directives
			'base-uri', 'sandbox',
			// Navigation directives
			'form-action', 'frame-ancestors',
			// Reporting directives
			'report-to',
			// Other directives
			'require-trusted-types-for', 'trusted-types', 'upgrade-insecure-requests',
			// Deprecated directives
			'block-all-mixed-content', 'prefetch-src', 'report-uri',
		];

		$values = [];
		foreach (new TCspDirective() as $value) {
			$values[] = $value;
		}

		foreach ($expected as $directive) {
			self::assertContains($directive, $values, "Missing directive: $directive");
		}

		self::assertCount(count($expected), $values, 'Unexpected number of directives');
	}

	// -----------------------------------------------------------------------
	// Usability — constants are valid as THttpHeaderCsp policy name keys
	// -----------------------------------------------------------------------

	public function testConstantsAreUsableAsPolicyNames()
	{
		$directives = [
			TCspDirective::DefaultSrc,
			TCspDirective::ScriptSrc,
			TCspDirective::StyleSrc,
			TCspDirective::ImgSrc,
			TCspDirective::ConnectSrc,
			TCspDirective::FrameAncestors,
			TCspDirective::ReportTo,
		];

		foreach ($directives as $directive) {
			self::assertIsString($directive);
			self::assertNotEmpty($directive);
			self::assertMatchesRegularExpression('/^[a-z][a-z0-9-]*$/', $directive, "Invalid format: $directive");
		}
	}
}

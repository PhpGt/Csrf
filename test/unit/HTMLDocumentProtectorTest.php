<?php
namespace Gt\Csrf;

use Gt\Dom\HTMLDocument;
use PHPUnit\Framework\TestCase;

class HTMLDocumentProtectorTest extends TestCase {
	const NO_FORMS
		= <<<HTML
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Test HTML</title>
</head>
<body>
	<h1>This HTML is for the unit test.</h1>
	<p>There are a few elements in this document.</p>
</body>
</html>
HTML;

	const ONE_FORM
		= <<<HTML
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Test HTML</title>
</head>
<body>
	<h1>This HTML is for the unit test.</h1>
	<p>Hello</p>
    <form method="POST">
        <input type="text">
        <button type="submit"></button>
    </form>
</body>
</html>
HTML;

	const THREE_FORMS
		= <<<HTML
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Test HTML</title>
</head>
<body>
	<h1>This HTML is for the unit test.</h1>
	<p>Hello</p>
    <form method="POST">
        <input type="text">
        <button type="submit"></button>
    </form>
    <form method="GET">
        <input type="text" value="A text field">
        <button type="submit"></button>
    </form>
    <!-- an empty form too...-->
    <form method="POST">
    </form>
</body>
</html>
HTML;

	const HAS_META_ALREADY
		= <<<HTML
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<meta name="csrf-token" content="abc"/>
	<title>Test HTML</title>
</head>
<body>
	<h1>This HTML is for the unit test.</h1>
	<p>Hello</p>
    <!-- an empty form too...-->
    <form method="POST">
    </form>
</body>
</html>
HTML;

	const NO_HEAD
		= <<<HTML
<!doctype html>
<html>
</html>
HTML;


	public function testConstructFromString() {
		$sut = new HTMLDocumentProtector(new HTMLDocument(self::NO_FORMS), new ArrayTokenStore());
		$doc = $sut->getHTMLDocument();
		$this->assertInstanceOf(HTMLDocument::class, $doc);
		$this->assertEquals(
			"Test HTML", $doc->querySelector("title")->textContent);
	}

	public function testConstructFromDomDocument() {
		$domDoc = new \Gt\Dom\HTMLDocument(self::ONE_FORM);
		$sut = new HTMLDocumentProtector($domDoc, new ArrayTokenStore());
		$doc = $sut->getHTMLDocument();
		$this->assertSame($domDoc, $doc);
		$this->assertEquals(
			"Test HTML", $doc->querySelector("title")->textContent);
	}

	public function testZeroForms() {
		$sut = new HTMLDocumentProtector(new HTMLDocument(self::NO_FORMS), new ArrayTokenStore());
		$sut->protectAndInject();

		$built = $sut->getHTMLDocument();
		$domEls = $built
			->querySelectorAll("head meta[name='" . HTMLDocumentProtector::TOKEN_NAME . "']");

		// check that the token has been injected into the head
		$this->assertEquals(1, count($domEls));
		$this->assertNotEmpty($domEls[0]->getAttribute("content"));
		// and that a form hasn't been created or something similar
		$this->assertNull($built->querySelector("form"));
	}

	public function testSingleForm() {
		$sut = new HTMLDocumentProtector(new HTMLDocument(self::ONE_FORM), new ArrayTokenStore());
		$sut->protectAndInject();

		// check that the token has been injected
		$doc = $sut->getHTMLDocument();
		$this->assertTrue(
			strpos($doc->saveHTML(), HTMLDocumentProtector::TOKEN_NAME) >= 0);
		$this->assertNotNull(
			$doc->querySelector(
				"input[name='" . HTMLDocumentProtector::TOKEN_NAME . "']"));
		$this->assertNotEmpty(
			$doc->querySelector(
				"input[name='" . HTMLDocumentProtector::TOKEN_NAME . "']")
				->getAttribute("value"));

		// check that the meta tag has been created too
		$metaTag = $doc->querySelector("head meta[name='" . HTMLDocumentProtector::TOKEN_NAME . "']");
		$this->assertNotNull($metaTag);
		$this->assertNotEmpty(
			$metaTag->getAttribute("content"));
	}

	public function testMultipleForms() {
		$sut = new HTMLDocumentProtector(new HTMLDocument(self::THREE_FORMS), new ArrayTokenStore());
		$sut->protectAndInject();

		// check that the token has been injected in all forms
		$doc = $sut->getHTMLDocument();
		$this->assertEquals(
			3, $doc->querySelectorAll(
			"input[name='" . HTMLDocumentProtector::TOKEN_NAME . "']")->length);
		$this->assertEquals(
			1, $doc->querySelectorAll(
			"head meta[name='" . HTMLDocumentProtector::TOKEN_NAME . "']")->length);
	}

	public function testSingleCodeSharedAcrossForms() {
		$sut = new HTMLDocumentProtector(new HTMLDocument(self::THREE_FORMS), new ArrayTokenStore());
		$sut->protectAndInject(HTMLDocumentProtector::ONE_TOKEN_PER_PAGE);

		$doc = $sut->getHTMLDocument();
		$token = null;
		foreach($doc->querySelectorAll(
			"input[name='" . HTMLDocumentProtector::TOKEN_NAME . "']") as $input) {
			if($token === null) {
				$token = $input->getAttribute("value");
			}
			else {
				$this->assertEquals($token, $input->getAttribute("value"));
				$metaTag = $doc->querySelector("head meta[name='" . HTMLDocumentProtector::TOKEN_NAME . "']");
				$this->assertNotNull($metaTag);
				$this->assertEquals($token, $metaTag->getAttribute("content"));
			}
		}
	}

	public function testUniqueCodePerForm() {
		$sut = new HTMLDocumentProtector(new HTMLDocument(self::THREE_FORMS), new ArrayTokenStore());
		$sut->protectAndInject(HTMLDocumentProtector::ONE_TOKEN_PER_FORM);

		$doc = $sut->getHTMLDocument();
		$metaTag = $doc->querySelector("head meta[name='" . HTMLDocumentProtector::TOKEN_NAME . "']");
		$this->assertNotNull($metaTag);
		$prevToken = $metaTag->getAttribute("content");
		$newToken = null;
		foreach($doc->querySelectorAll(
			"input[name='" . HTMLDocumentProtector::TOKEN_NAME . "']") as $input) {
			$newToken = $token = $input->getAttribute("value");
			$this->assertNotEquals($newToken, $prevToken);
			$prevToken = $newToken;
		}
	}

	public function testMetaTagNoHead() {
		$sut = new HTMLDocumentProtector(new HTMLDocument(self::NO_HEAD), new ArrayTokenStore());
		$sut->protectAndInject(HTMLDocumentProtector::ONE_TOKEN_PER_PAGE);

		$doc = $sut->getHTMLDocument();
		$metaTag = $doc->querySelector("head meta[name='" . HTMLDocumentProtector::TOKEN_NAME . "']");
		$this->assertNotNull($metaTag);
		$this->assertNotEmpty($metaTag->getAttribute("content"));
	}

	public function testMetaTagAlreadyExists() {
		$sut = new HTMLDocumentProtector(new HTMLDocument(self::HAS_META_ALREADY), new ArrayTokenStore());
		$sut->protectAndInject(HTMLDocumentProtector::ONE_TOKEN_PER_PAGE);

		$doc = $sut->getHTMLDocument();
		$metaTag = $doc->querySelector("head meta[name='" . HTMLDocumentProtector::TOKEN_NAME . "']");
		$this->assertNotNull($metaTag);
		$this->assertNotEmpty($metaTag->getAttribute("content"));
		// make sure it's been updated (i.e. doesn't still have the value from the original html)
		$this->assertNotEquals("abc", $metaTag->getAttribute("content"));
	}

	public function testDifferentTokenName() {
		$sut = new HTMLDocumentProtector(new HTMLDocument(self::HAS_META_ALREADY), new ArrayTokenStore());
		$tokenName = $sut::TOKEN_NAME;
		$sut->protectAndInject();

		// check that the token has been injected in all forms
		$doc = $sut->getHTMLDocument();
		$this->assertEquals(
			1, $doc->querySelectorAll(
			"input[name='$tokenName']")->length);
		$this->assertEquals(
			1, $doc->querySelectorAll(
			"head meta[name='$tokenName']")->length);

		// and make sure the pre-existing meta tag hasn't been squashed
		$this->assertEquals(
			1, $doc->querySelectorAll(
			"head meta[name='csrf-token']")->length);
	}
}
<?php
namespace phpgt\csrf;

class HTMLDocumentProtectorTest extends \PHPUnit_Framework_TestCase {
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
HTML;

	public function testConstructFromString() {
		$sut = new HTMLDocumentProtector(self::NO_FORMS, new ArrayTokenStore());
		$doc = $sut->getHTMLDocument();
		$this->assertInstanceOf("\\phpgt\\dom\\HTMLDocument", $doc);
		$this->assertEquals(
			"Test HTML", $doc->querySelector("title")->textContent);
	}

	public function testConstructFromDomDocument() {
		$domDoc = new \phpgt\dom\HTMLDocument(self::ONE_FORM);
		$sut = new HTMLDocumentProtector($domDoc, new ArrayTokenStore());
		$doc = $sut->getHTMLDocument();
		$this->assertSame($domDoc, $doc);
		$this->assertEquals(
			"Test HTML", $doc->querySelector("title")->textContent);
	}

	public function testZeroForms() {
		$sut = new HTMLDocumentProtector(self::NO_FORMS, new ArrayTokenStore());
		$sut->protectAndInject();

		// check that the token hasn't been injected anywhere
		$this->assertFalse(
			strpos(
				$sut->getHTMLDocument()->saveHTML(),
				HTMLDocumentProtector::$TOKEN_NAME));
		// and that a form hasn't been created or something similar
		$this->assertNull($sut->getHTMLDocument()->querySelector("form"));
	}

	public function testSingleForm() {
		$sut = new HTMLDocumentProtector(self::ONE_FORM, new ArrayTokenStore());
		$sut->protectAndInject();

		// check that the token has been injected
		$doc = $sut->getHTMLDocument();
		$this->assertTrue(
			strpos($doc->saveHTML(), HTMLDocumentProtector::$TOKEN_NAME) >= 0);
		$this->assertNotNull(
			$doc->querySelector(
				"input[name='" . HTMLDocumentProtector::$TOKEN_NAME . "']"));
		$this->assertNotEmpty(
			$doc->querySelector(
				"input[name='" . HTMLDocumentProtector::$TOKEN_NAME . "']")
			    ->getAttribute("value"));
	}

	public function testMultipleForms() {
		$sut = new HTMLDocumentProtector(self::THREE_FORMS, new ArrayTokenStore());
		$sut->protectAndInject();

		// check that the token has been injected in all forms
		$doc = $sut->getHTMLDocument();
		$this->assertEquals(
			3, $doc->querySelectorAll(
			"input[name='" . HTMLDocumentProtector::$TOKEN_NAME . "']")->length);
	}

	// we don't need separate tokens for each form - that would be both wasteful,
	// and would increase the number of valid tokens that could be guessed
	// (particularly as at most one would ever be consumed & so burnt, leaving
	// lots of valid tokens lying around).  So check that one token is shared
	// across all of the forms
	public function testSingleCodeSharedAcrossForms() {

		$sut = new HTMLDocumentProtector(self::THREE_FORMS, new ArrayTokenStore());
		$sut->protectAndInject();

		$doc = $sut->getHTMLDocument();
		$token = null;
		foreach($doc->querySelectorAll(
			"input[name='" . HTMLDocumentProtector::$TOKEN_NAME . "']") as $input) {
			if($token === null) {
				$token = $input->getAttribute("value");
			} else {
				$this->assertEquals($token, $input->getAttribute("value"));
			}
		}
	}
}#
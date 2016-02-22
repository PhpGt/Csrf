<?php
namespace phpgt\csrf;


class HTMLDocumentProtector {
	public static $TOKEN_NAME = "csrf-token";
	private       $doc;
	private       $tokenStore;

	public function __construct($html, TokenStore $tokenStore) {
		$this->tokenStore = $tokenStore;

		if($html instanceof \phpgt\dom\HTMLDocument) {
			$this->doc = $html;
		} else {
			$this->doc = new \phpgt\dom\HTMLDocument($html);
		}
	}

	public function protectAndInject() {
		$forms = $this->doc->querySelectorAll("form");
		if($forms->length > 0) {
			$token = $this->tokenStore->generateNewToken();
			$this->tokenStore->saveToken($token);

			foreach($forms as $form) {
				$csrfElement = $this->doc->createElement("input");
				$csrfElement->setAttribute("name", static::$TOKEN_NAME);
				$csrfElement->setAttribute("value", $token);
				$csrfElement->setAttribute("type", "hidden");
				$form->appendChild($csrfElement);
			}
		}
	}

	public function getHTMLDocument() : \phpgt\dom\HTMLDocument {
		return $this->doc;
	}
}#
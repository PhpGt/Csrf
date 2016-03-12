<?php
namespace phpgt\csrf;

use phpgt\dom\HTMLDocument;

class HTMLDocumentProtector
{
    /**
     * @var string The name to be used for the hidden html input field used
     * to store the token in each form
     */
    public static $TOKEN_NAME = "csrf-token";
    private $doc;
    private $tokenStore;

    /**
     * HTMLDocumentProtector constructor.
     *
     * @param            $html       string|HTMLDocument The html document
     *                               whose forms should be injected with CSRF
     *                               tokens.  This can either be a
     *                               \phpgt\dom\HTMLDocument or anything that
     *                               can be used to construct one (such as
     *                               string).
     * @param TokenStore $tokenStore The TokenStore implementation to be used
     *                               for generating and storing tokens.
     */
    public function __construct($html, TokenStore $tokenStore)
    {
        $this->tokenStore = $tokenStore;

        if ($html instanceof HTMLDocument) {
            $this->doc = $html;
        } else {
            $this->doc = new HTMLDocument($html);
        }
    }

    /**
     * Inject a CSRF token into each form in the html page.
     */
    public function protectAndInject()
    {
        $forms = $this->doc->forms;
        if ($forms->length > 0) {
            $token = $this->tokenStore->generateNewToken();
            $this->tokenStore->saveToken($token);

            foreach ($forms as $form) {
                $csrfElement = $this->doc->createElement("input");
                $csrfElement->setAttribute("name", static::$TOKEN_NAME);
                $csrfElement->setAttribute("value", $token);
                $csrfElement->setAttribute("type", "hidden");
                $form->appendChild($csrfElement);
            }
        }
    }

    /**
     * Retrieve the injected html.
     *
     * @return HTMLDocument Note that this can be used as-is, or if you just
     * need to access the html as a string, call the HTMLDocument->saveHTML()
     * method.
     */
    public function getHTMLDocument() : HTMLDocument
    {
        return $this->doc;
    }
}#

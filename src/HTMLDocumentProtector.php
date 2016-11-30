<?php
namespace Gt\Csrf;

use phpgt\dom\HTMLDocument;

class HTMLDocumentProtector
{
    /**
     * Use this flag in the protectAndInject() method
     */
    const ONE_TOKEN_PER_PAGE = "PAGE";
    const ONE_TOKEN_PER_FORM = "FORM";
    /**
     * @var string The name to be used for the head meta tag and hidden html input fields used
     * to store the token in the page
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
     * @param            $tokenStore TokenStore The TokenStore implementation
     *                               to be used for generating and storing
     *                               tokens.
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
     * Inject a CSRF token into each form in the html page, and add a meta tag
     * to the header with a "content" attribute containing a token.
     *
     * The way the tokens are generated can be configured using the
     * $tokenSharing parameter:
     *
     * Specify self::ONE_TOKEN_PER_FORM if different
     * tokens should be used for each form on the page (and a different token again in the head
     * meta tag).  This is only required if multiple forms from a single page could be submitted
     * without reloading the page - using AJAX for example.  (note that the submitted token would
     * still be "spent", so the server response page should be parsed to lift out the new token and
     * inject it into the form that was just submitted.
     *
     * Specify self::ONE_TOKEN_PER_PAGE if the same token can be used for all
     * forms across the page and the head meta tag.  This is the default, and
     * is considerably more efficient than generating unique tokens.  In most
     * cases this default is suitable - wherever the normal model of returning
     * a new page in response to a form submit is used.
     *
     * @param $tokenSharing string Use either self::ONE_TOKEN_PER_PAGE (the
     *                      default) or self::ONE_TOKEN_PER_FORM, depending on
     *                      your requirements.
     */
    public function protectAndInject(string $tokenSharing = self::ONE_TOKEN_PER_PAGE)
    {
        $uniqueTokens = ($tokenSharing === self::ONE_TOKEN_PER_FORM);
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

                // generate and store a different token if necessary
                if ($uniqueTokens === true) {
                    $token = $this->tokenStore->generateNewToken();
                    $this->tokenStore->saveToken($token);
                }
            }
        } else {
            $token = $this->tokenStore->generateNewToken();
            $this->tokenStore->saveToken($token);
        }

        // now set the token in meta tag in the page head
        $meta = $this->doc->querySelector("head meta[name='" . self::$TOKEN_NAME . "']");
        if ($meta === null) {
            $meta = $this->doc->createElement("meta");
            $meta->setAttribute("name", self::$TOKEN_NAME);

            $head = $this->doc->querySelector("head");
            if($head === null) {
                $head = $this->doc->createElement("head");
                $this->doc->querySelector("html")->appendChild($head);
            }

            $head->appendChild($meta);
        }
        $meta->setAttribute("content", $token);
    }

    /**
     * Retrieve the injected html.
     *
     * @return HTMLDocument Note that this can be used as-is, or if you
     * want to access the html as a string call the HTMLDocument->saveHTML()
     * method.
     */
    public function getHTMLDocument() : HTMLDocument
    {
        return $this->doc;
    }
}#

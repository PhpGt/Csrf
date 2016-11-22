# Automatic protection from Cross-Site Request Forgery for PHP 7 projects.

***

<a href="https://gitter.im/phpgt/csrf" target="_blank">
    <img src="https://img.shields.io/gitter/room/phpgt/csrf.svg?style=flat-square" alt="Gitter chat" />
</a>
<a href="https://circleci.com/gh/phpgt/csrf" target="_blank">
    <img src="https://img.shields.io/circleci/project/phpgt/csrf/master.svg?style=flat-square" alt="Build status" />
</a>
<a href="https://scrutinizer-ci.com/g/phpgt/csrf" target="_blank">
    <img src="https://img.shields.io/scrutinizer/g/phpgt/csrf/master.svg?style=flat-square" alt="Code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/phpgt/csrf" target="_blank">
    <img src="https://img.shields.io/scrutinizer/coverage/g/phpgt/csrf/master.svg?style=flat-square" alt="Code coverage" />
</a>
<a href="https://packagist.org/packages/phpgt/csrf" target="_blank">
    <img src="https://img.shields.io/packagist/v/phpgt/csrf.svg?style=flat-square" alt="Current version" />
</a>

<wiki-marker-start name="intro" />

This library handles [CSRF](https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)) protection automatically for you — including generating tokens, injecting them into all forms in the page and then verifying that a valid token is present whenever a POST request is received.

<wiki-marker-end />

<wiki-marker-start name="usage" />

## Protection in Three Steps

The CSRF library does two things:

  * Injects CSRF tokens into `form`s
  * Verifies `POST` requests to make sure they contain a valid token

Each is just a single method call, but you need to set up first.


### Step 1: Set Up

Start by creating the TokenStore.  There is currently a single implementation — the `ArrayTokenStore`.  Because the `ArrayTokenStore` is not peristent, you need to save it between page requests so that tokens generated for one page request can be checked on another.  The easiest way to save it is to put it on the `Session`:

```php
use phpgt\csrf\ArrayTokenStore;

// check to see if there's already a token store for this session, and
// create one if not
if(!isset($_SESSION["phpgt/csrf/tokenstore"])) {
    $_SESSION["phpgt/csrf/tokenstore"] = new ArrayTokenStore();
}

$tokenStore = $_SESSION["phpgt/csrf/tokenstore"];
```


### Step 2: Verify

Before running any other code (especially things that could affect data), you should check to make sure that there's a valid CSRF token in place if it's needed.  That step is also very straightforward:

```php
use phpgt\csrf\exception\CSRFException;

try {
    $tokenStore->processAndVerify();

} catch(CSRFException $e) {
    // stop processing this request and get out of there!
}
```

If the request contains a POST and there is no valid CSRF token, a `CSRFException` will be thrown — so you should plan to catch it.  Remember, if that happens, the request was fraudulent so you shouldn't process it!


### Step 3: Inject for Next Time

Finally, once you've finished processing your html code and it's ready to send back to the client, you should inject the CSRF tokens.  If you don't, the request will fail to pass Step 2 when the page gets submitted!

```php
use phpgt\csrf\HTMLDocumentProtector;

// the html can come in as anything accepted by phpgt\dom - here it's a
// plain string in a variable
$htmlIn = "<html>...</html>";

// now do the processing
$page = new HTMLDocumentProtector($html, $tokenStore);
$page->protectAndInject();

// and you can get it back out however you wish.
$htmlOut = $page->getHTMLDocument()->saveHTML();
```

#### Using Tokens of a Different Length

By default, 32 character tokens are generated.  They use characters from the set \[a-zA-Z0-9\], meaning a 64-bit token which would take a brute-force attacker making 100,000 requests per second around 2.93 million years to guess.  If this seems either excessive or inadequate you can change the token length using `TokenStore::setTokenLength()`.


#### Special Note About AJAX Clients

Note that if several of the forms on your page could be submitted without reloading the page (which is uncommon, but could happen if you're using AJAX and not reloading the page using on the server response), you will want to call `$page->protectAndInject(HTMLDocumentProtector::TOKEN_PER_FORM);`, to have a unique token injected into each form.  This uses more server resources, and means there are far more unused tokens that could be guessed, but is unavoidable.  (Remember, if you'll still need to parse the new token for that form out of the page response and update the client-side form, otherwise a second submit would fail as the original token will have been spent.)

<wiki-marker-end />

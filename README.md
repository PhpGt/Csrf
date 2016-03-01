# phpgt csrf
Automatic protection from Cross-Site Request Forgery for PHP 7 projects

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

This library handles [CSRF](https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)) 
protection automatically for you - including generating tokens, injecting them 
into all forms in the page and then verifying that a valid token is present 
whenever a POST request is received.



## Protection in Three Steps

The CSRF library does two things:

  * Injects CSRF tokens into `form`s
  * Verifies `POST` requests to make sure they contain a valid token
  
Each is just a single method call, but you need to set up first.


### Step 1: Set Up

Start by creating the TokenStore.  There is currently a single implementation - 
the `ArrayTokenStore`.  Because the `ArrayTokenStore` is not peristent, you need 
to save it between page requests so that tokens generated for one page request 
can be checked on another.  The easiest way to save it is to put it on the 
`Session`:

```php
    // check to see if there's already a token store for this session, and 
    // create one if not
    if(!isset($_SESSION["phpgt/csrf/tokenstore"])) {
        $_SESSION["phpgt/csrf/tokenstore"] = new ArrayTokenStore();
    }
    
    $tokenStore = $_SESSION["phpgt/csrf/tokenstore"];
```

### Step 2: Verify

Before running any other code (especially things that could affect data), 
you should check to make sure that there's a valid CSRF token in place if it's 
needed.  That step is also very straightforward:

```php
    try {
        $tokenStore->processAndVerify();

    } catch(CSRFException $e) {
        // stop processing this request and get out of there!
    }
```

If the request contains a POST and there is no valid CSRF token, a 
`CSRFException` will be thrown - so you should plan to catch it.  Remember, if 
that happens, the request was fraudulent so you shouldn't process it!

### Step 3: Inject for Next Time

Finally, once you've finished processing your html code and it's ready to send 
back to the client, you should inject the CSRF tokens.  If you don't, the 
request will fail to pass Step 2 when the page gets submitted!

```php
    // the html can come in as anything accepted by phpgt\dom - here it's a 
    // plain string in a variable
    $htmlIn = "<html>...</html>"
    
    // now do the processing
    $page = new HTMLDocumentProtector($html, $tokenStore);
    $page->protectAndInject();
    
    // and you can get it back out however you wish.  
    $htmlOut = $page->getHTMLDocument()->saveHTML();
```


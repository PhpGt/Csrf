<?php
namespace phpgt\csrf;

use phpgt\csrf\exception\CSRFTokenInvalidException;
use phpgt\csrf\exception\CSRFTokenMissingException;
use phpgt\csrf\exception\CSRFTokenSpentException;
use RandomLib\Factory;
use SecurityLib\Strength;

/**
 * Class TokenStore
 * Extend this base class to create a store for CSRF tokens.  The core
 * functionality of generating the tokens is provided by the base class, but
 * can be overridden.
 *
 * @package phpgt\csrf
 */
abstract class TokenStore
{
    /**
     * @var int|null The maximum number of tokens to be retained.
     */
    public static $MAX_TOKENS  = 1000;
    private static $strength    = Strength::MEDIUM;
    private static $tokenLength = 64;
    private $tokenGenerator;

    /**
     * TokenStore constructor.
     *
     * @see TokenStore::$MAX_TOKENS the class property storing the maximum
     * tokens limit.
     *
     * @param int|null $maxTokens An optional limit to the number of valid
     *                            tokens the TokenStore will retain.
     *                            If not specified, an unlimited number of
     *                            tokens will be retained (which is probably
     *                            fine unless you have a very, very busy site
     *                            with long-running sessions).
     */
    public function __construct(int $maxTokens = null)
    {
        if ($maxTokens !== null) {
            self::$MAX_TOKENS = $maxTokens;
        }

        $factory = new Factory();
        $this->tokenGenerator = $factory->getGenerator(
            new Strength(self::$strength));
    }

    /**
     * Generate a new token.  NOTE: This method does NOT store the token.  To
     * do so
     *
     * @see TokenStore::saveToken() for storing a generated token.
     *
     * @return string The newly generated token.
     */
    public function generateNewToken() : string
    {
        return $this->tokenGenerator->generateString(
            self::$tokenLength);
    }

    /**
     * If a $_POST global exists, check that it contains a token and that the
     * token is valid.  The name the token is stored-under is contained in
     * @see HTMLDocumentProtector::$TOKEN_NAME.
     *
     * NOTE that the method will always either return true if everything is
     * ok, or it will throw an exception if not.
     *
     * @return bool True if the request should be expected.
     * @throws CSRFTokenMissingException There's a $_POST request present but no
     * token present
     * @throws CSRFTokenInvalidException There's a token included on the $_POST,
     * but its value is invalid.
     * @throws CSRFTokenSpentException  There's a token included on the
     * $_POST but it has already been consumed by a previous request.  @see
     * TokenStore::verifyToken().
     */
    public function processAndVerify() : bool
    {
        // expect the token to be present on ALL post requests
        if (!empty($_POST)) {
            if (!isset($_POST[ HTMLDocumentProtector::$TOKEN_NAME ])) {
                throw new CSRFTokenMissingException();
            }

            $this->verifyToken($_POST[ HTMLDocumentProtector::$TOKEN_NAME ]);
            $this->consumeToken($_POST[ HTMLDocumentProtector::$TOKEN_NAME ]);
        }

        return true;
    }

    /**
     * Save a token as valid for later verification
     *
     * @param string $token The token to be stored
     */
    abstract public function saveToken(string $token);

    /**
     * Mark a token as "used".
     *
     * @param string $token The token to consume
     */
    abstract public function consumeToken(string $token);

    /**
     * Checks that the token is valid (i.e. exists and has not been consumed
     * already).
     *
     * @param string $token  The token to be checked.
     *
     * @throws CSRFTokenInvalidException The token is invalid (i.e. is not
     * contained within the store).
     * @throws CSRFTokenSpentException The token has been consumed already. This
     * scenario might be handled differently by the web app in case the user
     * pressed submit twice in quick succession - instructing them
     * to refresh the page and resubmit their form for example.
     *
     * @return bool true if the token is valid
     */
    abstract public function verifyToken(string $token) : bool;
}#

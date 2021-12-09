<?php
namespace IMSGlobal\LTI;

use DivisionByZeroError;
use phpseclib\Crypt\RSA;
use \Firebase\JWT\JWT;
use UnexpectedValueException;

class JWKS_Endpoint {

    private $keys;

    /**
     * JWKS_Endpoint constructor
     * 
     * @param array $keys An associative array of kid => private key pairs 
     */
    public function __construct(array $keys) {
        $this->keys = $keys;
    }

    /**
     * Creates a new JWKS_Endpoint instance
     * 
     * Added to enable the builder pattern in PHP 5.5
     * 
     * @param array $keys An associative array of kid => private key pairs 
     * 
     * @return JWKS_Endpoint 
     */
    public static function newInstance(array $keys) {
        return new JWKS_Endpoint($keys);
    }

    /**
     * Created a JWKS_Endpoint based on an issuer registered in $database
     * 
     * @param Database    $database LTI Registration database
     * @param string      $issuer   LTI issuer ID
     * @param string|null $clientId LTI Optional LTI client id
     * 
     * @return JWKS_Endpoint 
     * 
     * @throws Exception If $issuer does not appear in registration database
     * @throws UnexpectedValueException If registration does not have a proper key id
     */
    public static function from_issuer(Database $database, $issuer, $clientId = null) {
        $registration = $database->find_registration_by_issuer($issuer, $clientId);
        return self::from_registration($registration);
    }

    /**
     * Creates a JWKS_Endpoint based on LTI_Registration
     * 
     * @param LTI_Registration $registration 
     * 
     * @return JWKS_Endpoint 
     * 
     * @throws UnexpectedValueException If registration does not have proper key id
     */
    public static function from_registration(LTI_Registration $registration) {
        return new JWKS_Endpoint([$registration->get_kid() => $registration->get_tool_private_key()]);
    }

    /**
     * Returns an associative array of unserialized JSON Web Keys
     * 
     * @return array 
     * 
     * @throws DivisionByZeroError If the math doesn't add up
     */
    public function get_public_jwks() {
        $jwks = [];
        foreach ($this->keys as $kid => $private_key) {
            $key = new RSA();
            $key->setHash("sha256");
            $key->loadKey($private_key);
            $key->setPublicKey(false, RSA::PUBLIC_FORMAT_PKCS8);
            if ( !$key->publicExponent ) {
                continue;
            }
            $components = [
                'kty' => 'RSA',
                'alg' => 'RS256',
                'use' => 'sig',
                'e' => JWT::urlsafeB64Encode($key->publicExponent->toBytes()),
                'n' => JWT::urlsafeB64Encode($key->modulus->toBytes()),
                'kid' => $kid,
            ];
            $jwks[] = $components;
        }
        return ['keys' => $jwks];
    }

    /**
     * Echoes the JSON encoded JSON Web Keys
     * 
     * @return void 
     * 
     * @throws DivisionByZeroError If the math doesn't add up
     */
    public function output_jwks() {
        echo json_encode($this->get_public_jwks());
    }
}

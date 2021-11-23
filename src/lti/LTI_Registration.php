<?php
namespace IMSGlobal\LTI;

use UnexpectedValueException;

class LTI_Registration {

    private $issuer;
    private $client_id;
    private $key_set_url;
    private $auth_token_url;
    private $auth_login_url;
    private $auth_server;
    private $tool_private_key;
    private $kid;

    /**
     * Creates a new LTI_Registration instance
     * 
     * Added to enable the builder pattern in PHP 5.5
     * 
     * @return LTI_Registration 
     */
    public static function newInstance() {
        return new self();
    }

    /**
     * Returns the registration issuer
     * 
     * @return string
     */
    public function get_issuer() {
        return $this->issuer;
    }

    /**
     * Set the LTI issuer
     * 
     * @param string $issuer Issuer identifier
     * 
     * @return $this 
     */
    public function set_issuer($issuer) {
        $this->issuer = $issuer;
        return $this;
    }

    /**
     * Returns the client id
     * 
     * @return string
     */
    public function get_client_id() {
        return $this->client_id;
    }

    /**
     * Sets the LTI client id
     * 
     * @param string $client_id Client id
     * 
     * @return $this 
     */
    public function set_client_id($client_id) {
        $this->client_id = $client_id;
        return $this;
    }

    /**
     * Gets the key set url
     * 
     * @return string
     */
    public function get_key_set_url() {
        return $this->key_set_url;
    }

    /**
     * Set the key set url
     * 
     * @param string $key_set_url Key set url
     * 
     * @return $this 
     */
    public function set_key_set_url($key_set_url) {
        $this->key_set_url = $key_set_url;
        return $this;
    }

    /**
     * Get the auth token url
     * 
     * @return string
     */
    public function get_auth_token_url() {
        return $this->auth_token_url;
    }

    /**
     * Sets the auth toke url
     * 
     * @param string $auth_token_url LTI auth token url
     * 
     * @return $this 
     */
    public function set_auth_token_url($auth_token_url) {
        $this->auth_token_url = $auth_token_url;
        return $this;
    }

    /**
     * Gets the auth login url
     * 
     * @return string
     */
    public function get_auth_login_url() {
        return $this->auth_login_url;
    }

    /**
     * Sets the auth login url
     * 
     * @param string $auth_login_url LTI auth login url
     * 
     * @return $this 
     */
    public function set_auth_login_url($auth_login_url) {
        $this->auth_login_url = $auth_login_url;
        return $this;
    }

    /**
     * Gets the auth server. Will return auth_token_url if auth_server is not set
     * 
     * @return string
     */
    public function get_auth_server() {
        return empty($this->auth_server) ? $this->auth_token_url : $this->auth_server;
    }

    /**
     * Sets the auth server
     * 
     * @param string $auth_server Auth server
     * 
     * @return $this 
     */
    public function set_auth_server($auth_server) {
        $this->auth_server = $auth_server;
        return $this;
    }

    /**
     * Gets the tool's ssl private key
     * 
     * @return string
     */
    public function get_tool_private_key() {
        return $this->tool_private_key;
    }

    /**
     * Sets the tool's ssl private key
     * 
     * @param string $tool_private_key SSL private key
     * 
     * @return $this 
     */
    public function set_tool_private_key($tool_private_key) {
        $this->tool_private_key = $tool_private_key;
        return $this;
    }

    /**
     * Gets the key id
     * 
     * @return string
     * 
     * @throws UnexpectedValueException If kid or issuer and client properties are not set
     */
    public function get_kid() {
        if (empty($this->kid) && (empty($this->issuer) || empty($this->client_id))) {
            throw new \UnexpectedValueException('kid or issuer and client_id must be set');
        }
        return empty($this->kid) ? hash('sha256', trim($this->issuer . $this->client_id)) : $this->kid;
    }

    /**
     * Sets the key id
     * 
     * @param string $kid Key id
     * 
     * @return $this 
     */
    public function set_kid($kid) {
        $this->kid = $kid;
        return $this;
    }

}

?>
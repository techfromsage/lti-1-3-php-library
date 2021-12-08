<?php
namespace IMSGlobal\LTI;

class Cookie {
    /**
     * Get a cookie, if defined. Will look for the $name prefixed with "LEGACY_" if not found
     * 
     * @param string $name Cookie name
     * 
     * @return mixed|boolean 
     */
    public function get_cookie($name) {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        // Look for backup cookie if same site is not supported by the user's browser.
        if (isset($_COOKIE["LEGACY_" . $name])) {
            return $_COOKIE["LEGACY_" . $name];
        }
        return false;
    }

    /**
     * Sets a cookie
     * 
     * @param string  $name    Cookie name
     * @param mixed   $value   Cookie value
     * @param integer $exp     Time to live
     * @param array   $options set_cookie options
     * 
     * @return $this 
     */
    public function set_cookie($name, $value, $exp = 3600, array $options = []) {
        $cookie_options = [
            'expires' => time() + $exp
        ];

        // SameSite none and secure will be required for tools to work inside iframes
        $same_site_options = [
            'samesite' => 'None',
            'secure' => true
        ];

        self::setcookie73($name, $value, array_merge($cookie_options, $same_site_options, $options));

        // Set a second fallback cookie in the event that "SameSite" is not supported
        self::setcookie73("LEGACY_" . $name, $value, array_merge($cookie_options, $options));
        return $this;
    }

    /**
     * Add support for the PHP7.3+ `setcookie` with options, in a <PHP7.3-friendly way
     * 
     *  @param string $name    The name of the cookie to set
     *  @param mixed  $value   The cookie value 
     *  @param array  $options Cookie options
     * 
     * @return void
     */
    private static function setcookie73($name, $value, array $options) {
        $expires = isset($options['expires']) ? $options['expires'] : 0;
        $path = isset($options['path']) ? $options['path'] : '/';
        $domain = isset($options['domain']) ? $options['domain'] : '';
        $secure = isset($options['secure']) ? $options['secure'] : false;
        $httponly = isset($options['httponly']) ? $options['httponly'] : false;

        // samesite can only be represented as a hack before PHP7.3
        $samesite = isset($options['samesite']) ? $options['samesite'] : null;
        $pathWithSamesiteHack = is_null($samesite) ? $path : "$path; SameSite=$samesite";

        setcookie($name, $value, $expires, $pathWithSamesiteHack, $domain, $secure, $httponly);
    }
}

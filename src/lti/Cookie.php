<?php
namespace IMSGlobal\LTI;

class Cookie {
    /**
     * Get a cookie, if defined. Will look for the $name prefixed with "LEGACY_" if not found
     *
     * @param string $name Cookie name
     *
     * @return mixed|false Cookie value or false if not found
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

        setcookie($name, $value, array_merge($cookie_options, $same_site_options, $options));

        // Set a second fallback cookie in the event that "SameSite" is not supported
        setcookie("LEGACY_" . $name, $value, array_merge($cookie_options, $options));
        return $this;
    }
}

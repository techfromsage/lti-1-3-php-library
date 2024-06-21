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
        // Look for backup cookie partitioned if same site is not supported by the user's browser.
        if (isset($_COOKIE["PARTITIONED_" . $name])) {
            return $_COOKIE["PARTITIONED_" . $name];
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
            'secure' => true,
            'partitioned' => true,
        ];

        $merged_options = array_merge($cookie_options, $same_site_options, $options);

        setcookie($name, $value, $merged_options);

        // Set a second fallback cookie in the event that "SameSite" is not supported
        setcookie("LEGACY_" . $name, $value, array_merge($cookie_options, $options));

        // PHP does not support the 'partitioned' flag, so we need to manually set a partitioned cookie header
        header('Set-Cookie: ' . rawurlencode("PARTITIONED_" . $name) . '=' . rawurlencode($value)
                          . (empty($merged_options['expires']) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', $merged_options['expires']) . ' GMT')
                          . (empty($merged_options['path']) ? '' : '; path=' . $merged_options['path'])
                          . (empty($merged_options['domain']) ? '' : '; domain=' . $merged_options['domain'])
                          . (empty($merged_options['samesite']) ? '' : '; samesite=' . $merged_options['samesite'])
                          . (empty($merged_options['secure']) ? '' : '; secure')
                          . (empty($merged_options['partitioned']) ? '' : '; partitioned')
                          . (empty($merged_options['http_only']) ? '' : '; HttpOnly'), false);
        return $this;
    }
}

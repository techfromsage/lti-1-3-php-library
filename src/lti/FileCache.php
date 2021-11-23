<?php
namespace IMSGlobal\LTI;

/**
 * An example, file-based, cache class that implements the Cache interface
 * 
 * @package IMSGlobal\LTI
 */
class FileCache implements Cache {

    private $cache;

    /**
     * Get the LTI launch data JWT from cache
     * 
     * @param string $key 
     * 
     * @return string 
     */
    public function get_launch_data($key) {
        $this->load_cache();
        return $this->cache[$key];
    }

    /**
     * Cache the LTI launch JWT
     * 
     * @param string $key 
     * @param string $jwt_body 
     * 
     * @return Cache 
     */
    public function cache_launch_data($key, $jwt_body) {
        $this->cache[$key] = $jwt_body;
        $this->save_cache();
        return $this;
    }

    /**
     * Set the LTI launch nonce
     * 
     * @param string $nonce 
     * 
     * @return Cache 
     */
    public function cache_nonce($nonce) {
        $this->cache['nonce'][$nonce] = true;
        $this->save_cache();
        return $this;
    }

    /**
     * Ensure the validity of the nonce
     * 
     * @param string $nonce 
     * 
     * @return boolean
     */
    public function check_nonce($nonce) {
        $this->load_cache();
        if (!isset($this->cache['nonce'][$nonce])) {
            return false;
        }
        return true;
    }

    /**
     * Load the cache from file
     * 
     * @return void 
     */
    private function load_cache() {
        $cache = file_get_contents(sys_get_temp_dir() . '/lti_cache.txt');
        if (empty($cache)) {
            file_put_contents(sys_get_temp_dir() . '/lti_cache.txt', '{}');
            $this->cache = [];
        }
        $this->cache = json_decode($cache, true);
    }

    /**
     * Save to the file cache
     * 
     * @return void 
     */
    private function save_cache() {
        file_put_contents(sys_get_temp_dir() . '/lti_cache.txt', json_encode($this->cache));
    }
}
?>
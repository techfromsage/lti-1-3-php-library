<?php

namespace IMSGlobal\LTI;

interface Cache {
    /**
     * Returns the cached launch data JWT body, if it exists
     * 
     * @param string $key  
     * 
     * @return string JWT body 
     */
    public function get_launch_data($key);

    /**
     * Stores the lauch data JWT body
     * 
     *  @param string $key 
     *  @param string $jwt_body 
     * 
     * @return $this
     */
    public function cache_launch_data($key, $jwt_body);

    /**
     * Stores the request nonce
     * 
     * @param string $nonce 
     * 
     * @return $this
     */
    public function cache_nonce($nonce);

    /**
     * Checks the validity of the nonce
     * 
     * @param string $nonce 
     * 
     * @return boolean 
     */
    public function check_nonce($nonce);
}
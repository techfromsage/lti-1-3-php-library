<?php

namespace IMSGlobal\LTI\Tests\unit\helpers;

use IMSGlobal\LTI\Cache;

class InMemoryCache implements Cache {

    private $data = ['nonce' => []];

    public function get_launch_data($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function cache_launch_data($key, $jwt_body)
    {
        $this->data[$key] = $jwt_body;
    }

    public function cache_nonce($nonce)
    {
        $this->data['nonce'][$nonce] = true;
    }

    public function check_nonce($nonce)
    {
        return isset($this->data['nonce'][$nonce]);
    }
}
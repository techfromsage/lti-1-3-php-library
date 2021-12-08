<?php
namespace IMSGlobal\LTI;

class LTI_Deployment {

    /** @var string */ 
    private $deployment_id;

    /**
     * Creates a new LTI_Deployment instance
     * 
     * Added to enable the builder pattern in PHP 5.5
     * 
     * @return LTI_Deployment 
     */
    public static function newInstance() {
        return new LTI_Deployment();
    }

    /**
     * Set the deployment id
     * 
     * @return string
     */
    public function get_deployment_id() {
        return $this->deployment_id;
    }

    /**
     * Get the deployment id
     * 
     * @param string $deployment_id Deployment id
     * 
     * @return $this 
     */
    public function set_deployment_id($deployment_id) {
        $this->deployment_id = $deployment_id;
        return $this;
    }

}

?>
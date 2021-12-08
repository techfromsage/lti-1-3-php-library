<?php
namespace IMSGlobal\LTI;

interface Database {

    /**
     * Find an LTI_Registration based on the issuer and client id
     * 
     * @param string      $iss      Issuer identifier
     * @param string|null $clientId Client id
     * 
     * @return LTI_Registration 
     */
    public function find_registration_by_issuer($iss, $clientId = null);

    /**
     * Find an LTI_Deployment based on the issuer and deployment id
     * 
     * @param string $iss           Issuer identifier
     * @param string $deployment_id Deployment id
     * 
     * @return LTI_Deployment
     */    
    public function find_deployment($iss, $deployment_id);
}

?>
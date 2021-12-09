<?php
namespace IMSGlobal\LTI;

use DomainException;
use \Firebase\JWT\JWT;
use UnexpectedValueException;

class LTI_Deep_Link {

    /** @var LTI_Registration */
    private $registration;
    /** @var string */
    private $deployment_id;
    /** @var array */
    private $deep_link_settings;

    /**
     * LTI_Deep_Link constructor
     * 
     * @param LTI_Registration $registration       LTI registration
     * @param string           $deployment_id      Deployment id
     * @param array            $deep_link_settings Deep link settings
     * 
     * @return void 
     */
    public function __construct(LTI_Registration $registration, $deployment_id, array $deep_link_settings) {
        $this->registration = $registration;
        $this->deployment_id = $deployment_id;
        $this->deep_link_settings = $deep_link_settings;
    }

    /**
     * Generates the deep link response JWT
     * 
     * @param LTI_Deep_Link_Resource[] $resources Deep link resources
     * 
     * @return string 
     * 
     * @throws UnexpectedValueException If unable to return a key id for the registration
     * @throws DomainException If unable to encode or sign JWT
     */
    public function get_response_jwt(array $resources) {
        $message_jwt = [
            "iss" => $this->registration->get_client_id(),
            "aud" => [$this->registration->get_issuer()],
            "exp" => time() + 600,
            "iat" => time(),
            "nonce" => 'nonce' . hash('sha256', random_bytes(64)),
            "https://purl.imsglobal.org/spec/lti/claim/deployment_id" => $this->deployment_id,
            "https://purl.imsglobal.org/spec/lti/claim/message_type" => "LtiDeepLinkingResponse",
            "https://purl.imsglobal.org/spec/lti/claim/version" => "1.3.0",
            "https://purl.imsglobal.org/spec/lti-dl/claim/content_items" => array_map(
                function($resource) { return $resource->to_array(); }, 
                $resources
            ),
            "https://purl.imsglobal.org/spec/lti-dl/claim/data" => $this->deep_link_settings['data'],
        ];
        return JWT::encode(
            $message_jwt, 
            $this->registration->get_tool_private_key(), 
            'RS256', 
            $this->registration->get_kid()
        );
    }

    /**
     * Generates an auto submitting form for the deep link response
     * 
     * @param LTI_Deep_Link_Resource[] $resources Deep link resources
     * 
     * @return void 
     * 
     * @uses get_response_jwt
     */
    public function output_response_form(array $resources) {
        $jwt = $this->get_response_jwt($resources);
        ?>
        <form id="auto_submit" action="<?= $this->deep_link_settings['deep_link_return_url']; ?>" method="POST">
            <input type="hidden" name="JWT" value="<?= $jwt ?>" />
            <input type="submit" name="Go" />
        </form>
        <script>
            document.getElementById('auto_submit').submit();
        </script>
        <?php
    }
}

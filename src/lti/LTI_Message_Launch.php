<?php
namespace IMSGlobal\LTI;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

JWT::$leeway = 5;

class LTI_Message_Launch {

    private $db;
    private $cache;
    private $request;
    private $cookie;
    private $jwt;
    private $registration;
    private $launch_id;
    private $defaultAlgorithm = 'RS256';

    /**
     * Constructor
     *
     * @param Database $database Instance of the database interface used for looking up registrations and deployments.
     * @param Cache    $cache    Instance of the Cache interface used to loading and storing launches. If none is provided launch data will be stored in FileCache.
     * @param Cookie   $cookie   Instance of Cookie used to set and read cookies.
     */
    public function __construct(Database $database, Cache $cache = null, Cookie $cookie = null) {
        $this->db = $database;

        $this->launch_id = uniqid('lti1p3_launch_', true);

        if ($cache === null) {
            $cache = new FileCache();
        }
        $this->cache = $cache;

        if ($cookie === null) {
            $cookie = new Cookie();
        }
        $this->cookie = $cookie;
    }

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     *
     * @param Database $database Instance of the database interface used for looking up registrations and deployments.
     * @param Cache    $cache    Instance of the Cache interface used to loading and storing launches. If none is provided launch data will be stores in FileCache.
     * @param Cookie   $cookie   Instance of Cookie used to set and read cookies.
     * 
     * @return LTI_Message_Launch
     */
    public static function newInstance(Database $database, Cache $cache = null, Cookie $cookie = null) {
        return new LTI_Message_Launch($database, $cache, $cookie);
    }

    /**
     * Load an LTI_Message_Launch from a Cache using a launch id.
     *
     * @param string    $launch_id  The launch id of the LTI_Message_Launch object that is being pulled from the cache.
     * @param Database  $database   Instance of the database interface used for looking up registrations and deployments.
     * @param Cache     $cache      Instance of the Cache interface used to loading and storing launches. If non is provided launch data will be store in $_SESSION.
     *
     * @throws LTI_Exception        Will throw an LTI_Exception if validation fails or launch cannot be found.
     * @return LTI_Message_Launch   A populated and validated LTI_Message_Launch.
     */
    public static function from_cache($launch_id, Database $database, Cache $cache = null) {
        $new = new LTI_Message_Launch($database, $cache, null);
        $new->launch_id = $launch_id;
        $new->jwt = [ 'body' => $new->cache->get_launch_data($launch_id) ];
        return $new->validate_registration();
    }

    /**
     * Validates all aspects of an incoming LTI message launch and caches the launch if successful.
     *
     * @param array|string  $request    An array of post request parameters. If not set will default to $_POST.
     *
     * @throws LTI_Exception        Will throw an LTI_Exception if validation fails.
     * @return LTI_Message_Launch   Will return $this if validation is successful.
     */
    public function validate(array $request = null) {

        if ($request === null) {
            $request = $_POST;
        }
        $this->request = $request;

        return $this->validate_state()
            ->validate_jwt_format()
            ->validate_nonce()
            ->validate_registration()
            ->validate_jwt_signature()
            ->validate_deployment()
            ->validate_message()
            ->cache_launch_data();
    }

    /**
     * Returns whether or not the current launch can use the names and roles service.
     *
     * @return boolean  Returns a boolean indicating the availability of names and roles.
     */
    public function has_nrps() {
        return !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice']['context_memberships_url']);
    }

    /**
     * Fetches an instance of the names and roles service for the current launch.
     *
     * @return LTI_Names_Roles_Provisioning_Service An instance of the names and roles service that can be used to make calls within the scope of the current launch.
     */
    public function get_nrps() {
        return new LTI_Names_Roles_Provisioning_Service(
            new LTI_Service_Connector($this->registration),
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice']);
    }

    /**
     * Returns whether or not the current launch can use the groups service.
     *
     * @return boolean  Returns a boolean indicating the availability of groups.
     */
    public function has_gs() {
        return !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti-gs/claim/groupsservice']['context_groups_url']);
    }

    /**
     * Fetches an instance of the groups service for the current launch.
     *
     * @return LTI_Course_Groups_Service An instance of the groups service that can be used to make calls within the scope of the current launch.
     */
    public function get_gs() {
        return new LTI_Course_Groups_Service(
            new LTI_Service_Connector($this->registration),
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-gs/claim/groupsservice']);
    }

    /**
     * Returns whether or not the current launch can use the assignments and grades service.
     *
     * @return boolean  Returns a boolean indicating the availability of assignments and grades.
     */
    public function has_ags() {
        return !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint']);
    }

    /**
     * Fetches an instance of the assignments and grades service for the current launch.
     *
     * @return LTI_Assignments_Grades_Service An instance of the assignments an grades service that can be used to make calls within the scope of the current launch.
     */
    public function get_ags() {
        return new LTI_Assignments_Grades_Service(
            new LTI_Service_Connector($this->registration),
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint']);
    }

    /**
     * Fetches a deep link that can be used to construct a deep linking response.
     *
     * @return LTI_Deep_Link An instance of a deep link to construct a deep linking response for the current launch.
     */
    public function get_deep_link() {
        return new LTI_Deep_Link(
            $this->registration,
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/deployment_id'],
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings']);
    }

    /**
     * Returns whether or not the current launch is a deep linking launch.
     *
     * @return boolean  Returns true if the current launch is a deep linking launch.
     */
    public function is_deep_link_launch() {
        return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'] === 'LtiDeepLinkingRequest';
    }

    /**
     * Returns whether or not the current launch is a submission review launch.
     *
     * @return boolean  Returns true if the current launch is a submission review launch.
     */
    public function is_submission_review_launch() {
        return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'] === 'LtiSubmissionReviewRequest';
    }

    /**
     * Returns whether or not the current launch is a resource launch.
     *
     * @return boolean  Returns true if the current launch is a resource launch.
     */
    public function is_resource_launch() {
        return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'] === 'LtiResourceLinkRequest';
    }

    /**
     * Fetches the decoded body of the JWT used in the current launch.
     *
     * @return array|object Returns the decoded json body of the launch as an array.
     */
    public function get_launch_data() {
        return $this->jwt['body'];
    }

    /**
     * Get the unique launch id for the current launch.
     *
     * @return string   A unique identifier used to re-reference the current launch in subsequent requests.
     */
    public function get_launch_id() {
        return $this->launch_id;
    }

    protected function get_public_key() {
        $key_set_url = $this->registration->get_key_set_url();

        $opts = [
            'http' => ['header' => 'User-Agent:lti-1-3-php-library']
        ];

        $context = stream_context_create($opts);

        // Download key set
        $public_key_set = json_decode(file_get_contents($key_set_url, false, $context), true);

        if (empty($public_key_set)) {
            // Failed to fetch public keyset from URL.
            throw new LTI_Public_Key_Exception('Failed to fetch public key');
        }

        // Find key used to sign the JWT (matches the KID in the header)
        foreach ($public_key_set['keys'] as $key) {
            if ($key['kid'] == $this->jwt['header']['kid']) {
                try {
                    return JWK::parseKey($key, $this->defaultAlgorithm);
                } catch(\Exception $e) {
                    return false;
                }
            }
        }

        // Could not find public key with a matching kid and alg.
        throw new LTI_Public_Key_Exception('Unable to find public key');
    }

    private function cache_launch_data() {
        $this->cache->cache_launch_data($this->launch_id, $this->jwt['body']);
        return $this;
    }

    private function validate_state() {
        // Check State for OIDC.
        $expectedState = $this->cookie->get_cookie('lti1p3_' . $this->request['state']);
        if (empty($expectedState) || $expectedState !== $this->request['state']) {
            // Error if state doesn't match
            throw new LTI_No_State_Found_Exception('State not found');
        }
        return $this;
    }

    private function validate_jwt_format() {
        $jwt = $this->request['id_token'];

        if (empty($jwt)) {
            throw new LTI_JWT_Exception('Missing id_token');
        }

        // Get parts of JWT.
        $jwt_parts = explode('.', $jwt);

        if (count($jwt_parts) !== 3) {
            // Invalid number of parts in JWT.
            throw new LTI_JWT_Exception('Invalid id_token, JWT must contain 3 parts');
        }

        // Decode JWT headers.
        $this->jwt['header'] = json_decode(JWT::urlsafeB64Decode($jwt_parts[0]), true);
        // Decode JWT Body.
        $this->jwt['body'] = json_decode(JWT::urlsafeB64Decode($jwt_parts[1]), true);

        return $this;
    }

    private function validate_nonce() {
        if (!$this->cache->check_nonce($this->jwt['body']['nonce'])) {
            //throw new LTI_Exception("Invalid Nonce");
        }
        return $this;
    }

    private function validate_registration() {
        if (empty($this->jwt['body']['iss'])) {
            throw new LTI_Registration_Exception('Invalid issuer');
        }

        $client_id = $this->get_client_id_from_jwt();

        if (empty($client_id)) {
            throw new LTI_Registration_Exception('Invalid client id');
        }
        
        // Find registration.
        $this->registration = $this->db->find_registration_by_issuer($this->jwt['body']['iss'], $client_id);

        if (empty($this->registration)) {
            throw new LTI_Registration_Exception('Registration not found.');
        }
        // Check client id.
        if ( $client_id !== $this->registration->get_client_id()) {
            // Client not registered.
            throw new LTI_Registration_Exception('Client id not registered for this issuer');
        }

        return $this;
    }

    private function validate_jwt_signature() {
        // Fetch public key. Returns a Key object
        $public_key = $this->get_public_key();

        // Validate JWT signature
        try {
            JWT::decode($this->request['id_token'], $public_key);
        } catch(\Exception $e) {
            // Error validating signature.
            throw new LTI_JWT_Exception('Invalid signature on id_token');
        }

        return $this;
    }

    private function validate_deployment() {
        if (empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/deployment_id'])) {
            throw new LTI_Registration_Exception('Invalid deployment');
        } 
        
        // Find deployment.
        $deployment = $this->db->find_deployment(
            $this->jwt['body']['iss'], 
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/deployment_id']
        );

        if (empty($deployment)) {
            // deployment not recognized.
            throw new LTI_Registration_Exception('Unable to find deployment');
        }

        return $this;
    }

    private function validate_message() {
        if (empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'])) {
            // Unable to identify message type.
            throw new LTI_Message_Validation_Exception('Invalid message type');
        }

        // Do message type validation

        // Import all validators
        $this->import_validators();

        // Create instances of all validators
        $classes = get_declared_classes();
        $validators = [];
        foreach ($classes as $class_name) {
            // Check the class implements message validator
            $reflect = new \ReflectionClass($class_name);
            if ($reflect->implementsInterface('\IMSGlobal\LTI\Message_Validator')) {
                // Create instance of class
                $validators[] = new $class_name();
            }
        }

        $message_validator = false;
        foreach ($validators as $validator) {
            if ($validator->can_validate($this->jwt['body'])) {
                if ($message_validator !== false) {
                    // Can't have more than one validator apply at a time.
                    throw new LTI_Message_Validation_Exception('Validator conflict');
                }
                $message_validator = $validator;
            }
        }

        if ($message_validator === false) {
            throw new LTI_Message_Validation_Exception('Unrecognized message type.');
        }

        if (!$message_validator->validate($this->jwt['body'])) {
            throw new LTI_Message_Validation_Exception('Message validation failed.');
        }

        return $this;

    }

    protected function import_validators()
    {
        foreach (glob(__DIR__ . '/message_validators/*.php') as $filename) {
            include_once $filename;
        }
    }

    private function get_client_id_from_jwt()
    {
        if (isset($this->jwt['body']['aud'])) {
            return is_array($this->jwt['body']['aud']) ? $this->jwt['body']['aud'][0] : $this->jwt['body']['aud'];
        }
        return null;
    }
}

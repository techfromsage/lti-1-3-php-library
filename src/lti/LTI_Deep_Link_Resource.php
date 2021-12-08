<?php
namespace IMSGlobal\LTI;

class LTI_Deep_Link_Resource {

    private $type = 'ltiResourceLink';
    /** @var string */
    private $title;
    /** @var string */
    private $url;
    /** @var LTI_LineItem */
    private $lineitem;
    private $custom_params = [];
    private $target = 'iframe';

    /**
     * Creates a new LTI_Deep_Link_Resource
     * 
     * Added to enable the builder pattern in PHP 5.5
     * 
     * @return LTI_Deep_Link_Resource 
     */
    public static function newInstance() {
        return new LTI_Deep_Link_Resource();
    }

    /**
     * Get the deep link resource type
     * 
     * @return string 
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Set the deep link resource type
     * 
     * @param string $value 
     * 
     * @return $this 
     */
    public function set_type($value) {
        $this->type = $value;
        return $this;
    }

    /**
     * Get the deep link resource title
     * 
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Set the deep link resource type
     * 
     * @param string $value 
     * 
     * @return $this 
     */
    public function set_title($value) {
        $this->title = $value;
        return $this;
    }

    /**
     * Get the deep link resource url
     * 
     * @return string
     */
    public function get_url() {
        return $this->url;
    }

    /**
     * Set the deep link resource url
     * 
     * @param string $value 
     * 
     * @return $this 
     */
    public function set_url($value) {
        $this->url = $value;
        return $this;
    }

    /**
     * Get the deep link resource line item
     * 
     * @return LTI_Lineitem 
     */
    public function get_lineitem() {
        return $this->lineitem;
    }

    /**
     * Set the deep link resource line item
     * 
     * @param LTI_Lineitem $value 
     * 
     * @return $this 
     */
    public function set_lineitem(LTI_Lineitem $value) {
        $this->lineitem = $value;
        return $this;
    }

    /**
     * Get the deep link resource custom parameters
     * 
     * @return array 
     */
    public function get_custom_params() {
        return $this->custom_params;
    }

    /**
     * Set the deep link resource custom parameters
     * 
     * @param array $value 
     * 
     * @return $this 
     */
    public function set_custom_params(array $value) {
        $this->custom_params = $value;
        return $this;
    }

    /**
     * Get the deep link resource target
     * 
     * @return string 
     */
    public function get_target() {
        return $this->target;
    }

    /**
     * Set the deep link resource target
     * 
     * @param string $value 
     * 
     * @return $this 
     */
    public function set_target($value) {
        $this->target = $value;
        return $this;
    }

    /**
     * Return the deep link resource properties as an array
     * 
     * @return array 
     */
    public function to_array() {
        $resource = [
            "type" => $this->type,
            "title" => $this->title,
            "url" => $this->url,
            "presentation" => [
                "documentTarget" => $this->target,
            ],
            "custom" => $this->custom_params,
        ];
        if ($this->lineitem !== null) {
            $resource["lineItem"] = [
                "scoreMaximum" => $this->lineitem->get_score_maximum(),
                "label" => $this->lineitem->get_label(),
            ];
        }
        return $resource;
    }
}
?>

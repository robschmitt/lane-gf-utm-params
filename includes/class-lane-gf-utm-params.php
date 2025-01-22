<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Lane_GF_UTM_Params {

    // Singleton instance
    private static $instance = null;

    private array $utm_params = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

	private function __construct() {
        
        if (!session_id()) {
            session_start();
        }

        // Checks for UTM parameters on the current URL and stores them as session variables
        add_action( 'init', [ $this, 'capture_utm_parameters' ] );

        // Add the UTM fields to the forms where the UTM functionality is enabled
        add_filter( 'gform_pre_render', [ $this, 'add_utm_fields' ] );
        add_filter( 'gform_pre_submission_filter', [ $this, 'add_utm_fields' ] );
        add_filter( 'gform_admin_pre_render', [ $this, 'add_utm_fields' ] );

        // Add the UTM setting to the Form Options box on the form settings page
        add_filter('gform_form_settings_fields', [ $this, 'add_utm_form_settings' ], 10, 2);

        add_filter('gform_pre_form_settings_save', [$this, 'handle_settings_save']);

        // Set the UTM hidden form field values from the stored session variables
        foreach ($this->utm_params as $param) {
            add_filter( 'gform_field_value_' . $this->get_param_field_name($param),  [ $this, 'prepopulate_field_value' ] );
        }

	}

    /**
     * @param $value
     * @return mixed
     * Gravity Forms filter callback that sets the UTM hidden form field values
     * from the stored session variables.
     */
    function prepopulate_field_value( $value ) {
        $utm_param = str_replace('gform_field_value_lane_gf_', '', current_filter());
        if (isset($_SESSION[$utm_param])) {
            return $_SESSION[$utm_param];
        }
        return $value;
    }

    /**
     * @param $fields
     * @param $form
     * @return array
     * Gravity Forms filter callback that adds the UTM setting to the Form Options
     * box on the form settings page.
     */
    function add_utm_form_settings($fields, $form) {
        $fields['form_options']['fields'][] = [
            'name'          => 'lane_gf_utm_params_enabled',
            'type'          => 'toggle',
            'label'         => __('UTM Parameters'),
            'description'   => __('Enable UTM parameter tracking for this form'),
            'default_value' => false,
            'tooltip'       => __('When enabled, UTM parameters will be captured and stored with this form\'s submissions.')
        ];
        return $fields;
    }

    /**
     * @param $form_id
     * @return bool
     * Is tracking enabled for a particular form?
     */
    public function is_tracking_enabled($form_id) {
        return (bool)get_option('lane_gf_utm_tracking_enabled_form_' . (int)$form_id, false);
    }

    function handle_settings_save($form) {

        $form_id = (int)$form['id'];

        $is_enabled = rgar($form, 'lane_gf_utm_params_enabled') === '1';

        update_option('lane_gf_utm_tracking_enabled_form_' . $form_id, $is_enabled);

        return $form;

    }

    public function get_param_field_name($param) {
        return 'lane_gf_' . $param;
    }

    /**
     * @param $form
     * @return array|mixed
     * Gravity Forms filter callback that adds the UTM fields to the forms
     * where the UTM functionality is enabled.
     */
    public function add_utm_fields($form) {

        $form_id = (int) $form['id'];

        if (!$this->is_tracking_enabled($form_id)) {
            return $form;
        }

        // Get the highest field ID currently in the form
        $max_field_id = 0;
        foreach ($form['fields'] as $field) {
            if ($field->id > $max_field_id) {
                $max_field_id = $field->id;
            }
        }

        // Add hidden fields for each UTM parameter
        foreach ($this->utm_params as $param) {
            $max_field_id++;

            // Check if field already exists to avoid duplicates
            $field_exists = false;
            foreach ($form['fields'] as $field) {
                if ($field->inputName === $this->get_param_field_name($param)) {
                    $field_exists = true;
                    break;
                }
            }

            if (!$field_exists) {
                $hidden_field = new GF_Field_Hidden();
                $hidden_field->id = $max_field_id;
                $hidden_field->formId = $form['id'];
                $hidden_field->label = $param;
                $hidden_field->inputName = $this->get_param_field_name($param);
                $hidden_field->allowsPrepopulate = true;

                $form['fields'][] = $hidden_field;
            }
        }

        return $form;
    }

    /**
     * @param $form_id
     * @return bool
     * Helper function to check if UTM tracking is enabled for a specific form
     */
    public function is_lane_gf_utm_params_enabled($form_id) {
        $form = GFAPI::get_form($form_id);
        return rgar($form, 'lane_gf_utm_params_enabled') === '1';
    }

    /**
     * @return void
     * Action callback that checks for UTM paramaters on the current URL
     * and stores them as session variables.
     */
    public function capture_utm_parameters() {
        // Check for `utm_*` parameters in the URL and store them in the session
        foreach ($this->utm_params as $key) {
            if (isset($_GET[$key])) {
                $_SESSION[$key] = sanitize_text_field($_GET[$key]);
            }
        }
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() {}

}
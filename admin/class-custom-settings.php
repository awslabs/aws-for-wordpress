<?php

/**
 * This file defines functions to add custom fields
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */

class AmazonAI_Settings
{
    public $settings = array();
    public $sections = array();
    public $fields = array();

    public function set_settings( array $settings )
    {
        $this->settings = $settings;

        return $this;
    }

    public function set_sections( array $sections )
    {
        $this->sections = $sections;

        return $this;
    }

    public function set_fields( array $fields )
    {
        $this->fields = $fields;

        return $this;
    }

    public function register_custom_fields()
    {
        // Register settings
        foreach ( $this->settings as $setting ) {
            register_setting( $setting["option_group"], $setting["option_name"], ( isset( $setting["callback"] ) ? $setting["callback"] : '' ) );
        }

        // Add settings section
        foreach ( $this->sections as $section ) {
            add_settings_section( $section["id"], $section["title"], ( isset( $section["callback"] ) ? $section["callback"] : '' ), $section["page"] );
        }

        // Add settings field
        foreach ( $this->fields as $field ) {
            add_settings_field( $field["id"], $field["title"], ( isset( $field["callback"] ) ? $field["callback"] : '' ), $field["page"], $field["section"], ( isset( $field["args"] ) ? $field["args"] : '' ) );
        }
    }

}
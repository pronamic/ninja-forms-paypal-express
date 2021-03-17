<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_PayPalExpress_Admin_Settings
 */
final class NF_PayPalExpress_Admin_Settings
{
    public function __construct()
    {
        add_filter( 'ninja_forms_plugin_settings',                  array( $this, 'plugin_settings'             ), 10, 1 );
        add_filter( 'ninja_forms_plugin_settings_groups',           array( $this, 'plugin_settings_groups'      ), 10, 1 );
    }

    public function plugin_settings( $settings )
    {
        $settings[ 'paypal_express' ] = NF_PayPalExpress()->config( 'PluginSettings' );

        return $settings;
    }

    public function plugin_settings_groups( $groups )
    {
        $groups = array_merge( $groups, NF_PayPalExpress()->config( 'PluginSettingsGroups' ) );
        return $groups;
    }

} // End Class NF_PayPalExpress_Admin_Settings

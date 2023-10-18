<?php
/**
 * Plugin Name:     Greeting Plugin
 * Plugin URI:      https://github.com/mevolkan/
 * Description:     This is a greeting plugin with Oauth2
 * Author:          volkan
 * Author URI:      https://github.com/mevolkan/
 * Text Domain:     greeting-plugin
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Greeting_Plugin
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/src/wp-rest-api-auth0.php';

// Database table creation on activation.
register_activation_hook(__FILE__, 'greeting_plugin_activate');
register_deactivation_hook(__FILE__, 'greeting_plugin_deactivate');

function greeting_plugin_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'greetings';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        greeting text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function greeting_plugin_deactivate() {
    // Clean up tasks on plugin deactivation, if needed.
}

// Register the API endpoint for storing greetings.
add_action('rest_api_init', 'greeting_plugin_register_routes');

function greeting_plugin_register_routes() {
    // Endpoint to store a greeting via POST request.
    register_rest_route('greeting', '/store', array(
        'methods' => 'POST',
        'callback' => 'store_greeting',
        'permission_callback' => 'determine_current_user',
    ));

    // Endpoint to retrieve a greeting via GET request.
    register_rest_route('greeting', '/fetch', array(
        'methods' => 'GET',
        'callback' => 'fetch_greeting',
        'permission_callback' => 'determine_current_user',
    ));
}
function store_greeting($request) {
    $greeting = sanitize_text_field($request->get_param('greeting'));

    global $wpdb;
    $table_name = $wpdb->prefix . 'greetings';
    $wpdb->insert($table_name, array('greeting' => $greeting));

    return 'Greeting stored successfully!';
}

function fetch_greeting() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'greetings';
    $greeting = $wpdb->get_var("SELECT greeting FROM $table_name WHERE id = (SELECT MAX(id) FROM $table_name)");

    return $greeting ? $greeting : 'No greeting found.';
}


// Add an admin menu to display greetings.
add_action('admin_menu', 'greeting_plugin_admin_menu');

function greeting_plugin_admin_menu() {
    add_menu_page('Greeting Plugin', 'Greeting Plugin', 'manage_options', 'greeting-plugin', 'display_greetings');
}

function display_greetings() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'greetings';
    $greetings = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap">';
    echo '<h2>Greetings</h2>';
    echo '<ul>';
    foreach ($greetings as $greeting) {
        echo '<li>' . esc_html($greeting->greeting) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}
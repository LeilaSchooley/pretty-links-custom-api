<?php
/*
Plugin Name: Custom Pretty Link API
Description: Custom plugin to create Pretty Links via the WordPress REST API. Includes a welcome page with instructions and example code.
Version: 1.1
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Register the REST API endpoint
add_action( 'rest_api_init', function () {
    register_rest_route( 'pretty-link-api/v1', '/create/', array(
        'methods' => 'POST',
        'callback' => 'create_pretty_link',
        'permission_callback' => '__return_true',
    ));
});
// Function to create Pretty Link via the API with detailed debugging information
function create_pretty_link( $request ) {
    // Check if Pretty Links is active
    if ( ! is_plugin_active( 'pretty-link/pretty-link.php' ) ) {
        return new WP_Error( 'pretty_link_inactive', 'Pretty Link plugin is not active', array( 'status' => 400 ) );
    }

    // Get parameters from the request
    $target_url = sanitize_text_field( $request->get_param( 'target_url' ) );
    $slug = sanitize_title( $request->get_param( 'slug' ) );
    $name = sanitize_text_field( $request->get_param( 'name' ) );

    // Validate inputs
    if ( empty( $target_url ) ) {
        return rest_ensure_response( array(
            'status' => 400,
            'error' => 'Missing target URL',
            'debug' => array(
                'target_url' => $target_url,
                'slug' => $slug,
                'name' => $name
            )
        ));
    }

    // Check if Pretty Link function exists
    if ( ! function_exists( 'prli_create_pretty_link' ) ) {
        return rest_ensure_response( array(
            'status' => 500,
            'error' => 'Pretty Link function not found',
            'debug' => array(
                'function' => 'prli_create_pretty_link',
                'active_plugins' => get_option('active_plugins')
            )
        ));
    }

    // Call Pretty Links function to create the link
    $pretty_link = prli_create_pretty_link( $target_url, $slug, $name );

    // Check if Pretty Link was successfully created
    if ( $pretty_link ) {
        // Log success
        error_log("Pretty Link created successfully: " . $pretty_link);

        // Return success response
        return rest_ensure_response( array(
            'status' => 200,
            'message' => 'Pretty Link created successfully',
            'full_link' => $target_url,
            'short_link' => $pretty_link
        ));
    } else {
        // Log failure
        error_log("Failed to create Pretty Link for URL: " . $target_url);

        // Capture detailed error messages from the Pretty Links plugin
        global $prli_error_messages;
        $debug_info = array(
            'target_url' => $target_url,
            'slug' => $slug,
            'name' => $name,
            'error_messages' => $prli_error_messages,
            'active_plugins' => get_option('active_plugins')
        );

        // Return failure response with debug information
        return rest_ensure_response( array(
            'status' => 500,
            'error' => 'Could not create Pretty Link',
            'debug' => $debug_info
        ));
    }
}

// Add a custom welcome page in the admin dashboard
add_action( 'admin_menu', 'custom_pretty_link_welcome_page' );

function custom_pretty_link_welcome_page() {
    add_menu_page(
        'Pretty Link API Instructions',
        'Pretty Link API',
        'manage_options',
        'custom-pretty-link-welcome',
        'custom_pretty_link_welcome_content',
        'dashicons-admin-links',
        20
    );
}
function custom_pretty_link_welcome_content() {
    // Get the current WordPress username and site URL dynamically
    $current_user = wp_get_current_user();
    $username = $current_user->user_login;
    $site_url = site_url();

    ?>
    <div class="wrap">
        <h1 style="font-size: 2rem; margin-bottom: 1rem;">Welcome to the Custom Pretty Link API Plugin</h1>
        <p style="font-size: 1.2rem; line-height: 1.6;">This plugin allows you to create Pretty Links programmatically using the WordPress REST API. Below are instructions to get started, along with an example implementation in Python.</p>
        
        <h2 style="font-size: 1.5rem; margin-top: 2rem;">How to Use</h2>
        <p style="font-size: 1.1rem;">Make a <strong>POST</strong> request to the following API endpoint:</p>
        <pre style="background-color: #f7f7f7; padding: 1rem; border-left: 5px solid #0073aa;">
<code>POST <?= esc_html( $site_url ); ?>/wp-json/pretty-link-api/v1/create/</code>
        </pre>

        <h3 style="font-size: 1.3rem; margin-top: 2rem;">Example Request Body</h3>
        <p style="font-size: 1.1rem;">Send the following JSON data in your request:</p>
        <pre style="background-color: #f7f7f7; padding: 1rem; border-left: 5px solid #0073aa;">
<code>{
    "target_url": "https://amazon.com/product-url",
    "slug": "best-fitness-tracker",
    "name": "Best Fitness Tracker"
}</code>
        </pre>

        <h3 style="font-size: 1.3rem; margin-top: 2rem;">Python Implementation</h3>
        <p style="font-size: 1.1rem;">Use the following Python snippet to make a request to the API:</p>
        <pre style="background-color: #f7f7f7; padding: 1rem; border-left: 5px solid #0073aa;">
<code>import requests
from requests.auth import HTTPBasicAuth

# WordPress credentials
username = '<?= esc_html( $username ); ?>'  # Your WordPress username
app_password = 'your_application_password'  # Generate this in WordPress admin

# API endpoint
api_url = '<?= esc_html( $site_url ); ?>/wp-json/pretty-link-api/v1/create/'

# Request payload
data = {
    "target_url": "https://amazon.com/product-url",
    "slug": "best-fitness-tracker",
    "name": "Best Fitness Tracker"
}

# Make the request
response = requests.post(api_url, json=data, auth=HTTPBasicAuth(username, app_password))

# Handle the response
if response.status_code == 200:
    print("Pretty Link created:", response.json().get("short_link"))
else:
    print(f"Error: {response.status_code}, {response.text}")
</code>
        </pre>

        <h3 style="font-size: 1.3rem; margin-top: 2rem;">Expected API Response</h3>
        <p style="font-size: 1.1rem;">If the request is successful, you'll receive a response like this:</p>
        <pre style="background-color: #f7f7f7; padding: 1rem; border-left: 5px solid #0073aa;">
<code>{
    "status": 200,
    "message": "Pretty Link created successfully",
    "full_link": "https://amazon.com/product-url",
    "short_link": "<?= esc_html( $site_url ); ?>/best-fitness-tracker"
}</code>
        </pre>

        <h2 style="font-size: 1.5rem; margin-top: 2rem;">Need Help?</h2>
        <p style="font-size: 1.2rem; line-height: 1.6;">Visit the <a href="https://your-wordpress-site.com/docs" target="_blank" style="color: #0073aa;">documentation</a> for more details, or reach out to support.</p>
    </div>
    <?php
}

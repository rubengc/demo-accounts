<?php
/**
 * Plugin Name:     	Demo Accounts
 * Plugin URI:      	https://demo.gamipress.com
 * Description:     	Demo accounts created by the GamiPress and AutotomatorWP team for the GamiPress demo site
 * Version:         	1.0.0
 * Author:          	Ruben Garcia
 * Author URI:      	https://automatorwp.com/
 * Text Domain:     	demo-accounts
 * Domain Path: 		/languages/
 * Requires at least: 	4.4
 * Tested up to: 		5.5
 * License:         	GNU AGPL v3.0 (http://www.gnu.org/licenses/agpl.txt)
 *
 * @package         	Demo_Accounts
 * @author          	AutomatorWP <contact@automatorwp.com>, GamiPress <contact@gamipress.com>, Ruben Garcia <rubengcdev@gmail.com>
 * @copyright       	Copyright (c) AutomatorWP
 */

// Start the session on init to avoid conflicts
function demo_accounts_login_start_session() {
    // Start session
    if ( session_status() == PHP_SESSION_NONE )
        session_start();
}
add_shortcode( 'init', 'demo_accounts_login_start_session' );


// [demo_accounts_login_form] shortcode
function demo_accounts_login_form_shortcode( $atts, $content ) {

    // NOTE: If an account is created at every page load then is required WP Session Manager plugin (https://wordpress.org/plugins/wp-session-manager/)

    global $wp;

    $a = shortcode_atts( array(
        'guest_text'        => '', // Text for guest users (before the form)
        'logged_in_text'    => '', // Text for logged in users
    ), $atts );

    // If user already logged in display the logged in text (is any text provided
    if( is_user_logged_in() ) {
        // Fallback to the shortcode content if for some reason user wants to place HTML and shortcodes inside
        if( empty( $a['logged_in_text'] ) )
            $a['logged_in_text'] = $content;

        if( ! empty( $a['logged_in_text'] ) )
            return wpautop( do_shortcode( $a['logged_in_text'] ) );
        else
            return '';
    }

    $demo_user = demo_accounts_get_user();

    $current_url = home_url( $wp->request );

    // Render login form
    ob_start(); ?>
        <?php if( ! empty( $a['guest_text'] ) ) echo wpautop( $a['guest_text'] ); ?>

        <form name="login-form" id="demo-accounts-login-form" class="standard-form" action="<?php echo site_url( 'wp-login.php', 'login_post' ); ?>" method="post">

            <p>
                <strong><?php _e( 'Username:', 'demo-accounts' ); ?></strong>
                <?php echo $demo_user['user_login']; ?>
            </p>

            <p>
                <strong><?php _e( 'Password:', 'demo-accounts' ); ?></strong>
                <?php echo $demo_user['user_pass']; ?>
            </p>

            <p>
                <label for="demo-accounts-user-login"><?php _e( 'Username:', 'demo-accounts' ); ?></label>
                <input type="text" name="log" id="demo-accounts-user-login" class="input" tabindex="97" />
            </p>

            <p>
                <label for="demo-accounts-user-pass"><?php _e( 'Password:', 'demo-accounts' ); ?></label>
                <input type="password" name="pwd" id="demo-accounts-user-pass" class="input" tabindex="98" />
            </p>

            <?php // NOTE: There isn't a remember me field since demo accounts gets removed after a period of time ?>

            <input type="submit" name="wp-submit" id="demo-accounts-wp-submit" value="<?php esc_attr_e( 'Log In', 'demo-accounts' ); ?>" tabindex="100" />

            <input type="hidden" name="redirect_to" id="demo-accounts-redirect-to" value="<?php esc_attr_e( $current_url ); ?>" />

            <?php do_action( 'demo_accounts_login_form' ); ?>

        </form>
    <?php $output = ob_get_clean();

    return $output;
}
add_shortcode( 'demo_accounts_login_form', 'demo_accounts_login_form_shortcode' );

// Login details on log in screen
function demo_accounts_login_message( $message ) {

    $demo_user = demo_accounts_get_user();

    // Render the log in details
    ob_start(); ?>
    <?php if( ! empty( $a['guest_text'] ) ) echo wpautop( $a['guest_text'] ); ?>

    <div id="demo-accounts-login-details">

        <p>
            <strong><?php _e( 'Username:', 'demo-accounts' ); ?></strong>
            <?php echo $demo_user['user_login']; ?>
        </p>

        <p>
            <strong><?php _e( 'Password:', 'demo-accounts' ); ?></strong>
            <?php echo $demo_user['user_pass']; ?>
        </p>

        <?php do_action( 'demo_accounts_login_details' ); ?>

    </div>
    <?php $message .= ob_get_clean();

    return $message;
}
add_filter( 'login_message', 'demo_accounts_login_message', 99999 );

function demo_accounts_get_user() {

    // If not demo user has been created yet, create a new one
    if( ! isset( $_SESSION['demo_accounts_user'] ) ) {

        $current_demo = 1;

        // Get the last demo user created
        $users = get_users( array(
            'search'            => 'demo*',
            'search_columns'    => array( 'user_login' ),
            'number'            => 1,
            'orderby'           => 'ID',
            'order'             => 'desc',
        ) );

        if( count( $users ) )
            $current_demo = absint( str_replace( 'demo', '', $users[0]->user_login ) ) + 1;

        $user_data = array(
            'user_login'    => 'demo' . $current_demo,
            'user_pass'     => 'demo' . $current_demo,
            'user_email'    => 'demo' . $current_demo . '@' . $_SERVER['HTTP_HOST'],
        );

        /**
         * Filters the default account data passed to the wp_insert_user() funciton
         *
         * @since 1.0.0
         *
         * @param array $user_data
         * @param int $current_demo
         */
        $user_data = apply_filters( 'demo_accounts_default_account_data', $user_data, $current_demo );

        $user_id = wp_insert_user( $user_data );

        $user_data['ID'] = $user_id;

        /**
         * Action triggered when an account gets created
         *
         * @since 1.0.0
         *
         * @param int $user_id
         * @param array $user_data
         * @param int $current_demo
         */
        do_action( 'demo_accounts_account_created', $user_id, $user_data, $current_demo );

        $_SESSION['demo_accounts_user'] = $user_data;
    }

    return $_SESSION['demo_accounts_user'];

}

// Dashboard widget
function demo_accounts_dashboard_setup() {
    wp_add_dashboard_widget( 'demo-accounts-dashboard', __( 'Demo Accounts', 'demo-accounts' ), 'demo_accounts_dashboard_widget' );
}
add_action( 'wp_dashboard_setup', 'demo_accounts_dashboard_setup' );

function demo_accounts_dashboard_widget() {

    $user_search = new WP_User_Query( ( array(
        'search'            => 'demo*',
        'search_columns'    => array( 'user_login' ),
        'fields'            => array( 'ID' ),
    ) ) );

    $users = $user_search->get_total();

    ?>

    <p>There are currently <?php echo $users; ?> registered demo accounts.</p>

    <?php if( $users > 0 ) : ?>
        <button type="button" class="button-primary" onclick="jQuery(this).next().slideToggle();">Delete all demo accounts</button>

        <div style="display: none;">
            <p>Are you sure you want to remove <?php echo $users; ?> user accounts?</p>
            <button type="button" class="button-primary" onclick="demo_accounts_delete_users( this, '<?php echo wp_create_nonce( 'demo_accounts_delete_users' ); ?>' );">Yes</button>
            <button type="button" class="button" onclick="jQuery(this).parent().slideToggle();">No</button>
        </div>
    <?php endif; ?>

    <?php
}

// Admin scripts
function demo_accounts_admin_head() {
     ?>
    <script>
    function demo_accounts_delete_users( element, nonce ) {

        var $ = $ || jQuery;

        var $this = $(element);

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'demo_accounts_delete_users',
                nonce: nonce,
            },
            success: function( response ) {
                $this.parent().append('<p>' + response.data + '</p>');

                setTimeout( function() { window.location.reload(); }, 2000 );
            }
        });
    }
    </script>
    <?php
}
add_action( 'admin_head', 'demo_accounts_admin_head' );

// Ajax functions
function demo_accounts_delete_users_ajax() {
    // Security check, forces to die if not security passed
    check_ajax_referer( 'demo_accounts_delete_users', 'nonce' );

    $users_deleted = demo_accounts_delete_users();

    // Return information about accounts deleted
    wp_send_json_success( $users_deleted . ' accounts deleted successfully.' );

}
add_action( 'wp_ajax_demo_accounts_delete_users', 'demo_accounts_delete_users_ajax' );

// Delete users function, returns the number of accounts deleted
function demo_accounts_delete_users() {
    // Required for wp_delete_user()
    require_once( ABSPATH . 'wp-admin/includes/user.php' );

    // Search for all demo accounts
    $user_search = new WP_User_Query( ( array(
        'search'            => 'demo*',
        'search_columns'    => array( 'user_login' ),
        'fields'            => array( 'ID' ),
        'number'            => -1,
    ) ) );

    $users = $user_search->get_results();

    foreach( $users as $user )
        wp_delete_user( $user->ID );

    return count( $users );
}

// Registers a custom cron schedule
function demo_accounts_cron_schedules( $schedules ) {

    if( ! isset( $schedules['weekly'] ) )
        $schedules['weekly'] = array(
            'interval' => 604800,
            'display'  => __( 'Once Weekly', 'demo-accounts' )
        );

    return $schedules;

}
add_filter( 'cron_schedules', 'demo_accounts_cron_schedules' );

// Delete users function, returns the number of accounts deleted
function demo_accounts_schedule_events() {
    $event_name = 'demo_accounts_cleanup_demo_accounts';

    // Setup a weekly cron event to cleanup demo accounts
    //if ( ! wp_next_scheduled( $event_name ) )
        //wp_schedule_event( time(), 'weekly', $event_name );

    // Uncomment to unschedule events
    //wp_unschedule_event( wp_next_scheduled( $event_name ), $event_name );
}
add_action( 'init', 'demo_accounts_schedule_events' );

// Function called by the WordPress cron to cleanup demo accounts in a specific interval
function demo_accounts_cleanup_demo_accounts() {
    $users_deleted = demo_accounts_delete_users();
}
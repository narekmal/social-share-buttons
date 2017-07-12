<?php
/*
Plugin Name: Social Share Buttons
Description: Adds social share buttons to posts and pages
Author: Narek Malkhasyan
Text Domain: social-share-buttons
Version: 0.0.1
*/

// If this file is called directly, abort
if ( ! defined( 'WPINC' ) ) 
	die;

if ( !class_exists( 'SocialShareButtonsPlugin' ) ) {
    class SocialShareButtonsPlugin
    {
        /* Static property to hold singleton instance */
        static $instance = false;

        /* If an instance exists, returns it, if not, creates one and returns it */
        static function getInstance() {
            if ( !self::$instance )
                self::$instance = new self;
            return self::$instance;
        }

        /* Constructor */
        private function __construct() {
            /* Back end */
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueueStylesScripts') );
            add_action( 'admin_menu', array( $this, 'addAdminMenu') );
            add_action( 'admin_init', array( $this, 'addAdminSettings') );

            /* Front end */
            add_filter( 'the_title', array( $this, 'filterTitle') );
            //add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ), 10 );
        }

        function enqueueStylesScripts() {
            wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
            wp_enqueue_style( 'social-share-buttons', plugin_dir_url( __FILE__ ) . 'admin/social-share-buttons-admin.css' );
            wp_enqueue_script( 'social-share-buttons', plugin_dir_url( __FILE__ ) . 'admin/social-share-buttons-admin.js', array( 'jquery', 'scriptaculous-dragdrop' ) );
        }

        function addAdminMenu() {
            add_menu_page(
                'Share Button Settings',
                'Share Button Settings',
                'manage_options',
                'share_button_settings',
                array( $this, 'showSettings'),
                'dashicons-chart-pie',
                1000
            );
        }

        function showSettings() {
            // check if the user have submitted the settings
            // wordpress will add the "settings-updated" $_GET parameter to the url
            if ( isset( $_GET['settings-updated'] ) ) {
                // add settings saved message with the class of "updated"
                add_settings_error( 'ssb_messages', 'ssb_message', __( 'Settings Saved', 'social-share-buttons' ), 'updated' );
            }
            // show error/update messages
            settings_errors( 'ssb_messages' );
            ?>
            <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "wporg"
            settings_fields( 'ssb_settings' );
            // output setting sections and their fields
            // (sections are registered for "wporg", each field is registered to a specific section)
            do_settings_sections( 'share_button_settings' );
            // output save settings button
            submit_button( 'Save Changes' );
            ?>
            </form>
            <?php
        }

        function addAdminSettings() {
            // register a new setting for "reading" page
            register_setting('ssb_settings', 'ssb_settings');
        
            // register a new section in the "reading" page
            add_settings_section(
                'ssb_settings_section',
                'Social Share Button Settings',
                null,
                'share_button_settings'
            );
        
            add_settings_field(
                'ssb_settings_icon_size',
                'Size of Icons',
                array( $this, 'outputIconSizeField'),
                'share_button_settings',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_visibility',
                'Visibility of Icons',
                array( $this, 'outputIconVisibilityField'),
                'share_button_settings',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_placing',
                'Placing of Social Share Bar',
                array( $this, 'outputIconPlacingField'),
                'share_button_settings',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_order',
                'Order of Icons',
                array( $this, 'outputIconOrderField'),
                'share_button_settings',
                'ssb_settings_section'
            );
        }

        function outputIconSizeField() {
            ?>
            <select name="ssb_settings[icons_size]">
                <option value="small" <?php echo get_option( 'ssb_settings' )['icons_size'] == 'small' ? 'selected' : ''; ?>>Small</option>
                <option value="medium" <?php echo get_option( 'ssb_settings' )['icons_size'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="large" <?php echo get_option( 'ssb_settings' )['icons_size'] == 'large' ? 'selected' : ''; ?>>Large</option>
            </select>
            <?php
        }

        function outputIconVisibilityField() {
            ?>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[facebook_visibility]" <?php echo get_option( 'ssb_settings' )['facebook_visibility'] ? 'checked' : ''; ?> >
                Facebook
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[twitter_visibility]" <?php echo get_option( 'ssb_settings' )['twitter_visibility'] ? 'checked' : ''; ?> />
                Twitter
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[googleplus_visibility]" <?php echo get_option( 'ssb_settings' )['googleplus_visibility'] ? 'checked' : ''; ?> />
                Google+
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[pinterest_visibility]" <?php echo get_option( 'ssb_settings' )['pinterest_visibility'] ? 'checked' : ''; ?> />
                Pinterest
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[linkedin_visibility]" <?php echo get_option( 'ssb_settings' )['linkedin_visibility'] ? 'checked' : ''; ?> />
                LinkedIn
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[whatsapp_visibility]" <?php echo get_option( 'ssb_settings' )['whatsapp_visibility'] ? 'checked' : ''; ?> />
                Whatsapp (shown only on mobile displays)
            </label>
            <?php
        }

        
        function outputIconPlacingField() {
            ?>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="below_title" <?php echo get_option( 'ssb_settings' )['placing'] == 'below_title' ? 'checked' : ''; ?> />
                Below the Post Title
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="floating" <?php echo get_option( 'ssb_settings' )['placing'] == 'floating' ? 'checked' : ''; ?> />
                Floating on the Left Area
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="after_content" <?php echo get_option( 'ssb_settings' )['placing'] == 'after_content' ? 'checked' : ''; ?> />
                After the Post Content
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="inside_image" <?php echo get_option( 'ssb_settings' )['placing'] == 'inside_image' ? 'checked' : ''; ?> />
                Inside the Featured Image
            </label>
            <?php
        }

        function outputIconOrderField() {
            ?>
            <div id="ssb_admin-sortable-list-container">
                <input id="ssb_admin-icon-order-hidden-input" type="hidden" name="ssb_settings[order]" value="ftgplw" />
                <?php 
                $order = get_option( 'ssb_settings' )['order'];
                for ($i = 0; $i < strlen($order); $i++){
                    echo "<i class='fa fa-";
                    switch($order[$i]){
                        case 'f':
                            echo 'facebook';
                            break;
                        case 't':
                            echo 'twitter';
                            break;
                        case 'g':
                            echo 'google-plus';
                            break;
                        case 'p':
                            echo 'pinterest';
                            break;
                        case 'l':
                            echo 'linkedin';
                            break;
                        case 'w':
                            echo 'whatsapp';
                            break;
                    }
                    echo  " ssb_admin-icon'></i>";
                }
                ?>
            </div>
            <?php
        }
        



        function filterTitle( $title ) {
            $a = get_the_permalink();
            $custom_title = "<div>YOUR CONTENT GOES HERE, {$a}</div>";
            $title .= $custom_title;
            return $title;
        }


    }

    // Instantiate class
    SocialShareButtonsPlugin::getInstance();
}




?>
<?php
/*
Plugin Name: Social Share Buttons
Description: Adds social share buttons to posts and pages
Author: Narek Malkhasyan
Text Domain: social-share-buttons
Version: 0.0.1
*/

/* If this file is called directly, abort */
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

        /* Holds settings set in the admin page */
        private $settings;

        /* Constructor */
        private function __construct() {
            /* Get settings from DB */
            $this->settings = get_option( 'ssb_settings' );

            /* Admin */
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminStylesScripts') );
            add_action( 'admin_menu', array( $this, 'addAdminMenu') );
            add_action( 'admin_init', array( $this, 'addAdminSettings') );

            /* Front end */
            add_filter( 'the_content', array( $this, 'filterContent') );
            add_filter( 'post_thumbnail_html', array( $this, 'filterPostThumbnailHtml') );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueueStylesScripts') );
        }

        function enqueueAdminStylesScripts() {
            wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_style( 'social-share-buttons', plugin_dir_url( __FILE__ ) . 'assets/admin/social-share-buttons-admin.css' );
            wp_enqueue_script( 'social-share-buttons', plugin_dir_url( __FILE__ ) . 'assets/admin/social-share-buttons-admin.js', array( 'jquery', 'scriptaculous-dragdrop', 'wp-color-picker' ) );
        }

        function enqueueStylesScripts() {
            wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
            wp_enqueue_style( 'social-share-buttons', plugin_dir_url( __FILE__ ) . 'assets/social-share-buttons.css' );
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
                'ssb_settings_icons_display',
                'Display Icons',
                array( $this, 'outputIconsDisplayField'),
                'share_button_settings',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_size',
                'Size of Icons',
                array( $this, 'outputIconsSizeField'),
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
                'ssb_settings_icons_colors',
                'Colors of Icons',
                array( $this, 'outputIconsColorsField'),
                'share_button_settings',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_visibility',
                'Visibility of Icons',
                array( $this, 'outputIconsVisibilityField'),
                'share_button_settings',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_order',
                'Order of Icons (Drag to Change)',
                array( $this, 'outputIconOrderField'),
                'share_button_settings',
                'ssb_settings_section'
            );
        }

        function outputIconsDisplayField() {
            ?>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[post_type_post]" <?php echo $this->settings['post_type_post'] ? 'checked' : ''; ?> />
                On Posts
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[post_type_page]" <?php echo $this->settings['post_type_page'] ? 'checked' : ''; ?> />
                On Pages
            </label>
            <?php
            // Get custom post types as public and 'not builtin' post types 
            $customPostTypes = get_post_types( array('public' => true,'_builtin' => false), 'objects', 'and' );
            foreach ($customPostTypes as $customPostType): ?>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[post_type_<?php echo $customPostType->name; ?>]" <?php echo $this->settings["post_type_{$customPostType->name}"] ? 'checked' : ''; ?> />
                On <?php echo $customPostType->label; ?>
            </label>
            <?php endforeach;
            
            ?>
            <?php
        }

        function outputIconsSizeField() {
            ?>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[icons_size]" value="small" <?php echo $this->settings['icons_size'] == 'small' ? 'checked' : ''; ?> />
                Small
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[icons_size]" value="medium" <?php echo $this->settings['icons_size'] == 'medium' ? 'checked' : ''; ?> />
                Medium
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[icons_size]" value="large" <?php echo $this->settings['icons_size'] == 'large' ? 'checked' : ''; ?> />
                Large
            </label>
            <?php
        }

        function outputIconPlacingField() {
            ?>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="below_title" <?php echo $this->settings['placing'] == 'below_title' ? 'checked' : ''; ?> />
                Below the Post Title
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="floating" <?php echo $this->settings['placing'] == 'floating' ? 'checked' : ''; ?> />
                Floating on the Left Area
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="after_content" <?php echo $this->settings['placing'] == 'after_content' ? 'checked' : ''; ?> />
                After the Post Content
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="inside_image" <?php echo $this->settings['placing'] == 'inside_image' ? 'checked' : ''; ?> />
                Inside the Featured Image
            </label>
            <?php
        }

        function outputIconsColorsField() {
            ?>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[icons_color]" value="original" <?php echo $this->settings['icons_color'] == 'original' ? 'checked' : ''; ?> />
                Original Colors
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[icons_color]" value="custom" <?php echo $this->settings['icons_color'] == 'custom' ? 'checked' : ''; ?> />
                All in a Selected Color
            </label>
            <div id="ssb_admin-color-picker-container">
                <input type="text" name="ssb_settings[icons_custom_color]" value="<?php echo $this->settings['icons_custom_color']; ?>">
            </div>
            
            <?php
        }

        function outputIconsVisibilityField() {
            ?>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[facebook_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['facebook_visibility'] ? 'checked' : ''; ?> >
                Facebook
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[twitter_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['twitter_visibility'] ? 'checked' : ''; ?> />
                Twitter
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[google-plus_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['google-plus_visibility'] ? 'checked' : ''; ?> />
                Google+
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[pinterest_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['pinterest_visibility'] ? 'checked' : ''; ?> />
                Pinterest
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[linkedin_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['linkedin_visibility'] ? 'checked' : ''; ?> />
                LinkedIn
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[whatsapp_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['whatsapp_visibility'] ? 'checked' : ''; ?> />
                Whatsapp (shown only on mobile displays)
            </label>
            <?php
        }

        function outputIconOrderField() {
            ?>
            <div id="ssb_admin-sortable-list-container">
                <input id="ssb_admin-icon-order-hidden-input" type="hidden" name="ssb_settings[order]" value="ftgplw" />
                <?php 
                $order = $this->settings['order'];
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
        
        function filterContent( $content ) {
            if(!$this->isCurrentPostTypeDisplayEnabled())
                return $html;
            if($this->settings['placing'] != 'after_content')
                return $html;

            $postLink = get_the_permalink();
            $content .= $this->getButtonsHtml($postLink);

            return $content;
        }

        function filterPostThumbnailHtml( $html ) {
            if(!$this->isCurrentPostTypeDisplayEnabled())
                return $html;
            if($this->settings['placing'] != 'inside_image')
                return $html;
            
            $postLink = get_the_permalink();
            $buttonsHtml = $this->getButtonsHtml($postLink);
            $html = "<div class='ssb_thumbnail-wrapper'>{$html}{$buttonsHtml}</div>";

            return $html;
        }

        function isCurrentPostTypeDisplayEnabled() {
            $postTypes = array();
            foreach($this->settings as $key => $value){
                if (0 === strpos($key, "post_type_")) {
                    // $key starts with "post_type_", push the rest of $key to array
                    array_push($postTypes, substr($key, strlen("post_type_")));
                }
            }
            if (empty($postTypes)) 
                return false;
            else
                return is_singular($postTypes);
        }

        function getButtonsHtml($postLink){
            $html = "<div class='ssb_buttons-wrapper'>";
            $order = $this->settings['order'];
            $iconsSize = $this->settings['icons_size'];
            $iconsColorStyleString = $this->getIconsColorStyleString();
            for ($i = 0; $i < strlen($order); $i++){
                switch($order[$i]){
                    case 'f':
                        if($this->settings['facebook_visibility'])
                            $html .= $this->getFacebookButtonHtml($postLink, $iconsSize, $iconsColorStyleString);
                        break;
                    case 't':
                        if($this->settings['twitter_visibility'])
                            $html .= $this->getTwitterButtonHtml($postLink, $iconsSize, $iconsColorStyleString);
                        break;
                    case 'g':
                        if($this->settings['google-plus_visibility'])
                            $html .= $this->getGooglePlusButtonHtml($postLink, $iconsSize, $iconsColorStyleString);
                        break;
                    case 'p':
                        if($this->settings['pinterest_visibility'])
                            $html .= $this->getPinterestButtonHtml($postLink, $iconsSize, $iconsColorStyleString);
                        break;
                    case 'l':
                        if($this->settings['linkedin_visibility'])
                            $html .= $this->getLinkedinButtonHtml($postLink, $iconsSize, $iconsColorStyleString);
                        break;
                    case 'w':
                        if($this->settings['whatsapp_visibility'])
                            $html .= $this->getWhatsAppButtonHtml($postLink, $iconsSize, $iconsColorStyleString);
                        break;
                }
            }
            $html .= "</div>";
            return $html;
        }

        function getFacebookButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='https://www.facebook.com/sharer/sharer.php?u={$postLink}&amp;src=sdkpreparse'><i class='fa fa-facebook ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        function getTwitterButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='https://twitter.com/intent/tweet?url={$postLink}'><i class='fa fa-twitter ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        function getGooglePlusButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='https://plus.google.com/share?url={$postLink}'><i class='fa fa-google-plus ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        function getPinterestButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='http://pinterest.com/pin/create/button/?url={$postLink}'><i class='fa fa-pinterest ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        function getLinkedInButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='https://www.facebook.com/sharer/sharer.php?u={$postLink}&amp;src=sdkpreparse'><i class='fa fa-linkedin ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        function getWhatsAppButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='https://www.facebook.com/sharer/sharer.php?u={$postLink}&amp;src=sdkpreparse'><i class='fa fa-whatsapp ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        function getIconsColorStyleString(){
            if ($this->settings['icons_color'] != 'custom')
                return '';
            $color = $this->settings['icons_custom_color'];
            return "style='color: {$color}'";
        }
    }

    // Instantiate class
    SocialShareButtonsPlugin::getInstance();
}




?>
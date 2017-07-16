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

        /* Holds settings set in the admin page */
        private $settings;

        /* Constructor */
        private function __construct() {
            // Get settings from DB
            $this->settings = get_option( 'ssb_settings' );

            // Admin
            register_activation_hook( __FILE__, array( $this, 'addDefaultSettings') );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminStylesScripts') );
            add_action( 'admin_menu', array( $this, 'addAdminMenu') );
            add_action( 'admin_init', array( $this, 'addAdminSettings') );

            // Front end
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueueStylesScripts') );
            add_filter( 'the_content', array( $this, 'filterPostContent') );
            add_filter( 'post_thumbnail_html', array( $this, 'filterPostThumbnailHtml') );
            add_action( 'wp_footer', array( $this, 'addFloatingBar') );

            // Shortcode
            add_shortcode( 'social-share-buttons', array( $this, 'getShortcodeOutput') );
        }

        /* Add default settings */
        function addDefaultSettings() {
            add_option('ssb_settings', array(
                'post_type_post' => 'on',
                'post_type_page' => 'on',
                'icons_size' => 'medium',
                'placing' => 'below_title',
                'icons_color' => 'original',
                'facebook_visibility' => 'on',
                'twitter_visibility' => 'on',
                'google-plus_visibility' => 'on',
                'pinterest_visibility' => 'on',
                'linkedin_visibility' => 'on',
                'whatsapp_visibility' => 'on',
                'order' => 'ftgplw'
            ));
        }

        /* Enqueue admin styles and scripts */
        function enqueueAdminStylesScripts() {
            wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_style( 'social-share-buttons', plugin_dir_url( __FILE__ ) . 'assets/admin/social-share-buttons-admin.css' );
            wp_enqueue_script( 'social-share-buttons', plugin_dir_url( __FILE__ ) . 'assets/admin/social-share-buttons-admin.js', array( 'jquery', 'scriptaculous-dragdrop', 'wp-color-picker' ) );
        }

        /* Enqueue front end styles and scripts */
        function enqueueStylesScripts() {
            wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
            wp_enqueue_style( 'social-share-buttons', plugin_dir_url( __FILE__ ) . 'assets/social-share-buttons.css' );
        }

        /* Return HTML output of [social-share-buttons] shortcode */
        function getShortcodeOutput(){
            return $this->getButtonsHtml(get_the_permalink());
        }

        /* Add menu subitem to Settings menu */
        function addAdminMenu() {
            add_options_page(
                __('Social Share Button Settings', 'social-share-buttons'),
                __('Social Share Buttons', 'social-share-buttons'),
                'manage_options',
                'ssb_settings_menu',
                array( $this, 'showAdminSettings')
            );
        }

        /* Output contents of settings page */
        function showAdminSettings() {
            ?>
            <form action="options.php" method="post">
            <?php
            // Output security fields for the registered setting "ssb_settings"
            settings_fields( 'ssb_settings' );
            // Output setting sections and their fields
            do_settings_sections( 'ssb_settings_menu_page' );
            // Output save settings button
            submit_button( __('Save Changes', 'social-share-buttons') );
            ?>
            </form>
            <?php
        }

        /* Register settings, sections, fields using the Settings API */
        function addAdminSettings() {
            // Register a setting
            register_setting('ssb_settings', 'ssb_settings');
        
            // Register a section 
            add_settings_section(
                'ssb_settings_section',
                __('Social Share Buttons Settings', 'social-share-buttons'),
                null,
                'ssb_settings_menu_page'
            );
        
            // Add settings fields
            add_settings_field(
                'ssb_settings_icons_display',
                __('Display Icons', 'social-share-buttons'),
                array( $this, 'outputIconsDisplayField'),
                'ssb_settings_menu_page',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_size',
                __('Size of Icons', 'social-share-buttons'),
                array( $this, 'outputIconsSizeField'),
                'ssb_settings_menu_page',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_placing',
                __('Placing of Social Share Bar', 'social-share-buttons'),
                array( $this, 'outputIconsPlacingField'),
                'ssb_settings_menu_page',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_colors',
                __('Colors of Icons', 'social-share-buttons'),
                array( $this, 'outputIconsColorsField'),
                'ssb_settings_menu_page',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_visibility',
                __('Visibility of Icons', 'social-share-buttons'),
                array( $this, 'outputIconsVisibilityField'),
                'ssb_settings_menu_page',
                'ssb_settings_section'
            );

            add_settings_field(
                'ssb_settings_icons_order',
                __('Order of Icons (Drag to Reorder)', 'social-share-buttons'),
                array( $this, 'outputIconsOrderField'),
                'ssb_settings_menu_page',
                'ssb_settings_section'
            );
        }

        function outputIconsDisplayField() {
            ?>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[post_type_post]" <?php echo $this->settings['post_type_post'] ? 'checked' : ''; ?> />
                <?php _e('On Posts', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[post_type_page]" <?php echo $this->settings['post_type_page'] ? 'checked' : ''; ?> />
                <?php _e('On Pages', 'social-share-buttons'); ?>
            </label>
            <?php
            // Get custom post types as public and 'not builtin' post types 
            $customPostTypes = get_post_types( array('public' => true,'_builtin' => false), 'objects', 'and' );
            foreach ($customPostTypes as $customPostType): ?>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[post_type_<?php echo $customPostType->name; ?>]" <?php echo $this->settings["post_type_{$customPostType->name}"] ? 'checked' : ''; ?> />
                <?php printf(__( 'On %s', 'social-share-buttons' ), $customPostType->label); ?>
            </label>
            <?php 
            endforeach;
        }

        function outputIconsSizeField() {
            ?>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[icons_size]" value="small" <?php echo $this->settings['icons_size'] == 'small' ? 'checked' : ''; ?> />
                <?php _e('Small', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[icons_size]" value="medium" <?php echo $this->settings['icons_size'] == 'medium' ? 'checked' : ''; ?> />
                <?php _e('Medium', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[icons_size]" value="large" <?php echo $this->settings['icons_size'] == 'large' ? 'checked' : ''; ?> />
                <?php _e('Large', 'social-share-buttons'); ?>
            </label>
            <?php
        }

        function outputIconsPlacingField() {
            ?>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="below_title" <?php echo $this->settings['placing'] == 'below_title' ? 'checked' : ''; ?> />
                <?php _e('Below the Post Title', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="floating" <?php echo $this->settings['placing'] == 'floating' ? 'checked' : ''; ?> />
                <?php _e('Floating on the Left Area', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="after_content" <?php echo $this->settings['placing'] == 'after_content' ? 'checked' : ''; ?> />
                <?php _e('After the Post Content', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[placing]" value="inside_image" <?php echo $this->settings['placing'] == 'inside_image' ? 'checked' : ''; ?> />
                <?php _e('Inside the Featured Image', 'social-share-buttons'); ?>
            </label>
            <?php
        }

        function outputIconsColorsField() {
            ?>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[icons_color]" value="original" <?php echo $this->settings['icons_color'] == 'original' ? 'checked' : ''; ?> />
                <?php _e('Original Colors', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="radio" name="ssb_settings[icons_color]" value="custom" <?php echo $this->settings['icons_color'] == 'custom' ? 'checked' : ''; ?> />
                <?php _e('All in a Selected Color', 'social-share-buttons'); ?>
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
                <?php _e('Facebook', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[twitter_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['twitter_visibility'] ? 'checked' : ''; ?> />
                <?php _e('Twitter', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[google-plus_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['google-plus_visibility'] ? 'checked' : ''; ?> />
                <?php _e('Google+', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[pinterest_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['pinterest_visibility'] ? 'checked' : ''; ?> />
                <?php _e('Pinterest', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[linkedin_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['linkedin_visibility'] ? 'checked' : ''; ?> />
                <?php _e('LinkedIn', 'social-share-buttons'); ?>
            </label>
            <label class="ssb_admin-label">
                <input type="checkbox" name="ssb_settings[whatsapp_visibility]" class="ssb_admin-visibility-checkbox" <?php echo $this->settings['whatsapp_visibility'] ? 'checked' : ''; ?> />
                <?php _e('Whatsapp (shown only on mobile displays)', 'social-share-buttons'); ?>
            </label>
            <?php
        }

        function outputIconsOrderField() {
            ?>
            <div id="ssb_admin-sortable-list-container">
                <input id="ssb_admin-icon-order-hidden-input" type="hidden" name="ssb_settings[order]" value="<?php echo $this->settings['order']; ?>" />
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
        
        /* Filter post content, used to insert buttons 'After Content' and 'Below Title' */
        function filterPostContent( $content ) {
            if(!$this->isCurrentPostTypeDisplayEnabled())
                return $content;

            if($this->settings['placing'] == 'after_content'){
                $postLink = get_the_permalink();
                $content .= $this->getButtonsHtml($postLink);
            }

            if($this->settings['placing'] == 'below_title'){
                $postLink = get_the_permalink();
                // For the placement 'Below Title', we insert buttons before content. If we use title filter for this, 
                // buttons will be inserted everywhere on the page where the title is output.
                $content = $this->getButtonsHtml($postLink).$content;
            }

            return $content;
        }

        /* Filter post thumbnail HTML, used to insert buttons 'Inside Featured Image' */
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

        /* Used to insert buttons 'Floating on the Left Area' */
        function addFloatingBar() {
            if(!$this->isCurrentPostTypeDisplayEnabled())
                return;
            if($this->settings['placing'] != 'floating')
                return;
            
            $postLink = get_the_permalink();
            $buttonsHtml = $this->getButtonsHtml($postLink);
            $html = "<div class='ssb_floating-bar'>{$buttonsHtml}</div>";

            echo $html;
        }

        /* Retrieve all post types for which display is enabled in the settings page and determine if currently viewing on of those post types */
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

        /* Get buttons' block HTML */
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

        /* Get Facebook button HTML */
        function getFacebookButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='https://www.facebook.com/sharer/sharer.php?u={$postLink}' class='ssb_button-facebook'><i class='fa fa-facebook ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        /* Get Twitter button HTML */
        function getTwitterButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='https://twitter.com/intent/tweet?url={$postLink}' class='ssb_button-twitter'><i class='fa fa-twitter ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        /* Get Google Plus button HTML */
        function getGooglePlusButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='https://plus.google.com/share?url={$postLink}' class='ssb_button-google-plus'><i class='fa fa-google-plus ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        /* Get Pinterest button HTML */
        function getPinterestButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='https://pinterest.com/pin/create/bookmarklet/?url={$postLink}' class='ssb_button-pinterest'><i class='fa fa-pinterest ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        /* Get LinkedIn button HTML */
        function getLinkedInButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='https://www.linkedin.com/shareArticle?url={$postLink}' class='ssb_button-linkedin'><i class='fa fa-linkedin ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        /* Get WhatsApp button HTML */
        function getWhatsAppButtonHtml($postLink, $iconsSize, $iconsColorStyleString){
            return "<a target='_blank' href='whatsapp://send?text={$postLink}' class='ssb_button-whatsapp'><i class='fa fa-whatsapp ssb_icon-{$iconsSize}' {$iconsColorStyleString}></i></a>";
        }

        /* Retrieve custom icons color code from settings if set, and return style attribute string based on it */
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
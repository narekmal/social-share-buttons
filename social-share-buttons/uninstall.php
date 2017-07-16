<?php

// If uninstall.php is not called by WordPress, abort
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

if (!is_multisite()) {
    // Delete plugin options
    delete_option( "ssb_settings" );
} 
else {
    // For multisite need to delete options for all blogs (sites in the network)
    global $wpdb;
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
    $original_blog_id = get_current_blog_id();

    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );
        delete_option( "ssb_settings" );     
    }

    switch_to_blog( $original_blog_id );
}

?>
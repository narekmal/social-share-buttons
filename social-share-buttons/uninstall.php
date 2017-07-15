<?php

// If uninstall.php is not called by WordPress, abort
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
// Delete plugin options
delete_option("ssb_settings");

?>
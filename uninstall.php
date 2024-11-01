<?php
## This file removes all saved settings from the database.

global $wpdb;
$sb_ip_log = $wpdb->prefix . 'sb_ip_log';

if(WP_UNINSTALL_PLUGIN){
	$wpdb->query("DROP TABLE $sb_ip_log"); //deleting custom table

		//deleting options:
	delete_option('sb_plugin_version');
	delete_option('sb_detection_of_spammers_method');
	delete_option('sb_detection_of_spammers_time');
	delete_option('sb_banned_message');
		//v1.4:
	delete_option('sb_automatic_ip_address_duplication');
	delete_option('sb_automatic_ip_address_duplication_recurrence');
	delete_option('sb_spam_comment_automatic_elimination');
	delete_option('sb_automatic_ip_address_duplication_last_cron_run_time');
	wp_clear_scheduled_hook('sb_call_automatic_ip_address_duplication'); //remove cron for duplicating ip addresses
	remove_action('sb_call_automatic_ip_address_duplication', 'sb_automatic_ip_address_duplication'); //remove action for calling the function
		//v1.5
	delete_option('sb_access_restrictions');
	delete_option('sb_plugin_activation_notice');
}
?>

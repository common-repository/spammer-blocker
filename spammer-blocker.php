<?php
/*
Plugin Name: Spammer Blocker
Plugin URI: http://wordpress.org/extend/plugins/spammer-blocker
Description: This plugin prevents users from commenting or viewing your website if a comment of theirs has been marked as spam.
Version: 1.5
Author: Lelkoun
Author URI: http://lelkoun.cz
License: GPL2
*/

#################################################################
#################################################################
########################### VARIABLES ###########################
#################################################################
#################################################################

global $wpdb, $sb_current_ip, $sb_ip_log, $sb_current_time, $sb_find_ip, $sb_find_ip_value, $sb_sql_banned_ips, $sb_banned_ips_number, $sb_backup_file_name, $sb_uploaded_file_name, $sb_backup_file_export_dir, $sb_backup_file_export_url, $sb_banned_message_default;

$sb_current_ip = $_SERVER['REMOTE_ADDR'];
$sb_ip_log = $wpdb->prefix . 'sb_ip_log'; //name of the plugin's custom table
$sb_current_time = date("Y-m-d H:i:s", time()+get_option('gmt_offset')*3600); //GTM time + GMT offset = current time

//when we add a mysql query we should not forget to add '@' before it to avoid unexpected output message
$sb_find_ip = @mysql_query("SELECT * FROM $sb_ip_log WHERE ip='$sb_current_ip' LIMIT 0, 1;");
$sb_find_ip_value = @mysql_fetch_array($sb_find_ip);
$sb_sql_banned_ips = @mysql_query("SELECT ip FROM $sb_ip_log ORDER BY last_visit DESC");
$sb_banned_ips_number = @mysql_num_rows($sb_sql_banned_ips);

$sb_backup_file_name = 'sb_ip_log.csv';
$sb_uploaded_file_name = $_FILES['sb_uploaded_file']['name'];
$sb_backup_file_export_dir = WP_PLUGIN_DIR . "/" . basename(dirname(__FILE__)) . "/" . $sb_backup_file_name;
$sb_backup_file_export_url = WP_PLUGIN_URL . "/" . basename(dirname(__FILE__)) . "/" . $sb_backup_file_name;

$sb_banned_message_default = '<html>
<head>
<title>You are banned from this site!</title>
</head>
<body>

<h1>You are banned from this site! Stop spamming and behave yourself.</h1>
<h2>Your IP address has been blocked by <a href="http://wordpress.org/extend/plugins/spammer-blocker">Spammer Blocker</a>.</h2>

</body>
</html>';

#################################################################
#################################################################
########################### FUNCTIONS ###########################
#################################################################
#################################################################


#################### table creation function ####################
function sb_create_db_table(){ //this functions defines the plugin table structure - it is called when the plugin is activated or updated
global $wpdb, $sb_ip_log;

$sql = 'CREATE TABLE '. $sb_ip_log .'(
	id INT,
	ip VARCHAR (15),
	last_visit VARCHAR (19),
	hits INT,
	PRIMARY KEY  (ip)
	);
	';

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
#################### marking a comment as spam ##################
function sb_comment_status_change($comment_id, $comment_status){
	global $wpdb, $sb_ip_log;

	$sb_comment_author_ip = $wpdb->get_var("SELECT comment_author_IP FROM $wpdb->comments WHERE comment_ID=$comment_id");
	$sb_last_visit = $wpdb->get_var("SELECT comment_date FROM $wpdb->comments WHERE comment_ID=$comment_id");

	if($comment_status == 'spam'){ //copy spammer's IP address to wp_sb_ip_log after marking his comment as spam
		$wpdb->query("INSERT INTO $sb_ip_log (id, ip, last_visit, hits) VALUES ('$comment_id', '$sb_comment_author_ip', '$sb_last_visit', '1')");
	}

/*
"UNSPAMMING" COMMENT FUNCTION DOES NOT WORK!!!

	if($comment_status == 'approve' OR $comment_status == 'hold'){ //delete IP from wp_sb_ip_log after marking his comment as approve or hold
		
		//$wpdb->query("SELECT ip FROM $sb_ip_log WHERE id=$comment_id"); //checking if the IP is in the wp_sb_ip_log table

		$wpdb->query("INSERT INTO $sb_ip_log (id, ip, last_visit, hits) VALUES ('999', 'lol4', 'lol')");
			//$wpdb->query("DELETE FROM $sb_ip_log WHERE id=$comment_id"); //deleting the IP from the table
		
	}

*/

//there is no 'trash' status - it would be illogical to delete addresses when emptying the DB containing useless spam comments
}

#################### automatic ip address duplication ###########
function sb_automatic_ip_address_duplication(){
	global $wpdb, $sb_ip_log;

	$wpdb->query("INSERT IGNORE INTO $sb_ip_log (id, ip, last_visit, hits) SELECT comment_ID, comment_author_IP, comment_date, 1 FROM $wpdb->comments WHERE comment_approved='spam'"); //copy all IP addresses to $sb_ip_log

	if(get_option('sb_spam_comment_automatic_elimination') == '1'){ //if it is allowed to delete spam comments after the duplication
		$wpdb->query("DELETE FROM $wpdb->comments WHERE comment_approved='spam'"); //delete all spam comments
	}

	//saving the last and next time of execution of this function
	update_option('sb_automatic_ip_address_duplication_last_cron_run_time', time());
}
#################### show custom ban message and ban ############
function sb_deny_access() {
	@header('HTTP/1.1 403 Forbidden');
	echo eval('?>' . get_option('sb_banned_message') . '<?'); //show custom ban message
	exit(0);
}
#################### update spammer info ##########################
function sb_update_spammer_info(){ //update last visit and visit count of the spammer
	global $wpdb, $sb_ip_log, $sb_current_ip, $sb_current_time;

	$wpdb->query("UPDATE $sb_ip_log SET last_visit='$sb_current_time' WHERE ip='$sb_current_ip'"); //updating last time visit
	$wpdb->query("UPDATE $sb_ip_log SET hits=hits+1 WHERE ip='$sb_current_ip'"); //incrementing last visit
}
#################### get plugin version #########################
function sb_get_plugin_version(){ //return plugin version
	if(!function_exists('get_plugins')){
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	}
	$plugin_folder = get_plugins('/' . plugin_basename(dirname(__FILE__)));
	$plugin_file = basename(( __FILE__ ));
	return $plugin_folder[$plugin_file]['Version'];
}
#################### settings link ##############################
function sb_plugin_action_links($links, $file){
	$this_plugin = plugin_basename(__FILE__);

	if($file == $this_plugin){
 		$settings_link = '<a href="'. admin_url('options-general.php?page=spammer-blocker') .'">' . __('Settings') . '</a>';
		array_unshift($links, $settings_link);
	}
 	return $links;
}
#################### menu link ##################################
function sb_menu_link(){
	$page = add_options_page('Spammer Blocker', 'Spammer Blocker', 'manage_options', 'spammer-blocker', 'spammer_blocker_options');
}
#################### activation notice ##########################
function sb_plugin_activation_notice(){
	if(get_option('sb_plugin_activation_notice') == 1) {
		echo '<div id="message" class="updated"><p>Please <a href="'. admin_url('options-general.php?page=spammer-blocker') .'">go to Settings</a> and set up the behaviour of Spammer Blocker.</p></div>';
	update_option('sb_plugin_activation_notice', 0);
	}
}
#################################################################
#################################################################
#################################################################

#################### activate function ##########################
function sb_activate_plugin(){ //runs only after MANUAL activation!
	global $wpdb, $sb_ip_log, $sb_banned_message_default;

	add_option('sb_plugin_version', sb_get_plugin_version(), '', 'yes');
	add_option('sb_detection_of_spammers_method', '1', '', 'yes');
	add_option('sb_detection_of_spammers_time', '2592000', '', 'yes');
	add_option('sb_banned_message', "$sb_banned_message_default", '', 'yes');
		//v1.4:
	add_option('sb_automatic_ip_address_duplication', '0', '', 'yes');
	add_option('sb_automatic_ip_address_duplication_recurrence', 'twicedaily', '', 'yes');
	add_option('sb_spam_comment_automatic_elimination', '1', '', 'yes');
	add_option('sb_automatic_ip_address_duplication_last_cron_run_time', '0', '', 'yes');
		//v1.5
	add_option('sb_access_restrictions', '1', '', 'yes'); //automatic using comments posting restriction
	add_option('sb_plugin_activation_notice', '1', '', 'yes'); //option for displaying admin notices after activation/update

	sb_create_db_table();

	$wpdb->query("INSERT IGNORE INTO $sb_ip_log (id, ip, last_visit, hits) SELECT comment_ID, comment_author_IP, comment_date, 1 FROM $wpdb->comments WHERE comment_approved='spam'"); //copies all IP addresses to $sb_ip_log

}

#################### update function ############################
function sb_update_plugin(){ //runs when all plugins are loaded (needs to be deleted after register_update_hook is available)
	global $wpdb, $sb_ip_log;

	if(sb_get_plugin_version() != get_option('sb_plugin_version') OR get_option('sb_plugin_version') == FALSE){

		sb_create_db_table(); //update the plugin table to a desired structure

		#### now comes everything what must be changed in the new version
		if(get_option('sb_plugin_version') == '1.3'){ //upgrade to v1.4:				
			add_option('sb_automatic_ip_address_duplication', '0', '', 'yes');
			add_option('sb_automatic_ip_address_duplication_recurrence', 'twicedaily', '', 'yes');
			add_option('sb_spam_comment_automatic_elimination', '1', '', 'yes');
			add_option('sb_automatic_ip_address_duplication_last_cron_run_time', '0', '', 'yes');
			add_option('sb_plugin_activation_notice', '0', '', 'yes'); //option for displaying admin notices after activation/update

			//renaming options and values -- v1.5 - zpetna kompatibilita
			add_option('sb_access_restrictions', '1', '', 'yes'); //adding new option
			delete_option('sb_detection_of_spammers_cookies');
			add_option('sb_detection_of_spammers_method', '1', '', 'yes');

			delete_option('sb_detection_of_spammers_time');
			add_option('sb_detection_of_spammers_time', '2592000', '', 'yes');
		}

		if(get_option('sb_plugin_version') == '1.4'){ //upgrade to v1.5
			add_option('sb_access_restrictions', '1', '', 'yes'); //adding new option
			add_option('sb_plugin_activation_notice', '0', '', 'yes'); //option for displaying admin notices after activation/update

			//renaming options/adding new values
			delete_option('sb_detection_of_spammers_cookies');
			add_option('sb_detection_of_spammers_method', '1', '', 'yes');

			if(get_option('sb_automatic_ip_address_duplication_last_cron_run_time') == ''){ //we do not want to delete the data if not empty
				delete_option('sb_automatic_ip_address_duplication_last_cron_run_time');
				add_option('sb_automatic_ip_address_duplication_last_cron_run_time', '0', '', 'yes');
			}

			delete_option('sb_detection_of_spammers_time'); //but here i do not give a fuck because i am lazy to find appropriate time values for replacing when using a switch :P
			add_option('sb_detection_of_spammers_time', '2592000', '', 'yes');

			//add new column hits to sb_ip_log table
			$wpdb->query("ALTER TABLE $sb_ip_log ADD hits INT;");
			//add 1 to all rows
			$wpdb->query("UPDATE $sb_ip_log SET hits = '1';");			
		}
		#### -/changes

		update_option('sb_plugin_activation_notice', 1); //we want to show the admin notice after upgrading, right?
		update_option('sb_plugin_version', sb_get_plugin_version(), '', 'yes'); //update plugin version in DB
	}
}
#################### deactivate function ########################
function sb_deactivate_plugin(){ //runs after deactivation of the plugin
	update_option('sb_automatic_ip_address_duplication', '0'); //we must set the value to 0 to set the cron as turned off
	wp_clear_scheduled_hook('sb_call_automatic_ip_address_duplication'); //remove cron for duplicating ip addresses
	remove_action('sb_call_automatic_ip_address_duplication', 'sb_automatic_ip_address_duplication'); //remove action for calling the function
	update_option('sb_plugin_activation_notice', 1); //we want to show the admin notice next time, right?
}
#################### hooks ######################################
if(is_admin()){ //run functions when the admin is logged in
	add_action('admin_menu', 'sb_menu_link');
	add_filter('plugin_action_links', 'sb_plugin_action_links', 10, 2);
	add_action('plugins_loaded', 'sb_update_plugin');
	add_action('wp_set_comment_status', 'sb_comment_status_change', 10, 2);
	add_action('admin_notices', 'sb_plugin_activation_notice');
}
	add_action('sb_call_automatic_ip_address_duplication', 'sb_automatic_ip_address_duplication');

register_activation_hook(__FILE__, 'sb_activate_plugin'); //call a function after activation
register_deactivation_hook(__FILE__, 'sb_deactivate_plugin'); //call a function after deactivation

#################################################################
#################################################################
########################## IP CHECK & BAN #######################
#################################################################
#################################################################

if($sb_banned_ips_number !== 0){ //if the list of banned ip addresses is not empty, run the check and ip ban, otherwise don't do anything
//also nobody will get a cookie until at elast one record is in the DB
	#################### fast method ################################
	if(get_option('sb_detection_of_spammers_method') == 1){ //fast method
		if(!isset($_COOKIE['spammer_blocker'])){ //if the visitor does not have a cookie
			if($sb_current_ip == $sb_find_ip_value['ip']){ //banning the spammer if the IP address has been found in the database
				sb_update_spammer_info(); //update last visit and visit count of the spammer

				if(get_option('sb_access_restrictions') == 1){ //disable commenting
					add_filter('comments_open', '__return_false' );
					add_action('comment_closed', 'sb_deny_access');

				}
				else{ //disable access completely
					sb_deny_access();
				}

			}
			else { //if the ip address is not in FB, calculating choosen cookie expiration retrieved from the saved options(select) in seconds
				$sb_cookie_expiration = time()+get_option('sb_detection_of_spammers_time');
				setcookie('spammer_blocker', 'not_a_spammer', $sb_cookie_expiration); //saving a cookie -- there must be a value!
			}
		} //-if
	} //-if

	#################### slow method ################################
	else {
		if($sb_current_ip == $sb_find_ip_value['ip']){ //what to do if the spammer's IP address has been found in the database
			if(isset($_COOKIE['spammer_blocker'])){
				setcookie('spammer_blocker', 'not_a_spammer', time()-3600); //id the spammer has a cookie, delete it
			}

			sb_update_spammer_info(); //update last visit and visit count of the spammer

			if(get_option('sb_access_restrictions') == 1){ //disable commenting
				add_filter('comments_open', '__return_false' );
				add_action('comment_closed', 'sb_deny_access');

			}
			else{ //disable access completely
				sb_deny_access();
			}
		} //-if
	} //-else
} //-if
#################################################################
#################################################################
########################## OPTIONS PAGE #########################
#################################################################
#################################################################

function spammer_blocker_options(){ //load options page
if(current_user_can('manage_options')){ //is the admin logged in?

######################## DECLARATIONS ###########################
global $wpdb, $sb_current_ip, $sb_ip_log, $sb_current_time, $sb_find_ip, $sb_find_ip_value, $sb_backup_file_name, $sb_uploaded_file_name, $sb_backup_file_export_dir, $sb_backup_file_export_url, $sb_banned_message_default, $sb_banned_ips_number, $sb_sql_banned_ips;
?>

<div class="wrap">
<h2>Spammer Blocker</h2>

<?php
######################## TRIGGERED ACTIONS ######################
if($_POST['sb_automatic_ip_address_duplication_button']){ //save automatic IP address duplication
	if($_POST['sb_automatic_ip_address_duplication'] == '1'){ //status "on" or "off"
		update_option('sb_automatic_ip_address_duplication', '1');

				wp_clear_scheduled_hook('sb_call_automatic_ip_address_duplication'); //remove cron for duplicating ip addresses -- we do not want the previous cron to run anymore
				update_option('sb_automatic_ip_address_duplication_recurrence', $_POST['sb_automatic_ip_address_duplication_recurrence']); //update recurrence
				wp_schedule_event(time(), $_POST['sb_automatic_ip_address_duplication_recurrence'], 'sb_call_automatic_ip_address_duplication'); //sb_automatic_ip_address_duplication() on


	}
	else { //switching off
		update_option('sb_automatic_ip_address_duplication', '0');
		wp_clear_scheduled_hook('sb_call_automatic_ip_address_duplication'); //remove cron for duplicating ip addresses
		remove_action('sb_call_automatic_ip_address_duplication', 'sb_automatic_ip_address_duplication'); //remove action for calling the function
	}

	if(isset($_POST['sb_spam_comment_automatic_elimination'])){ //checkbox - automatic comment elimination - "on" and "off"
		update_option('sb_spam_comment_automatic_elimination', '1');
	}
	else {
		update_option('sb_spam_comment_automatic_elimination', '0');
	}

	echo '<div class="updated"><p>Your settings have been saved.</p></div>'; //confirm message
}

if($_GET['sb_banned_message_preview'] == "1"){ //banned message preview
	echo eval('?>' . get_option('sb_banned_message') . '<?'); //show custom ban message
	exit(0);
}

if($_POST['sb_detection_of_spammers_button']){ //save ways of detecting spammers
	update_option('sb_detection_of_spammers_method', $_POST['sb_detection_of_spammers_method']); //update detection method type
	update_option('sb_detection_of_spammers_time', $_POST['sb_detection_of_spammers_time']); //update the cookie expiration

	echo '<div class="updated"><p>Your settings have been saved.</p></div>'; //confirm message
}

if($_POST['sb_access_restrictions_button']){ //save access restrictions
	update_option('sb_access_restrictions', $_POST['sb_access_restrictions']); //update type of restriction
	echo '<div class="updated"><p>Your settings have been saved.</p></div>'; //confirm message
}



if($_POST['sb_banned_message_button']){ //banned message
	update_option('sb_banned_message', stripslashes($_POST['sb_banned_message_textarea']));
	echo '<div class="updated"><p>Your custom ban message has been saved.</p></div>'; //confirm message
}

if($_POST['sb_banned_message_restore_default_value_button']){ //banned message - restore the default value
	update_option('sb_banned_message', $sb_banned_message_default);
	echo '<div class="updated"><p>The default value has been restored.</p></div>'; //confirm message
}



#################### managing IP addresses ######################
if($_POST['sb_ban_an_ip_address_button']){ //ban a single IP address
	if(filter_var($_POST['sb_ban_an_ip_address_input'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){ //checks if the ip address is valid

		$wpdb->query("INSERT IGNORE INTO $sb_ip_log (id, ip, last_visit, hits) VALUES ('0', '". $_POST['sb_ban_an_ip_address_input'] ."', '$sb_current_time', '0')");
		echo '<div class="updated"><p>The IP address '. $_POST['sb_ban_an_ip_address_input'] .' has been banned.</p></div>';
	}
	else {
		echo '<div class="error"><p>You must enter a valid IP address.</p></div>'; //error message
	}
}

if($_POST['sb_inactive_spammers_button']){ //delete old records from $sb_ip_log
	if($_POST['sb_inactive_spammers'] == 'week'){
		$sb_date_week_ago = date("Y-m-d H:i:s", strtotime('-1 week'));
		$wpdb->query("DELETE FROM $sb_ip_log WHERE last_visit < '$sb_date_week_ago'");
	}
	if($_POST['sb_inactive_spammers'] == 'month'){
		$sb_date_month_ago = date("Y-m-d H:i:s", strtotime('-1 month'));
		$wpdb->query("DELETE FROM $sb_ip_log WHERE last_visit < '$sb_date_month_ago'");	
	}
	if($_POST['sb_inactive_spammers'] == 'half year'){
		$sb_date_half_year_ago = date("Y-m-d H:i:s", strtotime('-6 months'));
		$wpdb->query("DELETE FROM $sb_ip_log WHERE last_visit < '$sb_date_half_year_ago'");
	}
	if($_POST['sb_inactive_spammers'] == 'year'){
		$sb_date_year_ago = date("Y-m-d H:i:s", strtotime('-12 months'));
		$wpdb->query("DELETE FROM $sb_ip_log WHERE last_visit < '$sb_date_year_ago'");
	}
	echo '<div class="updated"><p>All records older than a '. $_POST['sb_inactive_spammers'] .' have been deleted.</p></div>';
}

if($_POST['sb_delete_ip_address_button']){ //delete choosen IP address
	$wpdb->query("DELETE FROM $sb_ip_log WHERE ip='". $_GET['sb_delete_ip'] ."'");
	echo '<div class="updated"><p>The IP address '. $_GET['sb_delete_ip'] .' has been deleted.</p></div>';
}

if($_POST['sb_delete_all_records_button']){ //delete all records from $sb_ip_log
	$wpdb->query('TRUNCATE TABLE '. $sb_ip_log);
	echo '<div class="updated"><p>All IP addresses have been deleted.</p></div>';
}
#################### import/export ##############################
if($_POST['sb_import_ip_addresses_button']){ //import a backup file
	if($sb_uploaded_file_name == $sb_backup_file_name){ //checks if the name of uploaded file is valid

		if(!move_uploaded_file($_FILES['sb_uploaded_file']['tmp_name'], $sb_backup_file_export_dir)){
			echo '<div class="error"><p>The file could not be uploaded.</p></div>'; //error message
		}

		//import section
		$sb_backup_file_import_handle = fopen($sb_backup_file_export_dir, 'r');
		while(($sb_csv_row = fgetcsv($sb_backup_file_import_handle, ',')) !== FALSE){
       			mysql_query("INSERT IGNORE INTO $sb_ip_log(id,ip,last_visit,hits) VALUES('$sb_csv_row[0]','$sb_csv_row[1]','$sb_csv_row[2]','$sb_csv_row[3]')");
		}
		fclose($sb_backup_file_import_handle);

		echo '<div class="updated"><p>All IP addresses from your backup have been imported.</p></div>';
	}
	else {
		echo '<div class="error"><p>The name of the imported file must be "'. $sb_backup_file_name .'".</p></div>'; //error message
	}
}

if($_POST['sb_make_a_backup_button']){ //export - creating a backup file
	$sb_backup_query = mysql_query("SELECT id, ip, last_visit, hits FROM $sb_ip_log");

	while ($sb_backup_file_export_row = mysql_fetch_array($sb_backup_query)){
		$sb_backup_file_export_write = $sb_backup_file_export_write . $sb_backup_file_export_row['id'] .','. $sb_backup_file_export_row['ip'] .','. $sb_backup_file_export_row['last_visit'] .','. $sb_backup_file_export_row['hits'] ."\n"; //the quotes must be here instead apostrophes, or the new line will not be created; $sb_backup_file_export_write = $sb_backup_file_export_write . has to be there repeated or only one line is exported
	}

	@$sb_backup_file_export = fopen($sb_backup_file_export_dir, 'w');
	@fwrite($sb_backup_file_export, $sb_backup_file_export_write);
	@fclose($sb_backup_file_export);

	if(file_exists($sb_backup_file_export_dir)){
		echo '<div class="updated"><p>Your <a href="'. $sb_backup_file_export_url .'">backup</a> has been created.</p></div>';
	}
	else {
		echo '<div class="error"><p>Your backup could not be created. Change the permissions of the directory <code>'. dirname(__FILE__) .'</code> to 777 first.</p></div>'; //error message
	}
}


#################################################################
#################################################################
########################## USER INTERFACE #######################
#################################################################
#################################################################
?>

<div id="poststuff" class="metabox-holder has-right-sidebar">
	<div class="inner-sidebar">
		<div id="side-sortables" class="meta-box-sortabless" style="position: relative;">
	 								
			<div class="postbox" style="background:#f0fff0;">
				<div class="inside" style="background:#f0fff0;">


<p><span>Latest news:</span><span style="float:right;"><a href="http://twitter.com/spammerblocker">@spammerblocker</a></span><br>

<ul id="twitter_update_list" style="padding:0;margin:0;">
<li>Loading Tweets..</li>
</ul>

	<style type="text/css">
	#twitter_update_list li {
	background:#ffffff;padding:5px;margin-bottom:3px;border: 1px solid #dfdfdf;
	}
	</style>
<!-- the javascript loading tweets is located at the end of the file  -->
</p>




<p style="border-top:1px solid gray;margin-top:10px;padding-top:9px;">Need help? Visit <a href="http://wordpress.org/extend/plugins/spammer-blocker/faq">FAQ</a> or the <a href="http://wordpress.org/support/plugin/spammer-blocker">support forum</a>.</p>

<p>If you like this plugin, please <a href="http://wordpress.org/extend/plugins/spammer-blocker/">rate it</a> or <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JD64C5FTRMQXC&lc=CZ&item_name=Spammer%20Blocker%20%2d%20donation&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">consider donating</a>. Even just a few dollars motivate me to continue working on the plugin. Thank you.</p>


				</div>
			</div>
			<div class="postbox">
				<h3 style="cursor:default;">Adding IP addresses</h3>
				<div class="inside">
					<p>If you know a bot's IP address, you can block it directly without marking a comment as spam.</p>
					<p>
						<form action="<?php echo admin_url('options-general.php?page=spammer-blocker'); ?>" method="post">
							<input type="text" name="sb_ban_an_ip_address_input" maxlength="15" value="" style="width:123px;">
							<input class="button-highlighted" style="float:right;" type="submit" name="sb_ban_an_ip_address_button" value=" Ban IP address ">
						</form>
					</p>

					<hr style="border: 1px solid #dfdfdf;margin-bottom:10px;margin-top:10px;">
					<p>You can also import records from a backup. New IP addresses will be added to the existing ones.</p>
					<form enctype="multipart/form-data" action="<?php echo admin_url('options-general.php?page=spammer-blocker'); ?>" method="post">
						<p style="text-align:center;margin-top:10px;">
							<input type="file" size="3" name="sb_uploaded_file">
							<input class="button" style="float:right;" type="submit" name="sb_import_ip_addresses_button" value=" Import data ">
						</p>
					</form>
				</div>
			</div>

			<div class="postbox">
				<h3 style="cursor:default;">Banned IP addresses (<?php echo $sb_banned_ips_number; ?>)</h3>
				<div class="inside">
<?php
if(mysql_num_rows($sb_sql_banned_ips) == 0){
	echo '<p>There are not any records.</p>';
}
else {
	echo '<p>Entries in bold have been added manually.</p>

<div style="overflow:auto;max-height:163px;">
<table style="margin-top:10px;margin-left:auto;margin-right:auto;"><tr>
	<td></td>
		<td style="text-align:center;padding-right:6px;"><strong>IP address</td>
		<td style="text-align:center;padding-left:20px;"><strong>Last visit</strong></td>
	</tr>'; //table head

	while($sb_banned_ips_list = mysql_fetch_array($sb_sql_banned_ips)){
		$sb_comment_id = $wpdb->get_var("SELECT id FROM $sb_ip_log WHERE ip='". $sb_banned_ips_list['ip'] ."'"); //gets id
		$sb_ip_last_visit = $wpdb->get_var("SELECT last_visit FROM $sb_ip_log WHERE ip='". $sb_banned_ips_list['ip'] ."'");
		$sb_ip_hits = $wpdb->get_var("SELECT hits FROM $sb_ip_log WHERE ip='". $sb_banned_ips_list['ip'] ."'");

		$sb_ip_last_visit_strtotime = strtotime($sb_ip_last_visit);
		$sb_current_date_strtotime = strtotime($sb_current_time);
		$sb_ip_last_visit_days_ago = round(($sb_current_date_strtotime - $sb_ip_last_visit_strtotime)/(86400), 0);

			 //link to delete ip
		echo '<tr><td><form action="'. admin_url('options-general.php?page=spammer-blocker') .'&sb_delete_ip='. $sb_banned_ips_list['ip'] .'" method="post">';
		echo '<input type="submit" name="sb_delete_ip_address_button" value=" × " style="background-color:transparent;text-decoration:underline;border:none;cursor:pointer;color:darkred;padding:0;margin:0;"></form></td>';

			 //show ip address
		echo '<td style="padding-left:6px;">';
		if($sb_comment_id == 0){ //if ID is 0 record was manually added
			echo '<strong>';
		}
		echo '<a href="'. admin_url('edit-comments.php?s='. $sb_banned_ips_list['ip'] .'&amp;comment_status=spam') .'" title="View spammer\'s comments">'. $sb_banned_ips_list['ip'] .'</a>';
		if($sb_comment_id == 0){ //if ID is 0 record was manually added
			echo '</strong>';
		}
		echo '</td>';

			 //show last visit
		echo '<td style="padding-left:5px;text-align:right;"><span style="color:gray;"><abbr title="Hits: '. $sb_ip_hits .'">';
			if($sb_ip_hits == 0){ //spammer never visited our blog, print Never
					echo 'Never';
			}else{
				if($sb_ip_last_visit_days_ago == 0){ //spammer visited our blog, print Today or 'X days ago'
					echo 'Today';
				}else{
					echo $sb_ip_last_visit_days_ago .' days ago';
				}
			}//-else
		echo '</abbr></span></td></tr>';

	}
			//end of list, buttons
	echo '</table></div>
<form action="'. admin_url('options-general.php?page=spammer-blocker') .'" method="post">
	<p style="margin-top:10px;text-align:center;">
		<input class="button-highlighted" type="submit" name="sb_make_a_backup_button" value=" Make a backup ">
		<input class="button" type="submit" style="float:right;" name="sb_delete_all_records_button" value=" Delete all records " onclick="return confirm(\'Do you really want to erase all records?\');">
	</p>
</form>';
} //-else
?>
				</div>
			</div>
			<div class="postbox">
				<h3 style="cursor:default;">Forget about inactive spammers</h3>
				<div class="inside">
					<form action="<?php echo admin_url('options-general.php?page=spammer-blocker'); ?>" method="post">
					<p>Get rid of old, useless records! Delete all IP addresses of spammers who haven't visited your blog for a 
						<select size="1" name="sb_inactive_spammers">
							<option value="week">week</option>
							<option value="month" selected="selected">month</option>
							<option value="half year">half year</option>
							<option value="year">year</option>
						</select>.
					</p>
					<p><center><input class="button" type="submit" name="sb_inactive_spammers_button" value=" Delete records " onclick="return confirm('Do you really want to erase all records for the specified period of time?')"></center></p>

					<p>This tool will help you prevent slowing down your blog.</p>
					</form>
				</div>
			</div>
		</div>
	</div>


	<div class="has-sidebar sm-padded">
		<div id="post-body-content" class="has-sidebar-content">
			<div class="meta-box-sortabless">


				<div class="postbox">
					<h3 style="cursor:default;">Access restrictions</h3>
					<div class="inside">
						<form action="<?php echo admin_url('options-general.php?page=spammer-blocker'); ?>" method="post">					<p>What should Spammer Blocker do when it detects a spammer?</p>

						<p><label><input type="radio" name="sb_access_restrictions" value="1" <?php if(get_option('sb_access_restrictions') == 1){ echo ' checked="checked"'; } ?>> <strong>Disable commenting</strong> (recommended) - disallows posting comments</label></p>

						<p><label><input type="radio" name="sb_access_restrictions" value="2" <?php if(get_option('sb_access_restrictions') == 2){ echo ' checked="checked"'; } ?>> <strong>Disable access to the whole blog</strong> - prevents viewing any page</label></p>

						<p><input class="button" type="submit" name="sb_access_restrictions_button" value=" Save changes ">
						</form>




					</div>
				</div>

				<div class="postbox">
					<h3 style="cursor:default;">Detection of spammers</h3>
					<div class="inside">
						<form action="<?php echo admin_url('options-general.php?page=spammer-blocker'); ?>" method="post">
						<p><label><input type="radio" name="sb_detection_of_spammers_method" value="1" <?php if(get_option('sb_detection_of_spammers_method') == 1){ echo ' checked="checked"'; } ?>> <strong>Fast method</strong> (recommended) - all visitors (except for the majority of bots) will get a cookie and their IP addresses will be rechecked again in a </label>

						<select size="1" name="sb_detection_of_spammers_time">
							<option value="31104000" <?php if(get_option('sb_detection_of_spammers_time') == 31104000){ echo ' selected="selected"'; } ?>>year</option>
							<option value="15552000" <?php if(get_option('sb_detection_of_spammers_time') == 15552000){ echo ' selected="selected"'; } ?>>half year</option>
							<option value="2592000" <?php if(get_option('sb_detection_of_spammers_time') == 2592000){ echo ' selected="selected"'; } ?>>month</option>
							<option value="604800" <?php if(get_option('sb_detection_of_spammers_time') == 604800){ echo ' selected="selected"'; } ?>>week</option>
							<option value="86400" <?php if(get_option('sb_detection_of_spammers_time') == 86400){ echo ' selected="selected"'; } ?>>day</option>
						</select>.
						</p> 

						<p><label><input type="radio" name="sb_detection_of_spammers_method" value="2" <?php if(get_option('sb_detection_of_spammers_method') == 2){ echo ' checked="checked"'; } ?>> <strong>Slow method</strong> - the plugin will recheck everyone's IP address for each page reload. This option may slow down your blog if your list of IP addresses is too long.</label></p>
						<p><input class="button" type="submit" name="sb_detection_of_spammers_button" value=" Save changes "></p>
						</form>

					</div>
				</div>


				<div class="postbox">
					<h3 style="cursor:default;">Banned message</h3>
					<div class="inside">
						<form action="<?php echo admin_url('options-general.php?page=spammer-blocker'); ?>" method="post">
						<p>You can customize the page shown to bad guys when they are detected. Using PHP code is allowed. | <a href="<?php echo admin_url('options-general.php?page=spammer-blocker&sb_banned_message_preview=1'); ?>">Preview</a></p>
						<p><textarea style="height:230px;width:100%;" name="sb_banned_message_textarea"><?php echo get_option('sb_banned_message'); ?></textarea></p>
						<p><input class="button-highlighted" type="submit" name="sb_banned_message_button" value=" Save changes "> 
						<input class="button" type="submit" name="sb_banned_message_restore_default_value_button" onclick="return confirm('Do you really want to restore the default value?')" value=" Restore default value "></p>
						</form>
					</div>
				</div>

				<div class="postbox">
					<h3 style="cursor:default;">Automatic IP address duplication</h3>
					<div class="inside">
						<table><tr>
						<td style="width:50%;" valign="top">
							<form action="<?php echo admin_url('options-general.php?page=spammer-blocker'); ?>" method="post">
							<p>Status: 
								<label><input type="radio" name="sb_automatic_ip_address_duplication" value="1" <?php if(get_option('sb_automatic_ip_address_duplication') == 1){ echo ' checked="checked"'; } ?>> On</label>
								<label><input type="radio" name="sb_automatic_ip_address_duplication" value="0" <?php if(get_option('sb_automatic_ip_address_duplication') == 0){ echo ' checked="checked"'; } ?>> Off</label>
							</p>

							<p>Duplication recurrence:  
								<select size="1" name="sb_automatic_ip_address_duplication_recurrence">
									<option value="hourly" <?php if(get_option('sb_automatic_ip_address_duplication_recurrence') == 'hourly'){ echo ' selected="selected"'; } ?>>hourly</option>
									<option value="twicedaily" <?php if(get_option('sb_automatic_ip_address_duplication_recurrence') == 'twicedaily'){ echo ' selected="selected"'; } ?>>twice daily</option>
									<option value="daily" <?php if(get_option('sb_automatic_ip_address_duplication_recurrence') == 'daily'){ echo ' selected="selected"'; } ?>>daily</option>
								</select>
							</p>

							<p><label><input type="checkbox" name="sb_spam_comment_automatic_elimination" <?php if(get_option('sb_spam_comment_automatic_elimination') == '1'){ echo ' checked="checked"'; } ?>> <em>Delete all spam comments after the duplication is done</em></label>							</p>
							<p><input class="button" type="submit" name="sb_automatic_ip_address_duplication_button" value=" Save changes "></p>
							</form>

									<p style="margin-top:30px;">
									Last cron run: <?php if(get_option('sb_automatic_ip_address_duplication_last_cron_run_time') == 0){echo 'Never';}else{echo date('Y-m-d H:i:s', get_option('sb_automatic_ip_address_duplication_last_cron_run_time')+get_option('gmt_offset')*3600);} ?><br>

									Next cron run: <?php if(wp_next_scheduled('sb_call_automatic_ip_address_duplication') == TRUE){echo date('Y-m-d H:i:s', wp_next_scheduled('sb_call_automatic_ip_address_duplication')+get_option('gmt_offset')*3600); }else{echo '?';} ?>
								</p>
						</td>
						<td style="width:50%;" valign="top">
							<p>Set the delay between each automatic duplication of IP addresses (belonging to the comments which were marked as spam) from the wp_comments table to the plugin's table. This is useful when you use Akismet to recognize spammers.</p>
							<p>It is recommended to keep your <a href="<?php echo admin_url('edit-comments.php?comment_status=spam'); ?>">spam folder</a> as clean as possible to avoid slowing down your blog.</p>
<p>The duplication will start immediately after you change the status to "On" and click the button "Save changes".</p>
						</td>
						</tr></table>

					</div>
				</div>



			</div>
		</div>
	</div>

</div>
</div>

<!-- scripts for loading tweets in the FYI box -->
<script type="text/javascript" src="http://twitter.com/javascripts/blogger.js"></script>
<script type="text/javascript" src="http://twitter.com/statuses/user_timeline/spammerblocker.json?callback=twitterCallback2&count=2&exclude_replies=true"></script>

<?php } //-if current_user_can()
else {
	wp_die( __('You do not have sufficient permissions to access this page.') );
}
} //-function options page
?>

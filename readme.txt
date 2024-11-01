=== Spammer Blocker ===
Contributors: Lelkoun
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JD64C5FTRMQXC&lc=CZ&item_name=Spammer%20Blocker%20%2d%20donation&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: comment, comments, commenting, spam, spammer, spammers, ban, access, deny, block, cookie, cookies, bot, bots, anti-spam, ip, akismet, user, users, visitor, visitors
Requires at least: 2.9
Tested up to: 3.3.2
Stable tag: trunk

This plugin prevents users from commenting or viewing your website if a comment of theirs has been marked as spam.

== Description ==
If you are tired of blocking spammers' IP addresses manually, you can use this plugin. It blocks all visitors who posted a comment that was later **marked as spam** or prevents them from posting comments. For example if your Akismet catches a spam comment, its author will be muted/blocked until you delete the spammer's IP address from your database.

= Features =
* Advanced tools for managing banned IP addresses
* Simple tool for importing and exporting records
* Automatic banning of spammy IP addresses
* Automatic removing of spam comments
* Configurable banned message
* Two ways of detecting spammers
* Two ways of restricting access to spammers

**No more mess!** All plugin data will be automatically removed from your database after you delete the plugin.

== Installation ==
1. Download the plugin, extract and upload it to your plugins folder on your server.
2. Activate the plugin.
3. Configure the plugin (Settings → Spammer Blocker)

== Screenshots ==
1. Plugin administration interface overview
2. Tool for duplicating and deleting IP addresses
3. Tool for switching the way of detecting spammers
4. Configurable banned message
5. Contact form for reporting bugs
6. Tool for adding IP addresses and importing records
7. Tool for deleting all or selected records and creating backups
8. Tool for deleting old records

== Frequently Asked Questions ==
= Where does the plugin store its data in my MySQL database? =
SB stores banned IP addresses in a table called "wp_sb_ip_log". Plugin's settings can be found in the table "wp_options":

* sb_plugin_version
* sb_detection_of_spammers_method
* sb_detection_of_spammers_time
* sb_banned_message
* sb_automatic_ip_address_duplication
* sb_automatic_ip_address_duplication_recurrence
* sb_automatic_ip_address_duplication_last_cron_run_time
* sb_spam_comment_automatic_elimination
* sb_access_restrictions

= What should I do if I accidentally marked my own comment as spam and the plugin has blocked my IP address? =
Delete your IP address from the table "wp_sb_ip_log" in your MySQL database.

= What should I do if there is a spammer who has a cookie and is still able to post comments even if I marked the comments as spam? =
Wait until spammer's cookie expires or use the slow method of detection of spammers. When the spammer visits your blog again, their cookie will be deleted.

= What happens if I add the same IP address more than once? =
Nothing. Duplicate records are automatically filtered.

= I got an error when I finished updating the plugin. =
Uninstall and install the plugin again. I try to avoid all errors that may be caused by updating to a newer version, but nobody is perfect, so this might help in the case that I forgot to prepare something for an update (typically new names of the plugin's options in the wp_options table in the MySQL database).

= Are IPv6 addresses supported? =
No. (That may change in the future.)

= I have another problem that is not described on this page or the forum. =
Post a new thread on the forum.

== Changelog ==
= 1.5 =
* options sb_detection_of_spammers_method and sb_detection_of_spammers_time are rewrited to a new default value, sorry folks!
* default value of option sb_automatic_ip_address_duplication_last_cron_run_time is 0 now
* the plugin engine (IP check and ban) is not runned if there are not any banned IP addresses
* all screenshots (except for the overview one) were removed, the donate button as well
* a notification is displayed after activation/upgrade of the plugin
* error message displayed when the uploaded file has a different name was specified
* function sb_update_last_visit() renamed to sb_update_spammer_info()
* new option/variable/MySQL field added -> hits
* spammer hit (page request) counter added
* option renamed: sb_detection_of_spammers_cookies -> sb_detection_of_spammers_method
* if an IP address is added, the last visit is showed as 'Never'
* all times are converted to the local time instead of GMT (cron, last visit)
* link for deleting single IP addresses replaced with a form + button
* forms and links use the admin_url() function now
* design of FYI block changed - now includes 2 latest news from Twitter account @spammerblocker
* ifs added to check for a version when updating from an older version
* position of blocks with settings changed
* uninstall.php file updated
* new variables: $sb_sql_banned_ips, $sb_banned_ips_number (marked as global)
* new options: sb_access_restrictions, sb_plugin_activation_notice
* the option to disable commenting instead blocking access to the whole blog added
* contact form removed
* donation URL changed
* number of banned IP addresses added
* displaying '-0' after adding a IP address fixed
* grammar errors in readme.txt corrected

= 1.4 =
* added a check for WP_UNINSTALL_PLUGIN in uninstall.php
* added sb_update_plugin() function
* added a confirmation dialog for deleting records
* added a new option in the <select> menu - "year"
* added a new function sb_create_db_table() with dbDelta for effective updating the plugin table
* added class "button-highlighted" to some buttons
* added options "year" and "half year" to the cookie check function
* fixed unbalanced quotes (line 372)
* fixed a bug causing saving the cookie expiration date to the value of the cookie
* admin_init was replaced with plugins_loaded (update function)
* changed "if(" and "else{" to "if (" and "else {"
* changed a position of "Forget about inactive spammers" box (below list of IPs)
* changed a position of two buttons (below the list of IPs)
* centered list of IPs and buttons
* removed ping function that updated my stats about plugin users
* the list of banned IP addresses is ordered by last_visit (ascending mode)
* all "badly" used apostrophes and quotes were fixed -> the plugin should be slightly faster now
* deactivation function commented out (it was required only for updating my user counter)
* minor design changes
* added more text
* added automatic IP address duplicating function with a cron
* added a function for automatic removing spam comments
* uninstall.php file updated
* deleted unnecessary valuse checks, replaced with $_POST['sb_detection_of_spammers_time'], $_POST['sb_detection_of_spammers_cookies'], $_POST['sb_automatic_ip_address_duplication_recurrence']
* changed the form action URL to absolute format
* deactivate, activate and update functions were moved deeped in the file
* the function for restoring the default value of the banned message is not done by javascript now but by a POST method instead
* added a confirm dialog to the button for restoring the default value of the banned message
* when is a visitor recognized as a spammer with a cookie, the cookie will be deleted when it will be switched to the slow method of detection
* fixed bug with the empty cookie value - it has to have some value!

= 1.3 =
* added a tool for uploading backups from a computer (instead importing from FTP)
* added a preview of banned message
* added file uninstall.php that removes all saved data from the database
* added function that restores a default value of banned message
* added new option - sb_plugin_version
* added version check function
* added wp_die error for users without sufficient permissions
* banned message is always stripslahed during saving to the DB
* SB returns 403 HTTP header status code to banned spammers
* adding, updating and deleting options is now provided by WP Options API
* comments marked as spam can be added to wp_sb_ip_log by non-administrators
* the sender's e-mail address is checked via filter_var
* sended e-mails are stripsplashed
* the blog's URL was removed from the contact form
* file sb-options.php is no longer used because a lot of complications
* a capabilitity is used instead a role name (administrator)
* removed hardcoded plugin's name from variables used for getting plugin's dir
* fixed bug causing changing name of an imported backup file
* fixed several grammar mistakes
* minor changes in activation queries
* minor changes in readme.txt, added new screenshots

= 1.2 =
* added a tool for exporting and importing banned IP addresses
* IP addresses are checked for validity (PHP 5.2.0 required)
* added "Buy Me a Coffee!" button

= 1.1 =
* settings from the database are no longer being deleted after deactivating the plugin
* names of some variables have been changed
* old options in the database will be deleted after the upgrade

= 1.0 =
* all IP addresses are stored in a special table
* added a function for manual adding IP addresses
* added a function for deleting IP addresses by a time period
* added a function for deleting choosen IP addresses
* fixed a way of showing banned message
* function file_get_contents() is not required anymore

= 0.9.1 =
* SB is faster and more secured now
* banned message bug fixed
* database connection improved
* list containing IP addresses improved

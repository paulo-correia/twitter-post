<?php
/**
 * @package Twitter Post
 */
/*
Plugin Name: Twitter Post
Plugin URI: http://correiatec.com.br/twitter-post
Description: Get <strong>Title and Content</strong> of an <strong>Post or Page</strong> and send to Twitter. To get started: activate the Twitter Post plugin and then go to your Twitter Post Settings page to set up yours keys
Version: 1.0.0
Author: Paulo Correia
Author URI: http://correiatec.com.br
Domain Path: /languages
*/
register_activation_hook( __FILE__, 'tw_activation' );
register_deactivation_hook(__FILE__, 'tw_deactivation' );

function tw_debug($message) {
	if( WP_DEBUG === true ) {
		if ( (is_array($message)) || (is_object($message)) ) {
			error_log("[Twitter Post] ".print_r($message, true));
		} else {
			error_log("[Twitter Post] ".$message);
		}
	}
}

function tw_activation() {
	tw_debug("Enabled");
}

function tw_deactivation() {
	tw_debug("Disabled");
}

function isExtensionLoaded($extension_name){
	return extension_loaded($extension_name);
}

load_plugin_textdomain( 'twitter-post', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

function tw_menu_html() {
        if (!current_user_can('manage_options')) {
        	return;
	}

	function tw_show_option($option) {
		$tw_options = get_option('twitterpost_options');
		if (isset($tw_options[$option])) {
	                return $tw_options[$option];
	        }
	}

	$flag_curl=1;
	$flag_oauth=1;

	if (!isExtensionLoaded('curl')) {
		tw_debug("Curl Disabled");
		$flag_curl=0;
	}

	if (!class_exists('OAuth')) {
		tw_debug("OAuth Disabled");
		$flag_oauth=0;
	}

	if ( ($flag_curl==0) && ($flag_oauth==0) ) {
		tw_debug("Error: Curl and Oauth Disabled!");
		?>
		<div class="wrap">
			<h1><? _e('Warning', 'twitter-post');  ?></h1><br>
			<? _e('Is missing Curl or OAuth in PHP', 'twitter-post') ?><br>
			<? _e('Contact your server administrator', 'twitter-post')?>.  
		</div>
		<?php
		exit;
	}

	?>
	<div class="wrap">
		<div>
			<h1><? _e(get_admin_page_title(), 'twitter-post'); ?></h1>
			<form method="post">
			<div>
				&nbsp;
			</div>
			<div>
				<label for="api_key">Api Key:</label>
			        <input type="text" name="api_key" size="30" style="margin-left: 36px;" value="<?php echo tw_show_option("api_key");?>" >
			</div>
			<div>
			        <label for="api_secret">Api Secret:</label>
			        <input type="text" name="api_secret" size="50" style="margin-left: 20px;" value="<?php echo tw_show_option("api_secret");?>" >
		        </div>
		        <div>
		                <label for="oauth_token">Oauth Token:</label>
		                <input type="text" name="oauth_token" size="50" style="margin-left: 6px;" value="<?php echo tw_show_option("oauth_token");?>" >
		        </div>
		        <div>
		                <label for="oath_secret">Oauth Secret:</label>
		                <input type="text" name="oauth_secret" size="50" value="<?php echo tw_show_option("oauth_secret");?>" >
		        </div>
			<div>
				<label for="screen_name"><? _e('Screen Name', 'twitter-post') ?>:</label>
				<input type="text" name="screen_name" size="20" value="<?php echo tw_show_option("screen_name");?>" placeholder="@screen_name"> <? _e('Without "@"', 'twitter-post') ?>
			</div>
			<div>
				&nbsp;
			</div>
			<div>
				<? _e('Publish an App and', 'twitter-post') ?> <a href="https://developer.twitter.com" target="_blank"> <? _e('Get the Keys', 'twitter-post') ?></a>
			</div>
	<?
	settings_fields('twitterpost_options');
	do_settings_sections('twitterpost');
	submit_button(__('Save Settings', 'twitter-post'));
        ?>
               		</form>
		</div>
	    </div>
        <?php
}

function tw_menu_page() {
	add_menu_page(
		__('Settings', 'twitter-post'),
		__('Twitter Post Options', 'twitter-post'),
		'manage_options', 
		'twitter-post.php',
		'tw_menu_html',
		plugin_dir_url(__FILE__) . 'assets/images/twlogo.png',
		20
	);
}

add_action('admin_menu', 'tw_menu_page');

function publish_post_tweet($post_ID) {
	$post_data = get_post($post_ID, ARRAY_A);
	$permalink = get_permalink($post_ID);

	tw_debug("Post ID: ".$post_ID);
	tw_debug("Url: ".$permalink);
	tw_debug("Post Data");
	tw_debug($post_data);
	
	$flag_curl=1;
	$flag_oauth=1;

	if (!isExtensionLoaded('curl')) {
		$flag_curl=0;
	}

	if (!class_exists('OAuth')) {
		$flag_oauth=0;
	}

	include_once "includes/send-twitter.php";
}

add_action('publish_post', 'publish_post_tweet');

if (isset($_POST["submit"])) {

	if ( (isset($_POST["api_key"])) && (isset($_POST["api_secret"])) && (isset($_POST["oauth_token"])) &&
		(isset($_POST["oauth_secret"])) && (isset($_POST["screen_name"])) ) {

		$arr_data = ["api_key"=>$_POST["api_key"], "api_secret"=>$_POST["api_secret"], "oauth_token"=>$_POST["oauth_token"],
			"oauth_secret"=>$_POST["oauth_secret"], "screen_name"=>$_POST["screen_name"] ];

		update_option('twitterpost_options', $arr_data);
	}
}

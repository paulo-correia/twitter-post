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
*/
if ( ($flag_curl==0) && ($flag_oauth==0) ) {
	tw_debug("Error: Curl and OAuth Extension Disabled!");
}

$is_curl=0;
$is_oauth=0;

if ( ($flag_curl==1) || ($flag_oauth==1) ) {
	if ( ($flag_curl==1) && ($flag_oauth==0) ) {
		tw_debug("Curl Extension Enabled");
		$is_curl=1;
	}
	if ( ($flag_curl==0) && ($flag_oauth==1) ) {
		tw_debug("OAuth Extension Enabled");
		$is_oauth=1;
	}
	if ( ($flag_curl==1) && ($flag_oauth==1) ) {
		tw_debug("Curl and OAuth Extensions Enabled");
		$is_oauth=1;
	}
}

function check_option ($option, &$flag_publish) {
	$options_tw = get_option('twitterpost_options');

	$options_name = [ "api_key"=>"Api Key", "api_secret"=>"Api Secret", "oauth_token"=>"Oauth Token",
		"oauth_secret"=>"Oauth Secret" ];

	if (!array_key_exists($option, $options_name)) {
		tw_debug("Don't have the name ".$option);
		$flag_publish[$option]=1;
	}

	if (!isset($options_tw[$option])) {
		if (strlen($options_tw[$option])<1) {
			tw_debug("Don't have ".$options_name[$option]);
			$flag_publish[$option]=1;
		}
	} else {
		if (strlen($options_tw[$option])<1) {
			tw_debug("Don't have ".$options_name[$option]);
			$flag_publish[$option]=1;
		}
	}

	return $flag_publish;
}

$options_check = ["api_key", "api_secret", "oauth_token", "oauth_secret"];

$flag_publish=[];

foreach ($options_check as $check) {
	$flag_return = check_option($check, $flag_publish);
}

if (count($flag_return)>0) {
	tw_debug("Don't post on Twitter");
} else {
	tw_debug("Will put on Twitter");

	$options_tw = get_option('twitterpost_options');

	if ( ($is_curl==0) && ($is_oauth==0) ) {
		tw_debug("Curl and OAuth Extension Disabled!");
	}

	if ($is_curl==1) {
		$consumer_key    = $options_tw["api_key"];
		$consumer_secret = $options_tw["api_secret"];
		$user_token      = $options_tw["oauth_token"];
		$user_secret     = $options_tw["oauth_secret"];
		$screen_name     = $options_tw["screen_name"];

		$content = $post_data["post_content"];
		$title   = $post_data["post_title"];

		$dir = plugin_dir_path( __FILE__ );

		include_once $dir."/tmhOAuth.php";

		$url = '1.1/statuses/update';

		$connection = new tmhOAuth( ['consumer_key'    => $consumer_key,
					     'consumer_secret' => $consumer_secret,
					     'user_token'      => $user_token,	
					     'user_secret'     => $user_secret] );

		$status = mb_substr($title."\n".$content, 0, 150)."\n".$permalink;

		$connection->request('POST', $connection->url($url), ['status' => $status] );

		$response_code = $connection->response['code'];
	
		$response_data = json_decode($connection->response['response'],true);

		if ($response_code != 200) {
			tw_debug("Error: Curl ".$response_code);
		} 

		$return = $connection->response['response'];
		tw_debug("Curl Return Data: ".$return);
	}	

	if ($is_oauth==1) {
		$api_key      = $options_tw["api_key"];
		$api_secret   = $options_tw["api_secret"];
		$oauth_token  = $options_tw["oauth_token"];
		$oauth_secret = $options_tw["oauth_secret"];
		$screen_name  = $options_tw["screen_name"];

		$content = $post_data["post_content"];
                $title   = $post_data["post_title"];

		try {
			$oauth = new OAuth($api_key, $api_secret, OAUTH_SIG_METHOD_HMACSHA1);

			$oauth->enableDebug();
			$oauth->setToken($oauth_token, $oauth_secret);

			$base = 'https://api.twitter.com';

			$status = mb_substr($title."\n".$content, 0, 150)."\n".$permalink;

			$args = ['status'=> $status];
			
			$tweet = $oauth->fetch($base.'/1.1/statuses/update.json', $args, OAUTH_HTTP_METHOD_POST);

			$json = json_decode($oauth->getLastResponse());
		
			$return = $oauth->getLastResponse();

			tw_debug("OAuth Return Data: ".$return);	

		} catch (OAuthException $e) {
			tw_debug("Error: OAuth Exception ".$e);
		}
		
	}

}	

<?php 
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

	function curl_posting($fields,$url)
	{
		if(in_array('curl',get_loaded_extensions())){
			$fields_string = '';
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string, '&');

			//open connection
			$ch = curl_init();

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_POST, count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			//execute post
			$result = curl_exec($ch);
			//close connection
			curl_close($ch);
			return $result;
		}else{
			$args = array('method'=>'POST','body'=>$fields);
			$response = wp_remote_post($url,$args);
			return $response['body'];
		}
	}
	$api_url = get_option('fs_api_base').'/wordpress_links';
	$blogname = get_option('blogname');
	$data = array('active' => 0, 'link'=>get_site_url());
	curl_posting($data,$api_url);
	// $api_url = get_option('fs_api_base').'/blog_plugin_uninstall';
	// $code = get_option('focusedstamps_secret_key');
	// $time = time();
	// $final_code = hash_hmac('sha1',$time,$code);
	// $data = array('link'=>get_site_url(),'time'=>$time,'code'=>$final_code);
	// $metrics = curl_posting($data,$api_url);

delete_option('focusedstamps_secret_key');
delete_option('fs_activated');
delete_option('fs_options');
delete_option('fs_api_base');
delete_option('rewardial_server');


//drop a custom db table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_actions" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_actions" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_attributes" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_attributes" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_attributes_users" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_attributes_users" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_comments" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_comments" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_quests" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_quests" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_quest_alerts" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_quest_alerts" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_quest_questions" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_quest_questions" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_quest_steps" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_quest_steps" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_quest_user" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_quest_user" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_users" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_users" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_quest_questions_points" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_quest_questions_points" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}focusedstamps_quest_question_answers" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rwd_quest_question_answers" );

//note in multisite looping through blogs to delete options on each blog does not scale. You'll just have to leave them.

?>
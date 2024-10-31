<?php 
/* 
Plugin Name: Rewardial Engagements
Version: 1.1.2.0
Author: Puga Software
Description: Your gateway to the Rewardial marketplace.
*/

global $wpdb;

	function curl_posting($fields,$url) {
		
		if(in_array('curl',get_loaded_extensions() )){



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
      if ( is_array ($response) ) {
          return $response['body'];

      } else {
          $errmsg = '';
          $codes = $response->get_error_codes();
          foreach ( $codes as $code ) {
              $errmsg .= $response->get_error_message($code) . ' ';
          }

          return $errmsg;
      }
		}
	}

	function rwd_after_activate_plugin(){

		
		global $api_url;   
		global $wpdb;
		$rwd_options = array(
			'last_update' => time(),
			'update_interval' => 24 * 60 * 60 // 24 hours, 60 minutes, 60 seconds
			);
			
		$plugin_data = get_plugin_data( __FILE__);
		$plugin_version  = 'unavailable';        
    if ( isset($plugin_data['Version']) ) {
        $plugin_version  = $plugin_data['Version'];
    }
		
		$api_url = 'http://rewardial.com/api2';
		$test = curl_posting(array('link'=>get_site_url(),'active'=>1),$api_url.'/activate_blog2'); // save active plugin on the main platform
		if($test == 'email_sended'){
			$api_url_log = get_rwd_api_url('/save_blog_logs');
			$wp_version = get_bloginfo('version');
			$data2 = array('action'=>'activate','link'=>get_site_url(),'version'=>$plugin_version, 'wp_version'=>$wp_version);
			curl_posting($data2,$api_url_log);
			
			if (!get_option('rwd_api_base'))
				add_option('rwd_api_base',$api_url);
			else
				update_option('rwd_api_base',$api_url);
			
			if (!get_option('rwd_options'))
				add_option('rwd_options', $rwd_options);
			else
				update_option('rwd_options',$rwd_options);
			
			if(get_option('rwd_activated')){
				update_option('rwd_activated',' ');
			}else{
				add_option('rwd_activated',' ');
			}
			
			if (get_option('rwd_secret_key'))
				update_option('rwd_redirect',admin_url('/admin.php?page=rwd-settings&onboarding=1'));
			else
				add_option('rwd_redirect',admin_url('/admin.php?page=rewardial'));	
		}
		
	}
	
	add_action('admin_init','rwd_redirect');
	function rwd_redirect(){
		$redirect = get_option('rwd_redirect');
		delete_option('rwd_redirect');
		if ($redirect)
			header('Location:'.$redirect);
	}
	
	register_deactivation_hook(__FILE__,'rwd_deactivate');
	function rwd_deactivate(){
		$api_url = get_rwd_api_url('/wordpress_links');
		$blogname = get_option('blogname');
		$data = array('active' => 0, 'link'=>get_site_url());
		curl_posting($data,$api_url);
		
		$plugin_data = get_plugin_data( __FILE__);
		$plugin_version  = 'unavailable';        
        if ( isset($plugin_data['Version']) ) {
            $plugin_version  = $plugin_data['Version'];
        }
		
		$api_url_log = get_rwd_api_url('/save_blog_logs');
		$wp_version = get_bloginfo('version');

		$data2 = array('action'=>'deactivate','link'=>get_site_url(),'version'=>$plugin_version, 'wp_version'=>$wp_version);
		curl_posting($data2,$api_url_log);
	}
	register_activation_hook(__FILE__,'rwd_after_activate_plugin');
	
/***** Create table into the database *****/

	global $jal_db_version;
	$jal_db_version = "1.0";

function rwd_install() {
	// adding tables to the database
	global $wpdb;
	global $jal_db_version;
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	
	$table_name1 = $wpdb->prefix . "rwd_users";
	$table_name1_old = $wpdb->prefix . "focusedstamps_users";

	$sql1 = "DROP TABLE IF EXISTS `$table_name1`;
	DROP TABLE IF EXISTS `$table_name1_old`;
	CREATE TABLE IF NOT EXISTS `$table_name1`  (
	id int(11) NOT NULL AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	created varchar(255) NOT NULL,
	uid int(11) NOT NULL,
	last_login varchar(255) NOT NULL,
	fame int(11) NOT NULL,
	credits int(11) NOT NULL,
	premium_currency int(11) NOT NULL,
	level int(11) NOT NULL,
	PRIMARY KEY id (id)
	)CHARACTER SET utf8 COLLATE utf8_general_ci;";
	dbDelta( $sql1);

	$table_name3 = $wpdb->prefix . "rwd_comments";
	$table_name3_old = $wpdb->prefix . "focusedstamps_comments";

	$sql3 = "DROP TABLE IF EXISTS $table_name3;
		DROP TABLE IF EXISTS $table_name3_old;
		CREATE TABLE IF NOT EXISTS $table_name3 (
		id int(11) NOT NULL AUTO_INCREMENT,
		comment_id int(11) NOT NULL,
		comment_post_id int(11) NOT NULL,
		comment_author varchar(255) NOT NULL,
		comment_author_email varchar(255) NOT NULL,
		comment_content text NOT NULL,
		comment_date datetime NOT NULL,
		comment_approved varchar(20) NOT NULL,
		user_id int(11) NOT NULL,
		username varchar(255) NOT NULL,
		PRIMARY KEY id (id)
	)CHARACTER SET utf8 COLLATE utf8_general_ci;";
	dbDelta( $sql3);
	
	$attributes_table_name = $wpdb->prefix . "rwd_attributes";
	$attributes_table_name_old = $wpdb->prefix . "focusedstamps_attributes";

	$attributes_table = "DROP TABLE IF EXISTS $attributes_table_name;
		DROP TABLE IF EXISTS $attributes_table_name_old;
		CREATE TABLE IF NOT EXISTS $attributes_table_name (
		id int(11) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		is_active varchar(255) NOT NULL,
		PRIMARY KEY id (id)
	)CHARACTER SET utf8 COLLATE utf8_general_ci;";
	dbDelta( $attributes_table);
	
	$attributes_users_table_name = $wpdb->prefix . "rwd_attributes_users";
	$attributes_users_table_name_old = $wpdb->prefix . "focusedstamps_attributes_users";

	$attributes_users_table = "DROP TABLE IF EXISTS $attributes_users_table_name;
		DROP TABLE IF EXISTS $attributes_users_table_name_old;
		CREATE TABLE IF NOT EXISTS $attributes_users_table_name (
		id int(11) NOT NULL AUTO_INCREMENT,
		attribute_id int(11) NOT NULL,
		user_id int(11) NOT NULL,
		value int(11) NOT NULL,
		PRIMARY KEY id (id)
	)CHARACTER SET utf8 COLLATE utf8_general_ci;";
	dbDelta( $attributes_users_table);

	/*** Quest tables ***/
	$quest_table_name = $wpdb->prefix . "rwd_quests";
	$quest_table_name_old = $wpdb->prefix . "focusedstamps_quests";

	$quest_table = "DROP TABLE IF EXISTS $quest_table_name;
		DROP TABLE IF EXISTS $quest_table_name_old;
		CREATE TABLE IF NOT EXISTS $quest_table_name (
			  `id` int(11) NOT NULL,
			  `title` varchar(300) NOT NULL,
			  `description` text NOT NULL,
			  `user_limit` int(20) NOT NULL,
			  `deadline_date` varchar(100) NOT NULL,
			  `dadline_tz` varchar(255) NOT NULL,
			  `point_scale_status` int(11) NOT NULL,
			  `point_scale_value` int(11) NOT NULL,
			  `custom_prize` varchar(300) DEFAULT NULL,
			  `cloned` int(20) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
	dbDelta( $quest_table );

	//points for questions
	$quest_table_name = $wpdb->prefix . "rwd_quest_questions_points";
	$quest_table_name_old = $wpdb->prefix . "focusedstamps_quest_questions_points";

	$quest_table = "DROP TABLE IF EXISTS $quest_table_name;
		DROP TABLE IF EXISTS $quest_table_name_old;
		CREATE TABLE IF NOT EXISTS $quest_table_name (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `credit` varchar(255) NOT NULL,
			  `question_id` int(11) NOT NULL,
			  `fame` varchar(255) NOT NULL,
			  `dificulty` int(11) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8_general_ci AUTO_INCREMENT=1 ;";
	dbDelta( $quest_table );

	$quest_steps_table_name = $wpdb->prefix . "rwd_quest_steps";
	$quest_steps_table_name_old = $wpdb->prefix . "focusedstamps_quest_steps";

	$quest_steps_table = "DROP TABLE IF EXISTS $quest_steps_table_name;
	DROP TABLE IF EXISTS $quest_steps_table_name_old;
		CREATE TABLE IF NOT EXISTS $quest_steps_table_name (
		id int(20) NOT NULL,
		quest_id int(20) NOT NULL,
		step_nr int(20) NOT NULL,
		currency varchar(100) NOT NULL,
		number int(20) NOT NULL,
		title varchar(100) NOT NULL,
		PRIMARY KEY id (id)
	)CHARACTER SET utf8 COLLATE utf8_general_ci;";
	dbDelta( $quest_steps_table );
	
	$quest_questions_table_name = $wpdb->prefix . "rwd_quest_questions";
	$quest_questions_table_name_old = $wpdb->prefix . "focusedstamps_quest_questions";

	$quest_questions_table = "DROP TABLE IF EXISTS $quest_questions_table_name_old;
		DROP TABLE IF EXISTS $quest_questions_table_name;
		CREATE TABLE IF NOT EXISTS $quest_questions_table_name (
		id int(20) NOT NULL,
		step_id int(20) NOT NULL,
		question varchar(300) NOT NULL,
		answer varchar(144) NOT NULL,
		wrong_1 varchar(144) NOT NULL,
		wrong_2 varchar(144) NOT NULL,
		wrong_3 varchar(144) NOT NULL,
		PRIMARY KEY id (id)
	)CHARACTER SET utf8 COLLATE utf8_general_ci;";
	dbDelta( $quest_questions_table );
	
	
	$quest_questions_answer_table_name = $wpdb->prefix . "rwd_quest_question_answers";
	$quest_questions_answer_table_name_old = $wpdb->prefix . "focusedstamps_quest_question_answers";

	$quest_questions_answer_table = "DROP TABLE IF EXISTS $quest_questions_answer_table_name;
	DROP TABLE IF EXISTS $quest_questions_answer_table_name_old;
	CREATE TABLE IF NOT EXISTS $quest_questions_answer_table_name (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `retry` int(11) NOT NULL DEFAULT '1' COMMENT 'indexStart:1',
  `uid` int(11) NOT NULL,
  `answer_status` int(11) NOT NULL,
  `credit` int(11) NOT NULL,
  `fame` int(11) NOT NULL,
  PRIMARY KEY (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;";
	dbDelta( $quest_questions_answer_table );
	
	$quest_user_table_name = $wpdb->prefix . "rwd_quest_user";
	$quest_user_table_name_old = $wpdb->prefix . "focusedstamps_quest_user";

	$quest_user_table = "DROP TABLE IF EXISTS $quest_user_table_name;
		DROP TABLE IF EXISTS $quest_user_table_name_old;
		CREATE TABLE IF NOT EXISTS $quest_user_table_name (
		id int(20) NOT NULL AUTO_INCREMENT,
		quest_id int(20) NOT NULL,
		step_id int(20) NOT NULL,
		question_id int(20) NOT NULL,
		retry int(20) NOT NULL,
		uid int(20) NOT NULL,
		status int(2) NOT NULL DEFAULT 0,
		PRIMARY KEY id (id)
	)CHARACTER SET utf8 COLLATE utf8_general_ci;";
	dbDelta( $quest_user_table );	
	
	$quest_alerts_table_name = $wpdb->prefix.'rwd_quest_alerts';
	$quest_alerts_table_name_old = $wpdb->prefix.'focusedstamps_quest_alerts';

	$quest_alerts_table = "DROP TABLE IF EXISTS $quest_alerts_table_name;
		DROP TABLE IF EXISTS $quest_alerts_table_name_old;
		CREATE TABLE IF NOT EXISTS $quest_alerts_table_name (
		id int(20) NOT NULL,
		quest_id int(20) NOT NULL,
		text varchar(255),
		users varchar(255),
		type int(3),
		PRIMARY KEY id (id)
	)CHARACTER SET utf8 COLLATE utf8_general_ci;";
	dbDelta( $quest_alerts_table );
}

function get_rwd_api_url($addr = '/'){
	// get the full link of the api method
	$rwd_base = get_option('rwd_api_base');
	return $rwd_base.$addr;
}
function get_user_ip(){
	$ip = '';
	if (isset($_SERVER)) {

		$ip =  $_SERVER["REMOTE_ADDR"];
		
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];

        if (isset($_SERVER["HTTP_CLIENT_IP"]))
            $ip = $_SERVER["HTTP_CLIENT_IP"];

    }
	return $ip;
}

	function rwd_install_data() {
	   global $wpdb;
	   $welcome_name = "Mr. WordPress";
	   $welcome_text = "Congratulations, you just completed the installation!";
	   $table_name = $wpdb->prefix . "rwd_users";
	   $rows_affected = $wpdb->insert( $table_name, array( 'time' => current_time('mysql'), 'name' => $welcome_name, 'text' => $welcome_text ) );
	}
	register_activation_hook( __FILE__, 'rwd_install' );
	register_activation_hook( __FILE__, 'rwd_install_data' );
	
	
	
	function rewardial_menus(){
		// add menus in the admin area
		$appName = 'Rewardial';
		$appID = 'rewardial';
		if (!get_option('rwd_secret_key'))
			add_menu_page($appName,$appName, 'manage_options', 'rewardial', 'rwd_user_info');
		else{
			add_menu_page($appName,$appName, 'manage_options', 'rwd-overview', 'rwd_overview');
			add_submenu_page( 'rwd-overview', 'Overview', 'Overview', 'manage_options', 'rwd-overview', 'rwd_overview');
			add_submenu_page( 'rwd-overview', 'Settings', 'Settings', 'manage_options', 'rwd-settings', 'rwd_settings_page');

			$api_url = get_rwd_api_url('/check_rstk_option');
			$data = array('link'=>get_site_url());
			$result = curl_posting($data,$api_url);
			if($result){
				$result = json_decode($result,true);
				if($result['status'] == 200){
					add_submenu_page( 'rwd-overview', 'Reset', 'Reset', 'manage_options', 'rwd-reset', 'rewardial_reset_blog');
				}
			}
		}
	}
	add_action('admin_menu', 'rewardial_menus');

	function rewardial_reset_blog(){
		ob_start();?>
		<div class="rwd-reset-options">
			<div class="rewardial-reset-button">
				<input type="hidden" value="<?php echo get_site_url(); ?>" class="rewardial-blog-link">
				<div id="rewardial-reset-blog">Reset Options</div>
			</div>
			<div class="rewardial-reset-button-info">
				Push this button in case the plugin didn't install correctly and try again.
			</div>
		</div>
		<?php $content = ob_get_clean();
		
		echo $content;
		
	}

	add_action( 'wp_ajax_change_order_status', 'rew_ajax_change_order_status' );
	add_action( 'wp_ajax_nopriv_change_order_status', 'rew_ajax_change_order_status' );
	function rew_ajax_change_order_status(){
		
		$order_id = intval($_POST['order_id']);
		$value_selected = intval($_POST['status']);

		$key = get_option('rwd_secret_key');
		$timer = time();
		$string = get_site_url().$timer;
		$secret_key = hash_hmac('sha1',$string,$key);
		
		$api_url = get_rwd_api_url('/order_status');
		
		$data = array('link'=>get_site_url(),'time'=>$timer,'code'=>$secret_key,'order_id'=>$order_id,'status'=>$value_selected);
		$order_status = curl_posting($data,$api_url);
		if($order_status){
			$status = json_decode($order_status,true);
			
			echo json_encode(array('status'=>$status['status'],'message'=>$status['message'])); die();
		}else{
			echo json_encode(array('status'=>'error','message'=>'Invalid request')); die();
		}
		//add_option('testttttttttttttttttttttttttttt',$order_id.'testttttttt'.$value_selected);
		
	}

	function rwd_user_info(){
		
		// add content before the plugin is loaded
		global $wpdb;
		$key = get_option('rwd_secret_key');
		$siteurl = get_option('siteurl');
		$secret_key = hash_hmac('sha1',$siteurl,$key);
		
		ob_start();?>
		
		<div id="rwd-admin-page" style="margin-top:40px;">
			<input type="hidden" value="<?php echo get_option('rwd_api_base'); ?>" id="rwd-api-base"/>
			<input type="hidden" value="<?php echo $secret_key; ?>" class="rwd-secret-key"/>
			<input type="hidden" value="<?php echo $siteurl; ?>" class="rwd-siteurl"/>
			<input type="hidden" value="<?php echo $city; ?>" class="rwd-city"/>
			
			<input type="hidden" value="" class="rwd-selected-city"/>
			<input type="hidden" value="" class="rwd-selected-country"/>
			<div id="rwd-admin-page-container">
					
				<div class="rwd-register-block">
					<h2>Register as WordPress blogger</h2>
					<label for="rwd-input-first-name"> First Name : </label>
						<input type="text" name="first_name" class="rwd-input-first-name"  id="rwd-input-first-name"/>
					<label for="rwd-input-last-name"> Last Name : </label>
						<input type="text" name="last_name" class="rwd-input-last-name"  id="rwd-input-last-name"/>
					<label for="rwd-input-email"> E-mail Address : </label>
						<input type="email" name="email" class="rwd-input-email" id="rwd-input-email"  />
					<label for="rwd-input-password"> Password : </label>
						<input type="password" name="password" class="rwd-input-password" id="rwd-input-password"  />
					<label for="rwd-input-repeat-password"> Repeat Password : </label>
						<input type="password" name="repeat_password" class="rwd-input-repeat-password"  id="rwd-input-repeat-password" />
						
						<input type="submit" value="Register" id="rwd-register-admin-submit">
					<div class="rwd-register-messages"></div>
				</div>
				
				<div class="rwd-add-account">
					<h2> Add this blog to an existing Rewardial blogger's account </h2>
					<label for="rwd-add-email" >E-mail Address :</label>
					<input type="email" name="add-email" class="rwd-add-email" id="rwd-add-email" />
					<label for="rwd-add-password" >Password :</label>
					<input type="password" name="add-password" class="rwd-add-password" id="rwd-add-password" />
						
					<input type="submit" value="Add" id="rwd-add-admin-submit"/>
					<div class="rwd-add-account-messages"></div>
				</div>
			</div>
			
			<div class="rwd-accept-communication">
				<input type="checkbox" class="rewardial-accept-communication" id="rewardial-accept-claim"/>
				<label for="rewardial-accept-claim">I agree to the <a href="http://www.rewardial.com/page/index/terms-of-service" target="_blank">Terms and Conditions</a> for using the Rewardial plug-in</label>
			</div>
			
			<hr/>
			<div class="rwd-reset-options">
				<div class="rewardial-reset-button">
					<input type="hidden" value="<?php echo get_site_url(); ?>" class="rewardial-blog-link">
					<div id="rewardial-reset-blog">Reset Options</div>
				</div>
				<div class="rewardial-reset-button-info">
					Push this button in case the plug-in didn't install correctly and try again.
				</div>
			</div>
		</div>
		<div id="overlay-container">
			<img src="<?php echo plugins_url('rewardial'); ?>/img/loading.gif">					
		</div>
			
		<?php 
		$focused = ob_get_clean();
		echo $focused;
	}


	function rwd_settings_page(){
		// admin menu settings page
		$plugin_data = get_plugin_data( __FILE__);
		if(get_option('rewardial_info')){
			update_option('rewardial_info',json_encode($plugin_data));
		}else{
			add_option('rewardial_info',json_encode($plugin_data));
		}
		
		if(isset($_GET['onboarding']) and $_GET['onboarding'] == 1){

			$modifiedTimeStamp = time();
			if(get_option('rewardial_modified_date')){
				update_option('rewardial_modified_date', $modifiedTimeStamp);
			}else{
				add_option('rewardial_modified_date', $modifiedTimeStamp);
			}

		}

		$plugin_data = get_plugin_data( __FILE__);
		$plugin_version  = 'unavailable';        
    if ( isset($plugin_data['Version']) ) {
        $plugin_version  = $plugin_data['Version'];
    }
		
		global $wpdb;
		$data = array('link'=>get_site_url(), 'plugin_version'=>$plugin_version);
		$all_settings = curl_posting($data,get_rwd_api_url('/settings_page'));

		$all_settings = json_decode($all_settings,true);

		if(!empty($all_settings)){
			if (empty($all_settings['user_admin'])) {
				delete_option('rwd_secret_key');
				add_option('rewardial_reset_blog',200);
				$redirect = admin_url('/admin.php?page=rewardial');
			}
		
			$user_admin = $all_settings['user_admin'];
			$logo = $all_settings['logo'];
			
			$options = $all_settings['options'];
			$blogname = $all_settings['blogname'];

			$category_selected = $all_settings['category_selected'];
			
			$blog_description = $all_settings['description'];
			$post_rate = $all_settings['post_rate'];

			$app_link = str_replace('/api2','',get_rwd_api_url()); 
			
			$deadline = '';
			$licensed = '';
			$profile_type = '';
		
			$app_link1 = str_replace('/api2','/admin',get_rwd_api_url());
			$code_s1 = get_option('rwd_secret_key');
			$my_string1 = time();
			$final_encode1 = hash_hmac('sha1',$my_string1,$code_s1);

		ob_start(); ?>
		<?php if(!empty($redirect)){ ?>
			<!-- <input type="hidden" value="<?php echo $redirect ?>" id="redirect_after_delete_by_platform"> -->
		<?php } ?>
		<div class="rwd-settings-page-content">
			<div class="rwd-settings-page-website-type rwd-settings-page-box">
				<h4> Blog Settings </h4>
				<hr>
				<div class="rwd-website-info">
					<table>
						<tr>
							<th>Admin Name:</th>
							<td><?php echo $user_admin['first_name'].' '.$user_admin['last_name']; ?></td>
						</tr>
						<tr>
							<th>Admin E-mail Address: </th>
							<td><?php echo $user_admin['user_email']; ?></td>
						</tr>
						<tr>
							<th>Blog URL:</th>
							<td><?php echo get_site_url(); ?></td>
						</tr>
						<tr>
							<th>Blog Name:</th>
							<td><?php echo $blogname; ?></td>
						</tr>
						<tr>
							<th>Blog Description:</th>
							<td><?php echo nl2br($blog_description) ?></td>
						</tr>
						<tr>
							<th>Blog Category:</th>
							<td><?php echo $category_selected ?></td>
						</tr>
						<tr>
							<th>Standard blog post rate:</th>
							<td><?php echo !empty($post_rate)?$post_rate.' USD':'Not specified' ?></td>
						</tr>
					</table>
				</div>
				<div class="rwd-website-logo">
					<img src="<?php echo $app_link; ?>img/uploads/websites/<?php echo $logo; ?>">
					<div class="rwd-redirect-button">
						<a target="_blank" href="<?php echo $app_link1.'?time='.$my_string1.'&code='.$final_encode1.'&link='.get_site_url().'&page=settings&section=#website-setup'; ?>">Edit Blog Settings</a>
					</div>
				</div>
				<div class="rwd-website-support">
					<p>For any questions or suggestions, please report back to us at  <a href="http://support.rewardial.com/" target="_blank">Support Rewardial</a></p>
				</div>
		<?php } elseif(get_option('rewardial_server') and get_option('rewardial_server') == 'no'){ ?>
			<div class="rewardial-admin-server-inactive">
				<div class="rewardial-admin-server-message">
					Server is currently inactive. We are sorry for the inconvenience ...
				</div>
			</div>
		<?php } ?>
				
				

					
			</div>

			<?php if(isset($_GET['onboarding']) and $_GET['onboarding'] == 1){ ?>
			<div class="rwd-settings-welcome">
				<div class="welcome-text">
					<div class="welcome-first">Thank you for installing the Rewardial plug-in and welcome into the Rewardial community.</div>
					<div class="welcome-second">This page will allow you to customize the plug-in. You will be able to change the settings at any time.</div>
				</div>
				<div class="rwd-welcome-image">
					<img src="<?php echo plugins_url('rewardial'); ?>/img/rewardial-plugin-blog1.png">
				</div>
				<blockquote class="continue-blockquote">
					Clicking on Continue will open your account settings on the Rewardial platform for you to edit.
				</blockquote>
				
				<div class="welcome-skip">
					<a href="javascript:void(0);">Skip</a>
				</div>
				
				<div class="welcome-continue">
					<a target="_blank" href="<?php echo $app_link1.'?time='.$my_string1.'&code='.$final_encode1.'&link='.get_site_url().'&page=settings&section=#website-setup'; ?>">Continue</a>
				</div>
				
			</div>
			<div class="settings-overlay">
				
			</div>
			<?php } ?>
		</div>
		<?php $settings = ob_get_clean();
		echo $settings;
	}
	function rwd_overview(){
		// admin menu overview page
	
		$plugin_data = get_plugin_data( __FILE__);
		if(get_option('rewardial_info')){
			update_option('rewardial_info',json_encode($plugin_data));
		}else{
			add_option('rewardial_info',json_encode($plugin_data));
		}
		global $wpdb;

		$api_url = get_rwd_api_url('/overview_graphs');
		$data = array('link'=>get_site_url(),'option1'=>'license_deadline','option2'=>'licensed');
		$metrics = curl_posting($data,$api_url);
		$metrics = json_decode($metrics,true);
		
		
		$users = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."rwd_users",ARRAY_A);
		
		$url = plugins_url('rewardial');
		
		$daily_logins = array();
		$months_logins = array();
		$daily_users = array();
		$monthly_users = array();
		$m_labels = '';
		$tips = array();
		if($metrics){
			$daily_logins = $metrics['daily'];
			$months_logins = $metrics['monthly'];
			$daily_users = $metrics['daily_users'];
			$monthly_users = $metrics['monthly_users'];
			$m_labels = $metrics['m_labels'];
			$tips = $metrics['tips'];
			$opt_date = $metrics['license_deadline'];
			$opt_licensed = $metrics['licensed'];
		}
				
		ob_start();?>
		
		<?php if(get_option('rewardial_server') and get_option('rewardial_server') == 'no'){ ?>
			<div class="rewardial-admin-server-inactive">
				<div class="rewardial-admin-server-message">
					Server is currently inactive. We are sorry for the inconvenience ..
				</div>
			</div>
		<?php } ?>
		<div class="wp-overview-content">
			<div class="wp-overview-description-content">
				<h3> Description </h3>
				<div class="wp-description-about">
					
				<p>Rewardial Engagement is a plug-in provided to you by the Rewardial platform. Its role is to validate and confirm you are the owner of the current blog in order to gain access to the advertisement opportunities presented on Rewardial.</p>
				<p>
					<a id="rewardial-reset-blog"  href="javascript:void();">Reset Options</a>
				</p>
				</div>
				<?php if($tips){ ?>
				<div class="wp-description-tips flexslider tips-flexslider">
					<ul class="slides">
						<?php $mainweb = get_option('rwd_api_base');
							$mainwebsite = explode('api',$mainweb);
							$weburl = $mainwebsite[0];
							foreach($tips as $tip){
						?>
						<li><img src="<?php echo $weburl; ?>/img/tips/<?php echo $tip['Tips']['img']; ?>"/></li>
						<?php } ?>
					</ul>
				</div>
				<?php } ?>

			</div>
			
		</div>
		
		<?php $overview = ob_get_clean();
		echo $overview;
	}

	function rwd_custom_admin_head(){
		$url = plugins_url('rewardial');
		echo '<link rel="stylesheet" type="text/css" href="http://rewardial.com/plugin_files/admin-style.css">';
		echo '<script type="text/javascript" src="http://rewardial.com/plugin_files/admin-js.js"></script>';
	}
	add_action('admin_head', 'rwd_custom_admin_head');
	
	add_action( 'wp_enqueue_script', 'load_jquery' );
	function load_jquery() {
		wp_enqueue_script( 'jquery' );
	}
	
	function rwd_add_js($content){
		// add the javascript files
		$page = '';
		if(is_single()) $page = 'post';
		$url = plugins_url('rewardial');
		ob_start();
		?>
		<div class="rewardial_links">
			<input type="hidden" id="rwd-plugin-url" value="<?php echo plugins_url('rewardial'); ?>"/>
			<input type="hidden" id="rewardial-blog-url" value="<?php echo get_site_url(); ?>"/>
			<input type="hidden" id="rewardial-blog-logged" value="<?php if(isset($_COOKIE['rewardial_Logged'])) echo $_COOKIE['rewardial_Logged']; ?>"/>
			<input type="hidden" id="rewardial-page-type" value="<?php echo $page; ?>"/>
			<input type="hidden" id="rewardial-page-link" value="<?php the_permalink() ?>"/>
			
		</div>
		<div id="rewardial-plugin-loader">
			<img class="rewardial-first-loader" src="<?php echo $url; ?>/img/ajax-loader-1.gif" style="position:fixed; right:50px; bottom:0; display:none; width:100px; z-index:99999;">
		</div>
		<script>var rwd_ajax = "<?php echo admin_url('admin-ajax.php');?>";</script>
		<script>var rwd_api_base = "<?php echo get_rwd_api_url();?>";</script>
		<script type="text/javascript" src="<?php echo $url; ?>/js/noconflict.js"></script>
		
	
		<?php
			$rwd_script = ob_get_clean();
			$content .= $rwd_script;
			echo $content;
	}
	add_action('wp_footer','rwd_add_js');
		
	add_action( 'wp_ajax_reset_blog_options', 'rew_ajax_reset_blog_options');
	add_action( 'wp_ajax_nopriv_reset_blog_options', 'rew_ajax_reset_blog_options');
	function rew_ajax_reset_blog_options(){
		
		global $wpdb;
		$api_url = get_rwd_api_url('/wordpress_links');
		$blogname = get_option('blogname');
		$data = array('active' => 0, 'link'=>get_site_url(), 'reset_option'=>'true');
		curl_posting($data,$api_url);
		
		delete_option('rwd_secret_key');
		$redirect = admin_url('/admin.php?page=rewardial');
		add_option('rewardial_reset_blog',200);
		echo $redirect; die();
	}

	add_action( 'wp_ajax_connect_rewardial', 'rew_ajax_connect_rewardial' );
	add_action( 'wp_ajax_nopriv_connect_rewardial', 'rew_ajax_connect_rewardial' );
	
	function rew_ajax_connect_rewardial(){
		// generate a secret key and save the blog locally and on the main platform
		global $wpdb;
		$url_save = get_rwd_api_url('/wordpress_links');
		$url = plugins_url();
		$blogname = get_option('blogname');
		$blogdescription = get_option('blogdescription');
		$registered_users = 1;
		$link = get_site_url();
		$secret_code = md5(md5($link).md5(rand(1,5000)));
		
		if(get_option('rewardial_reset_blog')){
			$reset = get_option('rewardial_reset_blog');
		}else{
			$reset = '';
		}
		
		$data = array('link'=>$link,'code'=>$secret_code, 'blogdescription'=>$blogdescription, 'blogname'=>$blogname,'users'=>$registered_users, 'reset'=>$reset);
		$res = curl_posting($data,$url_save);

		$resp = json_decode($res,true);

		// if(!empty($resp['was_deleted'])){

		// }

		if(isset($resp['status']) and $resp['status'] == 200){
			if(get_option('rwd_secret_key')){
				update_option('rwd_secret_key',$secret_code);
			}else{
				add_option('rwd_secret_key',$secret_code);
			}
			delete_option('rewardial_reset_blog');
			echo hash_hmac('sha1',$link,$secret_code);
			
			die();
		}else{
			echo 'error';
			die();
		}
	}
	
	add_action( 'wp_ajax_disconnect_rewardial', 'rew_ajax_disconnect_rewardial' );
	add_action( 'wp_ajax_nopriv_disconnect_rewardial', 'rew_ajax_disconnect_rewardial' );
	
	function rew_ajax_disconnect_rewardial(){
		// save the current blog as inactive on the main platform
		global $wpdb;
		if(get_option('rewardial_server') and get_option('rewardial_server') == 'yes'){
			$url_save = get_rwd_api_url('/error_register_or_attach');
			$url = plugins_url();
			$blogname = get_option('blogname');
			$registered_users = 1;
			$link = get_site_url();
			$secret_code = md5(md5($link).md5(rand(1,5000)));
			$data = array('link'=>$link,'code'=>$secret_code,'blogname'=>$blogname,'users'=>$registered_users);
			$res = curl_posting($data,$url_save);
			delete_option('rwd_secret_key');
			//echo hash_hmac('sha1',$link,$secret_code);
		}
		die();
	}
	
	
	add_action('admin_head', 'rwd_api_base_hidden');
	function rwd_api_base_hidden(){
		?>
			<?php $app_link1 = str_replace('/api2','/admin',get_rwd_api_url());
				$code_s1 = get_option('rwd_secret_key');
				$my_string1 = time();
				$final_encode1 = hash_hmac('sha1',$my_string1,$code_s1);
				?>
			<input type="hidden" id="rwd_api_base_hidden" value="<?php echo $app_link1.'?time='.time().'&code='.$final_encode1.'&link='.get_site_url().'&page=index'; ?>"/>
		<?php
	}
	
	add_action('init', 'check_server');
	function check_server(){

		$api_url = 'http://rewardial.com/api2';
		update_option('rwd_api_base',$api_url);

		global $wpdb;
		
		$api_url = get_rwd_api_url('/check_server');
		$data = array('status'=>'test','key'=>get_option('rwd_secret_key'),'link'=>get_site_url());
		$server = curl_posting($data,$api_url);

		$server = json_decode($server,true);
		
		if(isset($server['message']) and $server['message'] == 'Invalid key'){
			
		}
		
		if($server['status'] == 'success'){

			if(get_option('rwd_secret_key')){
				if(get_option('rewardial_server') and get_option('rewardial_server') == 'no'){
					update_option('rewardial_server','yes');
				}else{
					add_option('rewardial_server','yes');
				}

			}else{
				if(get_option('rewardial_server')){
					update_option('rewardial_server','no');
				}else{
					add_option('rewardial_server','no');
				}
			}
		}else{
			if(get_option('rewardial_server')){
				update_option('rewardial_server','no');
			}else{
				add_option('rewardial_server','no');
			}
		}
		if(get_option('rewardial_server') == 'yes'){
			if (!isset($_POST['action']) && get_option('rwd_secret_key')){
				$retry = 0;
			}
		}
		
	}
	
	function get_fsfb_login_url(){
		$api_url = get_rwd_api_url('/get_facebook_login_url/'.urlencode(get_home_url()));
		return $api_url;
	}

	
?>
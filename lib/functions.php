<?php 

	function socialink_load_networks(){
		
		if($networks = socialink_get_available_networks()){
			foreach($networks as $network){
				require_once(dirname(__FILE__) . "/networks/" . $network . ".php");
			}
		}
	}

	function socialink_twitter_available(){
		$result = false;
		
		if(get_plugin_setting("enable_twitter", "socialink") == "yes"){
			$consumer_key = get_plugin_setting("twitter_consumer_key", "socialink");
			$consumer_secret = get_plugin_setting("twitter_consumer_secret", "socialink");
			
			if(!empty($consumer_key) && !empty($consumer_secret)){
				$result = array(
					"consumer_key" => $consumer_key,
					"consumer_secret" => $consumer_secret,
				);
			}
		}
		
		return $result;
	}
	
	function socialink_facebook_available(){
		$result = false;
		
		if(get_plugin_setting("enable_facebook", "socialink") == "yes"){
			$app_id = get_plugin_setting("facebook_app_id", "socialink");
			$app_secret = get_plugin_setting("facebook_app_secret", "socialink");
			$api_key = get_plugin_setting("facebook_api_key", "socialink");
			
			if(!empty($app_id) && !empty($app_secret) && !empty($api_key)){
				$result = array(
					"app_id" => $app_id,
					"app_secret" => $app_secret,
					"api_key" => $api_key
				);
			}
		}
		
		return $result;
	}
	
	function socialink_linkedin_available(){
		$result = false;
		
		if(get_plugin_setting("enable_linkedin", "socialink") == "yes"){
			$consumer_key = get_plugin_setting("linkedin_consumer_key", "socialink");
			$consumer_secret = get_plugin_setting("linkedin_consumer_secret", "socialink");
			
			if(!empty($consumer_key) && !empty($consumer_secret)){
				$result = array(
					"consumer_key" => $consumer_key,
					"consumer_secret" => $consumer_secret,
				);
			}
		}
		
		return $result;
	}
	
	function socialink_openbibid_available(){
		$result = false;
		
		if(get_plugin_setting("enable_openbibid", "socialink") == "yes"){
			$consumer_key = get_plugin_setting("openbibid_consumer_key", "socialink");
			$consumer_secret = get_plugin_setting("openbibid_consumer_secret", "socialink");
			
			if(!empty($consumer_key) && !empty($consumer_secret)){
				$result = array(
					"consumer_key" => $consumer_key,
					"consumer_secret" => $consumer_secret,
				);
			}
		}
		
		return $result;
	}

	function socialink_get_supported_networks(){
		return array("twitter", "linkedin", "facebook", "openbibid");
	}
	
	function socialink_is_supported_network($network){
		$result = false;
		
		if(!empty($network) && ($networks = socialink_get_supported_networks())){
			$result = in_array($network, $networks);
		}
		
		return $result;
	}
	
	function socialink_get_available_networks(){
		static $available;
		
		if(!isset($available)){
			if($networks = socialink_get_supported_networks()){
				$available = array();
				
				foreach($networks as $network){
					if(call_user_func("socialink_" . $network . "_available")){
						$available[] = $network;
					}
				}
			} else {
				$available = false;
			}
		}
		
		return $available;
	}
	
	function socialink_is_available_network($network){
		$result = false;
		
		if(!empty($network) && ($networks = socialink_get_available_networks())){
			$result = in_array($network, $networks);
		}
		
		return $result;
	}
	
	/**
	 * Returns all the networks the user is connected to or an empty array if none available
	 */
	function socialink_get_user_networks($user_guid){
		$result = array();
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		if($available_networks = socialink_get_available_networks()){
			foreach($available_networks as $network){
				if(call_user_func("socialink_" . $network . "_is_connected", $user_guid)){
					$result[] = $network;
				}
			}
		}
		
		return $result;
	}
	
	function socialink_validate_network($network, $user_guid){
		
		$result = call_user_func("socialink_" . $network . "_validate_connection", $user_guid);
		
		return $result;
	}
	
	/**
	 * get an array of the supported network fields
	 * 
	 * result is in format
	 * 		settings_name => network_name
	 * 
	 * @param $network
	 * @return unknown_type
	 */
	function socialink_get_network_fields($network){
		$result = false;
		
		if(!empty($network) && socialink_is_supported_network($network)){
			$fields = array(
				"twitter" => array(
					"name" => "name",
					"location" => "location",
					"url" => "url",
					"description" => "description",
					"screen_name" => "screen_name",
					"profile_url" => "socialink_profile_url",
				),
				"linkedin" => array(
					"firstname" => "firstName",
					"lastname" => "lastName",
					"name" => "socialink_name",
					"profile_url" => "publicProfileUrl",
					"location" => "location->name",
					"industry" => "industry"
				),
				"facebook" => array(
					"name" => "name",
					"firstname" => "first_name",
					"lastname" => "last_name",
					"profile_url" => "link",
					"email" => "email",
					"location" => "location",
					"gender" => "gender",
					"about" => "about",
					"bio" => "bio",
					"hometown" => "hometown"
				)
			);
			
			$result = $fields[$network];
		}
		
		return $result;
	}
	
	function socialink_get_configured_network_fields($network){
		$result = false;
		
		if(!empty($network) && socialink_is_available_network($network)){
			if(($fields = socialink_get_network_fields($network)) && !empty($fields)){
				$temp = array();
				
				foreach($fields as $setting_name => $network_name){
					if(($profile_field = get_plugin_setting($network . "_profile_" . $setting_name, "socialink")) && !empty($profile_field)){
						$result[$setting_name] = $profile_field;
					}
				}
				
				if(!empty($temp)){
					$result = $temp;
				}
			}
		}
		
		return $result;
	}
	
	function socialink_create_username_from_email($email){
		$result = false;
		
		if(!empty($email) && is_email_address($email)){
			list($username) = explode("@", $email);
							
			// show hidden entities
			$access = access_get_show_hidden_status();
			access_show_hidden_entities(TRUE);
			
			// check if username extist
			if(get_user_by_username($username)){
				$i = 1;
				while(get_user_by_username($username . $i)){
					$i++;
				}
				
				$username = $username . $i;
			}
			
			// restore access settings
			access_show_hidden_entities($access);
			
			// return username
			$result = $username;
		}
		
		return $result;
	}
	
?>
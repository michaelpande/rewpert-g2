<?php 
	include('parser/parse.php');
	include('../../../wp-load.php'); // Potentially creates bugs.
	
	$API_KEY = "qibgUASv9D489EL6tDEuNXyH3faoHkvWDxTssIWJhF3UlIGkGlUvGUDoMIPxeGGo";
	$DEBUG = true;
	
	// Injections on $_GET?
	

	
	
	
	// Authentication
	echo $DEBUG == true ? "<h3>Authentication</h3>" : ""; 
	if(!authentication()){
		echo $DEBUG == true ? "Failed" : ""; 
		http_response_code(401);
		exit;
	}
	echo $DEBUG == true ? "Successful" : ""; 
	
	
	
	if($_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'PUT'){
		exit;
	}
	
	$postdata = getRequestParams();
	$parsed = Parse::createPost($postdata);
	
	if($DEBUG){echo "<h3>Returned from Parse.php: </h3>"; var_dump($parsed);};

	$post = $parsed['post'];
	$post = $parsed['meta'];
		
	if($post != null){
		
		
		// CREATE
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$wp_error = insertPost($post, $meta);
			if($DEBUG){echo "<h3>Returned from Wordpress: </h3>"; var_dump($wp_error);};
			
		// UPDATE
		}elseif($_SERVER['REQUEST_METHOD'] == 'PUT'){
			$wp_error = updatePost($post, $meta);
			if($DEBUG){echo "<h3>Returned from Wordpress: </h3>"; var_dump($wp_error);};	
		}
		
	}
			

	
	// Authenticates and returns true if API key matches the provided key.
	function authentication(){
		global $API_KEY;
		
		if (isset($_GET['key'])) {
			$USER_KEY = $_GET['key'];
			
			if($USER_KEY == $API_KEY){
				return true;
			}
		
		}
		return false;
	}



	// Inserts the post into the WordPress Database
	function insertPost($post, $meta){
		$result = wp_insert_post( $post, true); 

		// if POST_ID was returned & meta data was included
		if(is_int($result) && $meta != null){
			$unique = true; // True: No duplicate with matching Meta_key for post_id
			
			foreach($meta as $key=>$val){
				if($key != null && $val != null && strlen($val) > 0){
					add_post_meta($post_id, $key, $val, $unique);
				}
			}
		}
		
		
		return $returned;
		
	}

	// Updates the post in the WordPress Database
	function updatePost($post, $meta){
		// Trenger her IDen til artikkelen som skal oppdateres. Den kan hentes med GUID.
		return wp_update_post( $post, true); 
	}






	// Fra Stefan
	function getRequestParams()
	{
		 if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			return $_GET;
		 } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return file_get_contents("php://input");
		 } elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
			return file_get_contents("php://input");
		 } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
			return file_get_contents("php://input");
		 }
	}










?>
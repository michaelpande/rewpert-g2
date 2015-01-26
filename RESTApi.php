<?php 
	ob_start(); // Turns output buffering on, making it possible to modify header information after echoing and var_dumping. 
	
	include('parser/parse.php');
	include('../../../wp-load.php'); // Potentially creates bugs.

	$DEBUG = false;
	
	// POSSIBLE AND KNOWN ISSUES: 
	// 		Injections on $_GET?
	//
	// RESPONSES:
	// 201: Created // Updated or created new
	// 409: Conflict // Existing GUID & Version >= Current
	// 401: Unauthorized // Wrong key
	// 						(304: Not modified Something went wrong and it was not modified)
	// 400: Bad request (Something wrong with the NewsML-G2)
	// 500: Internal Server Error
	
	
	

	if(isset($_GET["debug"]) && $_GET["debug"] == true){
		$DEBUG = true;
	}
	
	
	// Authentication
	echo $DEBUG == true ? "<h3>Authentication</h3>" : ""; 
	
	if(!authentication()){
		echo $DEBUG == true ? "Failed" : ""; 
		setHeader(401); // Unauthorized
		exit;
	}
	echo $DEBUG == true ? "Successful" : ""; 
	
	
	if($DEBUG){echo "<p>REQUEST_METHOD: ".$_SERVER['REQUEST_METHOD']."</p>";};
	
	
	if($_SERVER['REQUEST_METHOD'] != 'POST'){
		exit;
	}

	$postdata = getRequestParams();
	$parsed = Parse::createPost($postdata);
	
	if($DEBUG){echo "<h3>Returned from Parse.php: </h3>"; var_dump($parsed);};

	$post = $parsed['post'];
	$meta = $parsed['meta'];
		
	if($post != null && $meta != null){
		$wp_error = insertPost($post, $meta);
		if($DEBUG){echo "<h3>Returned from Wordpress: </h3>"; var_dump($wp_error);};
		
	}
			

			
			
			
			
	
	// Authenticates and returns true if API key matches the provided key.
	function authentication(){
		global $DEBUG;
		
		if (isset($_GET['key'])) {
			$USER_KEY = $_GET['key'];
			
			if($USER_KEY == getAPIkey()){
				return true;
			}
		
		}
		return false;
	}

	
	
	
	// Returns new or existing author ID
	function insertAuthor(){
		return null;
	}
	
	// Returns Wordpress Post by NML2-GUID
	function getPostByGUID($guid){
		global $DEBUG;
			
		if($DEBUG){echo "<p><strong>Get post by nml2-guid:</strong> $guid </p>";};	
		//$the_query = new WP_Query( "post_type=player&meta_key=player_team&meta_value=$teamname&order=ASC" );
		$the_query = new WP_Query( "post_type=post&meta_key=nml2_guid&meta_value=$guid&order=ASC" );
		
		// The Loop
		if ( $the_query->have_posts() ) {
			
			
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				return get_post();
				//$the_query->the_post();
				//echo '<li>' . get_the_title() . '</li>';
			}
			
		}
		
		return null;

	}
	

	// Inserts the post into the WordPress Database
	function insertPost($post, $meta){
		global $DEBUG;
		
		if($DEBUG){echo "<br><h2>Insert Post: </h2>";};	
		
		
		$existing_post = getPostByGUID($meta['nml2_guid']);
		
		
		
		// Updates post with corresponding ID, if the NML2-GUID is found in the WP Database and the meta->version is higher.
		if(	$existing_post != null){
			if($DEBUG){echo "<strong>Found post with ID: </strong> $post_id -> Just update existing" ;};
			if($DEBUG){var_dump($existing_post) ;};
			$version = $meta['nml2_version'];
			if($version > get_post_meta( $existing_post->ID, 'nml2_version' )[0]){
				if($DEBUG){echo "<p>UPDATE EXISTING RECORD</p>";};
				$post['ID'] = $existing_post->ID;
				$result = wp_update_post( $post, true);  // Creates a new revision, leaving two similar versions, only showing the newest.
				
			}else{
				if($DEBUG){echo "<p>NOT A NEWER VERSION: " . get_post_meta( $existing_post->ID, 'nml2_version' )[0] . "</p>";}; // Array Dereferencing (Requires PHP version > 5.4)
			}
		}else{
			
			$result = wp_insert_post( $post, true); // Creates new post
		}
		
		
		
		
		
		// if POST_ID was returned & meta data was included
		if(is_numeric($result) && $meta != null){
			if($DEBUG){echo "<h4>Set metadata in Wordpress: </h4>";};	
			insertPostMeta($result, $meta);
			setHeader(201); // Created
			
		}
		if($result == null){
			setHeader(409); // Conflict
		}else if(!is_numeric($result)){
			setHeader(304); // Not modified
		}
		
		return $result;
		
	}
	
	

	// Inserts or updates meta data for a post
	function insertPostMeta($post_id, $meta){
		global $DEBUG;
		$unique = true; // True: No duplicate with matching Meta_key for post_id
		
		foreach($meta as $key=>$val){
				
			if($DEBUG){echo "<br /><strong>Key:</strong> $key  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Value:</strong> $val";};	
			update_post_meta($post_id, $key, $val);
				
		}
		
	}

	// Returns the API key from the Wordpress Database
	function getAPIkey(){
		return get_option("nml2-plugin-api-key");
		
	}


	
	
	
	function setHeader($event){
		if($event != null){
			if($event != 200 && $event != 201){
				errorLogger::headerStatus($event); 
				exit;
			}
			errorLogger::headerStatus($event); 
			
		}
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
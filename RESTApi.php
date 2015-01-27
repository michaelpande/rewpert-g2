<?php 
	ob_start(); // Turns output buffering on, making it possible to modify header information after echoing and var_dumping. 
	
	include('parser/parse.php');
	include('../../../wp-load.php'); // Potentially creates bugs.

	$DEBUG = false;
	
	if(isset($_GET["debug"]) && $_GET["debug"] == true){
		$DEBUG = true;
	}
	
	// POSSIBLE AND KNOWN ISSUES: 
	// 		Injections on $_GET?
	//
	// RESPONSES:
	// 201: Created // Updated or created new
	// 409: Conflict // Existing GUID & Version >= Current
	// 401: Unauthorized // Wrong key
	// 						(304: Not modified Something went wrong and it was not modified (no more room in db etc))
	// 400: Bad request (Something wrong with the NewsML-G2)
	// 500: Internal Server Error
	
	
	

	
	
	// Authentication
	debug("<h3>Authentication</h3>");
	
	if(!authentication()){
		debug("Failed");
		setHeader(401); // Unauthorized
		exit;
	}
	
	debug("Successful");
	debug("REQUEST_METHOD: ".$_SERVER['REQUEST_METHOD']);

	
	
	if($_SERVER['REQUEST_METHOD'] != 'POST'){
		debug("The REQUEST_METHOD was not POST");
		setHeader(400); // Bad Request
		exit;
	}

	
	$postdata = getRequestParams();
	$parsed = Parse::createPost($postdata);
	
	
	debug("<h3>Returned from Parse.php: </h3>");
	debug(var_dump($parsed));
	
	// If something went wrong during parsing
	if($parsed['status_code'] != 200){
		setHeader($parsed['status_code']);
		exit;
	}

	// For each NewsItem or similar returned from Parse
	foreach($parsed as $key => $value){
		
		// If nothing went wrong during parsing
		$post = $value['post'];
		$meta = $value['meta'];
			
		if($post != null && $meta != null){
			$wp_error = insertPost($post, $meta);
			debug("<h3>Returned from Wordpress: </h3>");
			debug(var_dump($wp_error));
			
		}
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
		
		debug("<p><strong>Get post by nml2-guid:</strong> $guid </p>");
		$the_query = new WP_Query( "post_type=post&meta_key=nml2_guid&meta_value=$guid&order=ASC" );
		
		
		
		// The Loop
		if ( $the_query->have_posts() ) {
			
			
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				return get_post();
			}
			
		}
		
		return null;

	}
	

	// Inserts the post into the WordPress Database
	function insertPost($post, $meta){
		
		debug("<h2>Insert Post: </h2>");
		
		$existing_post = getPostByGUID($meta['nml2_guid']);
		
		
		
		// Updates post with corresponding ID, if the NML2-GUID is found in the WP Database and the meta->version is higher.
		if(	$existing_post != null){
			debug("<strong>Found post with ID: </strong> $post_id -> Just update existing");
			debug(var_dump($existing_post));
			
			$version = $meta['nml2_version'];
			
			// Check if imported version is higher than stored. 
			if($version > get_post_meta( $existing_post->ID, 'nml2_version' )[0]){ // Array Dereferencing (Requires PHP version > 5.4)
				debug("<p>UPDATE EXISTING RECORD</p>");
				$post['ID'] = $existing_post->ID; 
				$result = wp_update_post( $post, true);  // Creates a new revision, leaving two similar versions, only showing the newest.
				
			}else{
				debug("<p>NOT A NEWER VERSION: " . get_post_meta( $existing_post->ID, 'nml2_version' )[0] . "</p>"); // Array Dereferencing (Requires PHP version > 5.4)
			}
		}else{
			
			$result = wp_insert_post( $post, true); // Creates new post
		}
		
		
		
		
		
		// if POST_ID was returned & meta data was included
		if(is_numeric($result) && $meta != null){
			debug("<h4>Set metadata in Wordpress: </h4>");
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
		$unique = true; // True: No duplicate with matching Meta_key for post_id
		
		foreach($meta as $key=>$val){
			debug("<strong>Key:</strong> $key  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Value:</strong> $val");
			update_post_meta($post_id, $key, $val);
				
		}
		
	}
	
	
	// Not implemented yet
	// Purpose: Sets correct post categories for the post
	// Challenge: Need array of category IDs from DB.
	// Solutions: Foreach category string, find or create category id. 
	function setPostCategories($post_id, $meta){
		;
	}
	
	// Not implemented yet
	// Purpose: Convert to the correct dateformat to post_date and post_date_gmt
	/*function convertDate($post){
		
		// [ Y-m-d H:i:s ]
		if(isset($post['post_date']) && $post['post_date'] != null){
			$post['post_date'] = getDateFromString($post['post_date']);
		}
		if(isset($post['post_date_gmt']) && $post['post_date_gmt'] != null){
			$post['post_date_gmt'] = getGMTDateFromString($post['post_date_gmt']);
		}
		
	}*/
	
	function getDateFromString($str ){

		debug("Format date: " . date('Y-m-d\TH:i:s', $str)->format('Y-m-d'));
		
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


	// Simplifies code structure.
	function debug($str){
		global $DEBUG;
		if($DEBUG){
			echo "<p>".$str."</p>"; // Using <p> to create new lines. 
		}
	}






?>
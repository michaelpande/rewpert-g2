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
	
	
	if($DEBUG){echo "<p>REQUEST_METHOD: ".$_SERVER['REQUEST_METHOD']."</p>";};
	
	
	if($_SERVER['REQUEST_METHOD'] != 'POST'){
		exit;
	}

	$postdata = getRequestParams();
	$parsed = Parse::createPost($postdata);
	
	if($DEBUG){echo "<h3>Returned from Parse.php: </h3>"; var_dump($parsed);};

	$post = $parsed['post'];
	$meta = $parsed['meta'];
		
	if($post != null){
		$wp_error = insertPost($post, $meta);
		if($DEBUG){echo "<h3>Returned from Wordpress: </h3>"; var_dump($wp_error);};
		
	}
			

			
			
			
			
	
	// Authenticates and returns true if API key matches the provided key.
	function authentication(){
		global $API_KEY, $DEBUG;
		
		if (isset($_GET['key'])) {
			$USER_KEY = $_GET['key'];
			
			if($USER_KEY == $API_KEY){
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
			var_dump($existing_post);
			$version = $meta['nml2_version'];
			var_dump(get_post_meta( $existing_post->ID, 'nml2_version' ));
			if($version > get_post_meta( $existing_post->ID, 'nml2_version' )[0]){
				if($DEBUG){echo "<p>UPDATE EXISTING RECORD</p>";};
				$post['ID'] = $existing_post->ID;
				$result = wp_update_post( $post, true);  // Creates a new revision, leaving two similar versions, only showing the newest.
			}else{
				if($DEBUG){echo "<p>NOT A NEWER VERSION: " . get_post_meta( $existing_post->ID, 'nml2_version' )[0] . "</p>";};
			}
		}else{
			$result = wp_insert_post( $post, true); // Creates new post
		}
		
		
		
		
		
		// if POST_ID was returned & meta data was included
		if(is_numeric($result) && $meta != null){
			if($DEBUG){echo "<h4>Set metadata in Wordpress: </h4>";};	
			insertPostMeta($result, $meta);
		}
		
		return $result;
		
	}
	
	

	// Inserts or updates meta data for a post
	function insertPostMeta($post_id, $meta){
		global $DEBUG;
		$unique = true; // True: No duplicate with matching Meta_key for post_id
		
		foreach($meta as $key=>$val){
				
			if($DEBUG){echo "<br /><strong>Key:</strong> $key  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Value:</strong> $val";};	
			update_post_meta($post_id, $key, $val, true);
				
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
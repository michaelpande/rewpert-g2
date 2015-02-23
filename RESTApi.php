<?php 
	ob_start(); // Turns output buffering on, making it possible to modify header information after echoing and var_dumping. 
	
	require('parser/newsItemParse.php');
	require('parser/DateParser.php');
	require('parser/errorLogger.php');
	require('../../../wp-load.php'); // Potentially creates bugs.
	require('parser/QCodes.php');
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
	$parsed = newsItemParse::createPost($postdata);
	
	
	debug("<h3>Returned from Parse.php: </h3>");
	debug($parsed);
	
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
		$subjects = $value['subjects'];
		$authors = $value['users'];
			
		if($post != null && $meta != null){
			$wp_error = insertPost($post, $meta, $subjects, $authors);
			debug("<h3>Returned from Wordpress: </h3>");
			debug(var_dump($wp_error));
			
			// Wordpress returns post_id if successful, so a number can be used to confirm a successful post creation.
			if( is_numeric($wp_error) ) {
				setAuthors($authors, $wp_error);
			}
			
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
	function insertPost($post, $meta, $subjects, $authors){
		
		debug("<h2>Insert Post: </h2>");
		
		$existing_post = getPostByGUID($meta['nml2_guid']);
		
		if(isset($meta['nml2_versionCreated'])){
			$post['post_date'] = DateParser::getNonGMT($meta['nml2_versionCreated']);
			$post['post_date_gmt'] = DateParser::getGMTDateTime($meta['nml2_versionCreated']);
		}
		
		$post['post_author'] = getCreator($authors);
		// Updates post with corresponding ID, if the NML2-GUID is found in the WP Database and the meta->version is higher.
		if(	$existing_post != null){
			debug("<strong>Found post with ID: </strong> $post_id -> Just update existing");
			debug($existing_post);
			
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
			setPostCategories($result, $subjects, $meta['nml2_language']);
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
	function setPostCategories($post_id, $subjects, $lang){
		debug("<h2>setPostCategories</h2>");
		debug("<strong>Language: </strong> $lang");
		var_dump($subjects);
		$category_id = array();

		
		//foreach subject in meta
		foreach($subjects as $key=>$subject){
			$id = null;
			// Have to find match on language 
			var_dump($subject);
			foreach($subject['name'] as $nameKey=>$nameVal){
				$id = null;
				// If subject with name and correct lanuage is found
				if($nameVal['lang'] == $lang || $nameVal['lang'] == null){
					// Will use 'name' if set 
					debug("Lang is $lang");
					if($nameVal['text'] != null && $nameVal['role'] != "nrol:mnemonic"){
						debug("Text and role is OK.");
						QCodes::setSubject($subject['qcode'], $lang, $nameVal['text']);
						$id = createOrGetCategory($nameVal['text']);
					}else{
						debug("Checking for existing category..");
						$result = QCodes::getSubject($subject['qcode'], $lang); 
						if($result != null){
							$id = createOrGetCategory($result['name']);
						}
					}
					
				}else{
					debug("GET SUBJECT: " .$subject['qcode'].", $lang");
					$id = createOrGetCategory(QCodes::getSubject($subject['qcode'], $lang));
				}
				
				
				
				if($id != null){
					// PUSH TO ARRAY
					array_push($category_id, $id);
					
				}
				
				
			}
			
			if($id == null){
				debug("GET SUBJECT: " .$subject['qcode'].", $lang");
				$result = QCodes::getSubject($subject['qcode'], $lang); 
				var_dump($result);
				if($result != null){
					$id = createOrGetCategory($result['name']);
					array_push($category_id, $id);
				}	
				
			}
			
			
			
			createOrGetCategory($subject);
			
		}
		debug(var_dump($category_id));
		debug(var_dump($post_id));
		wp_set_post_categories( $post_id, $category_id, false );
	}

	// Creates and/or returns category ID for given string
	function createOrGetCategory($cat){
		if($cat == null){
			return;
		}
		
		$cat = ucfirst($cat);
		$cat_id = get_cat_ID( $cat);
		
		if($cat_id != 0){
			debug("Found category! " . $cat_id);
			return $cat_id;
		}
		// CREATE CATEGORY AND RETURN ID; wp_insert_category
		$result = wp_insert_term(
			$cat,
			'category',
			array(
			  'description'	=> '',
			  'slug' 		=> ''
			)
		);
	  
		debug("Result from creation of category: " . var_dump($result));
		debug("Create or get Category" . get_cat_ID( $cat));
		return get_cat_id($cat);
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
	
	
	// Returns first element of array or null.
	function getCreator($authors){
		debug("<strong>Get creator</strong>");
		foreach($authors as $nameKey=>$nameVal){
			$creator = createOrGetAuthor($nameVal);
			debug("Creator:" . var_dump($creator));
			return $creator;
		}
	}
	
	function setAuthors($authors, $post_id){
		debug("<strong>Set authors</strong>");
		debug(var_dump($authors));          

		$author_meta = "";
		
		
		// Create or get author ID
		foreach($authors as $nameKey=>$nameVal){
			$id = createOrGetAuthor($nameVal);
			if(is_numeric($id)){
				$author_meta = $author_meta . "$id,";
			}	
			
		}
		
		$result = update_post_meta($post_id, "nml2_multiple_authors", $author_meta);
		debug("Result " . var_dump($result));
		
	}
	
	
	function createOrGetAuthor($auth){
		
		
		// Return if null or author has no name
		if($auth == null || $auth['user_login'] == null){
			return;
		}
		
		$author = get_user_by( 'login', $auth['user_login'] );
		
		
		if($author->ID != null){
			debug("Found author! " . var_dump($author));
			return $author->ID;
		}
		// CREATE CATEGORY AND RETURN ID; wp_insert_category
		$email = ($auth['user_email'] == null ? "" : $auth['user_email']);
		$password = "";
		
		$result = wp_create_user ( $auth['user_login'], $password, $email );
	  
		debug("Result from creation of category: " . var_dump($result));
		debug("Create or get Author" . get_cat_ID( $auth));
		return $result;
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
			if(is_string($str)){
				echo "<p>".$str."</p>"; // Using <p> to create new lines. 
			}else{
				var_dump($str);
			}
		}
	}




?>
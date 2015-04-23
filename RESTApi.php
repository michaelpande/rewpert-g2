<?php 

/**
* This PHP file handles all interaction with the Wordpress Database and makes it possible to send HTTP POST requests with 
* NewsML-G2 in XML to WordPress. 
* Usage of this REST API requires a key, the key is retrieved from the WordPress database after installing the plugin.
* The REST API transaction is not atomic, the post will still be added to the WordPress database even if subjects or authors fail. 
*
* RESPONSES:
* 201: Created * Updated or created new
* 409: Conflict * Existing GUID & Version >= Current
* 401: Unauthorized * Wrong key
*(304: Not modified Something went wrong and it was not modified (no more room in db etc))
* 400: Bad request (Something wrong with the NewsML-G2)
* 500: Internal Server Error
*
* Untested possible security issues: 
* 	$_GET injection on key
*
*
* @author Michael Pande
*/

    require('parser/newsItemParse.php'); 	  // Parses NewsML-G2 NewsItems
    require('parser/DateParser.php'); 		  // Parses Date strings
    require('parser/httpHeader.php'); 		  // Sets HTTP status codes
    require('parser/QCodes.php'); 			  // Handles storage and retrieval of QCodes and their values.
    require('functions/functions.php');
    require('../../../wp-load.php'); 		  // Potentially creates bugs. Necessary to access methods in the WordPress core from outside.



	ob_start();                     // Turns output buffering on, making it possible to modify header information (status codes etc) after echoing, printing and var_dumping.

	$DEBUG = false;                 // Return debug information or not
	$UPDATE_OVERRIDE = false;       // True = Ignore version number in NewsItem and do update anyways.
    $MANUAL_UPLOAD = false;         // If the import is done manually

    setGlobalUserVariables();       // Sets the global variables above with user values

    authenticateUser();             // Stops the entire process if key is invalid

	$userInput = getUserInput();    // Will stop the entire process if invalid request and set headers accordingly
	




    $containedKnowledgeItems = QCodes::updatePluginDB($userInput);   // If $userInput has KnowledgeItem: Updates the plugin specific QCodes database with QCodes
	debug("<h3>Returned from KnowledgeItemParser.php: </h3>");
	debug($containedKnowledgeItems);


    $parsedXML = newsItemParse::parseNewsML($userInput);              // Gets a multidimensional array in return with potential NewsItems.
	debug("<h3>Returned from NewsItemParse.php: </h3>");
    debug($parsedXML);





	if($parsedXML['status_code'] != 200){       // Checks if something went wrong during parsing

		if($containedKnowledgeItems){
			debug("KnowledgeItem was imported, setting http header 201");
			setHeader(201);
		}else{
			debug("Did not contain KnowledgeItem, so use http header from NewsItemParser");
			setHeader($parsedXML['status_code']);
		}

		exitApi();
	}




	// For each NewsItem or similar returned from NewsItemParse
	foreach($parsedXML as $newsItem){
		insertPost($newsItem);
	}
	
	
	exitAPI();

	

	/**
	 * Inserts:
	 *    NewsItem (post), 
	 * 	  NMLG2 meta data, 
	 *    Keywords(tags), 
	 *    Subjects(categories),
	 *    Creators / Contributors (authors) 
	 * 	into WordPress 
	 *
	 * @param $newsItem - A NewsItem object (array of values)
	 * @author Michael Pande
	 */
	function insertPost($newsItem){
		global $UPDATE_OVERRIDE;

        $post = $newsItem['post'];
        $meta = $newsItem['meta'];
        $subjects = $newsItem['subjects'];
        $authors = $newsItem['users'];
        $photos = $newsItem['photo'];


        // Check if something vital is missing after parsing
        if($post == null && $meta == null && ($post['post_content'] == null || $post['post_title'] == null)){
            debug('Something vital was not parsed: importing stopped.');
            return;
        }

        debug("<h2>Insert/Update Post: </h2>");

		// Sets date on WP_POST object, it supports GMT (UTC) and Non-GMT.
		if(isset($meta['nml2_versionCreated'])){
			$post['post_date'] = DateParser::getNonGMT($meta['nml2_versionCreated']);
			$post['post_date_gmt'] = DateParser::getGMTDateTime($meta['nml2_versionCreated']);
		}



		// Sets author like the creator, for the single author support in WordPress, multi author support is handled in another method
		$post['post_author'] = getCreator($authors); // null = Will be shown as default user in WP (Admin)


        $existing_post = getPostByGUID($meta['nml2_guid']);
        debug("<strong>Existing post: </strong>");
        debug($existing_post);

		// Updates post with corresponding ID, if the NML2-GUID is found in the WP Database and the meta->version is higher.
		if(	$existing_post != null){
			debug('<strong>Found post with ID: </strong> $post_id -> Just update existing');
			debug($existing_post);
			
			$version = $meta['nml2_version'];
			
			
			// Check if imported version is higher than stored. 
			if($UPDATE_OVERRIDE || $version > get_post_meta( $existing_post->ID, 'nml2_version' )[0]){ // Array Dereferencing (Requires PHP version >= 5.4)
				debug("<p>UPDATE EXISTING RECORD</p>");
				$post['ID'] = $existing_post->ID; 
				$result = wp_update_post( $post, true);  // Creates a new revision, leaving two similar versions in wp_database, only showing the newest. Both are accessible through the WP admin interface.
				
				
			}else{
				debug("<p>NOT A NEWER VERSION: " . get_post_meta( $existing_post->ID, 'nml2_version' )[0] . "</p>"); // Array Dereferencing (Requires PHP version >= 5.4)
			}
			
			
		}else{
			$result = wp_insert_post( $post, true); // Creates new post
			
		}





        insertPostMeta($result, $meta);
        setPostCategories($result, $subjects, $meta['nml2_language']);

		insertPhotos($result, $post, $photos);
        setAuthors($result, $authors); // Allows multiple author support.





        debug("Returned from wordpress");
        debug( $result);
		
		if($result == null){
			setHeader(409); // Conflict (Existing copy with same version number and GUID)
		}

		if(is_numeric($result)){
			setHeader(201); // Created
		}
        else {
	        setHeader( 304 ); // Not modified
        }

	}
	
	/**
	 * Inserts:
	 *    PHOTOS
	 * 	into WordPress 
	 *
	 * @param $post - A WP_Post object (array of values)
	 * @param $post_id - The ID of the post 
	 * @param $photos - A multidimensional array of photos. 
	 * @author Michael Pande
	 */
	function insertPhotos($post_id, $post, $photos){
		debug("<h2>Insert Photos</h2>");
		if(!is_numeric($post_id) || $post == null || $photos == null){
			return;
		}
		$count = 0;
		$firstUrl = null;
		$setFeatureImage = true;
		
		foreach($photos as $key=>$val){
				if($val["href"] != null){
					$count++;
					$image = wp_get_image_editor( $val["href"] );
					debug("<strong>Check for WP_ERROR</strong>");
					if ( ! is_wp_error( $image ) ) {
						debug("No error");
						$imgUrl = '/images/'.$post_id.'/' . $count . '.jpg';
						debug("ImageUrl: " . $imgUrl);
						
						$orig_filename = basename($val["href"]);
						debug("Original filename: " . $orig_filename);
						$image->save( ".".$imgUrl );
	
						
						
						$pattern = "/(src=)[\"\'](.*)".$orig_filename."[\"\']/";
						debug("Pattern: " . $pattern);
						
						$new_url = "src=\"". getPathToPluginDir() .$imgUrl."\"";
						
						// Replace 
						if($firstUrl == null){
							$firstUrl = getPathToPluginDir() .$imgUrl;
							// Remove first img tag. 	
							$post['post_content'] =  preg_replace('/(<img[^>]+>)/i','',$post['post_content'],1); 
						}
						
						
						$post['post_content'] = preg_replace($pattern,$new_url,$post['post_content'],-1);
						
				
						debug($post);
						debug($image);
					}else{debug("Error");}
					
				}
				
				
		}
        $post['ID'] = $post_id; // Ensure that ID is set
		$result = wp_update_post( $post, true);  // Creates a new revision, leaving two similar versions, only showing the newest.
		
		// Set feature image 
		if($firstUrl != null){
			
			setFeatureImage($post_id, $firstUrl);

			//update_post_meta($post_id, '_wp_attached_file', $firstUrl);
		}
		
		debug("<strong>Result from WP_UPDATE:</strong>");
		debug($result);		

	}
	
	
	
	/* Attribution:
	 * The code in this method is from Stack Overflow 
	 * Link to question: http://wordpress.stackexchange.com/questions/100838/
	 * Question authors:
	 *	Faisal Shehzad - http://wordpress.stackexchange.com/users/30847/faisal-shehzad
	 * Answer author:
	 *  GhostToast - http://wordpress.stackexchange.com/users/13676/ghosttoast */
	function setFeatureImage($post_id, $url){
		
		// only need these if performing outside of admin environment
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		// example image
		$image = 'http://example.com/logo.png';

		// magic sideload image returns an HTML image, not an ID
		$media = media_sideload_image($url, $post_id);

		// therefore we must find it so we can set it as featured ID
		if(!empty($media) && !is_wp_error($media)){
			$args = array(
				'post_type' => 'attachment',
				'posts_per_page' => -1,
				'post_status' => 'any',
				'post_parent' => $post_id
			);

			// reference new image to set as featured
			$attachments = get_posts($args);

			if(isset($attachments) && is_array($attachments)){
				foreach($attachments as $attachment){
					// grab source of full size images (so no 300x150 nonsense in path)
					$image = wp_get_attachment_image_src($attachment->ID, 'full');
					// determine if in the $media image we created, the string of the URL exists
					if(strpos($media, $image[0]) !== false){
						// if so, we found our image. set it as thumbnail
						set_post_thumbnail($post_id, $attachment->ID);
						// only want one image
						break;
					}
				}
			}
		}
	}

	
	
	
	/**
	 * This method inserts meta data in to the wordpress database, by using the post_meta table, this process 
	 * requires a post_id and an associative array of meta data
	 *
	 * @param $post_id - integer of post_id 
	 * @param $meta - An associative array of metadata
	 * 
	 * @author Michael Pande
	 */
	function insertPostMeta($post_id, $meta){
		debug("<h4>Set metadata in Wordpress: </h4>");
		if(!is_numeric($post_id) || $meta == null){
			return;
		}

		foreach($meta as $key=>$val){
			debug("<strong>Key:</strong> $key  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Value:</strong> $val");
			update_post_meta($post_id, $key, $val);
				
		}
		
		
	}
	
	
	/**
	 * This method sets the post categories in WordPress, it requires the QCodes class, which handles QCodes and
	 * corresponding language values. 
	 *
	 * @param $post_id - A WP_Post object id
	 * @param $subjects - A multidimensional array of subjects containing qcodes, names and misc metadata
	 * @param $lang - Language of post, used to select from the database of QCodes, as secondary key. 
	 * @return $result - After attempting to create a WP post this result is created.
	 *  
	 * @author Michael Pande
	 */
	function setPostCategories($post_id, $subjects, $lang){
		debug("<h2>setPostCategories</h2>");
		debug("<strong>Language: </strong> $lang");
		debug($subjects);

		if(!is_numeric($post_id) || $subjects == null){
			return;
		}

		$category_id = array();

		
		//foreach subject in meta
		foreach($subjects as $key=>$subject){
			
			// Have to find match on language 
			debug($subject);
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
				debug($result);
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

	/**
	 * This method creates or gets category for a given string
	 *
	 * @param $cat - The displayed category name (The one that will be shown to users in Wordpress)
	 * @return $result - Wordpress error, or category id. 
	 *  
	 * @author Michael Pande
	 */
	function createOrGetCategory($cat){
		if($cat == null || !is_string($cat) || strlen($cat) == 0){
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
	
	
	
	
	/**
	 * This method returns the API key stored in the WordPress database.
	 *
	 * @return string - The API key. 
	 *  
	 * @author Michael Pande
	 */
	function getAPIkey(){
		return get_option("nml2-plugin-api-key");
		
	}



	
	
	
	/**
	 * This method sets the header with an http status. 
	 *
	 * @param int $event - The number that will be set.
	 *  
	 * @author Michael Pande
	 */
	function setHeader($event){
		if($event != null){
			if($event != 200 && $event != 201){
				httpHeader::setHeader($event);
				exitApi();
			}
			httpHeader::setHeader($event);
			
		}
	}
	
	
	/**
	 * Returns the creator of the $author array. Which will always be the first or null. (Return in the foreach).
	 *
	 *
	 * @param $authors - array of authors (Creator $ Contributors)
	 * @return Creator as string or null
	 * @author Michael Pande
	 */
	function getCreator($authors){
		debug("<strong>Get creator</strong>");
		if($authors == null)
			return null;
		
		foreach($authors as $nameKey=>$nameVal){
			
			$creator = createOrGetAuthor($nameVal);
			
			if($creator != null || $creator == ""){
				debug("Creator:");
				debug($creator);
				return $creator;
			}
		}
		
		return null;
	}
	
	
	/**
	 * Set multiple authors on a post, if they don't exist in the Wordpress database, it will create them. 
	 *
	 * @param $authors - array of authors (Creator $ Contributors)
	 * @param $post_id - ID of the post
	 *  
	 * @author Michael Pande
	 */
	function setAuthors($post_id, $authors){
		debug("<strong>Set authors</strong>");
		debug($authors);
		if(!is_numeric($post_id) || $authors == null){
			return;
		}

		$author_meta = "";
		
		
		// Create or get author ID
		foreach($authors as $nameKey=>$nameVal){
			$id = createOrGetAuthor($nameVal);
			if(is_numeric($id)){
				$author_meta = $author_meta . "$id,";
			}	
			
		}
		
		$result = update_post_meta($post_id, "nml2_multiple_authors", $author_meta);
		debug("Result " );
		debug($result);
		
	}
	
	/**
	 * Creates or gets an author in the WordPress database. 
	 *
	 * @param $auth - author (Creator / Contributor)
	 * @return $result - the result for author creation, retrieved user or a WP_error. 
	 *
	 * @author Michael Pande
	 */
	function createOrGetAuthor($auth){
		
		
		// Return if null or author has no name
		if($auth == null || strlen($auth['user_login']) <= 0){
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
	


	/**
	 * Simply exits the API, and prevents showing output if debug isn't true 
	 *
	 * @author Michael Pande
	 */
	function exitApi(){
		global $DEBUG, $MANUAL_UPLOAD;
		
		
		if(!$DEBUG){
			ob_clean();
		}
		
		// Always return successful (Not critical should be fixed)
		if($MANUAL_UPLOAD){
		
			if(http_response_code() == 201){
				die("File successfully uploaded: " . http_response_code() . '<br><a href="'.$_SERVER['HTTP_REFERER'].'">Back</a>');
			}else{
				die("File upload failed: " . http_response_code() . '<br><a href="'.$_SERVER['HTTP_REFERER'].'">Back</a>');
			}
		}
		exit;
	}


    function authenticateUser(){
        // Authentication, returns 401 if wrong API key
        debug("<h3>Authentication</h3>");

        if(!authentication()){
            debug("Failed");
            setHeader(401); // Unauthorized
            exitApi();
        }
        debug("Successful");
    }


	/**
	 * Authenticates and returns true if API key matches the key sent with HTTP_GET['key'].
	 * 
	 * @return boolean
	 *
	 * @author Michael Pande
	 */
	function authentication(){
		
		if (isset($_GET['key'])) {
			$USER_KEY = $_GET['key'];
			
			if($USER_KEY == getAPIkey()){
				return true;
			}
		
		}
		return false;
	}

	
	function setGlobalUserVariables(){
        global $DEBUG, $UPDATE_OVERRIDE,$MANUAL_UPLOAD;

        if(isset($_GET["debug"]) && $_GET["debug"] == true){
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            $DEBUG = true;
        }

        if(isset($_GET["update_override"]) && $_GET["update_override"] == true) {
            $UPDATE_OVERRIDE = true;
        }

        if(isset($_GET["manual"]) && $_GET["manual"] == true){
            $MANUAL_UPLOAD = true;
        }
    }

	

	/**
	 * Returns Wordpress Post by NML2-GUID
	 *
	 * This method uses a GUID from the NewsML-G2 and checks for a WordPress post with same GUID meta value.
	 *
	 * @param STRING the GUID to match WordPress posts with
	 * @return First matching post with GUID
	 *
	 * @author Michael Pande
	 */
	function getPostByGUID($guid){
		
		debug("<p><strong>Get post by nml2-guid:</strong> $guid </p>");
		
		$args = array(
			'meta_key' => 'nml2_guid',
			'meta_value' => $guid,
			'post_status' => 'any'
		);
		
		$the_query = new WP_Query( $args );
		
		debug($the_query);
		
		
		// The WordPress Loop
		if ( $the_query->have_posts() ) {
			
			
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				return get_post();
			}
			
		}
		
		return null;

	}
	



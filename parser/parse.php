<?php

class Parse {
	
	/*A metod used to create and fill the array that contains a status code, post information and metadata from a NewsML-G2 document
	  
	  Array structore:
		$returnArray = array(
			'status_code' => int
			0 => newsItemArray = array(
					'post' => $post = array(
								'post_content' => string
								'post_name'    => string
								'post_title'   => string
								'post_status'  => string
								'tags_input'   => string
							  );
					'meta' => $meta  = array(
								'nml2_guid' 		  	=> string
								'nml2_version' 		  	=> string
								'nml2_firstCreated'   	=> string
								'nml2_versionCreated' 	=> string
								'nml2_embarogDate' 	  	=> string
								'nml2_newsMessageSendt' => string
								'nml2_language'			=> string
							  );
					'users' => $users = array(
								'creators' 	   => $creator = array(
													0 => $user = array(
															'user_login'  => string
															'description' => string
															'nml2_qude'	  => string
															'nml2_uri'	  => string
														  );
													1 => Same as the index above. Number of indexes dependes on number of creators
								'contributors' => $contributor = array(
													0 => $user array(
															'user_login'  => string
															'description' => string
															'nml2_qude'	  => string
															'nml2_uri'	  => string
												         );
													1 => Same as the index above. Number of indexes dependes on number of contributors
													);
							   );
				 );
			1 => Same as index 0. This is index, index 0 and alle numbers above is added whit array_push
				 and the index numbers used is decidded by the numbers of newsItems
		);		 
		
	  Returns: an associative array
	  Parameters:
		- $file: the raw XML in the NewsML-G2 format
	*/ 	
	public static function createPost($file){
		$doc = new DOMDocument();
		
		if(is_file($file)) {
			$doc->load($file);
		}
		else {
			$doc->loadXML($file);
		}

        $xpath = new DOMXPath($doc);
		
		//XML namescpaces
        $xpath->registerNamespace('html', "http://www.w3.org/1999/xhtml");
		$xpath->registerNamespace('nitf', "http://iptc.org/std/NITF/2006-10-18/");
		$xpath->registerNamespace('newsMessage', "http://iptc.org/std/nar/2006-10-01/");
		
		/*Query to separate the direfent newsItems in a newsMessage
		  This query vill find the absolutt path (without XML namespaces): newsMessage/itemSet/newsItem
		*/
		$newsItemList = $xpath->query("//newsMessage:newsItem");
		
		$returnArray = array( 
			'status_code' => 200 //int, the status code automatically set to 200
		);
		
		foreach($newsItemList as $newsItem) {
			$newsItemArray = array(
				'post'  => Parse::createPostArray($newsItem, $xpath),
				'meta'  => Parse::createMetaArray($newsItem, $xpath),
				'users' => Parse::createUserArray($newsItem, $xpath)
			);
			
			//Cheking if there is anny errors in the data gathert from the newsML document and chenges status code accordingly
			$returnArray['status_code'] = Parse::setStatusCode($returnArray, $newsItemArray);
			
			//Cheking if an embargo date is present and changes 'post_status' accordingly
			$newsItemArray['post']['post_status'] = Parse::setEbargoState($newsItemArray['meta']['nml2_embarogDate']);
			
			//Adds the informating found in the newsItem to the array that will be sendt to the RESTApi
			array_push($returnArray, $newsItemArray);
		}
		
		return $returnArray;	
	}
	
	/*A metod used to create and fill the array containg the post that are to be added to the Worptess database
	  Returns: an associative array on a satadard given by Wordpress, see http://codex.wordpress.org/Function_Reference/wp_insert_post
	  for more information on the post array
	  Parameters:
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/ 	
	private static function createPostArray($newsItem, $xpath) {
        $post = array(
            //'ID'           => [ <post id> ] // Are you updating an existing post?
            'post_content'   => Parse::getPostContent($newsItem, $xpath), // The full text of the post.
            'post_name'      => Parse::getPostName($newsItem, $xpath), // The name (slug) for your post
            'post_title'     => Parse::getPostHeadline($newsItem, $xpath), // The title of your post.
            'post_status'  	 => 'publish', //[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
            /*'post_type'      => [ 'post' | 'page' | 'link' | 'nav_menu_item' | custom post type ] // Default 'post'.
            'post_author'    => [ <user ID> ] // The user ID number of the author. Default is the current user ID.
            'ping_status'    => 'closed',// Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
            'post_parent'    => [ <post ID> ] // Sets the parent of the new post, if any. Default 0.
            'menu_order'     => [ <order> ] // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
            'to_ping'        => // Space or carriage return-separated list of URLs to ping. Default empty string.
            'pinged'         => // Space or carriage return-separated list of URLs that have been pinged. Default empty string.
            'post_password'  => [ <string> ] // Password for post, if any. Default empty string.
            'guid'           => Parse::getPostGuid($xpath)// Skip this and let Wordpress handle it, usually.
            'post_content_filtered' => // Skip this and let Wordpress handle it, usually.
            'post_excerpt'   => [ <string> ] // For all your post excerpt needs.
            'post_date'      => [ Y-m-d H:i:s ] // The time post was made.
            'post_date_gmt'  => [ Y-m-d H:i:s ] // The time post was made, in GMT.
            'comment_status' => [ 'closed' | 'open' ] // Default is the option 'default_comment_status', or 'closed'.
            'post_category'  => [ array(<category id>, ...) ] // Default empty.*/
            'tags_input'     => Parse::getPostTags($newsItem, $xpath) // Default empty.
            /*'tax_input'      => [ array( <taxonomy> => <array | string> ) ] // For custom taxonomies. Default empty.
            'page_template'  => [ <string> ] // Requires name of template file, eg template.php. Default empty.*/
        );
		
		return $post;
	}
	
	/*A metod used to create and fill the array containg metadata frome the NewsML document
	  Returns: an associative array
	  Parameters:
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/ 	
	private static function createMetaArray($newsItem, $xpath) {
		$meta = array(
			'nml2_guid' 		  	=> Parse::getMetaGuid($newsItem, $xpath), //string, the guide of the newsItem
			'nml2_version' 		  	=> Parse::getMetaVersion($newsItem, $xpath), //string, the version of the newsItem
			'nml2_firstCreated'   	=> Parse::getMetaFirstCreated($newsItem, $xpath), //string, the timesap when the newsItem was first created
			'nml2_versionCreated' 	=> Parse::getMetaVersionCreated($newsItem, $xpath), //string, the timestamp when the curent version of the newsItem var created
			'nml2_embarogDate' 	  	=> Parse::getMetaEmbargo($newsItem, $xpath), //string, timestamp of the embargo
			'nml2_newsMessageSendt' => Parse::getMetaSentDate($xpath), //string
			'nml2_language'			=> Parse::getMetaLanguage($newsItem, $xpath), //string
			'nml2_creator' 			=> null, //array
			'nm2_contributor' 		=> null //array
		);
		
		return $meta;
	}
	
	private static function createUserArray($newsItem, $xpath) {
		$users = array(
			'creators' 		=> Parse::getCreator($newsItem, $xpath),
			'contributors' 	=> Parse::getContributor($newsItem, $xpath)
		);
		
		return $users;
	}
	
	/*A metod used to find the contet of the news articel
	  Returns: content as string, null if no content found
	  Parameters: 
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/
	private static function getPostContent($newsItem, $xpath) {
		$content = null;
		
		/*Query path that continus from first query at the start of the document.
		  Path without XML namespace: contentSet/inlineXML/html/body
		*/
		$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:inlineXML/html:html/html:body", $newsItem);
		
		if($nodelist->length == 0) {
			
			/*Traying this query if the above query  gives no result.
			  Query path that continus from first query at the start of the document.
			  Path without XML namespace: contentSet/inlineXML/nitf/body/body.content
			*/  
			$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:inlineXML/nitf:nitf/nitf:body/nitf:body.content", $newsItem);
			
			if($nodelist->length == 0) {
			
				/*Traying this query if the above query  gives no result.
				  Query path that continus from first query at the start of the document.
				  Path without XML namespace: contentSet/inlineData
				*/  
				$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:inlineData", $newsItem);
			}
		}
		
		/*Sets the results of the query above on the return variable if anny
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly
		*/
		foreach($nodelist as $node) {
            $content = $node->nodeValue;
        }
		
        return $content;
	}
	
	/*A metod used to find the headline of the news articel
	  Returns: headline as string, null if no headline found
	  Parameters: 
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/
	private static function getPostHeadline($newsItem, $xpath) {
		$headline = null;
		
		/*Query path that continus from first query at the start of the document.
		  Path without XML namespace: contentMeta/headline
		*/
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:headline", $newsItem);
		
		if($nodelist->length == 0) {
		
			/*Traying this query if the above query  gives no result
			  Query path that continus from first query at the start of the document.
			  Path without XML namespace: contentSet/inlineXML/html/head/title
			*/  
			$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:inlineXML/html:html/html:head/html:title", $newsItem);
			
			if($nodelist->length == 0) {
				
				/*Traying this query if the above query  gives no result
				  Query path that continus from first query at the start of the document.
				  Path without XML namespace: contentSet/inlineXML/html/head/title
				*/  
				$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:inlineXML/nitf:nitf/nitf:body.head/nitf:hedline", $newsItem);
			}
		}
		
		/*Sets the results of the query above on the return variable if anny
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly
		*/
		foreach($nodelist as $node) {
			$headline = $node->nodeValue;
		}

		return $headline;
	}
	
	/*A metod used to find the slugline of the news articel
	  Returns: slugline as string, null if no slugline found
	  Parameters: 
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/
	private static function getPostName($newsItem, $xpath) {
		$name = null;
		
		/*Query path that continus from first query at the start of the document
		  Path without XML namespace: contentMeta/slugline
		*/
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:slugline", $newsItem);
		
		/*Sets the results of the query above on the return variable if anny
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly
		*/
		foreach($nodelist as $node) {
			$name = $node->nodeValue;
		}
		
		return $name;
	}
	
	/*A metod used to find the keywords of the news articel and organises them soe they fit the way tags are stored in Wordpress
	  Returns: tags as string, null if no keywords found
	  Parameters: 
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/
	private static function getPostTags($newsItem, $xpath) {
		$tags = null;
		
		/*Query path that continus from first query at the start of the document
		  Path without XML namespace: contentMeta/keyword
		*/
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:keyword", $newsItem);
		
		/*Sets the results of the query above on the return variable if anny
		  Result of this loop shud lock like: '<keyword>,<keyword>,...'
		*/
		foreach($nodelist as $node) {
			$tags .= $node->nodeValue . ",";
		}
		
		return $tags;
	}
	
	/*A metod used to find the guid of the news articel
	  Returns: guid as string, null if no guid found
	  Parameters: 
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/
	private static function getMetaGuid($newsItem, $xpath) {
		$guid = null;
		
		/*Query path that continus from first query at the start of the document
		  Path without XML namespace: @guid (find the guid attribute in the newsItem tag)
		*/
		$nodelist = $xpath->query("@guid", $newsItem);
		
		/*Sets the results of the query above on the return variable if anny
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly
		*/
		foreach($nodelist as $node) {
			$guid = $node->nodeValue;
		}
		
		return $guid;
	}
	
	/*A metod used to find the version of the news articel
	  Returns: version as string, null if no version found
	  Parameters: 
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/
	private static function getMetaVersion($newsItem, $xpath) {
		$version = null;
		
		/*Query path that continus from first query at the start of the document
		  Path without XML namespace: @version (find the version attribute in the newsItem tag)
		*/
		$nodelist = $xpath->query("@version", $newsItem);
		
		/*Sets the results of the query above on the return variable if anny
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly
		*/
		foreach($nodelist as $node) {
			$version = $node->nodeValue;
		}
		
		return $version;
	}
	
	/*A metod used to find the timestamp frome when the news articel where first created
	  This metod does not change the timestamp in any way, just fetches it from the XML and send it ot the RESTapi
	  Returns: timestamp as string, null if no firstCreated tag found
	  Parameters: 
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/
	private static function getMetaFirstCreated($newsItem, $xpath) {
		$firstCreated = null;
		
		/*Query path that continus from first query at the start of the document
		  Path without XML namespace: itemMeta/firstCreated
		*/
		$nodelist = $xpath->query("newsMessage:itemMeta/newsMessage:firstCreated", $newsItem);
		
		/*Sets the results of the query above on the return variable if anny	
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$firstCreated = $node->nodeValue;
		}
		
		return $firstCreated;
	}
	
	/*A metod used to find the timestamp frome when the curent version of the news articel where created
	  This metod does not change the timestamp in any way, just fetches it from the XML and send it ot the RESTapi
	  Returns: timestamp as string, null if no versionCreated tag found
	  Parameters: 
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/
	
	/**
	 * Findes and returns timestamp from when the present version where created
	 *
	 * This metod uses a DOMXPath query to find and return a timestamp from when the curent version of the NewsML-G2
	 * document where created
	 */
	private static function getMetaVersionCreated($newsItem, $xpath) {
		$versionCreated = null;
		
		/*Query path that continus from first query at the start of the document.
		  Path without XML namespace: itemMeta/versionCreated
		*/
		$nodelist = $xpath->query("newsMessage:itemMeta/newsMessage:versionCreated", $newsItem);
		
		/*Sets the results of the query above on the return variable if anny.
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$versionCreated = $node->nodeValue;
		}
		
		return $versionCreated;
	}
	
	/**
	 * Finds and returns the embargo if present
	 *
	 * This metod user DOMXPath query to find the embargo date of a NewsML-G2 Docuemtn and reutrns it as a string
	 *
	 * @param DOMNodeList $newsItem XPath query from an erlier part of the document that the new query shal be preformed eon
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string embargo date, null of no embargo i present
	 */
	private static function getMetaEmbargo($newsItem, $xpath) {
		$embargo = null;
		
		/*Query path that continus from first query at the start of the document.
		  Path without XML namespace: itemMeta/embargoed
		*/
		$nodelist = $xpath->query("newsMessage:itemMeta/newsMessage:embargoed", $newsItem);
		
		/*Sets the results of the query above on the return variable if anny.
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$embargo = $node->nodeValue;
		}
		
		return $embargo;
	}

	/**
	 * Finds and returns the sent date frome the NewsML document
	 *
	 * This metod finds the <sent> tag in NewsML-G2 and returns it as a astring. It uses DOMEXpath
	 * find the tag
	 *
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string Date sent, null if no date is present
	 * @author Petter Lundberg Olsen
	 */ 
	private static function getMetaSentDate($xpath) {
		$dateSent = null;
		
		//Path without XML namespace: newsMessage/header/sent
		$nodelist = $xpath->query("//newsMessage:newsMessage/newsMessage:header/newsMessage:sent");
		
		/*Sets the results of the query above on the return variable if anny.
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$dateSent = $node->nodeValue;
		}
		
		return $dateSent;
	}
	
	/**
	 * Finds and returns the language of the news aritcel
	 *
	 * This metod findes the laguage of the content in a NewsML-G2 docuemnt using DOMXPath and returns it as a string
	 *
	 * @param DOMNodeList $newsItem XPath query from an erlier part of the document that the new query shal be preformed eon
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string The laguage of the news articel
	 * @author Petter Lundberg Olsen
	 */
	private static function getMetaLanguage($newsItem, $xpath) {
		$language = null;
		
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:language/@tag", $newsItem);
		
		foreach($nodelist as $node) {
			$language = $node->nodeValue;
		}
		
		return $language;
	}
	
	private static function getCreator($newsItem, $xpath) {
		$creator = array ( );
		
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:creator", $newsItem);
		
		foreach($nodelist as $node) {
			$user = array(
				'user_login' 	=> Parse::getUserName($node, $xpath),
				'description'	=> Parse::getUserDescription($node, $xpath),
				'nml2_qcode'		=> Parse::getUserQcode($node, $xpath),
				'nml2_uri'		=> Parse::getUserUri($node, $xpath)
			);

			array_push($creator, $user);
		}
		
		return $creator;
	}
	
	private static function getContributor($newsItem, $xpath) {
		$contributor = array( );
		
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:contributor", $newsItem);
		
		foreach($nodelist as $node) {
			$user = array(
				'user_login' 	=> Parse::getUserName($node, $xpath),
				'description'	=> Parse::getUserDescription($node, $xpath),
				'nml2_qude'		=> Parse::getUserQcode($node, $xpath),
				'nml2_uri'		=> Parse::getUserUri($node, $xpath)
			);
			
			array_push($contributor, $user);
		}
		
		return $contributor;
	}
	
	private static function getUserName($cTag, $xpath) {
		$userName = null;
		
		$nodelist = $xpath->query("newsMessage:name", $cTag);
		
		if($nodelist->length == 0) {
			$nodelist = $xpath->query("@literal", $cTag);
		}
		
		foreach($nodelist as $node) {
			$userName = $node->nodeValue;
		}
		
		return $userName;
	}
	
	private static function getUserDescription($cTag, $xpath) {
		$description = null;
		
		$nodelist = $xpath->query("@role", $cTag);
		
		foreach($nodelist as $node) {
			$description = $node->nodeValue;
		}
		
		return $description;
	}
	
	private static function getUserQcode($cTag, $xpath) {
		$qcode = null;
		
		$nodelist = $xpath->query("@qcode", $cTag);
		
		foreach($nodelist as $node) {
			$qcode = $node->nodeValue;
		}
		
		return $qcode;
	}
	
	private static function getUserUri($cTag, $xpath) {
		$uri = null;
		
		$nodelist = $xpath->query("@uri", $cTag);
		
		foreach($nodelist as $node) {
			$uri = $node->nodeValue;
		}
		
		return $uri;
	}
		
		
	
	/*A method used to find the subjects in the NewsML article
	  Returns: array containing subjects, empty array if no subject is found
	  Parameters: 
		- $newsItem: the result of the query made at the start of the document to separate diferent newsItems
		- $xpath: the XPath variable neded to peform a query on a XML document
	*/
	private static function getMetaSubjects($newsItem, $xpath) {
		$subjects = array ( );
		
		/*Query path that continus from first query at the start of the document.
		  Path without XML namespace: contentMeta/subject/name
		*/
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:subject/newsMessage:name", $newsItem);
		
		//Pushes the subjects found in the query on to the end of the array
		foreach($nodelist as $node) {
			array_push($subjects, $node->nodeValue);
		}

		
		//If noe subject where found in the first query enter this part of the code
		if(count($subjects) == 0) {
		
			/*Query path that continus from first query at the start of the document.
			  Path without XML namespace: contentMeta/subject/@qcode (fetching qcode forom the subject tag)
			*/
			$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:subject/@qcode", $newsItem);
			
			/*pushes the value of the subject on the end of the array if the value matches the regula expresion
			  Exsamples that will pas the regex: subj:11000000, subj:10000000, subj:04007000
			*/
			if(preg_match("/subj:[0-9]{8}/", $node->nodeValue)) {
				array_push($subjects, $node->nodeValue);
			}
		}
		
		return $subjects;
	}
	
	/**
	 * Checks if some of the parts of the data being sendt to Wordpress is mising and setting status code acordingly
	 *
	 * Checks first if 'status_code' in $returnArray is set to something diferent then 200 and returns that number if it dose.
	 * Checks then if any of the more inportant parts of the meta and post arrays are missing, and if the are returning 400.
	 * The metod returns 200 if everthing is OK
	 *
	 * @param array $returnArray The array containg 'status_code'
	 * @param array $newsItemArray The array holding all the data that are to be checked
	 * @return int 200 if all OK, 400 if something is missing and 'status_code' value if not 200
	 * @author Petter Lundberg Olsen
	 */
	private static function setStatusCode($returnArray, $newsItemArray) {
		if($returnArray['status_code'] != 200) {
			return $returnArray['status_code'];
		}
		
		//Cheking if the content is missing
		if($newsItemArray['post']['post_content'] === null) {
			return 400;
		}
		
		//Cheking if the headline is missing
		if($newsItemArray['post']['post_title'] === null) {
			return 400;
		}
		
		//Cheking if the guid is missing
		if($newsItemArray['meta']['nml2_guid'] === null) {
			return 400;
		}
		
		//Cheking if the version number is missing
		if($newsItemArray['meta']['nml2_version'] === null) {
			return 400;
		}

		return 200;
	} 

	/**
	 * A metod that return ether 'publish' or 'future' depending in embargo.
	 * 
	 * This metod is used to change the 'post_status' in the $post array. Returns if 'publish' if
	 * $embargo is set. Returns 'future' if $embargo is null.
	 * 
	 * @param string $embargo Embargo date as string, may be null
	 * @reutrn string 'publish' or 'future'
	 * @author Petter Lundberg Olsen
	 */
	public static function setEbargoState($embargo) {
		if($embargo === null) {
			return 'publish';
		}
		return 'future';
		
	}
	
}
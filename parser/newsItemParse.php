<?php

/**
 * Class used to parse newsItems
 *
 * This class parses newsItems in NewsML-G2 using DOMXPath. It den send them to a RESTApi
 *
 * @author Petter Lundberg Olsen
 */
class NewsItemParse {
	
	/*Array structore of $returnArray that are sendt to the RESTApi:
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
								'nml2_copyrightHolder' 	=> string
								'nml2_copyrightNotice' 	=> string
							  );
					'users' => $users = array(
								0 => user = array(
										'user_login' 	=> string
										'description'	=> string
										'user_email'	=> string
										'nml2_qcode'	=> string
										'nml2_uri'		=> string
									 );
								1 => user = array(
										'user_login' 	=> string
										'description'	=> string
										'user_email'	=> string
										'nml2_qcode'	=> string
										'nml2_uri'		=> string
									 );
								2 => Same as the indexes above. Number of indexes depends on number of creators and contributors
									 The first index is always the creator, and all other the contributors
					'subjects' => $subjects = array(
									0 => subject = array(
											'qcode'  => string
											'name' 	 => $nameArray = array(
														0 => name = array(
																'text' => string
																'lang' => string
																'role' => string
															 );
														1 => Same as the index above. Number of indexes depends on number of names
														);
											'type' 	 => string
											'uri' 	 => string
											'sameAs' => $sameAsArray = array(
														0 => sameAs = array(
															'qcode'  => string
															'name' 	 => $nameArray = array(
																		0 => name = array(
																			'text' => string
																			'lang' => string
																			'role' => string
																			 );
																1 => Same as the index above. Number of indexes depends on number of names
																		);
															'type' 	 => string
															'uri' 	 => string
															);
														1 => same as the index above. Number of indexes depends on number of sameAs tags under a subjects
														);
											'broader' => $broaderArray = array(
														  0 => broader = array(
																'qcode'  => string
																'name' 	 => $nameArray = array(
																		0 => name = array(
																			'text' => string
																			'lang' => string
																			'role' => string
																			 );
																		1 => Same as the index above. Number of indexes depends on number of names
																			);
																'type' 	 => string
																'uri' 	 => string
																);
														  1 => same as the index above. Number of indexes depends on number of broader tags under a subjects
														  );
										);
									1 => same as the index above. Number of indexes depends on number of subjects
								    );
					'photo' => $photos = array(
								0 => $photo = array(
										'href' 			=> string
										'size' 			=> string
										'width' 		=> string
										'height' 	  	=> string
										'contenttype' 	=> string
										'colourspace' 	=> string
										'rendition' 	=> string
										'description' 	=> string
									 );
								1 => same as index above. Number of indexes depends on number of photos
								);
				 );
			1 => Same as index 0. This is index, index 0 and all numbers above is added whit array_push
				 and the index numbers used is decided by the numbers of newsItems
		);
	*/		
	
	//A variable holding the namespace of the xml file
	//Automatic set to empty string and changed if xml has a namespace in its outermost tag
	private static $ns = "";
	private static $photoFound = false;
	
	/**
	 * Creates and returns the array structure that are sent to the RESTApi
     *
	 * This method is the main method of the NewsItemParse file. It creates the DOMXpath object used to query information from a NewsML-G2 document.
	 * The array congaing all the information from the NewsML document are created in this method.
	 *
	 * @param $file, raw XML or XML file
	 * @return array
	 * @author Petter Lundberg Olsen
	 */
	public static function createPost($file){
		global $ns;
		global $photoFound;
		$doc = new DOMDocument();
		
		//Checks if $file is raw XML or a XML file and uses the correct load operation
		if(is_file($file)) {
			$doc->load($file);
		}
		else {
			$doc->loadXML($file);
		}
		
		//Finds the namespace of the outermost tag in the xml file
		$uri = $doc->documentElement->lookupnamespaceURI(NULL);

		
        $xpath = new DOMXPath($doc);
		
		//XML namescpaces
        $xpath->registerNamespace('html', "http://www.w3.org/1999/xhtml");
		$xpath->registerNamespace('nitf', "http://iptc.org/std/NITF/2006-10-18/");
		
		//Test to see if $uri if not equal to ""
		if(strcmp("",$uri) != 0) {
			$xpath->registerNamespace("docNamespace", $uri);
			$ns = "docNamespace:";
		}
		
		/*Query to separate the different newsItems in a newsMessage
		  This query will find the absolute path (without XML namespaces): newsMessage/itemSet/newsItem
		*/
		$newsItemList = $xpath->query("//".$ns."newsItem");
		
		$returnArray = array( 
			'status_code' => 200 //int, the status code automatically set to 200
		);
		
		//Files the given array for each newsItem
		foreach($newsItemList as $newsItem) {
			$newsItemArray = array(
				'post'  	=> self::createPostArray($newsItem, $xpath), //array
				'meta'  	=> self::createMetaArray($newsItem, $xpath), //array
				'users' 	=> self::createUserArray($newsItem, $xpath), //array
				'subjects' 	=> self::createSubjectArray($newsItem, $xpath), //array
				'photo' 	=> array( )
			);
			
			$returnArray = self::createPhotoArray($newsItem, $xpath, $returnArray);
			
			//Checking if there is any errors in the data gathered from the newsML document and changes status code accordingly
			$returnArray['status_code'] = self::setStatusCode($returnArray, $newsItemArray);
			
			if(strcmp($newsItemArray['post']['post_status'], 'publish') == 0) {
				//Checking if an embargo date is present and changes 'post_status' accordingly
				$newsItemArray['post']['post_status'] = self::setEbargoState($newsItemArray['meta']['nml2_embarogDate']);
			}
			
			//Adds the information found in the newsItem to the array that will be sent to the RESTApi
			if(!$photoFound) {
				array_push($returnArray, $newsItemArray);
				$photoFound = false;
			}
		}
		
		return $returnArray;	
	}
	
	/**
	 * Creates and returns the array containing the post
	 *
	 * This method creates and returns an array congaing the post that are sendt to the Wordpress database. The way to array is structured is
	 * given by Worpdress to be able to use the wp_inser_post method, see http://codex.wordpress.org/Function_Reference/wp_insert_post
	 * for more information on the post array
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that a new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array
	 * @author Petter Lundberg Olsen
	 */
	private static function createPostArray($newsItem, $xpath) {
        $post = array(
            'post_content'   => self::getPostContent($newsItem, $xpath), // The full text of the post.
            'post_name'      => self::getPostName($newsItem, $xpath), // The name (slug) for your post
            'post_title'     => self::getPostHeadline($newsItem, $xpath), // The title of your post.
            'post_status'  	 => self::setPostStatus($newsItem, $xpath), //[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
            'tags_input'     => self::getPostTags($newsItem, $xpath) // Default empty.
        );
		
		return $post;
	}
	
	/**
	 * Creates and files the metadata array
	 *
	 * This method creates and files the array containing the metadata of a newsMessage
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that a new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array
	 * @author Petter Lundberg Olsen
	 */
	private static function createMetaArray($newsItem, $xpath) {
		$meta = array(
			'nml2_guid' 		  	=> self::getMetaGuid($newsItem, $xpath), //string, the guide of the newsItem
			'nml2_version' 		  	=> self::getMetaVersion($newsItem, $xpath), //string, the version of the newsItem
			'nml2_firstCreated'   	=> self::getMetaFirstCreated($newsItem, $xpath), //string, the timesap when the newsItem was first created
			'nml2_versionCreated' 	=> self::getMetaVersionCreated($newsItem, $xpath), //string, the timestamp when the current version of the newsItem was created
			'nml2_embarogDate' 	  	=> self::getMetaEmbargo($newsItem, $xpath), //string, timestamp of the embargo
			'nml2_newsMessageSendt' => self::getMetaSentDate($xpath), //string, timestamp from when the newsMessage where sent
			'nml2_language'			=> self::getMetaLanguage($newsItem, $xpath), //string, the language of the content in the newsItem
			'nml2_copyrightHolder' 	=> self::getMetaCopyrightHolder($newsItem, $xpath),
			'nml2_copyrightNotice' 	=> self::getMetaCopyrightNotice($newsItem, $xpath),
		);
		
		return $meta;
	}
	
	/**
	 * Creates an array containing all users
	 *
	 * This method uses a DOMXPath query to find and return an array containing the creator and all contributors in a newsItem.
	 * The first entry in the array is always the creator, and the rest is the contributors 
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that a new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array
	 * @author Petter Lundberg Olsen
	 */
	private static function createUserArray($newsItem, $xpath) {
		global $ns;
		$users = array( );
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: contentMeta/creator
		*/
		$nodelist = $xpath->query($ns."contentMeta/".$ns."creator", $newsItem);
		
		//Creates the creator of the news article
		foreach($nodelist as $node) {
			$user = array(
				'user_login' 	=> self::getUserName($node, $xpath), //string login_name of the user
				'description'	=> self::getUserDescription($node, $xpath), //string, describing the role of the user
				'user_email'	=> self::getUserEmail($node, $xpath),
				'nml2_qcode'	=> self::getUserQcode($node, $xpath), //string, the users NewsML-G2 qcode
				'nml2_uri'		=> self::getUserUri($node, $xpath) //string, the users NewsML-G2 uri
			);
			
			array_push($users, $user);
		}
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: contentMeta/contributor
		*/
		$nodelist = $xpath->query($ns."contentMeta/".$ns."contributor", $newsItem);
		
		//Create the contributors of the news article
		foreach($nodelist as $node) {
			$user = array(
				'user_login' 	=> self::getUserName($node, $xpath), //string login_name of the user
				'description'	=> self::getUserDescription($node, $xpath), //string, describing the role of the user
				'user_email'	=> self::getUserEmail($node, $xpath), //string, the email of the user 
				'nml2_qude'		=> self::getUserQcode($node, $xpath), //string, the users NewsML-G2 qcode
				'nml2_uri'		=> self::getUserUri($node, $xpath) //string, the users NewsML-G2 uri
			);
			
			array_push($users, $user);
		}
		
		return $users;
	}
	
	/**
	 * Creates and returns an array congaing subjects
	 *
	 * This method uses a DOMXPath query to find all subjects in a newsItem and return them as an array
	 *
	 * @param DOMNode $newsItem XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array contaning all subjects
	 * @author Petter Lundberg Olsen
	 */
	private static function createSubjectArray($newsItem, $xpath) {
		global $ns;
		$subjects = array( );
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: contentMeta/subject
		*/
		$nodelist = $xpath->query($ns."contentMeta/".$ns."subject", $newsItem);
		
		//This loop creates an array cantoning information about each subject
		foreach($nodelist as $node) {
			$subject = array(
				'qcode'   => self::getSubjectQcode($node, $xpath), //string, the qcode of the subject
				'name' 	  => self::getSubjectName($node, $xpath), //array, an array containing name and its attributes
				'type' 	  => self::getSubjectType($node, $xpath), //string, the type of subject
				'uri' 	  => self::getSubjectUri($node, $xpath), //string, subject uri
				'sameAs'  => self::getSubjectSameAs($node, $xpath), //array, an array containing all subjects sameAs tags
				'broader' => self::getSubjectBroader($node, $xpath) //array, an array containing all subjects broader tags
			);
			
			array_push($subjects, $subject);
		}
		
		return $subjects;
	}
	
	/**
	 * Creates and returns an array congaing all photos
	 *
	 * This method uses DOMXPath to find all photos in a news message and returns them in an array
	 *
	 * @param DOMNode $newsItem XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array contaning all subjects
	 * @author Petter Lundberg Olsen
	 */
	private static function createPhotoArray($newsItem, $xpath, $returnArray) {
		global $ns;
		global $photoFound;
		$photos = array( );
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: contentSet/remoteContent
		*/
		$nodelist = $xpath->query($ns."contentSet/".$ns."remoteContent", $newsItem);

		
		//This loop creates an array containing information about each photo
		foreach($nodelist as $node) {
			$guid = self::getPhotoTextGuid($newsItem, $xpath);
			for($i = 0; $i < count($returnArray); $i++) {
				if(strcmp($returnArray[$i]['meta']['nml2_guid'], $guid) == 0) {
					$photo = array( 
					'href' 		  => self::getPhotoHref($node, $xpath), //string, the source of the image
					'size' 		  => self::getPhotoSize($node, $xpath), //string, the size of the image in bytes 
					'width' 	  => self::getPhotoWidth($node, $xpath), //string, the width of the picture in px
					'height' 	  => self::getPhotoHeight($node, $xpath), //string, the height of the image
					'contenttype' => self::getPhotoContenttype($node, $xpath), //string, what type of file the image is
					'colourspace' => self::getPhotoColourspace($node, $xpath), //string, what colorspace the image is
					'rendition'   => self::getPhotoRendition($node, $xpath), //string, tells if the image is higres, meant for web, or is a thumbnail
					'description' => self::getPhotoDescription($newsItem, $xpath)
					);
			
					array_push($returnArray[$i]['photo'], $photo); 
					$photoFound = true;
				}
			}
			
			
		}
		
		return $returnArray;
	}
	
	/**
	 * Finds and returns content of	 a newsItem
	 *
	 * This method uses a DOMXPath query to find and return the main content of a news article in a given newsItem
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string content, null if no content present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPostContent($newsItem, $xpath) {
		global $ns;
		$content = null;
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: contentSet/inlineXML/html/body/article/div it will only choose the div whit a itemprop attribute = articleBody
		*/
		$nodelist = $xpath->query($ns."contentSet/".$ns."inlineXML/html:html/html:body/html:article/html:div[@itemprop='articleBody']", $newsItem);
		
		if($nodelist->length == 0) {
			/*Trying this query if the above query  gives no result.
			Query path that continues from first query at the start of the document.
			Path without XML namespace: contentSet/inlineXML/html/body
			*/  

			$nodelist = $xpath->query($ns."contentSet/".$ns."inlineXML/html:html/html:body", $newsItem);

			if($nodelist->length == 0) {
				
				/*Trying this query if the above query  gives no result.
				  Query path that continues from first query at the start of the document.
				  Path without XML namespace: contentSet/inlineXML/nitf/body/body.content
				*/  
				$nodelist = $xpath->query($ns."contentSet/".$ns."inlineXML/nitf:nitf/nitf:body/nitf:body.content", $newsItem);
				
				if($nodelist->length == 0) {
				
					/*Trying this query if the above query  gives no result.
					  Query path that continues from first query at the start of the document.
					  Path without XML namespace: contentSet/inlineData
					*/  
					
					$nodelist = $xpath->query($ns."contentSet/".$ns."inlineData", $newsItem);
				}
			}
		}
		
		
		/*Sets the results of the query above on the return variable if any
		  The length of the $nodelist should only be 1 if the newsML is created correctly
		*/
		foreach($nodelist as $node) {
            $content = self::get_inner_html($node);
        }

        return $content;
	}
	
	/**
	 * Find ans return headline
	 *
	 * This method uses a DOMXPath query to find and return the headline of a newsItem
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string headline, null if no headline present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPostHeadline($newsItem, $xpath) {
		global $ns;
		$headline = null;
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: contentMeta/headline
		*/
		$nodelist = $xpath->query($ns."contentMeta/".$ns."headline", $newsItem);
		
		if($nodelist->length == 0) {
		
			/*Trying this query if the above query  gives no result
			  Query path that continues from first query at the start of the document.
			  Path without XML namespace: contentSet/inlineXML/html/head/title
			*/  
			$nodelist = $xpath->query($ns."contentSet/".$ns."inlineXML/html:html/html:head/html:title", $newsItem);
			
			if($nodelist->length == 0) {
				
				/*Trying this query if the above query  gives no result
				  Query path that continues from first query at the start of the document.
				  Path without XML namespace: contentSet/inlineXML/html/head/title
				*/  
				$nodelist = $xpath->query($ns."contentSet/".$ns."inlineXML/nitf:nitf//nitf:body/nitf:body.head/nitf:hedline", $newsItem);
			}
		}
		
		/*Sets the results of the query above on the return variable if any
		  The length of the $nodelist should only be 1 if the newsML is created correctly
		*/
		foreach($nodelist as $node) {
			$headline = $node->nodeValue;
		}

		return $headline;
	}
	
	/**
	 * Finds and returns slugline
	 *
	 * This method uses a DOMXPath query to find and return the slugline of a newsItem
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string slugline, null if no slugline present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPostName($newsItem, $xpath) {
		global $ns;
		$name = null;
		
		/*Query path that continues from first query at the start of the document
		  Path without XML namespace: contentMeta/slugline
		*/
		$nodelist = $xpath->query($ns."contentMeta/".$ns."slugline", $newsItem);
		
		/*Sets the results of the query above on the return variable if any
		  The length of the $nodelist should only be 1 if the newsML is created correctly
		*/
		foreach($nodelist as $node) {
			$name = $node->nodeValue;
		}
		
		return $name;
	}
	
	/**
	 * Find and returns the keyword of a newsItem
	 *
	 * This method uses a DOMEXPath query to find and return the keyword given in a newsItem. The keywords are on the form: '<keyword>,<keyword>,...'
	 * This form is needed to use the keywords as tags in the Wordpress database
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string tags, null if no tags present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPostTags($newsItem, $xpath) {
		global $ns;
		$tags = null;
		
		/*Query path that continues from first query at the start of the document
		  Path without XML namespace: contentMeta/keyword
		*/
		$nodelist = $xpath->query($ns."contentMeta/".$ns."keyword", $newsItem);
		
		/*Sets the results of the query above on the return variable if any
		  Result of this loop should lock like: '<keyword>,<keyword>,...'
		*/
		foreach($nodelist as $node) {
			$tags .= $node->nodeValue . ",";
		}
		
		return $tags;
	}
	
	/**
	 * Finds and returns guid
	 *
	 * This method uses a DOMXPath query to find and return the guid of a newsItem
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string guid, null if no guid present
	 * @author Petter Lundberg Olsen
	 */
	private static function getMetaGuid($newsItem, $xpath) {
		$guid = null;
		
		/*Query path that continues from first query at the start of the document
		  Path without XML namespace: @guid (find the guid attribute in the newsItem tag)
		*/
		$nodelist = $xpath->query("@guid", $newsItem);
		
		/*Sets the results of the query above on the return variable if any
		  The length of the $nodelist should only be 1 if the newsML is created correctly
		*/
		foreach($nodelist as $node) {
			$guid = $node->nodeValue;
		}
		
		return $guid;
	}
	
	/**
	 * Finds and returns the version number
	 *
	 * This method user DOMEXPath query to find and return the version number of the newsItem given in a NewsML-G2 document
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string version number, null if no version present
	 * @author Petter Lundberg Olsen
	 */
	private static function getMetaVersion($newsItem, $xpath) {
		$version = null;
		
		/*Query path that continues from first query at the start of the document
		  Path without XML namespace: @version (find the version attribute in the newsItem tag)
		*/
		$nodelist = $xpath->query("@version", $newsItem);
		
		/*Sets the results of the query above on the return variable if any
		  The length of the $nodelist should only be 1 if the newsML is created correctly
		*/
		foreach($nodelist as $node) {
			$version = $node->nodeValue;
		}
		
		return $version;
	}

	/**
	 * Finds and returns a timestamp from when the news article was first created
	 *
	 * This method uses a DOMXPath query to find and return a timestamp from when the first version of the newsItem where created
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string first created timestamp, null if no timestamp is present
	 * @author Petter Lundberg Olsen
	 */
	private static function getMetaFirstCreated($newsItem, $xpath) {
		global $ns;
		$firstCreated = null;
		
		/*Query path that continues from first query at the start of the document
		  Path without XML namespace: itemMeta/firstCreated
		*/
		$nodelist = $xpath->query($ns."itemMeta/".$ns."firstCreated", $newsItem);
		
		/*Sets the results of the query above on the return variable if any	
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$firstCreated = $node->nodeValue;
		}
		
		return $firstCreated;
	}
	
	/**
	 * Finds and returns a timestamp from when the present version was created
	 *
	 * This method uses a DOMXPath query to find and return a timestamp from when the current version of the newsItem where created
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string version created timestamp, null if no timestamp is present
	 * @author Petter Lundberg Olsen
	 */
	private static function getMetaVersionCreated($newsItem, $xpath) {
		global $ns;
		$versionCreated = null;
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: itemMeta/versionCreated
		*/
		$nodelist = $xpath->query($ns."itemMeta/".$ns."versionCreated", $newsItem);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$versionCreated = $node->nodeValue;
		}
		
		return $versionCreated;
	}
	
	/**
	 * Finds and returns the embargo if present
	 *
	 * This method user DOMXPath query to find the embargo date of a NewsML-G2 Document and returns it as a string
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string embargo date, null if no embargo is present
	 * @author Petter Lundberg Olsen
	 */
	private static function getMetaEmbargo($newsItem, $xpath) {
		global $ns;
		$embargo = null;
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: itemMeta/embargoed
		*/
		$nodelist = $xpath->query($ns."itemMeta/".$ns."embargoed", $newsItem);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$embargo = $node->nodeValue;
		}
		
		return $embargo;
	}

	/**
	 * Finds and returns the sent date from the NewsML document
	 *
	 * This method finds the <sent> tag in NewsML-G2 and returns it as a string. It uses DOMEXpath
	 * find the tag
	 *
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string date sent timestamp, null if no date is present
	 * @author Petter Lundberg Olsen
	 */ 
	private static function getMetaSentDate($xpath) {
		global $ns;
		$dateSent = null;
		
		//Path without XML namespace: newsMessage/header/sent
		$nodelist = $xpath->query("//".$ns."newsMessage/".$ns."header/".$ns."sent");
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$dateSent = $node->nodeValue;
		}
		
		return $dateSent;
	}
	
	/**
	 * Finds and returns the language of the news article
	 *
	 * This method finds the language of the content in a NewsML-G2 document using DOMXPath and returns it as a string
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string The language of the news article, null if no language present
	 * @author Petter Lundberg Olsen
	 */
	private static function getMetaLanguage($newsItem, $xpath) {
		global $ns;
		$language = null;
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: contentMeta/language/tag-attribute
		*/
		$nodelist = $xpath->query($ns."contentMeta/".$ns."language/@tag", $newsItem);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$language = $node->nodeValue;
		}
		
		return $language;
	}
	
	/**
	 * Finds and returns the copyright holder of the newsItem
	 *
	 * This method finds the copyright holder of the content in a NewsML-G2 document using DOMXPath and returns it as a string
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string The copyright holder of the news article, null if no copyright present
	 * @author Petter Lundberg Olsen
	 */
	private static function getMetaCopyrightHolder($newsItem, $xpath) {
		global $ns;
		$copyrightHolder = null;
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: rightsInfo/copyrightHolder/name
		*/
		$nodelist = $xpath->query($ns."rightsInfo/".$ns."copyrightHolder/".$ns."name", $newsItem);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$copyrightHolder = $node->nodeValue;
		}
		
		return $copyrightHolder;
	}
	
	/**
	 * Finds and returns the copyright notice of the newsItem
	 *
	 * This method finds the copyright notice of the content in a NewsML-G2 document using DOMXPath and returns it as a string
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string The copyright notice of the news article, null if no copyright present
	 * @author Petter Lundberg Olsen
	 */
	private static function getMetaCopyrightNotice($newsItem, $xpath) {
		global $ns;
		$copyrightNotice = null;
		
		/*Query path that continues from first query at the start of the document.
		  Path without XML namespace: rightsInfo/copyrightNotice
		*/
		$nodelist = $xpath->query($ns."rightsInfo/".$ns."copyrightNotice", $newsItem);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$copyrightNotice = $node->nodeValue;
		}
		
		return $copyrightNotice;
	}

	/**
	 * Find and returns the name of a creator/contributor
	 *
	 * This method uses a DOMXPath query to find and return the name of creator/contributor
	 *
	 * @param DOMNode $cTag XPath query result congaing one creator/contributor that is used in a sub-query in this method
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string name, null if no name present
	 * @author Petter Lundberg Olsen
	 */
	private static function getUserName($cTag, $xpath) {
		global $ns;
		$userName = null;
		
		/*Query path that continues from the query in function getCreator/getContributor
		  Path without XML namespace: name
		*/
		$nodelist = $xpath->query($ns."name", $cTag);
		
		//If noe name tag is present, enter this part of the code
		if($nodelist->length == 0) {
		
			/*Query path that continues from the query in function getCreator/getContributor
			  Path without XML namespace: literal-attribute
			*/
			$nodelist = $xpath->query("@literal", $cTag);
		}
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$userName = $node->nodeValue;
		}
		
		return $userName;
	}
	
	/**
	 * Find and returns the role of a creator/contributor
	 *
	 * This method uses a DOMXPath query to find and return the role of creator/contributor
	 *
	 * @param DOMNode $cTag XPath query result congaing one creator/contributor that is used in a sub-query in this method
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string role, null if no role present
	 * @author Petter Lundberg Olsen
	 */
	private static function getUserDescription($cTag, $xpath) {
		$description = null;
		
		/*Query path that continues from the query in function getCreator/getContributor
		  Path without XML namespace: role-attribute
		*/
		$nodelist = $xpath->query("@role", $cTag);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$description = $node->nodeValue;
		}
		
		return $description;
	}
	
	/**
	 * Finds and retruns an user email
	 *
	 * This method uses a DOMXPath query to find and return the email of creator/contributor
	 *
	 * @param DOMNode $cTag XPath query result congaing one creator/contributor that is used in a sub-query in this method
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string email, null if no email present
	 * @author Petter Lundberg Olsen
	 */
	private static function getUserEmail($cTag, $xpath) {
		global $ns;
		$email = null;
		
		$nodelist = $xpath->query($ns."personDetails/".$ns."contactInfo/".$ns."email", $cTag);
		
		foreach($nodelist as $node) {
			$email = $node->nodeValue;
		}
		
		return $email;
	}
	
	/**
	 * Find and returns the qcode of a creator/contributor
	 *
	 * This method uses a DOMXPath query to find and return the qcode of creator/contributor
	 *
	 * @param DOMNode $cTag XPath query result congaing one creator/contributor that is used in a sub-query in this method
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string qcode, null if no qcode present
	 * @author Petter Lundberg Olsen
	 */
	private static function getUserQcode($cTag, $xpath) {
		$qcode = null;
		
		/*Query path that continus from the query in function getCreator/getContributor
		  Path without XML namespace: qcode-attribute
		*/
		$nodelist = $xpath->query("@qcode", $cTag);
		
		/*Sets the results of the query above on the return variable if anny.
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$qcode = $node->nodeValue;
		}
		
		return $qcode;
	}
	
	/**
	 * Find and returns the uri of a creator/contributor
	 *
	 * This method uses a DOMXPath query to find and return the uri of creator/contributor
	 *
	 * @param DOMNode $cTag XPath query result contains one creator/contributor that is used in a sub-query in this method
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string uri, null if no uri present
	 * @author Petter Lundberg Olsen
	 */
	private static function getUserUri($cTag, $xpath) {
		$uri = null;
		
		/*Query path that continus from the query in function getCreator/getContributor
		  Path without XML namespace: uri-attribute
		*/
		$nodelist = $xpath->query("@uri", $cTag);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$uri = $node->nodeValue;
		}
		
		return $uri;
	}
	
	/**
	 * Creates and returns an array containing subjects
	 *
	 * This method uses a DOMXPath query to find all sameAs tags in a subject and return them as an array
	 *
	 * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array containing all subjects
	 * @author Petter Lundberg Olsen
	 */
	private static function getSubjectSameAs($subjectTag, $xpath) {
		global $ns;
		$sameAsArray = array( );
		
		/*This XPath query is a subquery from the query in the method createSubjectArray
		  Path without XML namespace: sameAs
		*/
		$nodelist = $xpath->query($ns."sameAs", $subjectTag);
		
		//This loop creates an array containing information about each subject
		foreach($nodelist as $node) {
			$sameAs = array(
				'qcode' => self::getSubjectQcode($node, $xpath), //string, the qcode of the subject
				'name'  => self::getSubjectName($node, $xpath), //array, an array containing name and its attributes
				'type'  => self::getSubjectType($node, $xpath), //string, the type of subject
				'uri'   => self::getSubjectUri($node, $xpath) //string, subject uri
			);
			 
			array_push($sameAsArray, $sameAs);
		}
		
		return $sameAsArray;
	}
	
	/**
	 * Creates and returns an array containing subjects
	 *
	 * This method uses a DOMXPath query to find all sameAs tags in a subject and return them as an array
	 *
	 * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array containing all subjects
	 * @author Petter Lundberg Olsen
	 */ 	
	private static function getSubjectBroader($subjectTag, $xpath) {
		global $ns;
		$broaderArray = array( );
		
		$nodelist = $xpath->query($ns."broader", $subjectTag);

		foreach($nodelist as $node) {
			$broader = array(
				'qcode' => self::getSubjectQcode($node, $xpath), //string, the qcode of the subject
				'name'  => self::getSubjectName($node, $xpath), //array, an array containig name and its attributes
				'type'  => self::getSubjectType($node, $xpath), //string, the type of subject
				'uri'   => self::getSubjectUri($node, $xpath) //string, subject uri
			);
			
			array_push($broaderArray, $broader);
		}
		
		return $broaderArray;
	}
	
	/**
	 * Finds and returns a subjects qcode
	 *
	 * This method uses a DOMXPath query to find and return a subjects qcode
	 *
	 * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string qcode, null if no qcode present
	 * @author Petter Lundberg Olsen
	 */
	private static function getSubjectQcode($subjectTag, $xpath) {
		$qcode = null;
		
		/*This XPath query is a subquery from the query in the method createSubjectArray/createSubjectSameAsArray
		  Path without XML namespace: qcode-attribute
		*/
		$nodelist = $xpath->query("@qcode", $subjectTag);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$qcode = $node->nodeValue;
		}
		
		return $qcode;
	}
	
	
	/**
	 * Find and returns an array containing name and other data
	 *
	 * This metod uses a DOMEXPath query to find a subjects name and put it and other data about it in an array
	 * that are being added in the array of names
	 *
	 * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array containing name arrays
	 * @author Petter Lundberg Olsen
	 */
	private static function getSubjectName($subjectTag, $xpath) {
		global $ns;
		$nameArray = array( );
		
		/*This XPath query is a subquery from the query in the method createSubjectArray/createSubjectSameAsArray
		  Path without XML namespace: name
		*/
		$nodelist = $xpath->query($ns."name", $subjectTag);
		
		//This loop creates the name arrays and storing there information
		foreach($nodelist as $node) {
			$name = array(
				'text' => $node->nodeValue,
				'lang' => self::getSubjectLang($node, $xpath),
				'role' => self::getSubjectRole($node, $xpath)
			);
			
			array_push($nameArray, $name);
		}
		
		return $nameArray;
	}
	
	/**
	 * Find and return a subject names language
	 *
	 * This method uses a DOMEXPath query to find a subjects name language and put it and other data about it in an array
	 * that are being added in the array of names
	 *
	 * @param DOMNode $nameTag XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string language, null if no language is present
	 * @author Petter Lundberg Olsen
	 */
	private static function getSubjectLang($nameTag, $xpath) {
		$lang = null;
		
		/*This XPath query is a subquery from the query in the method getSubjectName
		  Path without XML namespace: lang-attribute
		*/
		$nodelist = $xpath->query("@xml:lang", $nameTag);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$lang = $node->nodeValue;
		}
		
		return $lang;
	}
	
	/**
	 * Finds and returns a subject names role
	 *
	 * This method uses a DOMXPath query to find and return the role of a name tag under a subject
	 *
	 * @param DOMNode $nameTag XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string role null if no role is present
	 */
	private static function getSubjectRole($nameTag, $xpath) {
		 $role = null;
		 
		 /*This XPath query is a subquery from the query in the method getSubjectName
		  Path without XML namespace: role-attribute
		*/
		 $nodelist = $xpath->query("@role", $nameTag);
		 
		 /*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		 foreach($nodelist as $node) {
			$role = $node->nodeValue;
		}
		
		return $role;
	}
	
	/**
	 * Finds and returns a subjects type
	 *
	 * This method uses a DOMXPath query to find and return a subjects role attribute
	 *
	 * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string type, null if no type present
	 * @author Petter Lundberg Olsen
	 */
	private static function getSubjectType($subjectTag, $xpath) {
		 $type = null;
		 
		/*This XPath query is a subquery from the query in the method createSubjectArray/createSubjectSameAsArray
		  Path without XML namespace: type-attribute
		*/
		 $nodelist = $xpath->query("@type", $subjectTag);
		 
		 /*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		 foreach($nodelist as $node) {
			$type = $node->nodeValue;
		}
		
		return $type;
	}
	
	/**
	 * Finds and returns subject uri
	 *
	 * This method uses a DOMXPath query to find and return a subjects uri
	 *
	 * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string uri, null if no uri present
	 * @author Petter Lundberg Olsen
	 */
	private static function getSubjectUri($subjectTag, $xpath) {
		$uri = null;
		
		/*This XPath query is a subquery from the query in the method createSubjectArray/createSubjectSameAsArray
		  Path without XML namespace: type-attribute
		*/
		$nodelist = $xpath->query("@uri", $subjectTag);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$uri = $node->nodeValue;
		}
		
		return $uri;
	}
	
	/**
	 * Finds and returns a remoteContent href
	 *
	 * This method uses a DOMXPath query to find a remoteContent tags href attribute
	 *
	 * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string href, null if no href present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPhotoHref($remoteContent, $xpath) {
		$href = null;
		
		/*This XPath query is a subquery from the query in the method createPhotoArray
		  Path without XML namespace: href-attribute
		*/
		$nodelist = $xpath->query("@href", $remoteContent);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$href = $node->nodeValue;
		}
		
		return $href;
	}
	
	/**
	 * Finds and returns a remoteContent size
	 *
	 * This method uses a DOMXPath query to find a remoteContent tags size attribute
	 *
	 * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string size, null if no size present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPhotoSize($remoteContent, $xpath) {
		$size = null;
		
		/*This XPath query is a subquery from the query in the method createPhotoArray
		  Path without XML namespace: size-attribute
		*/
		$nodelist = $xpath->query("@size", $remoteContent);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$size = $node->nodeValue;
		}
		
		return $size;
	}
	
	/**
	 * Finds and returns a remoteContent width
	 *
	 * This method uses a DOMXPath query to find a remoteContent tags width attribute
	 *
	 * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string width, null if no width present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPhotoWidth($remoteContent, $xpath) {
		$width = null;
		
		/*This XPath query is a subquery from the query in the method createPhotoArray
		  Path without XML namespace: width-attribute
		*/
		$nodelist = $xpath->query("@width", $remoteContent);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$width = $node->nodeValue;
		}
		
		return $width;
	}
	
	/**
	 * Finds and returns a remoteContent height
	 *
	 * This method uses a DOMXPath query to find a remoteContent tags height attribute
	 *
	 * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string height, null if no height present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPhotoHeight($remoteContent, $xpath) {
		$height = null;
		
		/*This XPath query is a subquery from the query in the method createPhotoArray
		  Path without XML namespace: height-attribute
		*/
		$nodelist = $xpath->query("@height", $remoteContent);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$height = $node->nodeValue;
		}
		
		return $height;
	}
	
	/**
	 * Finds and returns a remoteContent content type
	 *
	 * This method uses a DOMXPath query to find a remoteContent tags contenttype attribute
	 *
	 * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string contenttype, null if no contenttype present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPhotoContenttype($remoteContent, $xpath) {
		$contenttype = null;
		
		/*This XPath query is a subquery from the query in the method createPhotoArray
		  Path without XML namespace: contenttype-attribute
		*/
		$nodelist = $xpath->query("@contenttype", $remoteContent);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$contenttype = $node->nodeValue;
		}
		
		return $contenttype;
	}
	
	/**
	 * Finds and returns a remoteContent colourspace
	 *
	 * This method uses a DOMXPath query to find a remoteContent tags colourspace attribute
	 *
	 * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string colourspace, null if no colourspace present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPhotoColourspace($remtoeContent, $xpath) {
		$colourspace = null;
		
		/*This XPath query is a subquery from the query in the method createPhotoArray
		  Path without XML namespace: colourspace-attribute
		*/
		$nodelist = $xpath->query("@colourspace", $remtoeContent);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$colourspace = $node->nodeValue;
		}
		
		return $colourspace;
	}
	
	/**
	 * Finds and returns a remoteContent rendition
	 *
	 * This method uses a DOMXPath query to find a remoteContent tags rendition attribute
	 *
	 * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string rendition, null if no rendition present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPhotoRendition($remoteContent, $xpath) {
		$rendition = null;
		
		/*This XPath query is a subquery from the query in the method createPhotoArray
		  Path without XML namespace: rendition-attribute
		*/
		$nodelist = $xpath->query("@rendition", $remoteContent);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$rendition = $node->nodeValue;
		}
		
		return $rendition;
	}
	
	/**
	 * Fin and returns the description of an image
	 *
	 * This method uses a DOMXPath query to find and return the description of and image
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string The description of the image, null if no description present
	 * @author Petter Lundberg Olsen
	 */
	private static function getPhotoDescription($newsItem, $xpath) {
		global $ns;	
		$description = null;
		
		/*This XPath query is a subquery from the query in the method createPhotoArray
		  Path without XML namespace: contentMeta/description
		*/
		$nodelist = $xpath->query($ns."contentMeta/".$ns."description", $newsItem);
		
		/*Sets the results of the query above on the return variable if any.
		  The length of the $nodelist should only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$description = $node->nodeValue;
		}
		
		return $description;
	}
	
	/**
	 * Checks if some of the parts of the data being sent to Wordpress is missing and setting status code accordingly
	 *
	 * Checks first if 'status_code' in $returnArray is set to something diferent then 200 and returns that number if it dose.
	 * Checks then if any of the more important parts of the meta and post arrays are missing, and if the are returning 400.
	 * The method returns 200 if everything is OK
	 *
	 * @param array $returnArray The array containing 'status_code'
	 * @param array $newsItemArray The array holding all the data that are to be checked
	 * @return int 200 if all OK, 400 if something is missing and 'status_code' value if not 200
	 * @author Petter Lundberg Olsen
	 */
	private static function setStatusCode($returnArray, $newsItemArray) {
		if($returnArray['status_code'] != 200) {
			return $returnArray['status_code'];
		} else if($newsItemArray['post']['post_content'] === null) { //Checking if the content is missing
			if(count($newsItemArray['photo']) == 0) {
				return 400;
			}
		} elseif($newsItemArray['post']['post_title'] === null) { //Checking if the headline is missing
			if(count($newsItemArray['photo']) == 0) {
				return 400;
			}
		} elseif($newsItemArray['meta']['nml2_guid'] === null) {  //Checking if the guid is missing
			return 400;
		} elseif($newsItemArray['meta']['nml2_version'] === null) { //Checking if the version number is missing
			return 400;
		} else {
			return 200;
		}
		
	} 

	/**
	 * A method that return ether 'publish' or 'future' depending in embargo.
	 * 
	 * This method is used to change the 'post_status' in the $post array. Returns if 'publish' if
	 * $embargo is set. Returns 'future' if $embargo is null.
	 * 
	 * @param string $embargo Embargo date as string, may be null
	 * @return string 'publish' or 'future'
	 * @author Petter Lundberg Olsen
	 */
	private static function setEbargoState($embargo) {
		if($embargo === null) {
			return 'publish';
		} else {
			return'future';
		}
		
	}
	
	/**
	 * Find and sets the publication status of the post
	 *
	 * This method uses a DOMXPath query to find the publication status on the NewsML-G2 document returns a valid Wordpress
	 * status depending on the pubStatus. It sends 'publish' if the status is usable, 'trash' if the status is canceled and
	 * 'pending' in all other cases.	
	 *
	 * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string 'publish', 'trash' or 'pending'
	 * @author Petter Lundberg Olsen
	 */
	private static function setPostStatus($newsItem, $xpath) {
		global $ns;
		
		$nodelist = $xpath->query($ns."itemMeta/".$ns."pubStatus/@qcode", $newsItem);
		
		foreach($nodelist as $node) {
			if(strcmp($node->nodeValue, "stat:withheld") == 0) {
				return 'pending';
			} elseif (strcmp($node->nodeValue, "stat:canceled") == 0) {
				return 'trash';
			}
		}
		
		return 'publish';
	}
	
	private static function getPhotoTextGuid($newsItem, $xpath) {
		global $ns;
		
		$pGuid = $xpath->query("@guid", $newsItem)->item(0)->nodeValue;

		$group = $xpath->query("//".$ns."group[./".$ns."itemRef/@residref = '".$pGuid."']")->item(0);

		$itemRef = $xpath->query($ns."itemRef[./".$ns."itemClass/@qcode='ninat:text']", $group)->item(0);
		
		$tGuid = $residref = $xpath->query("@residref", $itemRef)->item(0)->nodeValue;
		
		return $tGuid;
	}
	
	/**
	 * Forklarende tekst
	 *
	 * Utfylende text
	 *
	 * @param DOMNode $node
	 *
	 * Attribution:
	 * This method in gathered from a Stack Overflow when trying to solve a problem whit DOMNode stripping HTML tags
	 * Link to question: http://stackoverflow.com/questions/6286362/php-dom-get-nodevalue-html-without-stripping-tags
	 * Question authors:
	 *	Marty - http://stackoverflow.com/users/786758/marty
	 *  GetFree - http://stackoverflow.com/users/25700/getfree
	 * Answer author:
	 *  regex - http://stackoverflow.com/users/23869/regex
	 */
	function get_inner_html( $node ) { 
    $innerHTML= ''; 
    $children = $node->childNodes; 
    foreach ($children as $child) { 
        $innerHTML .= $child->ownerDocument->saveXML( $child ); 
    } 

    return $innerHTML;  }
	
}
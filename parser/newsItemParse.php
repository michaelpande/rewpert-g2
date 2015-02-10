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
							  );
					'users' => $users = array(
								'creators' 	   => $creator = array(
													0 => $user = array(
															'user_login'  => string
															'description' => string
															'nml2_qcode'	  => string
															'nml2_uri'	  => string
														  );
													1 => Same as the index above. Number of indexes dependes on number of creators
													);
								'contributors' => $contributor = array(
													0 => $user array(
															'user_login'  => string
															'description' => string
															'nml2_qcode'	  => string
															'nml2_uri'	  => string
												         );
													1 => Same as the index above. Number of indexes dependes on number of contributors
													);
							   );
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
														  1 => same as the index above. Number of indexes depends on number of broader tags under a subjects
																);
														  );
										);
									1 => same as the index above. Number of indexes depends on number of subjects
								    );
					'photo' => $photos = array(
								0 => $photo = array(
										'href' => string
										'size' => string
										'width' => string
										'height' => string
										'contenttype' => string
										'colourspace' => string
										'rendition' => string
									 );
								1 => same as indez above. Number of indexes depends on number of photos
								);
				 );
			1 => Same as index 0. This is index, index 0 and alle numbers above is added whit array_push
				 and the index numbers used is decidded by the numbers of newsItems
		);
	*/		 

	/**
	 * Creats and returns the arraystructure that are sendt to the RESTApi
     *
	 * This metod is the main metod of the NewsItemParse file. It creates the DOMXpath object used to query information from a NewsML-G2 document.
	 * The array containg all the information from the NewsML document are created in this metod.
	 *
	 * @param $file, raw XML or XML file
	 * @return array
	 * @author Petter Lundberg Olsen
	 */
	public static function createPost($file){
		$doc = new DOMDocument();
		
		//Checks if $file is raw XML or a XML file and uses the corect load operation
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
		
		//Files the given array for each newsItem
		foreach($newsItemList as $newsItem) {
			$newsItemArray = array(
				'post'  	=> NewsItemParse::createPostArray($newsItem, $xpath), //array
				'meta'  	=> NewsItemParse::createMetaArray($newsItem, $xpath), //array
				'users' 	=> NewsItemParse::createUserArray($newsItem, $xpath), //array
				'subjects' 	=> NewsItemParse::createSubjectArray($newsItem, $xpath), //array
				'photo' 	=> NewsItemParse::createPhotoArray($newsItem, $xpath) //array
			);
			
			//Cheking if there is anny errors in the data gathert from the newsML document and chenges status code accordingly
			$returnArray['status_code'] = NewsItemParse::setStatusCode($returnArray, $newsItemArray);
			
			//Cheking if an embargo date is present and changes 'post_status' accordingly
			$newsItemArray['post']['post_status'] = NewsItemParse::setEbargoState($newsItemArray['meta']['nml2_embarogDate']);
			
			//Adds the informating found in the newsItem to the array that will be sendt to the RESTApi
			array_push($returnArray, $newsItemArray);
		}
		
		return $returnArray;	
	}
	
	/**
	 * Creates and returns the array contaning the post
	 *
	 * This metod creates and returns an array containg the post that are sendt to the Wordpress database. The way to array is strucured is
	 * given by Worpdress to be able to use the wp_inser_post metod, see http://codex.wordpress.org/Function_Reference/wp_insert_post
	 * for more information on the post array
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that a new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array
	 * @author Petter Lundberg Olsen
	 */
	private static function createPostArray($newsItem, $xpath) {
        $post = array(
            'post_content'   => NewsItemParse::getPostContent($newsItem, $xpath), // The full text of the post.
            'post_name'      => NewsItemParse::getPostName($newsItem, $xpath), // The name (slug) for your post
            'post_title'     => NewsItemParse::getPostHeadline($newsItem, $xpath), // The title of your post.
            'post_status'  	 => 'publish', //[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
            'tags_input'     => NewsItemParse::getPostTags($newsItem, $xpath) // Default empty.
        );
		
		return $post;
	}
	
	/**
	 * Creates and files the metadata array
	 *
	 * This metod creates and files the array contaning the metadata of a newsMessage
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that a new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array
	 * @author Petter Lundberg Olsen
	 */
	private static function createMetaArray($newsItem, $xpath) {
		$meta = array(
			'nml2_guid' 		  	=> NewsItemParse::getMetaGuid($newsItem, $xpath), //string, the guide of the newsItem
			'nml2_version' 		  	=> NewsItemParse::getMetaVersion($newsItem, $xpath), //string, the version of the newsItem
			'nml2_firstCreated'   	=> NewsItemParse::getMetaFirstCreated($newsItem, $xpath), //string, the timesap when the newsItem was first created
			'nml2_versionCreated' 	=> NewsItemParse::getMetaVersionCreated($newsItem, $xpath), //string, the timestamp when the curent version of the newsItem var created
			'nml2_embarogDate' 	  	=> NewsItemParse::getMetaEmbargo($newsItem, $xpath), //string, timestamp of the embargo
			'nml2_newsMessageSendt' => NewsItemParse::getMetaSentDate($xpath), //string, timestamp from when the newsMessage where sendt
			'nml2_language'			=> NewsItemParse::getMetaLanguage($newsItem, $xpath), //string, the language of the content in the newsItem
		);
		
		return $meta;
	}
	
	/**
	 * Creates and returns an array contaning creators and contributors
	 *
	 * This metod creaetes and returns an array containg to inner arrays, on is a list of a newsItems creaotrs, and the other a list of its contributors
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that a new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array
	 * @author Petter Lundberg Olsen
	 */
	private static function createUserArray($newsItem, $xpath) {
		$users = array( );
		
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:creator", $newsItem);
		
		foreach($nodelist as $node) {
			$user = array(
				'user_login' 	=> NewsItemParse::getUserName($node, $xpath), //string login_name of the user
				'description'	=> NewsItemParse::getUserDescription($node, $xpath), //string, descibing the role of the user
				'user_email'	=> NewsItemParse::getUserEmail($node, $xpath),
				'nml2_qcode'	=> NewsItemParse::getUserQcode($node, $xpath), //string, the users NewsML-G2 qcode
				'nml2_uri'		=> NewsItemParse::getUserUri($node, $xpath) //string, the users NewsML-G2 uri
			);
			
			array_push($users, $user);
		}
		
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:contributor", $newsItem);
		
		foreach($nodelist as $node) {
			$user = array(
				'user_login' 	=> NewsItemParse::getUserName($node, $xpath), //string login_name of the user
				'description'	=> NewsItemParse::getUserDescription($node, $xpath), //string, descibing the role of the user
				'user_email'	=> NewsItemParse::getUserEmail($node, $xpath),
				'nml2_qude'		=> NewsItemParse::getUserQcode($node, $xpath), //string, the users NewsML-G2 qcode
				'nml2_uri'		=> NewsItemParse::getUserUri($node, $xpath) //string, the users NewsML-G2 uri
			);
			
			array_push($users, $user);
		}
		
		var_dump($users);
		
		return $users;
	}
	
	/**
	 * Creates and returns an array containg subjects
	 *
	 * This metod uses a DOMXPath query to find all subjects in a newsItem nad return them as an array
	 *
	 * @param DOMNode $newsItem XPath query from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array contaning all subjects
	 * @author Petter Lundberg Olsen
	 */
	private static function createSubjectArray($newsItem, $xpath) {
		$subjects = array( );
		
		/*Query path that continus from first query at the start of the document.
		  Path without XML namespace: contentMeta/subject
		*/
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:subject", $newsItem);
		
		//This loop creates an array contaning information about each subject
		foreach($nodelist as $node) {
			$subject = array(
				'qcode'   => NewsItemParse::getSubjectQcode($node, $xpath), //string, the qcode of the subject
				'name' 	  => NewsItemParse::getSubjectName($node, $xpath), //array, an array containig name and its attributes
				'type' 	  => NewsItemParse::getSubjectType($node, $xpath), //string, the type of subject
				'uri' 	  => NewsItemParse::getSubjectUri($node, $xpath), //string, subject uri
				'sameAs'  => NewsItemParse::getSubjectSameAs($node, $xpath), //array, an array containig all subjects sameAs tags
				'broader' => NewsItemParse::getSubjectBroader($node, $xpath) //array, an array containg all subjects broader tags
			);
			
			array_push($subjects, $subject);
		}
		
		return $subjects;
	}
	
	/**
	 * Greates and returns an array containg all photos
	 *
	 * This metod uses DOMXPath to find all photos in a news message and returns them in an array
	 *
	 * @param DOMNode $newsItem XPath query from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array contaning all subjects
	 * @author Petter Lundberg Olsen
	 */
	private static function createPhotoArray($newsItem, $xpath) {
		$photos = array( );
		
		/*Query path that continus from first query at the start of the document.
		  Path without XML namespace: contentSet/remoteContent
		*/
		$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:remoteContent", $newsItem);
		
		//This loop creates an array contaning information about each photo
		foreach($nodelist as $node) {
			$photo = array( 
				'href' 		  => NewsItemParse::getPhotoHref($node, $xpath), //string, the source of the image
				'size' 		  => NewsItemParse::getPhotoSize($node, $xpath), //string, the size of the image in bytes 
				'width' 	  => NewsItemParse::getPhotoWidth($node, $xpath), //string, the width of the picture in px
				'height' 	  => NewsItemParse::getPhotoHeight($node, $xpath), //string, the height of the image
				'contenttype' => NewsItemParse::getPhotoContenttype($node, $xpath), //string, what type of file the image is
				'colourspace' => NewsItemParse::getPhotoColourspace($node, $xpath), //stirng, what colorspace til image is
				'rendition'   => NewsItemParse::getPhotoRendition($node, $xpath) //string, tells if the image is higres, ment for web, or is a thumbnail
			);
			
			array_push($photos, $photo);
		}
		
		return $photos;
	}
	
	/**
	 * Finds and returns content of	 a newsItem
	 *
	 * This metod uses a DOMXPath query to find and return the main content of a newsarticel in a given newsItem
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string content, null if no content present
	 * @author Petter Lundberg Olsen
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
	
	/**
	 * Find ans return headline
	 *
	 * This metod uses a DOMXPath query to find and return the headline of a newsItem
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string headline, null if no headline present
	 * @author Petter Lundberg Olsen
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
	
	/**
	 * Finds and returns slugline
	 *
	 * This metod uses a DOMXPath query to find and return the slugline of a newsItem
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string slugline, null if no slugline present
	 * @author Petter Lundberg Olsen
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
	
	/**
	 * Find and returns the keyword of a newsItem
	 *
	 * This metod uses a DOMEXPath query to find and return the keyword given in a newsItem. The keywords are on the form: '<keyword>,<keyword>,...'
	 * This form is neaded to use the keywords as tags in the Wordpress database
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string tags, null if no guid present
	 * @author Petter Lundberg Olsen
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
	
	/**
	 * Findes and returns guid
	 *
	 * This metod uses a DOMXPath query to find and return the guid of a newsItem
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string guid, null if no guid present
	 * @author Petter Lundberg Olsen
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
	
	/**
	 * Finds and returns the version number
	 *
	 * This metod user DOMEXPath query to find and return the version number of the newsItem given in a NewsML-G2 document
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string version number, null if no version present
	 * @author Petter Lundberg Olsen
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

	/**
	 * Finds and returns a timestamp from when the news articel was first creaded
	 *
	 * This metod uses a DOMXPath query to find and return a timestamp from when the first version of the newsItem where created
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string first created timestamp, null if no timestamp is present
	 * @author Petter Lundberg Olsen
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
	
	/**
	 * Findes and returns a timestamp from when the present version was created
	 *
	 * This metod uses a DOMXPath query to find and return a timestamp from when the curent version of the newsItem where created
	 *
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string versioin created timestamp, null if no timestamp is present
	 * @author Petter Lundberg Olsen
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
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string embargo date, null if no embargo is present
	 * @author Petter Lundberg Olsen
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
	 * @return string date sent timestamp, null if no date is present
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
	 * @param DOMNode $newsItem XPath query result from an erlier part of the document that the new query shal be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string The language of the news articel, null if no language present
	 * @author Petter Lundberg Olsen
	 */
	private static function getMetaLanguage($newsItem, $xpath) {
		$language = null;
		
		/*Query path that continus from first query at the start of the document.
		  Path without XML namespace: contentMeta/language/tag-attribute
		*/
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:language/@tag", $newsItem);
		
		/*Sets the results of the query above on the return variable if anny.
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$language = $node->nodeValue;
		}
		
		return $language;
	}

	/**
	 * Find and returns the name of a creator/contributor
	 *
	 * This metod uses a DOMXPath query to find and return the name of creator/contributor
	 *
	 * @param DOMNode $cTag XPath query result containg one creator/contributor that is used in a sub-query in this metod
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string name, null if noe name present
	 * @author Petter Lundberg Olsen
	 */
	private static function getUserName($cTag, $xpath) {
		$userName = null;
		
		/*Query path that continus from the query in function getCreator/getContributor
		  Path without XML namespace: name
		*/
		$nodelist = $xpath->query("newsMessage:name", $cTag);
		
		//If noe name tag is present, enter this part of the code
		if($nodelist->length == 0) {
		
			/*Query path that continus from the query in function getCreator/getContributor
			  Path without XML namespace: literal-attribute
			*/
			$nodelist = $xpath->query("@literal", $cTag);
		}
		
		/*Sets the results of the query above on the return variable if anny.
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly.
		*/
		foreach($nodelist as $node) {
			$userName = $node->nodeValue;
		}
		
		return $userName;
	}
	
	/**
	 * Find and returns the role of a creator/contributor
	 *
	 * This metod uses a DOMXPath query to find and return the role of creator/contributor
	 *
	 * @param DOMNode $cTag XPath query result containg one creator/contributor that is used in a sub-query in this metod
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string role, null if noe role present
	 * @author Petter Lundberg Olsen
	 */
	private static function getUserDescription($cTag, $xpath) {
		$description = null;
		
		/*Query path that continus from the query in function getCreator/getContributor
		  Path without XML namespace: role-attribute
		*/
		$nodelist = $xpath->query("@role", $cTag);
		
		/*Sets the results of the query above on the return variable if anny.
		  The length of the $nodelist shuld only be 1 if the newsML is created correctly.
		*/ 
		foreach($nodelist as $node) {
			$description = $node->nodeValue;
		}
		
		return $description;
	}
	
	private static function getUserEmail($cTag, $xpath) {
		$email = null;
		
		$nodelist = $xpath->query("newsMessage:personDetails/newsMessage:contactInfo/newsMessage:email", $cTag);
		
		foreach($nodelist as $node) {
			$email = $node->nodeValue;
		}
		
		return $email;
	}
	
	/**
	 * Find and returns the qcode of a creator/contributor
	 *
	 * This metod uses a DOMXPath query to find and return the qcode of creator/contributor
	 *
	 * @param DOMNode $cTag XPath query result containg one creator/contributor that is used in a sub-query in this metod
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string qcode, null if noe qcode present
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
	 * This metod uses a DOMXPath query to find and return the uri of creator/contributor
	 *
	 * @param DOMNode $cTag XPath query result containg one creator/contributor that is used in a sub-query in this metod
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return string uri, null if noe uri present
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
	 * This method uses a DOMXPath query to find all subjects in a newsItem and return them as an array
	 *
	 * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
	 * @param DOMXpath $xpath Used to find information in a NewsML-G2 document
	 * @return array containing all subjects
	 * @author Petter Lundberg Olsen
	 */
	private static function getSubjectSameAs($subjectTag, $xpath) {
		$sameAsArray = array( );
		
		/*This XPath query is a subquery from the query in the method createSubjectArray
		  Path without XML namespace: sameAs
		*/
		$nodelist = $xpath->query("newsMessage:sameAs", $subjectTag);
		
		//This loop creates an array containing information about each subject
		foreach($nodelist as $node) {
			$sameAs = array(
				'qcode' => NewsItemParse::getSubjectQcode($node, $xpath), //string, the qcode of the subject
				'name'  => NewsItemParse::getSubjectName($node, $xpath), //array, an array containing name and its attributes
				'type'  => NewsItemParse::getSubjectType($node, $xpath), //string, the type of subject
				'uri'   => NewsItemParse::getSubjectUri($node, $xpath) //string, subject uri
			);
			 
			array_push($sameAsArray, $sameAs);
		}
		
		return $sameAsArray;
	}
	
	private static function getSubjectBroader($subjectTag, $xpath) {
		$broaderArray = array( );
		
		$nodelist = $xpath->query("newsMessage:broader", $subjectTag);

		foreach($nodelist as $node) {
			$broader = array(
				'qcode' => NewsItemParse::getSubjectQcode($node, $xpath), //string, the qcode of the subject
				'name'  => NewsItemParse::getSubjectName($node, $xpath), //array, an array containig name and its attributes
				'type'  => NewsItemParse::getSubjectType($node, $xpath), //string, the type of subject
				'uri'   => NewsItemParse::getSubjectUri($node, $xpath) //string, subject uri
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
		$nameArray = array( );
		
		/*This XPath query is a subquery from the query in the metod createSubjectArray/createSubjectSameAsArray
		  Path without XML namespace: name
		*/
		$nodelist = $xpath->query("newsMessage:name", $subjectTag);
		
		//This loop creates the name arrays and storing there information
		foreach($nodelist as $node) {
			$name = array(
				'text' => $node->nodeValue,
				'lang' => NewsItemParse::getSubjectLang($node, $xpath),
				'role' => NewsItemParse::getSubjectRole($node, $xpath)
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
		}
		
		//Checking if the content is missing
		if($newsItemArray['post']['post_content'] === null) {
			
			if(count($newsItemArray['photo']) == 0) {
				echo("test");
				return 400;
			}
		}
		
		//Checking if the headline is missing
		if($newsItemArray['post']['post_title'] === null) {
			if(count($newsItemArray['photo']) == 0) {
				echo("test1");
				return 400;
			}
		}
		
		//Checking if the guid is missing
		if($newsItemArray['meta']['nml2_guid'] === null) {
			echo("test2");
			return 400;
		}
		
		//Checking if the version number is missing
		if($newsItemArray['meta']['nml2_version'] === null) {
			echo("test3");
			return 400;
		}

		return 200;
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
	public static function setEbargoState($embargo) {
		if($embargo === null) {
			return 'publish';
		}
		return 'future';
		
	}
	
}
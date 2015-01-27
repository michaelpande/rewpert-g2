<?php

include('errorLogger.php');
class Parse {
	
	public static function createPost($file){
		$doc = new DOMDocument();
        $doc->loadXML($file); // This is for string not file, file is just $doc->load($file);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('html', "http://www.w3.org/1999/xhtml");
		$xpath->registerNamespace('nitf', "http://iptc.org/std/NITF/2006-10-18/");
		$xpath->registerNamespace('newsMessage', "http://iptc.org/std/nar/2006-10-01/");
		
		$newsItemList = $xpath->query("//newsMessage:newsItem");
		
		$returnArray = array( 
			'status_code' => 200
		);
		
		foreach($newsItemList as $newsItem) {
			$newsItemArray = array(
				'post' => Parse::createPostArray($newsItem, $xpath),
				'meta' => Parse::createMetaArray($newsItem, $xpath)
			);
			
			$returnArray['status_code'] = Parse::setStatusCode($returnArray, $newsItemArray);
			$newsItemArray['post']['post_status'] = Parse::setEbargoState($newsItemArray['meta']['nml2_embarogDate']);
			
			array_push($returnArray, $newsItemArray);
		}
		
		return $returnArray;	
	}
	
	private static function createPostArray($newsItem, $xpath) {
		/*
		An array containing infomation aout content, title, and other pysical infomnation
		that is containd in a NewsML-G2 document
		*/
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
	
	public static function createMetaArray($newsItem, $xpath) {
		$meta = array(
			'nml2_guid' 		  => Parse::getMetaGuid($newsItem, $xpath), //string
			'nml2_version' 		  => Parse::getMetaVersion($newsItem, $xpath),
			'nml2_firstCreated'   => Parse::getMetaFirstCreated($newsItem, $xpath),
			'nml2_versionCreated' => Parse::getMetaVersionCreated($newsItem, $xpath),
			'nml2_embarogDate' 	  => Parse::getMetaEmbargo($newsItem, $xpath)
		);
		
		return $meta;
	}
	
	private static function getPostContent($newsItem, $xpath) {
		$content = null;
		
		$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:inlineXML/html:html/html:body", $newsItem);
		
		if($nodelist->length == 0) {
			$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:inlineXML/nitf:nitf/nitf:body/nitf:body.content", $newsItem);
			if($nodelist->length == 0) {
			$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:inlineData", $newsItem);
			}
		}

		foreach($nodelist as $node) {
            $content = $node->nodeValue;
        }
		
        return $content;
	}
	
	private static function getPostHeadline($newsItem, $xpath) {
		$headline = null;
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:headline", $newsItem);
		
		if($nodelist->length == 0) {
			$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:inlineXML/html:html/html:head/html:title", $newsItem);
			
			if($nodelist->length == 0) {
				$nodelist = $xpath->query("newsMessage:contentSet/newsMessage:inlineXML/nitf:nitf/nitf:body.head/nitf:hedline", $newsItem);
			}
		}
		
		foreach($nodelist as $node) {
			$headline = $node->nodeValue;
		}

		return $headline;
	}
	
	private static function getPostName($newsItem, $xpath) {
		$name = null;
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:slugline", $newsItem);
		
		foreach($nodelist as $node) {
			$name = $node->nodeValue;
		}
		
		return $name;
	}
	
	private static function getPostTags($newsItem, $xpath) {
		$tags = null;
		$nodelist = $xpath->query("newsMessage:contentMeta/newsMessage:keyword", $newsItem);
		
		foreach($nodelist as $node) {
			$tags .= $node->nodeValue . ",";
		}
		
		return $tags;
	}
	
	private static function getMetaGuid($newsItem, $xpath) {
		$guid = null;
		$nodelist = $xpath->query("@guid", $newsItem);
		
		foreach($nodelist as $node) {
			$guid = $node->nodeValue;
		}
		
		return $guid;
	}
	
	private static function getMetaVersion($newsItem, $xpath) {
		$version = null;
		$nodelist = $xpath->query("@version", $newsItem);
		
		foreach($nodelist as $node) {
			$version = $node->nodeValue;
		}
		
		return $version;
	}
	
	/*
	Note: this function gives an unchanged string as ansver, not dateTime
	*/
	private static function getMetaFirstCreated($newsItem, $xpath) {
		$firstCreated = null;
		$nodelist = $xpath->query("newsMessage:itemMeta/newsMessage:firstCreated", $newsItem);
		
		foreach($nodelist as $node) {
			$firstCreated = $node->nodeValue;
		}
		
		return $firstCreated;
	}
	
	/*
	Note: this function gives an unchanged string as ansver, not dateTime
	*/
	private static function getMetaVersionCreated($newsItem, $xpath) {
		$versionCreated = null;
		$nodelist = $xpath->query("newsMessage:itemMeta/newsMessage:versionCreated", $newsItem);
		
		foreach($nodelist as $node) {
			$versionCreated = $node->nodeValue;
		}
		
		return $versionCreated;
	}
	
	private static function getMetaEmbargo($newsItem, $xpath) {
		$embargo = null;
		
		$nodelist = $xpath->query("newsMessage:itemMeta/newsMessage:embargoed", $newsItem);
		
		foreach($nodelist as $node) {
			$embargo = $node->nodeValue;
		}
		
		return $embargo;
		
	}
	
	public static function setStatusCode($returnArray, $newsItemArray) {
		if($returnArray['status_code'] != 200) {
			return $returnArray['status_code'];
		}
		else {
			if($newsItemArray['post']['post_content'] === null) {
				return 400;
			}
			if($newsItemArray['post']['post_title'] === null) {
				return 400;
			}
			if($newsItemArray['meta']['nml2_guid'] === null) {
				return 400;
			}
			if($newsItemArray['meta']['nml2_version'] === null) {
				return 400;
			}
		}
		return $returnArray['status_code'];
		
	} 
	
	public static function setEbargoState($embargo) {
		if($embargo === null) {
			return 'publish';
		}
		return 'future';
		
	}
	
}
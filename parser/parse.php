<?php


class Parse {





    public static function createPost($file){
		$doc = new DOMDocument();
        $doc->loadXML($file); // This is for string not file, file is just $doc->load($file);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('html', "http://www.w3.org/1999/xhtml");
		$xpath->registerNamespace('nitfns', "http://iptc.org/std/NITF/2006-10-18/");
		$xpath->registerNamespace('newsMessage', "http://iptc.org/std/nar/2006-10-01/");
		
		//Parse::getPostDate($xpath);
		
        $post = array(
            //'ID'           => [ <post id> ] // Are you updating an existing post?
            'post_content'   => Parse::getPostContent($xpath), // The full text of the post.
            'post_name'      => Parse::getPostSlug($xpath), // The name (slug) for your post
            'post_title'     => Parse::getPostHeadline($xpath), // The title of your post.
            'post_status'  	 => 'publish',//[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
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
            'post_category'  => [ array(<category id>, ...) ] // Default empty.
            'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
            'tax_input'      => [ array( <taxonomy> => <array | string> ) ] // For custom taxonomies. Default empty.
            'page_template'  => [ <string> ] // Requires name of template file, eg template.php. Default empty.*/
        );
		
		$meta = array(
			'nml2_guid' 		  => Parse::getMetaGuid($xpath),
			'nml2_version' 		  => Parse::getMetaVersion($xpath),
			'nml2_firstCreated'   => Parse::
			'nml2_versionCreated' => Parse::getMetaVersionCreated($xpath),
			'nml2_creator'		  => Parse::getMetaCreator($xpath)
		);
		
		$returnArray = array(
			'response' => "ok", //Response message placheholder, when ready update to: Parse::insertArrayCheck($array);
			'post'     => $post, // The post object created above
			'meta'     => $meta //Array whit metadata
		);
		
		return $returnArray;
    }

	
	private static function getPostContent($xpath){
		$content = "";
		$nodelist = $xpath->query("//html:p");
		
		if($nodelist->length == 0) {
			$nodelist = $xpath->query("//nitfns:p");
			if($nodelist->length == 0) {
			$nodelist = $xpath->query("//inlineData");
			}
		}

		foreach($nodelist as $node) {
            $content .= $node->nodeValue . "<br/><br/>";
        }

        return $content;
    }
	
	private static function getPostHeadline($xpath) {
		$headline = "";
		$nodelist = $xpath->query("//newsMessage:headline");
		
		if($nodelist->length == 0) {
			$nodelist = $xpath->query("//html:title");
			
			if($nodelist->length == 0) {
				$nodelist = $xpath->query("//nitfns:h1");
			}
		}
		
		foreach($nodelist as $node) {
			$headline = $node->nodeValue;
		}

		return $headline;
	}
	
	private static function getPostSlug($xpath) {
		$nodelist = $xpath->query("//newsMessage:slugline");
		$slugline = "";
	
		foreach($nodelist as $node) {
			$slugline = $node->nodeValue;
		}

		return $slugline;
	}
	
	private static function getMetaGuid($xpath) {
		$guid = null;
		$nodelist = $xpath->query("//newsMessage:newsItem/@guid");
		
		foreach($nodelist as $node) {
			$guid = $node->nodeValue;
		}

		return $guid;
	}
	
	private static function getMetaVersion($xpath) {
		$version = null;
		$nodelist = $xpath->query("//newsMessage:newsItem/@version");
		
		foreach($nodelist as $node) {
			$version = $node->nodeValue;
		}
		
		return $version;
	}
	
	private static function getMetafirstCreated($xpath) {
		$firstCreated = null;
		$nodelist = $xpath->query("//newsMessage:firstCreated");
		
		foreach($nodelist as $node) {
			$firstCreated = $node->nodeValue;
		}
		
		return $firstCreated;
	}
	
	private static function getMetaVersionCreated($xpath) {
		$versionCreated = null;
		$nodelist = $xpath->query("//newsMessage:versionCreated");
		
		foreach($nodelist as $node) {
			$versionCreated = $node->nodeValue;
		}
		
		return $versonCreated;
	}
	
	public static function getMetaCreator($xpath) {
		$creator = null;
		$nodelist = $xpath->query("//newsMessage:creator/name");
		
		if($nodelist->length == 0) {
			$nodelist = $xpath->query("//newsMessage:creator@literal");
		}
		
		foreach($nodelist as $node) {
			$creator = $node->nodeValue;
		}
		
		return $creator;
	}
	
	private static function insertArrayCheck($array) {
		if($array[ 'post_content' ] == "" and $array[ 'post_title' ] == "") {
			return false //Placeholder until response messages are ready
		}
		else if($array[ 'post_content' ] == "") {
			return false //Placeholder until response messages are ready
		}
		else if($array[ 'post_title' ] == "") {
			return false //Placeholder until response messages are ready
		}
		else {
			return true //Placeholder until response messages are ready
		}
	}
	
	/*private static function getPostDate ($xpath) {
		$nodelist = $xpath->query("//embargoed");
		
		$embargoString = '';
		
		foreach($nodelist as $node) {
			$embargoString = $node->nodeValue;
		}
		
		$embargoDate = DateTime::createFromFormat('Y-m-d H:i:s', substr($embargoString, 0, 10) . ' ' . substr($embargoString, 11, 8));
		
		echo $embargoDate->format('Y-m-d H:i:s');
		return $embargoDate;
	}*/
		

}
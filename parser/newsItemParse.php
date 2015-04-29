<?php

/**
 * Class used to parse newsItems
 *
 * This class parses newsItems in NewsML-G2 using DOMXPath. It den send them to a RESTApi
 *
 * @author Petter Lundberg Olsen
 */
class NewsItemParse {

    /*Array structure of $returnArray that are sent to the RESTApi:
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
                                'nml2_embargoDate' 	  	=> string
                                'nml2_newsMessageSent' => string
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


    /**
     * Creates and returns the array structure that are sent to the RESTApi
     *
     * This method is the main method of the NewsItemParse file. It creates the DOMXpath object used to query information from a NewsML-G2 document.
     * The array congaing all the information from the NewsML document are created in this method.
     *
     * @param string $xml raw XML on the NewsMl-G2 standard
     * @return array
     * @author Petter Lundberg Olsen
     */
    public static function parseNewsML($xml) {
        global $_ns;
        global $_addToArray;
        global $_xpath;

        $returnArray = array(
            'status_code' => 200 //int, the status code automatically set to 200
        );

        $_addToArray = true;

        if ($xml == null) {
            $returnArray['status_code'] = 400;

            return $returnArray;
        }

        self::createXpath($xml);

        /*Query to separate the different newsItems in a newsMessage
          This query will find the absolute path (without XML namespaces): newsMessage/itemSet/newsItem
        */
        $newsItemList = $_xpath->query("//" . $_ns . "newsItem");

        //Files the given array for each newsItem
        foreach ($newsItemList as $newsItem) {


            $newsItemArray = array(
                'post' => self::createPostArray($newsItem), //array
                'meta' => self::createMetaArray($newsItem), //array
                'users' => self::createUserArray($newsItem), //array
                'subjects' => self::createSubjectArray($newsItem), //array
                'photo' => array()
            );

            $returnArray = self::createPhotoArray($newsItem, $returnArray);


            if ($newsItemArray['post']['post_status'] == 'publish') {
                //Checking if an embargo date is present and changes 'post_status' accordingly
                $newsItemArray['post']['post_status'] = self::setEbargoState($newsItemArray['meta']['nml2_embargoDate']);
            }

            //Adds the information found in the newsItem to the array that will be sent to the RESTApi
            if ($_addToArray) {
                array_push($returnArray, $newsItemArray);
            }
        }
        $_addToArray = true;

        //Checking if there is any errors in the data gathered from the newsML document and changes status code accordingly
        $returnArray['status_code'] = self::setStatusCode($returnArray);

        return $returnArray;
    }

    /**
     * Declares a new DOMXpath object
     *
     * This method declares the DOMXpath object used to find all information in a XML document.
     * Namespaces are also registered in this method.
     * @param string $xml raw XML on the NewsMl-G2 standard
     * @author Petter Lundberg Olsen
     */
    private static function createXpath($xml) {
        global $_ns;
        global $_xpath;
        $doc = new DOMDocument();

        $file = ltrim($xml);

        //Checks if $file is raw XML or a XML file and uses the correct load operation
        if (is_file($file)) {
            $doc->load($file);
        } else {
            $doc->loadXML($file);
        }

        //Finds the namespace of the outermost tag in the xml file
        $uri = $doc->documentElement->lookupnamespaceURI(null);


        $_xpath = new DOMXPath($doc);

        //XML namescpaces
        $_xpath->registerNamespace('html', "http://www.w3.org/1999/xhtml");
        $_xpath->registerNamespace('nitf', "http://iptc.org/std/NITF/2006-10-18/");

        //Test to see if $uri if not equal to null
        if ($uri != null) {
            $_xpath->registerNamespace("docNamespace", $uri);
            $_ns = "docNamespace:";
        } else {
            $_ns = "";
        }
    }

    /**
     * Creates and returns the array containing the post
     *
     * This method creates and returns an array congaing the post that are sendt to the Wordpress database. The way to array is structured is
     * given by Worpdress to be able to use the wp_inser_post method, see http://codex.wordpress.org/Function_Reference/wp_insert_post
     * for more information on the post array
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that a new query shall be preformed on
     * @return array
     * @author Petter Lundberg Olsen
     */
    private static function createPostArray($newsItem) {
        $post = array(
            'post_content' => self::getPostContent($newsItem), // The full text of the post.
            'post_name' => self::getPostName($newsItem), // The name (slug) for your post
            'post_title' => self::getPostHeadline($newsItem), // The title of your post.
            'post_status' => self::setPostStatus($newsItem), //[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
            'tags_input' => self::getPostTags($newsItem) // Default empty.
        );

        return $post;
    }

    /**
     * Finds and returns content of a newsItem
     *
     * This method uses a DOMXPath query to find and return the main content of a news article in a given newsItem
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string content, null if no content present
     * @author Petter Lundberg Olsen
     */
    private static function getPostContent($newsItem) {
        global $_ns;
        global $_xpath;
        $content = null;

        /*Query path that continues from first query at the start of the document.
          Path without XML namespace: contentSet/inlineXML/html/body/article/div it will only choose the div whit a itemprop attribute = articleBody
        */
        $content = $_xpath->query($_ns . "contentSet/" . $_ns . "inlineXML/html:html/html:body/html:article/html:div[@itemprop='articleBody']", $newsItem)->item(0);

        if ($content == null) {

            /*Trying this query if the above query  gives no result.
            Query path that continues from first query at the start of the document.
            Path without XML namespace: contentSet/inlineXML/html/body
            */
            $content = $_xpath->query($_ns . "contentSet/" . $_ns . "inlineXML/html:html/html:body", $newsItem)->item(0);

            if ($content == null) {

                /*Trying this query if the above query  gives no result.
                  Query path that continues from first query at the start of the document.
                  Path without XML namespace: contentSet/inlineXML/nitf/body/body.content
                */
                $content = $_xpath->query($_ns . "contentSet/" . $_ns . "inlineXML/nitf:nitf/nitf:body/nitf:body.content", $newsItem)->item(0);

                if ($content == null) {

                    /*Trying this query if the above query  gives no result.
                      Query path that continues from first query at the start of the document.
                      Path without XML namespace: contentSet/inlineData
                    */
                    $content = $_xpath->query($_ns . "contentSet/" . $_ns . "inlineData", $newsItem)->item(0);

                    if ($content == null) {
                        return null;
                    }
                }
            }
        }

        $content = self::get_inner_html($content);

        return $content;
    }

    /**
     * Gets the inner html tags in a DOMNode
     *
     * This function takes a DOMNode and makes sure that the node value contains the original html/xml tags
     * that woad otherwise be removed.
     * Attribution:
     * From php manual comment 101243, http://php.net/manual/en/class.domelement.php#101243
     *
     * @param DOMNode $node
     * @return string innerHTML, the result from a query containing html tags
     */
    private static function get_inner_html($node) {
        $innerHTML = '';
        $children = $node->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $child->ownerDocument->saveXML($child);
        }

        return $innerHTML;
    }

    /**
     * Finds and returns slugline
     *
     * This method uses a DOMXPath query to find and return the slugline of a newsItem
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string slugline, null if no slugline present
     * @author Petter Lundberg Olsen
     */
    private static function getPostName($newsItem) {
        global $_ns;
        global $_xpath;

        /*Query path that continues from first query at the start of the document
          Path without XML namespace: contentMeta/slugline
        */
        $name = $_xpath->query($_ns . "contentMeta/" . $_ns . "slugline", $newsItem)->item(0);

        return self::nodeListNotNull($name);
    }

    /**
     * Find ans return headline
     *
     * This method uses a DOMXPath query to find and return the headline of a newsItem
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string headline, null if no headline present
     * @author Petter Lundberg Olsen
     */
    private static function getPostHeadline($newsItem) {
        global $_ns;
        global $_xpath;

        /*Query path that continues from first query at the start of the document.
          Path without XML namespace: contentMeta/headline
        */
        $headline = $_xpath->query($_ns . "contentMeta/" . $_ns . "headline", $newsItem)->item(0);

        if ($headline == null) {

            /*Trying this query if the above query  gives no result
              Query path that continues from first query at the start of the document.
              Path without XML namespace: contentSet/inlineXML/html/head/title
            */
            $headline = $_xpath->query($_ns . "contentSet/" . $_ns . "inlineXML/html:html/html:head/html:title", $newsItem)->item(0);

            if ($headline == null) {

                /*Trying this query if the above query  gives no result
                  Query path that continues from first query at the start of the document.
                  Path without XML namespace: contentSet/inlineXML/html/head/title
                */
                $headline = $_xpath->query($_ns . "contentSet/" . $_ns . "inlineXML/nitf:nitf//nitf:body/nitf:body.head/nitf:hedline", $newsItem)->item(0);

                if ($headline == null) {
                    return null;
                }
            }
        }

        return $headline->nodeValue;
    }

    /**
     * Find and sets the publication status of the post
     *
     * This method uses a DOMXPath query to find the publication status on the NewsML-G2 document returns a valid Wordpress
     * status depending on the pubStatus. It sends 'publish' if the status is usable, 'trash' if the status is canceled and
     * 'pending' in all other cases.
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string 'publish', 'trash' or 'pending'
     * @author Petter Lundberg Olsen
     */
    private static function setPostStatus($newsItem) {
        global $_ns;
        global $_xpath;

        /*Query path that continues from first query at the start of the document
          Path without XML namespace: itemMeta/pubStatus/qcode-attribute
        */
        $pubStatus = $_xpath->query($_ns . "itemMeta/" . $_ns . "pubStatus/@qcode", $newsItem)->item(0);

        if ($pubStatus != null) {
            if ($pubStatus->nodeValue == "stat:withheld") {
                return 'pending';
            } elseif ($pubStatus->nodeValue == "stat:canceled") {
                return 'trash';
            }
        }

        return 'publish';
    }

    /**
     * Find and returns the keyword of a newsItem
     *
     * This method uses a DOMEXPath query to find and return the keyword given in a newsItem. The keywords are on the form: '<keyword>,<keyword>,...'
     * This form is needed to use the keywords as tags in the Wordpress database
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string tags, null if no tags present
     * @author Petter Lundberg Olsen
     */
    private static function getPostTags($newsItem) {
        global $_ns;
        global $_xpath;
        $tags = null;

        /*Query path that continues from first query at the start of the document
          Path without XML namespace: contentMeta/keyword
        */
        $nodelist = $_xpath->query($_ns . "contentMeta/" . $_ns . "keyword", $newsItem);

        /*Sets the results of the query above on the return variable if any
          Result of this loop should lock like: '<keyword>,<keyword>,...'
        */
        foreach ($nodelist as $node) {
            $tags .= $node->nodeValue . ",";
        }

        return $tags;
    }

    /**
     * Creates and files the metadata array
     *
     * This method creates and files the array containing the metadata of a newsMessage
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that a new query shall be preformed on
     * @return array
     * @author Petter Lundberg Olsen
     */
    private static function createMetaArray($newsItem) {
        $meta = array(
            'nml2_guid' => self::getMetaGuid($newsItem), //string, the guide of the newsItem
            'nml2_version' => self::getMetaVersion($newsItem), //string, the version of the newsItem
            'nml2_firstCreated' => self::getMetaFirstCreated($newsItem), //string, the timesap when the newsItem was first created
            'nml2_versionCreated' => self::getMetaVersionCreated($newsItem), //string, the timestamp when the current version of the newsItem was created
            'nml2_embargoDate' => self::getMetaEmbargo($newsItem), //string, timestamp of the embargo
            'nml2_newsMessageSent' => self::getMetaSentDate(), //string, timestamp from when the newsMessage where sent
            'nml2_language' => self::getMetaLanguage($newsItem), //string, the language of the content in the newsItem
            'nml2_copyrightHolder' => self::getMetaCopyrightHolder($newsItem), //string,
            'nml2_copyrightNotice' => self::getMetaCopyrightNotice($newsItem), //string,
        );

        return $meta;
    }

    /**
     * Finds and returns guid
     *
     * This method uses a DOMXPath query to find and return the guid of a newsItem
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string guid, null if no guid present
     * @author Petter Lundberg Olsen
     */
    private static function getMetaGuid($newsItem) {
        global $_xpath;

        /*Query path that continues from first query at the start of the document
          Path without XML namespace: @guid (find the guid attribute in the newsItem tag)
        */
        $guid = $_xpath->query("@guid", $newsItem)->item(0);

        return self::nodeListNotNull($guid);
    }

    /**
     * Finds and returns the version number
     *
     * This method user DOMEXPath query to find and return the version number of the newsItem given in a NewsML-G2 document
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string version number, null if no version present
     * @author Petter Lundberg Olsen
     */
    private static function getMetaVersion($newsItem) {
        global $_xpath;

        /*Query path that continues from first query at the start of the document
          Path without XML namespace: @version (find the version attribute in the newsItem tag)
        */
        $version = $_xpath->query("@version", $newsItem)->item(0);

        return self::nodeListNotNull($version);
    }

    /**
     * Finds and returns a timestamp from when the news article was first created
     *
     * This method uses a DOMXPath query to find and return a timestamp from when the first version of the newsItem where created
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string first created timestamp, null if no timestamp is present
     * @author Petter Lundberg Olsen
     */
    private static function getMetaFirstCreated($newsItem) {
        global $_ns;
        global $_xpath;

        /*Query path that continues from first query at the start of the document
          Path without XML namespace: itemMeta/firstCreated
        */
        $firstCreated = $_xpath->query($_ns . "itemMeta/" . $_ns . "firstCreated", $newsItem)->item(0);

        return self::nodeListNotNull($firstCreated);
    }

    /**
     * Finds and returns a timestamp from when the present version was created
     *
     * This method uses a DOMXPath query to find and return a timestamp from when the current version of the newsItem where created
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string version created timestamp, null if no timestamp is present
     * @author Petter Lundberg Olsen
     */
    private static function getMetaVersionCreated($newsItem) {
        global $_ns;
        global $_xpath;

        /*Query path that continues from first query at the start of the document.
          Path without XML namespace: itemMeta/versionCreated
        */
        $versionCreated = $_xpath->query($_ns . "itemMeta/" . $_ns . "versionCreated", $newsItem)->item(0);

        return self::nodeListNotNull($versionCreated);
    }

    /**
     * Finds and returns the embargo if present
     *
     * This method user DOMXPath query to find the embargo date of a NewsML-G2 Document and returns it as a string
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string embargo date, null if no embargo is present
     * @author Petter Lundberg Olsen
     */
    private static function getMetaEmbargo($newsItem) {
        global $_ns;
        global $_xpath;

        /*Query path that continues from first query at the start of the document.
          Path without XML namespace: itemMeta/embargoed
        */
        $embargo = $_xpath->query($_ns . "itemMeta/" . $_ns . "embargoed", $newsItem)->item(0);

        return self::nodeListNotNull($embargo);
    }

    /**
     * Finds and returns the sent date from the NewsML document
     *
     * This method finds the <sent> tag in NewsML-G2 and returns it as a string. It uses DOMEXpath
     * find the tag
     *
     * @return string date sent timestamp, null if no date is present
     * @author Petter Lundberg Olsen
     */
    private static function getMetaSentDate() {
        global $_ns;
        global $_xpath;

        //Path without XML namespace: newsMessage/header/sent
        $sentDate = $_xpath->query("//" . $_ns . "newsMessage/" . $_ns . "header/" . $_ns . "sent")->item(0);

        return self::nodeListNotNull($sentDate);
    }

    /**
     * Finds and returns the language of the news article
     *
     * This method finds the language of the content in a NewsML-G2 document using DOMXPath and returns it as a string
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string The language of the news article, null if no language present
     * @author Petter Lundberg Olsen
     */
    private static function getMetaLanguage($newsItem) {
        global $_ns;
        global $_xpath;

        /*Query path that continues from first query at the start of the document.
          Path without XML namespace:4 contentMeta/language/tag-attribute
        */
        $lang = $_xpath->query($_ns . "contentMeta/" . $_ns . "language/@tag", $newsItem)->item(0);

        return self::nodeListNotNull($lang);
    }

    /**
     * Finds and returns the copyright holder of the newsItem
     *
     * This method finds the copyright holder of the content in a NewsML-G2 document using DOMXPath and returns it as a string
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string The copyright holder of the news article, null if no copyright present
     * @author Petter Lundberg Olsen
     */
    private static function getMetaCopyrightHolder($newsItem) {
        global $_ns;
        global $_xpath;

        /*Query path that continues from first query at the start of the document.
          Path without XML namespace: rightsInfo/copyrightHolder/name
        */
        $copyrightHolder = $_xpath->query($_ns . "rightsInfo/" . $_ns . "copyrightHolder/" . $_ns . "name", $newsItem)->item(0);

        return self::nodeListNotNull($copyrightHolder);
    }

    /**
     * Finds and returns the copyright notice of the newsItem
     *
     * This method finds the copyright notice of the content in a NewsML-G2 document using DOMXPath and returns it as a string
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string The copyright notice of the news article, null if no copyright present
     * @author Petter Lundberg Olsen
     */
    private static function getMetaCopyrightNotice($newsItem) {
        global $_ns;
        global $_xpath;

        /*Query path that continues from first query at the start of the document.
          Path without XML namespace: rightsInfo/copyrightNotice
        */
        $copyrightNotice = $_xpath->query($_ns . "rightsInfo/" . $_ns . "copyrightNotice", $newsItem)->item(0);

        return self::nodeListNotNull($copyrightNotice);
    }

    /**
     * Creates an array containing all users
     *
     * This method uses a DOMXPath query to find and return an array containing the creator and all contributors in a newsItem.
     * The first entry in the array is always the creator, and the rest is the contributors
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that a new query shall be preformed on
     * @return array
     * @author Petter Lundberg Olsen
     */
    private static function createUserArray($newsItem) {
        global $_ns;
        global $_xpath;

        $users = array();

        for ($i = 0; $i < 2; $i++) {
            if ($i == 0) {
                $nodelist = $_xpath->query($_ns . "contentMeta/" . $_ns . "creator", $newsItem);
            } else {
                $nodelist = $_xpath->query($_ns . "contentMeta/" . $_ns . "contributor", $newsItem);
            }

            foreach ($nodelist as $node) {
                $user = array(
                    'user_login' => self::getUserName($node), //string login_name of the user
                    'description' => self::getUserDescription($node), //string, describing the role of the user
                    'user_email' => self::getUserEmail($node), //string, the email of the user
                    'nml2_qcode' => self::getUserQcode($node), //string, the users NewsML-G2 qcode
                    'nml2_uri' => self::getUserUri($node) //string, the users NewsML-G2 uri
                );

                array_push($users, $user);
            }
        }

        return $users;
    }

    /**
     * Find and returns the name of a creator/contributor
     *
     * This method uses a DOMXPath query to find and return the name of creator/contributor
     *
     * @param DOMNode $cTag XPath query result congaing one creator/contributor that is used in a sub-query in this method
     * @return string name, null if no name present
     * @author Petter Lundberg Olsen
     */
    private static function getUserName($cTag) {
        global $_ns;
        global $_xpath;

        /*Query path that continues from the query in function getCreator/getContributor
          Path without XML namespace: name
        */
        $userName = $_xpath->query($_ns . "name", $cTag)->item(0);

        //If noe name tag is present, enter this part of the code
        if ($userName == null) {

            /*Query path that continues from the query in function getCreator/getContributor
              Path without XML namespace: literal-attribute
            */
            $userName = $_xpath->query("@literal", $cTag)->item(0);

            if ($userName == null) {
                return null;
            }

        }

        return $userName->nodeValue;
    }

    /**
     * Find and returns the role of a creator/contributor
     *
     * This method uses a DOMXPath query to find and return the role of creator/contributor
     *
     * @param DOMNode $cTag XPath query result congaing one creator/contributor that is used in a sub-query in this method
     * @return string role, null if no role present
     * @author Petter Lundberg Olsen
     */
    private static function getUserDescription($cTag) {
        global $_xpath;

        /*Query path that continues from the query in function getCreator/getContributor
          Path without XML namespace: role-attribute
        */
        $userDescription = $_xpath->query("@role", $cTag)->item(0);

        return self::nodeListNotNull($userDescription);
    }

    /**
     * Finds and retruns an user email
     *
     * This method uses a DOMXPath query to find and return the email of creator/contributor
     *
     * @param DOMNode $cTag XPath query result congaing one creator/contributor that is used in a sub-query in this method
     * @return string email, null if no email present
     * @author Petter Lundberg Olsen
     */
    private static function getUserEmail($cTag) {
        global $_ns;
        global $_xpath;

        $email = $_xpath->query($_ns . "personDetails/" . $_ns . "contactInfo/" . $_ns . "email", $cTag)->item(0);

        return self::nodeListNotNull($email);
    }

    /**
     * Find and returns the qcode of a creator/contributor
     *
     * This method uses a DOMXPath query to find and return the qcode of creator/contributor
     *
     * @param DOMNode $cTag XPath query result congaing one creator/contributor that is used in a sub-query in this method
     * @return string qcode, null if no qcode present
     * @author Petter Lundberg Olsen
     */
    private static function getUserQcode($cTag) {
        global $_xpath;

        $userQcode = $_xpath->query("@qcode", $cTag)->item(0);

        return self::nodeListNotNull($userQcode);
    }

    /**
     * Find and returns the uri of a creator/contributor
     *
     * This method uses a DOMXPath query to find and return the uri of creator/contributor
     *
     * @param DOMNode $cTag XPath query result contains one creator/contributor that is used in a sub-query in this method
     * @return string uri, null if no uri present
     * @author Petter Lundberg Olsen
     */
    private static function getUserUri($cTag) {
        global $_xpath;

        /*Query path that continus from the query in function getCreator/getContributor
          Path without XML namespace: uri-attribute
        */
        $uri = $_xpath->query("@uri", $cTag)->item(0);

        return self::nodeListNotNull($uri);
    }

    /**
     * Creates and returns an array congaing subjects
     *
     * This method uses a DOMXPath query to find all subjects in a newsItem and return them as an array
     *
     * @param DOMNode $newsItem XPath query from an earlier part of the document that the new query shall be preformed on
     * @return array containing all subjects
     * @author Petter Lundberg Olsen
     */
    private static function createSubjectArray($newsItem) {
        global $_ns;
        global $_xpath;
        $subjects = array();

        /*Query path that continues from first query at the start of the document.
          Path without XML namespace: contentMeta/subject
        */
        $nodelist = $_xpath->query($_ns . "contentMeta/" . $_ns . "subject", $newsItem);

        //This loop creates an array cantoning information about each subject
        foreach ($nodelist as $node) {
            $subject = array(
                'qcode' => self::getSubjectQcode($node), //string, the qcode of the subject
                'name' => self::getSubjectName($node), //array, an array containing name and its attributes
                'type' => self::getSubjectType($node), //string, the type of subject
                'uri' => self::getSubjectUri($node), //string, subject uri
                'sameAs' => self::getSubjectSameAsOrBroder($node, 'sameAs'), //array, an array containing all subjects sameAs tags
                'broader' => self::getSubjectSameAsOrBroder($node, 'broader') //array, an array containing all subjects broader tags
            );

            array_push($subjects, $subject);
        }


        return $subjects;
    }

    /**
     * Finds and returns a subjects qcode
     *
     * This method uses a DOMXPath query to find and return a subjects qcode
     *
     * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string qcode, null if no qcode present
     * @author Petter Lundberg Olsen
     */
    private static function getSubjectQcode($subjectTag) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createSubjectArray/createSubjectSameAsArray
          Path without XML namespace: qcode-attribute
        */
        $qcode = $_xpath->query("@qcode", $subjectTag)->item(0);

        return self::nodeListNotNull($qcode);
    }

    /**
     * Find and returns an array containing name and other data
     *
     * This metod uses a DOMEXPath query to find a subjects name and put it and other data about it in an array
     * that are being added in the array of names
     *
     * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
     * @return array containing name arrays
     * @author Petter Lundberg Olsen
     */
    private static function getSubjectName($subjectTag) {
        global $_ns;
        global $_xpath;

        $nameArray = array();

        /*This XPath query is a subquery from the query in the method createSubjectArray/createSubjectSameAsArray
          Path without XML namespace: name
        */
        $nodelist = $_xpath->query($_ns . "name", $subjectTag);

        //This loop creates the name arrays and storing there information
        foreach ($nodelist as $node) {
            $name = array(
                'text' => $node->nodeValue, //string, the actual name
                'lang' => self::getSubjectLang($node), //string, the language of the name
                'role' => self::getSubjectRole($node) //string, the role of the name
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
     * @return string language, null if no language is present
     * @author Petter Lundberg Olsen
     */
    private static function getSubjectLang($nameTag) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method getSubjectName
          Path without XML namespace:4 lang-attribute
        */
        $subjectLang = $_xpath->query("@xml:lang", $nameTag)->item(0);

        return self::nodeListNotNull($subjectLang);
    }

    /**
     * Finds and returns a subject names role
     *
     * This method uses a DOMXPath query to find and return the role of a name tag under a subject
     *
     * @param DOMNode $nameTag XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string role null if no role is present
     */
    private static function getSubjectRole($nameTag) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method getSubjectName
         Path without XML namespace: role-attribute
       */
        $role = $_xpath->query("@role", $nameTag)->item(0);

        return self::nodeListNotNull($role);
    }

    /**
     * Finds and returns a subjects type
     *
     * This method uses a DOMXPath query to find and return a subjects role attribute
     *
     * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string type, null if no type present
     * @author Petter Lundberg Olsen
     */
    private static function getSubjectType($subjectTag) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createSubjectArray/createSubjectSameAsArray
          Path without XML namespace: type-attribute
        */
        $type = $_xpath->query("@type", $subjectTag)->item(0);

        return self::nodeListNotNull($type);
    }

    /**
     * Finds and returns subject uri
     *
     * This method uses a DOMXPath query to find and return a subjects uri
     *
     * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string uri, null if no uri present
     * @author Petter Lundberg Olsen
     */
    private static function getSubjectUri($subjectTag) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createSubjectArray/createSubjectSameAsArray
          Path without XML namespace: type-attribute
        */
        $tag = $_xpath->query("@uri", $subjectTag)->item(0);

        return self::nodeListNotNull($tag);
    }

    /**
     * Creates and returns an array containing subjects
     *
     * This method uses a DOMXPath query to find all sameAs tags in a subject and return them as an array
     *
     * @param DOMNode $subjectTag XPath query from an earlier part of the document that the new query shall be preformed on
     * @param $queryDecision
     * @return array containing all subjects
     * @author Petter Lundberg Olsen
     */
    private static function getSubjectSameAsOrBroder($subjectTag, $queryDecision) {
        global $_ns;
        global $_xpath;

        $sameAsArray = array();

        $nodelist = $_xpath->query($_ns . $queryDecision, $subjectTag);


        //This loop creates an array containing information about each subject
        foreach ($nodelist as $node) {
            $sameAs = array(
                'qcode' => self::getSubjectQcode($node), //string, the qcode of the subject
                'name' => self::getSubjectName($node), //array, an array containing name and its attributes
                'type' => self::getSubjectType($node), //string, the type of subject
                'uri' => self::getSubjectUri($node) //string, subject uri
            );

            array_push($sameAsArray, $sameAs);
        }

        return $sameAsArray;
    }

    /**
     * Creates and returns an array congaing all photos
     *
     * This method uses DOMXPath to find all photos in a news message and returns them in an array
     *
     * @param DOMNode $newsItem XPath query from an earlier part of the document that the new query shall be preformed on
     * @param $returnArray
     * @return array contaning all subjects
     * @author Petter Lundberg Olsen
     */
    private static function createPhotoArray($newsItem, $returnArray) {
        global $_ns;
        global $_addToArray;
        global $_xpath;

        /*Query path that continues from first query at the start of the document.
          Path without XML namespace: contentSet/remoteContent
        */
        $nodelist = $_xpath->query($_ns . "contentSet/" . $_ns . "remoteContent", $newsItem);

        //This loop creates an array containing information about each photo
        foreach ($nodelist as $node) {
            $guid = self::getPhotoTextGuid($newsItem);

            if ($guid == null) {
                $_addToArray = false;
                return $returnArray;
            }

            for ($i = 0; $i < count($returnArray) - 1; $i++) {
                if ($returnArray[$i]['meta']['nml2_guid'] == $guid) {
                    $photo = array(
                        'href' => self::getPhotoHref($node), //string, the source of the image
                        'size' => self::getPhotoSize($node), //string, the size of the image in bytes
                        'width' => self::getPhotoWidth($node), //string, the width of the picture in px
                        'height' => self::getPhotoHeight($node), //string, the height of the image
                        'contenttype' => self::getPhotoContenttype($node), //string, what type of file the image is
                        'colourspace' => self::getPhotoColourspace($node), //string, what colourspace the image is
                        'rendition' => self::getPhotoRendition($node), //string, tells if the image is higres, meant for web, or is a thumbnail
                        'description' => self::getPhotoDescription($newsItem) //string, the description of the image
                    );

                    array_push($returnArray[$i]['photo'], $photo);
                    $_addToArray = false;
                }
            }
        }

        return $returnArray;
    }

    /**
     * Find and return the guid of the newsItem containing an image
     *
     * This method receives a newsItem containing a image. It then finds the guid of the image and uses it to find the group containing the image
     * In the end this is used to find the guid of the articel where image is found.
     *
     * @param DOMNode $newsItem
     * @return string guid of a newsItem, return null if noe
     * @author Petter Lundberg Olsen
     */
    private static function getPhotoTextGuid($newsItem) {
        global $_ns;
        global $_xpath;

        //Finds image guid
        $pGuid = $_xpath->query("@guid", $newsItem)->item(0)->nodeValue;

        if ($pGuid == null) {
            return null;
        }

        //Finds group containing the image
        $group = $_xpath->query("//" . $_ns . "group[./" . $_ns . "itemRef/@residref = '" . $pGuid . "']")->item(0);
        if ($group == null) {
            return null;
        }

        //Finds itemRef of the main article
        $itemRef = $_xpath->query($_ns . "itemRef[./" . $_ns . "itemClass/@qcode='ninat:text']", $group)->item(0);
        if ($itemRef == null) {
            return null;
        }

        //Finds guid of the main article
        $tGuid = $_xpath->query("@residref", $itemRef)->item(0)->nodeValue;

        return $tGuid;
    }

    /**
     * Finds and returns a remoteContent href
     *
     * This method uses a DOMXPath query to find a remoteContent tags href attribute
     *
     * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string href, null if no href present
     * @author Petter Lundberg Olsen
     */
    private static function getPhotoHref($remoteContent) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createPhotoArray
          Path without XML namespace: href-attribute
        */
        $href = $_xpath->query("@href", $remoteContent)->item(0);

        return self::nodeListNotNull($href);
    }

    /**
     * Finds and returns a remoteContent size
     *
     * This method uses a DOMXPath query to find a remoteContent tags size attribute
     *
     * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string size, null if no size present
     * @author Petter Lundberg Olsen
     */
    private static function getPhotoSize($remoteContent) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createPhotoArray
          Path without XML namespace: size-attribute
        */
        $size = $_xpath->query("@size", $remoteContent)->item(0);

        return self::nodeListNotNull($size);
    }

    /**
     * Finds and returns a remoteContent width
     *
     * This method uses a DOMXPath query to find a remoteContent tags width attribute
     *
     * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string width, null if no width present
     * @author Petter Lundberg Olsen
     */
    private static function getPhotoWidth($remoteContent) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createPhotoArray
          Path without XML namespace: width-attribute
        */
        $width = $_xpath->query("@width", $remoteContent)->item(0);

        return self::nodeListNotNull($width);
    }

    /**
     * Finds and returns a remoteContent height
     *
     * This method uses a DOMXPath query to find a remoteContent tags height attribute
     *
     * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string height, null if no height present
     * @author Petter Lundberg Olsen
     */
    private static function getPhotoHeight($remoteContent) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createPhotoArray
          Path without XML namespace: height-attribute
        */
        $hight = $_xpath->query("@height", $remoteContent)->item(0);

        return self::nodeListNotNull($hight);
    }

    /**
     * Finds and returns a remoteContent content type
     *
     * This method uses a DOMXPath query to find a remoteContent tags contenttype attribute
     *
     * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string contenttype, null if no contenttype present
     * @author Petter Lundberg Olsen
     */
    private static function getPhotoContenttype($remoteContent) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createPhotoArray
          Path without XML namespace: contenttype-attribute
        */
        $contenttype = $_xpath->query("@contenttype", $remoteContent)->item(0);

        return self::nodeListNotNull($contenttype);
    }

    /**
     * Finds and returns a remoteContent colourspace
     *
     * This method uses a DOMXPath query to find a remoteContent tags colourspace attribute
     *
     * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string colourspace, null if no colourspace present
     * @author Petter Lundberg Olsen
     */
    private static function getPhotoColourspace($remoteContent) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createPhotoArray
          Path without XML namespace: colourspace-attribute
        */
        $coloyrspace = $_xpath->query("@colourspace", $remoteContent)->item(0);

        return self::nodeListNotNull($coloyrspace);
    }

    /**
     * Finds and returns a remoteContent rendition
     *
     * This method uses a DOMXPath query to find a remoteContent tags rendition attribute
     *
     * @param DOMNode $remoteContent XPath query from an earlier part of the document that the new query shall be preformed on
     * @return string rendition, null if no rendition present
     * @author Petter Lundberg Olsen
     */
    private static function getPhotoRendition($remoteContent) {
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createPhotoArray
          Path without XML namespace: rendition-attribute
        */
        $rendition = $_xpath->query("@rendition", $remoteContent)->item(0);

        return self::nodeListNotNull($rendition);
    }

    /**
     * Fin and returns the description of an image
     *
     * This method uses a DOMXPath query to find and return the description of and image
     *
     * @param DOMNode $newsItem XPath query result from an earlier part of the document that the new query shall be preformed on
     * @return string The description of the image, null if no description present
     * @author Petter Lundberg Olsen
     */
    private static function getPhotoDescription($newsItem) {
        global $_ns;
        global $_xpath;

        /*This XPath query is a subquery from the query in the method createPhotoArray
          Path without XML namespace: contentMeta/description
        */
        $description = $_xpath->query($_ns . "contentMeta/" . $_ns . "description", $newsItem)->item(0);

        return self::nodeListNotNull($description);
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
        if ($embargo == null) {
            return 'publish';
        } else {
            return 'future';
        }
    }

    /**
     * Checks if some of the parts of the data being sent to Wordpress is missing and setting status code accordingly
     *
     * Checks first if 'status_code' in $returnArray is set to something diferent then 200 and returns that number if it dose.
     * Checks then if any of the more important parts of the meta and post arrays are missing, and if the are returning 400.
     * The method returns 200 if everything is OK
     *
     * @param array $returnArray The array containing 'status_code'
     * @return int 200 if all OK, 400 if something is missing and 'status_code' value if not 200
     * @author Petter Lundberg Olsen
     */
    private static function setStatusCode($returnArray) {

        if ($returnArray['status_code'] != 200) {
            return $returnArray['status_code'];
        }

        if (count($returnArray) == 1) {
            return 400;
        }

        for ($i = 0; $i < count($returnArray) - 1; $i++) {
            if ($returnArray[$i]['post']['post_content'] == null) {
                return 400;
            } else if ($returnArray[$i]['post']['post_title'] == null) {
                return 400;
            } else if ($returnArray[$i]['meta']['nml2_guid'] == null) {
                return 400;
            } else if ($returnArray[$i]['meta']['nml2_version'] == null) {
                return 400;
            }
        }

        return 200;
    }

    private static function nodeListNotNull($nodeList) {
        if ($nodeList != null) {
            return $nodeList->nodeValue;
        }

        return null;
    }

}

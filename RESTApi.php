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
 * 400: Bad request (Something wrong with the NewsML-G2)
 * 500: Internal Server Error
 *

 *
 * @author Michael Pande
 */

require('parser/newsItemParse.php');      // Parses NewsML-G2 NewsItems
require('parser/DateParser.php');          // Parses Date strings
require('parser/QCodes.php');              // Handles storage and retrieval of QCodes and their values.
require('functions/functions.php');
require('functions/httpHeader.php');          // Sets HTTP status codes
require('../../../wp-load.php');          // Potentially creates bugs. Necessary to access methods in the WordPress core from outside.


ob_start();                     // Turns output buffering on, making it possible to modify header information (status codes etc) after echoing, printing and var_dumping.

$DEBUG = false;                 // Return debug information or not
$UPDATE_OVERRIDE = false;       // True = Ignore version number in NewsItem and do update anyways.
$MANUAL_UPLOAD = false;         // If the import is done manually
$OUTPUT = new HTMLView();       // Create HTML view for all the output
$VALIDATE_NEWSML = true;        // If NewsML-G2 should be validated

setGlobalUserVariables();       // Sets the global variables above with user values

$OUTPUT->setTitle("Rewpert-G2 Output");
$OUTPUT->setDescription("");


authenticateUser();             // Stops the entire process if key is invalid

$userInput = getUserInput();    // Will stop the entire process if invalid request and set headers accordingly









$containedKnowledgeItems = QCodes::updatePluginDB($userInput);   // If $userInput has KnowledgeItem: Updates the plugin specific QCodes database with QCodes
$OUTPUT->newHeading("Parsing KnowledgeItems");
$OUTPUT->appendStrongText("Contained and imported KnowledgeItems:");
$OUTPUT->appendParagraph($containedKnowledgeItems);

if($containedKnowledgeItems){
    httpHeader::setHeader(201);
    $OUTPUT->appendParagraph("Ignore validate request, setting header 201 and exiting. This happens because the API
    and validator does not support KnowledgeItems and NewsItem in the same file.");
    exitAPI();
}








if($VALIDATE_NEWSML){  // Validating NewsItems only, not KnowledgeItems

    $OUTPUT->newHeading("Validating NewsML-G2");

    set_error_handler(function () {}); // Suppress warnings in the validator
    $validationResult = validateNewsML($userInput);
    restore_error_handler();


    if($validationResult != null && $validationResult->hasError) {
        $OUTPUT->appendStrongText("Document did not validate as correct NewsML-G2");
        if (empty($validationResult->errors)) {
            $OUTPUT->appendParagraph($validationResult->message);
        } else {
            $OUTPUT->appendParagraph($validationResult->errors);
        }

        httpHeader::setHeader(400);
        exitAPI();
    }else{
        $OUTPUT->appendParagraph("Document is valid NewsML-G2");
    }
}








$parsedXML = newsItemParse::parseNewsML($userInput);              // Gets a multidimensional array in return with potential NewsItems.
$OUTPUT->newHeading("Parsing NewsItems:");


if ($parsedXML['status_code'] != 200) {       // Checks if something went wrong during parsing
    if($parsedXML['error_message'] != null){
        $OUTPUT->appendStrongText($parsedXML['error_message']);
    }

    if ($containedKnowledgeItems) {
        $OUTPUT->appendParagraph("KnowledgeItem was imported, setting http header 201");
        httpHeader::setHeader(201);
    } else {
        $OUTPUT->appendParagraph("Did not contain KnowledgeItem, using http status code from NewsItemParser");
        httpHeader::setHeader($parsedXML['status_code']);

    }

    exitAPI(); 
}

$OUTPUT->appendParagraph($parsedXML);



// For each NewsItem or similar returned from NewsItemParse
foreach ($parsedXML as $newsItem) {
    if (is_array($newsItem)) {
        insertNewsItem($newsItem);
    }

}


exitAPI();


/**
 * Inserts:
 *    NewsItem (post),
 *     NML-G2 meta data,
 *    Keywords(tags),
 *    Subjects(categories),
 *    Creators / Contributors (authors)
 *    into WordPress
 *
 * @param $newsItem - A NewsItem object (array of values)
 * @author Michael Pande
 */
function insertNewsItem($newsItem)
{
    global $UPDATE_OVERRIDE, $OUTPUT, $IMPORTANT_MESSAGES;
    $OUTPUT->newHeading("Insert or Update Post:");


    $post = $newsItem['post'];
    $meta = $newsItem['meta'];
    $subjects = $newsItem['subjects'];
    $authors = $newsItem['users'];
    $photos = $newsItem['photo'];
    $result = null;                             // A numeric POST_ID or a WP_ERROR object

    $OUTPUT->appendParagraph($post);
    if ($post == null || $meta == null || ($post['post_content'] == null && $post['post_title'] == null)) {
        $OUTPUT->appendStrongText('Something vital was not parsed: importing stopped.');
        httpHeader::setHeader(400);
        exitAPI();
    }


    $post['post_date'] = DateParser::getNonGMT($meta['nml2_versionCreated']);
    $post['post_date_gmt'] = DateParser::getGMTDateTime($meta['nml2_versionCreated']);


    $post['post_author'] = getCreator($authors);    // Sets author like the creator, for the single author support in WordPress, multi author support is handled in another method


    $existing_post = getPostByGUID($meta['nml2_guid']);


    if ($existing_post == null) {
        $OUTPUT->appendStrongText('Did not find previous NewsItem (WP_POST) with same GUID, inserting the new one.');
        $result = wp_insert_post($post, true); // Creates new post
    } // Updates post with corresponding ID, if the NML2-GUID is found in the WP Database and the meta->version is higher.


    else if ($existing_post != null && ($UPDATE_OVERRIDE || $meta['nml2_version'] > get_post_meta($existing_post->ID, 'nml2_version')[0])) {   // Array Dereferencing (Requires PHP version >= 5.4)
        $OUTPUT->appendStrongText('Found NewsItem (WP_POST) with same GUID, starting update.');
        $post['ID'] = $existing_post->ID;
        $result = wp_update_post($post, true);  // Creates a new revision, leaving two similar versions in wp_database, only showing the newest. Both are accessible through the WP admin interface.
    }


    // Found post, but not newer version
    else if ($existing_post != null) {
        $OUTPUT->appendStrongText('Found NewsItem (WP_POST) with same GUID, but the NewsML-G2 document had a version number lower or equal to the existing post');
        $OUTPUT->appendStrongText('Add: &update_override=true to override NewsML-G2 version');
    }

    $OUTPUT->appendSubheading("Returned from WordPress");
    $OUTPUT->appendStrongText($result);

    insertPostMeta($result, $meta);
    setPostCategories($result, $subjects, $meta['nml2_language']);

    insertPhotos($result, $post, $photos);
    setAuthors($result, $authors); // Allows multiple author support.




    if ($result == null) {                // Conflict (Existing copy with same version number and GUID)
        httpHeader::setHeader(409);
        exitAPI();
    }

    if (is_numeric($result)) {            // Created
        httpHeader::setHeader(201);
    } else {
        httpHeader::setHeader(200); // Not modified
    }

}

/**
 * Inserts:
 *    PHOTOS
 *    into WordPress
 *
 * @param $post - A WP_Post object (array of values)
 * @param $post_id - The ID of the post
 * @param $photos - A multidimensional array of photos.
 * @author Michael Pande
 */
function insertPhotos($post_id, $post, $photos)
{
    global $OUTPUT;
    $OUTPUT->appendSubheading("Inserting Photos");

    if (!is_numeric($post_id) || $post == null || $photos == null) {
        $OUTPUT->appendParagraph("No photo changes - probably because you didn't have a newer version of a NewsItem that already exists in WP with that GUID.");
        return;
    }

    $count = 0;
    $firstUrl = null;

    foreach ($photos as $key => $val) {
        if ($val["href"] != null) {
            $count++;
            $image = wp_get_image_editor($val["href"]);

            if (!is_wp_error($image)) {

                $imgUrl = '/images/' . $post_id . '/' . $count . '.jpg';
                $OUTPUT->appendParagraph("ImageUrl: " . $imgUrl);

                $orig_filename = basename($val["href"]);
                $OUTPUT->appendParagraph("Original filename: " . $orig_filename);
                $image->save("." . $imgUrl);


                $pattern = "/(src=)[\"\'](.*)" . $orig_filename . "[\"\']/";

                $new_url = "src=\"" . getPathToPluginDir() . $imgUrl . "\"";


                if ($firstUrl == null) {  // Replace
                    $firstUrl = getPathToPluginDir() . $imgUrl;
                    $post['post_content'] = preg_replace('/(<img[^>]+>)/i', '', $post['post_content'], 1);    // Remove first img tag.
                }


                $post['post_content'] = preg_replace($pattern, $new_url, $post['post_content'], -1);

                $OUTPUT->appendParagraph("WP_Post after replacing inline image URLs with new URLs:");
                $OUTPUT->appendParagraph($post);
            } else {
                $OUTPUT->appendParagraph("Error");
            }

        }


    }
    $post['ID'] = $post_id; // Ensure that ID is set
    $result = wp_update_post($post, true);  // Creates a new revision, leaving two similar versions, only showing the newest.


    if ($firstUrl != null) {      // Set feature image

        setFeatureImage($post_id, $firstUrl);

    }

    $OUTPUT->appendParagraph("Result from WP_UPDATE:");
    $OUTPUT->appendParagraph($result);

}


/* Attribution:
 * The code in this method is from Stack Overflow
 * Link to question: http://wordpress.stackexchange.com/questions/100838/
 * Question authors:
 *	Faisal Shehzad - http://wordpress.stackexchange.com/users/30847/faisal-shehzad
 * Answer author:
 *  GhostToast - http://wordpress.stackexchange.com/users/13676/ghosttoast */
/**
 * @param $post_id
 * @param $url
 */
function setFeatureImage($post_id, $url)
{
    global $OUTPUT;

    $OUTPUT->appendParagraph('Set feature image');
    // only need these if performing outside of admin environment
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // example image
    $image = 'http://example.com/logo.png';

    // magic sideload image returns an HTML image, not an ID
    $media = media_sideload_image($url, $post_id);

    // therefore we must find it so we can set it as featured ID
    if (!empty($media) && !is_wp_error($media)) {
        $args = array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'post_parent' => $post_id
        );

        // reference new image to set as featured
        $attachments = get_posts($args);

        if (isset($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                // grab source of full size images (so no 300x150 nonsense in path)
                $image = wp_get_attachment_image_src($attachment->ID, 'full');
                // determine if in the $media image we created, the string of the URL exists
                if (strpos($media, $image[0]) !== false) {
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
function insertPostMeta($post_id, $meta)
{
    global $OUTPUT;
    $OUTPUT->appendSubheading("Setting metadata");
    if (!isset($post_id) || !isset($meta) || !is_numeric($post_id) || $meta == null) {
        $OUTPUT->appendParagraph("No metadata changes - probably because you didn't have a newer version of a NewsItem that already exists in WP with that GUID.");
        return;
    }

    foreach ($meta as $key => $val) {
        $OUTPUT->appendParagraph("$key : $val");
        update_post_meta($post_id, $key, $val);

    }


}


/**
 * This method sets the post categories in WordPress, it requires the QCodes class, which handles QCodes and
 * corresponding language values.
 *
 * @param $post_id - A numeric WP_Post ID
 * @param $subjects - A multidimensional array of subjects containing QCodes, names and misc metadata
 * @param $lang - Language of post, used to select from the database of QCodes, as secondary key.
 *
 * @author Michael Pande
 */
function setPostCategories($post_id, $subjects, $lang)
{
    global $OUTPUT;

    $OUTPUT->appendSubheading("Setting post categories");



    if (!is_numeric($post_id) || $subjects == null) {
        $OUTPUT->appendParagraph("No post category changes - probably because you didn't have a newer version of a NewsItem that already exists in WP with that GUID.");
        return;
    }
    $OUTPUT->appendParagraph("Current language:  $lang");
    $category_id = array();


    foreach ($subjects as $key => $subject) {

        // Have to find match on language
        $OUTPUT->appendParagraph($subject);
        $id = null;
        foreach ($subject['name'] as $nameKey => $nameVal) {
            $id = null;

            // If subject with name and correct lanuage is found
            if ($nameVal['lang'] == $lang || $nameVal['lang'] == null) {

                // Will use 'name' if set
                $OUTPUT->appendParagraph("Lang is $lang");
                if ($nameVal['text'] != null && $nameVal['role'] != "nrol:mnemonic") {
                    $OUTPUT->appendParagraph("Text and role is OK.");
                    QCodes::setSubject($subject['qcode'], $lang, $nameVal['text']);
                    $id = createOrGetCategory($nameVal['text']);
                } else {
                    $OUTPUT->appendParagraph("Checking for existing category..");
                    $result = QCodes::getSubject($subject['qcode'], $lang);

                    if ($result != null && isset($result['name'])) {
                        $id = createOrGetCategory($result['name']);
                    }
                }

            } else {
                $OUTPUT->appendParagraph("GET SUBJECT: " . $subject['qcode'] . ", $lang");
                $id = createOrGetCategory(QCodes::getSubject($subject['qcode'], $lang));
            }


            if ($id != null) {
                array_push($category_id, $id);

            }


        }

        if ($id == null) {
            $OUTPUT->appendParagraph("GET SUBJECT: " . $subject['qcode'] . ", $lang");
            $result = QCodes::getSubject($subject['qcode'], $lang);
            $OUTPUT->appendParagraph($result);
            if ($result != null && isset($result['name'])) {
                $id = createOrGetCategory($result['name']);
                array_push($category_id, $id);
            }

        }


        createOrGetCategory($subject);

    }
    $OUTPUT->appendParagraph($category_id);
    $OUTPUT->appendParagraph($post_id);
    wp_set_post_categories($post_id, $category_id, false);
}

/**
 * This method creates or gets category for a given string
 * @param $cat - The displayed category name (The one that will be shown to users in Wordpress)
 * @return int  - WordPress error, or category id
 */
function createOrGetCategory($cat)
{
    global $OUTPUT;
    if ($cat == null || !is_string($cat) || strlen($cat) == 0) {
        return;
    }
    $cat = ucfirst($cat);
    $cat_id = get_cat_ID($cat);

    if ($cat_id != 0) {
        $OUTPUT->appendParagraph("Found category! " . $cat_id);
        return $cat_id;
    }
    // CREATE CATEGORY AND RETURN ID; wp_insert_category
    $result = wp_insert_term(
        $cat,
        'category',
        array(
            'description' => '',
            'slug' => ''
        )
    );

    $OUTPUT->appendParagraph("Result from creation of category: ");
    $OUTPUT->appendParagraph($result);
    $OUTPUT->appendParagraph("Create or get Category" . get_cat_ID($cat));
    return get_cat_id($cat);
}


/**
 * This method returns the API key stored in the WordPress database.
 *
 * @return string - The API key.
 *
 * @author Michael Pande
 */
function getAPIkey()
{
    return get_option("nml2-plugin-api-key");

}


/**
 * Returns the creator of the $author array. Which will always be the first or null. (Return in the foreach).
 *
 * @param $authors - array of authors (Creator $ Contributors)
 * @return Creator as string or null
 * @author Michael Pande
 */
function getCreator($authors)
{
    global $OUTPUT;

    $OUTPUT->appendParagraph("Get creator");
    if ($authors == null)
        return null;

    foreach ($authors as $nameVal) {

        $creator = createOrGetAuthor($nameVal);

        if ($creator != null && !is_wp_error($creator) ) {
            return $creator;
        }
    }

    return null;
}


/**
 * Set multiple authors on a post, if they don't exist in the WordPress database, it will create them.
 *
 * @param $authors - array of authors (Creator $ Contributors)
 * @param $post_id - ID of the post
 *
 * @author Michael Pande
 */
function setAuthors($post_id, $authors)
{
    global $OUTPUT;
    $OUTPUT->appendSubheading("Setting authors");

    if (!is_numeric($post_id) || $authors == null) {
        $OUTPUT->appendParagraph("No author changes - probably because you didn't have a newer version of a NewsItem that already exists in WP with that GUID.");
        return;
    }

    $OUTPUT->appendParagraph($authors);
    $author_meta = "";


    // Create or get author ID
    foreach ($authors as $nameVal) {
        $id = createOrGetAuthor($nameVal);
        if (is_numeric($id)) {
            $author_meta = $author_meta . "$id,";
        }

    }

    $result = update_post_meta($post_id, "nml2_multiple_authors", $author_meta);
    $OUTPUT->appendParagraph("Result ");
    $OUTPUT->appendParagraph($result);

}

/**
 * Creates or gets an author in the WordPress database.
 *
 * @param $auth - String - author (Creator / Contributor)
 * @return wp_user - the result for author creation, retrieved user or a WP_error.
 *
 * @author Michael Pande
 */
function createOrGetAuthor($auth)
{
    global $OUTPUT;

    // Return if null or author has no name
    if ($auth == null || strlen($auth['user_login']) <= 0) {
        return;
    }

    $author = get_user_by('login', $auth['user_login']);


    if ($author != null && isset($author->ID) && $author->ID != null) {
        $OUTPUT->appendParagraph("Found author! Author ID: $author->ID");
        return $author->ID;
    }

    $email = ($auth['user_email'] == null ? "" : $auth['user_email']);
    $password = "";

    $result = wp_create_user($auth['user_login'], $password, $email);

    return $result;
}


/**
 * Simply exits the API, and prevents showing output if debug isn't true
 *
 * @author Michael Pande
 */
function exitAPI()
{
    global $DEBUG, $MANUAL_UPLOAD, $OUTPUT;

    $OUTPUT->appendSubheading("Exit API ");
    $OUTPUT->appendParagraph("Done!");
    if ($DEBUG) {
        $OUTPUT->render();
    }

    // Always return successful (Not critical should be fixed)
    if ($MANUAL_UPLOAD) {

        if (http_response_code() == 201) {
            die("File successfully uploaded: " . http_response_code() . '<br><a href="' . $_SERVER['HTTP_REFERER'] . '">Back</a>');
        } else {
            die("File upload failed: " . http_response_code() . '<br><a href="' . $_SERVER['HTTP_REFERER'] . '">Back</a>');
        }
    }


    exit;
}


/**
 * Authentication, exits and returns 401 if wrong API key
 * @author Michael Pande
 */
function authenticateUser()
{
    global $OUTPUT;

    $OUTPUT->newHeading("Authentication");

    if (!authentication()) {
        $OUTPUT->appendParagraph("Failed");
        httpHeader::setHeader(401); // Unauthorized
        exitAPI();
    }
    $OUTPUT->appendParagraph("Successful");
}


/**
 * Authenticates and returns true if API key matches the key sent with HTTP_GET['key'].
 *
 * @return boolean
 *
 * @author Michael Pande
 */
function authentication()
{

    if (isset($_GET['key'])) {
        $USER_KEY = $_GET['key'];

        if ($USER_KEY == getAPIkey()) {
            return true;
        }

    }
    return false;
}


/**
 * Sets global variables for the RESTApi.PHP file
 */
function setGlobalUserVariables()
{
    global $DEBUG, $UPDATE_OVERRIDE, $MANUAL_UPLOAD, $OUTPUT, $VALIDATE_NEWSML;

    if (isset($_GET["debug"]) && $_GET["debug"] == "true") {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $DEBUG = true;
        $OUTPUT = new HTMLView();
    }

    if (isset($_GET["update_override"]) && $_GET["update_override"] == "true") {
        $UPDATE_OVERRIDE = true;
    }

    if (isset($_GET["manual"]) && $_GET["manual"] == "true") {
        $MANUAL_UPLOAD = true;
    }

    if (isset($_GET["validate"]) && $_GET["validate"] == "false") {
        $VALIDATE_NEWSML = false;
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
function getPostByGUID($guid)
{
    global $OUTPUT;

    $OUTPUT->appendParagraph("Get post by nml2-guid: $guid ");

    $args = array(
        'meta_key' => 'nml2_guid',
        'meta_value' => $guid,
        'post_status' => 'any'
    );

    $the_query = new WP_Query($args);


    // The WordPress Loop
    if ($the_query->have_posts()) {


        while ($the_query->have_posts()) {
            $the_query->the_post();
            return get_post();
        }

    }

    return null;

}
	



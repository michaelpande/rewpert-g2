<?php

require(__DIR__ . '/../outputView/HTMLView.php');



 function validateNewsML($xml) {
    require(__DIR__ ."/NewsMLValidator/classes/DocumentDetector.php");
    require(__DIR__ ."/NewsMLValidator/classes/NewsMLValidationRunner.php");
    require(__DIR__ ."/NewsMLValidator/classes/NewsMLValidationResult.php");
    require(__DIR__ ."/NewsMLValidator/classes/DocumentProperties.php");

    $xml = ltrim($xml);

    $validator = new NewsMLValidationRunner();

    $result = $validator->run($xml);

    return $result;
}

/**
 * Gets userinput or exits the API with a 400 bad request.
 * @return File|null|string
 */
function getUserInput()
{

    // Checks request method, returns 400 if not HTTP POST
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
        output("The REQUEST_METHOD was not POST or not set");
        httpHeader::setHeader(400); // Bad Request
        exitAPI();
    }

    $userInput = null;

    $file = fileUpload();
    if ($file != null) {
        $userInput = $file;
    } else {
        $userInput = getRequestParams();
    }

    return $userInput;

}


/**
 * @return String - Contains the parameters from the user
 * @author Stefan Grunert (Aptoma - Dr. Publish)
 */
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


/**
 * Gets content from uploaded file
 * @return File contents
 *
 * @author Michael Pande
 */
function fileUpload()
{
    if ($_FILES == null || isset($_FILES["uploaded_file"]) || isset($_FILES["uploaded_file"]["tmp_name"])) {
        return null;
    }
    return file_get_contents($_FILES["uploaded_file"]["tmp_name"]);
}

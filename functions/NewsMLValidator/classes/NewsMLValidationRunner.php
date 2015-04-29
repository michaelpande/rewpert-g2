<?php

class NewsMLValidationRunner
{
    /**
     * Validates NewsML-G2 against XSD schema
     *
     * @param string $newsML NewsML-G2 document
     * @return NewsMLValidationResult
     */
    public function run($newsML)
    {
        $newsMLValidation = new NewsMLValidationResult('NewsML-G2');
        $newsMLValidation->documentOffsetLine = -1;
        try {
            $docProps = DocumentDetector::detectNewsML($newsML);
            $schema = $this->loadNewsMLSchema($docProps);
        } catch (Exception $e) {
            $newsMLValidation->hasError = true;
            $newsMLValidation->message = "Can't validate NewsML. " . $e->getMessage();
            return $newsMLValidation;
        }
        $newsMLValidation->detections = $docProps;
        $dom = DocumentDetector::loadNewsMLDom($newsML);
        libxml_use_internal_errors(true);
        $res = $dom->schemaValidateSource($schema);
        $numErrors = 0;
        if ($res == false) {
            $newsMLValidation->hasError = true;
            $fileA = mbsplit("\n", $newsML);
            $errors = libxml_get_errors();
            $numErrors = count($errors);
            foreach ($errors as $error) {
                $error->markup = (isset($fileA[$error->line - 1]) ? trim($fileA[$error->line - 1]) : '');
                $newsMLValidation->errors[] = $error;
            }
        }
        $newsMLValidation->passed = $numErrors === 0;
        $newsMLValidation->numErrors = $numErrors;
        $newsMLValidation->hasError = $numErrors > 0;
        $newsMLValidation->message = NewsMLValidationResult::generateMessage($numErrors);
        $newsMLValidation->service = "XSD schema by IPTC";
        return $newsMLValidation;
    }

    private function loadNewsMLSchema(DocumentProperties $documentProperties)
    {
        $conformance = ucfirst(strtolower($documentProperties->conformance));
        $version = $documentProperties->version;
        $dirname = dirname(__FILE__) . "/../xsd/newsml/";
        if($conformance == null) {
            $filename = "NewsML-G2_{$version}-spec-All-Core.xsd";
        } else {
            $filename = "NewsML-G2_{$version}-spec-All-{$conformance}.xsd";
        }

        if (file_exists($dirname . "/" . $filename)) {
            $documentProperties->validationSchema = $filename;
            return file_get_contents($dirname . "/" . $filename);
        } else {
            $availableSchemas = scandir($dirname);
            foreach ($availableSchemas as $schema) {
                $latestVersion = 19;
                if ($schema == '.' || $schema == '..') {
                    continue;
                }
                $res = array();
                preg_match("/(NewsML-G2_2\.)(\d{1,2})(.*)/", $schema, $res);
                if (isset($res[2])) {
                    if ((int)$res[2] > $latestVersion) {
                        $latestVersion = $res[2];
                    }
                }
            }
            $filename = "NewsML-G2_2.{$latestVersion}-spec-All-{$conformance}.xsd";
            if (file_exists($dirname . "/" . $filename)) {
                $documentProperties->versionMismatch = true;
                $documentProperties->validationSchema = $filename;

                return file_get_contents($dirname . "/" . $filename);
            }
            throw new Exception("XSD file '{$filename}' not found");
        }
    }

}
<?php

/**
 * Class NewsMLValidation
 *
 * Keeps validation results
 */
class NewsMLValidationResult
{
    public $validatedStandard = '';
    public $hasStandardElements = true;
    public $passed;
    public $errors = array();
    public $detections;
    public $guid = '';
    public $hasError = false;
    public $numErrors = 0;
    public $message = '';
    public $service;
    public $documentOffsetLine = 0;


    public function __construct($validatedStandard)
    {
        $this->validatedStandard = $validatedStandard;
    }

    public static function generateMessage($numErrors)
    {
        if ($numErrors > 0) {
            $m = $numErrors . " error";
            if ($numErrors > 1) {
                $m .= "s";
            }
            $m .= " detected.";
            return $m;
        } else {
            return "No errors detected.";
        }
    }
}
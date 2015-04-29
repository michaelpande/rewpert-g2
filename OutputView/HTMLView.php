<?php

/*$v = new HTMLView();

$v->setTitle("Tittel");
$v->newHeading("Overskrift 1", "id_1");
$v->newHeading("Overskrift 2", "id_2");
$v->newHeading("Overskrift 2", "id_2");
$v->appendTo("Dette er en kort tekst", $v->p, "id_1");
$v->appendParagraph("Dette er en tekst til");

$v->setDescription("Dette er en beskrivende tekst");
$v->appendSubheading("Test");
$v->appendParagraph("Dette er en teksfefet til");
$v->render();*/
?>

<?php

// CHECK IF ID exist


class HTMLView {


    private $title;
    private $description;
    private $items;
    private $lastIndex = 0;

    public $h2 = "h2";
    public $h3 = "h3";
    public $h4 = "h4";
    public $p = "p";
    public $strong = "strong";


    public function __construct(){

        $this->items = array();

    }

    public function setTitle($str){
        $str = $this->getString($str);

        $this->title = $str;
    }

    public function setDescription($desc){
        $this->description = $desc;
    }

    public function newHeading($str){
        $str = $this->getString($str);

        $this->lastIndex++;


        $this->items[$this->lastIndex] = array(
                                "text"=>$str,
                                "elements" => array()
                            );
        return $this->lastIndex;
    }


    public function appendParagraph($str){

        if(!isset($this->lastIndex)){
            return;
        }
        $this->appendTo($str,$this->p, $this->lastIndex);
    }

    public function appendParagraphTo($str, $id){
        $this->appendTo($str,$this->p, $id);
    }

    public function appendStrongText($str){


        if(!isset($this->lastIndex)){
            return;
        }
        $this->appendTo($str,$this->strong, $this->lastIndex);
    }

    public function appendStrongTextTo($str, $id){
        $this->appendTo($str,$this->strong, $id);
    }




    public function appendSubheading($str){
        if(!isset($this->lastIndex)){
            return;
        }
        $this->appendTo($str,$this->h3, $this->lastIndex);
    }

    public function appendSubheadingTo($str, $id){

        $this->appendTo($str,$this->h3, $id);
    }




    public function appendTo($str, $type, $id){
        $str = $this->getString($str);

        $element = array(
            "text" => $str,
            "type" => $type
        );

        array_push($this->items[$id]['elements'],$element);
    }



    private function getString($str){
        if(!is_string($str)){
            $str = var_export($str, true);
        }
        return $str;
    }



    public function render(){

        $doc = new DOMDocument('1.0');
        $doc->formatOutput = true;
        $root = $doc->createElement('html');
        $root = $doc->appendChild($root);

        $head = $doc->createElement('head');
        $head = $root->appendChild($head);

        $title = $doc->createElement('title');
        $title = $head->appendChild($title);

        $text = $doc->createTextNode($this->title);
        $title->appendChild($text);


        // Style
        $css= $doc->createElement("style");
        $text = $doc->createTextNode(file_get_contents(__DIR__ . "/outputCSS.css"));
        $css->appendChild($text);
        $head->appendChild($css);







        $body = $doc->createElement('body');
        $body = $root->appendChild($body);

        $heading= $doc->createElement("h1");
        $text = $doc->createTextNode($this->title);
        $heading->appendChild($text);
        $body->appendChild($heading);

        $description = $doc->createElement("p");
        $text = $doc->createTextNode($this->description);
        $description->appendChild($text);
        $body->appendChild($description);





        // Add headers
        foreach($this->items as $headingArray){
            $heading= $doc->createElement("h2");
            $text = $doc->createTextNode($headingArray["text"]);
            $heading->appendChild($text);
            $body->appendChild($heading);

            foreach($headingArray['elements'] as $item){
                $element = $doc->createElement($item['type']);
                $text = $doc->createTextNode($item['text']);
                $element->appendChild($text);
                $body->appendChild($element);
            }


        }


        echo '<!DOCTYPE html>';
        echo $doc->saveHTML();
    }





}

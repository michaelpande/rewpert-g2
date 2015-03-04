<link href='http://fonts.googleapis.com/css?family=Droid+Sans|Lato|Oswald|Merriweather|Open+Sans|Roboto+Slab|Roboto:400,,200,100|Roboto+Condensed' rel='stylesheet' type='text/css'>
<link rel="stylesheet" href="css/bootstrap.css" type="text/css">
<link rel="stylesheet" href="css/main.css" type="text/css">
<script src="js/bootstrap.min.js"></script>
<?php include("menu.php"); ?>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.9.1.js"></script>
<script src="https://google-code-prettify.googlecode.com/svn/loader/run_prettify.js"></script>
<script type="text/javascript" src="js/jquery.jumpto.js"></script>
<link href='css/jumpto.css' rel='stylesheet' type='text/css'>
<link href='css/design.css' rel='stylesheet' type='text/css'>
<link href="css/prettify.css" type="text/css" rel="stylesheet" />
 <script>
  $(document).ready( function() {
    $(".page_container").jumpto({
      firstLevel: "> h2",
      secondLevel: "> h3",
      offset: 700
    });
  });
</script>
<div class="wrapper">
  <div class="main">
    <div class="minibanner">
      <div class="pagetitle">
        Documentation
      </div>
      <div class="minibannertitle">
        Introduction
      </div>
      <div class="minibannerexplanation">
        Here we'll explain how our code is setup.
      </div>
    </div>
    <div class="page_container">
      <div class="jumpto-block align-left">
        <h2>newsItemParse.php</h2>
<p>
NewsItemParse is the main class for parsing NewsML-G2. It starts with NewsML
either as a file or as raw xml, and then loading it whit a DOMDocument and
creates a news DOMXpath object. This class returns the information as an array.

Namespaces
There are two namespaces that are always declared in this class.
</p>
<ul>
  <li>
    http://www.w3.org/1999/xhtml
</li>
  <li>
    http://iptc.org/std/NITF/2006-10-18/
  </li>
</ul>
<p>
This namespaces may be used to find content, and sometimes headline, of the news article.
The final namespace is set to be the namespace of the outmost tag of the xml file.
If this tag does have a namespace $ns will be changed to "docNamespace:", if no
namespace is present $ns will be left as "".
The following line is used to find  the namespace.
</p>
<pre class="prettyprint">
$uri = $doc->documentElement->lookupnamespaceURI(NULL);
</pre>
<p>
Method used to find information
All the methods finding information in the NewsML document follow this general structure:
</p>
<pre class="prettyprint">
private static function getMetaFirstCreated($newsItem, $xpath) {
		global $ns;
		$firstCreated = null;

		$nodelist = $xpath->query($ns."itemMeta/".$ns."firstCreated", $newsItem);

		foreach($nodelist as $node) {
			$firstCreated = $node->nodeValue;
		}

		return $firstCreated;
	}
</pre>
<p>
It starts with "global $ns" if the XPath query in the method requires us of the
xml namespace in the in the outermost tag in the document. This either is "" or
"docNamespace:" depending on the xml file having a namespace in the outermost
tag.
$nodelist if filed whit an XPath query. The query may contain a second argument.
This is because the second argument is the result of an earlier query.
In this example the earlier query is performed in the createPost function and
finds each individual newsItems.
The foreach loop will in the example only run one time. This is because there is
only one firstCreated tag in the NewsML document. Depending on what tag the
query is trying to find $nodelist may have different length.
Status code
The status code field in $returnArray is sent to the RESTApi to indicate if
there is anything wrong whit the NewsML document. All information that status
code is set to is standard http header responses. It is set to 200 if everything
is OK, or 400 if content, headline, guid or version number is missing from the
NewsML document. The only exception is when the newsItem contains some short of
remote content media. In this case the content and headline field may remain
empty.
$returnArray structure
The entire structure of $returnArray is shown in detail at the start of
newsitemParse.php (may move it here).
</p>
      </div> <!-- End of jumpto div -->
    </div> <!-- End of Page Container div -->
  </div> <!-- End of main DIV -->
</div> <!-- end of Wrapper -->
<body onload="prettyPrint()">
</body>
</html>

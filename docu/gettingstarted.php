<link href='http://fonts.googleapis.com/css?family=Droid+Sans|Josefin+Slab|Lato|Oswald|Merriweather|Open+Sans|Roboto+Slab|Roboto:400,200,100|Roboto+Condensed' rel='stylesheet' type='text/css'>
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
      secondLevel: "> h3"
    });
  });
</script>
<div class="wrapper">
  <div class="main">
    <div class="minibanner">
      <div class="pagetitle">
        Getting started
      </div>
      <div class="minibannertitle">
        Introduction
      </div>
      <div class="minibannerexplanation">
        Here we cover things for getting started, like requirements and setting
        up.
      </div>
    </div>
    <div class="page_container">
      <div class="jumpto-block">
        <h2>API Examples</h2>
        <h3>Powershell</h3>
          <p>An example of how to push content with PowerShell.</p>
          <pre class="prettyprint">
$wc = new-object System.Net.WebClient
ls *.xml | foreach {
$wc.UploadFile(
'http://localhost/Wordpress/wp-content/plugins/RESTful%20NewsML-G2/RESTApi.
php?key=1c1b15f59f5c4046ba5061e7465ff832b6272cb7830aa685c7ad1adcd17822ba&debug=true'
, $_.FullName )
}
</pre>
    </div>

    </div> <!-- End of Page Container div -->

    <div class="other">
      <p>Any information you don't want included in the menu, is put under any other class than "jumpto-block"</p>
    </div>
  </div>
</div> <!-- end of Wrapper -->
<body onload="prettyPrint()">
</body>
</html>

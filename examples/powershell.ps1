$wc = new-object System.Net.WebClient
ls *.xml | foreach { 
    $wc.UploadFile( 'http://localhost/Wordpress/wp-content/plugins/RESTful%20NewsML-G2/RESTApi.php?key=1c1b15f59f5c4046ba5061e7465ff832b6272cb7830aa685c7ad1adcd17822ba&debug=true', $_.FullName )
}
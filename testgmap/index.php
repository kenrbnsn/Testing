<?php
include ('../../dbconfig.php');
$db = new mysqli("localhost", $dbuser, $dbpass, "rbnsn_weavers1");
if (isset($_POST['x'])) {
	$sql = "INSERT into visitor_information (visitor_name, visitor_latitude, visitor_longitude) values (?, ?, ?)";
	$stmt = $db->prepare($sql);
	if($stmt === false) {
		exit (json_encode(array('ret'=>'Not OK','sql'=>$sql,'DB_Error'=>$db->error)));
	}
	$stmt->bind_param('sdd',$_POST['name'],$_POST['lat'],$_POST['long']);
	$stmt->execute();
	exit (json_encode(array('ret'=>'Ok','lat'=>$_POST['lat'],'long'=>$_POST['long'])));
}

?>
<!DOCTYPE HTML>
<html>
<head>
<script src="//maps.google.com/maps/api/js?sensor=false"></script>
<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position){
        var latitude = position.coords.latitude;
        var longitude = position.coords.longitude;
        var coords = new google.maps.LatLng(latitude, longitude);
        var mapOptions = {
            zoom: 15,
            center: coords,
            mapTypeControl: true,
            navigationControlOptions: {
                style: google.maps.NavigationControlStyle.SMALL
            },
            mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            map = new google.maps.Map(
                document.getElementById("mapContainer"), mapOptions
                );
            var infoContent = "Latitude: " + latitude + '<br>Longitude: ' + longitude;
					  var infowindow = new google.maps.InfoWindow({
					      content: infoContent
					  });

            var marker = new google.maps.Marker({
                    position: coords,
                    map: map,
                    title: "Your current location!",
            });
					  google.maps.event.addListener(marker, 'click', function() {
					    infowindow.open(map,marker);
					  });

	        $.post("<?php echo $_SERVER['PHP_SELF']?>",{x:1, name:'Test User', lat: position.coords.latitude, long: position.coords.longitude},function(data) {
	        	if (data.ret == 'Ok') {
		        	alert('Latitude: ' + data.lat + ', Longitude: ' + data.long);
		        } else {
		        	alert('Problem with ' + data.sql + ', DB Error' + data.DB_Error);
		        }
	        },'json');
        });
    }else {
        alert("Geolocation API is not supported in your browser.");
    }
  });
</script>
<style type="text/css">
#mapContainer {
    height: 500px;
    width: 800px;
    border:10px solid #eaeaea;
}
</style>
</head>
<body>
    <div id="mapContainer"></div>
</body>
</html>

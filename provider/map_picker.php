<?php
require_once '../includes/config.php';
if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'service') redirect('../login.php');

$saved_location = $_GET['location'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pick Location</title>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places"></script>
    <style>
        #map { height: 400px; width: 100%; }
        #search-box { width: 100%; padding: 10px; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="wrapper">
    <h3>Chagua Eneo Kwenye Ramani</h3>
    <input id="search-box" type="text" placeholder="Tafuta eneo...">
    <div id="map"></div>
    <form action="save_location.php" method="POST">
        <input type="hidden" id="location_data" name="location_data">
        <input type="hidden" id="place_name" name="place_name">
        <button type="submit" class="premium-btn">Hifadhi Eneo</button>
    </form>
</div>
<script>
    let map, marker, geocoder;
    function initMap() {
        const defaultLoc = { lat: -6.7924, lng: 39.2083 }; // Dar es Salaam
        map = new google.maps.Map(document.getElementById("map"), { zoom: 12, center: defaultLoc });
        geocoder = new google.maps.Geocoder();
        marker = new google.maps.Marker({ map: map, draggable: true });
        
        google.maps.event.addListener(marker, 'dragend', function() {
            geocodeLatLng(marker.getPosition());
        });
        
        const input = document.getElementById("search-box");
        const searchBox = new google.maps.places.SearchBox(input);
        map.addListener('bounds_changed', () => searchBox.setBounds(map.getBounds()));
        searchBox.addListener('places_changed', () => {
            const places = searchBox.getPlaces();
            if (places.length === 0) return;
            const bounds = new google.maps.LatLngBounds();
            places.forEach(place => {
                if (!place.geometry) return;
                bounds.extend(place.geometry.location);
                marker.setPosition(place.geometry.location);
                map.fitBounds(bounds);
                geocodeLatLng(place.geometry.location);
            });
        });
        
        function geocodeLatLng(latlng) {
            geocoder.geocode({ location: latlng }, (results, status) => {
                if (status === "OK" && results[0]) {
                    document.getElementById("location_data").value = latlng.lat() + "," + latlng.lng();
                    document.getElementById("place_name").value = results[0].formatted_address;
                }
            });
        }
        
        // If saved location exists
        <?php if($saved_location): ?>
            let coords = "<?php echo $saved_location; ?>".split(',');
            let latlng = new google.maps.LatLng(parseFloat(coords[0]), parseFloat(coords[1]));
            map.setCenter(latlng);
            marker.setPosition(latlng);
            geocodeLatLng(latlng);
        <?php endif; ?>
    }
    window.onload = initMap;
</script>
</body>
</html>

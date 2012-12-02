<?php
include_once('../config/app.php');
include_once('../config/tracker.php');
include_once('../lib/helpers.php');

# grab data from parameters

$vehicle      = $_REQUEST['vehicle'];
$gps_time     = $_REQUEST['time'];
$gps_lat      = $_REQUEST['lat'];
$gps_lon      = $_REQUEST['lon'];
$gps_alt      = $_REQUEST['alt'];
$gps_heading  = $_REQUEST['heading'];
$gps_speed    = $_REQUEST['speed'];
$picture      = $_REQUEST['picture'];
$temp_inside  = $_REQUEST['temp_inside'];
$data         = $_REQUEST['data'];
$pass         = $_REQUEST['pass'];
$callsign     = $_REQUEST['callsign'];
$sequence     = $_REQUEST['seq'];

if($pass != $track_password) {
  die("Wrong password!");
}

$callsign = $callsign ? $callsign : '';

# fix GPS and time parameters

$gps_lat = gps_to_decimal($gps_lat);
$gps_lon = gps_to_decimal($gps_lon);
$gps_time = gps_convert_time($gps_time);

# use UNIX timestamp in microseconds for position_id

$server_time = time();

$pos = array(
	'position_id' => (int)(microtime(true)*1000),
	'vehicle' => $vehicle,
	'server_time' => date('Y-m-d H:i:s', $server_time),
	'gps_time' => date('Y-m-d H:i:s', $gps_time),
	'gps_lat' => $gps_lat,
	'gps_lon' => $gps_lon,
	'gps_alt' => $gps_alt,
	'gps_heading' => $gps_heading,
	'gps_speed' => $gps_speed,
	'picture' => $picture,
	'temp_inside' => $temp_inside,
	'data' => $data,
	'callsign' => $callsign,
	'sequence' => $sequence
);

# write JSON to positions.json

$comma = (file_exists($pos_file_name) && filesize($pos_file_name) > 0) ? ',' : '';
file_put_contents($pos_file_name, $comma . json_encode($pos), FILE_APPEND);

echo json_encode($pos);
?>

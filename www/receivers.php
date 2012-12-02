<?php
include_once('../config/app.php');

header("Content-Type: application/json");

# update receivers cache file if file creation time is older than 30 seconds

if (time() > filemtime($receivers_file_name) + 30) {
	$data = file_get_contents("http://habitat.habhub.org/transition/receivers");
	file_put_contents($receivers_file_name, $data);
	echo $data;
} else {
	$ctx = stream_context_create(array("http" => array("header" => "Connection: close")));
	readfile($receivers_file_name, false, $ctx);
}

?>

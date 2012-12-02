<?php

// this full URL of the data.php file for AJAX
$site_url = "http://spacenear.us/tracker";

// track password
$track_password = "aurora";

// components

#$gmap_key = "ABQIAAAAeMKbTrWgK8lcXVYCoI2vVBS-snRltXcxX7Bn7KMk0LMTZbe4mxTc5UK_2QgLgj_g2DKhvk6A5z00Eg";
$gmap_key = "AIzaSyAYljEJrpY1sTWuop3T1JY0WrejdejbdHE";

$ustream_enable = false;
$ustream_embed = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="220" height="136" id="utv426081"><param name="flashvars" value="autoplay=false&amp;brand=embed&amp;cid=554950&amp;locale=en_US"/><param name="allowfullscreen" value="true"/><param name="allowscriptaccess" value="always"/><param name="movie" value="http://www.ustream.tv/flash/live/1/554950?v3=1"/><embed flashvars="autoplay=false&amp;brand=embed&amp;cid=554950&amp;locale=en_US" width="220" height="136" allowfullscreen="true" allowscriptaccess="always" id="utv426081" name="utv_n_56824" src="http://www.ustream.tv/flash/live/1/554950?v3=1" type="application/x-shockwave-flash" /></object>';

$twitter_enable = false;
//$twitter_user = "darksidelemm";
$twitter_user = "adamcudworth";

$mibbit_enable = false;
$mibbit_server = "irc.freenode.net";
$mibbit_channel = "#highaltitude";

$pictures_enable = false;

$logo_enable = true;
//$logo_url = "images/whitestar.png";
$logo_url = "images/tracker-logo.png";

$heading_enable = true;
//$heading_mission="SpaceNear.Us HAB Tracker";
$heading_mission="The Register / LOHAN";
$heading_info = <<<HTML
01/12/2012 Brightwalton, UK</br>
Launching 10:00AM UTC (ISH)</br>
TRUSS - 434.225Mhz USB 100 baud RTTY 7N2</br>
ROLL  - 434.650Mhz USB 100 baud RTTY 7N2</br>
ROCK  - Iridium Based Tracker</br>
SPEARS- 434.075Mhz USB 50 baud RTTY 8N1</br>
<a href="http://www.batc.tv/streams/lohan" target="_blank">Stream from Launch Site here</a>
<hr>
Come chat to us: <a href="http://webchat.freenode.net/?channels=highaltitude" target="_blank">#highaltitude</a> on irc.freenode.net
HTML;

$fullscreen = true;

$graph_enable = true;

// landing prediction

$default_burst_alt = 30000;			// m
$default_ascent_rate = 5;				// m/s
$default_descent_rate = 6;			// m/s
$throttle_predictions = 60;    // max once every minute per vehicle

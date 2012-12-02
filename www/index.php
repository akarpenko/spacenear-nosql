<?php
include_once('../config/tracker.php');

// Put IE9 into compatibility mode
if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/MSIE 9/', $_SERVER['HTTP_USER_AGENT']))
  Header("X-UA-Compatible: IE=8");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v style="height:100%">

<head>

<![if !IE]>
<link rel="icon" type="image/png" href="/favicon.ico" />
<![endif]>

<!-- Enable VML in IE -->
<!--[if IE]>
<?php echo '<'.'?import namespace="v" implementation="#default#VML" ?'.'>'."\n"; ?>
<style type="text/css">
v\: polyline
v\: line
v\: shape
v\: fill
v\: stroke {
  behavior: url(#default#VML);
}
</style>
<![endif]-->

<link rel="stylesheet" type="text/css" href="css/embed.css" /> 
<link rel="stylesheet" type="text/css" href="css/slimbox2.css" /> 

<!--[if IE 6]>
<link rel="stylesheet" type="text/css" href="css/ie_fixes.css" />
<![endif]-->
<!--[if IE 7]>
<link rel="stylesheet" type="text/css" href="css/ie_fixes.css" />
<![endif]-->
<!--[if IE 8]>
<link rel="stylesheet" type="text/css" href="css/ie_fixes.css" />
<![endif]-->

<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=<?php echo $gmap_key; ?>" type="text/javascript"></script>
<script src="js/PolylineEncoder.js" type="text/javascript"></script>
<script src="js/balloonmarker.js" type="text/javascript"></script>
<script src="js/BDCCCircle.js" type="text/javascript"></script>
<script src="js/selector.js" type="text/javascript"></script>

<!--[if IE]><script language="javascript" type="text/javascript" src="js/flot/excanvas.min.js"></script><![endif]--> 
<script src="js/flot/jquery.js" type="text/javascript"></script> 
<script src="js/slimbox2.js" type="text/javascript"></script>

<script src="js/jquery-ui-1.7.2.custom.min.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" /> 

<script src="js/jquery.cookie.js" type="text/javascript"></script>
<script src="js/jquery.hint.js" type="text/javascript"></script>

<?php
if($graph_enable) {
?>
<script src="js/flot/jquery.flot.js" type="text/javascript"></script> 
<?php
}
?>

<?php
/******************************************************************************
*  Note: Javascript code now located in js/tracker.js.php                     *
******************************************************************************/
?>
<script src="js/tracker.js.php" type="text/javascript"></script>

<title><?php echo $heading_mission; ?> : Tracker</title>
<?php include_once("analyticstracking.php") ?>
</head>

<body onload="load()" onunload="unload()" style="height: 100%; overflow: hidden;">

  <div id="tabs" style="position: absolute; top: 0; bottom: 0; left: 0; right: 0;">
    <ul>
       <li><a href="#map_container">Map</a></li>
       <li><a href="#info_container">Settings</a></li>
    </ul>
    <div id="map_container" style="height: 100%;">
      <div id="map" style="position: absolute; top: 38px; bottom: 22px; left: 3px; right: 3px;"></div>
      <div id="vehicle_container"></div>
      <a href="http://spacenear.us" target="_blank" id="powered_by" style="bottom: 56px;">powered by spacenear.us</a>
      <div id="mission_title">
        <img src="images/drag_handle.png" class="handle" />
        <div class="mission_name"><?php echo $heading_mission; ?></div>
        <div class="mission_info"><?php echo $heading_info; ?></div>
        <div class="mission_bar collapse"></div>
      </div>
    </div>
    <div id="info_container">
      <h4>Settings:</h4>
      <ul id="settings_list" style="padding-top: 10px;">
        <li>
          <form name="input" action="." method="get">
          Filter: <input type="text" name="filter" id="filter" title="Semicolon separated callsigns to track" />
          <script type="text/javascript">$('#filter').hint("callsign1;callsign2");</script>
          <input type="submit" value="Submit" />
          </form>
        </li>
        <br />
        <li>
          <input id="show_notams" type="checkbox" value="show_notams" />
          <label for="show_notams">Show UK NOTAMs</label>
        </li>
        <br />
        <li>
          <a href="admin" target="_blank">Go to administration</a>
        </li>
      </ul>
      <h4 style="padding-top: 20px;">Info:</h4>
      <p style="padding-top: 10px;">
      Join our chat on IRC: <a href="http://webchat.freenode.net/?channels=<?php echo $mibbit_channel; ?>" target="_blank"><?php echo $mibbit_channel; ?></a> on <?php echo $mibbit_server; ?>
      </p>
      <p style="padding-top: 20px; font-size: 8pt; color: #777;">
      This tracker uses landing prediction code by <a href="http://github.com/rjw57/cusf-landing-prediction" target="_blank">CU Spaceflight</a>. 
      </p>
    </div>
  </div>
  <div class="status_box" style="position:absolute; bottom:0; left:0; right:0">
    <div id="status_bar">&nbsp;</div>
    <div id="link_bar"></div>
  </div>

</body>
</html>

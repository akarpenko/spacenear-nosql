<?php
/*
  This converts coordinates of the form 5129.8844N and 00003.1758W to the decimal form.
  It also properly handles coordinates already in decimal form.
*/
function gps_to_decimal($n) {
  $c = substr($n, -1);
  $multiplier = 1.0;
  if($c == 'S' || $c == 'W') {
    $multiplier = -1.0;
  } else if($c != 'N' && $c != 'E') {
    return $n;  // $n is already a decimal coordinate, so return it
  }  
  preg_match('/([0-9]*)([0-9][0-9]\.[0-9]*)/', $n, $matches);
  $degrees = $matches[1];
  $minutes = $matches[2];
  
  return round($multiplier * ($degrees + $minutes / 60), 6);
}

function gps_convert_time($t) {
  // example: 233748.999
  if(strlen($t) == 6 || strpos($t, ".") !== false) {
    $hour   = substr($t, 0, 2);
    $minute = substr($t, 2, 2);
    $second = substr($t, 4, 2);
    return mktime($hour, $minute, $second);
  } else {
    $year   = substr($t, 0, 4);
    $month  = substr($t, 4, 2);
    $day    = substr($t, 6, 2);
    $hour   = substr($t, 8, 2);
    $minute = substr($t, 10, 2);
    $second = substr($t, 12, 2);
    return mktime($hour, $minute, $second, $month, $day, $year);
  }
}
?>

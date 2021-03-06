<?php
  header('Content-Type: text/javascript');
  
  include_once('../../config/tracker.php');
?>

//<![CDATA[

var mission_id = 0;
var position_id = 0;
var data_url = "/data.php";
var receivers_url = "/receivers.php";
var predictions_url = "";
var vehicle_names = [];
var vehicles = [];

var receiver_names = [];
var receivers = [];  

var num_updates = 0;
var got_positions = false;
var zoomed_in = false;
var max_positions = 0; // maximum number of positions that ajax request should return (0 means no maximum)
var selector = null;
var window_selector = null;
var cursor = null;
var selected_vehicle = 0;
var follow_vehicle = -1;

var signals = null;
var signals_seq = -1;  

var car_index = 0;
var car_colors = ["blue", "red", "green", "yellow"];
var balloon_index = 0;
var balloon_colors = ["red", "blue", "green", "yellow"];

var color_table = new Array("#aa0000", "#0000ff", "#006633", "#ff6600", "#003366", "#CC3333","#663366" ,"#000000");

var map = null;
var polylineEncoder = new PolylineEncoder();

var notamOverlay = null;

// preload images
img_spinner = new Image(100,25); 
img_spinner.src = "images/spinner.gif"; 

function load() {
<?php
  if(!$fullscreen) {
?>
	$("#map_container").resizable({ handles: "s", minHeight: 250,
																	resize: function(event, ui) {
																						map.checkResize();
																					},
																	stop: function(event, ui) {
																						$.cookie("map_height", $("#map_container").height(), { expires: 365 });
																					}
															  });
<?php
  }
?>

  if (GBrowserIsCompatible()) {
    mapDiv = document.getElementById("map");
    map = new GMap2(mapDiv);
    map.addControl(new GLargeMapControl3D());
     map.addControl(new GScaleControl());
    map.addMapType(G_PHYSICAL_MAP);
    map.addControl(new GHierarchicalMapTypeControl());
    map.enableScrollWheelZoom();
    //map.enableContinuousZoom();
    
    // set minimum zoom
    var mapTypes = map.getMapTypes();
    for(var i = 0; i < mapTypes.length; i++){
			//mapTypes[i].getMaximumResolution = function(latlng){ return 12;};
			mapTypes[i].getMinimumResolution = function(latlng){ return 2;};
		}

<?php
    if($kml_overlay_url != '') {
?>
    geoXml = new GGeoXml("<?php echo $kml_overlay_url; ?>");
    map.addOverlay(geoXml);
<?php
    }
?>

    map.setCenter(new GLatLng(0, 0), 2);

    //----- Stop page scrolling if wheel over map ----
    GEvent.addDomListener(mapDiv, "DOMMouseScroll", wheelevent);
    mapDiv.onmousewheel = wheelevent;
		
    GEvent.addListener(map, 'zoomend',
    										function() {
    											map.closeInfoWindow();
										  		if(window_selector) {
														map.removeOverlay(window_selector);
														window_selector = null;
													}
                          updateZoom();
    										});
    
    GEvent.addListener(map, 'mousemove', function(latlng) { mouseMove(latlng); });
    GEvent.addListener(map, 'click', function(overlay, latlng, overlaylatlng) { mouseClick(latlng ? latlng : (overlay != map.getInfoWindow() ? overlaylatlng : null)); });
		GEvent.addListener(map, 'infowindowclose', function(latlng) { infoWindowCloseEvent(); });

<?php
  if(!$fullscreen) {
?>
		var map_height = $.cookie("map_height");
	 	if(map_height) {
			$("#map_container").height(map_height + 'px');
			map.checkResize();
		}
<?php
  } else { // if fulscreen
?>
    $("#show_notams").change(function() {
      if($("#show_notams").is(':checked')) {
        if(!notamOverlay) notamOverlay = new GGeoXml("http://www.habhub.org/notam_overlay/notam_and_restrict.kml");
        map.addOverlay(notamOverlay);
      } else {
        map.removeOverlay(notamOverlay)
      }
    });

    $(window).resize(resizeVehicleContainer);

    $("#mission_title").draggable({containment: '#map', handle: '.handle'});
    $(".mission_bar").click(function() {
      width = $("#mission_title").width();
      $("#mission_title").width(width); 
      $(".mission_info").slideToggle(200, function() {
        $(".mission_bar").toggleClass("expand");
      });
    });
<?php
  }
?>
  }

  startAjax();

<?php
if($graph_enable) {
?>
  $("#tabs").tabs();
  $('#tabs').bind('tabsshow', function(event, ui) {
    var vehicle_name = ui.panel.id.substring(ui.panel.id.indexOf("-")+1);
    for(vehicle_index = 0; vehicle_index < vehicles.length; vehicle_index++) {
      var tabname = vehicle_names[vehicle_index].replace("/", "_");
      if(tabname == vehicle_name) {
        redrawPlot(vehicle_index);
        break;
      }
    }
  });
<?php
}
?>
}

function unload() {
  GUnload();
}

// resizes vehicle_container so that scroll bars are shown only when necessary
function resizeVehicleContainer() {
  if(vehicles.length > 0) {
    var v = $("#vehicle" + (vehicles.length-1));
    if(v && v.position()) {
      var p = v.position().top + $("#vehicle_container").scrollTop() + v.height() + 12;
      height = Math.min($("#map").height() - 39, p);
      $("#vehicle_container").height(height);
      if(height < p) {
        $("#vehicle_container").addClass("vehicle_container_scroll");
      } else {
        $("#vehicle_container").removeClass("vehicle_container_scroll");
      }
    }
  }
}

//----- Stop page scrolling if wheel over map ----
function wheelevent(e) {
    if (!e) e = window.event;
    if (e.preventDefault) e.preventDefault();
    e.returnValue = false;
}

var num_pics = 0;

function insertPicture(thumb_url, pic_url, text, title) {
  var table_pics = $('#picture_table_pics');
  table_pics.append('<td colspan="2" style="text-align: center;"><a href="' + pic_url + '" rel="lightbox[pics]" title="' + title + '"><img src="' + thumb_url + '" /></a></td>');
  var table_txts = $('#picture_table_txts');
  table_txts.append('<td style="border-right: 0; font-size: 15px; font-weight:bold; color: gray;" width="32">' + (num_pics+1) + '</td>');
  table_txts.append('<td align=left>' + text + '</td>');
  
  num_pics++;

  // update slimbox
  if (!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)) {
    jQuery(function($) {
      $("a[rel^='lightbox']").slimbox({/* Put custom options here */}, null, function(el) {
        return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel));
      });
    });
  }

  //$('#scroll_pane').animate({scrollLeft: '' + $('#scroll_pane').width() + 'px'}, 1000);
}

function addPicture(vehicle, gps_time, gps_lat, gps_lon, gps_alt, gps_heading, gps_speed, picture) {
  insertPicture("pics/thumb-" + picture, "pics/" + picture, "<b>Time:</b> " + gps_time.split(" ")[1] + "<br /><b>Altitude:</b> " + gps_alt + " m<br />", "Altitude: " + gps_alt + " m");
}

function panTo(vehicle_index) {
  map.panTo(new GLatLng(vehicles[vehicle_index].curr_position.gps_lat, vehicles[vehicle_index].curr_position.gps_lon));
}

function optional(caption, value, postfix) {
  // if(value && value != '') {
  if (value && value !== '') {
    if(value.indexOf("=") == -1) {
      return "<b>" + caption + ":</b> " + value + postfix + "<br />"
    } else {
      var a = value.split(";");
      var result = "";
      for(var i = 0; i < a.length; i++) {
        var b = a[i].split("=");
        result += "<b>" + b[0] + ":</b> " + b[1] + "<br />"
      }
      return result;
    }
  } else {
    return "";
  }
}

function title_case(s) {
  return s.replace(/\w\S*/g, function(txt) {
    return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
  });
}

function guess_name(key) {
  return title_case(key.replace(/_/g, " "));
}

function habitat_data(jsondata) {
  var keys = {
    "ascentrate": "Ascent Rate",
    "battery_percent": "Battery",
    "temperature_external": "Temperature, External",
    "pressure_internal": "Pressure, Internal",
    "voltage_solar_1": "Voltage, Solar 1",
    "voltage_solar_2": "Voltage, Solar 2",
    "light_red": "Light (Red)",
    "light_green": "Light (Green)",
    "light_blue": "Light (Blue)",
    "gas_a": "Gas (A)",
    "gas_b": "Gas (B)",
    "gas_co2": "Gas (CO)",
    "gas_combustible": "Gas (Combustible)",
    "radiation": "Radiation (CPM)",
    "temperature_radio": "Temperature, Radio",
    "uplink_rssi": "Uplink RSSI"
  }

  var hide_keys = {
    "spam": true,
    "battery_millivolts": true,
    "temperature_internal_x10": true,
    "uplink_rssi_raw": true
  }

  var suffixes = {
    "battery": "V",
    "temperature": "&deg;C",
    "temperature_external": "&deg;C",
    "temperature_radio": "&deg;C",
    "pressure": "Pa",
    "voltage_solar_1": "V",
    "voltage_solar_2": "V",
    "battery_percent": "%",
    "uplink_rssi": "dBm",
    "rssi_last_message": "dBm",
    "rssi_floor": "dBm",
    "iss_azimuth": "&deg;",
    "iss_elevation": "&deg;",
    "spam": ""
  }

  try
  {
    if (jsondata === undefined || jsondata === "")
      return "";

    var data = eval("(" + jsondata + ")");
    var output = [];

    for (var key in data) {
      if (hide_keys[key] === true)
        continue;

      var name = "", suffix = "";
      if (keys[key] !== undefined)
        name = keys[key];
      else
        name = guess_name(key);

      if (suffixes[key] !== undefined)
        suffix = " " + suffixes[key];

      output.push("<b>" + name + ":</b> " + data[key] + suffix + "<br />");
    }

    output.sort();
    return output.join(" ");
  }
  catch (error)
  {
    if (jsondata && jsondata != '')
      return "<b>Data:</b> " + jsondata + "<br /> ";
    else
      return "";
  }
}

function atlas_data(caption, value, postfix) {
  var fields = ["Crystal Temp (&deg;C)", "PID Controller", "Internal Temp (&deg;C)", "External Temp (&deg;C)", "Light Sensor"];
  var result = "";
  var extra = 0;
  if(value.indexOf(";") != -1) {
    values = value.split(";");
    for(var i = 0; i < values.length; i++) {
      if(i < fields.length) {
        caption = fields[i];
      } else {
        caption = "Extra " + extra;
        extra++;
      }
      var data = values[i];
      result += "<b>" + caption + ":</b> " + data + "<br />"
    }
  } else if(value != '') {
    result = "<b>" + caption + ":</b> " + value + postfix + "<br />"
  }
  return result;
}

function whitestar_data(caption, value, postfix) {
  var fields = ["Ice", "External Temp (&deg;C)", "Humidity", "Speed", "Climb", "Ballast Remaining"];
  var result = "";
  var extra = 0;
  if(value.indexOf(";") != -1) {
    values = value.split(";");
    for(var i = 0; i < values.length; i++) {
      if(i < fields.length) {
        caption = fields[i];
      } else {
        caption = "Extra " + extra;
        extra++;
      }
      var data = values[i];
      result += "<b>" + caption + ":</b> " + data + "<br />"
    }
  } else if(value != '') {
    result = "<b>" + caption + ":</b> " + value + postfix + "<br />"
  }
  return result;
}

function horus_data(caption, value, postfix) {
  var fields = ["GPS Sats", "Internal Temp (&deg;C)", "External Temp (&deg;C)", "Battery (V)"];
  var result = "";
  var extra = 0;
  if(value.indexOf(";") != -1) {
    values = value.split(";");
    for(var i = 0; i < values.length; i++) {
      if(i < fields.length) {
        caption = fields[i];
      } else {
        caption = "Extra " + extra;
        extra++;
      }
      var data = values[i];
      result += "<b>" + caption + ":</b> " + data + "<br />"
    }
  } else if(value != '') {
    result = "<b>" + caption + ":</b> " + value + postfix + "<br />"
  }
  return result;
}

function darkside_data(caption, value, postfix) {
  var fields = ["Internal Temp (&deg;C)", "Air Pressure (hPa)", "Battery (raw ADC val)"];
  var result = "";
  var extra = 0;
  if(value.indexOf(";") != -1) {
    values = value.split(";");
    for(var i = 0; i < values.length; i++) {
      if(i < fields.length) {
        caption = fields[i];
      } else {
        caption = "Extra " + extra;
        extra++;
      }
      var data = values[i];
      result += "<b>" + caption + ":</b> " + data + "<br />"
    }
  } else if(value != '') {
    result = "<b>" + caption + ":</b> " + value + postfix + "<br />"
  }
  return result;
}

function picochu_data(caption, value, postfix) {
  var fields = ["External Temp (&deg;C)", "Internal Temp (&deg;C)"];
  var result = "";
  var extra = 0;
  if(value.indexOf(";") != -1) {
    values = value.split(";");
    for(var i = 0; i < values.length; i++) {
      if(i < fields.length) {
        caption = fields[i];
      } else {
        caption = "Extra " + extra;
        extra++;
      }
      var data = values[i];
      result += "<b>" + caption + ":</b> " + data + "<br />"
    }
  } else if(value != '') {
    result = "<b>" + caption + ":</b> " + value + postfix + "<br />"
  }
  return result;
}

function apex_data(caption, value, postfix) {
  var fields = ["GPS Sats", "Internal Temperature (&deg;C)",
                "External Temperature (&deg;C)", "Pressure (mbar)",
                "Battery Voltage (V)", "IRD 1 (Counts/30sec)", 
                "IRD 2 (Counts/30sec)",
                "Light", "RSSI (%)"];
  var result = "";
  var extra = 0;
  if(value.indexOf(";") != -1) {
    values = value.split(";");
    for(var i = 0; i < values.length; i++) {
      if(i < fields.length) {
        caption = fields[i];
      } else {
        caption = "Extra " + extra;
        extra++;
      }
      var data = values[i];
      if(i == 3) {        // pressure
        data = roundNumber(parseInt(data, 16)/3.312, 2);
      } else if(i == 4) { // battery voltage
        data = roundNumber(10*parseInt(data, 16)/4096, 2);
      } else if(i == 5 || i == 6) { // IRD counts
        data = parseInt(data, 16);
      } else if(i == 8 ) { // RSSI
        data = roundNumber(100*parseInt(data, 16)/256, 2);
      }
      result += "<b>" + caption + ":</b> " + data + "<br />"
    }
  } else if(value != '') {
    result = "<b>" + caption + ":</b> " + value + postfix + "<br />"
  }
  return result;
}


function updateAltitude(index) {
  var pixel_altitude = 0;
  var zoom = map.getZoom();
  var position = vehicles[index].curr_position;
  if(zoom > 18) zoom = 18;
  if(position.gps_alt > 0) {
    pixel_altitude = Math.round(position.gps_alt/(1000/3)*(zoom/18.0));
  }
  if(position.vehicle.toLowerCase().indexOf("iss") != -1) {
    pixel_altitude = Math.round(40000/(1000/3)*(zoom/18.0));
  } else if(position.gps_alt > 55000) {
    position.gps_alt = 55000;
  }
  vehicles[index].marker.setAltitude(pixel_altitude);
}

function updateZoom() {
  for(var index = 0; index < vehicles.length; index++) {
    if(vehicles[index].vehicle_type == "balloon") {
      updateAltitude(index);
    }
  } 
}

function togglePath(index) {
	vehicles[index].path_enabled = !vehicles[index].path_enabled;
	if(vehicles[index].path_enabled) {
		$('#btn_path_' + index).addClass('vehicle_button_enabled');
	} else {
		$('#btn_path_' + index).removeClass('vehicle_button_enabled');
	}
	updatePolyline(index);
}

function followVehicle(index) {
	if(follow_vehicle != -1) {
		vehicles[follow_vehicle].follow = false;
		$('#btn_follow_' + follow_vehicle).removeClass('vehicle_button_enabled');
	}
	
	if(follow_vehicle == index) {
		follow_vehicle = -1;
	} else {
		follow_vehicle = index;
		vehicles[follow_vehicle].follow = true;
		$('#btn_follow_' + follow_vehicle).addClass('vehicle_button_enabled');
	}
}

function vehicleButtons(index) {
	var html = '<div class="vehicle_buttons">'
	         +    '<span class="vehicle_button" onclick="panTo(' + index + ')">Pan To</span>'
					 + ' | <span id="btn_path_' + index + '" class="vehicle_button' + (vehicles[index].path_enabled ? ' vehicle_button_enabled' : '') + '" onclick="togglePath(' + index + ')">Path</span>'
					 + ' | <span id="btn_follow_' + index + '" class="vehicle_button' + (vehicles[index].follow ? ' vehicle_button_enabled' : '') + '" onclick="followVehicle(' + index + ')">Follow</span>'
					 + '</div>';
	return html;
}

function roundNumber(number, digits) {
  var multiple = Math.pow(10, digits);
  var rndedNum = Math.round(number * multiple) / multiple;
  return rndedNum;
}

function updateVehicleInfo(index, position) {
  var latlng = new GLatLng(position.gps_lat, position.gps_lon);
  vehicles[index].marker.setLatLng(latlng);
  if(vehicles[index].vehicle_type == "balloon") {
    updateAltitude(index);
    var horizon_km = Math.sqrt(12.756 * position.gps_alt);
    vehicles[index].horizon_circle.setRadiusKm(Math.round(horizon_km));     
    vehicles[index].horizon_circle.setPoint(latlng);

    if(vehicles[index].subhorizon_circle) {
      // see: http://ukhas.org.uk/communication:lineofsight
      var el = 5.0; // elevation above horizon
      var rad = 6378.10; // radius of earth
      var h = position.gps_alt / 1000; // height above ground
      
      var elva = el * Math.PI / 180.0;
      var slant = rad*(Math.cos(Math.PI/2+elva)+Math.sqrt(Math.pow(Math.cos(Math.PI/2+elva),2)+h*(2*rad+h)/Math.pow(rad,2)));
      var x = Math.acos((Math.pow(rad,2)+Math.pow(rad+h,2)-Math.pow(slant,2))/(2*rad*(rad+h)))*rad;
   
      var subhorizon_km = x;
      vehicles[index].subhorizon_circle.setRadiusKm(Math.round(subhorizon_km));
      vehicles[index].subhorizon_circle.setPoint(latlng);
    }

    var landed =  vehicles[index].max_alt > 1000
               && vehicles[index].ascent_rate < 1.0
               && position.gps_alt < 300;

    if(landed) {
      vehicles[index].marker.setMode("landed");
    } else if(vehicles[index].ascent_rate > -3.0 ||
              vehicle_names[vehicle_index] == "wb8elk2") {
    	vehicles[index].marker.setMode("balloon");
    } else {
    	vehicles[index].marker.setMode("parachute");
    }
  }
  updateMarkerInfo(index, position);

  var pixels = Math.round(position.gps_alt / 500) + 1;
  if (pixels < 0) {
    pixels = 0;
  } else if (pixels >= 98) {
    pixels = 98;
  }

  var image = vehicles[index].image_src;

  //var container = $('vehicle' + index);
  var container = document.getElementById('vehicle' + index);
  if (container == null) {
    container = document.createElement('div');
    container.setAttribute('className', 'vehicle_box');
    container.setAttribute('class', 'vehicle_box');
    container.setAttribute('id', 'vehicle' + index);
    //container.setAttribute('style', 'background: url(' + image + ') no-repeat center;');
    //$('#vehicle_container').appendChild(container);
    document.getElementById("vehicle_container").appendChild(container);
    
  }

  ascent_text = position.gps_alt != 0 ? (' <b>Rate:</b> ' + vehicles[index].ascent_rate.toFixed(1) + ' m/s') : '';

  var html = '  <div class="altitude_container" id="altitude_' + index + '">'
           + '      <div class="altitude" style="font-size:0px; border-top: solid white ' + (98 - pixels) + 'px; height: ' + pixels + 'px;"></div>'
           + '  </div>'
           + '  <div class="vehicle_info_wrapper">'
           + '    <div class="vehicle_info" style="background: url(' + image + ') no-repeat top right;">'
           + '      <b style="font-size:12px">' + vehicle_names[index] + '</b><br />';

  /* XXX OSIRIS INVISIBILITY */  if (vehicle_names[index] != "OSIRIS" && vehicle_names[index] != "PETUNIA")
  html +=    '      <b>Time:</b> ' + position.gps_time + '<br />'
           + '      <b>Position:</b> ' + roundNumber(position.gps_lat, 6) + ',' + roundNumber(position.gps_lon, 6) + '<br />'
           + '      <b>Altitude:</b> ' + position.gps_alt + ' m' + ascent_text + '<br />'
           + (vehicles[index].vehicle_type == "balloon" ? ('<b>Max. Altitude:</b> ' + vehicles[index].max_alt + ' m<br />') : '');


  /* XXX OSIRIS INVISIBILITY */  else
  html +=    '      <b>Sentence ID:</b> ' + position.sequence + '<br />';

  html +=    optional("Heading", position.gps_heading, "&deg;")
           + optional("Speed", position.gps_speed, " km/h")
           + optional("Temperature", position.temp_inside, "C");

     /* Use habitat data! Just add the keys you want to the habitat_data function 
      *    if (position.vehicle == "wb8elk2")
      *      html += whitestar_data("Data", position.data, "");
      *    else if (position.vehicle == "apex")
      *      html += apex_data("Data", position.data, "");
      *    else if (position.vehicle == "picochu-1")
      *      html += picochu_data("Data", position.data, "");
      *    else if (position.vehicle == "DARKSIDE")
      *      html += darkside_data("Dara", position.data, "");
      *    else
      *      // html += optional("Data", position.data, "");
     */

  html +=    habitat_data(position.data);
  html +=    optional("Receivers", position.callsign.split(",").join(", "), "");
  /* XXX OSIRIS INVISIBILITY */  if (vehicle_names[index] != "OSIRIS" && vehicle_names[index] != "PETUNIA")
  html +=    vehicleButtons(index);

  html +=    '    </div>'
           + '  </div>'
           + '  <div style="clear:both;"></div>';
  container.innerHTML = html;
  
  //$("#debug_box").html("Height: " + container.offsetHeight);
  $("#altitude_" + index).css("margin-top", container.offsetHeight - 100);
}

function getInfoHtml(vehicle_index, position) {
  var html = "<b>" + position.vehicle + "</b><br />"
           + '<p style="font-size: 8pt;">'
           + "<b>Time:</b> " + position.gps_time + "<br />"
           + "<b>Position:</b> " + position.gps_lat + "," + position.gps_lon + "<br />"
           + "<b>Altitude:</b> " + position.gps_alt + " m<br />"
            + optional("Heading", position.gps_heading, "&deg;")
           + optional("Speed", position.gps_speed, " km/h")
           + optional("Temperature", position.temp_inside, "C");
    /* Use habitat_data ! 
           if (position.vehicle == "wb8elk2")
             html += whitestar_data("Data", position.data, "");
           else if (position.vehicle == "apex")
             html += apex_data("Data", position.data, "");
           else if (position.vehicle == "picochu-1")
             html += picochu_data("Data", position.data, "");
           else
             // html += optional("Data", position.data, "");
    */
             html += habitat_data(position.data);
           html += optional("Receivers", position.callsign.split(",").join(", "), "")
           + "</p>";
	return html;
}

function updateMarkerInfo(vehicle_index, position) {
  var marker = vehicles[vehicle_index].marker;
	var html = getInfoHtml(vehicle_index, position);
  GEvent.clearListeners(marker);
  GEvent.addListener(marker, "click", function() {
    marker.openInfoWindowHtml(html);
  });
  return marker;
}

function showSignals(index, position) {
  if(!position) return;
  if(signals_seq == position.sequence) return;
  hideSignals();
  signals_seq = position.sequence;
  signals = [];
  if(position.callsign == "") return;
  var callsigns = position.callsign.split(",");
  for(var i = 0; i < callsigns.length; i++) {
  	// check receivers first:
    var r_index = $.inArray(callsigns[i], receiver_names);
    if(r_index != -1) {
      var receiver = receivers[r_index];
      var latlngs = [];
      latlngs.push(new GLatLng(position.gps_lat, position.gps_lon));
      latlngs.push(new GLatLng(receiver.lat, receiver.lon));
      var poly = new GPolyline(latlngs, "#00FF00", 2, 0.5);
      signals.push(poly);
      map.addOverlay(poly);
    } else {
    	// if nothing found, check vehicles:
    	var vehicle_index;
    	var r = new RegExp(callsigns[i], "i"); // check if callsign is contained in vehicle name
    	for(vehicle_index = 0; vehicle_index < vehicle_names.length; vehicle_index++) {
    		if(vehicle_names[vehicle_index].search(r) != -1) break;
    	}
    	if(vehicle_index != vehicle_names.length
         && vehicle_names[vehicle_index].toLowerCase() != callsigns[i].toLowerCase()) {
	      var vehicle_pos = vehicles[vehicle_index].curr_position;
	      var latlngs = [];
	      latlngs.push(new GLatLng(position.gps_lat, position.gps_lon));
	      latlngs.push(new GLatLng(vehicle_pos.gps_lat, vehicle_pos.gps_lon));
	      var poly = new GPolyline(latlngs, "#00FF00", 2, 0.5);
	      signals.push(poly);
	      map.addOverlay(poly);
      }
    }
  }
}

function hideSignals() {
  if(!signals) return;
  for(var i = 0; i < signals.length; i++) {
    map.removeOverlay(signals[i]);
  }
  signals = null;
  signals_seq = -1;
}

function showSelector(latlng, color) {
  if(!selector) {
    selector = new Selector(latlng, {color: color});
    map.addOverlay(selector);
  } else {
  	selector.setLatLng(latlng);
  	selector.setColor(color);
  }
}

function hideSelector() {
	if(selector) {
	  map.removeOverlay(selector);
	  selector = null;
  }
}

function mouseVehiclePos(latlng) {
	if(!latlng) {
		return null;
	}
	var vehicle_index = -1, pos, best_dist = 9999999999;
	var p1 = map.fromLatLngToDivPixel(latlng);
	for(var v = 0; v < vehicles.length; v++) {
		if(!vehicles[v].path_enabled) {
			continue;
		}
		for(var i = 0; i < vehicles[v].line.length; i++) { // note: skip the last pos
			var p2 = map.fromLatLngToDivPixel(vehicles[v].line[i]);
			var dist = Math.sqrt(Math.pow(p2.x-p1.x, 2) + Math.pow(p2.y-p1.y,2));
			if(dist < best_dist) {
				best_dist = dist;
				vehicle_index = v;
				pos = i;
			}
		}
	}
	
	if(vehicle_index != -1 && best_dist < 16) {
		return {vehicle_index: vehicle_index, pos: pos};
	} else {
		return null;
	}
}

function mouseMove(latlng) {
	var result = mouseVehiclePos(latlng);
	
	if(result) {
    if(result.pos < vehicles[result.vehicle_index].line.length-1) { // do not show marker for current pos
		  showSelector(vehicles[result.vehicle_index].line[result.pos], color_table[result.vehicle_index]);
		  selector.setHtml(getInfoHtml(result.vehicle_index, vehicles[result.vehicle_index].positions[result.pos]));
    } else {
      hideSelector();
    }
    showSignals(result.vehicle_index, vehicles[result.vehicle_index].positions[result.pos]);
	} else {
		hideSelector();
    hideSignals();
	}
}

function mouseClick(latlng) {
	if(selector) {
		if(window_selector) {
			map.removeOverlay(window_selector);
			window_selector = null;
		}
		window_selector = selector.copy();
		map.addOverlay(window_selector);
		window_selector.openInfoWindow();
	}
}

function infoWindowCloseEvent() {
	if(!selector && window_selector) {
		map.removeOverlay(window_selector);
		window_selector = null;
	}
}

function pad(number, length) {
  var str = '' + number;
  while (str.length < length) {
      str = '0' + str;
  }
  return str;
}

function addMarker(icon, latlng, html) {
	var marker = new GMarker(latlng, {icon: icon});
  map.addOverlay(marker);
  
  GEvent.addListener(marker, "click", function() {
    marker.openInfoWindowHtml(html);
  });
  
  return marker;
}

function removePrediction(vehicle_index) {
  if(vehicles[vehicle_index].prediction_polyline) {
    map.removeOverlay(vehicles[vehicle_index].prediction_polyline);
    vehicles[vehicle_index].prediction_polyline = null;
  }
  if(vehicles[vehicle_index].prediction_target) {
    map.removeOverlay(vehicles[vehicle_index].prediction_target);
    vehicles[vehicle_index].prediction_target = null;
  }
  if(vehicles[vehicle_index].prediction_burst) {
    map.removeOverlay(vehicles[vehicle_index].prediction_burst);
    vehicles[vehicle_index].prediction_burst = null;
  }
}

function redrawPrediction(vehicle_index) {
	var data = vehicles[vehicle_index].prediction.data;
	if(data.warnings || data.errors) return;
		var line = [];
		var latlng = null;
		var max_alt = -99999;
		var latlng_burst = null;
		var	burst_index = 0;
		for(var i = 0; i < data.length; i++) {
			latlng = new GLatLng(data[i].lat, data[i].lon);
			line.push(latlng); 
			if(parseFloat(data[i].alt) > max_alt) {
				max_alt = parseFloat(data[i].alt);
				latlng_burst = latlng;
				burst_index = i;
			}
		}
		var polyline = polylineEncoder.dpEncodeToGPolyline(line, color_table[vehicle_index], 2, 0.3);
		removePrediction(vehicle_index);
  map.addOverlay(polyline);
		
		if(vehicle_names[vehicle_index] != "wb8elk2") { // WhiteStar
	  var image_src = "images/markers/target-" + balloon_colors[vehicles[vehicle_index].color_index] + ".png";
	  var icon = new GIcon();
	  icon.image = image_src;
	  icon.iconSize = new GSize(25,25);
	  icon.iconAnchor = new GPoint(13,13);
	  icon.infoWindowAnchor = new GPoint(13,5);
	  
	  var time = new Date(data[data.length-1].time * 1000);
	  var time_string = pad(time.getUTCHours(), 2) + ':' + pad(time.getUTCMinutes(), 2) + ' UTC';
	  var html = '<b>Predicted Landing</b><br />'
	  				 + '<p style="font-size: 10pt;">'
	  				 + data[data.length-1].lat + ', ' + data[data.length-1].lon + ' at ' + time_string
	  				 + '</p>';
	  vehicles[vehicle_index].prediction_target = addMarker(icon, latlng, html);
  } else {
    vehicles[vehicle_index].prediction_target = null;
  }
  
		if(burst_index != 0 && vehicle_names[vehicle_index] != "wb8elk2") {
	  var image_src = "images/markers/balloon-pop.png";
	  var icon = new GIcon();
	  icon.image = image_src;
	  icon.iconSize = new GSize(35,32);
	  icon.iconAnchor = new GPoint(18,15);
	  icon.infoWindowAnchor = new GPoint(18,5);
	  
	  var time = new Date(data[burst_index].time * 1000);
	  var time_string = pad(time.getUTCHours(), 2) + ':' + pad(time.getUTCMinutes(), 2) + ' UTC';
	  var html = '<b>Predicted Burst</b><br />'
	  				 + '<p style="font-size: 10pt;">'
	  				 + data[burst_index].lat + ', ' + data[burst_index].lon + ', ' + Math.round(data[burst_index].alt) + ' m at ' + time_string
	  				 + '</p>';
	  vehicles[vehicle_index].prediction_burst = addMarker(icon, latlng_burst, html);
  } else {
  	vehicles[vehicle_index].prediction_burst = null;
  }
		
		vehicles[vehicle_index].prediction_polyline = polyline;
}

function updatePolyline(vehicle_index) {
  if (got_positions && vehicles[vehicle_index].line.length > 1) {
    if (vehicles[vehicle_index].polyline) {
      map.removeOverlay(vehicles[vehicle_index].polyline);
    }
    vehicles[vehicle_index].polyline = polylineEncoder.dpEncodeToGPolyline(vehicles[vehicle_index].line, color_table[vehicle_index]);

    if(vehicles[vehicle_index].path_enabled) {
    	map.addOverlay(vehicles[vehicle_index].polyline);
    }
  }
}

function convert_time(gps_time) {
  // example: "2009-05-28 20:29:47"
  year = parseInt(gps_time.substring(0, 4), 10);
  month = parseInt(gps_time.substring(5, 7), 10);
  day = parseInt(gps_time.substring(8, 10), 10);
  hour = parseInt(gps_time.substring(11, 13), 10);
  minute = parseInt(gps_time.substring(14, 16), 10);
  second = parseInt(gps_time.substring(17), 10);
 
  date = new Date();
  date.setUTCFullYear(year);
  date.setUTCMonth(month-1);
  date.setUTCDate(day);
  date.setUTCHours(hour);
  date.setUTCMinutes(minute);
  date.setUTCSeconds(second);
  
  return date.getTime() / 1000; // seconds since 1/1/1970 @ 12:00 AM
}

function findPosition(positions, other) {
  var sequence = other.sequence;
	if (!sequence || sequence == '' || sequence == 0) {
		return -1;
	}
	for(var i = 0 ; i < positions.length; i++) {
		if(positions[i].sequence != sequence) continue;
		if(positions[i].gps_lat != other.gps_lat) continue;
		if(positions[i].gps_lon != other.gps_lon) continue;
		if(positions[i].gps_time != other.gps_time) continue;
    return i;
	}
	return -1;
}

<?php /*
// inserts position and returns current position
function insertPosition(vehicle, position) {
  if (!position.sequence || position.sequence == '' || position.sequence == 0) {
    vehicle.positions.push(position);
    vehicle.line.push(new GLatLng(position.gps_lat, position.gps_lon));
    return position;
  }

  // handle sequence number reset:
  if(vehicle.positions.length > 0 &&
     vehicle.positions[vehicle.positions.length-1].sequence > position.sequence + 10) {
    vehicle.positions.push(position);
    vehicle.line.push(new GLatLng(position.gps_lat, position.gps_lon));
    return position;
  }

  var i;
  for(i = vehicle.positions.length-1; i >= -1; i--) {
    if(i >= 0 && vehicle.positions[i].sequence < position.sequence) {
      break;
    }
  }
  vehicle.positions.splice(i+1, 0, position);
  // add the point to form new lines
  vehicle.line.splice(i+1, 0, new GLatLng(position.gps_lat, position.gps_lon));
  return vehicle.positions[vehicle.positions.length-1];
} 
*/ ?>  

function insertPosition(vehicle, position) {
  var i;
  for(i = vehicle.positions.length-1; i >= -1; i--) {
    if(i >= 0 && convert_time(vehicle.positions[i].server_time) < convert_time(position.server_time)) {
      break;
    }
  }
  vehicle.positions.splice(i+1, 0, position);
  // add the point to form new lines
  vehicle.line.splice(i+1, 0, new GLatLng(position.gps_lat, position.gps_lon));
<?php    
  if($graph_enable) {
?>
    var curr_time = convert_time(position.server_time)*1000;
    vehicle.alt_data.splice(i+1, 0, new Array(curr_time, position.gps_alt));
<?php
  }
?>
  return vehicle.positions[vehicle.positions.length-1];
}

function addPosition(position) { 
  // vehicle info
  //vehicle_names.include(position.vehicle);

  position.sequence = position.sequence ? parseInt(position.sequence, 10) : null;
  
  if($.inArray(position.vehicle, vehicle_names) == -1) {
    vehicle_names.push(position.vehicle);
    var marker = null;
    var vehicle_type = "";
    var horizon_circle = null;
    var subhorizon_circle = null;
    var point = new GLatLng(position.gps_lat, position.gps_lon);
    var image_src = "";
    var color_index = 0;
    if(position.vehicle.search(/(chase)|(car)/i) != -1  // whitelist
        && position.vehicle.search(/icarus/i) == -1) {  // blacklist
      vehicle_type = "car";
      color_index = car_index++;
      var c = color_index % car_colors.length;
      var image_src = "images/markers/car-" + car_colors[c] + ".png";
      var icon = new GIcon();
      icon.image = image_src;
      icon.iconSize = new GSize(55,25);
      icon.iconAnchor = new GPoint(27,22);
      icon.infoWindowAnchor = new GPoint(27,5);
      marker = new GMarker(point, {icon: icon});
    } else {
      vehicle_type = "balloon";
      color_index = balloon_index++;
      var c = color_index % balloon_colors.length;
      
      if(position.vehicle.toLowerCase().indexOf("iss") != -1) {
        image_src = "icons/iss.png";
        marker = new BalloonMarker(point, {color: "iss", width: 50, height: 38});
      } else if (position.vehicle.toLowerCase() == "osiris" || position.vehicle.toLowerCase() == "petunia") {
        /* XXX OSIRIS INVISIBLE */
        image_src = "images/markers/balloon-invisible.png";
        marker = new BalloonMarker(point, {color: "invisible"});
      } else {
        image_src = "images/markers/balloon-" + balloon_colors[c] + ".png";
        marker = new BalloonMarker(point, {color: balloon_colors[c]});
      }      

      var circle_radius_km = 1;
      //horizon_circle = new CircleOverlay(point, circle_radius_km, "#336699", 1, 1, '#336699', 0.0);
      horizon_circle = new BDCCCircle(point,
                                      circle_radius_km, // radius in km
                                      "#0000FF",        // stroke color
                                      3,                // stroke weight
                                      0.3,              // stroke opacity
                                      false,            // fill (true/false)
                                      "#FFFF00",        // fill color
                                      0.5,              // fill opacity
                                      "Horizon of " + position.vehicle); // tooltip 
      map.addOverlay(horizon_circle);
      subhorizon_circle = new BDCCCircle(point,
                                      circle_radius_km, // radius in km
                                      "#00FF00",        // stroke color
                                      5,                // stroke weight
                                      0.3,              // stroke opacity
                                      false,            // fill (true/false)
                                      "#FFFF00",        // fill color
                                      0.5,              // fill opacity
                                      "5 degree horizon of " + position.vehicle); // tooltip
      map.addOverlay(subhorizon_circle);


<?php
if($graph_enable) {
?>
      var tabname = position.vehicle.replace("/", "_");
      $("#tabs").append('<div id="tabs-'+tabname+'">' +
                        '<div id="graph-'+tabname+'" style="width:600px;height:300px;">' +
                        '</div>' +
                        '</div>');
      $("#tabs").tabs("add", "#tabs-"+tabname, tabname<?php if($fullscreen) echo ", balloon_index"; ?>);
<?php
}
?>
    }
    var vehicle_info = {vehicle_type: vehicle_type,
                        marker: marker,
                        image_src: image_src,
                        horizon_circle: horizon_circle,
                        subhorizon_circle: subhorizon_circle,
                        num_positions: 0,
                        positions: [],
                        curr_position: position,
                        line: [],
                        polyline: null,
                        prediction: null,
                        ascent_rate: 0.0,
                        max_alt: parseFloat(position.gps_alt),
                        alt_data: new Array(),
                        path_enabled: vehicle_type == "balloon" && position.vehicle.toLowerCase().indexOf("iss") == -1,
                        follow: false,
                        color_index: color_index};
    vehicles.push(vehicle_info);
    map.addOverlay(marker);
  }
  var vehicle_index = $.inArray(position.vehicle, vehicle_names);
  
  //
  // check if sequence already exists
  //
  var seq = findPosition(vehicles[vehicle_index].positions, position);
  if(seq == -1) {
	  vehicles[vehicle_index].num_positions++;

    var prev_position = vehicles[vehicle_index].curr_position;
    vehicles[vehicle_index].curr_position = insertPosition(vehicles[vehicle_index], position);
    
    // calculate ascent rate:
    if(vehicles[vehicle_index].num_positions == 0) {
      vehicles[vehicle_index].ascent_rate = 0;
    } else if(vehicles[vehicle_index].curr_position != prev_position) { // if not out-of-order
      dt = convert_time(position.gps_time)
         - convert_time(prev_position.gps_time);
      if(dt != 0) {
        rate = (position.gps_alt - prev_position.gps_alt) / dt;
        vehicles[vehicle_index].ascent_rate = 0.7 * rate
                                            + 0.3 * vehicles[vehicle_index].ascent_rate;
      }
    }
	} else { // sequence already exists
    // Doesn't work in IE7 or IE8 :-(
    // if (vehicles[vehicle_index].positions[seq].callsign.split(",").indexOf(position.callsign) === -1)
    if (("," + vehicles[vehicle_index].positions[seq].callsign + ",").indexOf("," + position.callsign + ",") === -1)
      vehicles[vehicle_index].positions[seq].callsign += "," + position.callsign;
	}
  if(parseFloat(position.gps_alt) > vehicles[vehicle_index].max_alt) {
    vehicles[vehicle_index].max_alt = parseFloat(position.gps_alt);
  }
}

function refresh() {
  status = '<img src="images/spinner.gif" width="16" height="16" alt="" /> Refreshing ...';
  $('#status_bar').html(status);
  
  $.ajax({
    type: "GET",
    url: data_url,
    data: "format=json&position_id=" + position_id + "&max_positions=" + max_positions,
    dataType: "json",
    success: function(response, textStatus) {
                update(response);
                $('#status_bar').html(status);
             },
    complete: function(request, textStatus) {
                // remove the spinner
                $('status_bar').removeClass('ajax_loading');
                periodical = setTimeout(refresh, timer_seconds * 1000);
           }
  });
}

function refreshReceivers() {
  $('#status_bar').html('<img src="images/spinner.gif" width="16" height="16" alt="" /> Refreshing receivers ...');

  $.ajax({
    type: "GET",
    url: receivers_url,
    data: "",
    dataType: "json",
    success: function(response, textStatus) {
                updateReceivers(response);
             },
    complete: function(request, textStatus) {
                // remove the spinner
                $('status_bar').removeClass('ajax_loading');
                $('#status_bar').html(status);
                periodical_listeners = setTimeout(refreshReceivers, 60 * 1000);
           }
  });
}

function refreshPredictions() {
  if (predictions_url == null || predictions_url == '') return;
  $('#status_bar').html('<img src="images/spinner.gif" width="16" height="16" alt="" /> Refreshing predictions ...');

  $.ajax({
    type: "GET",
    url: predictions_url,
    data: "",
    dataType: "json",
    success: function(response, textStatus) {
                updatePredictions(response);
             },
    complete: function(request, textStatus) {
                // remove the spinner
                $('status_bar').removeClass('ajax_loading');
                $('#status_bar').html(status);
                periodical_predictions = setTimeout(refreshPredictions, 2 * timer_seconds * 1000);
           }
  });
}

var periodical, periodical_receivers, periodical_predictions;
var timer_seconds = 10;

function startAjax() {
  // prevent insane clicks to start numerous requests
  clearTimeout(periodical);
  clearTimeout(periodical_receivers);
  clearTimeout(periodical_predictions);

  /* a bit of fancy styles */
  $('status_bar').innerHTML = '<img src="images/spinner.gif" width="16" height="16" alt="" /> Refreshing ...';

  // the periodical starts here, the * 1000 is because milliseconds required
  
  //periodical = setInterval(refresh, timer_seconds * 1000);
  refresh();

  //periodical_listeners = setInterval(refreshReceivers, 60 * 1000);
  refreshReceivers();
  
  //periodical_predictions = setInterval(refreshPredictions, 2 * timer_seconds * 1000);
  refreshPredictions();
}

function stopAjax() {
  // stop our timed ajax
  clearTimeout(periodical);
}

function centerAndZoomOnBounds(bounds) {
var center = bounds.getCenter();
var newZoom = map.getBoundsZoomLevel(bounds);
if (map.getZoom() != newZoom) {
  map.setCenter(center, newZoom);
} else {
  map.panTo(center);
}
}
function updateReceiverMarker(receiver) {
  var latlng = new GLatLng(receiver.lat, receiver.lon);
  if(!receiver.marker) {
    var icon = new GIcon();
    icon.image = "images/markers/antenna-green.png";
    icon.iconSize = new GSize(26,32);
    icon.iconAnchor = new GPoint(13,30);
    icon.infoWindowAnchor = new GPoint(13,3);
    receiver.marker = new GMarker(latlng, {icon: icon});
    map.addOverlay(receiver.marker);
  } else {
    receiver.marker.setLatLng(latlng);
  }
  var html = '<b style="font-size:12px">'
           + receiver.name + '</b>'
           + receiver.description;
  GEvent.clearListeners(receiver.marker, "click");
  GEvent.addListener(receiver.marker, "click", function() {
    receiver.marker.openInfoWindowHtml(html);
  });
}

function updateReceivers(r) {
  for(var i = 0; i < r.length; i++) {
    var lat = parseFloat(r[i].lat);
    var lon = parseFloat(r[i].lon);
    if(lat < -90 || lat > 90 || lon < -180 || lon > 180) continue;
    var r_index = $.inArray(r[i].name, receiver_names);
    var receiver = null;
    if(r_index == -1) {
      receiver_names.push(r[i].name);
      r_index = receiver_names.length - 1;
      receivers[r_index] = {marker: null};
    } 
    receiver = receivers[r_index];
    receiver.name = r[i].name;
    receiver.lat = lat;
    receiver.lon = lon;
    receiver.alt = parseFloat(r[i].alt);
    receiver.description = r[i].description;
    updateReceiverMarker(receiver);  
  }
}

function updatePredictions(r) {
	for(var i = 0; i < r.length; i++) {
		var vehicle_index = $.inArray(r[i].vehicle, vehicle_names);
		if(vehicle_index != -1) {
			if(vehicles[vehicle_index].prediction && vehicles[vehicle_index].prediction.time == r[i].time) {
				continue;
			}
      vehicles[vehicle_index].prediction = r[i];
      if(parseInt(vehicles[vehicle_index].prediction.landed) == 0) {
		  	vehicles[vehicle_index].prediction.data = eval('(' + r[i].data + ')');
			  redrawPrediction(vehicle_index);
      } else {
        removePrediction(vehicle_index); 
      }
		}
	}
}

var status = "";

function update(response) {
  if (response == null || !response.positions) {
    return;
  }
  
  num_updates++;
  var num_positions = response.positions.position.length;
  status = "Received " + num_positions + " new position" + (num_positions == 1 ? "" : "s")+ ".";

  var updated_position = false;
  var pictures_added = false;
  for (i = 0; i < response.positions.position.length; i++) {
    var position = response.positions.position[i];
    if (!position.picture) {
      addPosition(position);
      got_positions = true;
      updated_position = true;
<?php
if($pictures_enable) {
?>
    } else {
      addPicture(position.vehicle, position.gps_time, position.gps_lat, position.gps_lon, position.gps_alt, position.gps_heading, position.gps_speed, position.picture);
      pictures_added = true;
<?php
}
?>
    }
  }

  if(pictures_added) {
    $('#scroll_pane').animate({scrollLeft: '' + $('#scroll_pane').width() + 'px'}, 1000);
  }
  
  if (response.positions.position.length > 0) {
    var position = response.positions.position[response.positions.position.length-1];
    position_id = position.position_id;
  }
  
	if (updated_position) {
	  for (vehicle_index = 0; vehicle_index < vehicle_names.length; vehicle_index++) {
	  	updatePolyline(vehicle_index);
	    updateVehicleInfo(vehicle_index, vehicles[vehicle_index].curr_position);
	  }
    resizeVehicleContainer();
	  if(follow_vehicle != -1) {
	  	var pos = vehicles[follow_vehicle].curr_position;
	  	map.panTo(new GLatLng(pos.gps_lat, pos.gps_lon));
	  }
  }
  
  if (got_positions && !zoomed_in) {
  	if(vehicles[0].polyline) {
    	centerAndZoomOnBounds(vehicles[0].polyline.getBounds());
    } else {
    	map.setCenter(vehicles[0].line[0]);
    	map.setZoom(10);
    }
    map.savePosition();
    zoomed_in = true;
  }
  
<?php
if($graph_enable) {
?>     
  if(updated_position) {
    var selected = $("#tabs").tabs('option', 'selected') <?php if($fullscreen) echo "- 1" ?>;
    var index = 0;
    if(selected < 0) selected = 0;
    for(vehicle_index = 0; vehicle_index < vehicles.length; vehicle_index++) {
      if(vehicles[vehicle_index].vehicle_type == "balloon") {
        if(selected == index) {
          redrawPlot(vehicle_index);
        }
        index++;
      }
    }
  }
<?php
}
?>
}

function redrawPlot(vehicle_index) {
  var tabname = vehicle_names[vehicle_index].replace("/", "_");
  $.plot($("#graph-"+tabname),
         [{ data: vehicles[vehicle_index].alt_data, color: color_table[vehicle_index]
            /*,label: vehicle_names[vehicle_index]*/}],
                   { xaxis:
                   { mode: "time" },
                   grid: { borderWidth: 1, borderColor: "gray",
                           backgroundColor: { colors: ["#fff", "#eee"] }}});
}

//]]>

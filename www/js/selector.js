/* A Bar is a simple overlay that outlines a lat/lng bounds on the
 * map. It has a border of the given weight and color and can optionally
 * have a semi-transparent background color.
 * @param latlng {GLatLng} Point to place bar at.
 * @param opts {Object Literal} Passes configuration options - 
 *   weight, color, height, width, text, and offset.
 */
 
function Selector(latlng, opts) {
  this.latlng = latlng;

  if (!opts) opts = {};

  this.height_ = opts.height || 10;
  this.width_ = opts.width || 10;
  this.color_ = opts.color;
  this.clicked_ = 0;
  this.html_ = opts.html? opts.html : "";
}

/* Selector extends GOverlay class from the Google Maps API
 */
Selector.prototype = new GOverlay();

/* Creates the DIV representing this Selector.
 * @param map {GMap2} Map that bar overlay is added to.
 */
Selector.prototype.initialize = function(map) {
  var me = this;

  // Create the DIV representing our Selector
  var div = document.createElement("div");
  div.style.backgroundColor = me.color_;
  div.style.border = "1px solid white";
  div.style.position = "absolute";
  div.style.paddingLeft = "0px";
  div.style.cursor = 'pointer';
  div.style.opacity = '0.6';
  
  GEvent.addDomListener(div, "click", function(event) {
    me.clicked_ = 1;
    GEvent.trigger(me, "click");
  });

  map.getPane(G_MAP_MARKER_PANE).appendChild(div);

  this.map_ = map;
  this.div_ = div;
};

/* Remove the main DIV from the map pane
 */
Selector.prototype.remove = function() {
  this.div_.parentNode.removeChild(this.div_);
};

/* Copy our data to a new Selector
 * @return {Selector} Copy of bar
 */
Selector.prototype.copy = function() {
  var opts = {};
  opts.height = this.height_;
  opts.width = this.width_;
  opts.color = this.color_;
  opts.html = this.html_;
  return new Selector(this.latlng, opts);
};

/* Redraw the Selector based on the current projection and zoom level
 * @param force {boolean} Helps decide whether to redraw overlay
 */
Selector.prototype.redraw = function(force) {

  // We only need to redraw if the coordinate system has changed
  if (!force) return;

  // Calculate the DIV coordinates of two opposite corners 
  // of our bounds to get the size and position of our Selector
  if(!this.latlng) return;
  var divPixel = this.map_.fromLatLngToDivPixel(this.latlng);

  // Now position our DIV based on the DIV coordinates of our bounds
  this.div_.style.width = this.width_ + "px";
  this.div_.style.left = (divPixel.x - this.width_/2 - 1) + "px"
  this.div_.style.height = this.height_ + "px";
  this.div_.style.top = (divPixel.y - this.height_/2 - 1) + "px";
};

Selector.prototype.getZIndex = function(m) {
  return GOverlay.getZIndex(marker.getPoint().lat())-m.clicked*10000;
}

Selector.prototype.getPoint = function() {
  return this.latlng;
};

Selector.prototype.setStyle = function(style) {
  for (s in style) {
    this.div_.style[s] = style[s];
  }
};

Selector.prototype.openInfoWindowHtml = function(html) {
  this.map_.openInfoWindowHtml(this.latlng, html, {pixelOffset: new GSize(0, 0)});
}

Selector.prototype.openInfoWindow = function() {
  this.openInfoWindowHtml(this.html_);
}

Selector.prototype.setLatLng = function(latlng) {
  this.latlng = latlng;
  this.redraw(true);
}

Selector.prototype.setColor = function(color) {
  this.color_ = color;
  this.div_.style.backgroundColor = color;
  this.redraw(true);
}

Selector.prototype.setHtml = function(html) {
  this.html_ = html;
}




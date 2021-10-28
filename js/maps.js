// This is only used by getcookie.php and webstats.php
// We do this at the bottom of the page:
// $APIKEY = require_once("/var/www/bartonphillipsnet/google-maps-key/maps-apikey");
// <script src="https://bartonphillips.net/js/maps.js"></script>
// <script src="https://maps.googleapis.com/maps/api/js?key=$APIKEY&callback=initMap&v=weekly" async></script>

'use strict';

var map, marker;
var geoAjax = "/geoAjax.php";

function initMap() {
  map = new google.maps.Map(document.getElementById("geocontainer"));
  marker = new google.maps.Marker();
}

// Is this a mobile device? Only for webstats and getcookie

function isMobile() {
  return window.matchMedia("(hover: none) and (pointer: coarse)").matches;
}

function initMap() {
  map = new google.maps.Map(document.getElementById("geocontainer"));
  marker = new google.maps.Marker();

  // If we have done initMap then we should check to see if this is a
  // mobile device.

  if(isMobile()) {
    $("meta[name*='viewport']").attr({"content":"width=device-width initial-scale=.7"});
  }
}

// Only getcookie uses reset

$(".reset").on("click", function() {
  let cookieName = $('span', this).text();
  let self = this; // pass this into the ajax

  // call POST to remove the cookie

  $.ajax({
    url: geoAjax,
    data: { page: 'reset', name: cookieName },
    type: 'post',
    success: function(data) {
      console.log("return: " + data);
      $(self).parent().remove();
    },
    error: function(err) {
      console.log(err);
    }
  });
});

// both webstats and getcookie use the following

$("#mygeo tbody tr").on("click", function(e) {
  let lat = parseFloat($("td:first-of-type", this).text());
  let lng = parseFloat($("td:nth-of-type(2)", this).text());
  const pos = {
    lat: lat,
    lng: lng,
  };

  marker.setOptions( {
    position: pos,
    map,
    visible: true
  });

  map.setOptions( {center: pos, zoom: 9, mapTypeId: google.maps.MapTypeId.HYBRID} );

  $(this).closest("tr").css({"background-color": "green", color: "white"});

  let h = $(this).offset().top + $(this).height() + 10;
  //console.log("height: " + $(this).height() + " top: " + $(this).offset().top + " total: " + h);

  let l = isMobile() ? "25%" : "50%";
  
  $("#outer").css({top: h, left: l}).show();

  $("#removemsg").show();
  e.stopPropagation();
  return false;
});

$("#removemsg").on("click", function() {
  $("#outer").hide();
  $(this).hide();
});

$(".OLD").closest("tr").hide();
$(".ME").closest("tr").hide(); // Hide me to start

$("#showMe").on("click", function() {
  if(!this.showit) {
    $(".ME").closest("tr").show();
    $("#showMe").html("Hide Me");
    this.showit = true;
  } else {
    $(".ME").closest("tr").hide();
    $("#showMe").html("Show Me");
    this.showit = false;
  }
});

$("#showAll").on("click", function() {
  if(!this.showall) {
    $(".OLD").closest("tr").show();
    $(".ME").closest("tr").hide();
    $("#showAll").html("Show Today");
    //$("#showMe").trigger("click");
    this.showall = true;
  } else {
    $(".OLD").closest("tr").hide();
    $("#showAll").html("Show All");
    this.showall = false;
  }
});

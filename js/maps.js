// Javascript for google maps API
// This is only used by getcookie.php and webstats.php
// We do this at the bottom of the above two pages:
// <script src="https://bartonphillips.net/js/maps.js"></script>
// <script src="https://maps.googleapis.com/maps/api/js?key=theAPIKEY&callback=initMap&v=weekly" async></script>
// The key is restricted to our domains, see:
// https://console.cloud.google.com/google/maps-apis/overview?project=barton-1324.

// IMPORTANT: geo.js makes geoAjax. It is loaded via
// SiteClass::getPageHead().

'use strict';

var map, marker;

var uiheight, uiwidth, uitop, uileft, resized = false;

function initMap() {
  map = new google.maps.Map(document.getElementById("geocontainer"));
  marker = new google.maps.Marker();
}

// Is this a mobile device? Only for webstats and getcookie

function isMobile() {
  return window.matchMedia("(hover: none) and (pointer: coarse)").matches;
}

// If we have done initMap then we should check to see if this is a
// mobile device.

if(isMobile()) {
  $("meta[name*='viewport']").attr({"content":"width=device-width initial-scale=.7"});
}

// Only getcookie uses reset

$(".reset").on("click", function() {
  let cookieName = $('span', this).text();
  let self = this; // pass this into the ajax

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

// Both webstats and getcookie use the following
// Add the geomsg.

$("#geomsg").html("Click on table row to view map.<br>" +
                  "Ctrl-Click (or long press) on the 'finger' to see only those fingers.<br>" +
                  "Ctrl-Click (or long press) again to show only today.<br>" +
                  "<button id='showMe'>Show Me</button>&nbsp;<button id='showAll'>Show All except Me</button>");

// Hide OLD and ME rows

$(".OLD").closest("tr").hide(); // Hide OLD and
$(".ME").closest("tr").hide(); // Hide me at start. Show only TODAY

$("#location li:nth-of-type(2) i.green:first-of-type, #location i, #geo i").on("click", function(e) {
  let gps = ($(this).text()).split(",");
  const pos = {
    lat: parseFloat(gps[0]),
    lng: parseFloat(gps[1])
  }
  
  marker.setOptions( {
    position: pos,
    map,
    visible: true
  });

  map.setOptions( {center: pos, zoom: 9, mapTypeId: google.maps.MapTypeId.HYBRID} );
  let t = $(this).offset().top + $(this).height() + 10;

  let h, w, l;

  if(resized) {
    h = uiheight;
    w = uiwidth;
    t = uitop;
    l = uileft;
  } else {
    if(isMobile()) {
      h = "360px";
      w = "360px";
      l= "25%";
    } else {
      h = "500px";
      w = "500px";
      l = "50%";
    }
  }
  $("#outer").css({top: t, left: l, width: w, height: h}).show();
});

// If the row is clicked show the map

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

  let t = $(this).offset().top + $(this).height() + 10;

  let h, w, l;
  
  if(resized) {
    h = uiheight;
    w = uiwidth;
    t = uitop;
    l = uileft;
  } else {
    if(isMobile()) {
      h = "360px";
      w = "360px";
      l= "25%";
    } else {
      h = "500px";
      w = "500px";
      l = "50%";
    }
  }
  $("#outer").css({top: t, left: l, width: w, height: h}).show();
});

// I don't want to have the drag be remembered!
//$("#outer").on("drag", function(e, ui) {
//  drag(ui);
//});
//function drag(ui) {
//  uitop = ui.position.top;
//  uileft = ui.position.left;
//  resized = true;
//}

function rsize(ui) {
  uiheight = ui.size.height;
  uiwidth = ui.size.width;
  uitop = ui.position.top;
  uileft = ui.position.left;
  resized = true; // I do want the resize to be remembered!
}

$("#outer").on("resize", function(e, ui) {
  rsize(ui);
});

// Two helper functions

// Make the container for geo dragagle and resizable.

$("#outer").draggable();
$("#outer").resizable();

// Display finger toggle

function finger(self) {
  let finger = $(self).text();
  if(!finger) return;
  
  $(self).closest('tbody').find('tr').each(function() {
    let other = $("td:nth-of-type(3)", this).text();
    if(other != finger) {
      $(this).hide();
    } else {
      $(this).show();
    }
  });
  $("#outer").hide();
};

// Show only today

function showtoday() {
  $("#mygeo tbody tr").hide();
  $(".TODAY").closest("tr").show();
  $(".ME").closest("tr").hide();
  let showme = $("#showMe");
  showme[0].showme = false;
  showme.html("Show Me");
  let showall = $("#showAll");
  showall[0].showall = false;
  showall.html("Show All except Me");
  $("#outer").hide(); // remove map
}

// End of helper functions

let flag, flagShowMe, flagShowAll; // flags. To start they are undefined (false).

// taphold for phones

$("#mygeo, #tracker").on("taphold", " tbody td:nth-of-type(3)", function(e) {
  if(!flag) {
    finger(this);
  } else {
    if($(e.target).closest('table')[0].id == "mygeo") {
      showtoday(); // show only today. Show Me and Show All.
    } else {
      if(!($(this).text())) return;
      hideIt('all');
      flags = {all: false, webmaster: false, bots: false, ip6: true};
    }
  }
  e.stopPropagation();
  flag = !flag;
});

// Ctrl click for desktops

$("#mygeo, #tracker").on("click", " tbody td:nth-of-type(3)", function(e) {
  if(e.ctrlKey) {
    if(!(flag)) {
      finger(this);
    } else {
      if($(e.target).closest('table')[0].id == "mygeo") {
        showtoday(); // show only today. Show Me and Show All.
      } else {
        if(!($(this).text())) return;
        hideIt('all');
        flags = {all: false, webmaster: false, bots: false, ip6: true};
      }
    }
    e.stopPropagation();
    flag = !flag;
  } else {
    $(this).parent().trigger("click");
  }
});

$("#removemsg").on("click", function() {
  $("#outer").hide();
});

$("#showMe").on("click", function() {
  if(!flagShowMe) {
    $(".ME").closest("tr").show();
    $("#showMe").html("Hide Me");
  } else {
    $(".ME").closest("tr").hide();
    $("#showMe").html("Show Me");
  }
  flagShowMe = !flagShowMe;
  $("#outer").hide();
});

$("#showAll").on("click", function() {
  if(!flagShowAll) {
    $(".TODAY").closest("tr").show();
    $(".OLD").closest("tr").show();
    $(".ME").closest("tr").hide();
    flagShowMe = false;
    $("#showMe").html("Show Me");
    $("#showAll").html("Show Today");
  } else {
    $(".TODAY").closest("tr").show();
    $(".ME").closest("tr").hide();
    $(".OLD").closest("tr").hide();
    flagShowMe = false;
    $("#showMe").html("Show Me");
    $("#showAll").html("Show All except Me");
  }
  flagShowAll = !flagShowAll;
  $("#outer").hide();
});

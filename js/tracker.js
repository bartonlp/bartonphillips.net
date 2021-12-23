// Track user activity
// Goes hand in hand with tracker.php
// BLP 2021-06-05 -- new logic
// BLP 2021-03-26 -- get lastId from the <head> section

'use strict';

var lastId;

// Post a AjaxMsg. Local function

function postAjaxMsg(msg) {
  msg = "NEW: " + msg;
  $.ajax({
    url: trackerUrl,
    data: {page: 'ajaxmsg', ipagent: true, msg: msg},
    type: 'post',
    success: function(data) {
           console.log(data);
         },
         error: function(err) {
           console.log(err);
         }
  });             
}

// BLP 2021-06-05 -- get the image from image logo's data-image
// attribute. Then set the src attribute to the 'lastId' and the
// 'image' from logo.

jQuery(document).ready(function($) {
  // logo is in banner.i.php and it is now fully instantiated. We got
  // lastId from below via the script's data-lastid attribute.

  let image = $("#logo").attr("data-image");
  $("#logo").attr('src', "https://bartonphillips.net/tracker.php?page=script&id="+lastId+"&image="+image);
});

// The rest of this is for everybody!

(function($) {
  // BLP 2021-06-05 -- We get the lastId from the script's data-lastid attribute.
  // Then we add the css link just before the script.
  
  lastId = $("script[data-lastid]").attr("data-lastid");
  $("script[data-lastid]").before('<link rel="stylesheet" href="/csstest-' + lastId + '.css" title-"blp test">');

  // BLP 2021-06-05 -- 
  // Now tracker.php and beacon.php are at bartonphillips.net
  
  var trackerUrl = "https://bartonphillips.net/tracker.php";
  var beaconUrl =  "https://bartonphillips.net/beacon.php";

  // 'start' is done weather or not 'load' happens. As long as
  // javascript works. Otherwise we should get information from the
  // image in the noscript section of banner.i.php

  $.ajax({
    url: trackerUrl,
    data: {page: 'start', id: lastId },
    type: 'post',
    success: function(data) {
      console.log(data);
    },
    error: function(err) {
      console.log(err);
    }
  });

  $(window).on("load beforeunload unload pagehide", function(e) {
    var type = e.type;
    $.ajax({
      url: trackerUrl,
      data: {page: type, 'id': lastId},
      type: 'post',
      success: function(data) {
        console.log(data);
      },
      error: function(err) {
        console.log(err);
      }
    });
  });

  // We will use beacon also

  if(navigator.sendBeacon) {
    $(window).on("pagehide unload beforeunload", function(e) {
      let which;
      
      switch(e.type) {
        case 'pagehide':
          which = 1;
          break;
        case 'unload':
          which = 2;
          break;
        case 'beforeunload':
          which = 4;
          break;
      }
      console.log("Which: " + which + ", type: " + e.type);
      
      navigator.sendBeacon(beaconUrl, JSON.stringify({'id':lastId, 'type': e.type, 'which': which}));
    });
  } else {
    var msg = "NEW: Beacon NOT SUPPORTED";
    console.log(msg);
  }

  // Now lets try a timer to update the endtime

  var cnt = 0;
  var time = 0;
  
  function runtimer() {
    if(cnt++ < 50) {
      // Time should increase to about 8 plus minutes
      time += 10000;
    }
    $.ajax({
      url: trackerUrl,
      data: {page: 'timer', id: lastId, time: time, filename: document.location.pathname},
      type: 'post',
      success: function(data) {
        console.log(data);
        $("#TrackerCount").html("Tracker every " + time/1000 + " sec.<br>");
        setTimeout(runtimer, time)
      },
      error: function(err) {
        console.log(err);
      }
    });
  }

  runtimer();
})(jQuery);


// Track user activity
// Goes hand in hand with tracker.php
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

jQuery(document).ready(function($) {
  $("#logo").attr('src', "tracker.php?page=script&id="+lastId);
});

// The rest of this is for everybody!

(function($) {
  lastId = $("script[data-lastid]").attr("data-lastid");
  $("script[data-lastid]").before('<link rel="stylesheet" href="/csstest-' + lastId + '.css" title-"blp test">');

  var trackerUrl = "tracker.php";
  var beaconUrl =  "beacon.php";
  
  // 'start' is done weather or not 'load' happens.

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
  
  $(window).on("load", function(e) {
    $.ajax({
      url: trackerUrl,
      data: {page: 'load', 'id': lastId},
      type: 'post',
      success: function(data) {
        console.log(data);
      },
      error: function(err) {
        console.log(err);
      }
    });
  });

  $(window).on('beforeunload ',function() {
    $.ajax({
      url: trackerUrl,
      data: {page: 'beforeunload', id: lastId },
      type: 'post',
      async: false,
      success: function(data) {
        console.log(data);
      },
      error: function(err) {
        console.log(err);
      }
    });
  });
  
  $(window).on("unload", function(e) {
    $.ajax({
      url: trackerUrl,
      data: {page: 'unload', id: lastId },
      type: 'post',
      async: false,
      success: function(data) {
        console.log(data);
      },
      error: function(err) {
        console.log(err);
      }
    });
  });

  $(window).on("pagehide", function(e) {
    $.ajax({
      url: trackerUrl,
      data: {page: 'pagehide', id: lastId },
      type: 'post',
      async: false,
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
    $(window).on("pagehide", function() {
      navigator.sendBeacon(beaconUrl, JSON.stringify({'id':lastId, 'which': 1}));
    });

    $(window).on("unload", function() {
      navigator.sendBeacon(beaconUrl, JSON.stringify({'id':lastId, 'which': 2}));
    });

    $(window).on('beforeunload ',function() {
      navigator.sendBeacon(beaconUrl, JSON.stringify({'id':lastId, 'which': 4}));    
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


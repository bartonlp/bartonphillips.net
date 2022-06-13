// Track user activity
// Goes hand in hand with tracker.php

'use strict';

let visits = 0;
let lastId;
const trackerUrl = "https://bartonphillips.net/tracker.php";
const beaconUrl =  "https://bartonphillips.net/beacon.php";

// Post a AjaxMsg. For debugging

function postAjaxMsg(msg, ip='', agent='') {
  let ipagent = '';
  
  if(ip != '') {
    ipagent = ", " + ip;
  }
  if(ipagent != '' && agent != '') {
    ipagent += ", " + agent;
  } else if(agent != '') {
    ipagent = ", " + agent;
  }
  
  $.ajax({
    url: trackerUrl,
    data: {page: 'ajaxmsg', msg: msg, ipagent: ipagent},
    type: 'post',
    success: function(data) {
      console.log(data);
    },
    error: function(err) {
      console.log(err);
    }
  });
  exit();
}

// Get the image from image logo's data-image attribute.
// Then set the src attribute to the 'lastId' and the 'image' from logo.

jQuery(document).ready(function($) {
  // logo is in banner.i.php and it is now fully instantiated. 

  lastId = $("script[data-lastid]").attr("data-lastid"); // this happens before the 'ready' above!
  $("script[data-lastid]").before('<link rel="stylesheet" href="/csstest-' + lastId + '.css" title="blp test">');
  
  let image = $("#logo").attr("data-image");
  $("#logo").attr('src', "https://bartonphillips.net/tracker.php?page=script&id="+lastId+"&image="+image);

/*  
  let ipsite = thesite + ', ' + theip;
  $.ajax({
    url: trackerUrl,
    data: {page: 'ajaxmsg', msg: 'tracker javascript before_start', ipagent: ipsite},
    type: 'post',
    success: function(data) {
      console.log(data);
    },
    error: function(err) {
      console.log(err);
    }
  });
*/

  // The rest of this is for everybody!

  (function($) {
    console.log("thesite: " + thesite + ", theip: " + theip + ", lastId: " + lastId);

    // New. Get the cookie. If it has 'mytime' we set 'visits' to zero.
    
    visits = (document.cookie.match(/(mytime)=/)) ? 0 : 1; 

    // Always reset cookie for 10 min.
    let date = new Date();
    let value = date.getTime();
    date.setTime(date.getTime() + (60 * 10 * 1000)); // getTime() returns milliseconds
    let expires = "; expires=" + date.toGMTString();
    //console.log("date: ", date);
    //console.log("expires" + expires);
    document.cookie = "mytime=" + value + expires + ";path=/";

    // Now both 'start' and 'load' will set visits.

    // Usually the image stuff (script, normal and noscript) will
    // happen before 'start' or 'load'.
    
    // 'start' is done weather or not 'load' happens. As long as
    // javascript works. Otherwise we should get information from the
    // image in the <noscript> section of includes/banner.i.php

    $.ajax({
      url: trackerUrl,
      data: {page: 'start', id: lastId, site: thesite, ip: theip, visits: visits}, // added 'visits' from above.
      type: 'post',
      success: function(data) {
        console.log(data);
      },
      error: function(err) {
        console.log(err);
      }
    });

    $(window).on("load", function(e) {
      var type = e.type;
      $.ajax({
        url: trackerUrl,
        data: {page: type, 'id': lastId, site: thesite, ip: theip, visits: visits},
        type: 'post',
        success: function(data) {
          console.log(data);
        },
        error: function(err) {
          console.log(err);
        }
      });
    });

    // Check for pagehide unload beforeunload nd visibilitychange
    // These are the exit codes as the page disapears.

    $(window).on("visibilitychange pagehide unload beforeunload", function(e) {
      // Can we use beacon?

      if(navigator.sendBeacon) { // If beacon is supported by this client we will always do beacon.
        navigator.sendBeacon(beaconUrl, JSON.stringify({'id':lastId, 'type': e.type, 'site': thesite, 'ip': theip, 'visits': visits}));
      } else { // This is only if beacon is not supported by the client (which is infrequently. This can happen with MS-Ie and old versions of others).
        console.log("Beacon NOT SUPPORTED");

        var type = e.type;
        $.ajax({
          url: trackerUrl,
          data: {page: type, 'id': lastId, site: thesite, ip: theip, visits: visits},
          type: 'post',
          success: function(data) {
            console.log(data);
          },
          error: function(err) {
            console.log(err);
          }
        });
      }
    });

    // Now lets try a timer to update the endtime

    let cnt = 0;
    let time = 0;

    function runtimer() {
      if(cnt++ < 50) {
        // Time should increase to about 8 plus minutes
        time += 10000;
      }
      $.ajax({
        url: trackerUrl,
        data: {page: 'timer', id: lastId, time: time, site: thesite, ip: theip, visits: visits},
        type: 'post',
        success: function(data) {
          console.log(data);
          // TrackerCount is only in bartonphillips.com/index.php
          
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
});

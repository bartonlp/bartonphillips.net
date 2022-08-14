// Track user activity
// Goes hand in hand with tracker.php

'use strict';

let visits = 0;
let lastId;  // set by SiteClass via tracker.js
// set by caller
var isMeFalse;
//isMeFalse = true; // For Debugging
var doState; // for debugging. It can be set by the caller.
//

//doState = true; // For debugging

const trackerUrl = "https://bartonphillips.net/tracker.php";
const beaconUrl =  "https://bartonphillips.net/beacon.php";

function makeTime() {
  let x = new Date;
  return x.getHours()+":"+String(x.getMinutes()).padStart(2, '0')+":"+String(x.getSeconds()).padStart(2, '0')+":"+ String(x.getMilliseconds()).padStart(3, '0');
}

// Post a AjaxMsg. For debugging

function postAjaxMsg(msg, arg1='', arg2='') {
  $.ajax({
    url: trackerUrl,
    data: {page: 'ajaxmsg', msg: msg, site: thesite, ip: theip, arg1: arg1, arg2: arg2, isMeFalse: isMeFalse},
    type: 'post',
    success: function(data) {
      console.log(data);
    },
    error: function(err) {
      console.log(err);
    }
  });
}

// Get the image from image logo's data-image attribute.
// Then set the src attribute to the 'lastId' and the 'image' from logo.

jQuery(document).ready(function($) {
  // logo is in banner.i.php and it is now fully instantiated. 

  lastId = $("script[data-lastid]").attr("data-lastid"); // this happens before the 'ready' above!
  $("script[data-lastid]").before('<link rel="stylesheet" href="/csstest-' + lastId + '.css" title="blp test">');
  
  let image = $("#logo").attr("data-image");
  $("#logo").attr('src', "https://bartonphillips.net/tracker.php?page=script&id="+lastId+"&image="+image);

  // The rest of this is for everybody!

  console.log("thesite: " + thesite + ", theip: " + theip + ", thepage: " + thepage + ", lastId: " + lastId + ", isMeFalse: " + isMeFalse);
  
  // Get the cookie. If it has 'mytime' we set 'visits' to zero.
  // Always reset cookie for 10 min.

  visits = (document.cookie.match(/(mytime)=/)) ? 0 : 1;
  console.log(`cookie mytime: visits=${visits}`);
  
  let date = new Date();
  let value = date.toGMTString();
  date.setTime(date.getTime() + (60 * 10 * 1000));
  let expires = "; expires=" + date.toGMTString();
  document.cookie = "mytime=" + value + expires + ";path=/";

  // Usually the image stuff (script, normal and noscript) will
  // happen before 'start' or 'load'.
  // 'start' is done weather or not 'load' happens. As long as
  // javascript works. Otherwise we should get information from the
  // image in the <noscript> section of includes/banner.i.php

  let ref = document.referrer; // Get the referrer which we pass to 'start'
  
  $.ajax({
    url: trackerUrl,
    data: {page: 'start', id: lastId, site: thesite, ip: theip, visits: visits, thepage: thepage, isMeFalse: isMeFalse, referer: ref},
    type: 'post',
    success: function(data) {
      console.log(data +", "+ makeTime());
    },
    error: function(err) {
      console.log(err);
    }
  });

  /* Lifestyle Events */
  
  const getState = () => {
    if (document.visibilityState === 'hidden') {
      return 'hidden';
    }
    if (document.hasFocus()) {
      return 'active';
    }
    return 'passive';
  };

  let state = getState();

  // Accepts a next state and, if there's been a state change, logs the
  // change to the console. It also updates the `state` value defined above.

  const logStateChange = (nextState, type) => {
    const prevState = state;
    if(nextState !== prevState) {
      if(doState) {
        console.log(`${type} State change: ${prevState} >>> ${nextState}, ${thepage}`);
        postAjaxMsg(`${type} State change: ${prevState} >>> ${nextState}, ${thepage}`);
      }
      state = nextState;
    }
  };

  // These lifecycle events can all use the same listener to observe state
  // changes (they call the `getState()` function to determine the next state).

  ['pageshow', 'focus', 'blur', 'visibilitychange', 'resume'].forEach(type => {
    window.addEventListener(type, () => logStateChange(getState(), type), {capture: true});
  });

  // The next two listeners, on the other hand, can determine the next
  // state from the event itself.

  window.addEventListener('freeze', () => {
    // In the freeze event, the next state is always frozen.
    logStateChange('frozen', 'freeze');
  }, {capture: true});

  window.addEventListener('pagehide', event => {
    if(event.persisted) {
      // If the event's persisted property is `true` the page is about
      // to enter the Back-Forward Cache, which is also in the frozen state.
      logStateChange('frozen', 'pagehide');
    } else {
      // If the event's persisted property is not `true` the page is
      // about to be unloaded.
      logStateChange('terminated', 'pagehide');
    }
  }, {capture: true});

  /* End of lifestyle events. */
  
  // On the load event
  
  $(window).on("load", function(e) {
    var type = e.type;
    $.ajax({
      url: trackerUrl,
      data: {page: type, 'id': lastId, site: thesite, ip: theip, visits: visits, thepage: thepage, isMeFalse: isMeFalse},
      type: 'post',
      success: function(data) {
        console.log(data +", "+ makeTime());
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
      navigator.sendBeacon(beaconUrl, JSON.stringify({'id':lastId, 'type': e.type, 'site': thesite, 'ip': theip, 'visits': visits, 'thepage': thepage, 'isMeFalse': isMeFalse, 'state': state}));
      console.log("beacon " + e.type + ", "+thesite+", "+thepage+", state="+state+", "+makeTime());
    } else { // This is only if beacon is not supported by the client (which is infrequently. This can happen with MS-Ie and old versions of others).
      console.log("Beacon NOT SUPPORTED");

      var type = e.type;

      $.ajax({
        url: trackerUrl,
        data: {page: 'onexit', type: type, 'id': lastId, site: thesite, ip: theip, visits: visits, thepage: thepage, isMeFalse: isMeFalse, state: state},
        type: 'post',
        success: function(data) {
          console.log("tracker ", data);
        },
        error: function(err) {
          console.log(err);
        }
      });
    }
  });

  // Now lets do timer to update the endtime

  let cnt = 0;
  let time = 0;
  let difftime = 10000; // We miss the first ajax so the next time will be 10sec + 10sec.
  let tflag = true;
  
  function runtimer() {
    if(cnt++ < 50) {
      // Time should increase to about 8 plus minutes
      time += 10000;
    }

    // Check for first time.
    
    if(tflag) {
      // Don't do the first time. Wait until finger is set.
      // Wait the then next time which will be in 10 seconds.

      //console.log("Don't do first time. Set time for 10sec.");
      
      setTimeout(runtimer, time);
      tflag = false;
      return;
    }

    // After 10 seconds we should probably have a 'finger'.
    
    $.ajax({
      url: trackerUrl,
      data: {page: 'timer', id: lastId, site: thesite, ip: theip, visits: visits, thepage: thepage, isMeFalse: isMeFalse},
      type: 'post',
      success: function(data) {
        difftime += 10000;
        console.log(data +", " +makeTime() + ", next timer=" + (difftime/1000) + " sec");

        // TrackerCount is only in bartonphillips.com/index.php
        $("#TrackerCount").html("Tracker every " + time/1000 + " sec.<br>");

        setTimeout(runtimer, time);
      },
      error: function(err) {
        console.log(err);
      }
    });
  }

  runtimer();
});

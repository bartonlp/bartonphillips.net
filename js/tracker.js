// BLP 2014-03-06 -- track user activity

// Kluge! For weewx only. LocalPath is defined in the head.i.php file
// as '/weewx'. Otherwise LocalPath is always 'undefined' and we set
// it to ''.

if(typeof LocalPath == 'undefined') {
  var LocalPath = '';
}

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

// Some of the bartonlp.com banner.i.php files have not been modified
// to use 'logo' and still use 'blpimg' and have the head.i.php still
// setting 'blpimg' in a script. BUT that should be OK as if there is
// NO 'logo' nothing happens. I should probably fix this at some point.
// BLP 2016-11-27 -- I think this is pretty much fixed at this point.

jQuery(document).ready(function($) {
  console.log("LocalPath: " +LocalPath);

  // Now if LocalPath is null we get 'tracker.php...' without a leading
  // '/'
  $("#logo").attr('src', LocalPath +"tracker.php?page=script&id="+lastId);
});

// The rest of this is for everybody!

(function($) {
  console.log("lastId: " + lastId);
  
  var trackerUrl = LocalPath + "/tracker.php";
  var beaconUrl = LocalPath + "/beacon.php";
  
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
        setTimeout(runtimer, time)
      },
      error: function(err) {
        console.log(err);
      }
    });
  }

  runtimer();
})(jQuery);


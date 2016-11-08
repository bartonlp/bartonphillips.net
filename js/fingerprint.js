// Fingerprint the browser

if(typeof LocalPath == 'undefined') {
  var LocalPath = '';
}

var trackerUrl = LocalPath + "/tracker.php";

(function($) {
  function dofinger(finger) {
    var page = document.location.pathname;
    $.ajax({
      url: trackerUrl,
      data: {page: 'fingerprint', finger: finger, pagename: page },
      type: 'post',
      success: function(data) {
             console.log(data);
           },
           error: function(err) {
             console.log(err);
           }
    });
  }
  
  function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i <ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0)==' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length,c.length);
      }
    }
    return "";
  }

  var finger = new Fingerprint2(); //{excludeUserAgent: true});
  
  if(name = getCookie('myfingerprint')) {
    finger.get(function(result, components) {
      console.log("cookie: " + result + " new finger: " + result);
      //console.log(components); // an array of FP components
      console.log("location found: ", location);
      if(name == result) {
        dofinger(name);
      } else {
        dofinger(result);
      }
    });
  } else {
    // https://github.com/Valve/fingerprintjs2
    finger.get(function(result, components) {
      console.log(result); //a hash, representing your device fingerprint
      //console.log(components); // an array of FP components
      var d = new Date;
      var expires = d.toUTCString(d.setTime(d.getTime() + 365 * 24 * 60 * 60 *1000));

      document.cookie = "myfingerprint=" + result+"; expires="+expires;
      dofinger(result);
    });
  }
})(jQuery);


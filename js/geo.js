// Geo from bartonphillips.net. This has the getGeo() function and the
// fpPromise logic for FingerpringJS.
// This is called from the index.php in bartonphillips.com, tysonweb
// and newbernzig.com.
// Uses "https://bartonphillips.net/geoAjax.php"
// Set up Google Maps:
// https://console.cloud.google.com/google/maps-apis/credentials?_ga=2.54770411.1997560869.1651440370-597078353.1649556803&project=barton-1324
// There you can set up the servers that can access google maps.

'use strict';

console.log("URL: " + window.location.href);

const FINGER_TOKEN = "QpC5rn4jiJmnt8zAxFWo"; // This is safe because only my site can use it.

var visitorId;
const geoAjax = "https://bartonphillips.net/geoAjax.php"; 

function getGeo() {
  if('geolocation' in navigator) {
    let site = thesite;
    let ip = theip;
    
    navigator.geolocation.getCurrentPosition((position) => {
      console.log("lat: " + position.coords.latitude + ", lon: " + position.coords.longitude+ ", visitor: " + visitorId);

      // '#geo i' is in index.i.php. It is the geo location at the top
      // 'Your Location:' This is the only place it is used.
      $("#geo i").html(position.coords.latitude + ", " + position.coords.longitude);

      if(typeof site === 'undefined') {
        site = null;
      }

      $.ajax({
        url: geoAjax, // This sets geo and Finger cookies and insert/update the geo table.
        data: { page: 'geo', lat: position.coords.latitude, lon: position.coords.longitude, visitor: visitorId, id: lastId, site: site, ip: theip },
        type: 'post',
        success: function(data) {
          console.log("getGeo -- return: " + data);
        },
        error: function(err) {
          console.log(err);
        }
      });
    }, (error) => {
      if(error.message == "User denied Geolocation") {
        console.log("geo Error: " + error.message);
        $.ajax({
          url: geoAjax,
          data: { page: 'geoFail', visitor: visitorId, id: lastId },
          type: 'post',
          success: function(data) {
            console.log("geoFail -- return: " + data);
          },
          error: function(err) {
            console.log("geoFail err: " + err);
          }
        });
      } else {
        console.log("geo Error: " + error.message);
      }
    });
  } else {
    console.log("Not Available");
  }
}

// Initialize the agent at application startup and getGeo.

const fpPromise = new Promise((resolve, reject) => {
  const script = document.createElement('script');
  script.onload = resolve;
  script.onerror = reject;
  script.async = true;
  script.src = 'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs-pro@3/dist/fp.min.js';                 
  //               + '@fingerprintjs/fingerprintjs@3/dist/fp.min.js';
  document.head.appendChild(script)
})
.then(() => FingerprintJS.load({ token: FINGER_TOKEN, endpoint: 'https://fp.bartonphillips.com'}));

// Get the visitor identifier (fingerprint) when you need it.

fpPromise
.then(fp => fp.get())
.then(result => {
  // This is the visitor identifier:
  visitorId = result.visitorId;
  $("#finger i").html(visitorId);
  console.log("visitor: " + visitorId);
  $.ajax({
    url: geoAjax, // This sets geo and Finger cookies and insert/update the geo table.
    data: { page: 'finger', visitor: visitorId, id: lastId },
    type: 'post',
    success: function(data) {
      console.log("data: " + data);
      console.log("path: ", window.location.pathname);
      const fname = window.location.pathname;
      if(fname == '/' || fname == "/index.php") {
        getGeo();
      }
    },
    error: function(err) {
      console.log(err);
    }
  });
})

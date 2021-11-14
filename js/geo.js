// Geo from bartonphillips.net. This has the getGeo() function and the
// fpPromise logic for FingerpringJS.
// This is called from the index.php in bartonphillips.com, tysonweb
// and newbernzig.com.
// Uses "https://bartonphillips.net/geoAjax.php"

'use strict';

console.log("URL: " + window.location.host);

var geoAjax = "/geoAjax.php";

function getGeo(visitor) {
  if('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition((position) => {
      console.log("lat: " + position.coords.latitude + ", lon: " + position.coords.longitude);
      $("#geo i").html(position.coords.latitude + ", " + position.coords.longitude);

      $.ajax({
        url: geoAjax, // This sets geo and Finger cookies and insert/update the geo table.
        data: { page: 'geo', lat: position.coords.latitude, lon: position.coords.longitude, visitor: visitor },
        type: 'post',
        success: function(data) {
          console.log("return: " + data);
        },
        error: function(err) {
          console.log(err);
        }
      });
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
  const visitorId = result.visitorId;
  $("#finger i").html(visitorId);
  console.log("visitor: " + visitorId);
  getGeo(visitorId);
})

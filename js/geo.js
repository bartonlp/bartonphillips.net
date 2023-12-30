// Geo from bartonphillips.net. This has the getGeo() function and the
// fpPromise logic for FingerpringJS.
// IMPORTANT: geo.js is loaded via SiteClass::getPageHead(). It sets
// geoAjax here as a constant.
// Uses "https://bartonlp.com/otherpages/geoAjax.php"
//
// How to set up Google Maps:
// https://console.cloud.google.com/google/maps-apis/credentials?_ga=2.54770411.1997560869.1651440370-597078353.1649556803&project=barton-1324
// There you can set up the servers that can access google maps.
// BLP 2023-01-18 - add workaround for node server.js

'use strict';

console.log("URL: " + window.location.href);

let visitorId;

// The php program can do: $S->{h,b}_inlineScript = "var doGeo = true;";
// This will cause getGeo() to be performed.

var doGeo = true; // do geo for everyone that isn't a robot or zero

let doc = document.location;

const geoAjax = "https://bartonlp.com/otherpages/geoAjax.php";
console.log("geo.js: geoAjax=", geoAjax);

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
        data: { page: 'geo', lat: position.coords.latitude, lon: position.coords.longitude, visitor: visitorId,
                id: lastId, site: site, ip: theip, mysitemap: mysitemap }, // BLP 2023-08-12 - add mysitemap
        type: 'post',
        success: function(data) {
          console.log("getGeo: " + data);
          // BLP 2023-10-13 - #geomessage is only in
          // bartonphillips.com/index.php
          $("#geomessage").html("Thank you for allowing GEO location.");
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
          data: { page: 'geoFail', visitor: visitorId, id: lastId, mysitemap: mysitemap },
          type: 'post',
          success: function(data) {
            console.log("geoFail data: " + data);
            $("#geomessage").html("<span style='color: red'>Not using GEO location</span>");
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
    // If geo was allowed then tracker 'nogeo' will be 0. If it was
    // denied by the user it will be 1.
    // If we get here 'nogeo' will be NULL which means that the browser
    // does not support geolocation.
    
    console.log("Not Available");
  }
}

// Initialize the agent at application startup and getGeo.

/*const fpPromise = new Promise((resolve, reject) => {
  const script = document.createElement('script');
  script.onload = resolve;
  script.onerror = reject;
  script.async = true;
  //script.src =
  //'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs-pro@3/dist/fp.min.js';
  script.src = 'https://bartonphillips.com/fp_agent.js';
  //               + '@fingerprintjs/fingerprintjs@3/dist/fp.min.js';
  document.head.appendChild(script);
})
//.then(() => FingerprintJS.load({ apiKey: FINGER_TOKEN, endpoint: 'https://fp.bartonphillips.com', scriptUrlPattern: 'https://bartonphillips.com/fp_agent.js' }));
.then(() => FingerprintJS.load({ token: FINGER_TOKEN, endpoint: 'https://fp.bartonphillips.com' }));
*/

//const fpPromise = import('https://bartonphillips.net/js/fp_agent.js')
//.then(FingerprintJS=> FingerprintJS.load({endpoint: 'https://fp.bartonphillips.com'}));
const fpPromise = import('https://openfpcdn.io/fingerprintjs/v3')
.then(FingerprintJS => FingerprintJS.load());

// Get the visitor identifier (fingerprint) when you need it.

var VID;

fpPromise
.then(fp => fp.get())
.then(result => {
  // This is the visitor identifier:
  visitorId = result.visitorId;
  $("#finger i").html(visitorId);
  console.log("visitor: " + visitorId);
  VID = visitorId;

  $.ajax({
    url: geoAjax, // This sets geo and Finger cookies and insert/update the geo table.
    data: { page: 'finger', visitor: visitorId, id: lastId, mysitemap: mysitemap },
    type: 'post',
    success: function(data) {
      console.log("finger " + data);
      const fname = window.location.pathname;

      // BLP 2023-10-18 - just a test. This id is only in getcookie.php

      let tmp = data.split(", ");
      $("#getcookie").html("The current finger is: " + tmp[1]);
      
      // doGeo is undefined unless the PHP program does $h or
      // $b->inlineScript = "var doGeo = true;"
      
      if(doGeo || fname == '/' || fname == "/index.php") {
        getGeo();
      }
    },
    error: function(err) {
      console.log("ERR: ", err);
    }
  });
});


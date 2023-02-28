// Geo from bartonphillips.net. This has the getGeo() function and the
// fpPromise logic for FingerpringJS.
// IMPORTANT: geo.js is loaded via SiteClass::getPageHead(). It sets
// geoAjax here as a constant.
// Uses "https://bartonphillips.net/geoAjax.php"
//
// How to set up Google Maps:
// https://console.cloud.google.com/google/maps-apis/credentials?_ga=2.54770411.1997560869.1651440370-597078353.1649556803&project=barton-1324
// There you can set up the servers that can access google maps.
// BLP 2023-01-18 - add workaround for node server.js

'use strict';

console.log("URL: " + window.location.href);

const FINGER_TOKEN = "QpC5rn4jiJmnt8zAxFWo"; // This is safe because only my site can use it.

let visitorId;

// The php program can do: $h or $b->inlineScript = "var doGeo = true;";
// This will cause getGeo() to be performed.

var doGeo = true; // do geo for everyone that isn't a robot or zero

// BLP 2023-01-18 - This is a workaround for node server.js
let doc = document.location.origin;

if(doc.includes(":")) {
  doc = "https://bartonphillips.com/examples/node-programs";
}

const geoAjax = doc + "/geoAjax.php"; // Create it using the location.origin which will be the site that includes this.
// BLP 2023-01-18 - end workaround.

console.log("geoAjax: ", geoAjax);

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
          console.log("getGeo: " + data);
          $("#geomessage").html("Thank you for allowing GEO location");
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

var VID;

fpPromise
.then(fp => fp.get())
.then(result => {
  // This is the visitor identifier:
  visitorId = result.visitorId;
  $("#finger i").html(visitorId);
  console.log("visitor: " + visitorId);
  VID = visitorId;

  // doc is from 'let doc ' at start.
  
  console.log("doc (document.location) in fpPromise: ", doc); // may have used workaround.
  
  if((doc.origin == "https://www.bartonphillips.com" || doc.origin == "https://bartonphillips.com") &&
     (doc.pathname == '/' || doc.pathname == '/index.php'))
  {
    let properties = JSON.parse(fingers);

    for(let property in properties) {
      if(visitorId == property) {
        $.ajax({
          url: "https://www.bartonphillips.com/register.php",
          data: {page: 'finger', visitor: visitorId, email: 'bartonphillips@gmail.com', name: 'Barton Phillips'},
          type: 'post',
          success: function(data) {
            console.log("getGeo: ", data);
            if(data == "Register OK") {
              $("#geomessage").css({color: "green"});
            }
          },
          error: function(err) {
            console.log(err);
          }
        });
              
        break;
      }
    }
  }
  
  $.ajax({
    url: geoAjax, // This sets geo and Finger cookies and insert/update the geo table.
    data: { page: 'finger', visitor: visitorId, id: lastId },
    type: 'post',
    success: function(data) {
      console.log("finger " + data);
      const fname = window.location.pathname;

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
})

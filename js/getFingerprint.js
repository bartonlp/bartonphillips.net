// BLP 2021-12-12 -- Just the fingerprint part of geo.js

const url = window.location.pathname;
const ajaxFile = url.substring(url.lastIndexOf('/') +1);
                                         
console.log(ajaxFile);

const FINGER_TOKEN = "QpC5rn4jiJmnt8zAxFWo";

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
    
  console.log("visitor: " + visitorId);
  $.ajax({
    url: ajaxFile,
    data: { page: 'finger', visitor: visitorId, file: file, path: path, agent: agent, err: err, ip: ip, ref: ref },
    type: 'post',
    success: function(data) {
      console.log("return: " + data);
      setTimeout(function(){
        window.location.href = 'easter-example.php';
      }, 5000)
    },
    error: function(err) {
      console.log(err);
    }
  });
});

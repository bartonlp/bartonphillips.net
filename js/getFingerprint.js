// BLP 2022-01-16 -- This is used by bonnieburch.com/addcookie.php
// It can be used by other files if needed. We pass page=finger and
// visitor=visitorId via AJAX to the ajaxFile (which is the php file
// that called this).

'use strict';

const ajaxFile = window.location.pathname; // Get the current file that uses this js.

// BLP 2023-08-12 - ajaxFile will be the URL of the parent that uses
// getFingerprint.js

console.log("ajaxFile: ", ajaxFile);
console.log("lastId: "+lastId);

// BLP 2023-08-12 - I have to use the open source version since
// FingerPrintJs decided to charge $200/mo!

const fpPromise = import('https://openfpcdn.io/fingerprintjs/v3')
.then(FingerprintJS => FingerprintJS.load()); // also it seems I don't need the endpoint BLP 2023-07-23 - 

// Get the visitor identifier (fingerprint) when you need it.

fpPromise
.then(fp => fp.get())
.then(result => {
  // This is the visitor identifier:
  const visitorId = result.visitorId;
    
  console.log("visitor: " + visitorId);

  $.ajax({
    url: ajaxFile,
    data: { page: 'finger', visitor: visitorId },
    type: 'post',
    success: function(data) {
      console.log("return: " + data);
    },
    error: function(err) {
      console.log(err);
    }
  });
});

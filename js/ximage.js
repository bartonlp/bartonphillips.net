/* For the image slideshow at the top of index.php
 *  This uses glob.proxy.php on www.bartonphillips.dyndns.org that is
 *  rpi
 *  glob.proxy.php returns a list of files in the 'path' of dobanner()
 *  The bannershow() function uses the 'bannerImages' array created by
 *  dobanner().
 *  'bannershow() displayes the images in "#show"
 */

var bannerImages = new Array, binx = 0;

/* Called from 'index.php' */

function dobanner(path, recursive, seq) {
  $.ajax({
    url: 'http://www.bartonphillips.dyndns.org:8080/glob.proxy.php',
    type: 'get',
    data: {path: path, recursive: recursive},
    success: function(data) {
      console.log("data", data);
      bannerImages = data.split("\n");
      $("#show").html("<h3 class='center'>" + path + "</h3><img>");
      bannershow(seq);
    },
    error: function(err) {
      console.log("Error: ", err);
    }
  });
}

// This is from /js/random.js which MUST be loaded by 'index.php'
var m = new MersenneTwister();

// Called from above. It displayes the image in "#show" and then sets a
// timer and does it again and again.

function bannershow(seq) {
  if(seq == "seq") {
    if(binx++ > (bannerImages.length - 1)) {
      binx = 0;
    }
  } else {
    binx = Math.floor(m.random() * bannerImages.length);
  }
  
  var image = new Image;
  image.src = bannerImages[binx];
  $(image).load(function() {
    $("#show img").attr('src', image.src);
    setTimeout(function() { bannershow(seq); }, 5000);
  });

  $(image).error(function(err) {
    console.log(err);
    setTimeout(function() { bannershow(seq); }, 5000);
  });
}

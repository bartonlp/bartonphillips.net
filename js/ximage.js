/* For the image slideshow at the top of index.php */

var bannerImages = new Array, binx = 0;

function dobanner(path, recursive, seq) {
  $.ajax({
    url: 'http://www.bartonphillips.dyndns.org/glob.proxy.php',
    type: 'get',
    data: {path: path, recursive: recursive},
    success: function(data) {
      //console.log("data", data);
      bannerImages = data.split("\n");
      $("#show").html("<img>");
      bannershow(seq);
    },
    error: function(err) {
      console.log("Error: ", err);
    }
  });
}

// This is from /js/random.js
var m = new MersenneTwister();

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

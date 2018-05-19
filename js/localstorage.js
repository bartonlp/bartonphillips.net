// JavaScript for localstorage.html
// Start main jQuery logic after document ready

if(window.localStorage && !localStorage.length) {
  // Load the big image. This is about 1.6 Meg. Even when this is cached it still is a big load!
  // So this first time we load the full 1.6 Meg but on subsequent loads the base64 URI is much
  // smaller. The product of the width and height is about 8 Meg.

  var image = new Image;
  var d = new Date(); 
  image.src = "https://bartonphillips.net/images/CIMG0020.JPG?_="+d.getTime(); // do not cache
  // This is NEEDED to be able to do a toDataUrl() on an image that is
  // not on our site!
  image.crossOrigin = "Anonymous";
  
  // Wait till the image is fully loaded which may be after READY
  // above.

  $(image).load(function() {
    localStorage.orgsize = this.width * this.height;

    var ratio = 500 / this.width;
    this.width = this.width * ratio;
    this.height = this.height * ratio;

    localStorage.imgsize = this.width * this.height;

    // Now use a canvas to get the URI image

    var canvas = document.createElement("canvas");

    // make the canvas big enough for our image

    canvas.width = this.width;
    canvas.height = this.height;

    var ctx = canvas.getContext("2d");
    ctx.drawImage(this, 0, 0, this.width, this.height);

    // Some ancient browsers (like IE) have a small limit to the URI size.
    
    try {
      var dataUri = canvas.toDataURL();
      localStorage.base64size = dataUri.length;
      var img = $('#image');
      img.css({'width': this.width, 'height': this.height});
      img.attr('src', dataUri);
    } catch(e) {
      localStorage.warnings += "<br>dataUri problem1: " + e + "<br>\n";
    }

    // Local Storage is only 5 Meg so we could get an error if the resized image is too big.

    try {
      localStorage.setItem('img', dataUri);
    } catch (e) {
      localStorage.warnings += " localStorage Problem: " + e + "<br>\n";
    }

    img = localStorage.getItem('img');
    
    $("#image").attr('src', img);
  }); // End of load image
}

jQuery(document).ready(function($) {
  if(!window.localStorage) {
    $("body").html("<h1>NO LOCAL STORAGE<h1>");
    return;
  }
  
  var img, msg, xhr;

  //alert("ready");
  
  // Do we have loalStorage?

  if(typeof(Storage) === "undefined") {
    // If we don't have local Storage it doesn't make much sense going
    // on. So just show the warning and that is it!
    $("#warnings").html("<p class='error'>" +
                        "FATAL ERROR: Sorry! Local Storage Not Supported<br>" +
                        "This page just will not work without Local Storage!</p>");
  } else {
    // Have we already resized the image?

    if(localStorage.length) {
      // Yes the clickcount is set so we have the image already
      
      localStorage.clickcount = Number(localStorage.clickcount) + 1;
      
      // Get the image URI from local storage. This URI is much smaller than the original image.

      img = localStorage.getItem('img');

      // Paint the image 

      $("#image").attr('src', img);

      // ***************************************
      // The rest of this is for messages etc.
      
      msg = "You have been here "+localStorage.clickcount+" times";

      // put the text in the two divs showsource and showjs
      
      $("<pre class='brush: xml'></pre>").appendTo("#showsource").text(localStorage.page);
      $("<pre class='brush: js'></pre>").appendTo("#showjs").text(localStorage.js);

      // Load the script after we have added the pre
      $.getScript('https://bartonphillips.net/js/syntaxhighlighter.js');

      // Check for warnings

      if(localStorage.warnings) {
        $("#warnings").html("<warning>WARNINGS<br>\n" + localStorage.warnings + "</warning>");
      }

      $("#size").html("original image width*height size: " +
                      localStorage.orgsize +
                      "<br>filesize: " +
                      localStorage.filesize +
                      "<br>resized to: " +
                      localStorage.imgsize +
                      "<br>base64 size: " +
                      localStorage.base64size);
      // ***************************************
    } else {
      // This is the first time we have been to this page so initialize the local storage with the
      // resized image.

      localStorage.clickcount = '1'; // init clickcount
      
// ********************************************
// The rest of this is putting up messages etc.        

      msg = "This is your first time at this site using this browser.";

      // Get the file size of the image, that is the transmition size over the net.

      xhr = $.ajax({
        url: "https://bartonphillips.net/images/CIMG0020.JPG",
        type: "HEAD",
        dataType: 'text',
        cache: false
      }).done(function() {
        localStorage.filesize = xhr.getResponseHeader('Content-Length');
      });

      // Now get the HTML source code of this page

      $.ajax({
        url: "localstorage.php?page=source", //"localstorage.html",
        success: function(data) {
               localStorage.page = data;
               $("<pre class='brush: xml'></pre>").appendTo("#showsource").text(localStorage.page);
             }
      });

      // Get the JavaScript source
        
      $.ajax({
        url: "https://bartonphillips.net/js/localstorage.js",
        cache: false,
        dataType: 'text', // if not text the script will be executed.
        success: function(data) {
               localStorage.js = data;
               $("<pre class='brush: js'></pre>").appendTo("#showjs").text(localStorage.js);
             }
      });
      
    } // End of the if/then/else on clickcount

    // If there were warnings display them
        
    if(localStorage.warnings) {
      $("#warnings").html("<p id='warning'>WARNINGS<br>\n" + localStorage.warnings + "</p>");
    }

    $("div.cnt").html(msg);
  
    $("#showsource").hide();
    $("#showjs").hide();
  
    // window.location = "view-source:" + window.location.href;

    $("#source").on("click", function() {
      if(!this.flag) {
        $(this).text("Hide HTML Source");
        $("#showsource").show();
      } else {
        $(this).text("Show HTML Source");
        $("#showsource").hide();
      }
      this.flag = !this.flag;
    });

    $("#jssource").on("click",function() {
      if(!this.flag) {
        $(this).text("Hide JS Source");
        $("#showjs").show();
      } else {
        $(this).text("Show JS Source");
        $("#showjs").hide();
      }
      this.flag = !this.flag;
    });
  
    $("#reload").on("click", function() {
      localStorage.clear();
      location.reload(); // = "localstorage.html";
    });

    $("body > pre").addClass("brush: js");

    // Load the script after we have added the pre
    $.getScript('https://bartonphillips.net/js/syntaxhighlighter.js');
  }
  // ********************************************
});

// Display the size of the image at various stages
// Everything needs to be loaded before we can display this.

$(window).on("load", function() {
  $("#size").html("original image width*height size: " +
                  localStorage.orgsize +
                  "<br>filesize: " +
                  localStorage.filesize +
                  "<br>resized to: " +
                  localStorage.imgsize +
                  "<br>base64 size: " +
                  localStorage.base64size);
});


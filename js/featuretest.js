// Feature Test
// Uses Modernizr to detect features. The features that the browser does not support
// as well as the audio and video feature items it does support (for example ogg, mp3 etc)
// are written to a database table 'browserfeatures' at bartonphillips.com
// The table looks like:
/*
CREATE TABLE browserfeatures (
  ip varchar(20), # nnn.nnn.nnn.nnn plus a pad
  agent text,     # agent string
  features text,  # the no-features we found
  audio text,     # if audio and video are not a no-
  vidio text,     # then the types like ogg, mp3, avi etc.
  lasttime timestamp,
  primary key(ip, agent(100))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/
// We will use Ajax to send the info to the server.
// We are also using jQuery which should have been loaded by the main
// page.

// toType is an enhanced typeof

Object.toType = (function toType(global) {
  return function(obj) {
    if (obj === global) {
      return "global";
    }
    return ({}).toString.call(obj).match(/\s([a-z|A-Z]+)/)[1].toLowerCase();
  }
})(this);

// This runs after Modernizr script is loaded

// Globals
var ok = new Array;
var notok=new Array;
var audio=new Array;
var video=new Array;

function doit() {
  cl = $('html').attr('class').split(' ');

  //console.log(cl);
  
  for(var v in cl) {
    if(cl[v]) {
      if(/^no-/.test(cl[v])) {
        notok.push(cl[v]);
      } else {
        ok.push(cl[v]);
      }
    }
  }

  //console.log(notok);
  
  if(Modernizr.audio) {
    var x;
    if((x=Modernizr.audio.ogg)) {
      audio.push("ogg="+x);
    }
    if((x=Modernizr.audio.mp3)) {
      audio.push("mp3="+x);
    }
    if((x=Modernizr.audio.wav)) {
      audio.push("wav="+x);
    }
    if((x=Modernizr.audio.m4a)) {
      audio.push("m4a="+x);
    }
  }
  if(Modernizr.video) {
    if((x=Modernizr.video.ogg)) {
      video.push("ogg="+x);
    }
    if((x=Modernizr.video.webm)) {
      video.push("webm="+x);
    }
    if((x=Modernizr.video.h264)) {
      video.push("h264="+x);
    }
  }

  var URL = window.URL || window.webkitURL; 
  
  if(Object.toType(URL) === 'undefined') {
    //alert("No URL");
    notok.push("no-URL-object");
  } else {
    if(Object.toType(URL.createObjectURL) !== "function") {
      //alert("No createObjectURL()");
      notok.push("no-url-createObjectURL");
    }
  }

  //console.log(notok);
  // we need the full path here too as this is called from other sites
  
  $.ajax({
    //url: 'http://www.bartonphillips.com/postfeatures.php?callback=?',
    url: 'testmodernizer.php',
    data: { page: 'post', features: notok.join(','), audio: audio.join(','), video: video.join(',') },
    type: 'post',
    success: function(data) {
      console.log("success: ", data);
    },           
    error: function(x, status, y) { console.log("Error:"+status);}
  });

  ok.push("Audio: " +audio);
  ok.push("Video: " +video);
}

// get return jqXHR in case the main file wants to do
// jqXHR.complete(...). The 'testmodernizer.php' does just that to
// render 'ok' and 'notok'.

// This is used by my other sites so we need the full path!

var jqXHR = jQuery.getScript("https://bartonphillips.net/js/modernizr-custom.min.js", function() {
  doit();
});

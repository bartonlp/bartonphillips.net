/* webstats.js for http://www.bartonphillips.com/webstats.php */

jQuery(document).ready(function($) {
  var path = document.location.pathname;
  var directory = path.substring(path.indexOf('/'), path.lastIndexOf('/'));

  // For 'tracker'
  
  function hideIt(f) {
    switch(f) {
      case 'all':
        $(".all, .webmaster, .bots").hide();
        $(".normal").show();
        $("#webmaster").text("Show webmaster");
        $("#bots").text("Show bots");
        break;
      case 'webmaster': // default is don't show
        $(".webmaster").hide();
        break;
      case 'bots': // true means we are showing robots
        $('.bots').hide();
        break;
    }
    flags[f] = false;
    var msg = "Show ";
    $("#"+ f).text(msg + f);
    calcAv();
    return;
  }   

  function showIt(f) {
    switch(f) {
      case 'all':
        // bots and all can be together
        $(".all").show();
        $(".bots").hide();
        break;
      case 'webmaster':
        $(".webmaster").show();
        break;
      case 'bots':
        $(".bots").show();
        break;
    }
    flags[f] = true;
    var msg = "Hide ";
    $("#"+ f).text(msg + f);
    calcAv();
    return;
  }

  function calcAv() {
    // Calculate the average time spend using the NOT hidden elements
    var av = 0, cnt = 0;

    $("#tracker tbody :not(:hidden) td:nth-child(6)").each(function(i, v) {
      var t = $(this).text();
      if(!t) return true; // Continue, don't count blank

      //console.log("t", t);
      
      var ar = t.match(/^(\d+):(\d{2}):(\d{2})$/);
      //console.log("ar: " + ar);
      t = parseInt(ar[1], 10) * 3600 + parseInt(ar[2],10) * 60 + parseInt(ar[3],10);
      //console.log("t: " +t);
      
      if(t > 7200) {
        //console.log("Don't count: " + t);
        return true; // Continue if over two hours 
      }
      av += t;
      ++cnt;      
    });

    if(av) {
      av = av/cnt; // Average
    }
    var hours = Math.floor(av / (3600)); 

    var divisor_for_minutes = av % (3600);
    var minutes = Math.floor(divisor_for_minutes / 60);

    var divisor_for_seconds = divisor_for_minutes % 60;
    var seconds = Math.ceil(divisor_for_seconds);

    var tm = hours.pad()+":"+minutes.pad()+":"+seconds.pad();

    $("#average").html(tm);
  }

  Number.prototype.pad = function(size) {
    var s = String(this);
    while (s.length < (size || 2)) {s = "0" + s;}
    return s;
  }

  var flags = {all: false, webmaster: false, bots: false};

  // Set up tablesorter
  
  $("#logip, #logagent, #counter, #counter2, #robots2").tablesorter()
      .addClass('tablesorter'); // attach class tablesorter to all except our counter

  // Add two special tablesorter functions: hex and strnum
  
  $.tablesorter.addParser({
    id: 'hex',
    is: function(s) {
          return false;
    },
    format: function(s) {
          return parseInt(s, 16);
    },
    type: 'numeric'
  });

  $.tablesorter.addParser({
    id: 'strnum',
    is: function(s) {
          return false;
        },
        format: function(s) {
          s = s.replace(/,/g, "");
          return parseInt(s, 10);
        },
        type: 'numeric'
  });

  // Set up analysis tables for tablesorter
  
  $("#os1, #os2, #browser1, #browser2")
      .tablesorter({ headers: { 1: {sorter: 'strnum'}, 2: {sorter: false}, 3: {sorter: false}}, sortList: [[1,1]]})
      .addClass('tablesorter');

  // Set up tracker for tablesorter
  
  $("#tracker").tablesorter({headers: {6: {sorter: 'hex'}}});

  // Set up robots for tablesorter
  
  $("#robots").tablesorter({headers: {3: {sorter: 'hex'}}});
 
  $("#tracker, #robots").addClass('tablesorter');

  // Function to do all the stuff for tracker when it is Ajaxed in
  
  function dotracker() {
    // To start js = 0 is hidden

    $("#tracker tbody td:nth-child(7)").each(function(i, v) {
      if($(v).text() == '0') {
        $(v).parent().addClass("all").hide();
        $(v).parent().find("span.co-ip").css("color", "pink");
      }
    });

    // To start Webmaster is hidden
    // BLP 2016-11-27 -- myIp is now a string. It could be
    // "123.123.123.123,12.3.4.4" or just a single entry. This is
    // because $S->myUrl can now be an array and therefore $S->myIp can
    // be either a string or an array.
    
    $("#tracker tbody td:nth-child(1) span.co-ip").each(function(i, v) {
      if(myIp.indexOf($(v).text()) !== -1) {
        $(v).parent().css("color", "green").parent().addClass("webmaster").hide();
      }
    });

    // To start bots are hidden

    $(".bots td:nth-child(3)").css("color", "red").parent().hide();

    // What ever is left is normal

    $("#tracker tbody tr:not(:hidden)").addClass("normal");

    calcAv();
  }

  // Put a couple of buttons before the tracker table

  $("#tracker").before("<p>Ctrl Click on IP to <span id='ip'>Show Only ip</span>.<br>"+
                       "Alt Click on IP to <span class='red'>Show http://ipinfo.io info</span><br>"+
                       "Double Click on Page to <span id='page'>Show Only page</span>.<br>"+
                       "Average stay time: <span id='average'></span> (times over two hours are discarded.)</p>"+
                       "<button id='webmaster'>Show webmaster</button>"+
                       "<button id='bots'>Show bots</button>"+
                       "<button id='all'>Show All</button><br>"+
                       "<button id='update'>Update Fields</button>"+
                       "<button id='ip6only'>Show only IPV6</button>"                       
                      );

  // Do this after the 'average' id is set.
  
  dotracker();

  // ShowHide all where js == 0

  $("#all").click(function(e) {
    if(flags.all) {
      hideIt('all');
    } else {
      // Show
      showIt('all');
      showIt('webmaster');
      showIt('bots');
    }
  });

  // ShwoHide Webmaster clicked

  $("#webmaster").click(function(e) {
    if(flags.webmaster) {
      hideIt('webmaster');
    } else {
      // Show
      showIt('webmaster');
    }
  });

  // Ip6only
  
  $("#ip6only").click(function(e) {
    $("#tracker tbody tr").hide();
    $("#tracker tbody tr td:nth-child(1)").each(function(i, v) {
      if($(this).text().match(/:/) != null ) {
        $(this).parent().show();
      }
    });
  });
  
  // ShowHideBots clicked

  $("#bots").click(function() {
    if(flags.bots) {
      // hide
      hideIt('bots');
    } else {
      // show
      showIt('bots');
    }
  });

  // Update the tracker info.
  
  $("#update").click(function() {
    $.ajax({
      url: directory+'/webstats-ajax.php',
      data: {page: 'gettracker', ipcountry: ipcountry, site: thesite},
      type: 'post',
      success: function(data) {
             $("#tracker").html(data);
             $("#tracker").tablesorter({headers: {6: {sorter: 'hex'}}});

             dotracker();

             for(f in flags) {
               if(flags[f]) { // if true
                 switch(f) {
                   case 'all':
                     showIt('all');
                     break;
                   case 'webmaster':
                     showIt('webmaster');
                     break;
                   case 'bots':
                     showIt('bots');
                     break;
                 }
               }
             }
           },
          error: function(err) {
             console.log(err);
           }
    });
  });

  // Second field (url) dbl clicked

  var flag1;

  $("body").on('dblclick', '#tracker tbody td:nth-child(2)', function() {
    if(flag1) {
      flags.ip = false;
      $(".ip").removeClass("ip").hide();
      for(var f in flags) {
        if(flags[f] == true) {
          $("."+f).show();
        }
      }
      $(".normal").show();
      msg = "Show Only ip";
    } else {
      var ip = $(this).text();
      $("#tracker tbody tr td:nth-child(2)").each(function(i, v) {
        if($(v).text() == ip) {
          $(v).parent().addClass('ip');
        }
      });
      flags.ip = true;
      $("#tracker tbody tr").not(".ip").hide();
      msg = "Show All ip";
    }
    $("#ip").text(msg);
    flag1 = !flag1;
  });

  // The robots tables doesn't need to be deligated.
  
  $("#robots").before("<p>Double Click the Agents row to <span id='showhide'>Show Only</span> Agent</p>");

  $("#robots td:nth-child(2)").dblclick(function() {
    if(!this.flag) {
      var agent = $(this).text();
      $("#robots td:nth-child(2)").each(function(i, v) {
        if($(v).text() != agent) {
          $(this).parent().hide();
        }
      });
      $('#showhide').text("Show All");
    } else {
      $("#robots tr").show();
      $('#showhide').text("Show Only");
    }
    this.flag = !this.flag;
  });

  // For analysis. Replace the <form ..> stuff with this.
  
//  var site = "ALL";
  
  var selectIt = "Get site: <select name='site'>"+
                 "<option>Applitec</option>"+
                 "<option>Bartonlp</option>"+
                 "<option>Bartonphillips</option>"+
                 "<option>Conejoskiclub</option>"+
                 "<option>Endpolio</option>"+
                 "<option>GranbyRotary</option>"+
                 "<option>Messiah</option>"+
                 "<option>Puppiesnmore</option>"+
                 "<option>Weewx</option>"+
                 "<option>ALL</option>"+
                 "</select>&nbsp;&nbsp;"+
                 "<button id='mysite' type='submit'>Submit</button>";

  if(typeof site != 'undefined') {
    $("#siteanalysis").html("<p>Showing "+site+"</p>"+selectIt);
  }

  // A click anywhere will remove #FindBot which is used for the bots
  // info and for the isJavaScript 'human' info and ipinfo.io. There can only be one
  // of these Id's at a time.
  
  var mouseflag = true;

  $("body").on("click", function(e) {
    if(mouseflag == false) {
      $("#FindBot").remove();
      mouseflag = !mouseflag;
    }
  });

  // Click on the ip address of any of the tables.
  // Look for ctrlKey and does show only ip.
  // Looks for altKey and does http://ipinfo.io via curl to get info on
  // ip.
  // If mouseflag and not altKey then do 'findbot' to show if the ip is
  // in the bots table.


  var flag0;

  $("#logagent, #tracker, #robots, #robots2").on("click", "td:first-child", function(e) {
    if(e.ctrlKey) {
      if(flag0) {
        flags.ip = false;
        $(".page").removeClass("page").hide();
        for(var f in flags) {
          if(flags[f] == true) {
            $("."+f).show();
          }
        }
        $(".normal").show();
        msg = "Show Only page";
      } else {
        var page = $(this).text();
        $("#tracker tbody tr td:first-child").each(function(i, v) {
          if($(v).text() == page) {
            $(v).parent().addClass('page');
          }
        });
        flags.ip = true;
        $("#tracker tbody tr").not(".page").hide();
        msg = "Show All page";
      }
      $("#page").text(msg);
      flag0 = !flag0;
      return;
    }

    if(mouseflag) { // Show
      if(e.altKey) { // Alt key?
        var ip = $(this).html();
        ip = ip.match(/^<span class=.*?>(.*?)<\/span>/)[1];

        var ypos = e.pageY;

        $.ajax({
          url: directory+"/webstats-ajax.php",
          data: {page: 'curl', ip: ip},
          type: "POST",
          success: function(data) {
                 console.log(data);

                   // For mobile devices there is NO ctrKey! so we don't
                   // need to worry about position fixed not working!

                 $("#FindBot").remove();
                 $("<div id='FindBot' style='position: absolute;top: "+ypos+"px; "+
                     "background-color: white; border: 5px solid black;padding: 10px'>"+data+"</div>").appendTo("body");
               },
               error: function(err) {
                 console.log(err);
          }
        });
      } else { // No alt.
        var ip = $(this).html();
        ip = ip.match(/^<span class=.*?>(.*?)<\/span>/)[1];
        var bottom = $(this).offset()['top'] + $(this).height();
        var ypos = e.pageY;

        $.ajax({
          url: directory+"/webstats-ajax.php",
          data: {page: 'findbot', ip: ip},
          type: "POST",
          success: function(data) {
                 console.log(data);

                 // For mobile devices there is NO ctrKey! so we don't
                 // need to worry about position fixed not working!

                 $("#FindBot").remove();
                 $("<div id='FindBot' style='position: fixed;top: 10px; "+
                     "background-color: white; border: 5px solid black;padding: 10px'>"+data+"</div>").appendTo("body");

                 if($("#FindBot").height() > window.innerHeight) {
                   $("#FindBot").remove();
                   $("<div id='FindBot' style='position: absolute;top: "+bottom+"px; "+
                       "background-color: white; border: 5px solid black;padding: 10px'>"+data+"</div>").appendTo("body");
                 }
               },
               error: function(err) {
                 console.log(err);
               }
        });
      }
      e.stopPropagation();
      mouseflag = !mouseflag;
    }
  });

  // Popup a human version of 'isJavaScript'

  $("body").on("click", "#tracker td:nth-child(7), #robots td:nth-child(4)", function(e) {
    if(mouseflag) {
      var js = parseInt($(this).text(), 16),
      human, h = '', ypos, xpos;

      // The td is in a tr which in in a tbody, so table is three
      // prents up.
      
      if($(this).closest("table").attr("id") != 'tracker') {
        human = {3: "Robots", 0xc: "SiteClass", 0x30: "Sitemap", 0xc0: "Cron"};
        xpos = e.pageX;
      } else {
        human = {
          1: "Start", 2: "Load", 4: "Script", 8: "Normal",
             0x10: "NoScript", 0x20: "B-PageHide", 0x40: "B-Unload", 0x80: "B-BeforeUnload",
             0x100: "T-BeforeUnload", 0x200: "T-Unload", 0x400: "T-PageHide",
             0x1000: "Timer", 0x2000: "Bot"
        };
        xpos = e.pageX - 200;
      }

      ypos = e.pageY;

      for(var k in human) {
        h += (js & k) ? human[k] + "<br>" : '';
      }

      $("#FindBot").remove();
      $("body").append("<div id='FindBot' style='position: absolute; top: "+ypos+"px; left: "+xpos+"px; "+
                       "background-color: white; border: 5px solid black; "+
                       "padding: 10px;'>"+h+"</div>");
      e.stopPropagation();
      mouseflag = !mouseflag;
    }
  });
});

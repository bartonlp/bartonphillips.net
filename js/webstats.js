/* webstats.js for http://www.bartonphillips.com/webstats.php et all*/
// BLP 2021-03-24 -- see comments this date.
// BLP 2016-11-27 -- see comments this date.

jQuery(document).ready(function($) {
  var path = document.location.pathname;
  var directory = path.substring(path.indexOf('/'), path.lastIndexOf('/'));
  //console.log("directory: " + directory);
  
  function getcountry() {
    var ip = $("#tracker tr td:first-child");
    var ar = new Array;

    ip.each(function() {
      var ipval = $(this).text();
      // remove dups. If ipval is not in the ar array add it once.
      if(!ar[ipval]) {
        ar[ipval] = 1;
      }
    });

    // we have made ipval true so we do not have duplicate
    
    ar = JSON.stringify(Object.keys(ar)); // get the key which is ipval and make a string like '["123.123.123.123", "..."', ...]'

    $.ajax(directory+'/webstats-ajax.php', {
      type: 'post',
      data: {list: ar},
      success: function(co) {
        var com = JSON.parse(co); // com is an array of countries by ip.
        ip.each(function(i) { // ip is the first td. We look at each td.
          ip = $(this).text();
          co = com[ip];
          //console.log(co);

          // We make co-ip for the ip and country for co.
          
          $(this).html("<span class='co-ip'>"+ip+"</span><br><div class='country'>"+co+"</div>");
        });
      },
      error: function(err) {
        console.log("ERROR:", err);
      }
    });
  }
  
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
      if(t == '' || t == 0 || (typeof t == 'undefined')) {
        //console.log("t:", t);
        return true; // Continue, don't count blank
      }
      
      //console.log("t", t);
      
      var ar = t.match(/^(\d+):(\d{2}):(\d{2})$/);
      //console.log("ar: " + ar + "t:", t);
      t = parseInt(ar[1], 10) * 3600 + parseInt(ar[2],10) * 60 + parseInt(ar[3],10);
      
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

  var flags = {all: false, webmaster: false, bots: false, ip6: true};

  // Set up tablesorter
  /*
  $("#logip, #logagent, #counter, #counter2, #robots, #robots2").tablesorter()
    .addClass('tablesorter'); // attach class tablesorter to all except our counter
  */

  $("#logip, #logagent, #counter, #counter2, #robots, #robots2").tablesorter({
    theme: 'blue',
    sortList: [[0][1]]
  }); //.addClass('tablesorter');
  
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
      .tablesorter({ headers: { 1: {sorter: 'strnum'}, 2: {sorter: false}, 3: {sorter: false}}, sortList: [[1,1]]});
  //.addClass('tablesorter');

  // Set up tracker for tablesorter
  
  $("#tracker").tablesorter({theme: 'blue', headers: {6: {sorter: 'hex'}}});

  // Set up robots for tablesorter
  
  $("#robots").tablesorter({headers: {3: {sorter: 'hex'}}});
 
  //$("#tracker, #robots").addClass('tablesorter');

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
    // be either a string or an array. The Javascript variable myIp is
    // made from $S->myIp, which is an array, by doing $myIp = implode(',',
    // $S->myIP) and then in webstats.php adding it as a Javascript
    // varible.

    $("#logagent tbody td:nth-child(1)").each(function(i, v) {
      if(myIp.indexOf($(v).text()) !== -1) {
        $(v).css("color", "red");
      }
    });

    $("#logagent tbody td:nth-child(2)").each(function(i, v) {
      v = $(v);
      v.html((v.html().replaceAll(/</g, "&lt;")).replaceAll(/>/g, "&gt;"));
    });
    
    $("#tracker tbody td:nth-child(1) span.co-ip").each(function(i, v) {
      if(myIp.indexOf($(v).text()) !== -1) {
        $(v).parent().css("color", "red").parent().addClass("webmaster").hide();
      }
    });

    // To start bots are hidden

    $(".bots td:nth-child(3)").css("color", "red").parent().hide();

    // What ever is left is normal

    $("#tracker tbody tr:not(:hidden)").addClass("normal");

    calcAv();
  }

  // Put a couple of buttons before the tracker table

  $("#tracker").parent().before("<p class='desktop'>Ctrl Click on the 'ip' items to <span id='ip'>Show Only ip</span>.<br>"+
                       "Alt Click on the 'ip' items to <span class='red'>Show http://ipinfo.io info</span><br>"+
                       "Double Click on the 'page' items to <span id='page'>Show Only page</span>.<br>"+
                       "Click on the 'js' items to see human readable info.<br>"+
                       "Average stay time: <span id='average'></span> (times over two hours are discarded.)</p>"+
                       "<button id='webmaster'>Show webmaster</button>"+
                       "<button id='bots'>Show bots</button>"+
                       "<button id='all'>Show All</button><br>"+
                       "<button id='update'>Update Fields</button>"+
                       "<button id='ip6only'>Hide IPV6</button>"                       
                      );

  // Do this after the 'average' id is set.

  getcountry();
  dotracker();

  // ShowHide all where js == 0

  $("#all").on("click", function(e) {
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

  $("#webmaster").on("click", function(e) {
    if(flags.webmaster) {
      hideIt('webmaster');
    } else {
      // Show
      showIt('webmaster');
    }
  });

  // Ip6only
  
  $("#ip6only").on("click", function(e) {
    $("#tracker tbody tr td:nth-child(1)").each(function(i, v) {
      if($(this).text().match(/:/) != null ) {
        if(flags.ip6 === true) {
          $(this).parent().show();
        } else {
          $(this).parent().hide();
        }
      }
    });
    if(flags.ip6 === false) {
      $("#ip6only").text("Hide IPV6");
    } else {
      $("#ip6only").text("Show IPV6")
    }
    flags.ip6 = !flags.ip6;
  });
  
  // ShowHideBots clicked

  $("#bots").on("click", function() {
    if(flags.bots) {
      // hide
      hideIt('bots');
    } else {
      // show
      showIt('bots');
    }
  });

  // Update the tracker info.
  // BLP 2021-03-24 -- thesite and myIp are set in a script in
  // webstats.php
  
  $("#update").on("click", function() {
    $.ajax({
      url: directory+'/webstats-ajax.php',
      data: {page: 'gettracker', site: thesite},
      type: 'post',
      success: function(data) {
             $("#tracker").html(data);
             $("#tracker").tablesorter({headers: {6: {sorter: 'hex'}}});

             getcountry();
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

  // Second field 'page' dbl clicked

  $("body").on('dblclick', '#tracker td:nth-child(2)', function() { // This is 'page'
    if(flags.page) { // if true
      flags.page = false;

      $("#tracker tr").removeClass('page');

      for(var f in flags) {
        if(flags[f] == true) {
          $("."+f).show();
        }
      }
      $(".normal").show();
      msg = "Show Only Page";
    } else {
      flags.page = true;
      let page = $(this).text();
      $("#tracker td:nth-child(2)").each(function(i, v) {
        if($(v).text() == page) {
          $(v).parent().addClass('page');
        }
      });
      $("#tracker tr").not(".page").hide();
      msg = "Show All Page";
    }
    $("#page").text(msg);
  });

  // The robots tables doesn't need to be deligated.
  
  $("#robots").parent().before("<p class='desktop'>Double Click the 'agent' items to <span class='botsshowhide'>Show Only</span> Agent<br>" +
                      "Click the 'bots' items for human readable info.</p>");
  $("#robots2").parent().before("<p class='desktop'>Double Click the 'agent' items to <span class='botsshowhide'>Show Only</span> Agent</p>");
  
  $("#robots td:nth-child(2), #robots2 td:nth-child(2)").on("dblclick", function() {
    let tr = $(this).closest('table').find('tr');
    let showhide = $(this).closest('table').prev().find('.botsshowhide');

    if(!this.flag) {
      let agent = $(this).text();
      tr.each(function(i, v) {
        if($("td:nth-of-type(2)", v).text() != agent) {
          $(v).hide();
        }
      });
      showhide.text("Show All");
    } else {
      tr.show();
      showhide.text("Show Only");
    }
    this.flag = !this.flag;
  });

  // For analysis. Replace the <form ..> stuff with this.
  // BLP 2021-03-24 -- removed form replacement stuff

  // A click anywhere will remove #FindBot which is used for the bots,
  // for the isJavaScript 'human' and ipinfo.io.
  // There can only be one of these Id's at a time.
  
  $("body").on("click", function(e) {
    $("#FindBot").remove();
  });

  // Click on the ip address of any of the tables.
  // Look for ctrlKey and does show only ip.
  // Looks for altKey and does http://ipinfo.io via curl to get info on
  // ip.

  $("#logagent, #tracker, #robots, #robots2").on("click", "td:first-child", function(e) {
    if(e.ctrlKey) {
      console.log("delegateTarget.id: " + e.delegateTarget.id);
      
      if(e.delegateTarget.id == 'tracker') {
        if(flags.ip) {
          flags.ip = false;
          $(".ip").removeClass("ip").hide();
          for(var f in flags) {
            if(flags[f] == true) {
              $("."+f).show();
            }
          }
          $(".normal").show();
          msg = "Show Only ID";
        } else {
          flags.ip = true;
          let ip = $(this).text();
          $("#tracker td:first-child").each(function(i, v) {
            if($(v).text() == ip) {
              $(v).parent().addClass('ip');
            }
          });
          $("#tracker tbody tr").not(".ip").hide();
          msg = "Show All ID";
        }
        $("#ip").text(msg);
        flag0 = !flag0;
        return;
      }
    }

    let ip = $(".co-ip", this).text();
    let ypos = e.pageY;
    
    if(e.altKey) { // Alt key?
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
              "background-color: white; border: 5px solid black;padding: 10px'>"+
              data+"</div>").appendTo("body");
        },
        error: function(err) {
          console.log(err);
        }
      });
    } else { // No alt.
      let bottom = $(this).offset()['top'] + $(this).height();

      $.ajax({
        url: directory+"/webstats-ajax.php",
        data: {page: 'findbot', ip: ip},
        type: "POST",
        success: function(data) {
          $("#FindBot").remove();
          $("<div id='FindBot' style='position: fixed;top: 10px; "+
              "background-color: white; border: 5px solid black;padding: 10px'>"+
              data+"</div>").appendTo("body");

          if($("#FindBot").height() > window.innerHeight) {
            $("#FindBot").remove();
            $("<div id='FindBot' style='position: absolute;top: "+bottom+"px; "+
                "background-color: white; border: 5px solid black;padding: 10px'>"+
                data+"</div>").appendTo("body");
          }
        },
        error: function(err) {
          console.log(err);
        }
      });
    }
    e.stopPropagation();
  });

  // Popup a human version of 'isJavaScript'

  $("body").on("click", "#tracker td:nth-child(7), #robots td:nth-child(4)", function(e) {
    let js = parseInt($(this).text(), 16),
    human, h = '', ypos, xpos;

    // The td is in a tr which in in a tbody, so table is three
    // prents up.

    if($(this).closest("table").attr("id") != 'tracker') {
      human = {3: "Robots", 0xc: "SiteClass", 0x30: "Sitemap", 0xc0: "Cron", 0x100: "Zero"};
      xpos = e.pageX;
    } else {
      human = {
        1: "Start", 2: "Load", 4: "Script", 8: "Normal",
        0x10: "NoScript", 0x20: "B-PageHide", 0x40: "B-Unload", 0x80: "B-BeforeUnload",
        0x100: "T-BeforeUnload", 0x200: "T-Unload", 0x400: "T-PageHide",
        0x1000: "Timer", 0x2000: "Bot", 0x4000: "Csstest"
      };
      xpos = e.pageX - 200;
    }

    ypos = e.pageY;

    if(js == 0) {
      h = 'curl';
    } else {
      for(let [k, v] of Object.entries(human)) {
        h += (js & k) ? v + "<br>" : '';
      }
    }

    $("#FindBot").remove();
    $("body").append("<div id='FindBot' style='position: absolute; top: "+ypos+"px; left: "+xpos+"px; "+
                     "background-color: white; border: 5px solid black; "+
                     "padding: 10px;'>"+h+"</div>");

    e.stopPropagation();
  });
});

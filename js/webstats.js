/*
 * webstats.js for http://www.bartonphillips.net/webstats.php. Uses
 * webstats-ajax.php for AJAX calls.
 */
// BLP 2021-10-25 -- Maps and geo logic moved to geo.js
// BLP 2021-03-24 -- see comments this date.
// BLP 2016-11-27 -- see comments this date.

const flags = {all: false, webmaster: false, bots: false, ip6: true};
const path = document.location.pathname;
const ajaxurl = 'https://bartonphillips.net/webstats-ajax.php'; // URL for all ajax calls.

// For 'tracker'
// The .bots class is set in webstats-ajax.php.
// homeIp, thesite, myIp, robots and tracker are set in
// webstats.php in the inlineScript.

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
  let msg = "Show ";
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
  let msg = "Hide ";
  $("#"+ f).text(msg + f);
  calcAv();
  return;
}

function calcAv() {
  // Calculate the average time spend using the NOT hidden elements

  let av = 0, cnt = 0;

  $("#tracker tbody :not(:hidden) td:nth-child(7)").each(function(i, v) {
    let t = $(this).text();
    if(t == '' || t == 0 || (typeof t == 'undefined')) {
      //console.log("t:", t);
      return true; // Continue, don't count blank
    }

    //console.log("t", t);

    let ar = t.match(/^(\d+):(\d{2}):(\d{2})$/);
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
  let hours = Math.floor(av / (3600)); 

  let divisor_for_minutes = av % (3600);
  let minutes = Math.floor(divisor_for_minutes / 60);

  let divisor_for_seconds = divisor_for_minutes % 60;
  let seconds = Math.ceil(divisor_for_seconds);

  let tm = hours.pad()+":"+minutes.pad()+":"+seconds.pad();

  $("#average").html(tm);
}

Number.prototype.pad = function(size) {
  let s = String(this);
  while (s.length < (size || 2)) {s = "0" + s;}
  return s;
}

function getcountry() {
  let ip = $("#tracker tr td:first-child");
  let ar = new Array;

  ip.each(function() {
    let ipval = $(this).text();
    // remove dups. If ipval is not in the ar array add it once.
    if(!ar[ipval]) {
      ar[ipval] = 1; // true
    }
  });

  // we have made ipval true so we do not have duplicate

  ar = JSON.stringify(Object.keys(ar)); // get the key which is ipval and make a string like '["123.123.123.123", "..."', ...]'

  $.ajax(ajaxurl, {
    type: 'post',
    data: {list: ar},
    success: function(co) {
      let com = JSON.parse(co); // com is an array of countries by ip.
            
      ip.each(function(i) { // ip is the first td. We look at each td.
        ip = $(this).text();
        co = com[ip]; // co is the country
    
        // We make co-ip means country-ip.

        $(this).html("<span class='co-ip'>"+ip+"</span><br><div class='country'>"+co+"</div>");
      });
    },
    error: function(err) {
      console.log("ERROR:", err);
    }
  });
}

// Function to do all the stuff for tracker when it is Ajaxed in

function dotracker() {
  // To start Webmaster is hidden
  
  $("#logagent tbody td:nth-child(1)").each(function(i, v) {
    if(myIp.indexOf($(v).text()) !== -1) { // myIp was set in webstats.php in inlineScript
      if(homeIp === ($(v).text())) { // homeIp was set in webstats.php in inlineScript
        $(v).css({"color": "white", "background": "green"});
      } else {
        $(v).css({"color": "black", "background": "lightgreen"});
      }
    }
  });

  $("#logagent tbody td:nth-child(2)").each(function(i, v) {
    v = $(v);
    v.html((v.html().replaceAll(/</g, "&lt;")).replaceAll(/>/g, "&gt;"));
  });

  // Set class webmaster colors.
  
  $("#tracker tbody td:nth-child(1) span.co-ip").each(function(i, v) {
    if(myIp.indexOf($(v).text()) !== -1) { // myIp was set in webstats.php in inlineScript
      if(homeIp === ($(v).text())) { // homeIp was set in webstats.php in inlineScript
        $(v).parent().css({ "color":"white", "background":"green"}).parent().addClass("webmaster").hide();
      } else {
        $(v).parent().css({"color":"black", "background":"lightgreen"}).parent().addClass("webmaster").hide();
      }
    }
  });

  // To start bots are hidden

  $(".bots td:nth-child(4)").css("color", "red").parent().hide();
  
  // What ever is left is normal

  $("#tracker tbody tr:not(:hidden)").addClass("normal");

  calcAv();
}

function ipaddress(e, self) {
  if(e.ctrlKey) {
    let msg;
    
    console.log("delegateTarget.id: " + e.delegateTarget.id);

    if(e.delegateTarget.id == 'tracker') {
      if(flags.ip) {
        flags.ip = false;
        $(".ip").removeClass("ip").hide();
        for(let f in flags) {
          if(flags[f] == true) {
            $("."+f).show();
          }
        }
        $(".normal").show();
        msg = "Show Only ID";
      } else {
        flags.ip = true;
        let ip = $(self).text();
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

  let ip = $("span", self).text();
  let pos = $(self).position();
  let xpos = pos.left + $(self).width() + 10;
  let ypos = pos.top;
  let table = $(self).closest('table');

  console.log("IP: "+ip);

  if(e.altKey) { // Alt key?
    $.ajax(ajaxurl, {
        //url: directory+"/webstats-ajax.php",
      data: {page: 'curl', ip: ip},
      type: "post",
      success: function(data) {
        console.log(data);
          // For mobile devices there is NO ctrKey! so we don't
          // need to worry about position fixed not working!

        $("#FindBot").remove();
        table.append("<div id='FindBot' style='position: absolute;top: "+ypos+"px;left:"+xpos+"px;"+
                     "background-color: white; border: 5px solid black;padding: 10px'>"+
                     data+"</div>");
      },
      error: function(err) {
        console.log(err);
      }
    });
  } else { // No alt.
    let bottom = $(self).offset()['top'] + $(self).height();

    $.ajax(ajaxurl, {
        //url: directory+"/webstats-ajax.php",
      data: {page: 'findbot', ip: ip},
      type: "post",
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
}

function gettracker() {
  $.ajax(ajaxurl, {
    //url: directory+'/webstats-ajax.php',
    data: {page: 'gettracker', site: thesite}, // thesite is set in webstats via inlineScript
    type: 'post',
    success: function(data) {
      $("#trackerdiv").html(data);
      $("#tracker").tablesorter({theme: 'blue', headers: {6: {sorter: 'hex'}}});

      // Put a couple of buttons before the tracker table

      $("#tracker").parent().before("<div id='beforetracker'>Ctrl Click on the 'ip' items to <span id='ip'>Show Only ip</span>.<br>"+
                                    "Alt Click on the 'ip' items to <span class='red'>Show http://ipinfo.io info</span><br>"+
                                    "Double Click on the 'page' items to <span id='page'>Show Only page</span>.<br>"+
                                    "Click on the 'js' items to see human readable info.<br>"+
                                    "Average stay time: <span id='average'></span> (times over two hours are discarded.)<br>"+
                                    "<button id='webmaster'>Show webmaster</button>"+
                                    "<button id='bots'>Show bots</button>"+
                                    "<button id='all'>Show All</button><br>"+
                                    "<button id='update'>Update Fields</button>"+
                                    "<button id='ip6only'>Hide IPV6</button>"+
                                    "</div>"
                                   );

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

      // ShwoHide Webmaster

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

      // ShowHideBots

      $("#bots").on("click", function() {
        if(flags.bots) {
          // hide
          hideIt('bots');
        } else {
          // show
          showIt('bots');
        }
      });

      // Update the tracker info by getting the latest stuff.

      $("#update").on("click", function() {
        $("#beforetracker").remove();
        gettracker();
      });

      // Second field 'page' dbl clicked

      $("body").on('dblclick', '#tracker td:nth-child(2)', function() { // This is 'page'
        let msg;
        
        if(flags.page) { // if true
          flags.page = false;

          $("#tracker tr").removeClass('page');

          for(let f in flags) {
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

      $("#tracker td:first-child").on("click", function(e) {
        ipaddress(e, this);
      });
      
    }, error: function(err) {
      console.log(err);
    }
  });
}

jQuery(document).ready(function($) {
  $("#robots2 td:nth-of-type(4)").each(function() {
    let botCode = $(this).text();
    
    $(this).text(robots[botCode]); // robots was set in webstats.php in inlineScript
  });
  
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

  // Set up robots for tablesorter
  
  $("#robots").tablesorter({headers: {3: {sorter: 'hex'}}});
 
  // Do this after the 'average' id is set.

  gettracker();

  // The robots tables doesn't need to be deligated.
  
  $("#robots").parent().before("Double Click the 'agent' items to <span class='botsshowhide'>Show Only</span> Agent<br>" +
                      "Click the 'bots' items for human readable info.");
  $("#robots2").parent().before("Double Click the 'agent' items to <span class='botsshowhide'>Show Only</span> Agent");

  // This is the agent fiels on both tables. If we double click it
  // toggles between showing all and showing only the one you double
  // clicked on.
  
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

  $("#logagent, #robots, #robots2").on("click", "td:first-child", function(e) {
    ipaddress(e, this);
  });

  // Popup a human version of 'isJavaScript'

  $("body").on("click", "#tracker td:nth-child(8), #robots td:nth-child(4)", function(e) {
    let js = parseInt($(this).text(), 16),
    h = '', ypos, xpos;
    let human;

    // Make it look like a hex. Then and it with 0x100 if it is true
    // then make js 0x1..
    
    //if('0x'+js & 0x100) js='0x'+js;
    
    let table = $(this).closest("table");
    let pos = $(this).position(); // get the top and left
    let id = table.attr("id");
    
    // The td is in a tr which in in a tbody, so table is three
    // prents up.

    if(id != 'tracker') {
      // Robots (bots table)
      
      human = robots; // robots was set in webstats.php in the inlineScript.
      
      xpos = pos.left + $(this).width() + 17; // add the one border and one padding (15px) plus a mig.
    } else {
      // Tracker table.
      
      human = tracker; // tracker was set in webstats.php in the inlineScript
      
      xpos = pos.left - 300; // Push this to the left so it will render full size
    }
    ypos = pos.top;

    for(let [k, v] of Object.entries(human)) {
      h += (js & k) ? v + "<br>" : '';
    }
    
    $("#FindBot").remove();

    // Now append FindBot to the table.
    
    table.append("<div id='FindBot' style='position: absolute; top: "+ypos+"px; left: "+xpos+"px; "+
                 "background-color: white; border: 5px solid black; "+
                 "padding: 10px;'>"+h+"</div>");

    if(id == "tracker") {
      // For tracker recalculate the xpos based on the size of the
      // FindBot item.
      
      xpos = pos.left - ($("#FindBot").width() + 35); // we add the border and padding (30px) plus a mig.
      $("#FindBot").css("left", xpos + "px");
    }
    
    e.stopPropagation();
  });

  // BLP 2021-12-24 -- tracker agent field look for http: or https:

  $("body").on("click", "#tracker td:nth-child(4)", function(e) {
    if($(this).css("color") == "rgb(255, 0, 0)") {
      const txt = $(this).text();
      const pat = /(http.?:\/\/.*)[)]/;
      const found = txt.match(pat);
      if(found) {
        window.open(found[1], "bot");
      }
      console.log("found: "+found);
    }
  });
});

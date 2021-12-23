<?php
// Does all of the AJAX for webstats.js
// The main program is webstats.php

$_site = require_once(getenv("SITELOADNAME"));

// Turn an ip address into a long. This is for the country lookup

function Dot2LongIP($IPaddr) {
  if(strpos($IPaddr, ":") === false) {
    if($IPaddr == "") {
      return 0;
    } else {
      $ips = explode(".", "$IPaddr");
      return ($ips[3] + $ips[2] * 256 + $ips[1] * 256 * 256 + $ips[0] * 256 * 256 * 256);
    }
  } else {
    //error_log("IPaddr: $IPaddr");
    $int = inet_pton($IPaddr);
    $bits = 15;
    $ipv6long = 0;

    while($bits >= 0) {
      $bin = sprintf("%08b", (ord($int[$bits])));
      if($ipv6long){
        $ipv6long = $bin . $ipv6long;
      } else {
        $ipv6long = $bin;
      }
      $bits--;
    }
    $ipv6long = gmp_strval(gmp_init($ipv6long, 2), 10);
    //error_log("ipv6long: $ipv6long");
    return $ipv6long;
  }
}

// Ajax via list='json string of ips', like ["123.123.123.123", "..."', ...].
// Given a list of ip addresses get a list of countries as $ar[$ip] = $name of country.

if($list = $_POST['list']) {
  $S = new Database($_site);
  $list = json_decode($list); // turn json string backinto an array.

  $ar = array();

  foreach($list as $ip) {
    $iplong = Dot2LongIP($ip);
    if(strpos($ip, ":") === false) {
      $table = "ipcountry";
    } else {
      $table = "ipcountry6";
    }
    $sql = "select countryLONG from $S->masterdb.$table ".
           "where '$iplong' between ipFROM and ipTO";

    $S->query($sql);
    
    list($name) = $S->fetchrow('num');
    //error_log("name: $name");
    
    $ar[$ip] = $name;
  }

  $ret = json_encode($ar);
  //error_log("ret: $ret");
  echo $ret;
  exit();
}

// Ajax via page=curl, proxy for curl http://ipinfo.io/<ip>

if($_POST['page'] == 'curl') {
  $ip = $_POST['ip'];

  $cmd = "http://ipinfo.io/$ip";
  $ch = curl_init($cmd);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $loc = json_decode(curl_exec($ch));
  $locstr = "Hostname: $loc->hostname<br>$loc->city, $loc->region $loc->postal<br>Location: $loc->loc<br>ISP: $loc->org<br>";

  echo $locstr;
  exit();
}

// Ajax via page=findbot. Search the bots table looking for all the records with ip

if($_POST['page'] == 'findbot') {
  $S = new Database($_site);
  
  $ip = $_POST['ip'];

  $human = [3=>"Robots", 0xc=>"SiteClass", 0x30=>"Sitemap", 0xc0=>"cron", 0x100=>"zero"];
  
  $S->query("select agent, site, robots, count, creation_time from barton.bots where ip='$ip'");

  $ret = '';

  while(list($agent, $who, $robots, $count, $created) = $S->fetchrow('num')) {
    $h = '';
    
    foreach($human as $k=>$v) {
      $h .= $robots & $k ? "$v " : '';
    }

    $bot = sprintf("%X", $robots);
    $ret .= "<tr><td>$who</td><td>$agent</td><td>$h</td><td>$created</td><td>$count</td></tr>";
  }

  if(empty($ret)) {
    $ret = "<div style='background-color: pink; padding: 10px'>$ip Not In Bots</div>";
  } else {
    $ret = <<<EOF
<style>
#FindBot table {
  width: 100%;
}
#FindBot table td:first-child {
  width: 10rem;
}
#FindBot table td:nth-child(2) {
  word-break: break-all;
}
#FindBot table td:nth-child(3) {
  width: 5rem;
}
#FindBot table td:nth-child(4) {
  width: 7rem;
}
#FindBot table * {
  border: 1px solid black;
}
</style>
<table>
<thead>
  <tr><th>$ip</th><th>Agent</th><th>Human</th><th>Created</th><th>Count</th></tr>
</thead>
<tbody>
$ret
</tbody>
</table>
EOF;
  }
  echo $ret; 
  exit();
}

// AJAX via page=gettrackedr. site=thesite ($S->siteName)
// Get the info form the tracker table again.

if($_POST['page'] == 'gettracker') {
  $S = new Database($_site);
  $T = new dbTables($S);
  $site = $_POST['site'];

  // Callback function for maketable()

  function callback1(&$row, &$desc) {
    global $S;

    $ip = $S->escape($row['ip']);

    $row['ip'] = "<span class='co-ip'>$ip</span><br>";
    $row['refid'] = preg_replace('/\?.*/', '', $row['refid']);

    if(($row['js'] & 0x2000) === 0x2000) {
      $desc = preg_replace("~<tr>~", "<tr class='bots'>", $desc);
    }
    $row['js'] = dechex($row['js']);
    $t = $row['difftime'];
    if(empty($t)) return;
    
    $hr = floor($t/3600);
    $min = floor(($t%3600)/60);
    $sec = ($t%3600)%60;
      //echo "$ip, t=$t, hr: $hr, min: $min, sec: $sec<br>";
    $row['difftime'] = sprintf("%u:%02u:%02u", $hr, $min, $sec);
  } // End callback

  $sql = "select ip, page, agent, starttime, endtime, difftime, isJavaScript as js, refid ".
         "from $S->masterdb.tracker " .
         "where site='$site' and starttime >= current_date() - interval 24 hour ". 
         "order by starttime desc";

  list($tracker) = $T->maketable($sql, array('callback'=>callback1,
                                             'attr'=>array('id'=>'tracker', 'border'=>'1')));

  echo $tracker;
  exit();
}

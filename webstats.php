<?php
// All sites do a Symlink to ../bartonphillipsnet for webstats.php. The symlink is needed because we need to
// get the mysitemap.json for the specific website.
// The css is at https://bartonphillips.net/css/webstats.css
// BLP 2021-10-24 -- add Maps and geo logic like in getcookie.php
// BLP 2021-10-22 -- See getcookie.php for info on debugging.
// BLP 2021-10-12 -- Add geo logic
// BLP 2021-10-10 -- bots2 don't get site.
// BLP 2021-09-26 -- Simplify this whole thing. Remove getwebstats() and renderpage(). Now I have only one Render with an echo.
// BLP 2021-09-20 -- get the $ip array from the myip table. Don't use $S->myUrl.
// BLP 2021-06-08 -- Moved webstats.ajax.php to bartonphillipsnet/
// BLP 2021-06-05 -- There is no $S->membertable so remove it and tabel7a reference.
// BLP 2021-03-27 -- remove myip table stuff.
// BLP 2021-03-24 -- latest version of tablesorter (not used in some other files!).
// BLP 2021-03-22 -- Removed daycountwhat from daycounts. Added div class scrolling to most tables.
// Special case for Tysonweb.
// BLP 2018-01-07 -- changed tracker order by starttime to lasttime
// BLP 2017-03-23 -- set up to work with https  

// Form post. I do this because each domain has a symlink to webstats.php and I need to use the mysitemap.json form that domein.

if(isset($_POST['submit'])) {
  $siteName = $_POST['site'];

  // use header() to go to the loocation.
  
  switch($siteName) {
    case 'Allnatural': 
      header("Location: https://www.allnaturalcleaningcompany.com/webstats.php?blp=8653");
      break;
    case 'BartonlpOrg':
      header("Location: https://www.bartonlp.org/webstats.php?blp=8653");
      break;
    case 'Bartonphillips':
      header("Location: https://www.bartonphillips.com/webstats.php?blp=8653");
      break;
    case 'Tysonweb':
      header("Location: https://www.newbern-nc.info/webstats.php?blp=8653");
      break;
    case 'Newbernzig':
      header("Location: http://www.newbernzig.com/webstats.php?blp=8653");
      break;
    default:
      echo "OPS something went wrong: siteName: $siteName";
  }
  exit();
} 

// Instantiate our SiteClass

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);  
$S = new $_site->className($_site);
//vardump("S", $S);

// Check for magic 'blp'. If not found check if one of my recent ips. If not justs 'Go Away'

if(empty($_GET['blp']) || $_GET['blp'] != '8653') { // If blp is empty or set but not '8653' then check $S->myIp
  // myIp can be an array made from the myUri from mysitemap.json
  // it already has all of the data from the myip table along with anything from myUri.
  
  if(is_array($S->myIp)) {
    // Is one of the ips my ip?

    if(!array_intersect([$S->ip], $S->myIp)) {
      echo "<h1>Go Away</h1>";
      exit();
    }
  } else {
    if($S->ip != $S->myIp) {
      echo "<h1>Go Away</h1>";
      exit();
    }
  }
} 

$visitors = [];
$jsEnabled = [];

$h->link = <<<EOF
  <link rel="stylesheet" href="https://bartonphillips.net/css/newtblsort.css">
  <link rel="stylesheet" href="https://bartonphillips.net/css/webstats.css"> 
EOF;

$h->css = <<<EOF
<style>
/** {
  box-sizing: border-box !important;
}*/
.home {
  color: white;
  background: green;
  padding: 0 5px;
}
body { margin: 10px; }
#mygeo td {
  padding: 10px;
  cursor: pointer;
}

#geocontainer {
  width: 500px;
  height: 500px;
  margin-left: auto;
  margin-right: auto;
  border: 5px solid black;
  z-index: 20;
}
#showMe, #showAll {
  border-radius: 5px;
  color: white;
  background: green;
  padding: 2px;
}
#removemsg {
  display: none;
  color: white;
  background: red;
  border-radius: 5px;
  padding: 2px;
}
#outer {
  display: none;
  position:
  absolute;
  z-index: 20;
  padding-bottom: 30px;
}
@media (max-width: 850px) {
  html { font-size: 10px; }
  #geocontainer {
    width: 360px;
    height: 360px;
  }
}
@media (hover: none) and (pointer: coarse) {
  html { font-size: 10px; }
  #resetmsg { padding-inline-start: 5px; }
  #members td { width: 50px; };  
  /* mygeo is the table */
  #mygeo td {
    padding: 2px 2px 2px 5px;
    width: 50px;
  }
  /* geo is the div for the image */
  #geocontainer {
    width: 360px;
    height: 360px;
  }
}
</style>
EOF;

if(is_array($S->myIp)) {
  $myIp = implode(",", $S->myIp);
} else {
  $myIp = $S->myIp;
}

$homeIp = gethostbyname("bartonphillips.dyndns.org");

// Set up the javascript variables it needs from PHP

$h->script = <<<EOF
<script>
  var thesite = "$S->siteName";
  var myIp = "$myIp"; // $myIp has all of the data from the myip table and myUri
  var homeIp = "$homeIp"; // my home ip
</script>
<!-- BLP 2021-03-24 -- this is the latest version of tablesorter -->
<script src="https://bartonphillips.net/tablesorter-master/dist/js/jquery.tablesorter.min.js"></script>
<script src="https://bartonphillips.net/js/webstats.js"></script>
EOF;

// BLP 2021-10-12 -- add geo logic and Maps
if(array_intersect([$S->siteName], ['Bartonphillips', 'Tysonweb', 'Newbernzig'])[0]) {
  $b->script = <<<EOF
<script src="https://bartonphillips.net/js/maps.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAsfESZ7BBan6SX2qeCg3xDfzZbQLERo0U&callback=initMap&v=weekly" async></script>
EOF;
}

$h->title = "Web Statistics";

$h->banner = "<h1 id='maintitle' class='center'>Web Stats For <b>$S->siteName</b></h1>";

[$top, $footer] = $S->getPageTopBottom($h, $b);

$homeip = gethostbyname("bartonphillips.dyndns.org"); // My home ip. Updated via ddclient.
 
$T = new dbTables($S); // My table class

// BLP 2021-09-20 -- get the $ip array from the myip table.
  
function blphome(&$row, &$rowdesc) {
  global $homeip;
    
  if($row['myIp'] == $homeip) {
    $ip = $row['myIp'];
    $row['myIp'] = "<span class='home'>$ip</span>";
  }
  return false;
}

$sql = "select myip as myIp, createtime as Created, lasttime as Last from $S->masterdb.myip order by lasttime";

[$tbl] = $T->maketable($sql, array('callback'=>'blphome', 'attr'=>array('id'=>'blpid', 'border'=>'1')));
  
// end of BLP 2021-03-27
  
$creationDate = date("Y-m-d H:i:s T");

$page = <<<EOF
<hr/>
</script>

<h2>From table <i>myip</i></h2>
<p>These are the IP Addresses used by the Webmaster.<br>
When these addresses appear in the other tables they are in
<span style="color: red">RED</span> or <span style="color: white; background: green; padding: 0 5px;">GREEN</span> if my home IP.</p>
$tbl
EOF;

$sql = "select ip as IP, agent as Agent, count as Count, lasttime as LastTime " .
"from $S->masterdb.logagent ".
"where site='$S->siteName' and lasttime >= current_date() order by lasttime desc";

// BLP 2021-03-27 -- removed callback to blpip along with all ref to blpips.
  
list($tbl) = $T->maketable($sql, array('attr'=>array('id'=>"logagent", 'border'=>"1")));
if(!$tbl) {
  $tbl = "<h3 class='noNewData'>No New Data Today</h2>";
} else {
  $tbl = <<<EOF
<div class="scrolling">
$tbl
</div>
EOF;
}

$page .= <<<EOF
<h2 id="table3">From table <i>logagent</i> for today</h2>
<a href="#table4">Next</a>
<h4>Showing $S->siteName for today</h4>
$tbl
EOF;

// BLP 2021-08-20 -- 
// Here 'count' is total number of hits (bots and real) so count-realcnt is the number of Bots.
// 'realcnt' is used in $this->hitCount which is the hit counter at the bottom of some pages.
// We do not count BOTS in the hitCount.
// Also we do NOT count me! If isMe() is true we do not count. See myUri.json and mysitemap.json.
// In myUri.json "/ HOME" is bartonphillips.dyndns.org. I have added the DynDns updater to my
// home computer's systemd so the IP address should always be the current IP at DynDns.
  
$sql = "select filename as Page, realcnt as 'Real', (count-realcnt) as 'Bots', lasttime as LastTime ".
"from $S->masterdb.counter ".
"where site='$S->siteName' order by lasttime desc";

$tbl = <<<EOF
<table id="counter" border="1">
<thead>
<tr><th>Page</th><th>Real</th><th>Bots</th><th>Lasttime</th></tr>
</thead>
<tbody>
EOF;
  
if($S->siteName == 'Tysonweb') {
  $g = glob("*.php");

  $del = ['analysis.php', 'phpinfo.php', 'robots.php', 'sitemap.php']; 
  $S->query($sql);

  while([$filename, $count, $bots, $lasttime] = $S->fetchrow('num')) {
    $ar[trim($filename, '/')] = [$count, $bots, $lasttime];
  }

  foreach($g as $name) {
    if(array_intersect([$name], $del)) {
      continue;
    }
    $a = $ar[$name];
    $tbl .= "<tr><td>$name</td><td>$a[0]</td><td>$a[1]</td><td>$a[2]</td></tr>";
  }

  $tbl .= <<<EOF
<tbody>
</table>
EOF;
} else {
  list($tbl) = $T->maketable($sql, array('attr'=>array('border'=>'1', 'id'=>'counter')));
}

if(!$tbl) {
  $tbl = "<h3 class='noNewData'>No New Data Today</h2>";
}

if($S->reset) {
  $reset = " <span style='font-size: 16px;'>(Reset Date: $S->reset)</span>";
}
    
$page .= <<<EOF
<h2 id="table4">From table <i>counter</i> for today</h2>
<a href="#table5">Next</a>
<h4>Showing $S->siteName grand TOTAL hits since last reset $reset for pages viewed today</h4>
<p>'real' is the number of non-bots and 'bots' is the number of robots.</p>
<div class="scrolling">
$tbl
</div>
EOF;

$today = date("Y-m-d");

// 'count' is actually the number of 'Real' vs 'Bots'. A true 'count' would be Real + Bots.

$sql = "select filename as Page, count as 'Real', bots as Bots, lasttime as LastTime ".
"from $S->masterdb.counter2 ".
"where site='$S->siteName' and lasttime >= current_date() order by lasttime desc";

list($tbl) = $T->maketable($sql, array('attr'=>array('border'=>'1', 'id'=>'counter2')));

if(!$tbl) {
  $tbl = "<h3 class='noNewData'>No New Data Today</h2>";
}
  
$page .= <<<EOF
<h2 id="table5">From table <i>counter2</i> for today</h2>
<a href="#table6">Next</a>
<h4>Showing $S->siteName  number of hits TODAY</h4>
$tbl
EOF;

// Get the footer line
  
$sql = "select sum(`real`+bots) as Count, sum(`real`) as 'Real', sum(bots) as 'Bots', ".
"sum(visits) as Visits " .
"from $S->masterdb.daycounts ".
"where site='$S->siteName' and lasttime >= current_date() - interval 6 day";

$S->query($sql);
list($Count, $Real, $Bots, $Visits) = $S->fetchrow('num');

// Use 'tracker' to get the number of Visitors ie unique ip accesses.
// BLP 2018-01-07 -- changed order by from starttime to lasttime.
  
$S->query("select ip, date(lasttime) ".
"from $S->masterdb.tracker where lasttime>=current_date() - interval 6 day ".
"and site='$S->siteName' order by date(lasttime)");

$Visitors = 0;

// There should be ONE UNIQUE ip in the rows. So count them into the date.

$tmp = [];
  
while(list($ip, $date) = $S->fetchrow('num')) {
  $tmp[$date][$ip] = '';
}

foreach($tmp as $d=>$v) { 
  $visitors[$d] = $n = count($v);
  $Visitors += $n;
}
  
// Only show items that are not me.

foreach($S->myIp as $v) {
  $me .= "'" . gethostbyname($v) . "',";
}
$me = rtrim($me, ",");

// This screens me out.
  
$sql = "select count(*), date(starttime) from $S->masterdb.tracker ".
"where date(starttime)>=current_date() - interval 6 day and site='$S->siteName' and ".
"isJavaScript & ~(0x201c) and not (isJavaScript & 0x2000) and ip not in($me) ".
"group by date(starttime) order by date(starttime)";
  
$S->query($sql);

$jsenabled = 0;

while(list($cnt, $date) = $S->fetchrow('num')) {
  $jsEnabled[$date] += $cnt;
  $jsenabled += $cnt;
}

$ftr = "<tr><th>Totals</th><th>$Visitors</th><th>$Count</th><th>$Real</th>".
"<th>$jsenabled</th><th>$Bots</th><th>$Visits</th></tr>";

// Get the table lines
  
$sql = "select date as Date, 'visitors' as Visitors, `real`+bots as Count, `real` as 'Real', 'AJAX', ".
"bots as 'Bots', visits as Visits ".
"from $S->masterdb.daycounts where site='$S->siteName' and ".
"lasttime >= current_date() - interval 6 day order by lasttime desc";

function visit(&$row, &$rowdesc) { // callback from maketable()
  global $visitors, $jsEnabled;

  $row['Visitors'] = $visitors[$row['Date']];
  $row['AJAX'] = $jsEnabled[$row['Date']];
  return false;
}
  
list($tbl) = $T->maketable($sql, array('callback'=>'visit', 'footer'=>$ftr,
'attr'=>array('border'=>"1", 'id'=>"daycount")));

if(!$tbl) {
  $tbl = "<h3 class='noNewData'>No New Data Today</h2>";
}
    
$page .= <<<EOF
<h2 id="table6">From table <i>daycount</i> for seven days</h2>
<a href="#table7">Next</a>

<h4>Showing $S->siteName for seven days</h4>
<p>'Visitors' is the number of distinct IP addresses (via 'tracker' table).<br>
'Count' is the sum of 'Real' and 'Bots', the total number of HITS.<br>
'Real' is the number of non-robots.<br>
'AJAX' is the number of non-robots with AJAX functioning (via 'tracker' table) that are NOT Webmaster.<br>
'Bots' is the number of robots.<br>
'Visits' are hits outside of a 10 minutes interval.<br>
So if you come to the site from two different IP addresses you would be two 'Visitors'.<br>
If you hit our site 10 times the sum of 'Real' and 'Bots' would be 10.<br>
If you hit our site 5 time within 10 minutes you will have only one 'Visits'.<br>
If you hit our site again after 10 minutes you would have two 'Visits'.</p>
$tbl
EOF;

$analysis = file_get_contents("https://bartonphillips.net/analysis/$S->siteName-analysis.i.txt");
if(!$analysis) $errMsg = "https://bartonphillips.net/analysis/$S->siteName-analysis.i.txt: NOT FOUND";

// Callback for tracker below

function trackerCallback(&$row, &$desc) {
  global $S;

  $ip = $S->escape($row['ip']);

  $row['ip'] = "<span class='co-ip'>$ip</span><br>";
  $row['refid'] = preg_replace('/\?.*/', '', $row['refid']);

  if(($row['js'] & 0x2000) === 0x2000) {
    $desc = preg_replace("~<tr>~", "<tr class='bots'>", $desc);
  }
  $row['js'] = dechex($row['js']);
  $t = $row['difftime'];
  if(is_null($t)) {
    //echo "$ip, t=$t<br>";
    return;
  }
    
  $hr = $t/3600;
  $min = ($t%3600)/60;
  $sec = ($t%3600)%60;

  $row['difftime'] = sprintf("%u:%02u:%02u", $hr, $min, $sec);
}

// BLP 2018-01-07 -- changed from order by starttime to lasttime
  
$sql = "select ip, page, agent, starttime, endtime, difftime, isJavaScript as js, refid ".
"from $S->masterdb.tracker ".
"where site='$S->siteName' and starttime >= current_date() - interval 24 hour ". 
"order by lasttime desc";

list($tracker) = $T->maketable($sql, array('callback'=>'trackerCallback',
'attr'=>array('id'=>'tracker', 'border'=>'1')));

$tracker = <<<EOF
<div class="scrolling">
$tracker
</div>
EOF;
  
function botsCallback(&$row, &$desc) {
  global $S;

  $ip = $S->escape($row['ip']);

  $row['ip'] = "<span class='bots-ip'>$ip</span><br>";
}
  
$sql = "select ip, agent, count, hex(robots) as bots, site, creation_time as 'created', lasttime ".
"from $S->masterdb.bots ".
"where site like('%$S->siteName%') and lasttime >= current_date() - interval 24 hour and count !=0 order by lasttime desc";

list($bots) = $T->maketable($sql, array('callback'=>'botsCallback',
'attr'=>array('id'=>'robots', 'border'=>'1')));

$bots = <<<EOF
<div class="scrolling">
$bots
</div>
EOF;
  
function bots2Callback(&$row, &$desc) {
  global $S;

  $ip = $S->escape($row['ip']);

  $row['ip'] = "<span class='bots2-ip'>$ip</span><br>";
}

// BLP 2021-10-10 -- remove site from select for everyone

$sql = "select ip, agent, which, count from $S->masterdb.bots2 ".
"where site='$S->siteName' and date >= current_date() - interval 24 hour order by lasttime desc";

list($bots2) = $T->maketable($sql, array('callback'=>'bots2Callback',
'attr'=>array('id'=>'robots2', 'border'=>'1')));

$bots2 = <<<EOF
<div class="scrolling">
$bots2
</div>
EOF;
  
$date = date("Y-m-d H:i:s T");

// BLP 2021-10-10 -- Display even for Tysonweb

$form = <<<EOF
<form action="webstats.php" method="post">
  Select Site:
  <select name='site'>
    <option>Allnatural</option>
    <option>BartonlpOrg</option>
    <option>Bartonphillips</option>
    <option>Tysonweb</option>
    <option>Newbernzig</option>
  </select>

  <button type="submit" name='submit'>Submit</button>
</form>
EOF;

// BLP 2021-10-08 -- add geo

$today = date("Y-m-d");

function mygeofixup(&$row, &$rowdesc) {
  global $today;

  $me = [
         'hFBzuVRDeIWdbhXmhZv7' => "HP",
         'e30hJHxUeaToTAB6g4Zv' => "TAB",
         'agvmgLtbOej09pGw27ZF' => "LAP",
         'Z1Kx9vql4QxiMB9brOd2' => "i12",
        ];
  
  foreach($me as $key=>$val) {
    if($row['finger'] == $key) {
      $row['finger'] .= "<span class='ME' style='color: red'> : $val</span>";
    }
  }

  // BLP 2021-10-24 --
  
  if(strpos($row['lasttime'], $today) === false) {
    $row['lasttime'] = "<span class='OLD'>{$row['lasttime']}</span>";
  }

  return false;
}

if(array_intersect([$S->siteName], ['Bartonphillips', 'Tysonweb', 'Newbernzig'])[0] !== null) {
  $sql = "select lat, lon, finger, created, lasttime from geo order by lasttime desc";
  [$tbl] = $T->maketable($sql, ['callback'=>'mygeofixup', 'attr'=>['id'=>'mygeo', 'border'=>'1']]);

  // BLP 2021-10-12 -- add geo logic
  $geotbl = <<<EOF
<h2 id="table11">From table <i>geo</i></h2>
<a href="#analysis-info">Next</a>
<div id="geotable">
<div id="outer">
<div id="geocontainer"></div>
<button id="removemsg">Click to remove map image</button>
</div>
<p>Click on table row to view map.<br>
<button id="showMe">Show Me</button>&nbsp;<button id="showAll">Show All</button>
</p>
$tbl
</div>
EOF;
  
  $geoTable = "<li><a href='#table11'>Goto Table: geo</a></li>";
} else {
  $botsnext = "<a href='#analysis-info'>Next</a>";
}

// BLP 2021-06-23 -- Only bartonphillips.com has a members table.

if($S->memberTable) {
  $sql = "select name, email, ip, agent, created, lasttime from $S->memberTable";

  list($tbl) = $T->maketable($sql, array('attr'=>array('id'=>'members', 'border'=>'1')));

  if($geotbl) {
    $mTable = "<li><a href='#table10'>Goto Table: $S->memberTable</a></li>";
    $botsnext = "<a href='#table10'>Next</a>";
    $togeo = "<a href='#table11'>Next</a>";
  } else {
    $togeo = "<a href='#analysis-info'>Next</a>";
  }
  
  $mtbl = <<<EOF
<h2 id="table10">From table <i>$S->memberTable</i></h2>
$togeo
<div id="memberstable">
$tbl
</div>
EOF;
} else {
  $botsnext = $geotbl ? "<a href='#table11'>Next</a>" : "<a href='#analysis-info'>Next</a>";
}

// Render page

echo <<<EOF
$top
<div id="content">
$errMsg
$form
<main>
<p>$date</p>
<ul>
   <li><a href="#table3">Goto Table: logagent</a></li>
   <li><a href="#table4">Goto Table: counter</a></li>
   <li><a href="#table5">Goto Table: counter2</a></li>
   <li><a href="#table6">Goto Table: daycounts</a></li>
   <li><a href="#table7">Goto Table: tracker</a></li>
   <li><a href="#table8">Goto Table: bots</a></li>
   <li><a href="#table9">Goto Table: bots2</a></li>
$mTable
$geoTable
   <li><a href="#analysis-info">Goto Analysis Info</a></li>
</ul>

<div id="hourly-update">
$page
</div>

<h2 id="table7">From table <i>tracker</i> for last 24 hours</h2>
<a href="#table8">Next</a>
<h4>Only Showing $S->siteName</h4>
<p>'js' is hex. 1, 2, 32(x20), 64(x40), 128(x80), 256(x100), 512(x200) and 4096(x1000) are done by 'webstats.js'.<br>
4, 8 and 16(x10) via an &lt;img&gt; tag in the header<br>
16384 (x4000) var an attempt to read 'csstest.css' from the 'head.i.php' file.<br>
1=start, 2=load, 4=script, 8=normal, 16(x10)=noscript,<br>
32(x20)=beacon/pagehide, 64(x40)=beacon/unload, 128(x80)=beacon/beforeunload,<br>
256(x100)=tracker/beforeunload, 512(x200)=tracker/unload, 1024(x400)=tracker/pagehide,<br>
4096(x1000)=tracker/timer: hits once every 5 seconds via ajax.</br>
8192(x2000)=SiteClass (PHP) determined this is a robot via analysis of the 'user agent' or scan of 'bots'.<br>
16384(x4000)=tracker/csstest<br>
The 'starttime' is done by SiteClass (PHP) when the file is loaded.<br>
Rows with 'js' zero (0) are <b>curl</b> or something like <b>curl</b> (wget, lynx, etc) and are probaly really <b>ROBOTS</b>.</p>

$tracker
<h2 id="table8">From table <i>bots</i> for Today</h2>
<a href="#table9">Next</a>
<h4>Showing ALL <i>bots</i> for today</h4>
<p>The 'bots' field is hex.<br>
The 'count' field is the total count since 'created'.<br>
From 'rotots.txt': Initial Insert=1, Update= OR 2.<br>
From 'SiteClass' scan: Initial Insert=4, Update= OR 8.<br>
From 'Sitemap.xml': Initial Insert=16(x10), Update= OR 32(x20).<br>
From 'tracker' cron: Inital Insert=64(x40), Update= OR 128(x80).<br>
From CRON indicates a Zero in the 'tracker' table: 258(x100).<br>
So if you have a 1 you can't have a 2 and visa versa.</p>
$bots
<h2 id="table9">From table <i>bots2</i> for Today</h2>
$botsnext
<h4>Showing ALL <i>bots2</i> for today</h4>
<p>'which' is 0 for zero in tracker, 1 for 'robots.txt', 2 for 'SiteClass', 4 for 'Sitemap.xml'.<br>
The 'count' field is the number of hits today.</p>
$bots2
$mtbl
$geotbl
<div id="analysis-info">
<hr>
<h2>Analusis Information for $S->siteName</h2>
$analysis
</div>
<hr>
</main>
</div>
$footer
EOF;

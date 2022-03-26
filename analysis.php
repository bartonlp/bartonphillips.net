<?php
// This program is run from crontab via all-cron.sh in www/bartonlp/scripts.
// BLP 2021-03-24 -- removed links to yahoo pure stuff. Added webstats.css which has the pure
// stuff we need. Removed extranious divs also.
// NOTE: this file is not usually called directly by anything other than a cron. All of the
// info in webstats.php comes from https:bartonphillips.net/analysis/ where we have the
// $site-analysis.i.txt files that this program creates.
// BLP 2017-11-01 -- all-cron.sh runs update-analysis.sh
// BLP 2016-09-03 -- change ftp password to '7098653?' note without single quotes

// Ajax from CRON job /var/www/bartonlp/scrits/update-analysis.sh which is run via all-cron.sh

$_site = require_once(getenv("SITELOADNAME"));

if($thisSite = $_GET['siteupdate']) {
  //error_log("_site: " . print_r($_site, true));
  
  $S = new $_site->className($_site);
  getAnalysis($S, $thisSite);
  exit();
}

// Ajax from webstats.js

if($thisSite = $_GET['site']) {
  $analysis = file_get_contents("https://bartonphillips.net/analysis/$thisSite-analysis.i.txt");

  echo $analysis;
  exit();
}

// POST from this file when standalone.

if(isset($_POST['submit']) || !$S) {
  $S = new $_site->className($_site);

  $h->title = "Analysis";

  // BLP 2021-03-24 -- remove yahoo stuff added westats.css
  $h->link = <<<EOF
  <link rel="stylesheet" href="https://bartonphillips.net/css/webstats.css">
  <link rel="stylesheet" href="https://bartonphillips.net/css/newtblsort.css">
EOF;

  $h->extra = <<<EOF
  <script src="https://bartonphillips.net/tablesorter-master/dist/js/jquery.tablesorter.min.js"></script>
  <script>
jQuery(document).ready(function($) {
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

  $("#os1, #os2, #browser1, #browser2")
    .tablesorter({ headers: { 1: {sorter: 'strnum'}, 2: {sorter: false}, 3: {sorter: false}}, sortList: [[1,1]]})
    .addClass('tablesorter');
});
  </script>
EOF;

  $h->css = <<<EOF
  <style>
body {
  margin: 1rem;
}
button {
  font-size: 1rem;
  border-radius: .5rem;
}
.scrolling {
  overflow-x: auto;
}
  </style>
EOF;

$site = $_POST['site'] ?? 'ALL';
  
  $h->banner = "<h1 id='analysis-info'>Analysis Information for $site</h1>";

  [$top, $footer] = $S->getPageTopBottom($h);

  $analysis = file_get_contents("https://bartonphillips.net/analysis/$site-analysis.i.txt");

  echo <<<EOF
$top
<div id="content">
$analysis
</div>
$footer
EOF;
  exit();
}

return getAnalysis($S, $S->siteName);

// make table 2 test

function maketable2($sql, $S) {
  $total = [];
  $counts = [];
  
  $n = $S->query($sql);
  $r = $S->getResult();
  
  while([$agent, $count, $ip] = $S->fetchrow($r, 'num')) {
    // Do the same check as in SiteClass
    $agent = $S->escape($agent);
    
    if($S->query("select ip from $S->masterdb.bots where ip='$ip' and agent='$agent'") !== 0) {
      $total['os'][0] += $count;
      $total['os'][1] += $count;
      $total['browser'][0] += $count;
      $total['browser'][1] += $count;
      $counts['os']['ROBOT'] += $count;
      $counts['browser']['ROBOT'] += $count;
      continue;
    }

    $pat1 = "~blackberry|windows|android|ipad|iphone|darwin|macintosh|x11|linux|bsd|cros|msie~i";

    if(preg_match_all($pat1, $agent, $m)) {
      $mm = array_map('strtolower', $m[0]);

      switch($mm[0]) {
        case 'blackberry':
          $val = 'BlackBerry';
          break;
        case 'darwin':
          $val = 'Darwin';
          break;
        case 'android':
          if($mm[1] == 'linux') {
            $val = 'AndroidPhone';
          } else {
            $val = 'Android';
          }
          break;
        case 'windows':
        case 'msie':
          $val = 'Windows';
          break;
        case 'ipad':
          $val = 'iPad';
          break;
        case 'iphone':
          $val = 'iPhone';
          break;
        case 'macintosh':
          $val = 'Macintosh';
          break;
        case 'cros':
          $val = 'CrOS';
          break;
        case 'x11':
        case 'linux':
        case 'bsd':
          if($mm[1] == 'android') {
            $val = 'AndroidPhone';
          } else {
            $val = 'Unix/Linux/BSD';
          }
          break;
        default:
          $val = 'Other';
          break;
      }
      $counts['os'][$val] += $count;
    } else {
      $counts['os']['Other'] += $count;
    }

    $total['os'][0] += $count;

    $pat2 = "~firefox|chrome|safari|trident|msie| edge/|opera|konqueror~i";

    if(preg_match_all($pat2, $agent, $m)) {
      $mm = array_map('strtolower', $m[0]);

      switch($mm[0]) {
        case 'opera':
          $name = 'Opera';
          break;
        case ' edge/':
          $name = 'MS-Edge';
          break;
        case 'trident':
        case 'msie':
          $name = 'MsIe';
          break;
        case 'chrome':
          $name = 'Chrome';
          break;
        case 'safari':
          $name = 'Safari';
          break;
        case 'firefox':
          $name = 'Firefox';
          break;
        case 'konqueror':
          $name = 'Konqueror';
          break;
        default:
          $name = 'Other';
          break;
      }
      $counts['browser'][$name] += $count;
    } else {
      $counts['browser'][$name] += $count;
    }
    $total['browser'][0] += $count;
  }

  return [$total, $counts, $n];
}

// Main function to get analysis

function getAnalysis($S, $site='ALL') {
  $S->query("select myip from $S->masterdb.myip");

  $ips = '';
  
  while([$myip] = $S->fetchrow('num')) {
    $ips .= "'$myip',"; // the ips must be surrounded by single quotes (')
  }
  $ips = rtrim($ips, ',');

  $where1 = '';

  if($site && $site != 'ALL') {
    $where1 = " and site='$site'";
  }

  // get startDate. Limit 1 will get the OLDEST date
  
  $S->query("select created from $S->masterdb.logagent ".
            "where ip not in ($ips)$where1 order by created limit 1");

  $startDate = $S->fetchrow('num')[0];

  //echo "startDate: $startDate<br>";
  
  // Now select agent and count from logagent where it is not Me and the site if not ALL
  // This gets all of the records since the last time the table was truncated. Now it is truncated
  // in cleanuptables.php which is run from cron. See crontab -l and
  // /var/www/bartonlp/scripts/cleanuptables.php for details.
  
  $sql = "select agent, count, ip from $S->masterdb.logagent where ip not in($ips)$where1";
  
  [$totals, $counts, $n[0]] = maketable2($sql, $S);

  vardump("totals", $totals);
  vardump("counts", $counts);
  
  // Now we get only 60 days worth of data.
  
  $days = 60;

  $S->query("select created from $S->masterdb.logagent ".
            "where created >= current_date() - interval $days day ".
            "and ip not in ($ips)$where1 order by created limit 1");
  
  $sinceDate = $S->fetchrow('num')[0];

  $sql = "select agent, count, ip from $S->masterdb.logagent ".
         "where created >= current_date() - interval $days day and ip not in ($ips)$where1";

  [$totals2, $counts2, $n[1]] = maketable2($sql, $S);
  vardump("totals", $totals2);
  vardump("counts", $counts2);
  vardump("n", $n);
  
  
  $os = [];
  $browser = [];

  // Make the tables.
  
  for($i=1; $i<3; ++$i) {
    foreach(['os','browser'] as $v) {
      $V = ucwords($v);
      ${$v}[$i-1] = <<<EOF
<table id='$v$i' class='pure-table pure-table-bordered pure-table-striped'>
<thead>
<tr><th>$V</th><th>Count</th><th>%</th><th>% less Bots</th></tr>  
</thead>  
<tbody>  
EOF;
    }
  }

  foreach($counts as $k=>$v) {
    foreach($v as $kk=>$vv) {
      $percent = number_format($vv/$totals[$k][0]*100, 2);
      $percent2 = number_format($vv/($totals[$k][0] - $totals[$k][1])*100, 2);
      $vv = number_format($vv, 0);
      if($kk == "ROBOT") {
        $percent2 = '';
      }
      ${$k}[0] .= "<tr><td>{$kk}</td><td>{$vv}</td><td>$percent</td><td>$percent2</td></tr>";
    }
  }

  foreach($counts2 as $k=>$v) {
    foreach($v as $kk=>$vv) {
      $percent = number_format($vv/$totals2[$k][0]*100, 2);
      $percent2 = number_format($vv/($totals2[$k][0] - $totals2[$k][1])*100, 2);
      $vv = number_format($vv, 0);
      if($kk == "ROBOT") {
        $percent2 = '';
      }
      ${$k}[1] .= "<tr><td>{$kk}</td><td>{$vv}</td><td>$percent</td><td>$percent2</td></tr>";
    }
  }

  $os[0] .= "</tbody></table>";
  $os[1] .= "</tbody></table>";
  $browser[0] .= "</tbody></table>";
  $browser[1] .= "</tbody></table>"; 

  $form = <<<EOF
<!-- If not Tysonweb give the options to view different sites -->
<div id="siteanalysis">
<form method="post" action="analysis.php">
  Get Site: 
  <select name='site'>
    <option>Allnatural</option>
    <option>Bartonphillips</option>
    <option>BartonlpOrg</option>
    <option>Tysonweb</option>
    <option>Newbernzig</option>
    <option>BartonphillipsOrg</option>
    <option>Rpi</option>
    <option>ALL</option>
  </select>

  <button id="mysite" type="submit">Submit</button>
</form>
</div>
EOF;
  
  $creationDate = date("Y-m-d H:i:s T");

  // Make this function into a string so we can use it in the echo within {}
  $number_format = 'number_format';

  // BLP 2021-03-24 -- removed extranious divs where pure stuff was.
  
  $analysis = <<<EOF
<h2>Analysis Information for $S->siteName</h2>
<p class="h-update">Last updated $creationDate.</p>
$form
<p>These tables show the number and percentage of Operating Systems and Browsers.<br>
The Totals show the number of Records and Counts for the entire table and the last N days.<br>
The OS and Browser totals should be the same. <br>
The two sets of tables give you an idea
of how the market is trending.</p>
<p>These table are created from the 'logagent' table.</p>
<div class="scrolling">
<table id="CompareTbl">
<thead>
  <tr>
    <th>
      Total Records: {$number_format($n[0])}<br>
      From: $startDate<br>
      Total Count: {$number_format($totals['os'][0])}
    </th>
    <th>
      Total Records: {$number_format($n[1])}<br>
      First Record: $sinceDate<br>
      Total Count: {$number_format($totals2['browser'][0])}
    </th>
  </tr>
</thead>
<tbody>
  <!-- OS rows -->
  <tr class="HeaderRow"><th>OS All</th><th>OS Last $days Days</th></tr>
  <tr>
    <td class="AlignTop">
$os[0]
    </td>

    <td class="AlignTop">
$os[1]
    </td>
  </tr>
  <!-- Browser rows -->
  <tr class="HeaderRow"><th>Browser All</th><th>Browser Last $days Days</th></tr>
  <tr>
    <td class="AlignTop">
$browser[0]
    </td>

    <td class="AlignTop">
$browser[1]
    </td>
  </tr>
</tbody>
</table>
</div>
EOF;

  $analysis_dir = "/var/www/bartonphillipsnet/analysis/";

  // Look to see if this is BartonphillipsOrg or Rpi. These are on remote sites and we need to do
  // ftp to access the file on the server.
  
  if(array_intersect([$S->siteName], ['BartonphillipsOrg', 'Rpi'])[0] !== null) {
    // We will use ftp to access the server
    
    if(($ftp = ftp_connect("bartonphillips.net")) === false) {
      echo "ftp_connect('bartonphillips.net') Failed<br>";
      debug("analysis $S->siteName: ftp_connect('bartonphillips.net') Failed");
    }

    if(ftp_login($ftp, "barton", "7098653?") === false) {
      echo "ftp_login() Failed<br>";
      debug("analysis $S->siteName: ftp_login() Failed");
    }

    if(ftp_chdir($ftp, "www/bartonphillipsnet/analysis") === false) {
      error_log("ftp_chdir failed trying to make directory");
      if(ftp_mkdir($ftp, "www/bartonphillipsnet/analysis") === false) {
        echo "ftp_mkdir Failed</br>";
        debug("analysis $S->siteName: ftp_mkdir('www/bartonphillipsnet/analysis') Failed");
      }
      if(ftp_chdir($ftp, "www/bartonphillipsnet/analysis") === false) {
        debug("analysis $S->siteName: ftp_chdir() Failed");
      }
    }

    if(file_put_contents("/tmp/tempfile", $analysis) === false) {
      echo "file_put_contents('/tmp/tempfile', \$analysis) Failed<br>";
      debug("analysis $S->siteName: file_put_contents('/tmp/tempfile', \$analysis) Failed");      
    }

    if(file_exists("/tmp/tempfile") === false) {
      debug("Can't find /tmp/tempfile");
    }
    
    if(ftp_put($ftp, "$site-analysis.i.txt", "/tmp/tempfile") === false) {
      debug("analysis $S->siteName: ftp_put(\$ftp, '$site-analysis.i.txt', '/tmp/tempfile', ...) Failed");      
    }

    if(unlink("/tmp/tempfile") === false) {
      debug("analysis $S->siteName: unlink('/tmp/tempfile') Failed");
    }
  } else {
    if(file_exists($analysis_dir) === false) {
      if(mkdir($analysis_dir, 0770) === false) {
        debug("analysis $S->siteName: mkdir($analysis_dir, 0770) Failed");
      }
    }

    if(file_put_contents("/var/www/bartonphillipsnet/analysis/$site-analysis.i.txt", $analysis) === false) {
      $e = error_get_last();
      debug("analysis $S->siteName: file_put_content('/var/www/bartonphillipsnet/analysis/$site-analysis.i.txt') Failed err= ". print_r($e, true));
    }
  }
  
  return $analysis;
}

// Debug function. send message to error_log() and exit.

function debug($msg) {
  error_log("$msg");
  exit();
}

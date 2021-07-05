<?php
// This program is run from crontab via all-cron.sh in www/bartonlp/scripts.
// BLP 2021-03-24 -- removed links to yahoo pure stuff. Added webstats.css which has the pure
// stuff we need. Removed extranious divs also.
// NOTE: this file is not usually called directly by anything other than a cron. All of the
// info in webstats.php comes from https:bartonphillips.net/analysis/ where we have the
// $site-analysis.i.txt files that this program creates.
// BLP 2017-11-01 -- all-cron.sh runs update-analysis.sh
// BLP 2016-09-03 -- change ftp password to '7098653?' note without single quotes

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);

// Ajax from CRON job /var/www/bartonlp/scrits/update-analysis.sh which is run via all-cron.sh

if($thisSite = $_GET['siteupdate']) {
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

  list($top, $footer) = $S->getPageTopBottom($h);

  $site = empty($_POST['site']) ? 'ALL' : $_POST['site'];

  $analysis = file_get_contents("https://bartonphillips.net/analysis/$site-analysis.i.txt");

  echo <<<EOF
$top
$analysis
<hr>
$footer
EOF;
  exit();
}

return getAnalysis($S, $S->siteName);

// Helper function to make the tables

function maketable($sql, $S) {
  $total = array();
  $counts = array();

  $n = $S->query($sql);

  $pat1 = "~https?://|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|php/|urllib|".
          "gt::www|snoopy|mfc_tear_sample|http::lite|phpcrawl|uri::fetch|zend_http_client|".
          "http client|pecl::http|blackberry|windows|android|ipad|iphone|darwin|macintosh|".
          "x11|linux|bsd|cros|msie~i";

  while(list($agent, $count) = $S->fetchrow('num')) {
    if(preg_match_all($pat1, $agent, $m)) {
      $mm = array_map('strtolower', $m[0]);
      $val = '';
      
      if(array_intersect(array("http://","https://","python","java","wget","nutch","perl","libwww",
                               "lwp-trivial","curl","php/","urllib","gt::www","snoopy","mfc_tear_sample",
                               "http::lite","phpcrawl","uri::fetch","zend_http_client",
                               "http client","pecl::http"),
                         $mm))
      {
        $val = 'ROBOT';
        $total['os'][1] += $count;
      } elseif(array_intersect(array('blackberry'), $mm)) {
        $val = 'BlackBerry';
      } elseif(array_intersect(array('darwin'), $mm)) {
        $val = 'Darwin';
      } elseif(array_intersect(array('android'), $mm)) {
        $val = 'Android';
      } elseif(array_intersect(array('windows','msie'), $mm)) {
        $val = 'Windows';
      } elseif(array_intersect(array('ipad'), $mm)) {
        $val = 'iPad';
      } elseif(array_intersect(array('iphone'), $mm)) {
        $val = 'iPhone';
      } elseif(array_intersect(array('macintosh'), $mm)) {
        $val = 'Macintosh';
      } elseif(array_intersect(array('cros'), $mm)) {
        $val = 'CrOS';
      } elseif(array_intersect(['x11','linux','bsd'], $mm)) {
        $val = 'Unix/Linux/BSD';
      }
      $counts['os'][$val] += $count;
    } else {
      //echo "Other, $count: $agent<br>";
      $counts['os']['Other'] += $count;
    }
    $total['os'][0] += $count;

    // Now browsers

    $pat2 = "~https?://|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|php/|urllib|".
            "gt::www|snoopy|mfc_tear_sample|http::lite|phpcrawl|uri::fetch|zend_http_client|".
            "http client|pecl::http|".
            "firefox|chrome|safari|trident|msie| edge/|opera|konqueror~i";

    if(preg_match_all($pat2, $agent, $m)) {
      $mm = array_map('strtolower', $m[0]);

      if(array_intersect(array("http://","https://","python","java","wget","nutch","perl","libwww",
                               "lwp-trivial","curl","php/","urllib","gt::www","snoopy","mfc_tear_sample",
                               "http::lite","phpcrawl","uri::fetch","zend_http_client",
                               "http client","pecl::http"),
                         $mm))
      {
        $counts['browser']['ROBOT'] += $count;
        $total['browser'][0] += $count;
        $total['browser'][1] += $count;
        continue;
      }

      // NOTE the order of these tests. Check for 'opera' first then the "MsIe" variants then
      // 'chrome' and then the rest.
      
      if(array_intersect(['opera'], $mm)) {
        $name = 'Opera';
      } elseif(array_intersect([' edge/'], $mm)) {
        $name = 'MS-Edge';
      } elseif(array_intersect(['trident','msie'], $mm)) {
        $name = 'MsIe';
      } elseif(array_intersect(['chrome'], $mm)) {
        $name = 'Chrome';
      } elseif(array_intersect(['safari'], $mm)) {
        $name = 'Safari';
      } elseif(array_intersect(['firefox'], $mm)) {
        $name = 'Firefox';
      } elseif(array_intersect(['konqueror'], $mm)) {
        $name = 'Konqueror';
      } 
      $counts['browser'][$name] += $count;
    } else {
      $counts['browser']['Other'] += $count;
    }
    $total['browser'][0] += $count;
  }

  return array($total, $counts, $n);
}

// Main function to get analysis

function getAnalysis($S, $site='ALL') {
  $rows = [];
  $cnt = 0;
  $cnt2 = 0;

  $S->query("select myip from $S->masterdb.myip");

  $ips = '';
  
  while(list($myip) = $S->fetchrow('num')) {
    $ips .= "'$myip',"; // the ips must be surrounded by '..'
  }
  $ips = rtrim($ips, ',');

  $where1 = $for = '';

  if($site && $site != 'ALL') {
    $where1 = " and site='$site'";
    $for = " for $site";
  }

  // get startDate. Limit 1 will get the OLDEST date
  
  $S->query("select created from $S->masterdb.logagent ".
            "where ip not in ($ips)$where1 order by created limit 1");

  list($startDate) = $S->fetchrow('num');

  // Now select agent and count from logagent where it is not Me and the site if not ALL
  // This gets all of the records since the last time the table was truncated. Now it is truncated
  // in cleanuptables.php which is run from cron. See crontab -l and
  // /var/www/bartonlp/scripts/celanuptables.php for details.
  
  $sql = "select agent, count from $S->masterdb.logagent where ip not in($ips)$where1";
  
  list($totals, $counts, $n[0]) = maketable($sql, $S);

  // Now we get only 60 days worth of data.
  
  $days = 60;

  $S->query("select created from $S->masterdb.logagent ".
            "where created >= current_date() - interval $days day ".
            "and ip not in ($ips)$where1 order by created limit 1");
  
  list($sinceDate) = $S->fetchrow('num');

  $sql = "select agent, count from $S->masterdb.logagent ".
         "where created >= current_date() - interval $days day and ip not in ($ips)$where1";

  list($totals2, $counts2, $n[1]) = maketable($sql, $S);
  
  $os = [];
  $browser = [];

  // Make the tables.
  
  for($i=1; $i<3; ++$i) {
    foreach(array('os','browser') as $v) {
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

  if($site != 'Tysonweb') {
    $form = <<<EOF
<!-- If not Tysonweb give the options to view different sites -->
<div id="siteanalysis">
  <form method="post" action="analysis.php">
    <p>Showing $site</p>
    Get Site: 
    <select name='site'>
      <option>Allnatural</option>
      <option>Bartonphillips</option>
      <option>BartonOrg</option>
      <option>Tysonweb</option>
      <option>ALL</option>
    </select>

    <button id="mysite" type="submit">Submit</button>
  </form>
</div>
EOF;
  }
  
  $creationDate = date("Y-m-d H:i:s T");

  // Make this function into a string so we can use it in the echo within {}
  $number_format = 'number_format';

  // BLP 2021-03-24 -- removed extranious divs where pure stuff was.
  
  $analysis = <<<EOF
<h2 id="analysis-info">Analysis Information$for</h2>
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

  //error_log("analysis.php: UPDATE analisys.i.txt for $site");
  // Update the analysis.i.txt file
  if(file_put_contents("/var/www/bartonphillipsnet/analysis/$site-analysis.i.txt", $analysis) === false) {
    error_log("analysis: file_put_contents FAILED on $site-analysis.i.txt");
  }
  return $analysis;
}

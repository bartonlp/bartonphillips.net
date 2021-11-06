<?php
// BLP 2014-04-29 -- Do various git functions
$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);  

$sites = ['/vendor/bartonlp/site-class', 'allnaturalcleaningcompany', '/bartonlp', '/bartonphillips.com', '/bartonphillipsnet', '/newbernzig.com', '/tysonweb'];

if($site = $_POST['readme']) {
  error_log("readme: $site");
  $ret = file_get_contents("https://bartonphillips.com/showmarkdown.php?filename=../$site/README.md");

  $ret = "data:text/html;base64," . base64_encode($ret);
  echo $ret;
  exit();
}

if($cmd = $_POST['more']) {
  $site = $_POST['site'];
  
  chdir("/var/www/$site");
  exec("git $cmd", $out);
  $out = implode("\n", $out);
  $out = escapeltgt($out);
  echo <<<EOF
<pre>
$out
</pre>
EOF;
  
  exit();
}

if($cmd = $_POST['page']) {
  $ret = '';
  
  foreach($sites as $site) {
    chdir("/var/www/$site");
    exec("git $cmd", $out);
    $out = implode("\n", $out);
    $out = escapeltgt($out);
    $ret .= <<<EOF
<hr>
<pre><b>$site</b>
$out
</pre>
EOF;
  }

  echo $ret;
  exit();
}

$h->script =<<<EOF
<script>
jQuery(document).ready(function($) {
  $("#more").hide();
  $(".results").hide();
  
  $("#moreInfo").on('click', function() {
    $("#less > .results").hide();
    $("#more").show();
  });

  $(".btn").on("click", function() {
    let stat = $(this).attr("data-page");
    let site = $(this).attr("data-site");
    let result = $(this).parent().find(".results");
    result.css('overflow', 'auto');
    
    if(stat == "readme") {
      $.ajax({
        uri: "gitstatus.php",
        data: {readme: site},
        type: 'post',
        success: function(data) {
          console.log("readme: " + data);
          result.html("<iframe class='frame' src='" + data +
          "'></iframe>").css('overflow', 'hidden').show();
        },
        error: function(err) {
          console.log(err);
        }
      });
    } else {  
      $.ajax({
        url: "gitstatus.php",
        data: {more: stat, site: site},
        type: 'post',
        success: function(data) {
          result.html(data).show();
        },
        error: function(err) { console.log(err)}
      });
    }  
  });
  
  $("#status").on('click', function() {
    $("#less").show();
    $("#less > .results").html('').show();
    $("#more").hide();
    $.ajax({
      url: "gitstatus.php",
      data: {page: 'status'},
      type: 'post',
      success: function(data) {
        $('#less .results').html(data);
      },
      error: function(err) { console.log(err); }
    });
  });
});
</script>
EOF;

$h->css =<<<EOF
  <style>
#status,
#moreInfo button {
  border-radius: .5rem;
  font-size: 1rem;
  margin-bottom: .5rem;
}
.results {
  width: 100%;
  height: 500px;
  overflow: auto;
  border: 1px solid black;
  padding: 5px;
}
.frame {
  width: 100%;
  height: 500px;
}
.btn {
  border-radius: .5rem;
  background-color: pink;
}
  </style>
EOF;

$h->title = "GIT Status All";
$h->banner = "<h1>bartonlp.org</h1><h2>Show GIT Status All</h2>";

$S = new $_site->className($_site);
list($top, $footer) = $S->getPageTopBottom($h);

$data = '';

foreach($sites as $v) {
  $site = $v;
  $v = $prefix.$v;

  $data .= <<<EOF
<div>
<h2>For $site></h2>
<button class='btn' data-page="status" data-site='$v'>Status</button>
<button class='btn' data-page='log --abbrev-commit' data-site='$v'>Log</button>
<button class='btn' data-page='show' data-site='$v'>Show</button>
<button class='btn' data-page='diff -w' data-site='$v'>Diff</button>
<button class='btn' data-page='diff -w HEAD^' data-site='$v'>Diff -w HEAD^</button>
<button class='btn' data-page='readme' data-site='$v'>README.me</button>
<div class='results'></div>
</div>
EOF;
}

echo <<<EOF
$top
<div id="moreInfo">
<button>More Information</button>
</div>
<div id="less">
<button id='status'>Status</button>
<div class='results'></div>
</div>

<div id='more'>
$data
</div>

$footer
EOF;

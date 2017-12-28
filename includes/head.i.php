<?php
// BLP 2016-01-09 -- check to see if this may be a robot

return <<<EOF
<head>
  <title>{$arg['title']}</title>
  <!-- METAs -->
  <meta charset='utf-8'/>
  <meta name="copyright" content="$this->copyright">
  <meta name="Author" content="$this->author"/>
  <meta name="description" content="{$arg['desc']}"/>
  <meta name="keywords"
    content="cookieless domain"/>
  <meta name=viewport content="width=device-width, initial-scale=1">
  <link rel="canonical" href="http://www.bartonphillips.com">
  <!-- CSS -->
  <link rel="stylesheet" href="https://bartonphillips.net/css/blp.css">
  <!-- css is not css but a link to tracker via .htaccess RewriteRule. -->
  <link rel="stylesheet" href="/csstest.css?id=$this->LAST_ID" title="blp test">

  {$arg['link']}
  <!-- Custom Scripts -->
{$arg['extra']}
{$arg['script']}
{$arg['css']}
</head>
EOF;

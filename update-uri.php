<?php
// BLP 2021-08-19 -- This file is run from my computer at home via curl.
// It updates the 'myUri.json' file if the IP address at '// HOME' has changed.

$urifilename = "myUri.json";
$uri = gethostbyname("bartonphillips.dyndns.org");
$uri = "\"$uri\", // HOME";
// Just for testing
// $uri = '"123.456.789.123", // HOME';
echo "New URI: $uri\n";
$file = file_get_contents($urifilename);
echo "Org file: $file\n";
$newuri = preg_replace('~"\d+\.\d+\.\d+\.\d+", // HOME~', $uri, $file);
if($file != $newuri) {
  echo "New file: $newuri\n";
  $ex = file_put_contents($urifilename, $newuri);
  $ex = $ex !== false ? "OK\n" : "ERROR\n";
  echo "File changed\n$ex\n";
} else {
  echo "File not changed\n";
}

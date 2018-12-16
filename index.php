<?php
date_default_timezone_set('Europe/London');
ini_set('memory_limit', '512M');

$time = time();
 echo("<pre>");
 echo date("Y-m-d H:i:s");
   print_r($time);
    echo("</pre>");
session_start();
$refreshRate = 200;
echo "</pre>";
header("refresh:$refreshRate;url= index.php");
$timeBetweenEachPost =  600;
$enablePosting = 1;
$postOnlyApproved = true;
$timeToDelay = 129600;//36 hours

$jd = cal_to_jd(CAL_GREGORIAN, date("m"), date("d"), date("Y"));
$dayOfWeek = jddayofweek($jd, 1);
   echo($dayOfWeek);

  if ($dayOfWeek == "Monday" || $dayOfWeek == "Thursday" ||  $dayOfWeek == "Saturday") {
      $enablePosting = true;
  } else {
      $enablePosting = false;
  }
  if ($postOnlyApproved) {
      $enablePosting = 1;
      $timeToDelay = 0;
  }

if (!is_writable("lastSecretAndWhenPosted.txt")){
  exit("last secret and when posted is not writable so exiting. this is too crucial to allow.");
}else{
  echo "it is writable";
}


require_once __DIR__ . '/vendor/autoload.php';
require "googleapi.php";




//in case there is a problem with creds:
$refreshToken = "1/-notForYourEyes";
echo "end of script";

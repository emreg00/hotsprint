<?php

include('functions.php');

$a = -0.110;
$b = scaleScore($a);
echo $b."<br/>";
$arr = unScaleScore($b);
echo $arr[0]." ".$arr[1]."<br/>";

echo "-----<br>";

$a = -0.400;
$b = scaleScore($a);
echo $b."<br/>";
$arr = unScaleScore($b);
echo $arr[0]." ".$arr[1]."<br/>";

echo "-----<br>";

$a = -0.567;
$b = scaleScore($a);
echo $b."<br/>";
$arr = unScaleScore($b);
echo $arr[0]." ".$arr[1]."<br/>";

echo "-----<br>";

$a = -0.780;
$b = scaleScore($a);
echo $b."<br/>";
$arr = unScaleScore($b);
echo $arr[0]." ".$arr[1]."<br/>";

?>

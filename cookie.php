<?php
/*
<!--------------------------------------------
- Hotsprint option cookie creation / checking 
- eg - 06.01.2007
---------------------------------------------!--> 
*/
	if(isset($_GET["submitButton0"])) {
		$type = $_GET["optionTypeSelector"];
		$cutoffValue = $_GET["cutoffValue"];
		#setcookie("cutoff", "", time()-3600);
		setcookie("optionType", $type, time()+3600000);
		setcookie("cutoff", $cutoffValue, time()+3600000);
	} else if(isset($_GET["submitButton2"])) {
		$type = $_GET["optionTypeSelector2"];
		#setcookie("cutoff", "", time()-3600);
		switch($optionType) {
			case 1: $cutoffValue = 6.2;
					break;
			case 2: $cutoffValue = 6;
					break;
			case 3: $cutoffValue = 6;
					break;
		}
		setcookie("optionType", $type, time()+3600000);
		setcookie("cutoff", $cutoffValue, time()+3600000);
	} else if(isset($_COOKIE["optionType"])) { 
		$type = $_COOKIE["optionType"];
		$cutoff = $_COOKIE["cutoff"];
	} else {
		$type = "1";
		$cutoff = "6.2";
	}
?> 
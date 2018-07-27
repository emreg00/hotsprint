<?php 
	if(isset($_GET["submitButton0"])) {
		$type = $_GET["optionTypeSelector"];
		$cutoffValue = $_GET["cutoffValue"];
		#setcookie("optionType", "", time()-3600);
		#setcookie("cutoff", "", time()-3600);
		setcookie("optionType", $type, time()+3600000);
		setcookie("cutoff", $cutoffValue, time()+3600000);
		#echo "1 type:$type<br>";
	} else if(isset($_GET["submitButton2"])) {
		$type = $_GET["optionTypeSelector2"];
		switch($type) {
			case 1: $cutoffValue = 6;
					break;
			case 2: $cutoffValue = 6.2;
					break;
			case 3: $cutoffValue = 6.2;
					break;
		}
		setcookie("optionType", $type, time()+3600000);
		#setcookie("cutoff", $cutoffValue, time()+3600000);
		#echo "2 type:$type<br>";
	} else if(isset($_COOKIE["optionType"])) { 
		$type = $_COOKIE["optionType"];
		$cutoffValue = $_COOKIE["cutoff"];
		#echo "3 type:$type<br>";
	} else {
		$type = "3";
		$cutoffValue = "6.2";
		#echo "4<br>";
	}
	#echo "<br>def:$type cut:$cutoffValue<br>"
?>
<!--------------------------------------------
- Hotsprint Server Query Page
- Page where results for the queries is displayed 
- eg - 13.09.2006
----------------------------------------------->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
 	<title> HotSprint - <? echo (!isset($_GET['queryId']) ? "Result Page" : "Result Page for ".$_GET['queryId']); ?> </title>
	<?php include('header.php'); ?>
</head>

<body id="mainBody">
<?php include('top.php'); ?>
<?php include('menu_top.php'); ?>
	<div id="contentDiv" class="spacedText">
<?php
	$db = connectToHotsprintDatabase();
	// Fetch associated interfaces of a protein
	if (isset($_GET["pdbId"])) { //(isset($_GET["submitButton0"])) { 
			$pdbId = $_GET["pdbId"];
			$displayOnlyInterfaceResidues = $_GET['displayOnlyInterfaceResidues'];
			fetchAndDisplayInterfaceInformationFromDatabase($pdbId, $displayOnlyInterfaceResidues, $type, $cutoffValue);
	// Fetch chains of a protein
	}else if (isset($_GET["queryId"])) { 
			$queryId = $_GET["queryId"];
			switch(strlen($queryId)) 
			{
			case 6: 
				{
				 	$displayOnlyInterfaceResidues = $_GET['displayOnlyInterfaceResidues'];
				 	displayInterfaceInformation($queryId, $displayOnlyInterfaceResidues, $type, $cutoffValue);
					break;
				}
			case 5: 
				{
					displayChain($queryId);
					break;
				}
			case 4: 
				{
					fetchAndDisplayChainsOfProtein($queryId);
					break;
				}
			}
	} else if(isset($_GET["submitButton2"])){ 
		//form2 submit code here
		//$structureType = $_GET["structureType"];
		$nHotspotLower = $_GET["nHotspotLower"];
		$nHotspotUpper = $_GET["nHotspotUpper"];
		$nConservedLower = $_GET["nConservedLower"];
		$nConservedUpper = $_GET["nConservedUpper"];
		$avgConservationScoreLower = $_GET["avgConservationScoreLower"];
		$avgConservationScoreUpper = $_GET["avgConservationScoreUpper"];
		$propensityLower = $_GET["propensityLower"];
		$propensityUpper = $_GET["propensityUpper"];
		$asaLower = $_GET["asaLower"];
		$asaUpper = $_GET["asaUpper"];
		$residueName = $_GET["residueName"];
		# echo $residueName." <br/> ";
		fetchAndDisplayInterfacesSatisfyingSpecifiedCriteria($nHotspotLower, $nHotspotUpper, $nConservedLower, $nConservedUpper, $avgConservationScoreLower, $avgConservationScoreUpper, $residueName, $propensityLower, $propensityUpper, $asaLower, $asaUpper, $type);	
	}
	closeDatabaseConnection($db);
	/*
	echo $_GET['submitButton0']." - <br/>";
	echo isset($_GET['submitButton0'])." - <br/>";
	echo $_GET['submitButton2']." - <br/>";
	echo $_GET['pdbId']." - <br/>";
	echo $_GET['queryId']." - <br/>";
	*/
	?>
	</div>
<?php include('menu_bottom.php'); ?>
<?php include('bottom.php'); ?>	
</body>
</html>

	

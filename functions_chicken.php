<?php
/***********************************************************
 * Hotsprint Server commonly used fuctions 
 *
 * ## performance can be improved by fetching residues only once for interface display - handled on Aug 7, 2007
 * 
 *  Average score calculation for interfaces and proteins over scaled scores
 * instead of calculating total unscaled score and then averaging
 *
 * eg - 14.08.2006
 ***********************************************************/

// Global Declarations
define("INTERVALSIZE", 0.2857);
define("THRESHOLDSCORE", -0.429);	// 7: -0.42855, 8: -0.71425
define("THRESHOLDASAComplex", 12);
define("THRESHOLDASADifference", 72);

define("SCORE", 1000);
define("ASA", 1001);
define("SCORE_ASA", 1002);
define("ALL", 1003);

// fetchin propensities from database instead of below 
//define("PROPENSITIES", array('ALA'=> 0.516, 'CYS'=> 0.916, 'GLU'=> 0.913, 'ASP'=> 0.890, 'GLY'=> 0.531, 'PHE'=> 1.300, 'ILE'=> 0.929, 'HIS'=> 1.436, 'LYS'=> 0.805, 'MET'=> 1.169, 'LEU'=> 0.882, 'ASN'=> 0.985, 'GLN'=> 1.054, 'PRO'=> 1.084, 'SER'=> 0.713, 'ARG'=> 2.072, 'THR'=> 0.918, 'TRP'=> 1.605, 'VAL'=> 0.724, 'TYR'=> 1.469));

////////////////////////////////////////////////////////////////////////////
// Database Connection Functions

function connectToDatabase($databaseName, $userName) {
	$db = mysql_connect('localhost', $userName, 'passthru') or die('Could not connect to database');
    mysql_select_db($databaseName) or die('Could not change database');
	return $db;
}

function closeDatabaseConnection($db) {
        mysql_close($db);
}

function connectToHotsprintDatabase() {
	return connectToDatabase('hotsprint', 'usr_hotsprint');
}

/* function executeQueryOnDatabase($db, $queryString) {
	//$result = mysql_query($queryString);
        return mysql_query($queryString);
} */

////////////////////////////////////////////////////////////////////////////
// Conservation Score Normalization Functions

function scaleScore($score) {
	# $intervalSize = 2.0 / 7; # MACRO defined above
	if($score >= 1.0) {
		$scoreScaled = 1; 
	} else if($score <= -1.0) {
		$scoreScaled = 9;
	} else if($score >= -(INTERVALSIZE / 2) && $score <= (INTERVALSIZE / 2)) {
		$scoreScaled = 5;
	} else if($score > (INTERVALSIZE / 2)) {
		$scoreScaled = (int) (5 - (($score - (INTERVALSIZE / 2)) / INTERVALSIZE));
	} else if($score < -(INTERVALSIZE / 2)) {
		$scoreScaled = ceil(5 - (($score + (INTERVALSIZE / 2)) / INTERVALSIZE));
	} else {
		$scoreScaled = 0;
	}
	return $scoreScaled;
}

function unScaleScore($scoreScaled) {
	# $intervalSize = 2.0 / 7; # MACRO defined above
	switch ($scoreScaled) {
		case 9: 
			$score = array(-1000.0, -1.0);
			break;
		case 1: 
			$score = array(1.0, 1000.0);
			break;
		case 5: 
			$score = array(-(INTERVALSIZE / 2), (INTERVALSIZE / 2));
			break;
		default:
			if($scoreScaled < 5) {
				$score = array((INTERVALSIZE / 2) + (4 - $scoreScaled) * INTERVALSIZE, (INTERVALSIZE / 2) + (5 - $scoreScaled) * INTERVALSIZE);
			} else	if($scoreScaled > 5) {
				$score = array(-(INTERVALSIZE / 2) - ($scoreScaled - 5) * INTERVALSIZE, -(INTERVALSIZE / 2) - ($scoreScaled - 6) * INTERVALSIZE);
			} 
			break;
	} 
	return $score;
}

function testScaleScore() {
	echo '-1.2='.scaleScore(-1.2); echo '<br/>';
	echo '-0.8='.scaleScore(-0.8); echo '<br/>';
	echo '-0.4='.scaleScore(-0.4); echo '<br/>';
	echo '-0.1='.scaleScore(-0.1); echo '<br/>';
	echo '0.1='.scaleScore(0.1); echo '<br/>';
	echo '0.4='.scaleScore(0.4); echo '<br/>';
	echo '0.8='.scaleScore(0.8); echo '<br/>';
	echo '1.2='.scaleScore(1.2); echo '<br/>';
}


////////////////////////////////////////////////////////////////////////////
// Interface Fetching & Visualization Functions

// Display Interfaces associated with a protein  
function fetchAndDisplayInterfaceInformationFromDatabase($pdbId, $displayOnlyInterfaceResidues, $definitionType, $cutoff) {
	$pdbId = strtolower($pdbId);
	$queryString = "SELECT interfaceID from interfaces WHERE interfaceID LIKE '".$pdbId."%'";
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$num_results = mysql_num_rows($result);
	// if there is only one interface associated, directly display it
	if ($num_results == 0) {
		echo '<div id="resultDiv" class="warning">';
		echo "No interfaces associated with <b> $pdbId </b> was found!";
		echo '</div>';
	}
	else if ($num_results == 1) {
		$row = mysql_fetch_row($result);
		mysql_free_result($result);
		displayInterfaceInformation($row[0], $displayOnlyInterfaceResidues, $definitionType, $cutoff);
	} else {
		echo "Interfaces associated with ".$pdbId.":<br/>";
		echo '<i>(Select an interface to display its residues)</i><br/><br/>';
		while($row = mysql_fetch_row($result)) {
			// Provide links to display interface information page 
			echo '<a href="result.php?queryId='.$row[0].'&displayOnlyInterfaceResidues='.$displayOnlyInterfaceResidues.'" title="click to see residues of this interface">'.$row[0]."</a><br/><br/>";
		}
		mysql_free_result($result);
	}
}


function displayInterfaceInformation($queryId, $option, $definitionType, $cutoff) {
	echo "<h2> Interface $queryId: </h2>";
	echo '<div id="resultDiv">';
	$pdbId = substr($queryId, 0, 4);
	$queryString = "SELECT * FROM interfaces.proteins WHERE PDB_ID = '$pdbId'";
	$result = mysql_query($queryString) or die('Problem in SQL query');
	$row = mysql_fetch_array($result);
	$title = $row['title'];
	
	$leftPartnerChainLetter = $queryId[4];
	$rightPartnerChainLetter = $queryId[5];
	$resultOverall = fetchInterfaceOverallInformation($pdbId, $leftPartnerChainLetter, $rightPartnerChainLetter, $definitionType, $cutoff);
	$status = $resultOverall[0];
	$nInterface = $resultOverall[1];
	$nHotspot = $resultOverall[2];
	$nConserved = $resultOverall[3];
	$nAvgScore = $resultOverall[4];
	#$nHotspot = findNumberOfHotspotsInInterface($pdbId, $leftPartnerChainLetter, $rightPartnerChainLetter, $definitionType, $cutoff);
	#$nConserved = findNumberOfConservedResiduesInInterface($pdbId, $leftPartnerChainLetter, $rightPartnerChainLetter);
	#$nAvgScore = findAverageConservationScoreOfInterface($pdbId, $leftPartnerChainLetter, $rightPartnerChainLetter);
	$nASA = findBuriedASAOfInterface($pdbId, $leftPartnerChainLetter, $rightPartnerChainLetter);
	$nASATotal = ($nASA[0] + $nASA[1]);
	$nInterfaceTotal = ($nInterface[0] + $nInterface[1]);
	$nHotspotTotal = ($nHotspot[0]+$nHotspot[1]); 
	$nAvgScoreTotal = round(($nAvgScore[0]+$nAvgScore[1])/2.0);
	$nConservedTotal = ($nConserved[0]+$nConserved[1]);
	
	$explanationString = array("", "");
	if($status[0] == ALL || $status[1] == ALL) {
		print "<div class='warning'> Information for this interface is not available! </div>";
		return;
	} else {
		for($i=0; $i<2; $i++) {
			if($nASA[$i] == '') {
				$nASA[$i] = 'N/A';
				$nASATotal = 'N/A';
			}
			if($nInterface[$i] == 0) {
				$nInterface[$i] = 'N/A';
				$nInterfaceTotal = 'N/A';
				$nHotspot[$i] = 'N/A';
				$nHotspotTotal = 'N/A';
				$nConserved[$i] = 'N/A';
				$nConservedTotal = 'N/A';
				$nAvgScore[$i] = 'N/A';
				$nAvgScoreTotal = 'N/A';
				$explanationString[$i] = "Information not available";
			} 
			#print "$nASA[$i] $nInterface[$i] $status[$i] <br/> ";
			switch($status[$i]) {
			case SCORE:
				{
					$nHotspot[$i] = 'N/A';
					$nHotspotTotal = 'N/A';
					$nConserved[$i] = 'N/A';
					$nConservedTotal = 'N/A';
					$nAvgScore[$i] = 'N/A';
					$nAvgScoreTotal = 'N/A';
					$explanationString[$i] = "Conservation information not available!";
				} break;
			case ASA:
				{
					if($definitionType == 3) {
						$nHotspot[$i] = 'N/A';
						$nHotspotTotal = 'N/A';
						$explanationString[$i] = "ASA information not available!";
					}
				} break;
			case SCORE_ASA:
				{
					$nHotspot[$i] = 'N/A';
					$nHotspotTotal = 'N/A';
					$nConserved[$i] = 'N/A';
					$nConservedTotal = 'N/A';
					$nAvgScore[$i] = 'N/A';
					$nAvgScoreTotal = 'N/A';
					$explanationString[$i] = "Conservation and ASA information not available!";
				} break;
			}
		}
	}
	echo '<li> <span class="resultTitle"> Overall Characteristics </span> </li> <br/>';

	echo "<table class='resultTable'>";
	echo "<tr> <th class='headerResultCell'> Protein $pdbId: </th> <td class='resultCell'> $title </td> </tr>";
	echo "</table> <br/> ";			

	echo "<table class='resultTable'>";
	echo "<tr> <th class='headerResultCell'> Interface <br/> $queryId </th> <th class='headerResultCell'> # of<br/> Residues </th> <th class='headerResultCell'> # of<br/> Hot Spots </th> <th class='headerResultCell'> # of<br/> Conserved<br/> Residues </th> <th class='headerResultCell'> Avg.<br/> Conservation<br/> Score </th> <th class='headerResultCell'> Buried<br/> ASA (A°²) </th> </tr>";
	#echo "<tr> <th class='headerResultCell'> </th> <th class='headerResultCell'> # of Interface Residues </th> <th class='headerResultCell'> # of Hot Spots </th> <th class='headerResultCell'> # of Conserved Residues </th> <th class='headerResultCell'> Avg. Conservation Score </th> </tr>";
	echo "<tr> <th class='headerResultCell'> Chain $leftPartnerChainLetter </th>";
	echo "<td class='resultCell'> ".$nInterface[0]." </td>";
	echo "<td class='resultCell'> ".$nHotspot[0]." </td>";
	echo "<td class='resultCell'> ".$nConserved[0]." </td>";
	echo "<td class='resultCell'> ".$nAvgScore[0]."</td>";
	echo "<td class='resultCell'> ".$nASA[0]." </td>";
	echo "</tr>";
	echo "<tr> <th class='headerResultCell'> Chain $rightPartnerChainLetter </th>";
	echo "<td class='resultCell'> ".$nInterface[1]." </td>";
	echo " <td class='resultCell'> ".$nHotspot[1]." </td>";
	echo "<td class='resultCell'> ".$nConserved[1]." </td>";
	echo "<td class='resultCell'> ".$nAvgScore[1]."</td>";
	echo "<td class='resultCell'> ".$nASA[1]." </td>";
	echo "</tr>";
	echo "<tr> <th class='headerResultCell'> Total </th>";
	echo "<td class='resultCell'> ".$nInterfaceTotal." </td>";
	echo "<td class='resultCell'> ".$nHotspotTotal." </td>";
	echo "<td class='resultCell'> ".$nConservedTotal." </td>";
	echo "<td class='resultCell'> ".$nAvgScoreTotal."</td>";
	echo "<td class='resultCell'> ".$nASATotal." </td>";
	echo "</tr>";
	echo "</table> <br/>";			
	#echo "</table> <br/> <br/> ";			

	#echo "<table class='resultTable'>";
	#echo "<tr> <th class='headerResultCell'> Buried ASA&nbsp;</th> <td class='resultCell'> $nASA </td> </tr>";
	#echo "</table> <br/> <br/> ";			

	echo "<table class='resultTable'>";
	echo "<tr> <th class='headerResultCell'> External Links: </th> <td class='resultCell'> <a href='/interface/new_interface.py?message=$queryId'> $queryId @ DAPPI (Interaction information) </a> </td> </tr>";
	echo "</table> <br/> <br/> ";			

	echo '<li> <span class="resultTitle"> Residues of the Interface </span> </li> <br/>';
	displayLegend();
	echo '<br/> ';
	echo '<table> <td class="spacedVerticalCell">';
	fetchAndDisplayInterfaceResiudesInformationFromDatabase($pdbId, $leftPartnerChainLetter, $queryId, $option, $definitionType, $cutoff, $explanationString[0]);
	//fetchAndDisplayInterfacePartnerChainResiudesInformationFromDatabase($pdbId, $leftPartnerChainLetter, $option);
	echo '</td> <td class="spacedVerticalCell">';
	fetchAndDisplayInterfaceResiudesInformationFromDatabase($pdbId, $rightPartnerChainLetter, $queryId, $option, $definitionType, $cutoff, $explanationString[1]);
	//fetchAndDisplayInterfacePartnerChainResiudesInformationFromDatabase($pdbId, $rightPartnerChainLetter, $option);
	//echo '</td> <td class="spacedVerticalCell">';
	//echo '</td>';
	echo '</table>';
	echo '<br/> <br/>';
	echo '<li> <span class="resultTitle"> Visualization of the Interface (Snapshots from 4 different view points - Click on images for interactive visualization) </span> </li> <br/>';
	generateAndDisplayRasmolVisualization($queryId, $definitionType, $cutoff);
	echo '</div>';
}


function displayLegend() {
?>
	<fieldset>
	<legend> <strong>Legend:</strong> </legend>
	<table class="borderless">
		<tr><td class="resultCell"> C: Contacting Interface Residue </td> <td class="resultCell"> N: Neighboring Interface Residue </td> </tr>
		<tr> 
			<td class="resultCell"> <span class="interface"> Interface Residue </span> <br/></td>
			<td class="resultCell"> <span class="conserved" > Conserved Residue </span> </td> 
			<td class="resultCell"> <span class="hotspot">Interface Hot Spot </span> </td>
			<!-- <td> <span class="conservedInterface">Conserved Interface Residue </span> </td> -->
		</tr>
	</table>
	</fieldset>
<?php
}

function displayLegendChain() {
?>
	<fieldset>
	<legend> <strong>Legend:</strong> </legend>
	<table class="borderless">
		<tr class="resultCell"> <span class="conserved"> Conserved Residue </span> </tr>
	</table>
	</fieldset>
<?php
}

function fetchInterfaceOverallInformation($pdbId, $leftChain, $rightChain, $definitionType, $cutoff) {
	$queryString = "SELECT C.chainLetter, C.residueScore, C.allABS, PC.allABS_Cmplx, p.residuePropensity from chain_residues C, partner_chain_residues PC, propensities p WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$pdbId.$leftChain.$rightChain."'";
	$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
	$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')";
	$queryString = $queryString." AND PC.residueType='C'";
	$queryString = $queryString." AND C.residueName=p.residueName ";
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	#print $queryString;
	// signal with -1 if interface data does not exist
	if (mysql_num_rows($result)==0) array(ALL, ALL);
	#echo mysql_num_rows($result);
	$nLeft = 0;
	$nRight = 0;
	$nLeftHotspot = 0;
	$nRightHotspot = 0;
	$nLeftConserved = 0;
	$nRightConserved = 0;
	$nLeftAvgScore = 0;
	$nRightAvgScore = 0;
	$sumL = 0.0;
	$iL = 0;
	$sumR = 0.0;
	$iR = 0;
	while($row = mysql_fetch_array($result)) {
		$score = scaleScore($row['residueScore']);
		#print ($row['residueScore']=='')."<br>";
		if($row['residueScore'] == '' && ($row['allABS'] == '' || $row['allABS_Cmplx'] == '')) {  # signal n/a of hot spot because of both score and asa
			$score = -1;
			if($row['chainLetter'] == $leftChain) {
				$statusLeft = SCORE_ASA;	
			}
			else {
				$statusRight = SCORE_ASA;
			}
		} else if($row['allABS'] == '' || $row['allABS_Cmplx'] == '') { 
			if($definitionType!=1) {
				if($row['chainLetter'] == $leftChain) 
					$statusLeft = ASA;	# signal n/a of hot spot because of  asa
				else
					$statusRight = ASA;
			}
		} else if($row['residueScore'] == '') { 
				$score = -1;
				if($row['chainLetter'] == $leftChain) {
					$statusLeft = SCORE;	# signal n/a of hot spot because of score
				}
				else {
					$statusRight = SCORE;
				}
			}
		if($row['chainLetter'] == $leftChain) {
			$nLeft++;
			if($row['residueScore']<=THRESHOLDSCORE) {
				$nLeftConserved++;
			}
			$sumL += scaleScore($row['residueScore']);
			$iL++;
		}
		else {
			$nRight++;
			if($row['residueScore']<=THRESHOLDSCORE) {
				$nRightConserved++;
			}
			$sumR += scaleScore($row['residueScore']);
			$iR++;
		}

		switch ($definitionType) {
		case 1: 
			if($score>=$cutoff) {
				if($row['chainLetter'] == $leftChain)
					$nLeftHotspot++;
				else
					$nRightHotspot++;
			}
			break;
		case 2: 
			if(($score*$row['residuePropensity'])>=$cutoff) {
				if($row['chainLetter'] == $leftChain)
					$nLeftHotspot++;
				else
					$nRightHotspot++;			
			}
			break;
		case 3: 
			if(($score*$row['residuePropensity'])>=$cutoff && (($row['allABS']-$row['allABS_Cmplx'])>=THRESHOLDASADifference || $row['allABS_Cmplx']<=THRESHOLDASAComplex )) {
				if($row['chainLetter'] == $leftChain)
					$nLeftHotspot++;
				else
					$nRightHotspot++;
			}
			break;
		default:
			print "<div class='error'> An unexpected error occured, please contact with web master!! </div>"; 
			break;
		}
	}  	

	if ($iL==0) 
		$nLeftAvgScore = '-';
	else
		$nLeftAvgScore = round($sumL/$iL);
	if ($iR==0) 
		$nRightAvgScore = '-';
	else
		$nRightAvgScore = round($sumR/$iR);

	mysql_free_result($result);
	//echo $queryString;
	return array(array($statusLeft, $statusRight), array($nLeft, $nRight), array($nLeftHotspot, $nRightHotspot), array($nLeftConserved, $nRightConserved), array($nLeftAvgScore, $nRightAvgScore));
} 

function findBuriedASAOfInterface($pdbId, $leftChain, $rightChain) {
	$queryString = "SELECT (interfaceASAchainA - interfaceASAcmplxA) AS asaBuriedLeft, (interfaceASAchainB - interfaceASAcmplxB) AS asaBuriedRight from interfaces WHERE interfaceID='".$pdbId.$leftChain.$rightChain."'";
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$row = mysql_fetch_row($result);
	mysql_free_result($result);
	return array($row[0], $row[1]);
}


function fetchAndDisplayInterfaceResiudesInformationFromDatabase($pdbId, $chainLetter, $interfaceId, $option, $definitionType, $cutoff, $explanationString) {
	switch ($definitionType) {
		case 1: 
			$queryString = "SELECT C.residuePosition, C.residueName, C.residueScore, C.allABS, PC.residueType, PC.allABS_Cmplx from chain_residues C, partner_chain_residues PC WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$interfaceId."'";
			$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
			$queryString = $queryString." AND C.chainLetter=BINARY'".$chainLetter."'";
			if($option == "true") {
				$queryString = $queryString." AND PC.residueType IN ('C', 'N')";
			}
			break;
		case 2: 
			$queryString = "SELECT C.residuePosition, C.residueName, C.residueScore, C.allABS, PC.residueType, PC.allABS_Cmplx, p.residuePropensity from chain_residues C, partner_chain_residues PC, propensities p  WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$interfaceId."'";
			$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
			$queryString = $queryString." AND C.chainLetter=BINARY'".$chainLetter."'";
			$queryString = $queryString." AND C.residueName=p.residueName ";
			if($option == "true") {
				$queryString = $queryString." AND PC.residueType IN ('C', 'N')";
			}
			break;
		case 3: 
			$queryString = "SELECT C.residuePosition, C.residueName, C.residueScore, C.allABS, PC.residueType, PC.allABS_Cmplx, p.residuePropensity from chain_residues C, partner_chain_residues PC, propensities p WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$interfaceId."'";
			$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
			$queryString = $queryString." AND C.chainLetter=BINARY'".$chainLetter."'";
			$queryString = $queryString." AND C.residueName=p.residueName ";
			if($option == "true") {
				$queryString = $queryString." AND PC.residueType IN ('C', 'N')";
			}
			break;
		default:
			print "<div class='error'> An unexpected error occured, please contact with web master!! </div>";
			break;
	}
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	echo "<h5>".$pdbId.$chainLetter.(($explanationString=="")?"":" ($explanationString)")."</h5>";
	echo "<table class='resultTable'>";
	echo "<tr> <th class='headerResultCell'> Position </th> <th class='headerResultCell'> Name </th> <th class='headerResultCell'> Cons. <br/> Score </th> <th class='headerResultCell'> ASA <br/> in <br/> Chain <br/> (A°²) </th> <th class='headerResultCell'> ASA <br/> in <br/> Complex <br/> (A°²) </th> <th class='headerResultCell'> Type </th> <th class='headerResultCell'> Hot Spot </th> </tr>";
	$num_results = mysql_num_rows($result);
	for ($i=0 ; $i<$num_results ; $i++)
	{
		$isHotspotStr = '';
		$row = mysql_fetch_array($result);
		if($row['residueScore'] == '') {
			$score = -1;
		} else {
			$score = scaleScore($row['residueScore']);
		}
		switch ($definitionType) {
		case 1: 
			if($row['residueType'] == 'C' && $score>=$cutoff) {
				echo '<tr class="hotspot">';
				$isHotspotStr = '*';
			} else if($score>=scaleScore(THRESHOLDSCORE)) {
				echo '<tr class="conserved">'; 
			} else if($row['residueType'] == 'N' || $row['residueType'] == 'C') {
				echo '<tr class="interface">';
			} else {
				echo '<tr class="resultCell">';
			}
			break;
		case 2: 
			if($row['residueType'] == 'C' && ($score*$row['residuePropensity'])>=$cutoff) {
				echo '<tr class="hotspot">';
				$isHotspotStr = '*';
			} else if($score>=scaleScore(THRESHOLDSCORE)) {
				echo '<tr class="conserved">';
			} else if($row['residueType'] == 'N' || $row['residueType'] == 'C') {
				echo '<tr class="interface">';
			} else {
				echo '<tr class="resultCell">';
			}
			break;
		case 3: 
			if($row['residueType'] == 'C'  && ($score*$row['residuePropensity'])>=$cutoff && ( ($row['allABS']-$row['allABS_Cmplx'])>=THRESHOLDASADifference || $row['allABS_Cmplx']<=THRESHOLDASAComplex ) ) {
				if($row['allABS'] != '') {
					echo '<tr class="hotspot">';
					$isHotspotStr = '*';
				} else if($score>=scaleScore(THRESHOLDSCORE)) {
							echo '<tr class="conserved">';
						} else if($row['residueType'] == 'N' || $row['residueType'] == 'C') {
									echo '<tr class="interface">';
								} else {
										echo '<tr class="resultCell">';
									}
			} else if($score>=scaleScore(THRESHOLDSCORE)) {
				echo '<tr class="conserved">';
			} else if($row['residueType'] == 'N' || $row['residueType'] == 'C') {
				echo '<tr class="interface">';
			} else {
				echo '<tr class="resultCell">';
			}
			break;
		default:
			if($row['residueType'] == 'C') {
				echo '<tr class="interface">';
			} else if($score>=scaleScore(THRESHOLDSCORE)) {
				echo '<tr class="conserved">';
			} else {
				echo '<tr class="resultCell">';
			} 
			break;
		}
		echo "<td>".$row['residuePosition']."</td>";
		echo "<td>".$row['residueName']."</td>";
		if($score == -1) {
			echo "<td> </td>"; 
		} else {
			echo "<td>".$score."</td>";
		}
		echo "<td>".$row['allABS']."</td>";
		echo "<td>".$row['allABS_Cmplx']."</td>";
		echo "<td>".$row['residueType']."</td>";
		echo "<td>".$isHotspotStr."</td>";
		echo "</tr>";
 
		/*
		echo "<td class='resultCell'>".$row['residuePosition']."</td>";
		echo "<td class='resultCell'>".$row['residueName']."</td>";
		if($score == -1) {
			echo "<td class='resultCell'> </td>"; 
		} else {
			echo "<td class='resultCell'>".$score."</td>";
		}
		echo "<td class='resultCell'>".$row['allABS']."</td>";
		echo "<td class='resultCell'>".$row['allABS_Cmplx']."</td>";
		echo "<td class='resultCell'>".$row['residueType']."</td>";
		echo "<td class='resultCell'>".$isHotspotStr."</td>";
		echo "</tr>";
		*/
	}
	echo "</table>";
	mysql_free_result($result);
}


// Generate and display graphics generated with rasmol for a given interface
function generateAndDisplayRasmolVisualization($queryId, $definitionType, $cutoff) {
	$pdbDir = "pdb/";
	$pdbUrl = "http://www.rcsb.org/pdb/files/";
	$tempDir = "tmp/";
	$pdbId = substr($queryId, 0, 4);
	$leftChain = substr($queryId, 4, 1);
	$rightChain = substr($queryId, 5, 1);
	# These are predefined rasmol colors and their corresponding HTML codes
   $colorsRasmol = array('blue'=>'#0000FF', 'green'=>'#00FF00', 'red'=>'#FF0000',
                         'yellow'=>'#FFFF00', 'magenta'=>'#FF00FF', 'cyan'=>'#00FFFF',
                         'orange'=>'#FFA500', 'redorange'=>'#FF4500', 'violet'=>'#EE82EE',
                         'greenblue'=>'#2E8B57', 'purple'=>'#A020F0');         
   $colorsKeys = array_keys($colorsRasmol);                                                                                                                                                                       
   # First dynamically create a rasmol script file
	@ $fpRasmol = fopen($tempDir.$queryId.".scr", "w");
	@ $fpJmol = fopen($tempDir.$queryId.".spt", "w");
		  if ($fpRasmol == NULL)
        {
                echo '<b>Error: </b> Could not open script file for writing.<br>';
                exit;
        }                                                                                                                                                  
        fwrite($fpRasmol, "#Rasmol script for the visualization of interface $queryId \n#Generated automatically by result.php\n");
        fwrite($fpRasmol, "zap \n");
        fwrite($fpJmol, "zap;\n");
        fwrite($fpRasmol, "load ".$pdbDir.$pdbId.".pdb\n");
        fwrite($fpJmol, "load ".$pdbUrl.$pdbId.".pdb;\n");
        fwrite($fpRasmol, "background white \n");
        fwrite($fpJmol, "background white;\n");
        fwrite($fpRasmol, "wireframe off \n");
        fwrite($fpJmol, "wireframe off;\n");
        fwrite($fpRasmol, "restrict none \n");
        fwrite($fpJmol, "restrict none;\n");
        fwrite($fpRasmol, "select *:$queryId[4] \n");
        fwrite($fpJmol, "select *:$queryId[4];\n");
        fwrite($fpRasmol, "select selected or *:$queryId[5] \n");
        fwrite($fpJmol, "select selected or *:$queryId[5];\n");
        fwrite($fpRasmol, "trace on ");
        fwrite($fpJmol, "trace on;\n");
        fwrite($fpRasmol, "select *:$queryId[4] \n");
        fwrite($fpJmol, "select *:$queryId[4];\n");
        /*
        if (is_numeric($queryId[4]))
                fwrite($fpRasmol, "color ".$colorsKeys[$queryId[4]%11]."\n");
        else
                fwrite($fpRasmol, "color ".$colorsKeys[(ord($queryId[4])-65)%11]."\n");
        */
        fwrite($fpRasmol, "color ".$colorsKeys[10]."\n");
        fwrite($fpJmol, "color ".$colorsKeys[10].";\n");
        fwrite($fpRasmol, "select *:$queryId[5]\n");
        fwrite($fpJmol, "select *:$queryId[5];\n");
        /* 
        if (is_numeric($queryId[5]))
                fwrite($fpRasmol, "color ".$colorsKeys[$queryId[5]%11]."\n");
        else
                fwrite($fpRasmol, "color ".$colorsKeys[(ord($queryId[5])-65)%11]."\n");
        */
        fwrite($fpRasmol, "color ".$colorsKeys[7]."\n");                                                                 
        fwrite($fpJmol, "color ".$colorsKeys[7].";\n");
        fwrite($fpRasmol, "select none \n");
        fwrite($fpJmol, "select none;\n");
        switch ($definitionType) {
		case 1: 
			$queryString = "SELECT C.chainLetter, C.residuePosition, C.residueName, C.residueScore, PC.residueType from chain_residues C, partner_chain_residues PC WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$queryId."'";
			$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition";
			$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')"; 
			$queryString = $queryString." AND PC.residueType='C'";
			break;
		case 2: 
			$queryString = "SELECT C.chainLetter, C.residuePosition, C.residueName, C.residueScore, C.allABS, PC.residueType, PC.allABS_Cmplx, p.residuePropensity from chain_residues C, partner_chain_residues PC, propensities p  WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$queryId."'";
			$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition";
			$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')"; 
			$queryString = $queryString." AND C.residueName=p.residueName ";
			$queryString = $queryString." AND PC.residueType='C'";
			break;
		case 3: 
			$queryString = "SELECT C.chainLetter, C.residuePosition, C.residueName, C.residueScore, C.allABS, PC.residueType, PC.allABS_Cmplx, p.residuePropensity from chain_residues C, partner_chain_residues PC, propensities p WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$queryId."'";
			$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition";
			$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')"; 
			$queryString = $queryString." AND C.residueName=p.residueName ";
			$queryString = $queryString." AND PC.residueType='C'";
			break;
		default:
			print "<div class='error'> An unexpected error occured, please contact with web master!! </div>";
			break;
		}
		$result = mysql_query($queryString) or die('Problem in Sql Query');
        $num_results = mysql_num_rows($result);
        $firstTime = true;
		for ($i=0 ; $i<$num_results ; $i++)
		{
			$row = mysql_fetch_array($result);
			$score = scaleScore($row['residueScore']);
			if($row['residueScore'] == '') $score = -1;
            $residueNo = $row['residuePosition'];
            $chain = $row['chainLetter'];
            if($firstTime==true && $chain != $leftChain) {
            	fwrite($fpRasmol, "color ".$colorsKeys[1]."\n");
            	fwrite($fpJmol, "color ".$colorsKeys[1].";\n");
            	fwrite($fpRasmol, "select none "."\n");
            	fwrite($fpJmol, "select none ".";\n");
            	$firstTime = false;
            }
			switch ($definitionType) {
			case 1: 
				if($score>=$cutoff) {
					fwrite ($fpRasmol, "select selected or $residueNo:$chain \n");
					fwrite ($fpJmol, "select selected or $residueNo:$chain;\n");
				}
				break;
			case 2: 
				if(($score*$row['residuePropensity'])>=$cutoff) {
					fwrite ($fpRasmol, "select selected or $residueNo:$chain \n");
					fwrite ($fpJmol, "select selected or $residueNo:$chain;\n");
				}
				break;
			case 3: 
				#echo (($score*$row['residuePropensity'])>=$cutoff)." ".$row['allABS']-$row['allABS_Cmplx']." ".$row['allABS_Cmplx'].THRESHOLDASAComplex." ".($row['allABS_Cmplx']<=THRESHOLDASAComplex)."<br>" ;
				if(($score*$row['residuePropensity'])>=$cutoff && ( ($row['allABS']-$row['allABS_Cmplx'])>=THRESHOLDASADifference || $row['allABS_Cmplx']<=THRESHOLDASAComplex ) ) {
					if($row['allABS_Cmplx'] != '') {
						fwrite ($fpRasmol, "select selected or $residueNo:$chain \n");
						fwrite ($fpJmol, "select selected or $residueNo:$chain;\n");
					}
				}
				break;
			default: 
				break;
			} 
		}
		fwrite($fpRasmol, "color ".$colorsKeys[3]."\n");
		fwrite($fpJmol, "color ".$colorsKeys[3].";\n");
		fwrite($fpRasmol, "select none \n");
		fwrite($fpJmol, "select none;\n");
		
        $queryString = "SELECT C.chainLetter, C.residuePosition from chain_residues C, partner_chain_residues PC WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$queryId."'";
		$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
		$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')";
		$queryString = $queryString." AND PC.residueType='C'";
        $result = mysql_query($queryString) or die('Problem in Sql Query:');
        $num_results = mysql_num_rows($result);
        for ($i=0 ; $i<$num_results ; $i++)
        {
                $row = mysql_fetch_array($result);
                $residueNo = $row['residuePosition'];
                $chain = $row['chainLetter'];
                fwrite ($fpRasmol, "select selected or $residueNo:$chain \n");
                fwrite ($fpJmol, "select selected or $residueNo:$chain;\n");
        }
        mysql_free_result($result);
        fwrite($fpRasmol, "wireframe off \n");
        fwrite($fpJmol, "wireframe off;\n");
        fwrite($fpRasmol, "spacefill on \n");
        fwrite($fpJmol, "spacefill on;\n");
        fwrite($fpRasmol, "define bothChains selected \n");
        fwrite($fpJmol, "define bothChains selected;\n");
        fwrite($fpRasmol, "select bothChains and *:$queryId[4] \n");
        fwrite($fpJmol, "select bothChains and *:$queryId[4];\n");
        fwrite($fpRasmol, "define chainOne selected \n");
        fwrite($fpJmol, "define chainOne selected;\n");
        fwrite($fpRasmol, "select bothChains and *:$queryId[5] \n");
        fwrite($fpJmol, "select bothChains and *:$queryId[5];\n");
        fwrite($fpRasmol, "define chainTwo selected \n");
        fwrite($fpJmol, "define chainTwo selected;\n");
                                                                                                                                                             
        for ($i=0 ; $i<4 ; $i++)
        {
                fwrite($fpRasmol, "write $queryId$i.jpg \n");
                fwrite($fpRasmol, "rotate x 90 \n");
        }
        fwrite($fpRasmol, "quit \n");
        fclose($fpRasmol);
        fclose($fpJmol);
                                                                                                                                                             
        # Let Rasmol execute the script
        chdir($tempDir);    #Temporarily change directory s.t. output of the script will be under the right dir	
        exec("./rasmol_8BIT -nodisplay < $queryId.scr");
        chdir("..");
        //unlink("$tempDir.$queryId.scr");
                                                                                                                                                             
        # Print legend
        /* 
        if (is_numeric($queryId[4]))
                $index = $queryId[4];
        else
                $index = (ord($queryId[4])-65)%11;
        */
        $index = 10;
        echo "<fieldset>";
        echo "<legend> <strong>Legend:</strong> </legend>";
        echo '<font color="'.$colorsRasmol["$colorsKeys[$index]"].'"><b>Buried surface residues on chain '.$queryId[4].' </b></font>';
        echo ' &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp';
        /*
        if (is_numeric($queryId[5]))
                $index = $queryId[5];
        else
                $index = (ord($queryId[5])-65)%11;
        */
        $index = 7;
        echo '<font color="'.$colorsRasmol["$colorsKeys[$index]"].'"><b>Buried surface residues on chain '.$queryId[5].' </b></font>';
        echo "<br/>";
        echo '<font color="'.$colorsRasmol["$colorsKeys[1]"].'"><b> Hot spot residues on interface chain '.$queryId[4].' </b></font>';
        echo '<font color="'.$colorsRasmol["$colorsKeys[3]"].'"><b> Hot spot residues on interface chain '.$queryId[5].' </b></font>';
        echo "</fieldset>";
        # echo '</td> </tr> <tr> ';                                                                                                                                                     
        # Display the dynamically generated gif images
        for ($i=0 ; $i<4 ; $i++) //for ($i=0 ; $i<4 ; $i++)
        {
                #echo '<img src="'.$tempDir.$queryId.$i.'.gif" width="25%" alt=" Image of Interface '.$queryId.'">';
        	#echo '<img src="'.$tempDir.$queryId.$i.'.gif"'.' width="25%" alt='.'" Image of Interface '.$queryId.'">';
                # echo '<br/>';
        }
	#echo "$tempDir$queryId";
	#echo '<img src="'.$tempDir.$queryId.'0.gif"'.' width="25%" alt='.'" Image of Interface '.$queryId.'">';
        #echo '<br/> aloo <br/> <img src="a.gif" alt=" Image of Interface">';
        #echo '<a href="jmol.php?queryId='.$queryId.'> ';
	echo '<br/> Click on images below for interactive Jmol visualization of the interface. ';
	echo '<table 
		title="Click for an interactive 3D model. (Opens in new window)"
		onclick="window.open(\'jmol.php?queryId='.$queryId.'\', \'3DModel\', \'width=510, height=580\');">'; 
        #echo '<table>';
	echo '<tr> <td class="footerCellCenter"> <img src="'.$tempDir.$queryId.'0.jpg" width="50%" alt=" Image of Interface '.$queryId.'"> </td>';
        echo '<td> <img src="'.$tempDir.$queryId.'1.jpg" width="50%" alt=" Image of Interface '.$queryId.'"> </td> </tr>';
	echo '<tr> <td class="footerCellCenter"> <img src="'.$tempDir.$queryId.'2.jpg" width="50%" alt=" Image of Interface '.$queryId.'"> </td>';
        echo '<td> <img src="'.$tempDir.$queryId.'3.jpg" width="50%" alt=" Image of Interface '.$queryId.'"> </td> </tr>';
        echo '</table>';
        #echo '</a>';
        echo '</span>';
}

function printContentsOfFile($fileName) {
		if (!file_exists($fileName)) {
			exit("File $fileName does not exist");
		}
		$file = fopen($fileName, "r")  or exit("Failed to open file: $fileName");
		
		while(!feof($file))
		{
			#echo fgets($file)."<br />";
			$line = fgets($file);
			#list($resPos, $resName, $resScore, $resType, $resConserved) = sscanf($line, "%d %s %f %c %c");
			echo "$line";
		}
		fclose($file);
		return;
}

////////////////////////////////////////////////////////////////////////////
// Protein (its chains) Fetching Functions

function fetchAndDisplayChainsOfProtein($queryId) {
	$pdbId = strtolower($queryId);
	$queryString = "SELECT chainLetter from chain_residues WHERE pdbId='".$pdbId."'";
	$queryString = $queryString." GROUP BY chainLetter";
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$num_results = mysql_num_rows($result);
	// if there is only one interface associated, directly display it
	if ($num_results == 0) {
		echo '<div id="resultDiv" class="warning">';
		echo "Information for <b> $pdbId </b> is not available!";
		echo '</div>';
	}
	else if ($num_results == 1) {
		$row = mysql_fetch_row($result);
		displayChain($pdbId);
		mysql_free_result($result);
	} else {
		echo "Chains of ".$pdbId.":<br/>";
		echo '<i>(Select an chain to display its residues)</i><br/><br/>';
		while($row = mysql_fetch_row($result)) {
			// Provide links to display interface information page 
			echo '<a href="result.php?queryId='.$queryId.$row[0].'" title="click to see residues of this chain">'.$queryId.$row[0]."</a><br/><br/>";
		}
		mysql_free_result($result);
	}
}

function displayChain($queryId) {
	$pdbId = strtolower(substr($queryId, 0, 4));
	$chainLetter = substr($queryId, 4, 1);
	$queryString = "SELECT residuePosition, residueName, residueScore, allABS from chain_residues WHERE pdbId='".$pdbId."'"; 
	$queryString = $queryString." AND chainLetter=BINARY'".$chainLetter."'";
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$num_results = mysql_num_rows($result);
	if ($num_results == 0) {
		mysql_free_result($result);	
		echo '<div id="resultDiv" class="warning">';
		echo "Information for <b> $queryId </b> is not available!";
		echo '</div>';
		return;
	}
	echo "<h2> Structure $queryId: </h2>";
	displayLegendChain();
	echo '<br/> ';
	echo '<div id="resultDivChain">';
	$row = mysql_fetch_array($result);
	$strExplanation = "";
	if($row['residueScore'] == '' && $row['allABS'] == '') $strExplanation = "(Conservation and ASA information is not available!)"; 
	else if($row['residueScore'] == '') $strExplanation = "(Conservation information is not available!)"; 
	else if($row['allABS'] == '') $strExplanation = "(ASA information is not available!)"; 
	echo "<h5>".$pdbId.$chainLetter." ".$strExplanation."</h5>";
	echo "<table class='resultTable'>";
	echo "<tr> <th class='headerResultCell'> Position </th> <th class='headerResultCell'> Name </th> <th class='headerResultCell'> Cons. <br/> Score </th> <th class='headerResultCell'> ASA in <br/> Chain </th> </tr>";
	for ($i=0 ; $i<$num_results ; $i++)
	{
		$score = scaleScore($row['residueScore']);
		if($row['residueScore'] == '') $score = -1; 
		if($score>=scaleScore(THRESHOLDSCORE)) {
			echo '<tr class="conserved">';
		} else {
			echo '<tr class="resultCell">';
		} 
		echo "<td>".$row['residuePosition']."</td>";
		echo "<td>".$row['residueName']."</td>";
		if($score == -1) {
			echo "<td> </td>";
		}
		else {
			echo "<td>".$score."</td>";
		}
		echo "<td>".$row['allABS']."</td>";
		echo "</tr>";
		$row = mysql_fetch_array($result);
	}
	echo "</table>";
	echo '</div>';
	mysql_free_result($result);
}

////////////////////////////////////////////////////////////////////////////
// Functions for Finding and Displaying Interfaces With Provided Criteria

function fetchAndDisplayInterfacesSatisfyingSpecifiedCriteria($nHotspotLower, $nHotspotUpper, $nConservedLower, $nConservedUpper, $avgConservationScoreLower, $avgConservationScoreUpper, $residueName, 
$propensityLower, $propensityUpper, $asaLower, $asaUpper, $definitionType) {
	if(!strcmp($nHotspotLower, "")) $nHotspotLower = 0;
	if(!strcmp($nHotspotUpper, "")) $nHotspotUpper = 1000000;
	if(!strcmp($nConservedLower, "")) $nConservedLower = 0;
	if(!strcmp($nConservedUpper, "")) $nConservedUpper = 1000000;
	if(!strcmp($avgConservationScoreLower, "")) $avgConservationScoreLower = 0;
	if(!strcmp($avgConservationScoreUpper, "")) $avgConservationScoreUpper = 10;
	if(!strcmp($propensityLower, "")) $propensityLower = 0; 
	if(!strcmp($propensityUpper, "")) $propensityUpper = 1000000; 
	if(!strcmp($asaLower, "")) $asaLower = 0;
	if(!strcmp($asaUpper, "")) $asaUpper = 1000000;
	switch ($definitionType) {
		case 1:
			$queryString = "SELECT interfaceId, (residueCountLeft+residueCountRight) AS residueCount, (conservedCountLeft+conservedCountRight) AS conservedCount, hotspotCount1 AS hotspotCount, (avgScoreNormalizedLeft+avgScoreNormalizedRight)/2 AS avgScoreNormalized, (buriedASALeft+buriedASARight) AS buriedASA, propensity".$residueName." AS propensity";
			$queryString = $queryString." FROM interfaces_cumulative_information WHERE hotspotCount1>=".$nHotspotLower." AND hotspotCount1<=".$nHotspotUpper;
			$queryString = $queryString." AND (conservedCountLeft+conservedCountRight)>=".$nConservedLower." AND (conservedCountLeft+conservedCountRight)<=".$nConservedUpper;
			$queryString = $queryString." AND (avgScoreNormalizedLeft+avgScoreNormalizedRight)/2>=".$avgConservationScoreLower." AND (avgScoreNormalizedLeft+avgScoreNormalizedRight)/2<=".$avgConservationScoreUpper;
			$queryString = $queryString." AND (buriedASALeft+buriedASARight)>=".$asaLower." AND (buriedASALeft+buriedASARight)<=".$asaUpper;
			$queryString = $queryString." AND propensity".$residueName.">=".$propensityLower." AND propensity".$residueName."<=".$propensityUpper;
			break;
		case 2: 
			$queryString = "SELECT interfaceId, (residueCountLeft+residueCountRight) AS residueCount, (conservedCountLeft+conservedCountRight) AS conservedCount, hotspotCount2 AS hotspotCount, (avgScoreNormalizedLeft+avgScoreNormalizedRight)/2 AS avgScoreNormalized, (buriedASALeft+buriedASARight) AS buriedASA, propensity".$residueName." AS propensity";
			$queryString = $queryString." FROM interfaces_cumulative_information WHERE hotspotCount2>=".$nHotspotLower." AND hotspotCount2<=".$nHotspotUpper;
			$queryString = $queryString." AND (conservedCountLeft+conservedCountRight)>=".$nConservedLower." AND (conservedCountLeft+conservedCountRight)<=".$nConservedUpper;
			$queryString = $queryString." AND (avgScoreNormalizedLeft+avgScoreNormalizedRight)/2>=".$avgConservationScoreLower." AND (avgScoreNormalizedLeft+avgScoreNormalizedRight)/2<=".$avgConservationScoreUpper;
			$queryString = $queryString." AND (buriedASALeft+buriedASARight)>=".$asaLower." AND (buriedASALeft+buriedASARight)<=".$asaUpper;
			$queryString = $queryString." AND propensity".$residueName.">=".$propensityLower." AND propensity".$residueName."<=".$propensityUpper;
			break;
		case 3:
			$queryString = "SELECT interfaceId, (residueCountLeft+residueCountRight) AS residueCount, (conservedCountLeft+conservedCountRight) AS conservedCount, hotspotCount3 AS hotspotCount, (avgScoreNormalizedLeft+avgScoreNormalizedRight)/2 AS avgScoreNormalized, (buriedASALeft+buriedASARight) AS buriedASA, propensity".$residueName." AS propensity";
			$queryString = $queryString." FROM interfaces_cumulative_information WHERE hotspotCount3>=".$nHotspotLower." AND hotspotCount3<=".$nHotspotUpper;
			$queryString = $queryString." AND (conservedCountLeft+conservedCountRight)>=".$nConservedLower." AND (conservedCountLeft+conservedCountRight)<=".$nConservedUpper;
			$queryString = $queryString." AND (avgScoreNormalizedLeft+avgScoreNormalizedRight)/2>=".$avgConservationScoreLower." AND (avgScoreNormalizedLeft+avgScoreNormalizedRight)/2<=".$avgConservationScoreUpper;
			$queryString = $queryString." AND (buriedASALeft+buriedASARight)>=".$asaLower." AND (buriedASALeft+buriedASARight)<=".$asaUpper;
			$queryString = $queryString." AND propensity".$residueName.">=".$propensityLower." AND propensity".$residueName."<=".$propensityUpper;
			break;
	}
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$num_results = mysql_num_rows($result);
	echo "<h2> ADVANCED SEARCH RESULTS </h2>";
	if($num_results==0) {
		echo '<div id="resultDiv" class="warning">';
		echo "No interface related to your search criteria has been found!";
		echo '</div>';
		return;
	}
	#echo "Searched for interfaces having ";
	echo "<h3> Number of interfaces matched: $num_results </h3> <br/>";
	echo '<table class="resultTable"> <tr> <th class="headerResultCell"> Interface Id </th>';
	echo '<th class="headerResultCell"> # Of <br/> Residues </th> ';
	echo '<th class="headerResultCell"> # Of <br/> Hotspots </th> ';
	echo '<th class="headerResultCell"> # Of <br/> Conserved<br/> Residues </th> ';
	echo '<th class="headerResultCell"> Average<br/> Conservation<br/> Score </th> ';
	echo '<th class="headerResultCell"> ASA<br/> change </th> ';
	echo '<th class="headerResultCell"> Propensity <br/> of '.$residueName.' </th> ';
	echo '</tr>';
	for ($i=0 ; $i<$num_results ; $i++)
	{
		$row = mysql_fetch_array($result);
		echo '<tr>';
		echo '<td class="resultCell"> <a href="result.php?queryId='.$row['interfaceId'].'&displayOnlyInterfaceResidues=true">'.$row['interfaceId'].'</a></td> ';
		echo '<td class="resultCell">'.$row['residueCount'].'</td> ';
		echo '<td class="resultCell">'.$row['hotspotCount'].'</td> ';
		echo '<td class="resultCell">'.$row['conservedCount'].'</td> ';
		echo '<td class="resultCell">'.ceil($row['avgScoreNormalized']).'</td> ';
		echo '<td class="resultCell">'.$row['buriedASA'].'</td> ';
		#echo '<td class="resultCell">'.$row['propensity'].'</td> ';
		printf('<td class="resultCell"> %.2f </td> ', $row['propensity']);	
		echo '</tr>';
	}
	@mysql_free_result($result);
	echo '</table>';	
}

////////////////////////////////////////////////////////////////////////////

/*
function findNumberOfHotspotsInInterface($pdbId, $leftChain, $rightChain, $definitionType, $cutoff) {
	$queryString = "SELECT C.chainLetter, C.residueScore, C.allABS, PC.allABS_Cmplx, p.residuePropensity from chain_residues C, partner_chain_residues PC, propensities p WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$pdbId.$leftChain.$rightChain."'";
	$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
	$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')";
	$queryString = $queryString." AND PC.residueType='C'";
	$queryString = $queryString." AND C.residueName=p.residueName ";
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	#print $queryString;
	// signal with -1 if interface data does not exist
	if (mysql_num_rows($result)==0) return array(-1, -1);
	#echo mysql_num_rows($result);
	$nLeft = 0;
	$nRight = 0;
	while($row = mysql_fetch_array($result)) {
		$score = scaleScore($row['residueScore']);
		#print ($row['residueScore']=='')."<br>";
		if($row['residueScore'] == '' && ($row['allABS'] == '' || $row['allABS_Cmplx'] == '')) {  # signal n/a of hot spot because of both score and asa
			$score = -1;
			if($row['chainLetter'] == $leftChain) {
				$nLeft = -4;	
			}
			else {
				$nRight = -4;
			}
		} else if($row['allABS'] == '' || $row['allABS_Cmplx'] == '') { 
			if($row['chainLetter'] == $leftChain) 
				$nLeft = -3;	# signal n/a of hot spot because of  asa
			else
				$nRight = -3;
		} else if($row['residueScore'] == '') { 
				$score = -1;
				if($row['chainLetter'] == $leftChain) {
					$nLeft = -2;	# signal n/a of hot spot because of score
				}
				else {
					$nRight = -2;
				}
			}
		switch ($definitionType) {
		case 1: 
			if($score>=$cutoff) {
				if($row['chainLetter'] == $leftChain)
					$nLeft++;
				else
					$nRight++;
			}
			break;
		case 2: 
			if(($score*$row['residuePropensity'])>=$cutoff) {
				if($row['chainLetter'] == $leftChain)
					$nLeft++;
				else
					$nRight++;			
			}
			break;
		case 3: 
			if(($score*$row['residuePropensity'])>=$cutoff && (($row['allABS']-$row['allABS_Cmplx'])>=THRESHOLDASADifference || $row['allABS_Cmplx']<=THRESHOLDASAComplex )) {
				if($row['chainLetter'] == $leftChain)
					$nLeft++;
				else
					$nRight++;
			}
			break;
		default:
			print "<div class='error'> An unexpected error occured, please contact with web master!! </div>"; 
			break;
		}
	}  	
	mysql_free_result($result);
	//echo $queryString;
	return array($nLeft, $nRight);
} 


function findNumberOfConservedResiduesInInterface($pdbId, $leftChain, $rightChain) {
	$queryString = "SELECT C.chainLetter, COUNT(*) from chain_residues C, partner_chain_residues PC WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$pdbId.$leftChain.$rightChain."'";
	$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
	$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')";
	$queryString = $queryString." AND PC.residueType='C'"; // IN ('N', 'C')";
	$queryString = $queryString." AND C.residueScore<=".(THRESHOLDSCORE);	
	$queryString = $queryString." GROUP BY C.chainLetter"; 	
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$row = mysql_fetch_row($result);
	if ($row[0]==$leftChain) {
		$nLeft = $row[1];
		$row = mysql_fetch_row($result);
		if ($row[0]==$rightChain)
			$nRight = $row[1];
		else 
			$nRight = 0;
	}
	else { 
		$nLeft = 0;
		if ($row[0]==$rightChain)
			$nRight = $row[1];
		else
			$nRight = 0;
	}
	mysql_free_result($result);
	return array($nLeft, $nRight);
} 

function findAverageConservationScoreOfInterface($pdbId, $leftChain, $rightChain) {
	$queryString = "SELECT C.chainLetter, C.residueScore from chain_residues C, partner_chain_residues PC WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$pdbId.$leftChain.$rightChain."'";
	$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
	$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')";
	$queryString = $queryString." AND PC.residueType='C'";	
	$queryString = $queryString." GROUP BY C.chainLetter, C.residuePosition"; 
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$sumL = 0.0;
	$iL = 0;
	$sumR = 0.0;
	$iR = 0;
	while($row = mysql_fetch_row($result)) {
		if($row[0]==$leftChain) { 
			$sumL += scaleScore($row[1]);
			$iL++;
		}
		else {
			$sumR += scaleScore($row[1]);
			$iR++;
		}
	}
	if ($iL==0) 
		$avgLeft = '-';
	else
		$avgLeft = round($sumL/$iL);
	if ($iR==0) 
		$avgRight = '-';
	else
		$avgRight = round($sumR/$iR);
	mysql_free_result($result);
	// echo 'sum='.$sum; echo '<br/>'; 
	return array($avgLeft, $avgRight);
}
*/


////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////
/*
function propensityDeficient_findNumberOfHotspotsInInterface($pdbId, $leftChain, $rightChain, $definitionType, $cutoff) {
	switch ($definitionType) {
		case 1: 
			$score = unScaleScore($cutoff);
			$queryString = "SELECT C.chainLetter, COUNT(*) from chain_residues C, partner_chain_residues PC WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$pdbId.$leftChain.$rightChain."'";
			$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
			$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')";
			$queryString = $queryString." AND PC.residueType='C'";
			$queryString = $queryString." AND C.residueScore<=".$score[1];
			$queryString = $queryString." GROUP BY C.chainLetter";
			break;
		case 2: 
			$score = unScaleScore($cutoff);
			$queryString = "SELECT C.chainLetter, COUNT(*) from chain_residues C, partner_chain_residues PC, propensities p WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$pdbId.$leftChain.$rightChain."'";
			$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
			$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')";
			$queryString = $queryString." AND PC.residueType='C'";
			$queryString = $queryString." AND C.residueName=p.residueName ";
			$queryString = $queryString." AND C.residueScore<=".$score[1]."/p.residuePropensity";
			$queryString = $queryString." GROUP BY C.chainLetter";
			break;
		case 3: 
			$score = unScaleScore($cutoff);
			$queryString = "SELECT C.chainLetter, COUNT(*) from chain_residues C, partner_chain_residues PC WHERE C.pdbId='".$pdbId."' AND PC.interfaceId='".$pdbId.$leftChain.$rightChain."'";
			$queryString = $queryString." AND C.chainLetter=PC.chainLetter AND C.residuePosition=PC.residuePosition"; 
			$queryString = $queryString." AND (C.chainLetter=BINARY'".$leftChain."' OR C.chainLetter=BINARY'".$rightChain."')";
			$queryString = $queryString." AND PC.residueType='C'";
			$queryString = $queryString." AND ((C.allABS-PC.allABS_Cmplx)>=".THRESHOLDASADifference." OR PC.allABS_Cmplx<=".THRESHOLDASAComplex;
			$queryString = $queryString." AND C.residueScore<=".$score[1]."/p.residuePropensity";
			$queryString = $queryString." GROUP BY C.chainLetter";
			break;
		default:
			print "<div class='error'> An unexpected error occured, please contact with web master!! </div>";
			$score = array(-10.0, 10.0); 
			break;
	} 
	# echo "<br/>".$queryString."<br/>"; 	
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$row = mysql_fetch_row($result);
	if ($row[0]==$leftChain) {
		$nLeft = $row[1];
		$row = mysql_fetch_row($result);
		if ($row[0]==$rightChain)
			$nRight = $row[1];
		else 
			$nRight = 0;
	}
	else { 
		$nLeft = 0;
		if ($row[0]==$rightChain)
			$nRight = $row[1];
		else
			$nRight = 0;
	}
	mysql_free_result($result);
	//echo $queryString;
	return array($nLeft, $nRight);
} 


// Generate and display graphics generated with rasmol for a given interface
function old_generateAndDisplayRasmolVisualization($queryId) {
	$pdbDir = "../../consurf/data/pdb/";
	$tempDir = "tmp/";
	$pdbId = substr($queryId, 0, 4);
	# These are predefined rasmol colors and their corresponding HTML codes
   $colorsRasmol = array('blue'=>'#0000FF', 'green'=>'#00FF00', 'red'=>'#FF0000',
                         'yellow'=>'#FFFF00', 'magenta'=>'#FF00FF', 'cyan'=>'#00FFFF',
                         'orange'=>'#FFA500', 'redorange'=>'#FF4500', 'violet'=>'#EE82EE',
                         'greenblue'=>'#2E8B57', 'purple'=>'#A020F0');         
   $colorsKeys = array_keys($colorsRasmol);                                                                                                                                                                       
   # First dynamically create a rasmol script file
	@ $fpRasmol = fopen($tempDir.$queryId.".scr", "w");
		  if ($fpRasmol == NULL)
        {
                echo '<b>Error: </b> Could not open script file for writing.<br>';
                exit;
        }                                                                                                                                                  
        fwrite($fpRasmol, "#Rasmol script for the visualization of interface $queryId \n#Generated automatically by result.php\n");
        fwrite($fpRasmol, "zap \n");
        fwrite($fpRasmol, "load ".$pdbDir.$pdbId.".pdb\n");
        fwrite($fpRasmol, "background white \n");
        fwrite($fpRasmol, "wireframe off \n");
        fwrite($fpRasmol, "trace on \n");
        fwrite($fpRasmol, "select *:$queryId[4] \n");
        
        #if (is_numeric($queryId[4]))
        #        fwrite($fpRasmol, "color ".$colorsKeys[$queryId[4]%11]."\n");
        #else
        #        fwrite($fpRasmol, "color ".$colorsKeys[(ord($queryId[4])-65)%11]."\n");
        
        fwrite($fpRasmol, "color ".$colorsKeys[10]."\n");
        fwrite($fpRasmol, "select *:$queryId[5] \n");
         
        #if (is_numeric($queryId[5]))
        #        fwrite($fpRasmol, "color ".$colorsKeys[$queryId[5]%11]."\n");
        #else
        #        fwrite($fpRasmol, "color ".$colorsKeys[(ord($queryId[5])-65)%11]."\n");
        
        fwrite($fpRasmol, "color ".$colorsKeys[7]."\n");
                                                                                                                                                             
        fwrite($fpRasmol, "select none \n");
        $db = connectToHotsprintDatabase();
        # $query = "SELECT resNumber, chain FROM interface_residues WHERE interfaceID = '".$interfaceID."'";
        $query = "SELECT residuePosition, chainLetter FROM chain_residues_conservation_scores WHERE pdbId='".$pdbId."' AND (chainLetter='".$queryId[4]."' OR chainLetter='".$queryId[5]."') AND (residueType='C')";
        $result = mysql_query($query) or die('Problem in Sql Query:');
        $num_results = mysql_num_rows($result);
        for ($i=0 ; $i<$num_results ; $i++)
        {
                $row = mysql_fetch_array($result);
                $residueNo = $row['residuePosition'];
                $chain = $row['chainLetter'];
                fwrite ($fpRasmol, "select selected or $residueNo:$chain \n");
        }
        mysql_free_result($result);
		closeDatabaseConnection($db);
        fwrite($fpRasmol, "wireframe off \n");
        fwrite($fpRasmol, "spacefill on \n");
        fwrite($fpRasmol, "define bothChains selected \n");
        fwrite($fpRasmol, "select bothChains and *:$queryId[4] \n");
        fwrite($fpRasmol, "define chainOne selected \n");
        fwrite($fpRasmol, "select bothChains and *:$queryId[5] \n");
        fwrite($fpRasmol, "define chainTwo selected \n");
                                                                                                                                                             
        for ($i=0 ; $i<4 ; $i++)
        {
                fwrite($fpRasmol, "write gif $queryId$i.gif \n");
                fwrite($fpRasmol, "rotate x 90 \n");
        }
        fwrite($fpRasmol, "quit \n");
        fclose($fpRasmol);
                                                                                                                                                             
        # Let Rasmol execute the script
        chdir($tempDir);    #Temporarily change directory s.t. output of the script will be under the right dir	
        exec("./rasmol_8BIT -nodisplay < $queryId.scr");
        chdir("..");
        //unlink("$tempDir.$queryId.scr");
                                                                                                                                                             
        # Print legend
         
        #if (is_numeric($queryId[4]))
        #        $index = $queryId[4];
        #else
        #        $index = (ord($queryId[4])-65)%11;
        
        $index = 10;
        echo "<fieldset>";
		  echo "<legend> <strong>Legend:</strong> </legend>";
        echo '<font color="'.$colorsRasmol["$colorsKeys[$index]"].'"><b>Buried surface on chain '.$queryId[4].' </b></font>';
        echo ' &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp';
        
        #if (is_numeric($queryId[5]))
        #        $index = $queryId[5];
        #else
        #        $index = (ord($queryId[5])-65)%11;
        
        $index = 7;
        echo '<font color="'.$colorsRasmol["$colorsKeys[$index]"].'"><b>Buried surface on chain '.$queryId[5].' </b></font>';
        echo "</fieldset>";
        # echo '</td> </tr> <tr> ';                                                                                                                                                     
        # Display the dynamically generated gif images
        for ($i=0 ; $i<4 ; $i++) //for ($i=0 ; $i<4 ; $i++)
        {
                echo '<img src="'.$tempDir.$queryId.$i.'.gif" width="35%" height="35%" alt=" Image of Interface '.$queryId.'">';
                # echo '<br/>';
        }
}


function displayInterfacePartnerResidues($queryId, $option) {
	switch (strlen($queryId)) {
		case 6: {
			$pdbId = substr($queryId, 0, 4);
			$leftPartnerChainLetter = $queryId[4];
			$rightPartnerChainLetter = $queryId[5];
			echo '<li> <span class="resultTitle"> Overall Characteristics </span> </li> <br/>';
			echo "<table class='resultTable'>";
			echo "<tr> <th class='headerResultCell'> # of Conserved Residues </th> <th class='headerResultCell'> Avg. Conservation Score </th> <th class='headerResultCell'> Burried ASA </th>  </tr>";
			echo "<tr> <td class='resultCell'> ".old_findNumberOfHotspotsInInterface($pdbId, $leftPartnerChainLetter, $rightPartnerChainLetter)." </td>";
			echo "<td class='resultCell'> ".findAverageConservationScoreOfInterface($pdbId, $leftPartnerChainLetter, $rightPartnerChainLetter)."</td>";
			echo "<td class='resultCell'> ".findBurriedASAOfInterface($pdbId, $leftPartnerChainLetter, $rightPartnerChainLetter)." </td> </tr>";
			echo "</table> <br/> <br/> ";			
			echo '<li> <span class="resultTitle"> Residues of the Interface </span> </li> <br/>';
			displayLegend();
			echo '<br/> ';
			echo '<table> <td class="spacedVerticalCell">';
			fetchAndDisplayInterfaceResiudesInformationFromDatabase($pdbId, $leftPartnerChainLetter, $queryId, $option);
			//fetchAndDisplayInterfacePartnerChainResiudesInformationFromDatabase($pdbId, $leftPartnerChainLetter, $option);
			echo '</td> <td class="spacedVerticalCell">';
			fetchAndDisplayInterfaceResiudesInformationFromDatabase($pdbId, $rightPartnerChainLetter, $queryId, $option);
			//fetchAndDisplayInterfacePartnerChainResiudesInformationFromDatabase($pdbId, $rightPartnerChainLetter, $option);
			//echo '</td> <td class="spacedVerticalCell">';
			//echo '</td>';
			echo '</table>';
			echo '<br/> <br/>';
			echo '<li> <span class="resultTitle"> Visualization of the Interface </span> </li> <br/>';
			generateAndDisplayRasmolVisualization($queryId);
			break;
		}
		case 5: {
			displayLegend();
			$pdbId = substr($queryId, 0, 4);
			$leftPartnerChainLetter = $queryId[4];
			fetchAndDisplayInterfacePartnerChainResiudesInformationFromDatabase($pdbId, $leftPartnerChainLetter, $option);
			break;
			}
		case 4: {
			$pdbId = substr($queryId, 0, 4);
			fetchAndDisplayPartnerChainsOfInterfaceFromDatabase($pdbId, $option);
			break;
			}
	}
}

function old_fetchAndDisplayInterfaceResiudesInformationFromDatabase($pdbId, $chainLetter, $interfaceId, $option) {
	$db = connectToHotsprintDatabase();
	$queryString = "SELECT chainLetter, residuePosition, residueName, allABS, allABS_Cmplx from partner_chain_residues_ASAs WHERE interfaceId='".$interfaceId."' AND chainLetter='".$chainLetter."'";
	$resultASA = mysql_query($queryString) or die('Problem in Sql Query');
	$queryString = "SELECT * from chain_residues_conservation_scores WHERE pdbId='".$pdbId."' AND chainLetter=BINARY'".$chainLetter."'";
	if($option == "true") {
		$queryString = $queryString." AND (residueType='C' OR residueType='N')";
	}
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	echo $pdbId.$chainLetter;
	echo "<table class='resultTable'>";
	echo "<tr> <th class='headerResultCell'> Position </th> <th class='headerResultCell'> Name </th> <th class='headerResultCell'> Cons. <br/> Score </th> <th class='headerResultCell'> ASA <br/> in <br/> Chain </th> <th class='headerResultCell'> ASA in <br/> Complex </th> <th class='headerResultCell'> Type </th> </tr>";
	$num_results = mysql_num_rows($result);
	for ($i=0 ; $i<$num_results ; $i++)
	{
		$row = mysql_fetch_array($result);
		$rowASA = mysql_fetch_array($resultASA);
		$score = scaleScore($row['residueScore']);
		if ( $score >= 7) {
			if($row['residueType'] == 'N') { 
				echo '<tr class="conservedInterface">';
			}
			else if($row['residueType'] == 'C') {
				echo '<tr class="conservedInterface">';
			} else {
				echo '<tr class="conserved">';
			}
		} else if ($row['residueType'] == 'N' || $row['residueType'] == 'C') {
				echo '<tr class="interface">';
		} else {
				echo '<tr>';
		}
		echo "<td class='resultCell'>".$row['residuePosition']."</td>";
		echo "<td class='resultCell'>".$row['residueName']."</td>";
		echo "<td class='resultCell'>".$score."</td>";
		while($row['residuePosition'] != $rowASA['residuePosition']) {
			$rowASA = mysql_fetch_array($resultASA);
		}
		if($row['residueName'] != $rowASA['residueName']) {
			echo "<td class='resultCell'>".$rowASA['residueName']."</td>";
			echo "<td class='resultCell'>".$rowASA['residueName']."</td>";
		} else {
			echo "<td class='resultCell'>".$rowASA['allABS']."</td>";
			echo "<td class='resultCell'>".$rowASA['allABS_Cmplx']."</td>";
		}
		echo "<td class='resultCell'>".$row['residueType']."</td>";
		echo "</tr>";
	}
	echo "</table>";
	mysql_free_result($result);
	closeDatabaseConnection($db);
}

function fetchAndDisplayInterfacePartnerChainResiudesInformationFromDatabase($pdbId, $chainLetter, $option) {
	$db = connectToHotsprintDatabase();
	$queryString = "SELECT * from chain_residues_conservation_scores WHERE pdbId='".$pdbId."' AND chainLetter=BINARY'".$chainLetter."'";
	if($option == "true") {
		$queryString = $queryString." AND (residueType='C' OR residueType='N')";
	}
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	echo $pdbId.$chainLetter;
	echo "<table class='resultTable'>";
	echo "<tr> <th class='headerResultCell'> Position </th> <th class='headerResultCell'> Name </th> <th class='headerResultCell'> <center> Conservation <br/> Score </center> </th> <th class='headerResultCell'> Type </th> </tr>";
	$num_results = mysql_num_rows($result);
	for ($i=0 ; $i<$num_results ; $i++)
	{
		$row = mysql_fetch_array($result);
		$score = scaleScore($row['residueScore']);
		if ( $score >= 7) {
			if($row['residueType'] == 'N') { 
				echo '<tr class="conservedInterface">';
			}
			else if($row['residueType'] == 'C') {
				echo '<tr class="conservedInterface">';
			} else {
				echo '<tr class="conserved">';
			}
		} else if ($row['residueType'] == 'N' || $row['residueType'] == 'C') {
				echo '<tr class="interface">';
		} else {
				echo '<tr>';
		}
		echo "<td class='resultCell'>".$row['residuePosition']."</td>";
		echo "<td class='resultCell'>".$row['residueName']."</td>";
		echo "<td class='resultCell'>".$score."</td>";
		echo "<td class='resultCell'>".$row['residueType']."</td>";
		echo "</tr>";
	}
	echo "</table>";
	mysql_free_result($result);
	closeDatabaseConnection($db);
}

function fetchAndDisplayPartnerChainsOfInterfaceFromDatabase($pdbId, $option) {
	$db = connectToHotsprintDatabase();
	$queryString = "SELECT DISTINCT(chainLetter) from chain_residues_conservation_scores WHERE pdbId='".$pdbId."'";
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	echo 'Select a chain to display its residues <br/><br/>';
	while($row = mysql_fetch_row($result)) {
		echo '<a href="result.php?queryId='.$pdbId.$row[0].'&queryType=displayInterfaceResidues&displayOnlyInterfaceResidues='.$option.'">'.$pdbId.$row[0]."</a><br/><br/>";
	}
	mysql_free_result($result);
	closeDatabaseConnection($db);
}

////////////////////////////////////////////////////////////////////////////
// Average Score Functions

function displayInterfacePartnerAverageConservationScore($queryId, $option) {
	switch (strlen($queryId)) {
		case 6: {
			$pdbId = substr($queryId, 0, 4);
			$leftPartnerChainLetter = $queryId[4];
			$rightPartnerChainLetter = $queryId[5];
			echo '<table class="resultTable"> <tr> <th class="headerResultCell"> Id </th>';
			echo '<th class="headerResultCell"> Avg. Score </th> </tr>';
			echo '<tr> <td class="resultCell">'.$pdbId.$leftPartnerChainLetter.'</td>';
			$scoreLeft = findAverageConservationScoreOfInterfacePartnerChainResidues($pdbId, $leftPartnerChainLetter, $option);  
			echo '<td class="resultCell">'.scaleScore($scoreLeft).'</td> </tr>';
			echo '<tr> <td class="resultCell">'.$pdbId.$rightPartnerChainLetter.'</td>';
			$scoreRight = findAverageConservationScoreOfInterfacePartnerChainResidues($pdbId, $rightPartnerChainLetter, $option); 
			echo '<td class="resultCell">'.scaleScore($scoreRight)."</td> </tr>";
			echo '<tr> <td class="resultCell">'.$pdbId.$leftPartnerChainLetter.$rightPartnerChainLetter.'</td>'; 
			echo '<td class="resultCell">'.scaleScore(($scoreLeft + $scoreRight) / 2)."</td> </tr>"; 
			echo '</table>';
			break;
		}
		case 5: {
			$pdbId = substr($queryId, 0, 4);
			$leftPartnerChainLetter = $queryId[4];
			echo '<table class="resultTable"> <tr> <th class="headerResultCell"> Id </th>';
			echo '<th class="headerResultCell"> Avg. Score </th> </tr>';
			echo '<tr> <td class="resultCell">'.$pdbId.$leftPartnerChainLetter.'</td>';
			$scoreLeft = findAverageConservationScoreOfInterfacePartnerChainResidues($pdbId, $leftPartnerChainLetter, $option);  
			echo '<td class="resultCell">'.scaleScore($scoreLeft).'</td> </tr>';
			echo '</table>';
			break;
			}
		case 4: {
			$pdbId = substr($queryId, 0, 4);
			$db = connectToHotsprintDatabase();
			$queryString = "SELECT DISTINCT(chainLetter) from chain_residues_conservation_scores WHERE pdbId='".$pdbId."'";
			$result = mysql_query($queryString) or die('Problem in Sql Query');
			closeDatabaseConnection($db);
			$scoreTotal = 0;
			echo '<table class="resultTable"> <tr> <th class="headerResultCell"> Id </th>';
			echo '<th class="headerResultCell"> Avg. Score </th> </tr>';
			$i=0;
			while($row = mysql_fetch_row($result)) {
				echo '<tr> <td class="resultCell">'.$pdbId.$row[0].'</td>';
				$score = findAverageConservationScoreOfInterfacePartnerChainResidues($pdbId, $row[0], $option);
				$scoreTotal += $score; 
				echo '<td class="resultCell">'.scaleScore($score).'</td> </tr>';
				$i++;
			}
			echo '<tr> <td class="resultCell">'.$pdbId.'</td>';
			echo '<td class="resultCell">'.scaleScore($scoreTotal/$i).'</td> </tr>';
			echo '</table>'; 
			mysql_free_result($result);
			break;
			}
	}
}

function findAverageConservationScoreOfInterfacePartnerChainResidues($pdbId, $chainLetter, $option) {
	$db = connectToHotsprintDatabase();
	$queryString = "SELECT AVG(residueScore) from chain_residues_conservation_scores WHERE pdbId='".$pdbId."' AND chainLetter=BINARY'".$chainLetter."'";
	if($option == "true") {
		$queryString = $queryString." AND residueType='C'";
	} 
	# else {
	#	$queryString = $queryString." AND (residueType='C' OR residueType='N')";
	#} 
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$row = mysql_fetch_row($result);
	mysql_free_result($result);
	closeDatabaseConnection($db);
	return $row[0];
}

////////////////////////////////////////////////////////////////////////////
// Functions for Finding and Displaying Chains With Provided Number Of Hotspots

function displayNumberOfHotspots($queryId, $option) {
	switch (strlen($queryId)) {
		case 6: {
			$pdbId = substr($queryId, 0, 4);
			$leftPartnerChainLetter = $queryId[4];
			$rightPartnerChainLetter = $queryId[5];
			echo '<table class="resultTable"> <tr> <th class="headerResultCell"> Id </th>';
			echo '<th class="headerResultCell"> # of Hotspots </th> </tr>';
			echo '<tr> <td class="resultCell">'.$pdbId.$leftPartnerChainLetter.'</td>';
			$nHotspotLeft = findNumberOfHotspotsInAChain($pdbId, $leftPartnerChainLetter, $option);  
			echo '<td class="resultCell">'.$nHotspotLeft.'</td> </tr>';
			echo '<tr> <td class="resultCell">'.$pdbId.$rightPartnerChainLetter.'</td>';
			$nHotspotRight = findNumberOfHotspotsInAChain($pdbId, $rightPartnerChainLetter, $option);  
			echo '<td class="resultCell">'.$nHotspotRight."</td> </tr>";
			echo '<tr> <td class="resultCell">'.$pdbId.$leftPartnerChainLetter.$rightPartnerChainLetter.'</td>'; 
			echo '<td class="resultCell">'.($nHotspotLeft + $nHotspotRight)."</td> </tr>"; 
			echo '</table>';
			break;
		}
		case 5: {
			$pdbId = substr($queryId, 0, 4);
			$leftPartnerChainLetter = $queryId[4];
			echo '<table class="resultTable"> <tr> <th class="headerResultCell"> Id </th>';
			echo '<th class="headerResultCell"> # of Hotspots </th> </tr>';
			echo '<tr> <td class="resultCell">'.$pdbId.$leftPartnerChainLetter.'</td>';
			$nHotspotLeft = findNumberOfHotspotsInAChain($pdbId, $leftPartnerChainLetter, $option);  
			echo '<td class="resultCell">'.$nHotspotLeft.'</td> </tr>';
			echo '</table>';
			break;
			}
		case 4: {
			$pdbId = substr($queryId, 0, 4);
			$db = connectToHotsprintDatabase();
			$queryString = "SELECT DISTINCT(chainLetter) from chain_residues_conservation_scores WHERE pdbId='".$pdbId."'";
			$result = mysql_query($queryString) or die('Problem in Sql Query');
			closeDatabaseConnection($db);
			$nHotspotTotal = 0;
			echo '<table class="resultTable"> <tr> <th class="headerResultCell"> Id </th>';
			echo '<th class="headerResultCell"> # of Hotspots </th> </tr>';
			while($row = mysql_fetch_row($result)) {
				echo '<tr> <td class="resultCell">'.$pdbId.$row[0].'</td>';
				$nHotspot = findNumberOfHotspotsInAChain($pdbId, $row[0], $option);  
				$nHotspotTotal += $nHotspot; 
				echo '<td class="resultCell">'.$nHotspot.'</td> </tr>';
			}
			echo '<tr> <td class="resultCell">'.$pdbId.'</td>';
			echo '<td class="resultCell">'.$nHotspotTotal.'</td> </tr>';
			echo '</table>'; 
			mysql_free_result($result);
			break;
			}
	}
}

function findNumberOfHotspotsInAChain($pdbId, $chainLetter, $option) {
	$db = connectToHotsprintDatabase();
	$queryString = "SELECT COUNT(*) from chain_residues_conservation_scores WHERE pdbId='".$pdbId."' AND chainLetter=BINARY'".$chainLetter."'";
	$queryString = $queryString." AND residueScore<=".(THRESHOLDSCORE);	
	if($option == "true") {
		$queryString = $queryString." AND residueType='C'";
	}
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$row = mysql_fetch_row($result);
	mysql_free_result($result);
	closeDatabaseConnection($db);
	return $row[0];
}

////////////////////////////////////////////////////////////////////////////
// Functions for Finding and Displaying Chains With Provided Criteria

function fetchAndDisplayStructuresSatisfyingSpecifiedCriteria($structureType, $nHotspotLower, $nHotspotUpper, $avgConservationScoreLower, $avgConservationScoreUpper, $residueName, 
$propensityLower, $propensityUpper, $asaLower, $asaUpper, $option) {
	echo '<table class="resultTable"> <tr> <th class="headerResultCell"> Id </th>';
	# echo $avgConservationScoreLower."<br>";
	# echo strcmp($avgConservationScoreLower, "")."<br>";

	# if((strcmp($nHotspotLower, "") || strcmp($nHotspotUpper, "")) && (strcmp($avgConservationScoreLower, "") || strcmp($avgConservationScoreUpper, "")) && (strcmp($propensityLower, "") || strcmp($propensityUpper, ""))) {
	#	$result = fetchStructuresInProvidedHotspotCountAndAverageConservationScoreAndPropensityRange($structureType, $nHotspotLower, $nHotspotUpper, $avgConservationScoreLower, $avgConservationScoreUpper, $propensityLower, $propensityUpper, $option);
	#	echo '<th class="headerResultCell"> # Of Hotspots </th> ';
	#	echo '<th class="headerResultCell"> Avg. Score </th> ';
	#	echo '<th class="headerResultCell"> Propensity </th> ';
	#	echo '</tr>';
	#	switch ($structureType) {
	#		case "interface": {
	#			for($i=0; $i<count($result); $i++) {
	#				echo '<tr> <td class="resultCell">'.$result[$i][0].'</td>';
	#				echo '<td class="resultCell">'.$result[$i][1].'</td> ';
	#				echo '<td class="resultCell">'.(int)$result[$i][2].'</td> ';
	#				echo '<td class="resultCell">'.$result[$i][3].'</td> ';
	#				echo '</tr>';
	#			}
	#		} break;
	#		case "chain": {
	#			while($row = mysql_fetch_array($result)) {
	#				echo '<tr> <td class="resultCell">'.$row['pdbId'].$row['chainLetter'].'</td>';
	#				echo '<td class="resultCell">'.$row['count'].'</td> ';
	#				echo '<td class="resultCell">'.$row['avgScore'].'</td> ';
	#				echo '<td class="resultCell">'.$row['propensity'].'</td> ';
	#				echo '</tr>';
	#			}
	#		} break;
	#		case "protein": {
	#			while($row = mysql_fetch_array($result)) {
	#				echo '<tr> <td class="resultCell">'.$row['pdbId'].$row['chainLetter'].'</td>';
	#				echo '<td class="resultCell">'.$row['count'].'</td> ';
	#				echo '<td class="resultCell">'.(int)$row['avgScore'].'</td> ';
	#				echo '<td class="resultCell">'.$row['propensity'].'</td> ';
	#				echo '</tr>';
	#			}
	#		} break;
	#	}
	#	@mysql_free_result($result);
	#} else 
		 if((strcmp($nHotspotLower, "") || strcmp($nHotspotUpper, "")) && (strcmp($avgConservationScoreLower, "") || strcmp($avgConservationScoreUpper, ""))) {
		$result = fetchStructuresInProvidedHotspotCountAndAverageConservationScoreRange($structureType, $nHotspotLower, $nHotspotUpper, $avgConservationScoreLower, $avgConservationScoreUpper, $option);
		echo '<th class="headerResultCell"> # Of Hotspots </th> ';
		echo '<th class="headerResultCell"> Avg. Score </th> ';
		echo '</tr>';
		switch ($structureType) {
			case "interface": {
				for($i=0; $i<count($result); $i++) {
					echo '<tr> <td class="resultCell">'.$result[$i][0].'</td>';
					echo '<td class="resultCell">'.$result[$i][1].'</td> ';
					echo '<td class="resultCell">'.(int)$result[$i][2].'</td> ';
					echo '</tr>';
				}
			} break;
			case "chain": {
				while($row = mysql_fetch_array($result)) {
					echo '<tr> <td class="resultCell">'.$row['pdbId'].$row['chainLetter'].'</td>';
					echo '<td class="resultCell">'.$row['count'].'</td> ';
					echo '<td class="resultCell">'.$row['avgScore'].'</td> ';
					echo '</tr>';
				}
			} break;
			case "protein": {
				while($row = mysql_fetch_array($result)) {
					echo '<tr> <td class="resultCell">'.$row['pdbId'].$row['chainLetter'].'</td>';
					echo '<td class="resultCell">'.$row['count'].'</td> ';
					echo '<td class="resultCell">'.(int)$row['avgScore'].'</td> ';
					echo '</tr>';
				}
			} break;
		}
		@mysql_free_result($result);
	} else if(strcmp($nHotspotLower, "") || strcmp($nHotspotUpper, "")) {
		echo '<th class="headerResultCell"> # Of Hotspots </th> ';
		echo '</tr>';
		$resultNHotspot = fetchStructuresInProvidedHotspotCountRange($structureType, $nHotspotLower, $nHotspotUpper, $option);
		switch ($structureType) {
			case "interface": {
				for($i=0; $i<count($resultNHotspot); $i++) {
					echo '<tr> <td class="resultCell">'.$resultNHotspot[$i][0].'</td>';
					echo '<td class="resultCell">'.$resultNHotspot[$i][1].'</td> ';
					echo '</tr>';
				}
			} break;
			case "chain": {
				while($row = mysql_fetch_array($resultNHotspot)) {
					echo '<tr> <td class="resultCell">'.$row['pdbId'].$row['chainLetter'].'</td>';
					echo '<td class="resultCell">'.$row['count'].'</td> ';
					echo '</tr>';
				}
			} break;
			case "protein": {
				while($row = mysql_fetch_array($resultNHotspot)) {
					echo '<tr> <td class="resultCell">'.$row['pdbId'].'</td>';
					echo '<td class="resultCell">'.$row['count'].'</td> ';
					echo '</tr>';
				}
			} break;
		}
		@mysql_free_result($resultNHotspot);
	} else if(strcmp($avgConservationScoreLower, "") || strcmp($avgConservationScoreUpper, "")) {
		echo '<th class="headerResultCell"> Avg. Score </th> ';
		echo '</tr>';
		$resultAvgConservationScore = fetchStructuresInProvidedAverageConservationScoreRange($structureType, $avgConservationScoreLower, $avgConservationScoreUpper, $option);
		switch ($structureType) {
			case "interface": {
				for($i=0; $i<count($resultAvgConservationScore); $i++) {
					echo '<tr> <td class="resultCell">'.$resultAvgConservationScore[$i][0].'</td>';
					echo '<td class="resultCell">'.(int)$resultAvgConservationScore[$i][1].'</td> ';
					echo '</tr>';
				}
			} break;
			case "chain": {
				while($row = mysql_fetch_array($resultAvgConservationScore)) {
					echo '<tr> <td class="resultCell">'.$row['pdbId'].$row['chainLetter'].'</td>';
					echo '<td class="resultCell">'.$row['avgScore'].'</td> </tr>';
				}
			} break;
			case "protein": {
				while($row = mysql_fetch_array($resultAvgConservationScore)) {
					echo '<tr> <td class="resultCell">'.$row['pdbId'].'</td>';
					echo '<td class="resultCell">'.(int)$row['avgScore'].'</td> </tr>';
				}
			} break;
		}
		@mysql_free_result($resultAvgConservationScore);
	} else if(strcmp($propensityLower, "") || strcmp($propensityUpper, "")) {
		echo '<th class="headerResultCell"> Propensity </th> ';
		echo '</tr>';
		$resultPropensity = fetchStructuresInProvidedPropensityRange($structureType, $residueName, $propensityLower, $propensityUpper);
		switch ($structureType) {
			case "interface": {
				for($i=0; $i<count($resultPropensity); $i++) {
					echo '<tr> <td class="resultCell">'.$resultPropensity[$i][0].'</td>';
					#echo '<td class="resultCell">'.(100 * $resultPropensity[$i][1]).'</td> ';
					echo '<td class="resultCell">'.($resultPropensity[$i][1]).'</td> ';
					echo '</tr>';
				}
			} break;
			case "chain": {
				while($row = mysql_fetch_array($resultPropensity)) {
					echo '<tr> <td class="resultCell">'.$row['pdbId'].$row['chainLetter'].'</td>';
					#echo '<td class="resultCell">'.(100 * $row['propensity']).'</td> </tr>';
					echo '<td class="resultCell">'.($row['propensity']).'</td> </tr>';
				}
			} break;
			case "protein": {
				while($row = mysql_fetch_array($resultPropensity)) {
					echo '<tr> <td class="resultCell">'.$row['pdbId'].'</td>';
					#echo '<td class="resultCell">'.(100 * $row['propensity']).'</td> </tr>';
					echo '<td class="resultCell">'.($row['propensity']).'</td> </tr>';
				}
			} break;
		} 
		@mysql_free_result($resultPropensity);
	}else if(strcmp($asaLower, "") || strcmp($asaUpper, "")) {
		echo '<th class="headerResultCell"> ASA change </th> ';
		echo '</tr>';
		$resultAsa = fetchInterfacesInProvidedAsaRange($asaLower, $asaUpper);
		switch ($structureType) {
			case "interface": {
				while($row = mysql_fetch_array($resultAsa)) {
					echo '<tr> <td class="resultCell">'.$row['interfaceID'].'</td>';
					echo '<td class="resultCell">'.$row['asaBuried'].'</td> </tr>';
				}
			} break;
		}
		@mysql_free_result($resultAsa);
	}	
	echo '</table>';	
}

function fetchStructuresInProvidedHotspotCountRange($structureType, $nHotspotLower, $nHotspotUpper, $option) {
	$db = connectToHotsprintDatabase();
	if($option == "true") {
		$fieldName = "hotspotCountContact";
	} else {
		$fieldName = "hotspotCount";
	}
	if(strcmp($nHotspotLower, "") && strcmp($nHotspotUpper, "")) {
		$queryStringLast = " HAVING SUM(".$fieldName.") >= ".$nHotspotLower." AND SUM(".$fieldName.") <= ".$nHotspotUpper;
	}
	else {
		if(strcmp($nHotspotLower, "")) {
			$queryStringLast = " HAVING SUM(".$fieldName.") >= ".$nHotspotLower;
		} else if(strcmp($nHotspotUpper, "")) {
			$queryStringLast = " HAVING SUM(".$fieldName.") <= ".$nHotspotUpper;
		}
	}
	switch ($structureType) {
		case "interface": {
			$queryString = "SELECT interfaceID from interfaces";
			$result = mysql_query($queryString) or die('Problem in Sql Query');
			$resultArray = array();
			while($row = mysql_fetch_array($result)) {
				$pdbId = substr($row['interfaceID'], 0, 4);
				$leftPartnerChainLetter = $row['interfaceID'][4];
				$rightPartnerChainLetter = $row['interfaceID'][5];			
				$queryStringInner = "SELECT SUM(".$fieldName.") AS count from chains_cumulative_information WHERE pdbId='".$pdbId;
				$queryStringInner = $queryStringInner."' AND (chainLetter='".$leftPartnerChainLetter."' OR chainLetter='".$rightPartnerChainLetter."')";	
				$queryStringInner = $queryStringInner."  GROUP BY pdbId".$queryStringLast;
				$resultInner = mysql_query($queryStringInner) or die($queryStringInner);#die('Problem in Sql Query');
				$rowInner = mysql_fetch_array($resultInner);
				if($rowInner!=null) {
					$resultArray[] = array($row['interfaceID'], $rowInner['count']);
				}  
			} 
			mysql_free_result($result);
			closeDatabaseConnection($db);
			return $resultArray;
		}
		case "chain": {
			$queryString = "SELECT pdbId, chainLetter, SUM(".$fieldName.") AS count FROM chains_cumulative_information";
			$queryString = $queryString." GROUP BY pdbId, chainLetter".$queryStringLast;
			break;
		}
		case "protein": {
			$queryString = "SELECT pdbId, SUM(".$fieldName.") AS count from chains_cumulative_information";
			$queryString = $queryString." GROUP BY pdbId".$queryStringLast;
			break;
		}
	}
	#$result = mysql_query($queryString) or die('Problem in Sql Query');
	$result = mysql_query($queryString) or die($queryString);
	closeDatabaseConnection($db);
	return $result;	
}

function fetchStructuresInProvidedAverageConservationScoreRange($structureType, $avgConservationScoreLower, $avgConservationScoreUpper, $option) {
	$db = connectToHotsprintDatabase();
	if($option == "true") {
		$fieldName = "avgScoreScaledContact";
	} else {
		$fieldName = "avgScoreScaled";
	}	
	switch ($structureType) {
		case "interface": {
			$queryString = "SELECT interfaceID from interfaces";
			$result = mysql_query($queryString) or die('Problem in Sql Query');
			$resultArray = array();
			if(strcmp($avgConservationScoreLower, "") && strcmp($avgConservationScoreUpper, "")) {
				$queryStringLast = " HAVING SUM(".$fieldName.") / 2 >= ".$avgConservationScoreLower." AND SUM(".$fieldName.") / 2 <= ".$avgConservationScoreUpper;
			}
			else {
				if(strcmp($avgConservationScoreLower, "")) {
					$queryStringLast = " HAVING SUM(".$fieldName.") / 2 >= ".$avgConservationScoreLower;
				} else if(strcmp($avgConservationScoreUpper, "")) {
					$queryStringLast = " HAVING SUM(".$fieldName.") / 2 <= ".$avgConservationScoreUpper;
				}
			}
			while($row = mysql_fetch_array($result)) {
				$pdbId = substr($row['interfaceID'], 0, 4);
				$leftPartnerChainLetter = $row['interfaceID'][4];
				$rightPartnerChainLetter = $row['interfaceID'][5];			
				$queryStringInner = "SELECT SUM(".$fieldName.") / 2 AS avgScore from chains_cumulative_information WHERE pdbId='".$pdbId;
				$queryStringInner = $queryStringInner."' AND (chainLetter='".$leftPartnerChainLetter."' OR chainLetter='".$rightPartnerChainLetter."')";	
				$queryStringInner = $queryStringInner."  GROUP BY pdbId".$queryStringLast;
				$resultInner = mysql_query($queryStringInner) or die($queryStringInner);#die('Problem in Sql Query');
				$rowInner = mysql_fetch_array($resultInner);
				if($rowInner!=null) {
					$resultArray[] = array($row['interfaceID'], $rowInner['avgScore']);
				}  
			} 
			mysql_free_result($result);
			closeDatabaseConnection($db);
			return $resultArray;
		}
		case "chain": {
			if(strcmp($avgConservationScoreLower, "") && strcmp($avgConservationScoreUpper, "")) {
				$queryStringLast = " HAVING SUM(".$fieldName.") >= ".$avgConservationScoreLower." AND SUM(".$fieldName.") <= ".$avgConservationScoreUpper;
			}
			else {
				if(strcmp($avgConservationScoreLower, "")) {
					$queryStringLast = " HAVING SUM(".$fieldName.") >= ".$avgConservationScoreLower;
				} else if(strcmp($avgConservationScoreUpper, "")) {
					$queryStringLast = " HAVING SUM(".$fieldName.") <= ".$avgConservationScoreUpper;
				}
			}
			$queryString = "SELECT pdbId, chainLetter, SUM(".$fieldName.") AS avgScore from chains_cumulative_information";
			$queryString = $queryString." GROUP BY pdbId, chainLetter".$queryStringLast;
			break;
		}
		case "protein": {
			if(strcmp($avgConservationScoreLower, "") && strcmp($avgConservationScoreUpper, "")) {
				$queryStringLast = " HAVING SUM(".$fieldName.") / COUNT(pdbId) >= ".$avgConservationScoreLower." AND SUM(".$fieldName.") / COUNT(pdbId) <= ".$avgConservationScoreUpper;
			}
			else {
				if(strcmp($avgConservationScoreLower, "")) {
					$queryStringLast = " HAVING SUM(".$fieldName.") / COUNT(pdbId) >= ".$avgConservationScoreLower;
				} else if(strcmp($avgConservationScoreUpper, "")) {
					$queryStringLast = " HAVING SUM(".$fieldName.") / COUNT(pdbId) <= ".$avgConservationScoreUpper;
				}
			}
			$queryString = "SELECT pdbId, SUM(".$fieldName.") / COUNT(pdbId) AS avgScore from chains_cumulative_information";
			$queryString = $queryString." GROUP BY pdbId".$queryStringLast;;
			break;
		}
	}
	$result = mysql_query($queryString) or die($queryString); #die('Problem in Sql Query');
	closeDatabaseConnection($db);
	return $result;		
}

function fetchStructuresInProvidedPropensityRange($structureType, $residueName, $propensityLower, $propensityUpper) {
	# ni: "hotspotCountContact".$residueName 
	# Ni: "residueCount".$residueName
	# n: "hotspotCountContact"
	# N: "residueCount"
	# ni*N / Ni*n
	$db = connectToHotsprintDatabase();
	$ni = "hotspotCountContact".$residueName; 
	$Ni = "residueCount".$residueName;
	$n = "hotspotCountContact";
	$N = "residueCount";
	if(strcmp($propensityLower, "") && strcmp($propensityUpper, "")) {
		#$queryStringLast = " HAVING (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) >= ".($propensityLower / 100)." AND SUM(".$ni.")*SUM(".$N.") / SUM(".$Ni.")*SUM(".$n.") <= ".($propensityUpper / 100);
		$queryStringLast = " HAVING (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) >= ".($propensityLower)." AND (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) <= ".($propensityUpper);
	}
	else {
		if(strcmp($propensityLower, "")) {
			#$queryStringLast = " HAVING (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) >= ".($propensityLower / 100);
			$queryStringLast = " HAVING (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) >= ".($propensityLower);
		} else if(strcmp($propensityUpper, "")) {
			#$queryStringLast = " HAVING (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) <= ".($propensityUpper / 100);
			$queryStringLast = " HAVING (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) <= ".($propensityUpper);
		}
	}		
	switch ($structureType) {
		case "interface": {
			$queryString = "SELECT interfaceID from interfaces";
			$result = mysql_query($queryString) or die('Problem in Sql Query');
			$resultArray = array();	
			while($row = mysql_fetch_array($result)) {
				$pdbId = substr($row['interfaceID'], 0, 4);
				$leftPartnerChainLetter = $row['interfaceID'][4];
				$rightPartnerChainLetter = $row['interfaceID'][5];
				$queryStringInner = "SELECT SUM(".$ni.") AS a, SUM(".$N.") AS b, SUM(".$Ni.") AS c, SUM(".$n.") AS d, (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) AS propensity from chains_cumulative_information WHERE pdbId='".$pdbId;
				$queryStringInner = $queryStringInner."' AND (chainLetter='".$leftPartnerChainLetter."' OR chainLetter='".$rightPartnerChainLetter."')";	
				$queryStringInner = $queryStringInner."  GROUP BY pdbId".$queryStringLast;	
				$resultInner = mysql_query($queryStringInner) or die('Problem in Sql Query'); #die($queryStringInner);
				$rowInner = mysql_fetch_array($resultInner);
				#if(!strcmp($pdbId, "104l")) {
				#		echo $queryStringInner.'<br/>';	
				#		echo $row['a']." ";
				#		echo $row['b']." ";
				#		echo $row['c']." ";
				#		echo $row['d']." <br/>";
				#}
				if($rowInner!=null) {
					$resultArray[] = array($row['interfaceID'], $rowInner['propensity']);
					#if(!strcmp($pdbId, "104l")) {
					#	echo $queryStringInner.'<br/>';	
					#	echo $row['a']." ";
					#	echo $row['b']." ";
					#	echo $row['c']." ";
					#	echo $row['d']." <br/>";
					#}
				}  
			} 
			mysql_free_result($result);
			closeDatabaseConnection($db);
			return $resultArray;
		}
		case "chain": {
			$queryString = "SELECT pdbId, chainLetter, (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) AS propensity from chains_cumulative_information";
			$queryString = $queryString." GROUP BY pdbId, chainLetter".$queryStringLast;
			break;
		}
		case "protein": {
			$queryString = "SELECT pdbId, (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) AS propensity from chains_cumulative_information";
			$queryString = $queryString." GROUP BY pdbId".$queryStringLast;;
			break;
		}
	}
	$result = mysql_query($queryString) or die($queryString); # or die('Problem in Sql Query');
	closeDatabaseConnection($db);
	return $result;	
}

function fetchInterfacesInProvidedASARange($asaLower, $asaUpper) {
	$db = connectToHotsprintDatabase();
	if(strcmp($asaLower, "") && strcmp($asaUpper, "")) {
		$queryStringLast = " HAVING ((SUM(interfaceASAchainA) + SUM(interfaceASAchainB)) - (SUM(interfaceASAcmplxA) + SUM(interfaceASAcmplxB))) >= ".$asaLower." AND ((SUM(interfaceASAchainA) + SUM(interfaceASAchainB)) - (SUM(interfaceASAcmplxA) + SUM(interfaceASAcmplxB))) <= ".$asaUpper;
	}
	else {
		if(strcmp($asaLower, "")) {
			$queryStringLast = " HAVING ((SUM(interfaceASAchainA) + SUM(interfaceASAchainB)) - (SUM(interfaceASAcmplxA) + SUM(interfaceASAcmplxB))) >= ".$asaLower;
		} else if(strcmp($asaUpper, "")) {
			$queryStringLast = " HAVING ((SUM(interfaceASAchainA) + SUM(interfaceASAchainB)) - (SUM(interfaceASAcmplxA) + SUM(interfaceASAcmplxB))) <= ".$asaUpper;
		}
	}		
	$queryString = "SELECT interfaceID, ((interfaceASAchainA + interfaceASAchainB) - (interfaceASAcmplxA + interfaceASAcmplxB)) AS asaBuried from interfaces GROUP BY interfaceID".$queryStringLast;
	$result = mysql_query($queryString) or die($queryString); #or die('Problem in Sql Query');
	closeDatabaseConnection($db);
	return $result;
}

function fetchStructuresInProvidedHotspotCountAndAverageConservationScoreRange($structureType, $nHotspotLower, $nHotspotUpper, $avgConservationScoreLower, $avgConservationScoreUpper, $option) {
	$db = connectToHotsprintDatabase();
	if($option == "true") {
		$fieldName = "hotspotCountContact";
		$fieldNameScore = "avgScoreScaledContact";
	} else {
		$fieldName = "hotspotCount";
		$fieldNameScore = "avgScoreScaled";
	}
	if(strcmp($nHotspotLower, "") && strcmp($nHotspotUpper, "")) {
		$queryStringLast = " HAVING SUM(".$fieldName.") >= ".$nHotspotLower." AND SUM(".$fieldName.") <= ".$nHotspotUpper;
	}
	else {
		if(strcmp($nHotspotLower, "")) {
			$queryStringLast = " HAVING SUM(".$fieldName.") >= ".$nHotspotLower;
		} else if(strcmp($nHotspotUpper, "")) {
			$queryStringLast = " HAVING SUM(".$fieldName.") <= ".$nHotspotUpper;
		}
	}
	switch ($structureType) {
		case "interface": {
			$queryString = "SELECT interfaceID from interfaces";
			$result = mysql_query($queryString) or die('Problem in Sql Query');
			$resultArray = array();
			if(strcmp($avgConservationScoreLower, "") && strcmp($avgConservationScoreUpper, "")) {
				$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / 2 >= ".$avgConservationScoreLower." AND SUM(".$fieldNameScore.") / 2 <= ".$avgConservationScoreUpper;
			}
			else {
				if(strcmp($avgConservationScoreLower, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / 2 >= ".$avgConservationScoreLower;
				} else if(strcmp($avgConservationScoreUpper, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / 2 <= ".$avgConservationScoreUpper;
				}
			}
			while($row = mysql_fetch_array($result)) {
				$pdbId = substr($row['interfaceID'], 0, 4);
				$leftPartnerChainLetter = $row['interfaceID'][4];
				$rightPartnerChainLetter = $row['interfaceID'][5];			
				$queryStringInner = "SELECT SUM(".$fieldName.") AS count, SUM(".$fieldNameScore.") / 2 AS avgScore from chains_cumulative_information WHERE pdbId='".$pdbId;
				$queryStringInner = $queryStringInner."' AND (chainLetter='".$leftPartnerChainLetter."' OR chainLetter='".$rightPartnerChainLetter."')";	
				$queryStringInner = $queryStringInner."  GROUP BY pdbId".$queryStringLast;
				$resultInner = mysql_query($queryStringInner) or die($queryStringInner);#die('Problem in Sql Query');
				$rowInner = mysql_fetch_array($resultInner);
				if($rowInner!=null) {
					$resultArray[] = array($row['interfaceID'], $rowInner['count'], $rowInner['avgScore']);
				}
			} 
			mysql_free_result($result);
			closeDatabaseConnection($db);
			return $resultArray;
		}
		case "chain": {
			if(strcmp($avgConservationScoreLower, "") && strcmp($avgConservationScoreUpper, "")) {
				$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") >= ".$avgConservationScoreLower." AND SUM(".$fieldNameScore.") <= ".$avgConservationScoreUpper;
			}
			else {
				if(strcmp($avgConservationScoreLower, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") >= ".$avgConservationScoreLower;
				} else if(strcmp($avgConservationScoreUpper, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") <= ".$avgConservationScoreUpper;
				}
			}
			$queryString = "SELECT pdbId, chainLetter, SUM(".$fieldName.") AS count, SUM(".$fieldNameScore.") AS avgScore FROM chains_cumulative_information";
			$queryString = $queryString." GROUP BY pdbId, chainLetter".$queryStringLast;
			break;
		}
		case "protein": {
			if(strcmp($avgConservationScoreLower, "") && strcmp($avgConservationScoreUpper, "")) {
				$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / COUNT(pdbId) >= ".$avgConservationScoreLower." AND SUM(".$fieldNameScore.") / COUNT(pdbId) <= ".$avgConservationScoreUpper;
			}
			else {
				if(strcmp($avgConservationScoreLower, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / COUNT(pdbId) >= ".$avgConservationScoreLower;
				} else if(strcmp($avgConservationScoreUpper, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / COUNT(pdbId) <= ".$avgConservationScoreUpper;
				}
			}
			$queryString = "SELECT pdbId, SUM(".$fieldName.") AS count, SUM(".$fieldNameScore.") / COUNT(pdbId) AS avgScore from chains_cumulative_information";
			$queryString = $queryString." GROUP BY pdbId".$queryStringLast;
			break;
		}
	}
	#$result = mysql_query($queryString) or die('Problem in Sql Query');
	$result = mysql_query($queryString) or die($queryString);
	closeDatabaseConnection($db);
	return $result;	
}

function fetchStructuresInProvidedHotspotCountAndAverageConservationScoreAndPropensityRange($structureType, $nHotspotLower, $nHotspotUpper, $avgConservationScoreLower, $avgConservationScoreUpper, $propensityLower, $propensityUpper, $option) {
	$db = connectToHotsprintDatabase();
	$ni = "hotspotCountContact".$residueName; 
	$Ni = "residueCount".$residueName;
	$n = "hotspotCountContact";
	$N = "residueCount";		
	if($option == "true") {
		$fieldName = "hotspotCountContact";
		$fieldNameScore = "avgScoreScaledContact";
	} else {
		$fieldName = "hotspotCount";
		$fieldNameScore = "avgScoreScaled";
	}
	if(strcmp($nHotspotLower, "") && strcmp($nHotspotUpper, "")) {
		$queryStringLast = " HAVING SUM(".$fieldName.") >= ".$nHotspotLower." AND SUM(".$fieldName.") <= ".$nHotspotUpper;
	}
	else {
		if(strcmp($nHotspotLower, "")) {
			$queryStringLast = " HAVING SUM(".$fieldName.") >= ".$nHotspotLower;
		} else if(strcmp($nHotspotUpper, "")) {
			$queryStringLast = " HAVING SUM(".$fieldName.") <= ".$nHotspotUpper;
		}
	}
	if(strcmp($propensityLower, "") && strcmp($propensityUpper, "")) {
		#$queryStringLast = $queryStringLast." AND (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) >= ".($propensityLower / 100)." AND SUM(".$ni.")*SUM(".$N.") / SUM(".$Ni.")*SUM(".$n.") <= ".($propensityUpper / 100);
		$queryStringLast = $queryStringLast." AND (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) >= ".($propensityLower)." AND SUM(".$ni.")*SUM(".$N.") / SUM(".$Ni.")*SUM(".$n.") <= ".($propensityUpper);
	}
	else {
		if(strcmp($propensityLower, "")) {
			#$queryStringLast = $queryStringLast." AND (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) >= ".($propensityLower / 100);
			$queryStringLast = $queryStringLast." AND (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) >= ".($propensityLower);
		} else if(strcmp($propensityUpper, "")) {
			#$queryStringLast = $queryStringLast." AND (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) <= ".($propensityUpper / 100);
			$queryStringLast = $queryStringLast." AND (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) <= ".($propensityUpper);
		}
	}
	switch ($structureType) {
		case "interface": {
			$queryString = "SELECT interfaceID from interfaces";
			$result = mysql_query($queryString) or die('Problem in Sql Query');
			$resultArray = array();
			if(strcmp($avgConservationScoreLower, "") && strcmp($avgConservationScoreUpper, "")) {
				$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / 2 >= ".$avgConservationScoreLower." AND SUM(".$fieldNameScore.") / 2 <= ".$avgConservationScoreUpper;
			}
			else {
				if(strcmp($avgConservationScoreLower, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / 2 >= ".$avgConservationScoreLower;
				} else if(strcmp($avgConservationScoreUpper, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / 2 <= ".$avgConservationScoreUpper;
				}
			}
			while($row = mysql_fetch_array($result)) {
				$pdbId = substr($row['interfaceID'], 0, 4);
				$leftPartnerChainLetter = $row['interfaceID'][4];
				$rightPartnerChainLetter = $row['interfaceID'][5];			
				$queryStringInner = "SELECT SUM(".$fieldName.") AS count, SUM(".$fieldNameScore.") / 2 AS avgScore, (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) AS propensity from chains_cumulative_information WHERE pdbId='".$pdbId;
				$queryStringInner = $queryStringInner."' AND (chainLetter='".$leftPartnerChainLetter."' OR chainLetter='".$rightPartnerChainLetter."')";	
				$queryStringInner = $queryStringInner."  GROUP BY pdbId".$queryStringLast;
				$resultInner = mysql_query($queryStringInner) or die($queryStringInner);#die('Problem in Sql Query');
				$rowInner = mysql_fetch_array($resultInner);
				if($rowInner!=null) {
					$resultArray[] = array($row['interfaceID'], $rowInner['count'], $rowInner['avgScore'], $rowInner['propensity']);
				}
			} 
			mysql_free_result($result);
			closeDatabaseConnection($db);
			return $resultArray;
		}
		case "chain": {
			if(strcmp($avgConservationScoreLower, "") && strcmp($avgConservationScoreUpper, "")) {
				$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") >= ".$avgConservationScoreLower." AND SUM(".$fieldNameScore.") <= ".$avgConservationScoreUpper;
			}
			else {
				if(strcmp($avgConservationScoreLower, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") >= ".$avgConservationScoreLower;
				} else if(strcmp($avgConservationScoreUpper, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") <= ".$avgConservationScoreUpper;
				}
			}
			$queryString = "SELECT pdbId, chainLetter, SUM(".$fieldName.") AS count, SUM(".$fieldNameScore.") AS avgScore, (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) AS propensity FROM chains_cumulative_information";
			$queryString = $queryString." GROUP BY pdbId, chainLetter".$queryStringLast;
			break;
		}
		case "protein": {
			if(strcmp($avgConservationScoreLower, "") && strcmp($avgConservationScoreUpper, "")) {
				$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / COUNT(pdbId) >= ".$avgConservationScoreLower." AND SUM(".$fieldNameScore.") / COUNT(pdbId) <= ".$avgConservationScoreUpper;
			}
			else {
				if(strcmp($avgConservationScoreLower, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / COUNT(pdbId) >= ".$avgConservationScoreLower;
				} else if(strcmp($avgConservationScoreUpper, "")) {
					$queryStringLast = $queryStringLast." AND SUM(".$fieldNameScore.") / COUNT(pdbId) <= ".$avgConservationScoreUpper;
				}
			}
			$queryString = "SELECT pdbId, SUM(".$fieldName.") AS count, SUM(".$fieldNameScore.") / COUNT(pdbId) AS avgScore, (SUM(".$ni.")*SUM(".$N.")) / (SUM(".$Ni.")*SUM(".$n.")) AS propensity from chains_cumulative_information";
			$queryString = $queryString." GROUP BY pdbId".$queryStringLast;
			break;
		}
	}
	#$result = mysql_query($queryString) or die('Problem in Sql Query');
	$result = mysql_query($queryString) or die($queryString);
	closeDatabaseConnection($db);
	return $result;	
}

////////////////////////////////////////////////////////////////////////////
// Fetching interface information using chains cumulative information table
function old_findNumberOfHotspotsInInterface($pdbId, $leftChain, $rightChain) {
	$db = connectToHotsprintDatabase();
	$queryString = "SELECT SUM(hotspotCountContact) from chains_cumulative_information WHERE pdbId='".$pdbId."'";
	$queryString = $queryString." AND (chainLetter=BINARY'".$leftChain."' OR chainLetter=BINARY'".$rightChain."')";	
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$row = mysql_fetch_row($result);
	mysql_free_result($result);
	closeDatabaseConnection($db);
	return $row[0];
} 

function old_findAverageConservationScoreOfInterface($pdbId, $leftChain, $rightChain) {
	$db = connectToHotsprintDatabase();
	$queryString = "SELECT AVG(residueScore) from chain_residues_conservation_scores WHERE pdbId='".$pdbId."'";
	$queryString = $queryString." AND residueType='C' AND (chainLetter=BINARY'".$leftChain."' OR chainLetter=BINARY'".$rightChain."')";	
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$row = mysql_fetch_row($result);
	mysql_free_result($result);
	closeDatabaseConnection($db);
	return scaleScore($row[0]);
}

function old_findBurriedASAOfInterface($pdbId, $leftChain, $rightChain) {
	$db = connectToHotsprintDatabase();
	$queryString = "SELECT ((interfaceASAchainA + interfaceASAchainB) - (interfaceASAcmplxA + interfaceASAcmplxB)) AS asaBuried from interfaces WHERE interfaceID='".$pdbId.$leftChain.$rightChain."'";
	$result = mysql_query($queryString) or die('Problem in Sql Query');
	$row = mysql_fetch_row($result);
	mysql_free_result($result);
	closeDatabaseConnection($db);
	return $row[0];
}

////////////////////////////////////////////////////////////////////////////
// Obsolute - Display Interface Residues From File Functions

function printContentsOfFile($fileName) {
	if (!file_exists($fileName)) {
		exit("File $fileName does not exist");
	}
	$file = fopen($fileName, "r")  or exit("Failed to open file: $fileName");
	echo '<table class="spaced">';
	echo "<tr> <th> Position </th> <th> Name </th> <th> Score </th> <th> Type </th> <th> Conserved </th> </tr>";

	while(!feof($file))
	{
		#echo fgets($file)."<br />";
		$line = fgets($file);
		list($resPos, $resName, $resScore, $resType, $resConserved) = sscanf($line, "%d %s %f %c %c");
		if ($resConserved == '*') {
			if($resType == 'N') { 
				echo '<tr class="conservedInterface">';
			}
			else if($resType == 'C') {
				echo '<tr class="conservedInterface">';
			} else {
				echo '<tr class="conserved">';
			}
		} else if ($resType == 'N' || $resType == 'C') {
			echo '<tr class="interface">';
		}
		echo "<td> $resPos </td>";
		echo "<td> $resName </td>";
		echo "<td> $resScore </td>";
		echo "<td> $resType </td>";
		echo "<td> $resConserved </td>";
		echo "</tr>";
	}
	echo "</table>";
	fclose($file);
}
*/

?>


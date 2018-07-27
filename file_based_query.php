<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="keywords" content="none " />
    <meta name="description" content="none " />
    <meta name="robots" content="all" />
    <title>HOTSPRINT - Query Page</title>
    <link rel="stylesheet" type="text/css" href="style.css" >
    <link rel="Shortcut Icon" type="image/x-icon" href="favicon.ico" />
</head>

<body id="mainBody">

<div id="contentDiv">
<h1 align="center"> HotSpots of PRotein INTerfaces Database (HOTSPRINT)</h1>
<?php
if (isset($_POST["queryId"])) {  //if (isset($submitted)) {
//form submit code here
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

	$queryId = $_POST["queryId"];
	$showNonInterfaceResidues = $_POST['showNonInterfaceResidues'];
?>
	<div class="queryResult "id="queryDiv" align="center" >
		<form id="queryForm" method="post" action="index.php">
			Enter interface id (e.g. 1axdAB):
			<br/>
			<input class="spaced" type="text" name="queryId" size="8" value="<?php if (isset($queryId)) { echo $queryId; } ?>"/>
			<input class="spaced" type="checkbox" name="showNonInterfaceResidues" id="showNonInterfaceResiduesCheckBox" value="true" checked="checked" title="Check to see non-interface residues in addition to interface residues on the chain" /> 
			<label for="showNonInterfaceResiduesCheckBox"> Show non-interface residues </label>
			<br/>
			<input class="spaced" type="submit" name="submitted" value="Submit Query" />
		</form>
	</div>
<?
	echo '<div>';
	echo "Showing results for interface id <strong>".$queryId.":</strong> <br />";
	if ($showNonInterfaceResidues) {
		echo "(Show non-interface residues) <br /> <br />";
	} else {
		echo "(Do not show non-interface residues) <br /> <br />";
	}
	switch (strlen($queryId)) {
	case 6: {
		$pdbId = substr($queryId, 0, 4);
		$leftPartnerId = $pdbId.$queryId[4];
		$rightPartnerId = $pdbId.$queryId[5];
		echo '<table> <td>';
		printContentsOfFile($leftPartnerId.".cons");
		echo '</td> <td>';
		printContentsOfFile($rightPartnerId.".cons");
		echo '</td> </table>';
		
	} break;
	case 5: {
		printContentsOfFile($queryId.".cons");
	} break;
	case 4: {
		printContentsOfFile($queryId."A.cons");
	} break;
	}
	echo '</div>';
} else { 
?>
<div class="queryMain "id="queryDiv" align="center" >
	<form id="queryForm" method="post" action="index.php">
		Enter interface id (e.g. 1axdAB):
		<br/>
		<input class="spaced" type="text" name="queryId" size="8" value="<?php if (isset($queryId)) { echo $queryId; } ?>"/>
		<input class="spaced" type="checkbox" name="showNonInterfaceResidues" id="showNonInterfaceResiduesCheckBox" value="true" checked="checked" title="Check to see non-interface residues in addition to interface residues on the chain" /> 
		<label for="showNonInterfaceResiduesCheckBox"> Show non-interface residues </label>
		<br/>
		<input class="spaced" type="submit" name="submitted" value="Submit Query" />
	</form>
</div>
<?php } // end of form ?>
</div>

</body>
</html>


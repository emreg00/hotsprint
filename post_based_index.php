<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="keywords" content="none " />
    <meta name="description" content="none " />
    <meta name="robots" content="all" />
    <link rel="Shortcut Icon" type="image/x-icon" href="hotsprint.png"/> <!--favicon.ico" /> -->
    <link rel="stylesheet" type="text/css" href="style.css"/>
	<script type="text/javascript" src="functions.js"></script> 
    <title> HOTSPRINT - <? echo (!isset($_POST['queryId']) ? "Home Page" : "Result Page for ".$_POST['queryId']); ?> </title>
</head>

<?php
include('functions.php');
?>
<body id="mainBody">

<div id="headerDiv" class="spaced">
	<table id='headerTable'> 
		<td>
			<img id="hotsprintLogo" src='hotsprint.png' width="100" alt="Hotsprint Logo" title="Welcome to Hotsprint Database"/>
		</td>
		<td>
			<h1>Hot Spots of PRotein INTerfaces Database (HOTSPRINT)</h1>
		</td>
	</table>
</div>

<br/><br/>

<table id="seperatorTable">
<td width="120px" class="menuCell">
	<div id="menuDiv" class="spaced">
		<br/><br/>
		<a href="index.php"> Home </a>
		<br/><br/>
		<a href="about.php"> About </a>
		<br/><br/>
		<br/><br/>
	</div>
</td>
<td>
	<div id="contentDiv" class="spaced">
	<?php
	if (isset($_POST["queryId"])) {  //if (isset($submitted)) { //form submit code here
			$queryId = $_POST["queryId"];
			$queryType = $_POST["queryType"];
			echo "<h2> Results for $queryId </h2>";
			echo '<div id="resultDiv">';
			switch($queryType) 
			{
			case "displayInterfaceResidues": 
				{
					displayInterfacePartnerResidues($queryId);
					break;
				}
			case "averageConservationScore": 
				{
					//echo "HERE <br/><hr/>";
					$considerOnlyContactingInterfaceResidues = $_POST['considerOnlyContactingInterfaceResidues'];
					displayInterfacePartnerAverageConservationScore($queryId, $considerOnlyContactingInterfaceResidues);
					break;
				}
			}
		echo '</div>';
	} else { 
	?>
		<h2> Home </h2>
		Welcome to Hot Spots of Protein Interfaces (HOTSPRINT) database. 
		You can browse detailed information about residues of protein interfaces. 
		Yet, interface residue information and residue conservation scores available. 
		<br/><br/>
		Use query box provided below to start benefiting the database.
		You may search the database using 6 letter interface id, 5 letter chain id or 4 letter pdb id to search for an interface or its partner.
		<br/>
		<div id="queryDiv" class="queryMain">
			<form id="queryForm" method="post" action="index.php">
				Enter interface, chain or pdb id (e.g. 1axdAB, 1axdA, 1axd) and select query type:
				<br/>
				Query:
				<input name="queryId" class="spaced" type="text" size="8" value="<?php if (isset($queryId)) { echo $queryId; } ?>"/>
				with respect to:
				<select id="queryTypeSelector" name="queryType" class="spaced" onchange='showExtraQueryFields(document.getElementById("queryTypeSelector").value)'/>
					<option value="displayInterfaceResidues" selected="selected"> Residue Conservation Score </option> 
					<option value="averageConservationScore"> Average Conservation Score </option> 
					<option value="numberOfHotspots"> Number of Hotspots </option> 
					<option value="totalConservationScore"> Total Conservation Score </option> 
				</select>
				<span id="extraQueryField"> </span>
				<br/>
				<br/>
				<input class="spaced" type="submit" name="submitted" value="Submit Query" />
			</form>
		</div>
	<?php } // end of form ?>

	</div>
</td>
</table>

</body>
</html>


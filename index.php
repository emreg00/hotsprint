<!--------------------------------------------
- Hotsprint Server Home Page 
- eg - 14.08.2006
---------------------------------------------!-->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
 	<title> HotSprint - Home Page </title>
	<?php include('header.php'); ?>
</head>

<!--<body id="mainBody" onload="showExtraQueryFields(document.getElementById('queryTypeSelector').value);">-->
<body id="mainBody">
<?php include('top.php'); ?>
<?php include('menu_top.php'); ?>
	<div id="contentDiv" class="spacedText">
		<h2> HotSprint Home </h2>
		Welcome to Computational Hot Spots of Protein Interfaces (HotSprint) database. 
		Hotsprint gives information about the evolutionary history of the residues on the interface and represents which residues are highly conserved on the interface. 
		In this way, functionally and structurally important residues on the interface can be distinguished. <a href="about.php#more" class="informationLink" title="More information about Hotsprint and its usage"> &nbsp; &gt;&gt; More </a>
		<br/><br/>
		For downloading whole data in HotSprint database refer instructions for <a href="about.php#download" class="informationLink" title="Retrieve HotSprint Database"> retrieving HotSprint database. </a>
		<br/> <br/> 
		<!-- <div id="queryDiv" class="queryMain"> -->
		<?php 
			if(isset($_COOKIE["optionType"])) { 
				$type = $_COOKIE["optionType"];
				$cutoffValue = $_COOKIE["cutoff"];
			} else {
				$type = "1";
				$cutoffValue = "6.2";
			}
			include('query.php'); 
		?>
		<!-- </div> -->
	</div>
<?php include('menu_bottom.php'); ?>
<?php include('bottom.php'); ?>
</body>
</html>


<html>
<?php 
	$tempDir = "tmp/";
	$queryId = $_GET['queryId'];
	echo "<title> Interactive Jmol visualization for $queryId </title>";
?>
<head>
    <!--<script type="text/javascript" src="./jmol/Jmol.js"></script>-->
</head>
<body>
<form>
<?php
/*
	echo '<script type="text/javascript">';
	echo '  jmolInitialize("./jmol/, window.location.protocol==\"file:JmolAppletSigned.jar\"");';
	echo '  jmolApplet(500, "load ./jmol/caffeine.xyz");';
	#echo '  jmolApplet(600, "background white; load http://www.rcsb.org/pdb/files/11ba.pdb");';
	#echo '  jmolApplet(500, "load pdb/'.substr($queryId,0,4).'.pdb");';
	#echo '  jmolApplet(500, "load jmol/1yp2.pdb");';
	#echo '  jmolApplet(500, "script '.$tempDir.$queryId.'.scr");';
	#echo '  jmolApplet(500, "script '.$queryId.'.spt");';
	#echo '  jmolApplet(600, "script ./jmol/'.$queryId.'.scr");';
	#echo '  jmolApplet(600, "script ./jmol/1yp2AB.scr");';
	#echo '  jmolApplet(600, "load ./'.$tempDir.$queryId.'.scr");';
	#echo '  jmolApplet(600, "script ./jmol/den.scr");';
	echo '</script>';
*/
?>

<applet codebase="./jmol/" name="jmol" code="JmolApplet" archive="JmolAppletSigned.jar"
width="500" height="500">
<param name="script" value="<?php include("functions.php"); printContentsOfFile("$tempDir$queryId.spt"); ?>">
<!--<param name="load" value="./jmol/aromatic.mol">-->
<!--<param name="load" value="http://www.rcsb.org/pdb/files/11ba.pdb">-->
<!--<param name="script" value="<?php //echo $tempDir.$queryId; ?>.spt">-->
<!--<param name="script" value="
zap;
load http://www.rcsb.org/pdb/files/1yp2.pdb;
background white;
">-->
<!--<param name="load" value="http://www.rcsb.org/pdb/files/<?php //echo substr($queryId, 0, 4); ?>.pdb">-->
<param name="progressbar" value="true">
<param name="bgcolor" value="#FFFFFF">
<param name="style" value="shaded">
<param name="label" value="symbol">
<param name="wireframeRotation" value="true">
<param name="perspectiveDepth" value="false">
</applet>
</form>
</body>
<html>

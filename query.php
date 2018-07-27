<!--------------------------------------------
- Hotsprint Server Query Form
- 
- for tabbed queryboxes refer: http://jon.hedley.net/html-tabbed-dialog-widget
- for ajax refer w3schools
- eg - 13.09.2006
---------------------------------------------!-->
<br/>
<span class="queryTitle"> Search interfaces using pdbId </span>
<div id="queryDiv0" class="queryMain">
<form id="queryForm0" method="GET" action="result.php" onsubmit='return validate0(document.getElementById("pdbIdTextBox").value)'>
				Enter protein's PDB identifier (e.g. 1yp2) to see its associated interface(s):
				<hr/>
				<div id="optionDiv" class="optionBox">
				Predict <b> hot spots </b> based on 
				<select id="optionSelect" name="optionTypeSelector" class="spacedText"> <!--onchange='updateCutoff(document.getElementById("optionSelect").value)'>-->
					<option value="1" <?php if($type==1) echo 'selected="seleceted"' ?> > conservation score (score) </option>
					<option value="2" <?php if($type==2) echo 'selected="seleceted"' ?> > propensity scaled conservation score (pScore) </option> 
					<option value="3" <?php if($type==3) echo 'selected="seleceted"' ?> > pScore, complex ASA and difference ASA (pScore+ASA) </option>
				</select>
				<br/> 
				&nbsp;&nbsp; where score/pScore cutoff 
				&gt;= 
				<input id="cutoffTextBox" name="cutoffValue" class="spacedText" type="text" size="4" value="<?php echo $cutoffValue; ?>"/>
				</div>
				<br/>
				pdbId:
				<input id="pdbIdTextBox" name="pdbId" class="spacedText" type="text" size="8" value="<?php if (isset($pdbId)) { echo $pdbId; } ?>"/> 
				<input class="spacedText" type="submit" name="submitButton0" value="Fetch Associated Interface(s)"/>
				<br/>
				<input class="spaced" type="checkbox" name="displayOnlyInterfaceResidues" id="displayOnlyInterfaceResiduesCheckBox" value="true" title="Check to display only interface residues" checked/> <label for="displayOnlyInterfaceResiduesCheckBox"> Display only contacting and nearby interface residues </label>
				<span id="messageField0"> </span>
</form>
</div>
<br/><br/>
<span class="queryTitle"> Advanced Search </span>
<div id="queryDiv2" class="queryMain">
<form id="queryForm2" method="GET" action="result.php" onsubmit='return validate2(document.getElementById("structureTypeSelect").value, document.getElementById("nHotspotLowerTextBox").value, 
	 document.getElementById("nHotspotUpperTextBox").value, document.getElementById("avgConservationScoreLowerTextBox").value,
	 document.getElementById("avgConservationScoreUpperTextBox").value,	document)'>
	 <!-- document.getElementById("propensityLowerTextBox").value, 
	 document.getElementById("propensityUpperTextBox").value, document.getElementById("asaLowerTextBox").value,
	 document.getElementById("asaUpperTextBox").value -->
				Enter parameters for the interfaces to be matched.
				<hr/>
				<div id="optionDiv2" class="optionBox">
				Predict <b> hot spots </b> based on 
				<select id="optionSelect2" name="optionTypeSelector2" class="spacedText" onchange='updateCutoff(document.getElementById("optionSelect2").value)'>
					<option value="1" <?php if($type==1) {	echo 'selected="seleceted"'; $t = 6; } ?> > conservation score (score) </option>  
					<option value="2" <?php if($type==2) {	echo 'selected="seleceted"'; $t = 6.2; } ?> > propensity scaled conservation score (pScore) </option>
					<option value="3" <?php if($type==3) {	echo 'selected="seleceted"'; $t = 6.2; } ?> > pScore, complex ASA and difference ASA (pScore+ASA) </option>
				</select> 
				<br/>
				&nbsp;&nbsp; where score/pScore cutoff
				&gt;= 
				<span id="extraQueryField2"> <?php echo "$t"; ?> </span>
				<!-- <input id="cutoffTextBox" name="cutoffValue" class="spacedText" type="text" size="4" value="<?php echo $cutoffValue; ?>"/> -->
				</div>
				<br/>
				Find interfaces 
				<!-- 
				<select id="structureTypeSelect" name="structureType" class="spacedText" onchange='showExtraQueryFields2(document.getElementById("structureTypeSelect").value)'>
					<option value="interface" selected="seleceted"> interfaces </option> 
					<option value="chain"> chains </option>  
					<option value="protein"> proteins </option>
				</select>
				-->
				having (all conditions are <font class="emphasisedText">AND</font>ed ):
				<br/><br/>
				0 &lt;= <input id="nHotspotLowerTextBox" name="nHotspotLower" class="spacedText" type="text" size="2"/>
				&lt;= &nbsp;&nbsp; number of <a href="about.php#more" class="informationLink" title="Click for definition of this term"> hotspot residues </a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &lt;=
				<input id="nHotspotUpperTextBox" name="nHotspotUpper" class="spacedText" type="text" size="2"/> &lt;= 236
				<br/> 
				0 &lt;= <input id="nConservedLowerTextBox" name="nConservedLower" class="spacedText" type="text" size="2"/>
				&lt;= &nbsp;&nbsp; number of <a href="about.php#more" class="informationLink" title="Click for definition of this term"> conserved residues </a> &nbsp;&nbsp; &lt;=
				<input id="nConservedUpperTextBox" name="nConservedUpper" class="spacedText" type="text" size="2"/> &lt;= 192
				<br/> 
				1 &lt;= <input id="avgConservationScoreLowerTextBox" name="avgConservationScoreLower" class="spacedText" type="text" size="2"/>
				&lt;= &nbsp;&nbsp; <a href="about.php#more" class="informationLink" title="Click for definition of this term"> average conservation score </a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &lt;=
				<input id="avgConservationScoreUpperTextBox" name="avgConservationScoreUpper" class="spacedText" type="text" size="2"/>
				<!-- <i> (Over 9) </i> -->
				&lt;= 9
				<br/>
				0 &lt;= <input id="propensityLowerTextBox" name="propensityLower" class="spacedText" type="text" size="2"/>
				   &lt;= &nbsp;&nbsp; <a href="about.php#more" class="informationLink" title="Click for definition of this term"> conserved residue propensity </a> &nbsp;&nbsp;&nbsp; &lt;=
				 	<input id="propensityUpperTextBox" name="propensityUpper" class="spacedText" type="text" size="2"/>
				   for <select id="residueNameSelect" name="residueName" class="spacedText">
				 	<option value="ALA">ALA </option>
				 	<option value="ARG">ARG </option>
				 	<option value="ASN">ASN </option>
				 	<option value="ASP">ASP </option>
				 	<option value="CYS">CYS </option>
				 	<option value="GLN">GLN </option>
				 	<option value="GLU">GLU </option>
				 	<option value="GLY">GLY </option>
				 	<option value="HIS">HIS </option>
				 	<option value="ILE">ILE </option>
				 	<option value="LEU">LEU </option>
				 	<option value="LYS">LYS </option>
				 	<option value="MET">MET </option>
				 	<option value="PHE">PHE </option>
				 	<option value="PRO">PRO </option>
				 	<option value="SER">SER </option>
				 	<option value="THR">THR </option>
				 	<option value="TRP">TRP </option>
				 	<option value="TYR">TYR </option>
				 	<option value="VAL">VAL </option></select>
				&lt;= 46.66
				  <br/>
				<!--   
				<span id="extraQueryField2">
					<br/> ------------------------------------- or ------------------------------------- <br/>
				    <br/> ------------------------------------- or ------------------------------------- <br/>
				</span>    -->
				0 &lt;= <input id="asaLowerTextBox" name="asaLower" class="spacedText" type="text" size="2"/>
				&lt;= &nbsp;&nbsp; <a href="about.php#more" class="informationLink" title="Click for definition of this term"> buried accessible surface area </a> &nbsp;&nbsp; &lt;= <input id="asaUpperTextBox" name="asaUpper" class="spacedText" type="text" size="2"/> 
				&lt;= 19083 (A°²)
				<span id="messageField2"> </span>
				<br/>
				<br/>
				<input class="spacedText" type="submit" name="submitButton2" value="Submit Query"/>
</form>
</div>
<br/><br/>
<span class="queryTitle"> Search for information of a structure </span>
<div id="queryDiv" class="queryMain">
<form id="queryForm" method="GET" action="result.php" onsubmit='return validate(document.getElementById("queryIdTextBox").value)'>
				Enter chain or PDB identifier (e.g. 1yp2A, 1yp2):
				<hr/><br/>
				Query:
				<input id="queryIdTextBox" name="queryId" class="spacedText" type="text" size="8" value="<?php if (isset($queryId)) { echo $queryId; } ?>"/>
				<input class="spacedText" type="submit" name="submitButton" value="Submit Query"/>
				<!-- <span id="extraQueryField"> </span> -->
				<span id="messageField"> </span>
</form>
</div>

<!--------------------------------------------
- Hotsprint Option From
-
-
- eg - 29.12.2006
---------------------------------------------!-->
<i>
		<?php
			switch($type) 
			{
			case "1": 
				{
				 	echo "Hot spots are going to be taken as the residues that bears a <b> propensity scaled conservation score higher than '$cutoff'</b>.";
					break;
				}
			case "2": 
				{
				 	echo "Hot spots are going to be taken as the residues that bears a <b> conservation score higher than '$cutoff'</b>.";
					break;
				}
			case "3": 
				{
				 	echo "Hot spots are going to be taken as the residues that bears a <b> conservation score higher than '$cutoff'</b> and an <b> ASA change higher then '42 A^2' </b>.";
					break;
				}
			}
		?>
		You may change current criterion for a residue to be considered as hot spot and/or modify conservation score threshold from the option pane below:
</i>
<div id="optionDiv" class="optionBox">
<form id="optionForm" method="POST" action="preferences.php">
				Modify hot spot definiton to be used: 
				<select id="optionSelect" name="optionTypeSelector" class="spacedText" onchange='updateCutoff(document.getElementById("optionSelect").value)'>
					<option value="1" selected="seleceted"> Propensity Scaled Conservation Score </option> 
					<option value="2"> Conservation Score </option>  
					<option value="3"> Conservation Score and ASA change </option>
				</select>
				with conservation score cutoff: <input id="cutoffTextBox" name="cutoffValue" class="spacedText" type="text" size="4" value="6.2"/>
				<br/> <center>
				<input class="spacedText" type="submit" name="submitOption" value="Adopt New Convention"/>
				</center>
</form>
</div>
<br/>
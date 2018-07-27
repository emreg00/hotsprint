<!--------------------------------------------
- Hotsprint Server About Page 
- eg - 13.09.2006
---------------------------------------------!-->

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
 	<title> HotSprint - About Page </title>
	<?php include('header.php'); ?>
</head>

<body id="mainBody">
<?php include('top.php'); ?>
<?php include('menu_top.php'); ?>

	<div id="contentDiv" class="spacedText">
		<h2> About </h2>
		Welcome to Computational Hot Spots of Protein Interfaces (HotSprint) database. 
		Hotsprint gives information about the evolutionary history of the residues on the interface and represents which residues are highly conserved on the interface. 
		In this way, functionally and structurally important residues on the interface can be distinguished. 
		<br/> <br/>
		<a name="more"></a>
		<!-- <i> ! MORE INFORMATION ABOUT METHODS USED AND USAGE WILL BE AVAILABLE SOON ! </i>
		<br/> -->

		<h4> Definitions </h4>
		<dl>
		<dt> Interface Residue: </dt> 
		<dd> Conventionally, protein-protein interface is the set of residues that are connected to each other through non covalent interactions. In HotSprint, two residues on opposite chains of a protein complex are considered as interface residues if the distance between any atom of the first residue and any atom of the other residue is below sum of van der Waals radii of two atoms plus 0.5A°. 
		</dd> <br/>

		<dt> Conserved Residue: </dt>
		<dd> Some amino acids mutate more infrequently then others. Such amino acids are called conserved residues. In this particular work, a residue is considered as evolutionarily conserved (in sequence) if its conservation score (calculated by Rate4Site) is greater or equal to 7. 
		</dd> <br/>

		<dt> Average Conservation Score: </dt>
		<dd> Average conservation score of an interface is the sum of conservation scores of interface residues divided by the number of interface residues.
		</dd> <br/>

		<dt> Hot Spot Residue: </dt>
		<dd> Certain residues on the surfaces of the proteins are critical for binding and structure. Experimentally these residues are found by alanine scanning mutagenesis. In the context of this study (database), hot spot refers to computationally predicted hot spot residues. How the prediction is done explained below in hot spot prediction models section.
		</dd> <br/>

		<dt> Buried Accessible Surface Area (ASA): </dt>
		<dd> Buried ASA is the surface area of a protein complex that becomes inaccessible to solvent upon complexation. It is found by differentiating sum of chain ASAs from sum of complex ASA. NACCESS is employed to calculate ASA of the proteins' individual chains and protein complexes.
		</dd> <br/>

		<dt> Conserved Residue Propensity: </dt>
		<dd> Conserved residue propensity is enrichment of a certain residue on the interface in terms of conservation. For a residue of type i, propensity is calculated by multiplying the ratio of conserved interface residues of type i to number of residues of type i in the chains with the ratio of the number of residues in the chains to the number of conserved residues in the chains.
		</dd> 
		</dl>

		<h4> Database Contents </h4>
		Hotsprint contains overall properties of the interface such as number of computational hot spots on the interface, number of conserved residues on the interface, average conservation score of interface residues and buried ASA of the interface.
		Additionly, residues of the interface along with their position, name, conservation score, ASA in monomer, ASA in complex, type (contacting interface residue, neighboring interface residue or none) and whether the residue is computational hot spot or not information are presented.
		<br/>

		<h4> Hot Spot Prediction Models </h4>
		There are three different hot spot prediction models in HotSprint. 
		<ol> 
		<li> <font class="definitionTitle"> Prediction based on only sequence conservation (score): </font> <br/>
		The first model predicts hot spots based on the residues' 
		evolutionary conservation in sequence. A residue is tagged as hot spot if its conservation score (score) is higher than specified
		threshold (default threshold is 6). 
		</li>
		<br/>
		<li> <font class="definitionTitle"> Prediction based on propensity scaled sequence conservation (pScore): </font> <br/>
		In the second model a residue is tagged as hot spot if its conserved residue scaled score (pScore) 
		is higher than the specified threshold (default value is 6.2). 
		</li>
		<br/>
		<li> <font class="definitionTitle"> Prediction based on propensity scaled sequence conservation and solvent accessible surface area (pScore+ASA): </font> <br/>
		The third model flags a residue as hot spot if the residue has a 
		propensity scaled conservation score higher than 6.2 and either its ASA change upon complexation is higher than 49A°² or its ASA in 
		complex is lower than 12A°².
		</li>
		</ol>

		<h4> Web Interface Usage </h4>
		Use query boxes provided below to start benefiting the database;
		<ul> <li>
		In the first query box on the main page, you may search through interface dataset to fetch associated interfaces of a given protein using its 4 letter pdb identifier.
		</li> <li>
		Below the first query box, resides the second query box which allows you search for structures with given criteria.
		</li> <li>
		The query box at the bottom provides access to residue information of individual chains that interfaces come from.
		</li> </ul>
		<br/>
		
		<a name="download"></a>
		<h4> Retrieving HotSprint Database </h4>
		Data in HotSprint Database is provided as a single database dump file in SQL format. Download compressed version of HotSprint Database dump file <a href="hotsprint_dumped.sql.gz"> hotsprint_dumped.sql.gz (697M) </a>. First, create a database named HotSprint in your SQL server, then decompress the file and finally create and fill tables by executing the SQL querries in the decompressed file (e.g. For mysql users: mysql -u userName -p databaseName &lt; hotsprint_dumped.sql).
		<br/> <br/>
		You may also be intereseted in downloading non-redundant HotSprint database where interfaces with homologs to each other at least 40% based on BLAST are removed. Download compressed version of non-redundant HotSprint Database dump file <a href="hotsprint_NR_dumped.sql.tar.gz"> hotsprint_NR_dumped.sql.tar.gz (341M) </a>. 
	</div>
<?php include('menu_bottom.php'); ?>
<?php include('bottom.php'); ?>	
</body>
</html>

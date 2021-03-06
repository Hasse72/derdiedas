<?php
	//Initialize session
	session_start();
	$_SESSION["previousNoun"] = $_SESSION["noun"];
	$_SESSION["previousArticle"] = $_SESSION["article"];
	$_SESSION["previousAnswer"] = $_SESSION["answer"];
	$_SESSION["previousGuess"] = $_SESSION["guess"];
	
	if($_SESSION["points"]=="") {$_SESSION["points"]=0;}
	if($_SESSION["round"]=="") {$_SESSION["round"]=1;}
	
	//Query
	$sparqlQuery = '
	SELECT ?lemma (SAMPLE(?gender) AS ?gender) WITH {
	  SELECT DISTINCT ?lemma WHERE {
	    VALUES ?gender { wd:Q499327 wd:Q1775415 wd:Q1775461 }
	    ?lexeme dct:language wd:Q188;
	            wikibase:lexicalCategory wd:Q1084;
	            wdt:P5185 ?gender;
	            wikibase:lemma ?lemma.
	  }
	  ORDER BY CONCAT(MD5(?lemma), STR(NOW()))
	} AS %randomLemmas WHERE {
	  INCLUDE %randomLemmas.
	  ?lexeme wikibase:lemma ?lemma;
	          wdt:P5185 ?gender.
	}
	GROUP BY ?lemma
	HAVING(COUNT(?gender) = 1)';

	//get result of the query in json
	$result = file_get_contents('https://query.wikidata.org/bigdata/namespace/wdq/sparql?format=json&query='.urlencode($sparqlQuery));

	
	//parse the query
	try { $items = json_decode($result); }
		catch ( Exception $e ) {
		echo '<p>Problem, no results available.</p>';
	}
		
		if (count($items->results->bindings) == 0) {
			echo '<p>No results.</p>';
	    }
	    else {
    		//select a random lexeme among the results of the query
			$random=rand(0, 200);
			$line=0;
			foreach ($items->results->bindings as $item) {
				$line++;
				if ($line==$random)
				{
					break;
				}
			}
		}
	//convert Qitem of gender into the article (simple version)
	switch($item->gender->value) {
		case "http://www.wikidata.org/entity/Q499327": {$_SESSION["article"]="der";break;}
		case "http://www.wikidata.org/entity/Q1775415": {$_SESSION["article"]="die";break;}
		case "http://www.wikidata.org/entity/Q1775461": {$_SESSION["article"]="das";break;}
		}
		
	$_SESSION["noun"]=$item->lemma->value;

	// Check the answer and give feedback
	if (isset($_POST['article'])) 
		{ 
		$_SESSION["answer"]=$_POST['article'];
		
		
		//First round
		if($_SESSION["answer"]=='') {
			$_SESSION["previousGuess"]='';
			$_SESSION["image"]='';
		}
		
		//I don't know
		if($_SESSION["answer"]=='idk') {
			 $_SESSION["previousGuess"]='(0)';
			$_SESSION["image"]='zero';
			 
		}
		
		//Good guess
		elseif($_SESSION["answer"]==$_SESSION["previousArticle"]) {
			$_SESSION["points"]++;
			 $_SESSION["previousGuess"]='(+1)';
			$_SESSION["image"]='plus';
			}
			
		//Wrong guess
			else {
				$_SESSION["points"]=$_SESSION["points"]-1;
			 $_SESSION["previousGuess"]='(-1)';
			$_SESSION["image"]='minus';
				}
				
		//Increment round, check if it's the end
		$_SESSION["round"]++;
		if($_SESSION["round"]==11) { $phase='end';}
		else {$phase='game';}
		
		//Add the previous word, its article and the points into the list
		$_SESSION["list"]='<p class="list '.$_SESSION["image"].'">'.' '.$_SESSION["previousArticle"].' '.$_SESSION["previousNoun"].'</p>'.$_SESSION["list"];
		}
	?>
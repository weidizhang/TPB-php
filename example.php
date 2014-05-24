<?php
require_once __DIR__ . "/vendor/autoload.php";
$tpbObj = new \TPB\API();
$searchResults = $tpbObj->searchByTitle("The Big Bang Theory");
if (isset($searchResults[0])) {
	$topResult = $searchResults[0];
	echo "Top Result \n-------------------\n";
	
	foreach ($topResult as $infoType => $infoValue) {
		echo $infoType . ": " . $infoValue . " \n";
	}
}
else {
	echo "No results.";
}
?>
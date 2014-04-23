<?php
require "tpb.class.php";
$tpb = new \TPB\API();
/*
$info = $tpb->getTorrentById(8291015);
echo "Title: " . $info->Title . "\n";
echo "Seeders/Leechers: " . $info->Seeders . "/" . $info->Leechers . "\n";
*/
$res = $tpb->searchByTitle("steins;gate");
print_r($res);
?>
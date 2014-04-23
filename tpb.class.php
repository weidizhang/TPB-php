<?php
/*
 * @author Weidi Zhang <weidiz999@yahoo.com>
 * @license MIT License
 *
 * Copyright (c) 2014 Weidi Zhang
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace TPB;

class API
{

	public function getTorrentById($id) {
		$getTorrent = $this->getPage("https://thepiratebay.se/details.php?id=" . (int) $id);
		if (strpos($getTorrent, "<h2>Not Found (aka 404)</h2>") === false) {
			preg_match('/<div id="title">(.*?)<\/div>/si', $getTorrent, $matches);
			$title = trim($matches[1]);
			
			preg_match("/<dt>Size:<\/dt>\n		<dd>(.*?)<\/dd>/si", $getTorrent, $matches);
			$size = str_replace("&nbsp;", " ", $matches[1]);
			
			preg_match("/<dt>Seeders:<\/dt>\n		<dd>(\d+)<\/dd>/si", $getTorrent, $matches);
			$seeders = $matches[1];
			
			preg_match("/<dt>Leechers:<\/dt>\n		<dd>(\d+)<\/dd>/si", $getTorrent, $matches);
			$leechers = $matches[1];
			
			preg_match("/<dt>Type:<\/dt>\n		<dd><a href=\"\/browse\/(\d+)\" title=\"More from this category\">(.*?)<\/a><\/dd>/si", $getTorrent, $matches);
			$categoryID = $matches[1];
			$category = htmlspecialchars_decode($matches[2]);			
			
			preg_match('/<a style=\'background-image: url\("\/static\/img\/icons\/icon-magnet.gif"\);\' href="(.*?)" title="Get this torrent">/si', $getTorrent, $matches);
			$magnet = $matches[1];
			
			preg_match("/<dt>Info Hash:<\/dt><dd>&nbsp;<\/dd>(.*?)<\/dl>/si", $getTorrent, $matches);
			$infohash = trim($matches[1]);
			
			preg_match("/<div class=\"nfo\">\n<pre>(.*?)<\/pre>/si", $getTorrent, $matches);
			$description = "n/a";
			if (isset($matches[1])) {
				$description = strip_tags($matches[1]);
			}
			
			preg_match("/}; toggleFilelist\(\); return false;\">(\d+)<\/a><\/dd>/si", $getTorrent, $matches);
			$filecount = (int) $matches[1];
			
			$getFiles = $this->getPage("https://thepiratebay.se/ajax_details_filelist.php?id=" . (int) $id);
			
			preg_match_all('/<tr><td align="left">(.*?)<\/td><td align="right">(.*?)<\/tr>/si', $getFiles, $matches);
			$files = array();
			foreach ($matches[1] as $matchNum => $match) {
				$files[$match] = str_replace("&nbsp;", " ", $matches[2][$matchNum]);
			}
			ksort($files);
			
			return (object) array(
				"Title" => $title,
				"Size" => $size,
				"Seeders" => $seeders,
				"Leechers" => $leechers,
				"CategoryName" => $category,
				"CategoryID" => $categoryID,
				"Magnet" => $magnet,
				"InfoHash" => $infohash,
				"Description" => $description,
				"FileCount" => $filecount,
				"Files" => $files
			);
		}
		else {
			return (object) array("Error" => "Torrent not found");
		}
	}

	public function searchByTitle($keyword, $page = 1) {
		$page--;
		$getResults = $this->getPage("https://thepiratebay.se/search/" . urlencode($keyword) . "/" . $page . "/7/0/");
		
		$results = array();
		
		//preg_match_all('/<div class="detName">(.*?)<\/td>/si', $getResults, $matches);
		preg_match_all('/<td class="vertTh">(.*?)<\/tr>/si', $getResults, $matches);
		foreach ($matches[1] as $result) {
			preg_match('/<a href="\/torrent\/(\d+)\//si', $result, $rMatches);
			$torrentID = $rMatches[1];
			$torrentLink = "https://thepiratebay.se/torrent/" . $torrentID . "/";
			
			preg_match('/class="detLink" title="Details for (.*?)">/si', $result, $rMatches);
			$title = $rMatches[1];
			
			preg_match_all('/<a href="\/browse\/(\d+)" title="More from this category">(.*?)<\/a>/si', $result, $rMatches);
			$category = $rMatches[2][0] . " > " . $rMatches[2][1];
			$categoryID = $rMatches[1][1];
			
			preg_match('/<a href="magnet:(.*?)" title="Download this torrent using magnet">/si', $result, $rMatches);
			$magnet = "magnet:" . $rMatches[1];
			
			preg_match('/<font class="detDesc">(.*?)<\/font>/si', $result, $rMatches);
			$info = explode(", ", $rMatches[1]);
			$uploaded = strtr($info[0], array("Uploaded " => "", "&nbsp;" => "-"));
			$uplCheck = explode("-", $uploaded);
			if (strpos($uplCheck[2], ":") !== false) {
				$uploaded = $uplCheck[0] . "-" . $uplCheck[1] . "-" . date("Y");
			}
			$size = strtr($info[1], array("Size " => "", "&nbsp;" => " "));
			$uploadedBy = strip_tags(str_replace("ULed by ", "", $info[2]));
			
			preg_match_all('/<td align="right">(\d+)<\/td>/si', $result, $rMatches);
			$seeders = $rMatches[1][0];
			$leechers = $rMatches[1][1];
			
			$results[] = (object) array(
				"Title" => $title, 
				"Category" => $category,
				"CategoryID" => $categoryID,
				"TorrentID" => $torrentID,
				"TorrentLink" => $torrentLink,
				"Magnet" => $magnet,
				"Uploaded" => $uploaded,
				"UploadedBy" => $uploadedBy,
				"Size" => $size,
				"Seeders" => $seeders,
				"Leechers" => $leechers
			);
		}
		
		return $results;
	}
	
	private function getPage($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36');
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}
?>
<?php
require_once('FBLogin.php');
$link = mysqli_connect('localhost', 'root', 'password', 'chautauqua_sheriff.us');
if (!$link) { die('badness in the connection of the computer datin base'); }
$p = new FBLogin();

/**
 * Data resides at http[s]://findour.info/[a-z].aspx
 * We just grab it by letter and parse it into an array
 *
 * The information they provide is the folowing:
 * Picture, Name, Last Known Address, Age, Race/Sex, Height, Weight, Eye Color
 * "Wanted by", Charge, Judge
 */

function getMostWanted($alpha)
{
	$items = array();
	$base_url = 'http://findour.info';
	$alpha = strtolower($alpha);
	
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => $base_url . '/' . $alpha . '.aspx', // https is Digicert *.chautauquacounty.com, valid 2/19/2016->2/20/2019
		CURLOPT_HEADER => false,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false, // when ssl for this host becomes legit, we true this
		CURLOPT_CONNECTTIMEOUT => 5, 
		CURLOPT_MAXREDIRS => 30,
		CURLOPT_SSL_VERIFYHOST => 0, // would rather this be 2, but again, bad ssl
		CURLOPT_USERAGENT => 'MostWanted-14701 Spider (https://github.com/mjheick/mostwanted-14701)', // so it knows it's me in the logs

	));
	$data = curl_exec($ch);

	// https://regexr.com for awesome regex matching
	preg_match_all('/<tr[\s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?<>]*?<\/tr>/', $data, $matches);
	foreach($matches[0] as $match)
	{
		$item = array();
		/**
		 * Each item is going to be in 2 columns
		 * First is the image. Can either be URL (<img) or not (<span)
		 * Second is info, <br>-separated
		 */

		// The image
		if (strpos(strtolower($match), '<span>no image available</span>') === false)
		{
			if (preg_match('/<img src="([\w\d.\/]+)"\s\/>/', $match, $img_url_data) === 1)
			{
				$item['image'] = $base_url . '/' . $img_url_data[1];
			}
			else
			{
				$item['image'] = '';
			}
		}
		else
		{
			$item['image'] = '';
		}

		// the data, with capture regex taken from above preg_match_all
		if (preg_match('/<span\sid="[\w\d_]+"\sstyle="[\w\d\-:;]+">([\s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?<>]*?)<\/span>/', $match, $data) === 1)
		{
			// we can be quite specific in this, as they use <br>'s to separate out their data.
			// our "data"-catchall regex for information presented: \s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?
			if (preg_match('/([\s\w\d\r\n\'\[\]\/\-<>|!@#$%^&*()_+={};:",.?]+)<br><br>[\w\s:]+<br>([\s\w\d\r\n\'\[\]\/\-<>|!@#$%^&*()_+={};:",.?]+)<br>([\s\w\d\r\n\'\[\]\/\-<>|!@#$%^&*()_+={};:",.?]+)<br>([\s\w\d\r\n\'\[\]\/\-<>|!@#$%^&*()_+={};:",.?]+)<br><br>Wanted By:([\s\w\d\r\n\'\[\]\/\-<>|!@#$%^&*()_+={};:",.?]+)<br>Charge:([\s\w\d\r\n\'\[\]\/\-<>|!@#$%^&*()_+={};:",.?]+)<br>([\s\w\d\r\n\'\[\]\/\-<>|!@#$%^&*()_+={};:",.?]+)/', $data[1], $bioinfo) === 1)
			{
				$item['source-url'] = $base_url . '/' . $alpha . '.aspx';
				$item['name'] = trim($bioinfo[1]);
				$item['address'] = trim($bioinfo[2]);
				$item['vitals1'] = trim($bioinfo[3]);
				$item['vitals2'] = trim($bioinfo[4]);
				$item['wanted-by'] = trim($bioinfo[5]);
				$item['charge'] = trim($bioinfo[6]);
				$item['judge'] = trim($bioinfo[7]);
			}
			$items[] = $item;
		}
	}
	return $items;
}


// Get most wanted, A to Z
$most_wanted_all = array();
for ($alpha = ord('a'); $alpha <= ord('z'); $alpha++)
{
	$data = getMostWanted(chr($alpha));
	$most_wanted_all = array_merge($most_wanted_all, $data);
}

// load it up in the database, yo
foreach ($most_wanted_all as $item)
{
	// so we can group it all together
	$guid = hash('sha256', $item['image'] . $item['name']);
	$uid = hash('sha256', $item['image'] . $item['source-url'] . $item['name'] . $item['address'] . $item['vitals1'] . $item['vitals2'] . $item['wanted-by'] . $item['charge'] . $item['charge']);
	// see if the uid exists
	$query = "SELECT * FROM `most-wanted` WHERE `uid`='$uid' LIMIT 1";
	$res = mysqli_query($link, $query);
	if (mysqli_num_rows($res) == 0)
	{
		// insert posting to computer datin base
		$query = "INSERT INTO `most-wanted` (`guid`, `uid`, `image`, `source-url`, `name`, `address`, `vitals1`, `vitals2`, `wanted-by`, `charge`, `judge`, `added`, `lastseen`) VALUES ("
		. "'" . mysqli_real_escape_string($link, $guid) . "', "
		. "'" . mysqli_real_escape_string($link, $uid) . "', "
		. "'" . mysqli_real_escape_string($link, $item['image']) . "', "
		. "'" . mysqli_real_escape_string($link, $item['source-url']) . "', "
		. "'" . mysqli_real_escape_string($link, $item['name']) . "', "
		. "'" . mysqli_real_escape_string($link, $item['address']) . "', "
		. "'" . mysqli_real_escape_string($link, $item['vitals1']) . "', "
		. "'" . mysqli_real_escape_string($link, $item['vitals2']) . "', "
		. "'" . mysqli_real_escape_string($link, $item['wanted-by']) . "', "
		. "'" . mysqli_real_escape_string($link, $item['charge']) . "', "
		. "'" . mysqli_real_escape_string($link, $item['judge']) . "', "
		. "NOW(), "
		. "NOW()"
		. ")";
		mysqli_query($link, $query);
	}
	else
	{
		$query = "UPDATE `most-wanted` SET `lastseen`=NOW() WHERE `guid`='" . mysqli_real_escape_string($link, $guid) . "' LIMIT 1";
		mysqli_query($link, $query);
	}
}

// ask the database if there is anything, by GUID
$query = "SELECT DISTINCT `guid` FROM `most-wanted` WHERE `posted`='0000-00-00 00:00:00'";
$res = mysqli_query($link, $query);
while ($guid = mysqli_fetch_assoc($res))
{
	// This should exist on every iteration
	$guid_query = "SELECT * FROM `most-wanted` WHERE `guid`='" . $guid['guid'] . "' AND `posted`='0000-00-00 00:00:00' LIMIT 1";
	$guid_res = mysqli_query($link, $guid_query);
	$data = mysqli_fetch_assoc($guid_res);

	$canpost = false; // check if we have some qualifications to post
	// all these guids, we need to re-ask if there are matching charges to compile up. in the meantime, lets get the basics taken care of

	if (strlen(trim($data['image'])) > 0)
	{
		// find out if image exists
		$ch = curl_init($data['image']);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($httpcode != "404")
		{
			$canpost = true;
		}
	}
	else
	{
		$canpost = true;
	}

	if ($canpost)
	{
		echo "most-wanted:Posting:" . $data['name'] . "[" . $guid['guid'] . "]:";

		$message = "";
		$message .= "Wanted: " . $data['name'] . "\n\n";
		$message .= "Last known address: " . $data['address'] . "\n";
		$message .= $data['vitals1'] . "\n" . $data['vitals2'] . "\n\n";
		$message .= "Wanted by: " . $data['wanted-by'] . "\n";
		// lets see if there are 1 or more charges
		$subquery = "SELECT `charge` FROM `most-wanted` WHERE `guid`='" . $data['guid'] . "' AND `posted`='0000-00-00 00:00:00'";
		$subres = mysqli_query($link, $subquery);
		if (mysqli_num_rows($subres) == 1)
		{
			// use the charge from the previous query
			$message .= "Charge: " . $data['charge'] . "\n";
		}
		else
		{
			$message .= "Charges:\n";
			// enumerate through all the charges and append them together
			while ($subdata = mysqli_fetch_assoc($subres))
			{
				$message .= "- " . $subdata['charge'] . "\n";
			}
		}

		// if we got an image thats present, use it. else, use the URI
		if (strlen(trim($data['image'])) > 0)
		{
			$message .= "\n" . $data['image'];
		}
		else
		{
			$message .= "\n" . $data['source-url'];
		}
		
		$d = $p->forumPost($message);
		
		$query = "UPDATE `most-wanted` SET `posted`=NOW() WHERE `guid`='" . $data['guid'] . "' AND `posted`='0000-00-00 00:00:00'";
		mysqli_query($link, $query);
		echo "Ok\n";
	}
}
mysqli_close($link);

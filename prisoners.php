<?php
require_once('FBLogin.php');
$link = mysqli_connect('localhost', 'root', 'password', 'chautauqua_sheriff.us');
if (!$link) { die('badness in the connection of the computer datin base'); }
$p = new FBLogin();

/**
 * Data resides at http[s]://findour.info/ccj.aspx
 * Acquire information and parse into array
 *
 * The information they provide is the folowing:
 * Image, Name, Age, When booked, Category of Crime, Their Bail, and the MFN
 */

function getPrisoners()
{
	$items = array();
	$base_url = 'http://findour.info';
	
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => $base_url . '/ccj.aspx', // https is Digicert *.chautauquacounty.com, valid 2/19/2016->2/20/2019
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

	$prisoners = array();
	// We need to parse out the HTML elements that are not important	
	preg_match_all('/<tr[\s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?<>]*?<\/tr>/', $data, $matches);
	foreach($matches[0] as $prisoner)
	{
		$item = array();
		// the image for the prisoner
		if (preg_match('/<img[\d\s\w\/=":;#&.]+src="([\d\w.\/:;]+)"[\d\s\w\/=":;#&.]+\/>/', $prisoner, $data) === 1)
		{
			$item['image'] = $base_url . '/' . $data[1];
		}
		else
		{
			$item['image'] = $base_url . '/images/NOPIC.jpg';
		}
		
		// remainder of data is in a span
		if (preg_match('/<span[\d\s\w\/\-=":;#&.]+>([\s\w]+)\s+Age:\s+(\d+)<br>Booked:\s+([\d\s\/:APM]+)<br>Cat:\s+(\w+)<br>Bail:\s+([\d$,.]+)\s+MFN:\s+(\d+)<\/span>/', $prisoner, $data) === 1)
		{
			$item['name'] = $data[1];
			$item['age'] = $data[2];
			$item['booked'] = $data[3];
			$item['category'] = $data[4];
			$item['bail'] = $data[5];
			$item['mfn'] = $data[6];
			$idx = hash('sha256', $item['image'] . $item['name'] . $item['age'] . $item['booked'] . $item['category'] . $item['bail'] . $item['mfn']);
			$prisoners[$idx] = $item;
		}
	}
	return $prisoners;
}

// get some data
$prisoners = getPrisoners();

if (count($prisoners) > 0) // Do we have data from getPrisoners()?
{
	foreach ($prisoners as $key => $data)
	{
		$query = "SELECT * FROM `prisoner` WHERE `guid`='$key' LIMIT 1";
		$res = mysqli_query($link, $query);
		if (mysqli_num_rows($res) == 0)
		{
			// insert posting to computer datin base
			$query = "INSERT INTO `prisoner` (`guid`, `image`, `name`, `age`, `when_booked`, `category`, `bail`, `mfn`, `added`, `lastseen`) VALUES ("
			. "'" . mysqli_real_escape_string($link, $key) . "', "
			. "'" . mysqli_real_escape_string($link, $data['image']) . "', "
			. "'" . mysqli_real_escape_string($link, $data['name']) . "', "
			. "'" . mysqli_real_escape_string($link, $data['age']) . "', "
			. "'" . mysqli_real_escape_string($link, $data['booked']) . "', "
			. "'" . mysqli_real_escape_string($link, $data['category']) . "', "
			. "'" . mysqli_real_escape_string($link, $data['bail']) . "', "
			. "'" . mysqli_real_escape_string($link, $data['mfn']) . "', "
			. "NOW(), "
			. "NOW()"
			. ")";
			mysqli_query($link, $query);
		}
		else
		{
			$query = "UPDATE `prisoner` SET `lastseen`=NOW() WHERE `guid`='" . mysqli_real_escape_string($link, $key) . "' LIMIT 1";
			mysqli_query($link, $query);
		}
	}
}

$query = "SELECT * FROM `prisoner` WHERE `posted`='0000-00-00 00:00:00'";
$res = mysqli_query($link, $query);
while ($item = mysqli_fetch_assoc($res))
{
	// find out if image exists
	$ch = curl_init($item['image']);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$output = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($httpcode != "404")
	{
		echo "Posting: $key ";
		echo "prisoners:Posting:" . $item['name'] . "[" . $item['guid'] . "]:";
		$message = "";
		$message .= "Welcome " . $item['name'] . " to Jail!\n\n";
		$message .= "They were booked in on " . $item['when_booked'];
		if (($item['category'] != 'Unspecified') && ($item['category'] != 'unknown'))
		{
			$message .= ", accused of a " . $item['category'];
		}
		if ($item['bail'] == '$0.00') // cause Jake said so
		{
			$message .= " with no bail";
		} else {
			$message .= " with a bail of " . $item['bail'];
		}
		$message .= "\n\n" . $item['image'];
	
		$d = $p->forumPost($message);

		$query = "UPDATE `prisoner` SET `posted`=NOW() WHERE `guid`='" . $item['guid'] . "' LIMIT 1";
		mysqli_query($link, $query);
		echo "Ok\n";
	}
}
mysqli_close($link);

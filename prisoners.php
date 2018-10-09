<?php

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
		}
		$prisoners[] = $item;
	}
	return $prisoners;
}

$prisoners = getPrisoners();
print_r($prisoners);
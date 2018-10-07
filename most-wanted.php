<?php


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
			if (preg_match('/([\s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?]+)<br><br>[\w\s:]+<br>([\s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?]+)<br>([\s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?]+)<br>([\s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?]+)<br><br>Wanted By:([\s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?]+)<br>Charge:([\s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?]+)<br>([\s\w\d\r\n\'\[\]\/\-|!@#$%^&*()_+={};:",.?]+)/', $data[1], $bioinfo) === 1)
			{
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
$most_wanted = array();
for ($alpha = ord('a'); $alpha <= ord('z'); $alpha++)
{
	$data = getMostWanted(chr($alpha));
	$most_wanted = array_merge($most_wanted, $data);
}

print_r($most_wanted);
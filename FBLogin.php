<?php

/**
 * We're emulating Internet Explorer 8 running Windows XP SP3 logging into facebook
 */

/**
php -r '
include("FBLogin.php");
$p = new FBLogin();
$data = "This is a test from the FBLogin class";
$d = $p->forumPost($data);
'
*/
class FBLogin
{
	private $credentials = array(
		'email' => '',
		'pass' => '',
	);

	private $curl_options = array(
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_COOKIEFILE => 'FBLogin.txt',
		CURLOPT_COOKIEJAR => 'FBLogin.txt',
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HEADER => false,
		CURLOPT_HTTPGET	=> true,
		CURLOPT_HTTPHEADER => array(
			'Accept-Language: en-us',
			'Accept: image/gif, image/jpeg, image/pjpeg, application/x-shockwave-flash, */*',
			'User-Agent: Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',
		),
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_RETURNTRANSFER  => true,
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_TIMEOUT => 10,
		CURLOPT_URL => '',
		CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)', // IE8 on Windows XP
	);

	public function __construct()
	{
		// test if we are logged in. If not, perform login
		if (!$this->areWeLoggedIn())
		{
			// get the login page
			$data = $this->getLoginPage();
			// parse the login page and shove in credentials
			$form = $this->parseLoginPage($data);
			// perform the actual login
			$login = $this->loginToFacebook($form);
			if (!$this->areWeLoggedIn())
			{
				die('error(' . __FUNCTION__ . '): went through login flow and still are not logged in');
			}
		}
	}

	public function forumPost($post_data)
	{
		$ch = $this->getCurl('https://m.facebook.com/Chautauqua-County-NY-Bookings-Most-Wanted-928416917358590/');
		$data = curl_exec($ch);

		// we need to extract form elements
		$form_inputs = array(
			'POST' => '',
			'VARS' => array(),
		);
		$m = array();
		if (preg_match('/<form method="post" action="(\/composer\/mbasic\/.*?)"/', $data, $m) === 0)
		{
			die('error(' . __FUNCTION__ . '): form post is not parseable');
		}
		$form_inputs['POST'] = $m[1];

		// these all have values according to last html inspection
		$fb_vars = array('fb_dtsg', 'jazoest', 'r2a', 'xhpc_timeline', 'target', 'c_src', 'cwevent', 'referrer', 'ctype', 'cver');
		$vars = array(); // key => value pair of $fb_vars and results from preg_match loop
		foreach ($fb_vars as $fb_var)
		{
			$m = array();
			if (preg_match('/<input type="hidden" name="' . $fb_var . '" value="(.*?)"/', $data, $m) == 0)
			{
				die('error(' . __FUNCTION . '): form hidden variable ' . $fb_var . ' expected but not found in regex');
			}
			$vars[$fb_var] = $m[1];
		}
		// add additional variables
		$vars['rst_icv'] = '';
		$vars['xc_message'] = $post_data; // the actual area for the message
		$vars['view_post'] = 'Post'; // the actual Post button being hit
		$form_inputs['VARS'] = $vars;

		// setup cURL for a POST operation
		$curlopt = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $form_inputs['VARS'],
		);
		$ch = $this->getCurl('https://m.facebook.com/' . $form_inputs['POST'], $curlopt);
		$data = curl_exec($ch);
		return $data;
	}

	/**
	 * Sets up a unified cURL interface based on class config
	 *
	 * @param string URL to pass and get page
	 * @param array Additional cURL options
	 */
	private function getCurl($url = null, $options = array())
	{
		$ch = curl_init();
		curl_setopt_array($ch, $this->curl_options);
		if (!is_null($url))
		{
			curl_setopt($ch, CURLOPT_URL, $url);
		}
		else
		{
			die('error(' . __FUNCTION__ . '): no url passed');
		}
		if (count($options) > 0)
		{
			foreach ($options as $curl_const => $curl_value)
			{
				curl_setopt($ch, $curl_const, $curl_value);
			}
		}
		return $ch;
	}

	/**
	 * Gets the Facebook Mobile Login page
	 */
	private function getLoginPage()
	{
		$ch = $this->getCurl('https://m.facebook.com');
		$data = curl_exec($ch);
		return $data;
	}

	/**
	 * Get our page. If we detect we're on it, we're logged in
	 *
	 * @return bool whether we're on our wanted page or not
	 */
	private function areWeLoggedIn()
	{
		$ch = $this->getCurl('https://m.facebook.com/Chautauqua-County-NY-Bookings-Most-Wanted-928416917358590/');
		$data = curl_exec($ch);
		// when displaying the above page, there is text at the top introducing the page and prompting the user to join or log in
		// We'll look for the join or log in
		if (preg_match('/>Log In<\/a>/', $data) === 0)
		{
			return true;
		}
		if (preg_match('/>Join<\/a>/', $data) === 0)
		{
			return true;
		}
		return false;
	}

	/**
	 * Takes page grabbed by getLoginPage and parses out the form data
	 *
	 * @param string data returned by getLoginPage
	 */
	private function parseLoginPage($data)
	{
		// Need to get the POST destination
		// Regex: <form[\s\w="]+action="(.*?)"[\s\w="]+>
		$form_post = '';
		if (preg_match('/<form[\s\w="]+action="(.*?)"[\s\w="]+>/', $data, $result) === 1)
		{
			$form_post = $result[1];
		}
		else
		{
			die('error(' . __FUNCTION__ . '): cannot find out where to post login page to');
		}

		// Need to get all the inputs, assume name before value
		$form_inputs = array();
		if (preg_match_all('/<input[\s\w="]+name="([\w]+)"[\s\w="]+value="(.*?)"[\s\w="]+\/>/', $data, $results) > 0)
		{
			foreach ($results[1] as $idx => $name)
			{
				$form_inputs[$name] = $results[2][$idx];
			}
			// add the rest for a proper post
			$form_inputs['email'] = $this->credentials['email'];
			$form_inputs['pass'] = $this->credentials['pass'];
			$form_inputs['login'] = 'Log In'; // emulated login submit
		}
		return array(
			"POST" => $form_post,
			"VARS" => $form_inputs,
		);
	}

	/**
	 * POSTS login information from parseLoginPage and tests if we're logged in
	 *
	 * @param string data returned by parseLoginPage
	 */
	private function loginToFacebook($form)
	{
		if (!array_key_exists('POST', $form))
		{
			die('error(' . __FUNCTION__ . '): no POST passed in form array');
		}
		if (!array_key_exists('VARS', $form))
		{
			die('error(' . __FUNCTION__ . '): no VARS passed in form array');
		}
		$post_destination = $form['POST'];
		$post_variables = $form['VARS'];
		$curlopt = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_variables,
		);
		$ch = $this->getCurl($post_destination, $curlopt);
		$data = curl_exec($ch);
		return $data;
	}
}

<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace minerva\extensions\adapter\view;

/**
 * This view adapter renders content using the PHP cURL library.
 * It is meant to be a way to render external data instead of your conventional view templates from local disk.
 * All of the options for cURL can be passed to take full advantage.
 * 
 * Why not fopen/fread? Because cURL is much much faster and includes more options like authenticaion and SSL.
 * It should also be safer (even though since PHP 5.2.0 there's an 'allow_url_include' setting set to off by default in php.ini).
 */
 
class Curl extends \lithium\template\view\Renderer {

	/**
	 * Renders content from a URL.
	 *
	 * @param string $template  (not used)
	 * @param array $data (not used, maybe should be the data posted to the url)
	 * @param array $options (must include a 'url' key)
	 * @return string
	 */
	public function render($template, $data = array(), array $options = array()) {
		$defaults = array('url' => '/', 'curl_options' => array(CURLOPT_HEADER => 0, CURLOPT_RETURNTRANSFER => 1));
		$options += $defaults;
		$options['curl_options'] += $defaults['curl_options'];

		// set URL
		$ch = curl_init($options['url']);
		// set options
		curl_setopt_array($ch, $options['curl_options']);		

		// grab contents and store in $data
		$data = curl_exec($ch);

		// close cURL resource, and free up system resources
		curl_close($ch);
		
		// return/output $data
		return $data;		
	}

}
?>

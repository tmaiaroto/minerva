<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 *
 * @todo: do not use arrays with sub arrays.. => switch to a good lambda solution
*/
use lithium\core\Libraries;

if (isset($minerva_config['facebook'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';

    try {
        Libraries::add('li3_facebook', array(
            'appId' => $minerva_config['facebook']['appId'],
            'secret' => $minerva_config['facebook']['secret'],
            'locale' => (isset($minerva_config['facebook']['locale'])) ?
            	$minerva_config['facebook']['locale'] : 'en_US',
            'logout_url' => (isset($minerva_config['facebook']['logout_url'])) ?
            	$minerva_config['facebook']['logout_url'] :
            	$protocol . $_SERVER['HTTP_HOST'] . MINERVA_BASE_URL . '/users/logout',
            'login_url' => (isset($minerva_config['facebook']['login_url'])) ?
            	$minerva_config['facebook']['login_url'] :
            	$protocol . $_SERVER['HTTP_HOST'] . MINERVA_BASE_URL . '/users/login'
        ));

    } catch (\Exception $e) {
        // Not going to handle it... Dependency check will bark.
    }
}

?>
<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 * 
 * This file is part of the Minerva CMS bootstrap process and is responsible
 * for setting up the render paths. This is what allows for various templates
 * to be used and overridden when using Minerva. The benefit here is that neither
 * Minerva core template files nor Minerva plugin template files need to be altered.
 * This allows the main application to decide how the web site looks.
 * 
 * Any library can subscribe to this system of checking various render paths for templates
 * by defining a "mineva_plugin" configuration key in the Libraries::add() call and setting 
 * it to true.
 * 
 * @see minerva\extensions\util\Theme
*/
use lithium\action\Dispatcher;
use minerva\extensions\util\Theme;
use \Exception;

Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
    
	$params['options']['render']['paths'] = Theme::setRenderPaths($params['request']);
	
    return $chain->next($self, $params, $chain);
});
?>
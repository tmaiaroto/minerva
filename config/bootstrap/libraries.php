<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;

/**
 * Optimize default request cycle by loading common classes.  If you're implementing custom
 * request/response or dispatch classes, you can safely remove these.  Actually, you can safely
 * remove them anyway, they're just there to give slightly you better out-of-the-box performance.
 */
require LITHIUM_LIBRARY_PATH . '/lithium/core/Object.php';
require LITHIUM_LIBRARY_PATH . '/lithium/core/StaticObject.php';
require LITHIUM_LIBRARY_PATH . '/lithium/util/Collection.php';
require LITHIUM_LIBRARY_PATH . '/lithium/util/collection/Filters.php';
require LITHIUM_LIBRARY_PATH . '/lithium/util/Inflector.php';
require LITHIUM_LIBRARY_PATH . '/lithium/util/String.php';
require LITHIUM_LIBRARY_PATH . '/lithium/core/Adaptable.php';
require LITHIUM_LIBRARY_PATH . '/lithium/core/Environment.php';
require LITHIUM_LIBRARY_PATH . '/lithium/net/Message.php';
require LITHIUM_LIBRARY_PATH . '/lithium/net/http/Message.php';
require LITHIUM_LIBRARY_PATH . '/lithium/net/http/Media.php';
require LITHIUM_LIBRARY_PATH . '/lithium/net/http/Request.php';
require LITHIUM_LIBRARY_PATH . '/lithium/net/http/Response.php';
require LITHIUM_LIBRARY_PATH . '/lithium/net/http/Route.php';
require LITHIUM_LIBRARY_PATH . '/lithium/net/http/Router.php';
require LITHIUM_LIBRARY_PATH . '/lithium/action/Controller.php';
require LITHIUM_LIBRARY_PATH . '/lithium/action/Dispatcher.php';
require LITHIUM_LIBRARY_PATH . '/lithium/action/Request.php';
require LITHIUM_LIBRARY_PATH . '/lithium/action/Response.php';
require LITHIUM_LIBRARY_PATH . '/lithium/template/View.php';
require LITHIUM_LIBRARY_PATH . '/lithium/template/view/Renderer.php';
require LITHIUM_LIBRARY_PATH . '/lithium/template/view/Compiler.php';
require LITHIUM_LIBRARY_PATH . '/lithium/template/view/adapter/File.php';
require LITHIUM_LIBRARY_PATH . '/lithium/storage/Cache.php';
require LITHIUM_LIBRARY_PATH . '/lithium/storage/cache/adapter/Apc.php';

/**
 * Add the Lithium core library.  This sets default paths and initializes the autoloader.  You
 * generally should not need to override any settings.
 */
Libraries::add('lithium');

/**
 * Add the application.  You can pass a `'path'` key here if this bootstrap file is outside of
 * your main application, but generally you should not need to change any settings.
 */
Libraries::add('minerva', array('default' => true));

/**
 * Add some plugins
 */
// Libraries::add('li3_docs');


/**
 * Auto-load all libraries under the app/libraries folder
 * Comment this code out and use Libraries:add('library_name'); manually if desired (it would save a directory scan and loop).
 * All the library bootstrap and routes files will be included. Be careful about overwriting routes and other conflicts.
 *
 * TODO: Think about a weight system or alphabetical loading...??? ... most importantly: make minerva libraries prefixed
 * autoloading is not a great idea when configurations need to be passed with the library add...though i guess those can be
 * called after the autoloading part below. not sure how a double call to add() works
 */
/*
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
$libraries_directory = opendir(LITHIUM_APP_PATH.DS.'libraries');
$libraries = array();
while (false !== ($library = readdir($libraries_directory))) {
    switch($library) {
        case '.':
        case '..':
        case '_source':
        // Can continue to add more here like an "ignore list" or run an is in_array() instead of a switch...
        break;
        default:
            if(is_dir(LITHIUM_APP_PATH.DS.'libraries'.DS.$library)) {
                Libraries::add($library);
            }
        break;
    }
}
*/

Libraries::add('blog');

Libraries::add('li3_flash_message');
Libraries::add('li3_assets', array(
                                     'config' => array(
                                        'js' => array(
                                                      'compression' => 'jsmin', // possible values: 'jsmin', 'packer', false (true uses jsmin)
                                                      'output_directory' => 'optimized', // directory is from webroot/css if full path is not defined
                                                      'packer_encoding' => 'Normal', // level of encoding (only used for packer), possible values: 0,10,62,95 or 'None', 'Numeric', 'Normal', 'High ASCII'
                                                      'packer_special_chars' => true
                                                     ),
                                        'css' => array(
                                                       'compression' => 'tidy', // possible values: true, 'tidy', false
                                                       'tidy_template' => 'highest_compression',
                                                       'less_debug' => false, // debugs lessphp writing messages to a log file, possible values: true, false
                                                       'output_directory' => 'optimized' // directory is from webroot/css if full path is not defined
                                                    ),
                                        'image' => array(
                                                         'compression' => true, // uses base64/data uri, possible values: true, false
                                                         'allowed_formats' => array('jpeg', 'jpg', 'jpe', 'png', 'gif') // which images to base64 encode
                                                        )
                                        )
                                    )
               );


?>
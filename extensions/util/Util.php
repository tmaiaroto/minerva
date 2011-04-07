<?php
/*
 * A general utility class for Minerva. This includes several useful methods used throughout the site.
*/
namespace minerva\extensions\util;

use \RecursiveIteratorIterator;
use \RecursiveArrayIterator;
use lithium\core\Libraries;
use lithium\util\Set;

class Util {
    
    /*
     * in_array recursive function using Spl libraries. Quite useful.
    */
    public function in_array_recursive($needle=null, $haystack=null) {
        if((empty($needle)) || (empty($haystack))) {
            return false;
        }
        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack)); 
        foreach($it AS $element) {
            if($element === $needle) {
                return true;
            } 
        }
        return false;
    }
    
    /*
     * A simple method to return a unique string, useful for approval codes and such.
     * An md5 hash of the unique id will be 32 characters long and the sha1 will be 40 characters long.
     * Without hashing, the unique id will be 13 characters long and 23 long if more entropy is used.
     * 
     * @params $options Array
     *      - hash: The hash method to use to hash the uid, md5, sha1, or false (default is md5)
     *      - prefix: The prefix to use for uniqid() method
     *      - entropy: Boolean, whether or not to add additional entropy (more unique)
    */
    public function unique_string($options=array()) {
        $options += array('hash' => 'md5', 'prefix' => '', 'entropy' => false);
        switch($options['hash']) {
            case 'md5':
                return md5(uniqid($options['prefix'], $options['entropy']));
            default:
            break;
            case 'sha1':
                return sha1(uniqid($options['prefix'], $options['entropy']));
            break;
            case false:
                return uniqid($options['prefix'], $options['entropy']);
            break;
        }
    }
    
    /**
     * Generate a unique pretty url for the model's record.
     * 
     * @params $options Array
     *      - url: The requested url (typically the inflector::slug() for a title)
     *      - id: The current id (optional, only if editing a record, so it knows to exclude itself as a conflict)
     *      - model: The model that's used as the lookup (to run the find() on)
     *      - separator: The optional separator symbol for spaces (default: -)
     * @return String The unique pretty url.
    */
    public function unique_url($options=array()) {
        $options += array('url' => null, 'id' => null, 'model' => null, 'separator' => '-');
        if((!$options['url']) || (!$options['model'])) {
            return null;
        }        
        $records = $options['model']::find('all', array('fields' => array('url'), 'conditions' => array('url' => array('like' => '/'.$options['url'].'/'))));
        $conflicts = array();
        
        foreach($records as $record) {
            // If the record id is an object, it's probably a MongoId, so make it a string to compare IF the passed id was not an object too.
            if((is_object($record->{$options['model']::key()})) && (!is_object($options['id']))) {
                $record_id = (string)$record->{$options['model']::key()};
            } else {
                $record_id = $record->{$options['model']::key()};
            }
            if($record_id != $options['id']) {
                $conflicts[] = $record->url;
            }
        }
        
        if (!empty($conflicts)) {
                $firstSlug = $options['url'];
                $i = 1;
                while($i > 0) {                        
                        if (!in_array($firstSlug . $options['separator'] . $i, $conflicts)) {					
                                $options['url'] = $firstSlug . $options['separator'] . $i;
                                $i = -1;
                        }
                $i++;
                }
        }        
        return strtolower($options['url']);
    }
    
    /**
     * Returns an array of all libraries that have models in order to provide
     * a list for page types, block types, or user types. The list is returned
     * in alphabetical order.
     *
     * @param $model String The model
     * @param $options Array Various options
     *      - exclude_minerva Boolean Excludes the minerva\models classes
     *      - exclude Array Other class paths to exclude
     * @return Array All of the class paths to the types for that model
    */
    public function list_types($model='Page', $options=array()) {
        $options += array('exclude_minerva' => false, 'exclude' => array(), 'library' => 'minerva');
        $model = ucfirst($model);
        
        $types = array();
        
        $model_class_name = Inflector::classify($model_name);
		$models = Libraries::locate('minerva_models', $model_class_name);
		$controller = $options['library'] . '.' . strtolower(Inflector::pluralize($model_name));
        
        foreach($models as $class_path) {
            $pieces = explode('\\', $class_path);
            $types[] = $pieces[0];
        }
        
        var_dump($types);exit();
        
        if($options['exclude_minerva'] === true) {
            $options['exclude'][] = 'minerva\models\\' . $model;
        }
        if(count($options['exclude']) > 0) {
            $i=0;
            foreach($types as $type) {
                if(in_array($type, $options['exclude'])) {
                    unset($types[$i]);
                }
                $i++;
            }
        }
        
        sort($types);
        return $types;
    }
    
    /**
     * Formats the order array for the find() method's order option.
     * By default uses id descending, if invalid, an empty array is returned.
     * The order string is passed as dot separated field.direciton.
     * ex. created.desc or created.asc
     * Also valid: created.DESC or created.descending
     *
     * @param $order String The dot separated field.direction
    */
    public function format_dot_order($order='id.desc') {
	$order_pieces = explode('.', $order);
	if(count($order_pieces) > 1) {
            switch(strtolower($order_pieces[1])) {
                case 'desc':
                case 'descending':
                default:
                    $direction = 'desc';
                    break;
                case 'asc':
                case 'ascending':
                    $direction = 'asc';
                    break;
            }
	    return array($order_pieces[0], $direction);
	}
        return array();
    }
    
}
?>
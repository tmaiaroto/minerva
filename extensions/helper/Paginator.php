<?php
/**
 * Minerva's Paginator Helper
 * 
 * @author Tom Maiaroto
 * @website http://www.shift8creative.com
 * @modified 2011-01-10 22:00:00 
 * @created 2011-01-10 22:00:00
 *
*/
namespace minerva\extensions\helper;

use minerva\libraries\util\Util;
use lithium\util\Inflector;

class Paginator extends \al13_helpers\extensions\helper\Lists {

   /**
    * Generate a list of links for pagination
    *
    * @param int $total The total number of pages
    * @param int $limit The limit per page
    * @param int $page The current page number
    * @param array $options An array of options for how the links are styled and also for any args that need to be passed to the linked URLs
    * @return string Generated HTML
    */
    public function pagination($total=false, $limit=false, $page=false, $options=array()) {
        $options += array('args' => array(), 'ul_class' => 'pagination_actions', 'next_text' => 'Next', 'first_text' => 'First', 'previous_text' => 'Previous', 'last_text' => 'Last');
        if(!$total || !$limit || !$page) {
                return '';
        }
        $args = $options['args'];
        
        $ret = '<ul class="' . $options['ul_class'] . '"><li>';

        if ($total <= $limit || $page == 1) {
                $ret .= $options['first_text'] . '</li><li>' . $options['previous_text'];
        } else {
                $args_first = $args += array('page:1','limit:'.$limit);
                $ret .= $this->tag('link', array('title' => $options['first_text'],
                        'url' => array('action' => 'index', 'args' => $args_first)
                ));
                $ret .= '</li><li>';
                $args_prev = $args += array('page:'.($page-1),'limit:'.$limit);
                $ret .= $this->tag('link', array('title' => $options['previous_text'],
                        'url' => array(
                                'action' => 'index',
                                'args' => $args_prev
                        )
                ));
        }
        $ret .= '</li>';

        $p = 0; $count = $total;
        while ($count > 0) {
                $p++; $count -= $limit;
                $ret .= '<li>';
                if ($p == $page) {
                        $ret .= '['.$p.']';
                } else {
                        $args_page = $args += array('page:'.$p,'limit:'.$limit);
                        $ret .= $this->tag('link', array('title' => '['.$p.']',
                                'url' => array('action' => 'index', 'args' => $args_page)
                        ));
                }
                $ret .= '</li>';
        }
        $ret .= '<li>';
        if ($total <= $limit || $page == $p) {
                $ret .= $options['next_text'] . '</li><li>' . $options['last_text'];
        } else {
                $args_next = $args += array('page:'.($page+1),'limit:'.$limit);
                $ret .= $this->tag('link', array('title' => $options['next_text'],
                                'url' =>  array(
                                        'action' => 'index',
                                        'args' => $args_next
                                )
                        ));
                $ret .= '</li><li>';
                $args_last = $args += array('page:'.$p,'limit:'.$limit);
                $ret .= $this->tag('link', array('title' => $options['last_text'],
                                'url' =>  array('action' => 'index', 'args'=> $args_last)
                        ));
        }
        $ret .= '</li></ul>';
        return $ret;
     }
    
}
?>
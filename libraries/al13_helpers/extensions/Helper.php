<?php
/**
 * Helper class file.
 *
 * @copyright     Copyright 2010, alkemann
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace al13_helpers\extensions;

/**
 * Base helper for all helpers in this package
 */
class Helper extends \lithium\template\Helper {

	protected $_strings = array(
		'block'            => '<div{:options}>{:content}</div>',
		'block-end'        => '</div>',
		'block-start'      => '<div{:options}>',
		'charset'     	   => '<meta http-equiv="Content-Type" content="{:type}; charset={:charset}" />',
		'image'            => '<img src="{:path}"{:options} />',
		'js-block'         => '<script type="text/javascript"{:options}>{:content}</script>',
		'js-end'           => '</script>',
		'js-start'         => '<script type="text/javascript"{:options}>',
		'link'             => '<a href="{:url}"{:options}>{:title}</a>',
		'list'             => '<ul{:options}>{:content}</ul>',
		'list-item'        => '<li{:options}>{:content}</li>',
		'meta'             => '<meta{:options}/>',
		'meta-link'        => '<link href="{:url}"{:options} />',
		'para'             => '<p{:options}>{:content}</p>',
		'para-start'       => '<p{:options}>',
		'script'           => '<script type="text/javascript" src="{:path}"{:options}></script>',
		'style'            => '<style type="text/css"{:options}>{:content}</style>',
		'style-import'     => '<style type="text/css"{:options}>@import url({:url});</style>',
		'style-link'       => '<link rel="{:type}" type="text/css" href="{:path}"{:options} />',
		'table-header'     => '<th{:options}>{:content}</th>',
		'table-header-row' => '<tr{:options}>{:content}</tr>',
		'table-cell'       => '<td{:options}>{:content}</td>',
		'table-row'        => '<tr{:options}>{:content}</tr>',
		'strong'           => '<strong{:options}>{:content}</strong>',
		'span'             => '<span{:options}>{:content}</span>',
		'tag'              => '<{:name}{:options}>{:content}</{:name}>',
		'tag-end'          => '</{:name}>',
		'tag-start'        => '<{:name}{:options}>'
	);

	public function tag($tag, $options = array()) {
		if ($tag == 'link' && !isset($options['options'])) $options['options'] = array();
		return $this->_render(__METHOD__, $tag, $options, array('escape' => false));
	}
}

?>

<?php
/**
 * Lists helper 0.3
 *
 * @copyright     Copyright 2010, alkemann
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 *
 */
namespace al13_helpers\extensions\helper;

use lithium\net\http\Router;

/**
 * The purpose of this helper is to generate menus and other lists of links. The dynamic api
 * lets you build any amount of multi level "menus". Created for the purpose of main, sub and
 * context sensitive menues, this helper can also be used as an HTML `<ul />` generator.
 *
 * Installation and requirements:
 *
 * - Add the AL13_helpers library to your app by placing the `al13_helpers` folder either in your
 *   `/lithium/libraries` folder
 *   or in your `/app/libraries/` folder, or somewhere else, but then you must
 *   supply a `'path'` argument, too.
 *
 * - In `/app/config/bootstrap/libraries.php` add `Libraries::add('al13_helpers');`
 *
 * - And it is ready to use in view with autoloading by `$this->lists->*`
 *
 * **Description**
 *
 * To understand how this helper works there are two important concepts. Firstly, in a single
 * run of lithium, only one instance of any helper is used. There for we can temporarily "store"
 * information in it (as a property) between views, elements and layouts. In the most common
 * use of this helper, links are created in the view and in elements and then the layout renders
 * them. The reason why this works is the second important concept; the layout is rendered after
 * the view. Therefore we can add to the list of urls in when the layout is rendered, the menu
 * helper already know all that it is to render.
 *
 * Usage example 1: _Creating a list and rendering it_
 * {{{
 * // We have a list of links stored in the database and on this view we wish to list them out.
 * // $links being a collection of entities with at the properties 'url' and 'title':
 * foreach ($links as $link) {
 * 	$menu->add('link_list', array($link->title, $link->url));
 * }
 * echo $menu->generate('link_list');
 * }}}
 *
 * Usage example 2: _A multilevel list_
 * {{{
 * // Say we have an Article with hasMany Page, to render a list of links to both we could do :
 * foreach ($data as $article) {
 * 	$menu->add('articles', array($article->title, array(
 * 		'action' =>'view', $article->id
 * 	)));
 * 	foreach ($article->pages as $page) {
 * 		$menu->add(
 * 			array('articles', $article->className),
 * 			array($page->title, array(
 * 				'controller'=> 'pages', 'action' => 'view', $page->id
 * 			)
 * 		));
 * 	}
 * }
 * echo $menu->generate('articles');
 * }}}
 *
 * This will generate this:
 * {{{
 *  <ul class="menu_articles">
 *  <li><a href="/articles/view/1">Article 1</a></li>
 *  <ul class="menu_art_1_class_name">
 *  	<li><a href="/pages/view/1">Page 1</a></li>
 *  	<li><a href="/pages/view/2">Page 2</a></li>
 *  </ul>
 *  <li><a href="/articles/view/2">Article 2</a></li>
 *  <ul class="menu_art_2_class_name">
 *  	<li><a href="/pages/view/3">Page 3</a></li>
 *  	<li><a href="/pages/view/4">Page 4</a></li>
 *  </ul>
 *  </ul>
 * }}}
 *
 * **Customizations**
 *
 * If you wish to style the menus, take a look at the generated source code, each `<ul />` level
 * is given a unique class based on the target name. If you have need of more fine control,
 * you can use the `$options` paramter of the helpers methods to use image icons, class on
 * the `<a />` tags, id, class or style `<li />`, `<ul />` and `<div />`s. See each method for
 * specifics.
 *
 * @author Alexander Morland aka alkemann
 * @modified 27.june 2010
 * @version 0.3
 */
class Lists extends \al13_helpers\extensions\Helper {

	private $_items = array('main' => array());

	/**
	 * Generate a list of links for pagination
	 *
	 * @param int $total
	 * @param int $limit
	 * @param int $page
	 * @return string Generated HTML
	 */
	public function pagination($total, $limit, $page) {
		$ret = '<ul class="actions"><li>';

		if ($total <= $limit || $page == 1) {
			$ret .= 'First</li><li>Previous';
		} else {
			$ret .= $this->tag('link', array('title' => 'First',
				'url' => array('action' => 'index', 'args' => array('page:1','limit:'.$limit))
			));
			$ret .= '</li><li>';
			$ret .= $this->tag('link', array('title' => 'Previous',
				'url' => array(
					'action' => 'index',
					'args' => array('page:'.($page-1),'limit:'.$limit)
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
				$ret .= $this->tag('link', array('title' => '['.$p.']',
					'url' => array('action' => 'index', 'args' => array('page:'.$p,'limit:'.$limit))
				));
			}
			$ret .= '</li>';
		}
		$ret .= '<li>';
		if ($total <= $limit || $page == $p) {
			$ret .= 'Next</li><li>Last';
		} else {
			$ret .= $this->tag('link', array('title' => 'Next',
					'url' =>  array(
						'action' => 'index',
						'args' => array('page:'.($page+1),'limit:'.$limit)
					)
				));
			$ret .= '</li><li>';
			$ret .= $this->tag('link', array('title' => 'Last',
					'url' =>  array('action' => 'index', 'args'=>array('page:'.$p,'limit:'.$limit))
				));
		}
		$ret .= '</li></ul>';
		return $ret;
	}

	/**
	 * Adds a menu item to a target location
	 *
	 *
	 * @param mixed $target String or Array target notations
	 * @param array $link Array in same format as used by HtmlHelper::link()
	 * @param array $options
	 *  @options 'icon'  > $html->image() params
	 *  @options 'class' > <a class="?">
	 *  @options 'li'    > string:class || array('id','class','style')
	 *  @options 'div'	 > string:class || boolean:use || array('id','class','style')
	 *
	 * @return boolean successfully added
	 */
	function add($target = 'main', $link = array(), $options = array()) {

		if (!is_array($link) || !is_array($options) || !isset($link[0]) || !(is_array($link[0]) || is_string($link[0]))) {
			return false;
		}

		if (!isset($link[1])) {
			$link[1] = array();
		}

		if (!isset($link[2])) {
			$link[2] = array();
		}

		if (!isset($link[3])) {
			$link[3] = false;
		}

		if (!isset($link[4])) {
			$link[4] = true;
		}

		if (is_array($target)) {

			$depth = count($target);
			$menu = &$this->items;

			for ($i = 0; $i < $depth; $i++) {
				if (!empty($menu) && array_key_exists($target[$i], $menu)) {
					$menu = &$menu[$target[$i]];
				} else {
					$menu[$target[$i]] = array(true);
					$menu = &$menu[$target[$i]];
				}
			}

		} else {
			$menu = &$this->items[$target];
		}

		$menu[] = array($link, $options);

		return true;
	}

	/**
	 * Adds an element to a target item
	 *
	 * @param mixed $target String or Array target notations
	 * @param string $element Any string
	 * @param array $options
	 *  @options 'li'    > string:class || array('id','class','style')
	 *  @options 'div'	 > string:class || boolean:use || array('id','class','style')
	 *
	 * @return boolean successfully added
	 */
	function addElement($target = 'main', $element = false, $options = array()) {
		if ($element === false) {
			return false;
		}

		if (is_array($target)) {

			$depth = count($target);
			$menu = &$this->items;

			for ($i = 0; $i < $depth; $i++) {
				if (!empty($menu) && array_key_exists($target[$i], $menu)) {
					$menu = &$menu[$target[$i]];
				} else {
					$menu[$target[$i]] = array(true);
					$menu = &$menu[$target[$i]];
				}
			}

		} else {
			$menu = &$this->items[$target];
		}

		$menu[] = array(1 => $options, 2 => $element);

		return true;
	}

	/**
	 * Renders and returns the generated html for the targeted item and its element and children
	 *
	 * @param mixed $source String or Array target notations
	 * @param array $options
	 *  @options 'class' > <ul class="?"><li><ul>..</li></ul>
	 *  @options 'id' 	 > <ul id="?"><li><ul>..</li></ul>
	 *  @options 'ul'    > string:class || array('class','style')
	 *  @options 'div'	 > string:class || boolean:use || array('id','class','style')
	 *  @options 'active'> array('tag' => string(span,strong,etc), 'attributes' => array(htmlAttributes), 'strict' => boolean(true|false)))
	 *
	 * @example echo $menu->generate('context', array('active' => array('tag' => 'link','attributes' => array('style' => 'color:red;','id'=>'current'))));
	 * @return mixed string generated html or false if target doesnt exist
	 */
	function generate($source = 'main', $options = array()) {

		$out = '';
		$list = '';

		if (isset($options['ul']))
			$ulAttributes = $options['ul'];
		else
			$ulAttributes = array();

		// DOM class attribute for outer UL
		if (isset($options['class'])) {
			$ulAttributes['class'] = $options['class'];
		} else {
			if (is_array($source)) {
				$ulAttributes['class'] = 'menu_' . $source[count($source) - 1];
			} else {
				$ulAttributes['class'] = 'menu_' . $source;
			}
		}

		// DOM element id for outer UL
		if (isset($options['id'])) {
			$ulAttributes['id'] = $options['id'];
		}

		$menu = array();
		// Find source menu
		if (is_array($source)) {

			$depth = count($source);
			$menu = &$this->items;

			for ($i = 0; $i < $depth; $i++) {
				if (!empty($menu) && array_key_exists($source[$i], $menu)) {
					$menu = &$menu[$source[$i]];
				} else {
					if (!isset($options['force']) || (isset($options['force']) && !$options['force']))
						return false;
				}
			}

		} else {
			if (!isset($this->items[$source])) {
				if (!isset($options['force']) || (isset($options['force']) && !$options['force']))
					return false;
			} else {
				$menu = &$this->items[$source];
			}
		}

		if (isset($options['reverse']) && $options['reverse'] == true) {
			unset($options['reverse']);
			$menu = array_reverse($menu);
		}

		$requestObj = $this->_context->request();
		if (isset($options['active']['strict']) && !$options['active']['strict']) {
			$requestParams = $requestObj->params;
			$here = trim(Router::match(array(
				'controller' => $requestParams['controller'],
				'action' => $requestParams['action']
			), $requestObj), "/");
		} else {
			$here = $requestObj->url;
		}

		// Generate menu items
		foreach ($menu as $key => $item) {
			$liAttributes = array();
			$aAttributes = array();

			if (isset($item[1]['li'])) {
				$liAttributes = $item[1]['li'];
			}

			if (isset($item[0]) && $item[0] === true) {
				$menusource = $source;
				if (!is_array($menusource)) {
					$menusource = array($menusource);
				}
				$menusource[] = $key;
				// Don't set DOM element id on sub menus */
				if (isset($options['id'])) {
					unset($options['id']);
				}
				$listitem = $this->generate($menusource, $options);
				if (empty($listitem)) {
					continue;
				}
			} elseif (isset($item[0])) {
				if (!isset($item[0][2]['title'])) {
					$item[0][2]['title'] = $item[0][0];
				}
				$active = ($here == trim(Router::match($item[0][1], $requestObj), "/"));
				if ( isset($options['active']) && $active) {
					if (is_array($options['active'])) {
						$tagOptions = array();
						foreach ($options['active'] as $a => $v) {
							if ($a == 'tag' || $a == 'strict' || $a == 'options') continue;
							if ($a == 'title') $tagOptions[$v] = $item[0][1];
							elseif ($a == 'url') $tagOptions[$v] = $item[0][0];
						}
						if (empty($tagOptions)) $tagOptions['content'] = $item[0][0];
						$tag = isset($options['active']['tag'])?$options['active']['tag']:'span';
					} else {
						$tag = 'span';
						$tagOptions['content'] = $item[0][0];
					}
					$tagOptions['options'] = isset($options['active']['options'])? $options['active']['options']: array();
					$listitem = $this->tag($tag, $tagOptions);
				} else {
					if ($active) {
						if (is_array($item[0][2])) {
							if (isset($item[0][2]['class'])) {
								$item[0][2]['class'] .= ' active';
							} else {
								$item[0][2]['class'] = 'active';
							}
						} else {
							$item[0][2] = array('class' => 'active');
						}
					}
					$listitem = $this->tag('link', array(
						'title' => $item[0][0],
						'url' => $item[0][1],
						'options' => $item[0][2]
					));
				}
			} elseif (isset($item[2])) {
				$listitem = $item[2];
			} else {
				continue;
			}

			if (isset($item[1]['div']) && $item[1]['div'] !== false) {
				$divOptions = array();
				if (is_array($item[1]['div'])) {
					$divOptions = $item[1]['div'];
				}
				$listitem = $this->tag('block',
						array('content' => $listitem,'options' => $divOptions));
			}
			if (substr($listitem,0,3) == '<ul') {
				$list .= $listitem;
			} else {
				$list .= $this->tag('list-item',
					array('content' => $listitem,'options' => $liAttributes));	
			}
		}

		// Generate menu
		$out .= $this->tag('list', array('content' => $list, 'options' => $ulAttributes));

		// Add optional outer div
		if (isset($options['div']) && $options['div'] !== false) {
			$divOptions = array();
			if (is_array($options['div'])) {
				$divOptions = $options['div'];
			}
			$out = $this->tag('block', array('content' => $out, 'options' => $divOptions));
		}
		return $out;
	}
}

?>

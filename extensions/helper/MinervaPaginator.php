<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace minerva\extensions\helper;

class MinervaPaginator extends \lithium\template\Helper {

	protected $_strings = array(
		'pagingWrapper'	=> '<div>{:content}</div>',
		'pagingUrl'			=> '/{:controller}/{:template}/page:{:page}/limit:{limit}'
	);

	public function config($page, array $options) {
		if (empty($options['controller'])) {
			$options['controller'] = $this->_context->_config['controller'];
			$options['template'] = $this->_context->_config['template'];
		}
	}

	public function prev($page, array $options) {
		$this->config($page, $options);
        $defaults = array('pagingUrl' => 'pagingUrl');
        $options += $defaults;
        
		if ($page > 1) {
			$options['page'] = --$page;
			$url = $this->_render(__METHOD__, $options['pagingUrl'], $options, array('escape' => false));
			return $this->_context->html->link($options['prevText'], $url, array('class' => $options['prevClass']));
		}
		return '<div class="prev '.$options['disabledClass'].'">'.$options['prevTextDisabled'].'</div>';
	}

	public function next($page, $total, $limit, array $options) {
		$this->config($page, $options);
        $defaults = array('pagingUrl' => 'pagingUrl');
        $options += $defaults;
        
		if ($total > ($limit * $page)) {
			$options['page'] = ++$page;
			$url = $this->_render(__METHOD__, $options['pagingUrl'], $options, array('escape' => false));
			return $this->_context->html->link($options['nextText'], $url, array('class' => $options['nextClass']));
		}
		return '<div class="next '.$options['disabledClass'].'">'.$options['nextTextDisabled'].'</div>';
	}

	public function numbers($page, $total, $limit, array $options) {
		$this->config($page, $options);
        $defaults = array('pagingUrl' => 'pagingUrl');
        $options += $defaults;
        
		$start = ($page - 4);
		$end = floor(($total / $limit));
        $end = ($end < 1) ? (int)$end+1:(int)$end;
		if ($page <= 4) {
			$start = 1;
		}
		if (($page + 4) < $end) {
			$end = ($page + 4);
		}
		$buffer = '<div class="'.$options['numbersClass'].'">';
		for ($i = $start; $i <= $end; $i++) {
			$options['page'] = $i;
			$url = $this->_render(__METHOD__, $options['pagingUrl'], $options, array('escape' => false));
			if ($page == $i) {
				$buffer .= $options['separator'].$this->_context->html->link($i, $url, array('class' => $options['activePageClass']));
			} else {
				$buffer .= $options['separator'].$this->_context->html->link($i, $url);
			}
		}
		$buffer .= $options['separator'];
		$buffer .= '</div>';
		return $buffer;
	}

	public function paginate($page, $total, $limit, array $options = array()) {
		$defaults = array(
			'showPrevNext' => true,
			'showNumbers' => true,
			'prevText' => "< Prev",
			'prevTextDisabled' => "< Prev",
			'nextText' => "Next >",
			'nextTextDisabled' => "Next >",
			'separator' => " | ",
			'activePageClass' => 'current',
            'paginationClass' => 'pagination',
			'numbersClass' => 'numbers',
			'nextClass' => 'next',
			'prevClass' => 'prev',
			'disabledClass' => 'disabled',
			'controller' => $this->_context->_config['controller'],
			'template' => $this->_context->_config['template'],
            //'pagingWrapper' => 'pagingWrapper'
		);
		$options += $defaults;
        $options['limit'] = $limit;
        $options['pagingWrapper'] = '<div class="'.$options['paginationClass'].'">{:content}</div>';
        
        // Set the pagingUrl in a more universal way ... this also takes into account querystring for search
        if(isset($_GET['q']) && !empty($_GET['q'])) {
            $options['pagingUrl'] = '/'.$this->_context->minervaHtml->here(false, false, false).'/page:{:page}/limit:{:limit}?q=' . $_GET['q'];
        } else {
            $options['pagingUrl'] = '/'.$this->_context->minervaHtml->here(false, false, false).'/page:{:page}/limit:{:limit}';
        }
        
		$content = "";
		if ($options["showPrevNext"]) {
			$content .= $this->prev($page, $options);
		}
		if ($options["showNumbers"]) {
			$content .= $this->numbers($page, $total, $limit, $options);
		}
		//$content .= $options['separator'];
		if ($options["showPrevNext"]) {
			$content .= $this->next($page, $total, $limit, $options);
		}
		return $this->_render(__METHOD__, $options['pagingWrapper'], compact('content'), array('escape' => false));
	}
    
}
?>
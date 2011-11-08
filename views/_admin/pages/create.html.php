<?php
$tagger_javascript = '';
?>
<div class="grid_16">
	<h2 id="page-heading">Create <?=$display_name; ?></h2>  
</div>
<div class="clear"></div>

<div class="grid_12">
    	<?php // $this->form->config(array('templates' => array('error' => '<div class="error"{:options}>{:content}</div>'))); ?>
	<?php
	// no longer need this:
	// $this->form->create($document, array('url' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'pages', 'action' => 'create', 'document_type' => $document_type));
	?>
	<?=$this->form->create($document); ?>
	<fieldset class="admin">
		<legend>Primary Information</legend>
	    <?php
		foreach($fields as $k => $v) {
			if(!isset($v['form']['position']) || $v['form']['position'] != 'options') {
				if(!isset($v['type']) || $v['type'] != 'array') {
				//if(!isset($v['form']['class']) || $v['form']['class'] != 'tagger') {
					echo $this->form->field($k, $v['form']);
				} else {
					// For tags... it's an array of values, so it breaks the form->field() helper method
					if(isset($v['form']['class']) && $v['form']['class'] == 'tagger') {
						// So capture its value and we'll use it to add the tags with the jQuery plugin
						if(is_object($document) && isset($document->$k)) {
							$v['value'] = $document->$k->data();
						} else {
							$v['value'] = array();
						}
						$v['form']['id'] = ucfirst($k);
						$tagger_javascript .= '$("#' . ucfirst($k) . '").addTag("' . implode(',', $v['value']) . '");';
						unset($document->$k);
						echo $this->form->field($k, $v['form']);
						if(isset($v['form']['help_text'])) {
							echo '<div class="help_text">' . $v['form']['help_text'] . '</div>';
						}
					}
				}
			}
	    }
		?>
	    <?=$this->form->submit('Add ' . $display_name); ?> <?=$this->html->link('Cancel', array('admin' => $this->minervaHtml->admin_prefix, 'library' => 'minerva', 'controller' => 'pages', 'action' => 'index', 'args' => array($document_type))); ?>
	</fieldset>
	
</div>

<div class="grid_4">
    <div class="box">
        <h2>Options</h2>
	    <div class="block">
			<fieldset class="admin">
			<?php
			foreach($fields as $k => $v) {
				if(isset($v['form'])) {
					if(isset($v['form']['position']) && $v['form']['position'] == 'options') {
						if(!isset($v['type']) || $v['type'] != 'array') {
						// if(!isset($v['form']['class']) || $v['form']['class'] != 'tagger') {
							echo $this->form->field($k, $v['form']);
							if(isset($v['form']['help_text'])) {
								echo '<div class="help_text">' . $v['form']['help_text'] . '</div>';
							}
						} else {
							// For tags... it's an array of values, so it breaks the form->field() helper method
							if(isset($v['form']['class']) && $v['form']['class'] == 'tagger') {
								// So capture its value and we'll use it to add the tags with the jQuery plugin
								if(is_object($document) && isset($document->$k)) {
									$v['value'] = $document->$k->data();
								} else {
									$v['value'] = array();
								}
								$v['form']['id'] = ucfirst($k);
								$tagger_javascript .= '$("#' . ucfirst($k) . '").addTag("' . implode(',', $v['value']) . '");';
								unset($document->$k);
								echo $this->form->field($k, $v['form']);
								if(isset($v['form']['help_text'])) {
									echo '<div class="help_text">' . $v['form']['help_text'] . '</div>';
								}
							}
						}
						
					}
				}
			}
			?>
			</fieldset>
        </div>
    </div>
    
    <div class="box">
		<h2>Create Other Page Types</h2>
		<div class="block">
			<?=$this->minervaHtml->link_types('page', 'create'); ?>
		</div>
    </div>
</div>

<?=$this->form->end(); ?>
<div class="clear"></div>
<script type="text/javascript">
$(document).ready(function() {
	<?php echo $tagger_javascript; ?>
});
</script>
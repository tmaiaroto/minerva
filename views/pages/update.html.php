<div class="grid_16">
	<h2 id="page-heading">Update <?=$display_name; ?></h2>  
</div>
<div class="clear"></div>

<div class="grid_12">
	<?php // $this->form->config(array('templates' => array('error' => '<div class="error"{:options}>{:content}</div>'))); ?>
	<?=$this->form->create($document); ?>
	<fieldset class="admin">
		<legend>Primary Information</legend>
	    <?php
		foreach($fields as $k => $v) {
			if(!isset($v['form']['position']) || $v['form']['position'] != 'options') {
		?>	    
		<?=$this->form->field($k, $v['form']);?>
		<?php
			} 
	    }
		?>
	    <?=$this->form->submit('Edit ' . $display_name); ?> <?=$this->html->link('Cancel', array('controller' => 'pages', 'action' => 'index')); ?>
	</fieldset>
	
</div>

<div class="grid_4">
    <div class="box">
        <h2>Options</h2>
	    <div class="block">
			<fieldset class="admin">
			<?php
			foreach($fields as $k => $v) {
				if(isset($v['form']['position']) && $v['form']['position'] == 'options') {
			?>	    
			<?=$this->form->field($k, $v['form']);?>
			
			<?php
					if(isset($v['form']['help_text'])) {
						echo '<div class="help_text">' . $v['form']['help_text'] . '</div>';
					}
				} 
			}
			?>
			</fieldset>
        </div>
    </div>
</div>

<?=$this->form->end(); ?>
<div class="clear"></div>
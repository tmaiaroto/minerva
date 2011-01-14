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
				$v['form']['type'] = (isset($v['form']['type'])) ? $v['form']['type']:'text';
				switch($v['form']['type']) {
					case 'text':
					case 'input':
					default:
						echo $this->form->field($k, $v['form']);
						break;
					case 'select':
						echo $this->form->label($v['form']['label']);
						echo $this->form->select($k, $v['form']['options']);
						break;
				}
			}
	    }
		?>
	    <?=$this->form->submit('Edit ' . $display_name); ?> <?=$this->html->link('Cancel', array('controller' => 'users', 'action' => 'index')); ?>
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
			<?php if($v['form']['type'] == 'select') { ?>
				<?=$this->form->label($v['form']['label']);?>
				<?=$this->form->select($k, $v['form']['options']);?>
			<?php } else { ?>
				<?=$this->form->field($k, $v['form']);?>
			<?php } ?>
			
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
<div class="grid_16">
	<h2 id="page-heading">Create <?=$display_name; ?></h2>  
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
	    <?=$this->form->submit('Add ' . $display_name); ?> <?=$this->html->link('Cancel', array('controller' => 'users', 'action' => 'index')); ?>
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
		    
		    switch($v['form']['type']) {
			case 'text':
		        case 'input':
			default:
			    echo $this->form->field($k, $v['form']);
			    break;
			case 'select':
			    echo '<div>';
			    if(isset($v['form']['label'])) {
				echo '<label>' . $v['form']['label'] . '</label>';
			    }
			    echo $this->form->select($k, $v['form']['options']) . '</div>';
			    break;
		    }
		    
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
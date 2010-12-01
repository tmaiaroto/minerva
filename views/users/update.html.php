<div class="grid_4">
	<div class="box">
		<h2>Box</h2>
		<div class="block">
			
		</div>
	</div>
</div>


<div class="grid_8">
    <h2 id="page-heading">Update User</h2>    
    <div class="box">
	<?=$this->form->create(null, array('type' => 'file')); ?>
		<fieldset>
			<legend>Your Information</legend>
		    	<?=$this->form->field('first_name', array('label' => 'First Name', 'value' => $record->first_name));?>
			<?=$this->form->field('last_name', array('label' => 'Last Name', 'value' => $record->last_name));?>
			<?=$this->form->field('url', array('label' => 'Profile URL', 'value' => $record->url));?>
			<?=$this->form->field('email', array('label' => 'E-Mail', 'value' => $record->email));?>
		</fieldset>
	    <?=$this->form->submit('Update'); ?>
	<?=$this->form->end(); ?>
	</div>
	
</div>

<div class="grid_4">
	<div class="box">
		<h2>Box</h2>
		<div class="block">
			
		</div>
	</div>
</div>
<div class="clear"></div>
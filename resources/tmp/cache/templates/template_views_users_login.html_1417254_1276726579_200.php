<?php echo $this->form->create(); ?>
	<?php echo $this->form->field('username'); ?>
	<?php echo $this->form->field('password', array('type' => 'password')); ?>
	<?php echo $this->form->submit('Log in'); ?>
<?php echo $this->form->end(); ?>

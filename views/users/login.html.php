<?=$this->form->create(); ?>
	<?=$this->form->field('username'); ?>
	<?=$this->form->field('password', array('type' => 'password')); ?>
	<?=$this->form->submit('Log in'); ?>
<?=$this->form->end(); ?>

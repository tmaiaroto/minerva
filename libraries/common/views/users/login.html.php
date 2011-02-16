<?php
// use \lithium\storage\Session;
?>

<div class="grid_11">
    <div id="left_column">
        <div id="login_form">
        <?=$this->form->create(); ?>
        <fieldset class="login">
            <legend>Login with your account</legend>
            <div class="input"><?=$this->form->field('email'); ?></div>
            <div class="input"><?=$this->form->field('password', array('type' => 'password')); ?></div>
            <?=$this->form->submit('Log in', array('class' => 'submit')); ?>
        </fieldset>
        <?=$this->form->end(); ?>
        </div>
    </div>
</div>

<div class="grid_5" id="right_grid">
    <div id="right_column">
        <div class="box">
            <p>If you don't have an account yet, you can register for free by <a href="/register">clicking here.</a></p>
            <?php /*
            <div id="fb_login">
                <p>Alternatively, you can login if you have a Facebook account, without having to register!<br />
                <a href="<?php echo Session::read('fb_login_url'); ?>"><img src="/img/family_spoon/fb-login-button.png" alt="Login with Facebook" /></a></p>
            </div>
            */ ?>
        </div>
    </div>
</div>
<div class="grid_11">
    <div id="left_column">
    <h2 id="page-heading">Register for Free</h2>
    <p>
        Using Family Spoon is completely free and it's easy to sign up. Just fill out the form below and you'll be on your way. Afterward, you will have the opporunity to complete your profile which will help your friends and family find you.
    </p>
	<?=$this->form->create($user, array('action' => 'register')); ?>
	    <fieldset class="register">
                <legend>User Information</legend>
                <div class="input"><?=$this->form->field('first_name', array('label' => 'First Name'));?><div class="input_help">Please enter your first name.</div></div>
                <div class="input"><?=$this->form->field('last_name', array('label' => 'Last Name'));?><div class="input_help">Please enter your last name.</div></div>
                <div class="input"><?=$this->form->field('email', array('label' => 'E-mail', 'id' => 'email_input'));?><div class="input_help">Please enter your e-mail address.</div></div>
                <div class="input"><?=$this->form->field('password', array('label' => 'Password', 'id' => 'password_input'));?><div class="input_help">Choose a password at least 6 characters long.</div></div>
                <?php // echo $this->form->field('profile_pic', array('type' => 'file', 'label' => 'Profile Picture')); ?>
	    <?=$this->form->submit('Create my account', array('class' => 'submit')); ?>
            <br style="clear: left;" /><p class="agree_to_terms small">By clicking on the "create my account" button, you are agreeing to the Terms of Service and the Privacy Policy.</p>
            </fieldset>
	<?=$this->form->end(); ?>
    </div>
</div>

<div class="grid_5" id="right_grid">
	<div id="right_column">
            <div class="box terms_of_service_header">
                 <h2>Terms of Service</h2>
            </div>
	    <div class="terms_of_service_box">
                <p class="small">These Terms of Service ("Terms") govern your access to and use of the services and Family Spoon's websites (the "Services"), and any information, text, graphics, or other materials uploaded, downloaded or appearing on the Services (collectively referred to as "Content"). Your access to and use of the Services is conditioned on your acceptance of and compliance with these Terms. By accessing or using the Services you agree to be bound by these Terms.
                </p>
                <p class="small"><strong>Passwords</strong><br />
                You are responsible for safeguarding the password that you use to access the Services and for any activities or actions under your password. We encourage you to use “strong” passwords (passwords that use a combination of upper and lower case letters, numbers and symbols) with your account. Twitter cannot and will not be liable for any loss or damage arising from your failure to comply with the above requirements.
                </p>
                <p class="small"><strong>Copyright Policy</strong><br />
                Family Spoon respects the intellectual property rights of others and expects users of the Services to do the same. We will respond to notices of alleged copyright infringement that comply with applicable law and are properly provided to us. If you believe that your Content has been copied in a way that constitutes copyright infringement, please provide us with the following information: (i) a physical or electronic signature of the copyright owner or a person authorized to act on their behalf; (ii) identification of the copyrighted work claimed to have been infringed; (iii) identification of the material that is claimed to be infringing or to be the subject of infringing activity and that is to be removed or access to which is to be disabled, and information reasonably sufficient to permit us to locate the material; (iv) your contact information, including your address, telephone number, and an email address; (v) a statement by you that you have a good faith belief that use of the material in the manner complained of is not authorized by the copyright owner, its agent, or the law; and (vi) a statement that the information in the notification is accurate, and, under penalty of perjury, that you are authorized to act on behalf of the copyright owner.
                <br /><br />We reserve the right to remove Content alleged to be infringing without prior notice and at our sole discretion. In appropriate circumstances, Family Spoon will also terminate a user’s account if the user is determined to be a repeat infringer. Our designated copyright agent for notice of alleged copyright infringement appearing on the Services is:
                </p>
            </div>
	</div>
</div>
<div class="clear"></div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#password_input').val('');
        
        $('input').blur(function() {
           $('.input_help').hide();
           //$(this).siblings('.error').show();
           $(this).siblings('.in_use_error').show();
           $(this).siblings('.password_error').show();
        });
        
        $('input').focus(function() {
            $(this).parent().siblings().show();
            $(this).siblings('.error').hide();
            $(this).siblings('.in_use_error').hide();
            $(this).siblings('.password_error').hide();
        });
        
        $('#email_input').change(function() {
            $.get('/users/is_email_in_use/' + $('#email_input').val(), function(data) {
                if(data == 'true') {
                    $('.in_use_error').remove();
                    $('#email_input').parent().append('<div class="in_use_error" id="email_error">Sorry, this e-mail address is already registered.</div>');
                } else {
                    $('#email_error').remove();
                }
            });
        });
        
        $('#password_input').change(function() {
            if($('#password_input').val().length < 6) {
                $('.password_error').remove();
                $('#password_input').parent().append('<div class="password_error" id="password_error">Password must be at least 6 characters long.</div>');
            } else {
                $('#password_error').remove();
            }
        });
    });
</script>
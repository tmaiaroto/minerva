<div class="grid_11">
    <div id="left_column">
    <h2 id="page-heading">Manage Your Account</h2>    
    <div class="box">
        <div class="manage_account">
	<?=$this->form->create(null, array('type' => 'file')); ?>
		<fieldset>
			<legend>Your Information</legend>
		    	<div class="input"><?=$this->form->field('first_name', array('label' => 'First Name', 'value' => $record->first_name));?></div>
			<div class="input"><?=$this->form->field('last_name', array('label' => 'Last Name', 'value' => $record->last_name));?></div>
			<div class="input">
                            <?=$this->form->field('url', array('label' => 'Profile URL', 'value' => $record->url));?>
                            <div class="profile_url_helper_copy">
                                <span class="small"><em>http://www.familyspoon.com/user/profile/<strong><?=$record->url; ?></strong></em></span>
                            </div>
                        </div>
                        <div class="input">
                            <?php $email = !empty($record->new_email) ? $record->new_email : $record->email; ?>
                            <?=$this->form->field('new_email', array('label' => 'E-Mail', 'value' => $email));?>
                            <span class="small"><em>This is also your login. <?php echo !empty($record->new_email) ? ' <strong>Change pending confirmation.</strong>' : ''; ?></em></span>
                        </div>
                        <br />
		</fieldset>
		<fieldset>
			<legend>Your Preferences &amp; Privacy</legend>
			<div class="input">
                            <?=$this->form->field('alias', array('label' => 'Pen Name', 'value' => $record->alias));?>
                            <span class="small"><em>Always use this "pen name" / alias when creating recipes.</em></span><br />
                        </div>
                        
                        <div class="input checkbox">
                            <div>
                                <input type="checkbox" name="alias_profile" id="AliasProfile" <?php echo ($record->alias_profile = true) ? 'check="checked"' : ''; ?>/>
                                <label for="AliasProfile">Use my Pen Name in my Profile</label>
                            </div>
                            <div>
                                <span class="small"><em>If checked, your profile page will show your alias and not your real name.</em></span>
                            </div>
                        </div>
                        
                        <div class="input checkbox">
                            <div>
                                <input type="checkbox" name="no_emails" id="NoEmails" <?php echo ($record->no_emails = true) ? 'check="checked"' : ''; ?>/>
                                <label for="NoEmails">Do not Send me E-mail</label>
                            </div>
                            <div>
                                <span class="small"><em>If checked, the only e-mails we will send will be those related to your account.</em></span>
                            </div>
                        </div>
		</fieldset>
	    <?=$this->form->submit('Update my Information', array('class' => 'submit')); ?>
	<?=$this->form->end(); ?>
        </div>
        
        <?php if(!$record->facebook_uid) { ?>
            <br style="clear: left;" />
            <h2 class="title">Change Your Password</h2>
            <p>You can update your password here, you will not be sent a confirmation so please remember the password you choose. Your password must be at least six characters long and you must confirm your new password by entering it twice below. If you forget it, you can always <a class="underline" href="/recover-lost-password">recover your lost password</a>.</p>
            <div class="manage_account">
                <?php // echo $this->form->create(null, array('action' => 'update_password/'.$record->url)); ?>
                <form id="update_password_form" onSubmit="return false;">
		<fieldset>
                        <div class="input"><?=$this->form->field('password', array('label' => 'Password', 'value' => ''));?></div>
			<div class="input"><?=$this->form->field('password_confirm', array('label' => 'Confirm it', 'value' => ''));?></div>
                        <br />
                        <div id="update_password_response"></div>
		</fieldset>
                <?php // echo $this->form->submit('Update my Password', array('class' => 'submit')); ?>
                <?php // echo $this->form->end(); ?>
                    <button id="update_password" class="submit">Update my Password</button>
                </form>
            </div>
        <?php } ?>
    </div>
    </div>
</div>


<div class="grid_5" id="right_grid">
	<div id="right_column">
            <div class="box">
                <h2>Help</h2>
            </div>
	    <div>
                <p>
		Here you can control your preferences for using Family Spoon, update your profile information, and change your password. A few things to understand about the fields you see here:
		</p>
		<p>
		<strong>First &amp; Last Name</strong><br />
		Your first and last name (and e-mail if you're not using Facebook Connect) is the most that we ever require of you to use this site. We want to protect your privacy so you can always sign recipes with a "pen name" or author alias. We do not sell, give out, or otherwise share your name or e-mail address with anyone. The reason why we (and you) need to provide this information is so that your friends and family know who you are.
		</p>
		<p><strong>E-mail</strong><br />
		Your "username" actually is your e-mail address (unless you logged in using Facebook). It's unique, easy to remember, and we need it to send you a confirmation to activate your account, approve password changes, or recover lost passwords. If you created your account via Facebook Connect, you won't have an e-mail address filled in here (and you can only login using Facebook Connect). We trust Facebook to authorize you for us and we know you are who you say you are. You are welcome to add an e-mail address and create a new password so you can login to Family Spoon either way, but it's completely optional.
		<br /><br />
		If you do have an e-mail address, this is where we will send you notifications about things you need to act on. Such things may include someone sharing a recipe with your family that you might need to approve, etc.
		<br /><br />
		Remember, we do not sell or give out your e-mail address to anyone. We may, in the future, ocassionally send you information about the site as well with your approval. As of now, we do not have any sort of mailing list and you probably should at least glance at whatever we do send you for now.
		</p>
		<p><strong>Profile URL</strong><br />
		Like many sites, Family Spoon also has a place for you to have a profile page. This is so that your friends and family can find you and see what you're cooking. This URL is automatically built for you when you create your account, but you can change it to something else if you want. This could be something a little more creative and easy to remember than the default. Any spaces or special characters will be converted to dashes and everything will become lowercase. These have to be unique. So if "john-doe" is available, it can be yours.
		</p>
            </div>
	</div>
</div>    
<div class="clear"></div>

<script type="text/javascript">
$(document).ready(function() {
    $('#update_password').live('click', function() {
        $.post("/users/update_password/<?=$record->url; ?>.json",
            $("#update_password_form").serialize(),
            function(data){
                $("#update_password_response").text(data.response);
            },
        "json");
        return false;
    });
});
</script>
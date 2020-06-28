<form method="post" class="forgot_password_form" action="/forgot_password">
	<fieldset class="register">
	<input type="hidden" name="do" value="yes"/>
    <input type="hidden" name="token" value="<?=$this->get_csrf_token()?>"/>

		<legend>Forgot password</legend>
		<p>
			<label class="left" for="username">Username</label> <input type="text" class="field" id="username" name="username"/>
		</p>
		<div>
			<label class="left">&nbsp;</label> <input type="submit" class="button" id="submit" value="Reset password" />
		</div>
	</fieldset>
</form>

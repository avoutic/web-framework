<form method="post" class="login_form" action="/login" enctype="multipart/form-data" onsubmit="password.value = hex_sha1(password_helper.value); return true;">
	<fieldset class="login">
		<input type="hidden" name="do" value="yes"/>
        <input type="hidden" name="token" value="<?=get_csrf_token()?>"/>
        <input type="hidden" id="password" name="password" value=""/>
		<input type="hidden" name="return_page" value="<?=PageCore::encode($args['return_page'])?>"/>
		<input type="hidden" name="return_query" value="<?=PageCore::encode($args['return_query'])?>"/>
		<legend>Login form</legend>
		<p>
			<label class="left" for="username">Username</label> <input type="text" class="field" id="username" name="username" value="<?=$args['username']?>"/>
		</p>
		<p>
			<label class="left" for="password_helper">Password</label> <input type="password" class="field" id="password_helper" name="password_helper"/>
		</p>
		<div>
			<label class="left">&nbsp;</label> <input type="submit" class="button" id="submit" value="Submit" />
		</div>
	</fieldset>
</form>
<span class="login_form_links"><a class="login_forgot_password_link" href="/forgot_password">Forgot your password?</a><? if ($this->config['allow_registration']) echo "| <a class=\"login_register_link\" href=\"/register_account\">No account yet?</a>";?></span>

<form method="post" class="register_form" action="/register_account">
	<fieldset class="register">
		<input type="hidden" name="do" value="yes"/>
        <input type="hidden" name="token" value="<?=$this->get_csrf_token()?>"/>
        <input type="hidden" id="password" name="password" value=""/>
        <input type="hidden" id="password2" name="password2" value=""/>
		<legend>Login Details</legend>
		<p>
			<label class="left" for="username">Username</label> <input type="text" class="field" id="username" name="username" value="<?=$this->page_content['username']?>"/>
		</p>
		<p>
			<label class="left" for="password">Password</label> <input type="password" class="field" id="password" name="password"/>
		</p>
		<p>
			<label class="left" for="password2">Password verification</label> <input type="password" class="field" id="password2" name="password2"/>
		</p>
	</fieldset>
	<fieldset class="user_details">
		<legend>User Details</legend>
		<p>
			<label class="left" for="name">Name</label> <input type="text" class="field" id="name" name="name" value="<?=$this->page_content['name']?>"/>
		</p>
		<p>
			<label class="left" for="email">E-mail</label> <input type="text" class="field" id="email" name="email" value="<?=$this->page_content['email']?>"/>
		</p>
	</fieldset>
	<div>
		<label class="left">&nbsp;</label> <input type="submit" class="button" id="submit" value="Submit" />
	</div>
</form>

<?php
use WebFramework\Core\ActionCore;
?>
<form method="post" action="/<?=$args['login_page']?>">
  <fieldset>
    <input type="hidden" name="do" value="yes"/>
    <input type="hidden" name="token" value="<?=$this->get_csrf_token()?>"/>
    <input type="hidden" id="password" name="password" value=""/>
    <input type="hidden" name="return_page" value="<?=ActionCore::encode($args['return_page'])?>"/>
    <input type="hidden" name="return_query" value="<?=ActionCore::encode($args['return_query'])?>"/>
    <legend>Login form</legend>
    <p>
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?=ActionCore::encode($args['username'])?>" autocomplete="off"/>
    </p>
    <p>
      <label for="password_helper">Password</label>
      <input type="password" id="password_helper" name="password_helper"/>
    </p>
<?php
if ($args['recaptcha_needed'])
{
    echo <<<HTML
    <div>
      <div class="g-recaptcha" data-sitekey="{$args['recaptcha_site_key']}"></div>
    </div>
HTML;
}
?>

    <div>
      <label>&nbsp;</label>
      <input type="submit" value="Submit" />
    </div>
  </fieldset>
</form>
<span>
  <a href="/forgot_password">Forgot your password?</a>
<?php
if ($this->get_config('registration.allow_registration'))
{
    echo <<<HTML
  | <a href="/register_account">No account yet?</a>
HTML;
}
?>
</span>

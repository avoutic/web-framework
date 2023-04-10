<?php

use WebFramework\Core\ActionCore;

$csrf_token = $this->get_csrf_token();
$return_page_fmt = ActionCore::encode($args['return_page']);
$return_page_fmt = ActionCore::encode($args['return_query']);
$username_fmt = ActionCore::encode($args['username']);

echo <<<HTML
<form method="post" action="/{$args['login_page']}">
  <fieldset>
    <input type="hidden" name="do" value="yes"/>
    <input type="hidden" name="token" value="{$csrf_token}"/>
    <input type="hidden" id="password" name="password" value=""/>
    <input type="hidden" name="return_page" value="{$return_page_fmt}"/>
    <input type="hidden" name="return_query" value="{$return_query_fmt}"/>
    <legend>Login form</legend>
    <p>
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="{$username_fmt}" autocomplete="off"/>
    </p>
    <p>
      <label for="password_helper">Password</label>
      <input type="password" id="password_helper" name="password_helper"/>
    </p>
HTML;

if ($args['recaptcha_needed'])
{
    echo <<<HTML
    <div>
      <div class="g-recaptcha" data-sitekey="{$args['recaptcha_site_key']}"></div>
    </div>
HTML;
}

echo <<<'HTML'
    <div>
      <label>&nbsp;</label>
      <input type="submit" value="Submit" />
    </div>
  </fieldset>
</form>
<span>
  <a href="/forgot_password">Forgot your password?</a>
HTML;

if ($this->get_config('registration.allow_registration'))
{
    echo <<<'HTML'
  | <a href="/register_account">No account yet?</a>
HTML;
}

echo <<<'HTML'
</span>
HTML;

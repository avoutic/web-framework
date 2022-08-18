<form method="post" action="/change_email">
  <fieldset>
    <input type="hidden" name="do" value="yes"/>
    <input type="hidden" name="token" value="<?=$this->get_csrf_token()?>"/>
    <legend>Change email</legend>
    <p>
      <label for="email">E-mail</label> <input type="text" id="email" name="email" value="<?=$args['email']?>"/>
    </p>
  </fieldset>
  <p>
    <label>&nbsp;</label> <input type="submit" value="Change" />
  </p>
</form>

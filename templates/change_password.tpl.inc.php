<form method="post" class="contactform" action="/change_password" enctype="multipart/form-data">
  <fieldset class="register">
     <input type="hidden" name="do" value="yes"/>
     <input type="hidden" name="token" value="<?=get_csrf_token()?>"/>

     <legend>Change password</legend>
     <p>
       <label class="left" for="orig_password">Original password</label> <input type="password" class="field" id="orig_password" name="orig_password"/>
     </p>
     <p>
       <label class="left" for="password">Password</label> <input type="password" class="field" id="password" name="password"/>
     </p>
     <p>
       <label class="left" for="password2">Password verification</label> <input type="password" class="field" id="password2" name="password2"/>
     </p>
   </fieldset>
   <p>
     <label>&nbsp;</label> <input type="submit" class="button" id="submit" value="Change" />
   </p>
</form>

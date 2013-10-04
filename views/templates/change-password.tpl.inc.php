<form method="post" class="contactform" action="/change_password" enctype="multipart/form-data" onsubmit="password.value = hex_sha1(password_helper.value); password2.value = hex_sha1(password2_helper.value); orig_password.value = hex_sha1(orig_password_helper.value); return true;">
       <fieldset class="register">
               <input type="hidden" name="do" value="yes"/>
               <input type="hidden" name="token" value="<?=get_csrf_token()?>"/>
        <input type="hidden" id="password" name="password" value=""/>
        <input type="hidden" id="password2" name="password2" value=""/>
        <input type="hidden" id="orig_password" name="orig_password" value=""/>
               <legend>Change password</legend>
               <p>
                       <label class="left" for="orig_password_helper">Original password</label> <input type="password" class="field" id="orig_password_helper" name="orig_password_helper"/>
               </p>
               <p>
                       <label class="left" for="password_helper">Password</label> <input type="password" class="field" id="password_helper" name="password_helper"/>
               </p>
               <p>
                       <label class="left" for="password2_helper">Password verification</label> <input type="password" class="field" id="password2_helper" name="password2_helper"/>
               </p>
       </fieldset>
       <p>
               <label>&nbsp;</label> <input type="submit" class="button" id="submit" value="Change" />
       </p>
</form>

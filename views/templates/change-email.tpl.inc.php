<form method="post" class="contactform" action="/change_email" enctype="multipart/form-data">
       <fieldset class="register">
               <input type="hidden" name="do" value="yes"/>
               <legend>Change email</legend>
        <p>
            <label class="left" for="email">E-mail</label> <input type="text" class="field" id="email" name="email" value="<?=$args['email']?>"/>
        </p>
       </fieldset>
       <p>
               <label>&nbsp;</label> <input type="submit" class="button" id="submit" value="Change" />
       </p>
</form>

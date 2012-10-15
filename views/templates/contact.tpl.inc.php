<form method="post" class="contactform" action="/contact" enctype="multipart/form-data">
<?
if (!$args['logged_in'])
{
?>
	<fieldset class="contact_details">
		<legend>Contact Details</legend>
		<p>
			<label class="left" for="name">Name</label>
			<input type="text" class="field" id="name" name="name" value="<?=htmlentities($args['name']);?>"/>
		</p>
		<p>
			<label class="left" for="email">Email</label>
			<input type="text" class="field" id="email" name="email" value="<?=htmlentities($args['email']);?>"/>
		</p>
	</fieldset>
<?
}
?>
	<fieldset class="message_content">
        <input type="hidden" name="do" value="yes"/>
		<legend>Message Details</legend>
		<p>
			<label class="left" for="type">Type</label>
			<select class="select" id="type" name="type">
<?
foreach ($args['contact_types'] as $key => $text)
{
?>
			<option value="<?=$key?>"<?=($args['type'] == $key)?" selected":""?>><?=$text?></option>
<?
}
?>
			</select>
		</p>
		<p>
			<label class="left" for="subject">Subject</label> <input type="text" class="field" id="subject" name="subject" value="<?=htmlentities($args['subject']);?>"/>
		</p>
		<p>
			<label class="left" for="message_content">Message</label> <textarea class="field" id="message_content" name="message_content" rows="20" cols="50"><?=htmlentities($args['message_content']);?></textarea>
		</p>
	</fieldset>
	<p>
		<label>&nbsp;</label> <input type="submit" class="button" id="submit" value="Submit" />
	</p>
</form>

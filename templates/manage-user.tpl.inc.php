<div>
<h2>Information</h2>
<table>
  <tbody>
    <tr>
      <td>Username</td>
      <td><?=$this->page_content['user']['username']?></td>
    </tr>
    <tr>
      <td>Name</td>
      <td><?=$this->page_content['user']['name']?></td>
    </tr>
    <tr>
      <td>E-mail</td>
      <td><?=$this->page_content['user']['email']?></td>
    </tr>
    <tr>
      <td>Verified</td>
      <td><?=$this->page_content['user']['verified']?></td>
    </tr>
  </tbody>
</table>

<h2>Rights</h2>
<table>
  <thead>
    <tr>
      <th scope="col">Name</th>
      <th scope="col">Delete</th>
    </tr>
  </thead>
  <tbody>
<?
        foreach($this->page_content['user']['rights'] as $right) {
    	    print("<tr>\n");
	        print("  <td>".$right['name']."</td>\n");
	        print("  <td><a href=\"/manage_user?user_id=".$this->page_content['user']['user_id']."&amp;action=delete_right&amp;right_name=".$right['short_name']."&amp;do=yes&amp;token=".urlencode(get_csrf_token())."\">Delete</a></td>\n");
    	    print("</tr>\n");
        }
?>
  </tbody>
</table>

<form class="contactform" action="/manage_user" method="post">
<fieldset>
  <input type="hidden" name="user_id" value="<?=$this->page_content['user']['user_id']?>" />
  <input type="hidden" name="action" value="add_right" />
  <input type="hidden" name="do" value="yes" />
  <input type="hidden" name="token" value="<?=get_csrf_token()?>"/>
  <legend>Add right</legend>
  <p>
    <label class="left" for="right_name">Right</label>
    <select name="right_name">
<?
        foreach ($this->page_content['rights'] as $right)
	        print("  <option value=\"".$right['short_name']."\">".$right['name']."</option>\n");
?>
    </select>
  </p>
  <div>
    <label>&nbsp;</label> <input type="submit" class="button" value="Add right" />
  </div>
</fieldset>
</form>
</div>

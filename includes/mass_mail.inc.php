<?php
require_once('page_basic.inc.php');

class PageMassMail extends PageBasic
{
    static function get_filter()
    {
        return array(
                'query' => '.*',
                'address' => '.*',
                'title' => '.*',
                'text' => '.*',
                'do' => 'yes|preview'
                );
    }

    static function get_permissions()
    {
        return array(
                'logged_in',
                'user_management'
                );
    }

    function get_title()
    {
        return "Mass mail";
    }

    function do_logic()
    {
        $this->page_content['user_info'] = array();
        $this->page_content['query'] = $this->state['input']['query'];
        $this->page_content['address'] = $this->state['input']['address'];
        $this->page_content['mail_title'] = $this->state['input']['title'];
        $this->page_content['text'] = $this->state['input']['text'];

        // Retrieve users
        //
        $result = $this->database->Query('SELECT u.id, u.username, u.name, u.email FROM users AS u '.$this->state['input']['query'], array());

        foreach ($result as $k => $row) {
            array_push($this->page_content['user_info'], array('id' => $row[0], 'username' => $row[1], 'name' => $row[2], 'email' => $row[3]));
        }

        // Check if page logic is needed
        //
        if (!strlen($this->state['input']['do']))
            return;

        if ($this->state['input']['do'] == 'yes') {
            if (!strlen($this->state['input']['title']) ||
                    !strlen($this->state['input']['text']) ||
                    !strlen($this->state['input']['address']))
            {
                $this->add_message('error', 'One of the input values was empty.', '');
                return;
            }

            foreach ($this->page_content['user_info'] as $users) {
                $text = $this->state['input']['text'];
                $text = preg_replace('/USERNAME/is', $users['username'], $text);
                $text = preg_replace('/NAME/is', $users['name'], $text);
                $text = preg_replace('/EMAIL/is', $users['email'], $text);

                mail($users['email'],
                        SITE_NAME.": ".$this->state['input']['title'],
                        $text,
                        "From: ".$this->state['input']['address']."\r\n");
            }

            $this->add_message('success', 'Mass mail sent', '');
        }
    }

    function display_content()
    {
?>
<form class="contactform" action="/mass_mail" method="post">
  <fieldset>
    <input type="hidden" name="do" value="preview"/>
    <legend>Selection</legend>
    <p>
      <label class="left" for="query">Query</label>
      <input type="text" class="field" id="query" name="query" value="<?=$this->page_content['query']?>"/>
    </p>
    <legend>Message</legend>
    <p>
      <label class="left" for="address">Mail address</label>
      <input type="text" class="field" id="address" name="address" value="<?=$this->page_content['address']?>"/>
    </p>
    <p>
      <label class="left" for="title">Title</label>
      <input type="text" class="field" id="title" name="title" value="<?=$this->page_content['mail_title']?>"/>
    </p>
    <p>
      <label class="left" for="text">Text</label>
      <textarea cols="50" rows="20" class="field" id="text" name="text"><?=$this->page_content['text']?></textarea>
    </p>
    <div>
      <label class="left">&nbsp;</label>
      <input type="submit" class="button" id="submit" value="Preview"/>
    </div>
  </fieldset>
</form>
<p><b>Query:</b><br/>
SELECT u.id, u.username, u.name, u.email FROM users AS u <?=$this->page_content['query']?>
</p>
<table>
  <thead>
    <tr>
      <th scope="col">Id</th>
      <th scope="col">Username</th>
      <th scope="col">Name</th>
      <th scope="col">E-mail</th>
    </tr>
  </thead>
  <tbody>
<?
        if (count($this->page_content['user_info']) == 0) {
        	print("<tr><td colspan=\"4\">No selection</td></tr>\n");
        }

        foreach ($this->page_content['user_info'] as $users) {
        	print("<tr>\n");
        	print("  <td>".$users['id']."</td>\n");
        	print("  <td>".$users['username']."</td>\n");
        	print("  <td>".$users['name']."</td>\n");
        	print("  <td>".$users['email']."</td>\n");
        	print("</tr>\n");
        }
?>
  </tbody>
</table>
<form class="contactform" action="/mass_mail" method="post">
  <fieldset>
    <input type="hidden" name="do" value="yes"/>
    <input type="hidden" name="address" value="<?=$this->page_content['address']?>">
    <input type="hidden" name="title" value="<?=$this->page_content['mail_title']?>">
    <input type="hidden" name="text" value="<?=$this->page_content['text']?>">
    <input type="hidden" name="query" value="<?=$this->page_content['query']?>">
    <legend>Send mass mail</legend>
    <div>
      <label class="left">&nbsp;</label>
      <input type="submit" class="button" id="submit" value="Send"/>
    </div>
  </fieldset>
</form>
<?
    }
};
?>

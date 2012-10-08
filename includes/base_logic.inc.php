<?php
require_once('mail_message.inc.php');
require_once('data_core.inc.php');

class Right extends DataCore
{
    static protected $table_name = 'rights';
    static protected $base_fields = array('short_name', 'name');
};
    
class User extends DataCore
{
    // Error messages
    //
    const RESULT_SUCCESS = 0;
    const ERR_DUPLICATE_EMAIL = 1;
    const ERR_ORIG_PASSWORD_MISMATCH = 2;

    static protected $table_name = 'users';
    static protected $base_fields = array('username', 'name', 'email');

    public $rights;

    protected function fill_complex_fields()
    {
        $result = $this->database->Query('SELECT right_id FROM user_rights AS ur WHERE ur.user_id = ?',
                array($this->id));

        assert('$result !== FALSE /* Failed to retrieve user rights. */');

        foreach($result as $k => $row)
        {
            $right = new Right($this->global_info, $row['right_id']);
            $this->rights[$right->short_name] = $right;
        }
    }

    function check_password($password)
    {
        $result = $this->database->Query('SELECT password FROM users WHERE id = ?',
                array($this->id));

        if ($result === FALSE)
            die('Failed to select data. Exiting!');

        if ($result->RecordCount() != 1)
            return false;

        return ($password == $result->fields['password']);
    }

    function change_password($old_password, $new_password)
    {
        // Check if original password is correct
        //
        $result_check = $this->database->Query('SELECT username FROM users WHERE id=? AND password=?',
                array(
                    $this->id,
                    $old_password
                    ));

        if ($result_check->RecordCount() != 1)
            return User::ERR_ORIG_PASSWORD_MISMATCH;

        // Change password
        //
        if (FALSE === $this->database->Query('UPDATE users SET password=? WHERE id=? AND password=?',
                    array(
                        $new_password,
                        $this->id,
                        $old_password
                        )))
        {
            die("Failed to update data! Exiting!");
        }

        return User::RESULT_SUCCESS;
    }

    function update_password($new_password)
    {
        // Change password
        //
        if (FALSE === $this->database->Query('UPDATE users SET password=? WHERE id=?',
                    array(
                        $new_password,
                        $this->id
                        )))
        {
            die("Failed to update data! Exiting!");
        }

        return User::RESULT_SUCCESS;
    }

    function change_email($email, $require_unique = false)
    {
        if ($require_unique)
        {
            // Check if unique
            //
            $result = $this->database->Query('SELECT id FROM users WHERE email = ?', array($email));

            if ($result->RecordCount() > 0)
                return User::ERR_DUPLICATE_EMAIL;
        }

        // Update and unverify account
        //
        if (FALSE === $this->database->Query('UPDATE users SET email = ?, verified = 0 WHERE id = ?',
                    array($email,
                        $this->id)))
        {
            die("Failed to update data! Exiting!");
        }
        
        $this->email = $email;

        return User::RESULT_SUCCESS;
    }

    function is_verified()
    {
        $result = $this->database->Query('SELECT verified FROM users WHERE id = ?', array($this->id));
        assert('$result !== FALSE /* Failed to retrieve verified status */');
        assert('$result->RecordCount() == 1 /* Did not get single response */');

        return $result->fields['verified'] == 1;
    }

    function set_verified()
    {
        if (FALSE === $this->database->Query('UPDATE users SET verified=1 WHERE id=?',
                    array($this->id)))
        {
            die('Failed to update verified status for user! Exiting!');
        }
    }

    function add_right($short_name)
    {
        if (isset($this->rights[$short_name]))
            return TRUE;
            
        $result = $this->database->InsertQuery('INSERT INTO user_rights SET user_id = ?, right_id = (SELECT id FROM rights WHERE short_name = ?)',
                array(
                    $this->id,
                    $short_name
                    ));
        assert('$result !== FALSE /* Failed to insert user right */');

        $this->rights[$short_name] = Right::get_object($this->global_info, array('short_name' => $short_name));

        return TRUE;
    }

    function delete_right($short_name)
    {
        if (!isset($this->rights[$short_name]))
            return TRUE;
            
        $result = $this->database->Query('DELETE FROM user_rights WHERE id = (SELECT id FROM rights WHERE short_name = ?) AND user_id = ?',
                array(
                    $short_name,
                    $this->id
                    ));

        assert('$result !== FALSE /* Failed to delete user right */');

        unset($this->rights[$short_name]);

        return TRUE;
    }

    function has_right($short_name)
    {
        return isset($this->rights[$short_name]);
    }

    function generate_verify_code()
    {
        $hash = sha1("SuperSecretHashingKey*".SITE_NAME."*".$this->email."*".$this->username."*".$this->id);

        return $hash;
    }

    function send_verify_mail()
    {
        $hash = $this->generate_verify_code();

        $mail = new VerifyMail($this->name, $this->username, $hash);
        $mail->add_recipient($this->email);
        return $mail->send();
    }

    function send_new_password()
    {
        // Generate and store password
        //
        $new_pw = sha1("SuperSecretHashingKey*".mt_rand(0, mt_getrandmax())."*".$this->username."*".time());
        $new_pw = substr($new_pw, 0, 10);

        if (FALSE === $this->database->Query('UPDATE users SET password = ? WHERE id = ?',
                    array(
                        sha1($new_pw),
                        $this->id
                        )))
        {
            die("Failed to update data! Exiting!");
        }

        $mail = new ForgotPasswordMail($this->name, $this->username, $new_pw);
        $mail->add_recipient($this->email);

        return $mail->send();
    }
}

class BaseFactory extends FactoryCore
{
    function get_user($user_id, $type = 'User')
    {
        assert('class_exists($type) /* Selected type does not exist */');
        return new $type($this->global_info, $user_id);
    }

    function get_users($offset = 0, $results = 100)
    {
        return $this->get_core_objects('User', $offset, $results);
    }

    function get_user_by_username($username, $type = 'User')
    {
        return $this->get_core_object($type, array('username' => $username));
    }

    function get_user_by_email($email, $type = 'User')
    {
        return $this->get_core_object($type, array('email' => $email));
    }
};

class ItemStore
{
    protected $data = array();
    protected $id;

    function __construct($id)
    {
        $this->id = $id;
    }

    function get_id()
    {
        return $this->id;
    }

    function invalidate()
    {
        $this->data = array();
    }

    function get_value($tag)
    {
        if (isset($this->data[$tag]))
            return $this->data[$tag];

        return FALSE;
    }

    function set_value($tag, $value)
    {
        $this->data[$tag] = $value;
    }
};

?>

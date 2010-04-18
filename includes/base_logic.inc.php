<?php
require_once('mail_message.inc.php');

class User
{
    // Error messages
    //
    const RESULT_SUCCESS = 0;
    const ERR_DUPLICATE_EMAIL = 1;
    const ERR_ORIG_PASSWORD_MISMATCH = 2;

    protected $database;
    protected $id;

    function __construct($database, $id)
    {
        $this->database = $database;
        $this->id = $id;
    }

    function get_id()
    {
        return $this->id;
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
        
        return User::RESULT_SUCCESS;
    }

    function set_verified()
    {
        if (FALSE === $this->database->Query('UPDATE users SET verified=1 WHERE id=?',
                    array($this->id)))
        {
            die('Failed to update verified status for user! Exiting!');
        }
    }

    function add_right($right_id)
    {
        $result = $this->database->InsertQuery('INSERT INTO user_rights (user_id, right_id) VALUES (?, ?)',
                array(
                    $this->id,
                    $right_id
                    ));

        if ($result === FALSE)
            die('Failed to insert right. Exiting!');

        return $result;
    }

    function delete_right($user_right_id)
    {
        $result = $this->database->Query('DELETE FROM user_rights WHERE id = ? AND user_id = ?',
                array(
                    $user_right_id,
                    $this->id
                    ));

        if ($result === FALSE)
            die('Failed to delete right. Exiting!');
    }
};

class UserBasic extends User
{
    public $username;
    public $name;
    public $email;
    public $verified;

    protected $fields = array('username', 'name', 'email', 'verified');

    function __construct($database, $id)
    {
        parent::__construct($database, $id);

        $result = $this->database->Query('SELECT username, name, email, verified FROM users WHERE id = ?', array($id));
	
        if ($result === FALSE)
            die('Failed to retrieve information.');

        $row = $result->fields;

        foreach ($this->fields as $name)
            $this->$name = $row[$name];

        // Aggregate fields
        //
    }

    function change_email($email, $require_unique = false)
    {
        $result = parent::change_email($email, $require_unique);

        if ($result == User::RESULT_SUCCESS)
            $this->email = $email;

        return $result;
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

class UserFull extends UserBasic
{
    public $permissions = array();

    function __construct($database, $id)
    {
        parent::__construct($database, $id);

        // Add permissions
        //
        $result = $this->database->Query('SELECT r.short_name FROM rights AS r, user_rights AS ur WHERE r.id = ur.right_id AND ur.user_id = ?',
                array($id));

        foreach($result as $k => $row)
            array_push($this->permissions, $row['short_name']);
    }
};

class BaseFactory
{
    protected $database;

    function __construct($database)
    {
        $this->database = $database;
    }

    function get_user($user_id, $type = 'User')
    {
        return new $type($this->database, $user_id);
    }

    function get_user_by_username($username, $type = 'User')
    {
        $result = $this->database->Query('SELECT id FROM users WHERE username = ?',
                    array($username));

        if ($result->RecordCount() != 1) 
            return FALSE;

        return $this->get_user($result->fields['id'], $type);
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

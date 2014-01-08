<?php
require_once('mail_message.inc.php');
require_once('data_core.inc.php');

class Right extends DataCore
{
    static protected $table_name = 'rights';
    static protected $base_fields = array('short_name', 'name');
};

function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
{
    $algorithm = strtolower($algorithm);
    if(!in_array($algorithm, hash_algos(), true))
        die('PBKDF2 ERROR: Invalid hash algorithm.');
    if($count <= 0 || $key_length <= 0)
        die('PBKDF2 ERROR: Invalid parameters.');

    $hash_length = strlen(hash($algorithm, "", true));
    $block_count = ceil($key_length / $hash_length);

    $output = "";
    for($i = 1; $i <= $block_count; $i++) {
        // $i encoded as 4 bytes, big endian.
        $last = $salt . pack("N", $i);
        // first iteration
        $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
        // perform the other $count - 1 iterations
        for ($j = 1; $j < $count; $j++) {
            $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
        }
        $output .= $xorsum;
    }

    if($raw_output)
        return substr($output, 0, $key_length);
    else
        return bin2hex(substr($output, 0, $key_length));
}

class User extends DataCore
{
    // Error messages
    //
    const RESULT_SUCCESS = 0;
    const ERR_DUPLICATE_EMAIL = 1;
    const ERR_ORIG_PASSWORD_MISMATCH = 2;

    static protected $table_name = 'users';
    static protected $base_fields = array('username', 'name', 'email', 'failed_login');

    public $rights = array();

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

    static function new_hash_from_password($password)
    {
        $salt = base64_encode(mcrypt_create_iv(24, MCRYPT_DEV_URANDOM));
        return 'sha256:1000:'.$salt.':'.
                pbkdf2('sha256', $password, $salt, 1000, 24, false);
    }

    function check_password($password)
    {
        $result = $this->database->Query('SELECT solid_password, password FROM users WHERE id = ?',
                array($this->id));

        if ($result === FALSE)
            die('Failed to select data. Exiting!');

        if ($result->RecordCount() != 1)
            return false;

        $solid_password = $result->fields['solid_password'];
        if (!strlen($solid_password))
        {
            if ($password !== $result->fields['password'])
                return FALSE;

            $solid_password = User::new_hash_from_password($password);

            $result = $this->update_field('solid_password', $solid_password);
            assert('$result !== FALSE /* Failed to update solid_password */');

            $result = $this->update_field('password', '');
            assert('$result !== FALSE /* Failed to clear password */');
        }

        $params = explode(":", $solid_password);
        assert('count($params) == 4 /* Solid password format unknown */');

        $pbkdf2_hash = $params[3];
        $pbkdf2_calc = pbkdf2('sha256', $password, $params[2], (int) $params[1],
                              strlen($pbkdf2_hash) / 2, false);

        // Slow compare (time-constant)
        $diff = strlen($pbkdf2_hash) ^ strlen($pbkdf2_calc);
        for ($i = 0; $i < strlen($pbkdf2_hash) && $i < strlen($pbkdf2_calc); $i++)
            $diff |= ord($pbkdf2_hash[$i]) ^ ord($pbkdf2_calc[$i]);

        $result = ($diff === 0);

        if ($result)
            $this->failed_login = 0;
        else
            $this->failed_login++;

        $this->update_field('failed_login', $this->failed_login);

        return $result;
    }

    function change_password($old_password, $new_password)
    {
        // Check if original password is correct
        //
        if ($this->check_password($old_password) !== TRUE)
            return User::ERR_ORIG_PASSWORD_MISMATCH;

        // Change password
        //
        $solid_password = User::new_hash_from_password($new_password);

        $result = $this->update_field('solid_password', $solid_password);
        assert('$result !== FALSE /* Failed to update solid_password */');

        return User::RESULT_SUCCESS;
    }

    function update_password($new_password)
    {
        // Change password
        //
        $solid_password = User::new_hash_from_password($new_password);

        $result = $this->update_field('solid_password', $solid_password);
        assert('$result !== FALSE /* Failed to update solid_password */');

        return User::RESULT_SUCCESS;
    }

    function change_email($email, $require_unique = true)
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

        $result = $this->database->Query('DELETE FROM user_rights WHERE right_id = (SELECT id FROM rights WHERE short_name = ?) AND user_id = ?',
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

    function generate_verify_code($action = '')
    {
        $msg = array('id' => $this->id,
                     'username' => $this->username,
                     'action' => $action,
                     'timestamp' => time());
        $msg_str = json_encode($msg);
        return encode_and_auth_string($msg_str);
    }

    function send_verify_mail()
    {
        $code = $this->generate_verify_code('verify');

        $mail = new VerifyMail($this->name, $this->username, $code);
        $mail->add_recipient($this->email);
        return $mail->send();
    }

    function send_password_reset_mail()
    {
        $code = $this->generate_verify_code('reset_password');

        $mail = new ResetPasswordMail($this->name, $this->username, $code);
        $mail->add_recipient($this->email);
        return $mail->send();
    }

    function send_new_password()
    {
        // Generate and store password
        //
        $new_pw = bin2hex(substr(mcrypt_create_iv(24, MCRYPT_DEV_URANDOM), 0, 10));

        $this->update_password(sha1($new_pw));

        $mail = new ForgotPasswordMail($this->name, $this->username, $new_pw);
        $mail->add_recipient($this->email);

        return $mail->send();
    }

    function get_config_values($module = "")
    {
        $result = $this->database->Query('SELECT name, value FROM user_config_values WHERE user_id = ? AND module = ?',
            array($this->id, $module));

        assert('$result !== FALSE /* Failed to retrieve config values */');

        $info = array();

        foreach ($result as $row)
            $info[$row['name']] = $row['value'];

        return $info;
    }

    function get_config_value($name, $default = "", $module = "")
    {
        $result = $this->database->Query('SELECT value FROM user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            array($this->id, $module, $name));

        assert('$result !== FALSE /* Failed to retrieve config value */');

        if ($result->RecordCount() == 0)
            return $default;

        if ($result->RecordCount() != 1)
            return "";

        return $result->fields['value'];
    }

    function set_config_value($name, $value, $module = "")
    {
        $result = $this->database->Query('INSERT user_config_values SET user_id = ?, module = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?',
            array($this->id, $module, $name, $value, $value));

        assert('$result !== FALSE /* Failed to store config value */');
    }

    function delete_config_value($name, $module = "")
    {
        $result = $this->database->Query('DELETE user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            array($this->id, $module, $name));

        assert('$result !== FALSE /* Failed to delete config value */');
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

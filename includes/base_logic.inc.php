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
    static protected $base_fields = array('username', 'email', 'terms_accepted', 'verified', 'last_login', 'failed_login');

    public $rights = array();

    protected function fill_complex_fields()
    {
        $result = $this->database->Query('SELECT right_id FROM user_rights AS ur WHERE ur.user_id = ?',
                array($this->id));

        verify($result !== FALSE, 'Failed to retrieve user rights.');

        foreach($result as $k => $row)
        {
            $right = new Right($this->global_info, $row['right_id']);
            $this->rights[$right->short_name] = $right;
        }
    }

    static function new_hash_from_password($password)
    {
        $salt = base64_encode(openssl_random_pseudo_bytes(24));
        return 'sha256:1000:'.$salt.':'.
                pbkdf2('sha256', $password, $salt, 1000, 24, false);
    }

    function check_password($password)
    {
        $result = $this->database->Query('SELECT solid_password FROM users WHERE id = ?',
                array($this->id));

        verify($result !== false, 'Failed to retrieve password data');

        if ($result->RecordCount() != 1)
            return false;

        $solid_password = $result->fields['solid_password'];
        $stored_hash = 'stored';
        $calculated_hash = 'calculated';

        $params = explode(":", $solid_password);

        if ($params[0] == 'sha256')
        {
            verify(count($params) == 4, 'Solid password format unknown');

            $stored_hash = $params[3];
            $calculated_hash = pbkdf2('sha256', $password, $params[2], (int) $params[1],
                                 strlen($stored_hash) / 2, false);
        }
        else if ($params[0] == 'dolphin')
        {
            verify(count($params) == 3, 'Solid password format unknown');

            $stored_hash = $params[2];
            $calculated_hash = sha1(md5($password) . $params[1]);
        }
        else
            verify(false, 'Unknown solid password format');

        // Slow compare (time-constant)
        $diff = strlen($stored_hash) ^ strlen($calculated_hash);
        for ($i = 0; $i < strlen($stored_hash) && $i < strlen($calculated_hash); $i++)
            $diff |= ord($stored_hash[$i]) ^ ord($calculated_hash[$i]);

        $result = ($diff === 0);

        if ($result)
        {
            $this->update(array(
                    'failed_login' => 0,
                    'last_login' => time(),
            ));
        }
        else
            $this->increase_field('failed_login');

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
        verify($result !== FALSE, 'Failed to update solid_password');

        return User::RESULT_SUCCESS;
    }

    function update_password($new_password)
    {
        // Change password
        //
        $solid_password = User::new_hash_from_password($new_password);

        $result = $this->update_field('solid_password', $solid_password);
        verify($result !== FALSE, 'Failed to update solid_password');

        $security_iterator = $this->increase_security_iterator();

        return User::RESULT_SUCCESS;
    }

    function change_email($email, $require_unique = true)
    {
        if ($require_unique)
        {
            // Check if unique
            //
            $result = $this->database->Query('SELECT id FROM users WHERE LOWER(email) = LOWER(?)', array($email));

            if ($result->RecordCount() > 0)
                return User::ERR_DUPLICATE_EMAIL;
        }

        // Update account
        //
        $result = $this->update_field('email', $email);
        verify($result !== false, 'Failed to change email');

        fire_hook('change_email', array(
                                    'user_id' => $this->id,
                                    'old_email' => $this->email,
                                    'new_email' => $email));

        return User::RESULT_SUCCESS;
    }

    function send_change_email_verify($email, $require_unique = true)
    {
        if ($require_unique)
        {
            // Check if unique
            //
            $result = $this->database->Query('SELECT id FROM users WHERE LOWER(email) = LOWER(?)', array($email));

            if ($result->RecordCount() > 0)
                return User::ERR_DUPLICATE_EMAIL;
        }

        $security_iterator = $this->increase_security_iterator();

        $code = $this->generate_verify_code('change_email', array('email' => $email, 'iterator' => $security_iterator));
        $config = $this->global_info['config'];
        $verify_url = 'https://'.$config['server_name'].
                      $config['pages']['change_email']['verify_page'].
                      '?code='.$code;

        $result = SenderCore::send('change_email_verification_link', $email,
                                array(
                                    'user' => $this,
                                    'verify_url' => $verify_url,
                                ));
        if ($result == true)
            return USER::RESULT_SUCCESS;

        return false;
    }

    function is_verified()
    {
        return $this->verified == 1;
    }

    function set_verified()
    {
        $result = $this->update_field('verified', 1);
        verify($result !== false, 'Failed to update verified status');
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
        verify($result !== FALSE, 'Failed to insert user right');

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

        verify($result !== FALSE, 'Failed to delete user right');

        unset($this->rights[$short_name]);

        return TRUE;
    }

    function has_right($short_name)
    {
        return isset($this->rights[$short_name]);
    }

    function generate_verify_code($action = '', $params = array())
    {
        $msg = array('id' => $this->id,
                     'username' => $this->username,
                     'action' => $action,
                     'params' => $params,
                     'timestamp' => time());
        $msg_str = json_encode($msg);
        return encode_and_auth_string($msg_str);
    }

    function send_verify_mail($after_verify_data = array())
    {
        $code = $this->generate_verify_code('verify', $after_verify_data);
        $config = $this->global_info['config'];
        $verify_url = 'https://'.$config['server_name'].
                      $config['pages']['login']['verify_page'].
                      '?code='.$code;

        return SenderCore::send('email_verification_link', $this->email,
                                array(
                                    'user' => $this,
                                    'verify_url' => $verify_url,
                                ));
    }

    function increase_security_iterator()
    {
        $security_iterator = (int) $this->get_config_value('security_iterator', 0, 'account');
        $security_iterator += 1;
        $this->set_config_value('security_iterator', $security_iterator, 'account');

        return $security_iterator;
    }

    function get_security_iterator()
    {
        return $this->get_config_value('security_iterator', 0, 'account');
    }

    function send_password_reset_mail()
    {
        $security_iterator = $this->increase_security_iterator();

        $code = $this->generate_verify_code('reset_password', array('iterator' => $security_iterator));
        $config = $this->global_info['config'];
        $reset_url = 'https://'.$config['server_name'].
                     $config['pages']['forgot_password']['reset_password_page'].
                     '?code='.$code;

        return SenderCore::send('password_reset', $this->email,
                                array(
                                    'user' => $this,
                                    'reset_url' => $reset_url,
                                ));
    }

    function send_new_password()
    {
        // Generate and store password
        //
        $new_pw = bin2hex(substr(openssl_random_pseudo_bytes(24), 0, 10));

        $this->update_password($new_pw);

        return SenderCore::send('new_password', $this->email,
                                array(
                                    'user' => $this,
                                    'password' => $new_pw,
                                ));
    }

    function get_config_values($module = "")
    {
        $result = $this->database->Query('SELECT name, value FROM user_config_values WHERE user_id = ? AND module = ?',
            array($this->id, $module));

        verify($result !== FALSE, 'Failed to retrieve config values');

        $info = array();

        foreach ($result as $row)
            $info[$row['name']] = $row['value'];

        return $info;
    }

    function get_config_value($name, $default = "", $module = "")
    {
        $result = $this->database->Query('SELECT value FROM user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            array($this->id, $module, $name));

        verify($result !== FALSE, 'Failed to retrieve config value');

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

        verify($result !== FALSE, 'Failed to store config value');
    }

    function delete_config_value($name, $module = "")
    {
        $result = $this->database->Query('DELETE user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            array($this->id, $module, $name));

        verify($result !== FALSE, 'Failed to delete config value');
    }
}

class BaseFactory extends FactoryCore
{
    function get_user($user_id, $type = 'User')
    {
        verify(class_exists($type), 'Selected type does not exist');
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

    function create_user($username, $password, $email, $terms_accepted, $type = 'User')
    {
        $solid_password = User::new_hash_from_password($password);

        $user = $this->create_core_object($type, array(
                            'username' => $username,
                            'solid_password' => $solid_password,
                            'email' => $email,
                            'terms_accepted' => $terms_accepted,
                            'registered' => time(),
                ));
        verify($user !== false, 'Failed to create new user');

        return $user;
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

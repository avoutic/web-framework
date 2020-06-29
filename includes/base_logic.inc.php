<?php
require_once(WF::$includes.'data_core.inc.php');
require_once(WF::$includes.'config_values.inc.php');

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
    const ERR_NEW_PASSWORD_TOO_WEAK = 3;

    static protected $table_name = 'users';
    static protected $base_fields = array('username', 'email', 'terms_accepted', 'verified', 'last_login', 'failed_login');

    public $rights = array();
    protected $user_config = null;

    protected function fill_complex_fields()
    {
        $query = <<<SQL
        SELECT right_id
        FROM user_rights AS ur
        WHERE ur.user_id = ?
SQL;

        $result = $this->query($query, array($this->id));
        WF::verify($result !== false, 'Failed to retrieve user rights.');

        foreach($result as $k => $row)
        {
            $right = Right::get_object_by_id($row['right_id']);
            WF::verify($right !== false, 'Failed to retrieve right');

            $this->rights[$right->short_name] = $right;
        }
    }

    static function new_hash_from_password($password)
    {
        $salt = base64_encode(openssl_random_pseudo_bytes(24));
        return 'sha256:1000:'.$salt.':'.
                pbkdf2('sha256', $password, $salt, 1000, 24, false);
    }

    protected function get_custom_hash($params, $password)
    {
        return false;
    }

    function check_password($password)
    {
        $solid_password = $this->get_field('solid_password');
        $stored_hash = 'stored';
        $calculated_hash = 'calculated';

        $params = explode(":", $solid_password);
        $migrate_password = false;

        if ($params[0] == 'sha256')
        {
            WF::verify(count($params) == 4, 'Solid password format unknown');

            $stored_hash = $params[3];
            $calculated_hash = pbkdf2('sha256', $password, $params[2], (int) $params[1],
                                 strlen($stored_hash) / 2, false);
        }
        else if ($params[0] == 'bootstrap')
        {
            WF::verify(count($params) == 2, 'Solid password format unknown');

            $stored_hash = $params[1];
            $calculated_hash = $password;
            $migrate_password = true;
        }
        else if ($params[0] == 'dolphin')
        {
            WF::verify(count($params) == 3, 'Solid password format unknown');

            $stored_hash = $params[2];
            $calculated_hash = sha1(md5($password) . $params[1]);
            $migrate_password = true;
        }
        else
        {
            $result = $this->get_custom_hash($params, $password);
            WF::verify($result !== false, 'Unknown solid password format');
            WF::verify(isset($result['stored_hash']), 'Invalid result from get_custom_hash');
            WF::verify(isset($result['calculated_hash']), 'Invalid result from get_custom_hash');

            $stored_hash = $result['stored_hash'];
            $calculated_hash = $result['calculated_hash'];
            $migrate_password = true;
        }

        // Slow compare (time-constant)
        $diff = strlen($stored_hash) ^ strlen($calculated_hash);
        for ($i = 0; $i < strlen($stored_hash) && $i < strlen($calculated_hash); $i++)
            $diff |= ord($stored_hash[$i]) ^ ord($calculated_hash[$i]);

        $result = ($diff === 0);

        if ($result)
        {
            if ($migrate_password)
            {
                $solid_password = User::new_hash_from_password($password);
                $this->update_field('solid_password', $solid_password);
            }

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
        if ($this->check_password($old_password) !== true)
            return User::ERR_ORIG_PASSWORD_MISMATCH;

        if (strlen($new_password) < 8)
            return User::ERR_NEW_PASSWORD_TOO_WEAK;

        // Change password
        //
        $solid_password = User::new_hash_from_password($new_password);
        $this->update_field('solid_password', $solid_password);

        return User::RESULT_SUCCESS;
    }

    function update_password($new_password)
    {
        // Change password
        //
        $solid_password = User::new_hash_from_password($new_password);

        $this->update_field('solid_password', $solid_password);

        $security_iterator = $this->increase_security_iterator();

        return User::RESULT_SUCCESS;
    }

    function change_email($email, $require_unique = true)
    {
        if ($require_unique)
        {
            // Check if unique
            //
            $query = <<<SQL
            SELECT id
            FROM users
            WHERE LOWER(email) = LOWER(?)
SQL;

            $result = $this->query($query, array($email));

            if ($result->RecordCount() > 0)
                return User::ERR_DUPLICATE_EMAIL;
        }

        // Update account
        //
        $updates = array(
            'email' => $email,
        );

        if ($this->get_config('authenticator.unique_identifier') == 'email')
            $updates['username'] = $email;

        $this->update($updates);

        WF::fire_hook('change_email', array(
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
            $result = $this->query('SELECT id FROM users WHERE LOWER(email) = LOWER(?)', array($email));

            if ($result->RecordCount() > 0)
                return User::ERR_DUPLICATE_EMAIL;
        }

        $security_iterator = $this->increase_security_iterator();

        $code = $this->generate_verify_code('change_email', array('email' => $email, 'iterator' => $security_iterator));
        $verify_url = 'https://'.$this->get_config('server_name').
                      $this->get_config('pages.change_email.verify_page').
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
        $this->update_field('verified', 1);
    }

    function add_right($short_name)
    {
        if (isset($this->rights[$short_name]))
            return true;

        $query = <<<SQL
        INSERT INTO user_rights
        SET user_id = ?,
            right_id = ( SELECT id
                         FROM rights
                         WHERE short_name = ?
                       )
SQL;

        $result = $this->insert_query($query,
                array(
                    $this->id,
                    $short_name
                    ));
        WF::verify($result !== false, 'Failed to insert user right');

        $this->rights[$short_name] = Right::get_object(array('short_name' => $short_name));

        return true;
    }

    function delete_right($short_name)
    {
        if (!isset($this->rights[$short_name]))
            return true;

        $query = <<<SQL
        DELETE FROM user_rights
        WHERE right_id = ( SELECT id
                           FROM rights
                           WHERE short_name = ?
                         ) AND
              user_id = ?
SQL;


        $result = $this->query($query,
                array(
                    $short_name,
                    $this->id
                    ));

        WF::verify($result !== false, 'Failed to delete user right');

        unset($this->rights[$short_name]);

        return true;
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

        return encode_and_auth_array($msg);
    }

    function send_verify_mail($after_verify_data = array())
    {
        $code = $this->generate_verify_code('verify', $after_verify_data);
        $verify_url = 'https://'.$this->get_config('server_name').
                      $this->get_config('pages.login.verify_page').
                      '?code='.$code;

        return SenderCore::send('email_verification_link', $this->email,
                                array(
                                    'user' => $this,
                                    'verify_url' => $verify_url,
                                ));
    }

    function increase_security_iterator()
    {
        $security_iterator = (int) $this->get_config_value('account', 'security_iterator', 0);
        $security_iterator += 1;
        $this->set_config_value('account', 'security_iterator', $security_iterator);

        return $security_iterator;
    }

    function get_security_iterator()
    {
        return $this->get_config_value('account', 'security_iterator', 0);
    }

    function send_password_reset_mail()
    {
        $security_iterator = $this->increase_security_iterator();

        $code = $this->generate_verify_code('reset_password', array('iterator' => $security_iterator));
        $reset_url = 'https://'.$this->get_config('server_name').
                     $this->get_config('pages.forgot_password.reset_password_page').
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

    protected function get_config_store()
    {
        if ($this->user_config == null)
            $this->user_config = new userConfigValues($this->database, $this->id);

        return $this->user_config;
    }

    function get_config_values($module = "")
    {
        $config = $this->get_config_store();

        return $config->get_values($module);
    }

    function get_config_value($module, $name, $default = '')
    {
        $config = $this->get_config_store();

        return $config->get_value($name, $default, $module);
    }

    function set_config_value($module, $name, $value)
    {
        $config = $this->get_config_store();

        return $config->set_value($name, $value, $module);
    }

    function delete_config_value($module, $name)
    {
        $config = $this->get_config_store();

        return $config->delete_value($name, $module);
    }
}

class BaseFactory extends FactoryCore
{
    function get_user($user_id, $type = 'User')
    {
        WF::verify(class_exists($type), 'Class does not exist');

        return $type::get_object_by_id($user_id);
    }

    function get_users($offset = 0, $results = 10, $type = 'User')
    {
        WF::verify(class_exists($type), 'Class does not exist');

        return $type::get_objects($offset, $results);
    }

    function get_user_by_username($username, $type = 'User')
    {
        WF::verify(class_exists($type), 'Class does not exist');

        return $type::get_object(array('username' => $username));
    }

    function get_user_by_email($email, $type = 'User')
    {
        WF::verify(class_exists($type), 'Class does not exist');

        return $type::get_object(array('email' => $email));
    }

    function search_users($string, $type = 'User')
    {
        $query = <<<SQL
        SELECT id
        FROM users
        WHERE id = ? OR
              username LIKE ? OR
              email LIKE ?
SQL;

        $result = $this->query($query, array(
                        $string,
                        "%{$string}%",
                        "%{$string}%",
                    ));
        WF::verify($result !== false, 'Failed to search users');

        $data = array();
        foreach ($result as $row)
        {
            $user = $this->get_user($row['id'], $type);
            array_push($data, $user);
        }

        return $data;
    }

    function create_user($username, $password, $email, $terms_accepted, $type = 'User')
    {
        WF::verify(class_exists($type), 'Class does not exist');

        $solid_password = User::new_hash_from_password($password);

        $user = $type::create(array(
                            'username' => $username,
                            'solid_password' => $solid_password,
                            'email' => $email,
                            'terms_accepted' => $terms_accepted,
                            'registered' => time(),
                ));
        WF::verify($user !== false, 'Failed to create new user');

        return $user;
    }
};
?>

<?php
class PageUserOverview extends PageBasic
{
    static function get_permissions()
    {
        return array(
                'logged_in',
                'user_management'
                );
    }

    function get_title()
    {
        return "User overview";
    }

    function do_logic()
    {
        // Retrieve users
        //
        $result = $this->query('SELECT id, username, name, email FROM users ORDER BY username', array());

        $this->page_content['user_info'] = array();

        foreach ($result as $k => $row) {
            array_push($this->page_content['user_info'], array('id' => $row[0], 'username' => $row[1], 'name' => $row[2], 'email' => $row[3]));
        }
    }

    function display_content()
    {
        $this->load_template('user_overview.tpl', $this->page_content);
    }
};
?>

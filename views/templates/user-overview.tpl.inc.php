<table>
  <thead>
    <tr>
      <th scope="col">Username</th>
      <th scope="col">Name</th>
      <th scope="col">E-mail</th>
      <th scope="col">Grab</th>
    </tr>
  </thead>
  <tbody>
<?
        foreach ($args['user_info'] as $users) {
        	print("<tr>\n");
            print("  <td><a href=\"/manage_user?user_id=".$users['id']."\">".$users['username']."</a></td>\n");
            print("  <td>".$users['name']."</td>\n");
            print("  <td>".$users['email']."</td>\n");
            print("  <td><a href=\"/grab_identity?user_id=".$users['id']."\">Grab ".$users['username']."</a></td>\n");
            print("</tr>\n");
        }
?>
  </tbody>
</table>

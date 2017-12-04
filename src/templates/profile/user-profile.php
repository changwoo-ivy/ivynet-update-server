<?php
/**
 * @var WP_User $profileuser
 */
?>
  <h2>IUS Profile</h2>
  <table class="form-table">
    <tbody>
    <tr>
      <th>
        <label for="ius_github_personal_access_token">
          Github Personal Access Token
        </label>
      </th>
      <td>
        <input type="password"
               class="text"
               id="ius_github_personal_access_token"
               name="ius_github_personal_access_token"
               value="<?php echo esc_attr(
                   get_user_meta( $profileuser->ID, 'ius_github_personal_access_token', TRUE ) );
               ?>"/>
        <br/>
        <span class="description">
        Settings &gt; Developer settings &gt; <a href="https://github.com/settings/tokens" target="_blank">Personal access tokens</a>
      </span>
      </td>
    </tr>
    </tbody>
  </table>
<?php wp_nonce_field( 'ius_profile_98^25bse$#', 'ius-nonce', FALSE );

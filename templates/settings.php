<?php
// javascript
script('ldapcontacts', 'settings');
?>
<form id="ldapcontacts">
	<div class="section">
		<h2><?php p($l->t( 'LDAP Contacts' )); ?></h2>
		<table>
			<tbody>
				<tr>
					<td><label for="ldapcontacts_login_attribute"><?php p($l->t( 'Login Attribute' )); ?></label></td>
					<td><input type="text" id="ldapcontacts_login_attribute" placeholder="<?php p($l->t( 'Login Attribute' )); ?>" value="<?php p( \OCP\Config::getAppValue( 'ldapcontacts', 'login_attribute', '' ) ); ?>"></td>
				</tr>
				<tr>
					<td><label for="ldapcontacts_edit_login_url"><?php p($l->t( 'Edit Login URL' )); ?></label></td>
					<td><input type="url" id="ldapcontacts_edit_login_url" placeholder="<?php p($l->t( 'URL' )); ?>" value="<?php p( \OCP\Config::getAppValue( 'ldapcontacts', 'edit_login_url', '' ) ); ?>"></td>
				</tr>
			</tbody>
		</table>
		
		<button type="submit"><?php p($l->t( 'Save' )); ?></button>
	</div>
</form>




<?php
/*
<form id="external">
	<div class="section">
		<h2><?php p($l->t('External sites'));?></h2>
		<p>
			<em><?php p($l->t('Please note that some browsers will block displaying of sites via http if you are running https.')); ?></em>
			<br>
			<em><?php p($l->t('Furthermore please note that many sites these days disallow iframing due to security reasons.')); ?></em>
			<br>
			<em><?php p($l->t('We highly recommend to test the configured sites below properly.')); ?></em>
		</p>
		<ul class="external_sites">

		<?php
		$sites = \OCA\External\External::getSites();
		for($i = 0; $i < sizeof($sites); $i++) {
			print_unescaped('<li>
			<input type="text" class="site_name" name="site_name[]" value="'.OCP\Util::sanitizeHTML($sites[$i][0]).'" placeholder="'.$l->t('Name').'" />
			<input type="text" class="site_url"  name="site_url[]"  value="'.OCP\Util::sanitizeHTML($sites[$i][1]).'" placeholder="'.$l->t('URL').'" />
			<select class="site_icon" name="site_icon[]">');
			$nf = true;
			foreach($_['images'] as $image) {
				if (basename($image) == $sites[$i][2]) {
					print_unescaped('<option value="'.basename($image).'" selected>'.basename($image).'</option>');
					$nf = false;
				} else {
					print_unescaped('<option value="'.basename($image).'">'.basename($image).'</option>');
				}
			}
			if($nf) {
				print_unescaped('<option value="" selected>'.$l->t('Select an icon').'</option>');
			} else {
				print_unescaped('<option value="">'.$l->t('Select an icon').'</option>');
			}
			print_unescaped('</select>
			<img class="svg action delete_button" src="'.OCP\image_path("", "actions/delete.svg") .'" title="'.$l->t("Remove site").'" />
			</li>');
		}
		if(sizeof($sites) === 0) {
			print_unescaped('<li>
			<input type="text" class="site_name" name="site_name[]" value="" placeholder="'.$l->t('Name').'" />
			<input type="text" class="site_url"  name="site_url[]"  value="" placeholder="'.$l->t('URL').'" />
			<select class="site_icon" name="site_icon[]">');
			foreach($_['images'] as $image) {
				print_unescaped('<option value="'.basename($image).'">'.basename($image).'</option>');
			}
			print_unescaped('<option value="" selected>'.$l->t('Select an icon').'</option>
			</select>
			<img class="svg action delete_button" src="'.OCP\image_path("", "actions/delete.svg") .'" title="'.$l->t("Remove site").'" />
			</li>');
		}

		?>

		</ul>

        <input type="button" id="add_external_site" value="<?php p($l->t("Add")); ?>" />
		<span class="msg"></span>
	</div>
</form>
*/
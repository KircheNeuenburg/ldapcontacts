<?php
// javascript
script('ldapcontacts', 'script');
// styles
style('ldapcontacts', 'style');
style('ldapcontacts', 'tutorial');
// available data
$data = array( 'mail' => 'Mail', 'givenname' => 'First Name', 'sn' => 'Last Name', 'street' => 'Street', 'postaladdress' => 'House number', 'postalcode' => 'zip Code', 'l' => 'City', 'homephone' => 'Phone', 'mobile' => 'Mobile', 'description' => 'About me' );
?>
<div id="app">
	<div id="app-navigation">
		<script id="navigation-header-tpl" type="text/x-handlebars-template">
			<ul>
				<li><span><input type="search" id="search_ldap_contacts"><span class="abort"></span></span></li>
				<li><select id="ldap_contacts_group_selector">
					<option value="all"><?php p($l->t( "All" ) ); ?></option>
					{{#each groups}}
						<option value="{{ id }}">{{ cn }}</option>
					{{/each}}
				</select></li>
			</ul>
		</script>
		<div id="navigation-header"><div class="icon-loading centered"></div></div>
		
		
		<script id="navigation-tpl" type="text/x-handlebars-template">
			<ul>
				{{#if contacts}}
					{{#each contacts}}
						<li class="contact {{#if active}}active{{/if}}"  data-id="{{ id }}">
							<a href="#">{{ name }}</a>
						</li>
					{{/each}}
				{{else}}
					<li class="not-found"><span><?php p($l->t('No Matches')); ?></span></li>
				{{/if}}
			</ul>
		</script>
		<div class="info"><div class="icon-loading centered"></div></div>
		
		<script id="settings-tpl" type="text/x-handlebars-template">
			<ul><li class="nav-edit with-icon"><a href="#" class="icon-edit svg"><?php p($l->t('Edit Own Data')); ?></a></li></ul>
		</script>
		<div id="app-settings"><div class="icon-loading centered"></div></div>
	</div>

	<div id="app-content">
		<script id="content-tpl" type="text/x-handlebars-template">
			{{#if contact}}
				<h2>{{#if contact.name}}{{ contact.name }}{{/if}}</h2>
				<table>
					<tbody>
						<?php
						foreach( $data as $key => $name ) {
							echo '{{#if contact.'; p( $key ); echo '}}<tr><td>'; p($l->t( $name )); echo '</td> <td><span>{{ contact.'; p( $key ); echo ' }}</span></td> <td><a class="icon-copy" href="#" title="'; p($l->t( 'Copy to clipboard' )); echo '"></a></td></tr>{{/if}}';
						}
						?>
						{{#if contact.groups }}
							<tr>
								<td><?php p($l->t( 'Groups' )); ?></td>
								<td>
								{{#each contact.groups}}
									{{#if cn}}{{ cn }}<br>{{/if}}
								{{/each}}
								</td>
								<td></td>
							</tr>
						{{/if}}
					</tbody>
				</table>
			{{else}}
				<h3><?php p($l->t('Select a contact from the list to view details')); ?></h3>
			{{/if}}
		</script>
		
		<script id="content-edit-tpl" type="text/x-handlebars-template">
			<h2><?php p($l->t('Edit Own Data')); ?></h2>
			
			{{#if saved}}
				{{#if save_failed}}
					<div class="alert alert-danger"><?php p($l->t( 'Something went wrong while saving your data' )); ?></div>
				{{else}}
					<div class="alert alert-success"><?php p($l->t( 'Your data has successfully been saved' )); ?></div>
				{{/if}}
			{{/if}}
			
			{{#if me}}
				<?php if( \OCP\Config::getAppValue( 'ldapcontacts', 'edit_login_url', '' ) != '' ) { ?>
					<b><?php p($l->t( 'If you want to change your login data, use the following link:' )) ?> </b>
					<a href="<?php p( \OCP\Config::getAppValue( 'ldapcontacts', 'edit_login_url', '' ) ); ?>"><?php p($l->t( 'Edit login' )); ?></a>
					<br>
				<?php } ?>
				
				<table class="own">
					<tbody>
						<?php
						foreach( $data as $key => $name ) {
							// don't show the login attribute here
							if( $key == \OCP\Config::getAppValue( 'ldapcontacts', 'login_attribute', '' ) ) continue;
							echo '<tr>';
								echo '<td><label for="edit_'; p( $key ); echo'">'; p($l->t($name)); echo'</label></td>';
								echo '<td><input type="text" name="'; p( $key ); echo '" id="edit_'; p( $key ); echo'" value="{{#if me.'; p($key); echo ' }}{{ me.'; p( $key ); echo ' }}{{/if}}"></td>';
							echo '</tr>';
						}
						?>
					</tbody>
				</table>
				<button><?php p($l->t('Save')); ?></button>
			{{else}}
				<h3><?php p($l->t('No contact data could be found')); ?></h3>
			{{/if}}
		</script>
		<div id="info"><div class="icon-loading centered"></div></div>
	</div>
</div>

<script id="tutorial-tpl" type="text/x-handlebars-template">
	<div id="tutorial-container" style="display: none">
		<div class="body">
			{{ message }}
		</div>
		<div class="footer">
			<button id="tutorial-next"><?php p($l->t( 'Got it' )); ?></button>
		</div>
	</div>
</script>

<div id="tutorial-translations" style="display: none">
	<p><?php p($l->t( 'Search all contacts' )); ?></p>
	<p><?php p($l->t( 'Here you can restrict your search to members of a certain group' )); ?></p>
	<p><?php p($l->t( 'Select a contact from the list to view details' )); ?></p>
	<p><?php p($l->t( 'Down here you can edit your own data' )); ?></p>
</div>

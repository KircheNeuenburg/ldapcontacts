<?php
// javascript
script( 'ldapcontacts', [ 'settings', 'statistics', 'Chart.min' ] );
// style
style( 'ldapcontacts', [ 'settings', 'statistics' ] );
?>
<div id="ldapcontacts">
	<div class="section">
		<h2><?php p($l->t( 'LDAP Contacts' )); ?></h2>
        
        <script id="ldapcontacts-general-settings-tpl" type="text/x-handlebars-template">
			<form id="ldapcontacts-general-settings-form">
            <table>
                <tbody>
                    <tr>
                        <td><label for="ldapcontacts_form_login_attribute"><?php p($l->t( 'Login Attribute' )); ?></label></td>
                        <td><input type="text" id="ldapcontacts_form_login_attribute" name="login_attribute" placeholder="<?php p($l->t( 'Login Attribute' )); ?>" value="{{ settings.login_attribute }}"></td>
                    </tr>
                    <tr>
                        <td><label for="ldapcontacts_form_user_group_id_attribute"><?php p($l->t( 'LDAP User attribute used for group membership' )); ?></label></td>
                        <td><input type="text" id="ldapcontacts_form_user_group_id_attribute" name="user_group_id_attribute" placeholder="<?php p($l->t( 'User Attribute' )); ?>" value="{{ settings.user_group_id_attribute }}"></td>
                    </tr>
                    <tr>
                        <td><label for="ldapcontacts_form_user_group_id_group_attribute"><?php p($l->t( 'LDAP Group attribute used for group membership' )); ?></label></td>
                        <td><input type="text" id="ldapcontacts_form_user_group_id_group_attribute" name="user_group_id_group_attribute" placeholder="<?php p($l->t( 'Group Attribute' )); ?>" value="{{ settings.user_group_id_group_attribute }}"></td>
                    </tr>
                    <tr>
                        <td><label for="ldapcontacts_form_edit_login_url"><?php p($l->t( 'Edit Login URL' )); ?></label></td>
                        <td><input type="url" id="ldapcontacts_form_edit_login_url" name="edit_login_url" placeholder="<?php p($l->t( 'URL' )); ?>" value="{{ settings.edit_login_url }}"></td>
                    </tr>
                    <tr>
                        <td><label for="ldapcontacts_form_entry_id_attribute"><?php p($l->t( 'Unique LDAP Entry Identifier' )); ?></label></td>
                        <td><input type="text" id="ldapcontacts_form_entry_id_attribute" name="entry_id_attribute" placeholder="<?php p($l->t( 'Unique LDAP Entry Identifier' )); ?>" value="{{ settings.entry_id_attribute }}"></td>
                    </tr>
                </tbody>
            </table>
			
			<span class="msg ldapcontacts-settings-msg"></span><br>
			
			<br><h3><?php p($l->t( 'LDAP search filter preview' )); ?></h3><br>
			
			<table class="inline">
				<thead>
					<tr><th colspan="2"><?php p($l->t( 'Example of an LDAP user' )); ?></th></tr>
				</thead>
				<tbody>
					<tr><th>objectclass</td><td>inetOrgPerson</th></tr>
					<tr><th>{{ settings.entry_id_attribute }}</th><td>AA00BB</td></tr>
					<tr><th>{{ settings.user_group_id_attribute }}</th><td>USER</td></tr>
				</tbody>
			</table>
			<table class="inline">
				<thead>
					<tr><th colspan="2"><?php p($l->t( 'Example of an LDAP group' )); ?></th></tr>
				</thead>
				<tbody>
					<tr><th>objectclass</th><td>posixGroup</td></tr>
					<tr><th>{{ settings.entry_id_attribute }}</th><td>BB00CC</td>
				</tbody>
			</table>
			
			<table>
                <tbody>
                    <tr>
						<td><label><?php p($l->t( 'Filter for getting all users' ));?></label></td>
						<td><div class="code">{{ search_filter_preview.user }}</div></td>
					</tr>
					<tr>
						<td><label><?php p($l->t( 'Filter for getting specific user' ));?></label></td>
						<td><div class="code">{{ search_filter_preview.user_specific }}</div></td>
					</tr>
					<tr>
						<td><label><?php p($l->t( 'Filter for getting all groups' ));?></label></td>
						<td><div class="code">{{ search_filter_preview.group }}</div></td>
					</tr>
					<tr>
						<td><label><?php p($l->t( 'Filter for getting all groups a specific user is a member of' ));?></label></td>
						<td><div class="code">{{ search_filter_preview.group_specific }}</div></td>
					</tr>
				</tbody>
			</table>
			
			<br><h3><?php p($l->t( 'LDAP User Attributes' )); ?></h3><br>
			<small><?php p($l->t( 'Define LDAP attributes the users can see and edit' )); ?></small>
			
			<table class="ldap-attributes">
			<thead>
				<tr>
					<th><b><?php p($l->t( 'LDAP Attribute' )); ?></b></th>
					<th><b><?php p($l->t( 'Label' )); ?></b></th>
				</tr>
			</thead>
			<tbody>
			{{#each settings.user_ldap_attributes}}
				<tr id="user_ldap_attributes_{{@index}}">
					<td><input type="text" name="user_ldap_attributes[{{@index}}]['attribute']" placeholder="<?php p($l->t( 'LDAP Attribute' )); ?>" value="{{@key}}"></td>
					<td><input type="text" name="user_ldap_attributes[{{@index}}]['label']" placeholder="<?php p($l->t( 'Label' )); ?>" value="{{ this }}"></td>
					<td><button type="button" class="remove-attribute icon icon-delete" attribute="{{@key}}"></button></td>
				</tr>
			{{/each}}
			
			</tbody>
			</table>
			
			</form>
            
			<button class="add-attribute"><span class="icon icon-add"></span><?php p($l->t( 'Add Attribute' )); ?></button>
        </script>
		
		<script id="ldapcontacts-general-settings-new-attribute-tpl" type="text/x-handlebars-template">
			<tr id="user_ldap_attributes_{{ index }}">
				<td><input type="text" name="user_ldap_attributes[{{ index }}]['attribute']" placeholder="<?php p($l->t( 'LDAP Attribute' )); ?>"></td>
				<td><input type="text" name="user_ldap_attributes[{{ index }}]['label']" placeholder="<?php p($l->t( 'Label' )); ?>"></td>
				<td><button type="button" class="remove-attribute icon icon-delete" new_attribute="true"></button></td>
			</tr>
		</script>
		
        <div id="ldapcontacts-general-settings"><span class="icon-loading"></span></div>
        <span class="msg ldapcontacts-settings-msg"></span>
		<br>
		
		<!-- show and hide users section -->
		<script id="ldapcontacts-edit-user-tpl" type="text/x-handlebars-template">
			<div class="search-container">
				<span class="search"><input type="search" id="ldapcontacts-search-visible" placeholder="<?php p($l->t('Hide User')); ?>"><span class="abort"></span></span>
				<div class="search-suggestions"></div>
			</div>
			
			{{#if hidden}}
				<div class="container">
					{{#each hidden}}
						<span class="edit-user">
							<span class="name">{{ ldapcontacts_name }}</span><span class="remove" target-id="{{ ldapcontacts_entry_id }}">X</span>
						</span>
					{{/each}}
				</div>
			{{else}}
				<b><?php p($l->t('No users are hidden')); ?></b>
			{{/if}}
		</script>
		
		<br><h3><?php p($l->t('Hidden Users')); ?></h3><span id="ldapcontacts-edit-user-msg" class="msg"></span>
		<div id="ldapcontacts-edit-user"><div class="icon-loading"></div></div>
		
		<br>
		
		<!-- show and hide groups section -->
		<script id="ldapcontacts-edit-group-tpl" type="text/x-handlebars-template">
			<div class="search-container">
				<span class="search"><input type="search" id="ldapcontacts-search-groups-visible" placeholder="<?php p($l->t('Hide Group')); ?>"><span class="abort"></span></span>
				<div class="search-suggestions"></div>
			</div>
			
			{{#if hidden}}
				<div class="container">
					{{#each hidden}}
						<span class="edit-group">
							<span class="name">{{ ldapcontacts_name }}</span><span class="remove" target-id="{{ ldapcontacts_entry_id }}">X</span>
						</span>
					{{/each}}
				</div>
			{{else}}
				<b><?php p($l->t('No groups are hidden')); ?></b>
			{{/if}}
		</script>
		
		<br><h3><?php p($l->t('Hidden Groups')); ?></h3><span id="ldapcontacts-edit-group-msg" class="msg"></span>
		<div id="ldapcontacts-edit-group"><div class="icon-loading"></div></div>
        
        <br><h3><?php p($l->t( 'Statistics' )); ?></h3>
    
        <script id="ldapcontacts-stat-tpl" type="text/x-handlebars-template">
            <div class="stat">
                <h2 class="title">{{ title }}</h2>

                <canvas id="{{ id }}"></canvas>

                {{#if total}}
                    <h3 class="total"><?php p($l->t( 'Total:' )); ?> {{ total }}</h3>
                {{/if}}
            </div>
        </script>

        <div id="ldapcontacts-stats"><div class="icon-loading"></div></div>
	</div>
</div>
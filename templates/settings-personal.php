<?php
// javascript
script( 'ldapcontacts', 'settings-personal' );
?>
<div id="ldapcontacts" class="section">
	<h2><?php p($l->t('Contacts')); ?></h2>
	<form id="ldapcontacts-settings">
		<label for="ldapcontacts-order-by"><?php p($l->t('Order Contacts by:')); ?></label>
		<select name="ldapcontacts-order-by" id="ldapcontacts-order-by">
			<option></option>
			<?php
			foreach( $_['user_ldap_attributes'] as $attribute => $label ) {
				?><option value="<?php p( $attribute ); ?>" <?php if( $attribute === $_['order_by'] ) echo ' selected'; ?>><?php p( $label ); ?></option><?php
			}
			?>
		</select>
	</form>
	
	<span id="ldapcontacts-msg" class="msg"></span>
</div>
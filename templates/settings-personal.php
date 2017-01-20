<?php
// javascript
script( 'ldapcontacts', 'settings-personal' );
?>
<div id="ldapcontacts" class="section">
	<h2><?php p($l->t('Contacts')); ?></h2>
	<form id="ldapcontacts-settings">
		<h5><?php p($l->t('Order Contacts by:')); ?></h5>
		<input type="radio" name="ldapcontacts-order-by" id="ldapcontacts-order-by-firstname" <?php if( $_['order_by'] == 'firstname' ) echo 'checked'; ?>><label for="ldapcontacts-order-by-firstname"><?php p($l->t('Firstname')); ?></label>
		<input type="radio" name="ldapcontacts-order-by" id="ldapcontacts-order-by-lastname" <?php if( $_['order_by'] == 'lastname' ) echo 'checked'; ?>><label for="ldapcontacts-order-by-lastname"><?php p($l->t('Lastname')); ?></label>
	</form>
	
	<span id="ldapcontacts-msg" class="msg"></span>
</div>
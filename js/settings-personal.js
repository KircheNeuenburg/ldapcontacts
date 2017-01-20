$(document).ready(function(){
	var saveSettings = function( key, value ) {
		// get the settings data
		var data = Object();
		data.key = key;
		data.value = value;
		// start saving
		OC.msg.startSaving( '#ldapcontacts-msg' );
		// save the settings
		$.ajax({
            url: OC.generateUrl( '/apps/ldapcontacts/settings/personal' ),
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data)
        }).done( function( data ) {
			OC.msg.finishedSaving( '#ldapcontacts-msg', data );
		});
	}
	
	$( '#ldapcontacts-order-by-firstname' ).change( function() {
		saveSettings( 'order_by', 'firstname' );
	});
	
	$( '#ldapcontacts-order-by-lastname' ).change( function() {
		saveSettings( 'order_by', 'lastname' );
	});
});
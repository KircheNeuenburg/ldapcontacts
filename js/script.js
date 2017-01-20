(function (OC, window, $, undefined) {
'use strict';

$(document).ready(function () {

// this contacts object holds all our contacts
var Contacts = function (baseUrl) {
    this._baseUrl = baseUrl;
    this._contacts = [];
    this._activeContact = undefined;
	this._matches = [];
	this._search_id = 0;
	this._last_nav_render = 0;
	this._last_search = '';
	this._groups = [];
	this._activeGroup = 'all';
};

Contacts.prototype = {
    load: function (id) {
        var self = this;
        this._contacts.forEach(function (contact) {
            if (contact.id === id) {
                contact.active = true;
                self._activeContact = contact;
            } else {
                contact.active = false;
            }
        });
    },
    getActive: function () {
        return this._activeContact;
    },
    getAll: function () {
        return this._contacts;
    },
    loadAll: function () {
        var deferred = $.Deferred();
        var self = this;
        $.get(this._baseUrl).done(function (contacts) {
            // reset variables
			self._activeContact = undefined;
			self._matches = [];
			self._contacts = [];
			// do special actions on every contact
			$.each( contacts, function( i, contact ) {
				// add the general group to all contacts
				contacts[i].groups.push( {id: 'all'} );
				// add all contact ids to the match
				self._matches.push( contact.id );
			});
			
            self._contacts = contacts;
            deferred.resolve();
        }).fail(function () {
            deferred.reject();
        });
        return deferred.promise();
    },
	search: function ( search ) {
		if( search == this._last_search ) return false;
		this._last_search = search;
		
		var self = this;
		this._search_id++;
		var id = this._search_id;
		search = search.toLowerCase();
		
		var matches = [];
		var ids = [];
		
		$( this._contacts ).each( function( i, contact ) {
			if( self._search_id != id ) return false;
			ids.push( contact['id'] );
			$.each( contact, function( key, value ) {
				if( typeof( value ) != 'string' && typeof( value ) != 'number' ) return;
				value = String( value ).toLowerCase();
				if( ~value.indexOf( search ) ) {
					matches.push( contact['id'] );
					return false;
				}
			});
		});
		
		return matches;
	},
    loadOwn: function () {
        var deferred = $.Deferred();
        var self = this;
        $.get(this._baseUrl + '/own').done(function (me) {
            self._activeContact = undefined;
            self._me = me;
            deferred.resolve();
        }).fail(function () {
            deferred.reject();
        });
        return deferred.promise();
    },
	getOwn: function () {
		if( typeof( this._me ) != 'object' || this._me == null ) return
		return this._me[0];
	},
	updateOwn: function (givenname, sn, street, postaladdress, postalcode, l, homephone, mobile, description) {
		var own = Object();
		// save all the given values
		own.givenname = givenname;
		own.sn = sn;
		own.street = street;
		own.postaladdress = postaladdress;
		own.postalcode = postalcode;
		own.l = l;
		own.homephone = homephone;
		own.mobile = mobile;
		own.description = description;
		
        return $.ajax({
            url: this._baseUrl + '/own',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(own)
        });
    },
	loadGroups: function () {
		var deferred = $.Deferred();
        var self = this;
        $.get(this._baseUrl + "/groups").done(function (groups) {
            self._groups = groups;
            deferred.resolve();
        }).fail(function () {
            deferred.reject();
        });
        return deferred.promise();
	}
};

// this will be the view that is used to update the html
var View = function (contacts) {
    this._contacts = contacts;
};

View.prototype = {
    renderContent: function () {
		var self = this;
        var source = $('#content-tpl').html();
        var template = Handlebars.compile(source);
        var html = template({contact: this._contacts.getActive()});
        $('#info').html(html);
		
		$('#info .icon-copy').click(function() {
			var element = $( this.parentElement.parentElement.children[1].firstChild );
			var $input = $( document.createElement( "input" ) );
			$('body').append( $input );
			$input.val( element.text() ).select();
			document.execCommand('copy');
			$input.remove();
			
			
			
			element.addClass( 'highlight highlighted' );
			setTimeout( function() { element.removeClass( 'highlight' ); }, 500 );
		});
    },
    renderNavigation: function () {
		var self = this;
		
		if( this._contacts._last_nav_render + 100 > (new Date).getTime() ) return;
		this._contacts._last_nav_render = (new Date).getTime();
		
		// get all contacts
		var contacts = this._contacts.getAll();
		
		if( typeof( this._contacts._matches ) == 'object' && this._contacts._matches.length > 0 ) {
			var ids = this._contacts._matches;
			
			var tmp = contacts;
			contacts = [];
			$.each( tmp, function( i, contact ) {
				$.each( ids, function( j, key ) {
					if( key == contact['id'] ) {
						// go through all the contacts groups
						$.each( contact['groups'], function( k, group ) {
							// check if the contacts has the required group
							if( self._contacts._activeGroup == group.id ) {
								// add the contact
								contacts.push( contact );
								// to make the next search faster, we delete the already found id
								ids.slice( j, 1 );
								// if we found one match, the search is over
								return;
							}
						});
						return;
					}
				});
			});
		}
		else {
			// no contacts found in the search
			contacts = [];
		}
		
		var self = this;
        var source = $('#navigation-tpl').html();
        var template = Handlebars.compile(source);
		if( contacts != [] ) var html = template({"contacts": contacts});
		else var html = template();
        $('#app-navigation div.info').html(html);

        // show app menu
        $('#app-navigation .app-navigation-entry-utils-menu-button').click(function () {
            var entry = $(this).closest('.contact');
            entry.find('.app-navigation-entry-menu').toggleClass('open');
        });

        // load a contact
        $('#app-navigation .contact > a').click(function () {
            var id = parseInt($(this).parent().data('id'), 10);
            self._contacts.load(id);
            self.renderContent();
            $('#info').focus();
        });
    },
	renderSettings: function() {
		var self = this;
		var source = $('#settings-tpl').html();
        var template = Handlebars.compile(source);
        var html = template();
        $('#app-settings').html(html);
		
		// load own data for editing
        $('#app-settings a.icon-edit').click(function () {
            var id = parseInt($(this).parent().data('id'), 10);
            self._contacts.load(id);
            self.renderEdit();
            $('#info').focus();
        });
	},
	renderNavigationHeader: function () {
		var self = this;
		console.log( this._contacts._groups );
		var source = $('#navigation-header-tpl').html();
        var template = Handlebars.compile(source);
        var html = template({"groups": this._contacts._groups});
        $('#navigation-header').html(html);
		
		
		// search for contacts
		$( "#search_ldap_contacts" ).on( "change keyup paste", function() {
			var value = $( this ).val();
			
			// check if we are still searching
			if( value == '' ) $( this ).removeClass( 'searching' );
			else $( this ).addClass( 'searching' );
			
			// search for the given value and render the navigation
			var search = self._contacts.search( value );
			if( search ) {
				self._contacts._matches = search;
				self.renderNavigation();
			}
		});
		
		// button for clearing search input
		$( "#search_ldap_contacts + .abort" ).click( function() {
			$( "#search_ldap_contacts" ).val('');
			$( "#search_ldap_contacts" ).trigger( 'change' );
		});
		
		$('#ldap_contacts_group_selector').on('change', function () {
			var value = $("option:selected", this)[0].value;
			self._contacts._activeGroup = value;
			self.renderNavigation();
		});
	},
	renderEditContent: function ( saved, save_failed ) {
		if( typeof( saved ) == 'undefined' || saved == null ) saved = 0;
		if( typeof( save_failed ) == 'undefined' || save_failed == null ) save_failed = 1;
		
		var self = this;
		// unhighlight currently active contact
		$('#app-navigation .contact.active').removeClass( 'active' );
		this._contacts._activeContact = undefined;
		
		var source = $('#content-edit-tpl').html();
		var template = Handlebars.compile(source);
		var html = template({me: self._contacts.getOwn(), 'saved': saved, 'save_failed': save_failed});
		
		$('#info').html(html);
		
		// handle saves
		var givenname = $('#app-content input#edit_givenname');
		var sn = $('#app-content input#edit_sn');
		var street = $('#app-content input#edit_street');
		var postaladdress = $('#app-content input#edit_postaladdress');
		var postalcode  = $('#app-content input#edit_postalcode');
		var l = $('#app-content input#edit_l');
		var homephone = $('#app-content input#edit_homephone');
		var mobile = $('#app-content input#edit_mobile');
		var description = $('#app-content input#edit_description');
		
		$('#app-content button').click(function () {
			// disable save button to prevent multiple pressing
			this.disabled = true;
			$( this ).after( $( document.createElement( "span" ) ).addClass( "icon-loading" ) );
			
			var givenname_val = givenname.val();
			var sn_val = sn.val();
			var street_val = street.val();
			var postaladdress_val = postaladdress.val();
			var postalcode_val = postalcode.val();
			var l_val = l.val();
			var homephone_val = homephone.val();
			var mobile_val = mobile.val();
			var description_val = description.val();
			
			self._contacts.updateOwn(givenname_val, sn_val, street_val, postaladdress_val, postalcode_val, l_val, homephone_val, mobile_val, description_val).done(function (data) {
				if( data == "SUCCESS" ) {
					self._contacts.loadAll().done(function() {
						self._contacts.loadOwn().done(function() {
							self.renderNavigation();
							self.renderEdit( 1, 0 );
						});
					});
				}
				else self.renderEdit( 1, 1 );
			}).fail(function (data) {
				self.renderEdit( 1, 1 );
			});
		});
    },
    render: function () {
        this.renderNavigation();
        this.renderContent();
    },
    renderEdit: function ( saved, save_failed ) {
		if( typeof( saved ) == 'undefined' || saved == null ) saved = 0;
		if( typeof( save_failed ) == 'undefined' || save_failed == null ) save_failed = 1;
        this.renderEditContent( saved, save_failed );
    }
};

var contacts = new Contacts(OC.generateUrl('/apps/ldapcontacts/contacts'));
var view = new View(contacts);
contacts.loadAll().done(function () {
	contacts.loadOwn().done(function() {
		contacts.loadGroups().done(function() {
			view.renderEdit();
			view.render();
			view.renderSettings();
			view.renderNavigationHeader();
		});
	});
}).fail(function () {
    alert( 'Could not load contacts' );
});

});

})(OC, window, jQuery);
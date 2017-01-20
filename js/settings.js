$(document).ready(function(){
	// save settings
	$( "#ldapcontacts button[type='submit']" ).click( function ( event ) {
		event.preventDefault();
		// get the settings from the form
		var settings = $( '#ldapcontacts' ).serialize();
		// sending the new settings
		OC.msg.startSaving( '#ldapcontacts-settings-msg' );
		$.post( OC.filePath( 'ldapcontacts', 'ajax','save_settings.php' ), settings, function( data ) {
			OC.msg.finishedSaving( '#ldapcontacts-settings-msg', data );
		});
	});
	
	var Contacts = function() {
		this._baseUrl = OC.generateUrl('/apps/ldapcontacts/contacts');
		this._hidden = [];
		this._visible = [];
		this._groups = [];
		this._hidden_groups = [];
		this._last_search = '';
		this._last_group_search = '';
		this._search_id = 0;
		this._group_search_id = 0;
	};
	
	Contacts.prototype = {
		// load all required data
		loadAll: function() {
			var deferred = $.Deferred();
			// load data
			contacts.loadHidden().done( function() {
				contacts.loadVisible().done( function() {
					contacts.loadHiddenGroups().done( function() {
						contacts.loadGroups().done( function() {
							deferred.resolve();
						});
					});
				});
			});
			return deferred.promise();
		},
		// load all visible contacts
		loadVisible: function() {
			var deferred = $.Deferred();
			var self = this;
			// load the contacts
			$.get( this._baseUrl, function(data) {
				self._visible = data;
				deferred.resolve();
			}).fail( function() {
				// contacts couldn't be loaded
				deferred.reject();
			});
			return deferred.promise();
		},
		// load all invisible contacts
		loadHidden: function() {
			var deferred = $.Deferred();
			var self = this;
			// load the contacts
			$.get( this._baseUrl + '/admin', function(data) {
				self._hidden = data;
				deferred.resolve();
			}).fail( function() {
				// contacts couldn't be loaded
				deferred.reject();
			});
			return deferred.promise();
		},
		getVisible: function() {
			return this._visible;
		},
		getHidden: function() {
			return this._hidden;
		},
		// hide a certain contact from the users
		hideContact: function(contact) {
			var self = this;
			OC.msg.startSaving( '#ldapcontacts-edit-user-msg' );
			// send request
			$.get( this._baseUrl + '/admin/hide/' + encodeURI(contact.mail), function(data) {
				OC.msg.finishedSaving( '#ldapcontacts-edit-user-msg', data );
				// reload all data
				self.render();
			});
		},
		// make a certain contact visible again
		showContact: function(contact) {
			var self = this;
			OC.msg.startSaving( '#ldapcontacts-edit-user-msg' );
			// send request
			$.get( this._baseUrl + '/admin/show/' + encodeURI(contact.mail), function(data) {
				OC.msg.finishedSaving( '#ldapcontacts-edit-user-msg', data );
				// reload all data
				self.render();
			});
		},
		// renders the settings section
		render: function() {
			var self = this;
			// load all data
			this.loadAll().done( function() {
				// render the sections
				self.renderUser();
				self.renderGroups();
			});
		},
		// renders the settings for showing and hiding users
		renderUser: function() {
			var self = this;
			var source = $('#ldapcontacts-edit-user-tpl').html();
			var template = Handlebars.compile(source);
			var html = template({hidden: this.getHidden()});
			$('#ldapcontacts-edit-user').html(html);
			
			// unhide a user
			$('#ldapcontacts-edit-user .remove').click( function() {
				// get the users id
				var id = this.attributes['target-id'].value;
				
				// go through all users and find the one the id is fitting to
				$.each( self.getHidden(), function(index, data) {
					// if this is the user, request to show him again
					if( data['id'] == id ) self.showContact(data);
				});
			});
			
			// search form for hiding a user
			$('#ldapcontacts-search-visible').on( "change keyup paste", function() {
				var value = $( this ).val();
				
				// check if we are still searching
				if( value == '' ) $( this ).removeClass( 'searching' );
				else $( this ).addClass( 'searching' );
				
				// search for the given value and render the navigation
				self.searchUsers( value );
			});
			
			// abort the search
			$('#ldapcontacts-search-visible + .abort').click( function() {
				// clear the search form
				$('#ldapcontacts-search-visible').val('');
				$('#ldapcontacts-search-visible').trigger( 'change' );
			});
		},
		searchUsers: function ( search ) {
			if( search == this._last_search ) return false;
			this._last_search = search;
			
			// if the search form is empty, clean up
			if( search == '' ) {
				this.renderUserSuggestions(this._visible);
				return true;
			}
			
			var self = this;
			this._search_id++;
			var id = this._search_id;
			search = search.toLowerCase();
			
			var matches = [];
			
			$( this._visible ).each( function( i, contact ) {
				if( self._search_id != id ) return false;
				$.each( contact, function( key, value ) {
					if( typeof( value ) != 'string' && typeof( value ) != 'number' ) return;
					value = String( value ).toLowerCase();
					if( ~value.indexOf( search ) ) {
						matches.push( contact );
						return false;
					}
				});
			});
			
			return self.renderUserSuggestions(matches)
		},
		renderUserSuggestions: function(users) {
			var self = this;
			// clear the suggestions area
			$('#ldapcontacts-edit-user .search + .search-suggestions').empty();
			// don't show all users at once
			if( users != this._visible ) {
				// show all found users
				$.each( users, function(i, user) {
					// render the search suggestion
					var html = $(document.createElement('div'))
					.addClass('suggestion')
					// add the users name
					.text(user.name)
					// add the contact information to the suggestion
					.data('contact', user)
					// when clicked on the user, he will be hidden
					.click(function() {
						self.hideContact( $(this).data('contact') );
					});
					// add the option to the search suggestions
					$('#ldapcontacts-edit-user .search + .search-suggestions').append(html);
				});
			}
			
			return true;
		},
		
		
		
		
		
		
		
		
		
		/* group functions */
		searchGroups: function ( search ) {
			if( search == this._last_group_search ) return false;
			this._last_group_search = search;
			
			// if the search form is empty, clean up
			if( search == '' ) {
				this.renderGroupSuggestions(this._groups);
				return true;
			}
			
			var self = this;
			this._group_search_id++;
			var id = this._group_search_id;
			search = search.toLowerCase();
			
			var matches = [];
			
			$( this._groups ).each( function( i, group ) {
				if( self._group_search_id != id ) return false;
				$.each( group, function( key, value ) {
					if( typeof( value ) != 'string' && typeof( value ) != 'number' ) return;
					value = String( value ).toLowerCase();
					if( ~value.indexOf( search ) ) {
						matches.push( group );
						return false;
					}
				});
			});
			
			return self.renderGroupSuggestions(matches)
		},
		renderGroupSuggestions: function(groups) {
			var self = this;
			// clear the suggestions area
			$('#ldapcontacts-edit-group .search + .search-suggestions').empty();
			// don't show all groups at once
			if( groups != this._groups ) {
				// show all found groups
				$.each( groups, function(i, group) {
					// render the search suggestion
					var html = $(document.createElement('div'))
					.addClass('suggestion')
					// add the groups name
					.text(group.cn)
					// add the contact information to the suggestion
					.data('contact', group)
					// when clicked on the group, it will be hidden
					.click(function() {
						self.hideGroup( $(this).data('contact') );
					});
					// add the option to the search suggestions
					$('#ldapcontacts-edit-group .search + .search-suggestions').append(html);
				});
			}
			
			return true;
		},
		// load all visible groups
		loadGroups: function() {
			var deferred = $.Deferred();
			var self = this;
			// load the groups
			$.get( this._baseUrl + '/groups', function(data) {
				self._groups = data;
				deferred.resolve();
			}).fail( function() {
				// groups couldn't be loaded
				deferred.reject();
			});
			return deferred.promise();
		},
		// load all invisible groups
		loadHiddenGroups: function() {
			var deferred = $.Deferred();
			var self = this;
			// load the groups
			$.get( this._baseUrl + '/admin/group', function(data) {
				self._hidden_groups = data;
				deferred.resolve();
			}).fail( function() {
				// groups couldn't be loaded
				deferred.reject();
			});
			return deferred.promise();
		},
		getGroups: function() {
			return this._groups;
		},
		getHiddenGroups: function() {
			return this._hidden_groups;
		},
		// hide a certain group from the users
		hideGroup: function(group) {
			var self = this;
			OC.msg.startSaving( '#ldapcontacts-edit-group-msg' );
			// send request
			$.get( this._baseUrl + '/admin/group/hide/' + encodeURI(group.id), function(data) {
				OC.msg.finishedSaving( '#ldapcontacts-edit-group-msg', data );
				// reload all data
				self.render();
			});
		},
		// make a certain group visible again
		showGroup: function(group) {
			var self = this;
			OC.msg.startSaving( '#ldapcontacts-edit-group-msg' );
			// send request
			$.get( this._baseUrl + '/admin/group/show/' + encodeURI(group.id), function(data) {
				OC.msg.finishedSaving( '#ldapcontacts-edit-group-msg', data );
				// reload all data
				self.render();
			});
		},
		// renders the settings for showing and hiding groups
		renderGroups: function (){
			var self = this;
			var source = $('#ldapcontacts-edit-group-tpl').html();
			var template = Handlebars.compile(source);
			var html = template({hidden: this.getHiddenGroups()});
			$('#ldapcontacts-edit-group').html(html);
			
			// unhide a group
			$('#ldapcontacts-edit-group .remove').click( function() {
				// get the groups id
				var id = this.attributes['target-id'].value;
				
				// go through all groups and find the one the id is fitting to
				$.each( self.getHiddenGroups(), function(index, data) {
					// if this is the user, request to show him again
					if( data['id'] == id ) self.showGroup(data);
				});
			});
			
			// search form for hiding a user
			$('#ldapcontacts-search-groups-visible').on( "change keyup paste", function() {
				var value = $( this ).val();
				
				// check if we are still searching
				if( value == '' ) $( this ).removeClass( 'searching' );
				else $( this ).addClass( 'searching' );
				
				// search for the given value and render the navigation
				self.searchGroups( value );
			});
			
			// abort the search
			$('#ldapcontacts-search-groups-visible + .abort').click( function() {
				// clear the search form
				$('#ldapcontacts-search-groups-visible').val('');
				$('#ldapcontacts-search-groups-visible').trigger( 'change' );
			});
		}
	}
	
	var contacts = new Contacts();
	// initial rendering
	contacts.render();
});

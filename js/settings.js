(function (OC, window, $, undefined) {
'use strict';

$(document).ready(function(){
	var Contacts = function() {
		this._baseUrl = OC.generateUrl('/apps/ldapcontacts');
		this._hidden = [];
		this._visible = [];
		this._groups = [];
		this._hidden_groups = [];
		this._last_search = '';
		this._last_group_search = '';
		this._search_id = 0;
		this._group_search_id = 0;
        this._settings = [];
        this._ldap_search_previews = [];
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
                            contacts.loadSettings().done( function() {
								// everything loaded
								deferred.resolve();
                            }).fail( function() {
                                deferred.reject();
                            });
						}).fail( function() {
                            deferred.reject();
                        });
					}).fail( function() {
                        deferred.reject();
                    });
				}).fail( function() {
                    deferred.reject();
                });
			}).fail( function() {
                deferred.reject();
            });
			return deferred.promise();
		},
        // load all settings
        loadSettings: function() {
			var deferred = $.Deferred();
			var self = this;
			// load the contacts
			$.get( this._baseUrl + '/settings', function( data ) {
                if( data.status == 'success' ) {
                    self._settings = data.data;
					self.loadLdapSearchPreviews().done( function() {
						deferred.resolve();
					});
                }
                else {
                    // contacts couldn't be loaded
				    deferred.reject();
                }
			}).fail( function() {
				// contacts couldn't be loaded
				deferred.reject();
			});
			return deferred.promise();
		},
        // load LDAP search previews
        loadLdapSearchPreviews: function() {
			var deferred = $.Deferred();
			var self = this;
			// load the previews
			$.get( this._baseUrl + '/searchfilter/previews', function( data ) {
                if( data.status == 'success' ) {
					self._ldap_search_previews = [];
					// replace escaped data
					$.each( data.data, function( i, v ) {
						self._ldap_search_previews[ i ] = v.replace( '\\23\\55\\53\\45\\52\\23', 'AA00BB' ).replace( '\\23\\41\\41\\30\\30\\42\\42\\23', 'USER' );
					});
					
					deferred.resolve();
                }
                else {
                    // previews couldn't be loaded
				    deferred.reject();
                }
			}).fail( function() {
				// previews couldn't be loaded
				deferred.reject();
			});
			return deferred.promise();
		},
		// load all visible contacts
		loadVisible: function() {
			var deferred = $.Deferred();
			var self = this;
			// load the contacts
			$.get( this._baseUrl + '/load', function( data ) {
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
			$.get( this._baseUrl + '/admin/hidden/user', function( data ) {
				if( data.status == 'success' ) {
					self._hidden = data.data;
				}
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
		hideContact: function( contact ) {
			var self = this;
			OC.msg.startSaving( '#ldapcontacts-edit-user-msg' );
			// send request
			$.get( this._baseUrl + '/admin/hide/user/' + encodeURI( contact.ldapcontacts_entry_id ), function( data ) {
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
			$.get( this._baseUrl + '/admin/show/' + encodeURI( contact.ldapcontacts_entry_id ), function( data ) {
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
                self.renderSettings();
				self.renderUser();
				self.renderGroups();
			});
		},
        // render the apps general settings
        renderSettings: function() {
            var self = this;
            var source = $( '#ldapcontacts-general-settings-tpl' ).html();
			var template = Handlebars.compile( source );
			var html = template( { settings: self._settings, search_filter_preview: self._ldap_search_previews } );
			$( '#ldapcontacts-general-settings' ).html( html );
			
			// remove attribute button
			$( "#ldapcontacts-general-settings" ).on( 'click', '.remove-attribute', function( e ) {
				var element = $( this );
				// disable remove button and show loading circle
				element.disabled = true;
				element.removeClass( 'icon-delete' ).addClass( 'icon-loading' );
				
				// if this is an old, existing attribute, the setting has to be updated
				if( !element.attr( 'new_attribute' ) ) {
					// get the attribute to be removed
					var attribute = $( e.target ).attr( 'attribute' );
					
					// remove the attribute
					$.ajax({
						url: self._baseUrl + '/setting/array/remove',
						method: 'POST',
						contentType: 'application/json',
						data: JSON.stringify( { setting_key: 'user_ldap_attributes', key: attribute } )
					}).done( function( data ) {
						if( data.status == 'success' ) {
							// remove the html element
							element.parent().parent().remove();
						}
						else {
							// if the saving failed, reactivate the remove button
							element.disabled = false;
							element.removeClass( 'icon-loading' ).addClass( 'icon-delete' );
						}
						// show a message to the user
						OC.msg.finishedSaving( '#ldapcontacts .ldapcontacts-settings-msg', data );
					}).fail( function() {
						// if the saving failed, reactivate the remove button
						element.disabled = false;
						element.removeClass( 'icon-loading' ).addClass( 'icon-delete' );
						OC.msg.finishedError( '#ldapcontacts .ldapcontacts-settings-msg', t( 'ldapcontacts', 'Removing the attribute failed' ) );
					});
				}
				// if this is a new attribute, we only have to remove the html
				else {
					// remove the html element
					element.parent().parent().remove();
				}
				
			});
			
			// add attribute button
			$( "#ldapcontacts-general-settings .add-attribute" ).click( function( e ) {
				// get the next id to use
				if( typeof( self.user_ldap_attributes_id ) == 'undefined' || self.user_ldap_attributes_id === null ) {
					self.user_ldap_attributes_id = Object.keys( self._settings.user_ldap_attributes ).length;
				}
				else {
					self.user_ldap_attributes_id++;
				}
				
				// render html template
				var source = $( '#ldapcontacts-general-settings-new-attribute-tpl' ).html();
				var template = Handlebars.compile( source );
				var html = template( { index: self.user_ldap_attributes_id } );
				
				// add the new attribute to the table
				$( '#ldapcontacts-general-settings .ldap-attributes' ).children( 'tbody' ).append( html );
			});
			
            // save settings
            $( "#ldapcontacts-general-settings input:not(.ignore-change)" ).on( 'change', function( e ) {
				e.preventDefault();
				// get the settings from the form
				var settings = $( '#ldapcontacts-general-settings-form' ).serialize();
                // send the new settings
                OC.msg.startSaving( '#ldapcontacts .ldapcontacts-settings-msg' );
                $.ajax({
					url: self._baseUrl + '/settings/update',
					method: 'POST',
					contentType: 'application/json',
					data: JSON.stringify( { settings: settings } )
				}).done( function( data ) {
                    // load and render the settings section again
                    self.loadSettings().done( function() {
                        self.renderSettings();
                    });
                    OC.msg.finishedSaving( '#ldapcontacts .ldapcontacts-settings-msg', data );
                }).fail( function() {
                    // if the saving failed, reactivate the save
                    self.renderSettings();
                });
            });
			
			self.registerTestButton();
        },
		// register the test settings button
		registerTestButton: function() {
			var self = this;
			// test settings button
			$( '#ldapcontacts-test-settings button[target="all"]' ).click( function( e ) {
				e.preventDefault();
				var uid = $( '#ldapcontacts-test-settings-uid' ).val();
				// show loading icon
				$( this.parentElement ).html( '<span class="icon-loading"></span>' );
				self.testSettings( uid );
			});
		},
		// test the LDAP settings
		testSettings: function( uid ) {
			var self = this;
			var tests = {
				users: -1,
				user_specific: -1,
				groups: -1,
				user_groups: -1,
			};
			
			// count all users
			var users = $.get( self._baseUrl + '/test/users/0' ).done( function( data ) {
				if( data.status == 'success' ) {
					tests.users = data.data.length;
				}
			});
			
			// check if a specific user is given for testing
			if( typeof( uid ) != 'undefined' && uid != null && uid != '' ) {
				// get a specific user
				var user_specific = $.get( self._baseUrl + '/test/user/' + encodeURI( uid ) ).done( function( data ) {
					if( data.status == 'success' && data.data.length == 1 ) {
						tests.user_specific = 1;
						tests.user_groups = data.data[0].groups.length;
					}
				});
			}
			else {
				var user_specific = $.Deferred().resolve();
			}
			
			// count all groups
			var groups = $.get( self._baseUrl + '/test/groups/0' ).done( function( data ) {
				if( data.status == 'success' ) {
					tests.groups = data.data.length;
				}
			});
			
			// render test results when done with all tests
			$.when( users, user_specific, groups ).then( function( users, users_specific, groups ) {
				// remove failed tests
				$.each( tests, function( i, v ) {
					if( v == -1 ) delete tests[ i ];
				});
				
				// render test results
				var source = $( '#ldapcontacts-test-settings-tpl' ).html();
				var template = Handlebars.compile( source );
				var html = template( { tests: tests } );
				$( '#ldapcontacts-test-settings' ).html( html );
				// register new test button
				self.registerTestButton();
			});
		},
		// renders the settings for showing and hiding users
		renderUser: function() {
			var self = this;
			var source = $('#ldapcontacts-edit-user-tpl').html();
			var template = Handlebars.compile(source);
			var html = template( { hidden: this.getHidden() } );
			$('#ldapcontacts-edit-user').html(html);
			
			// unhide a user
			$('#ldapcontacts-edit-user .remove').click( function() {
				// get the users id
				var id = this.attributes['target-id'].value;
				
				// go through all users and find the one the id is fitting to
				$.each( self.getHidden(), function(index, data) {
					// if this is the user, request to show him again
					if( data['ldapcontacts_entry_id'] == id ) self.showContact(data);
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
				this.renderUserSuggestions( this._visible );
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
			
			return self.renderUserSuggestions(matches);
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
					.text(user.ldapcontacts_name)
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
					.text(group.ldapcontacts_name)
					// add the contact information to the suggestion
					.data('group', group)
					// when clicked on the group, it will be hidden
					.click(function() {
						self.hideGroup( $(this).data('group') );
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
			$.get( this._baseUrl + '/groups', function( data ) {
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
			$.get( this._baseUrl + '/admin/hidden/group', function( data ) {
				if( data.status == 'success' ) {
					// groups loaded
					self._hidden_groups = data.data;
					deferred.resolve();
				}
				else {
					// groups couldn't be loaded
					deferred.reject();
				}
				
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
			$.get( this._baseUrl + '/admin/hide/group/' + encodeURI( group.ldapcontacts_entry_id ), function( data ) {
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
			$.get( this._baseUrl + '/admin/show/' + encodeURI( group.ldapcontacts_entry_id ), function( data ) {
				OC.msg.finishedSaving( '#ldapcontacts-edit-group-msg', data );
				// reload all data
				self.render();
			});
		},
		// renders the settings for showing and hiding groups
		renderGroups: function (){
			var self = this;
			var source = $( '#ldapcontacts-edit-group-tpl' ).html();
			var template = Handlebars.compile( source );
			var html = template( { hidden: this.getHiddenGroups() } );
			$( '#ldapcontacts-edit-group' ).html( html );
			
			// unhide a group
			$( '#ldapcontacts-edit-group .remove' ).click( function() {
				// get the groups id
				var id = this.attributes['target-id'].value;
				
				// go through all groups and find the one the id is fitting to
				$.each( self.getHiddenGroups(), function( index, data ) {
					// if this is the user, request to show him again
					if( data.ldapcontacts_entry_id == id ) self.showGroup( data );
				});
			});
			
			// search form for hiding a user
			$( '#ldapcontacts-search-groups-visible' ).on( "change keyup paste", function() {
				var value = $( this ).val();
				
				// check if we are still searching
				if( value == '' ) $( this ).removeClass( 'searching' );
				else $( this ).addClass( 'searching' );
				
				// search for the given value and render the navigation
				self.searchGroups( value );
			});
			
			// abort the search
			$( '#ldapcontacts-search-groups-visible + .abort' ).click( function() {
				// clear the search form
				$( '#ldapcontacts-search-groups-visible' ).val( '' );
				$( '#ldapcontacts-search-groups-visible' ).trigger( 'change' );
			});
		},
	};
	
	var contacts = new Contacts();
	// initial rendering
	contacts.render();
});
})(OC, window, jQuery);
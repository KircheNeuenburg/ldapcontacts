(function (OC, window, $, undefined) {
'use strict';
$( document ).ready( function() {
    var Statistics = function() {
        this._data = {};
        this._baseUrl = OC.generateUrl( '/apps/ldapcontacts' );
        this._graphs = {
            bgColors: [
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 99, 132, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)',
            ],
            borderColors: [
                'rgba(54, 162, 235, 1)',
                'rgba(255,99,132,1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
            ],
            borderWidth: 1,
        };
        
        this._data_labels = {
            'entries': t( 'ldapcontacts', 'Entries' ),
            'entries_filled': t( 'ldapcontacts', 'Filled' ),
            'entries_empty': t( 'ldapcontacts', 'Empty' ),
            'entries_filled_percent': t( 'ldapcontacts', 'Filled' ),
            'entries_empty_percent': t( 'ldapcontacts', 'Empty' ),
            'users': t( 'ldapcontacts', 'Users' ),
            'users_filled_entries': t( 'ldapcontacts', 'Users with some filled entries' ),
            'users_empty_entries': t( 'ldapcontacts', 'Users with only empty entries' ),
            'users_filled_entries_percent': t( 'ldapcontacts', 'Users with some filled entries' ),
            'users_empty_entries_percent': t( 'ldapcontacts', 'Users with only empty entries' ),
        };
        
        this._stat_titles = {
            'entries': t( 'ldapcontacts', 'Entries filled' ),
            'user_entries': t( 'ldapcontacts', 'Users with filled entries' ),
        }
    };
    
    Statistics.prototype = {
        // load add statistics data
        loadAll: function() {
            var deferred = $.Deferred();
            var self = this;
			// load the statistics
			$.get( this._baseUrl + '/statistics', function( data ) {
				if( data.status == "success" ) {
                    delete data.status;
                    // save all data
                    $.each( data, function( type, data ) {
                        self._data[ type ] = data;
                    });
                    
                    deferred.resolve();
                }
                else {
                    // statistics couldn't be loaded
				    deferred.reject();
                }
			}).fail( function() {
				// statistics couldn't be loaded
				deferred.reject();
			});
			return deferred.promise();
        },
        renderAll: function() {
            // empty the content area
            $( '#ldapcontacts-stats' ).empty();
            
            // render all statistics
            this.renderEntriesStat();
            this.renderUsersEntriesStat();
        },
        renderEntriesStat: function() {
            this.renderBarGraph( 'entries', 'entries_stat', [ 'entries_filled', 'entries_empty' ], this._data[ 'entries' ] );
        },
        renderUsersEntriesStat: function() {
            this.renderBarGraph( 'user_entries', 'users_entries_stat', [ 'users_filled_entries', 'users_empty_entries' ], this._data[ 'users' ] );
        },
        renderBarGraph: function( title, id, data_keys, total ) {
            var html_id = 'ldapcontacts_' + id;
            title = this._stat_titles[ title ];
            var self = this;
			var source = $( '#ldapcontacts-stat-tpl' ).html();
			var template = Handlebars.compile( source );
			var html = template( { title: title, id: html_id, total: total } );
			$( '#ldapcontacts-stats' ).append( html );
            
            // get all data values
            var data = [];
            $.each( data_keys, function( k, key ) {
                data.push( self._data[ key ] );
            });
            // get all data labels
            var labels = [];
            $.each( data_keys, function( k, key ) {
                labels.push( self._data_labels[ key ] );
            });
            
            var ctx = document.getElementById( html_id ).getContext('2d');
            var chart = new Chart( ctx, {
               type: 'pie',
                data: {
                    datasets: [
                        {
                            data: data,
                            backgroundColor: self._graphs.bgColors,
                            borderColor: self._graphs.borderColors,
                            borderWidth: self._graphs.borderWidth,
                        },
                    ],
                    labels: labels,
                },
                options: {
                    
                }
            });
        }
    };
    
	var statistics = new Statistics();
	// load all statistics data
    statistics.loadAll().done( function() {
        // render the statistics
        statistics.renderAll();
    });
});
})(OC, window, jQuery);
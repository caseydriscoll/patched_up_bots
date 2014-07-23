// Calculate the total number of cpts in every library (users in library)
function calculateTotalItemsLeft() {
	totalItemsLeft = 0;
	Object.keys( libraries ).forEach( function( library ) {
		totalItemsLeft += Object.keys( libraries[library][cpt.plural] ).length;
	} );
}

function get_any_library() {
	var options = [];
	for( var library in libraries ) options.push( library );

	return options[Math.floor( Math.random() * options.length )];
}

function capitalize( word ) { return word.charAt( 0 ).toUpperCase() + word.slice( 1 ); }

jQuery( document ).ready( function() {
	for( var lib in libraries ) {
		takenData.forEach( function( data ) { if ( jQuery.inArray( data, libraries[lib] ) == -1 ) delete libraries[lib][cpt.plural][data];  } );
		if( Object.keys( libraries[lib][cpt.plural] ).length == 0 ) { 
			delete libraries[lib];
			jQuery( 'option[value="' + lib + '"]' ).remove();
		}
	}

	var library = get_any_library();
	var isScripted = jQuery( '#scripted' ).val() == 'scripted' ? true : false;

	// Initialize plurals 
	jQuery( '#cpt' ).text( cpt.single );
	jQuery( '.generate' ).val( 'Generate ' + capitalize( cpt.single ) );
	jQuery( 'input[name=submit]' ).val( 'Add ' + capitalize( cpt.single ) );

	// Iterate number generator
	jQuery( '#plus, #minus' ).on( 'click', function(e){
		num = parseInt( jQuery( 'input[name=amount]' ).val() );

		var data;
		calculateTotalItemsLeft();
		if( jQuery( 'select#library' ).val() == 'everything' )
			max = totalItemsLeft;
		else
			max = Object.keys( libraries[library][cpt.plural] ).length;

		if( jQuery( e.target ).attr('id') == 'minus' && num > 1 )
			if( e.shiftKey && num > 10 ) num -= 10;
			else if( e.altKey ) num = 1;
			else if( e.shiftKey ) num = 1;
			else num -= 1;
		else if( jQuery( e.target ).attr('id') == 'plus' )
			if( e.shiftKey ) num += 10;
			else if( e.altKey ) num = max;
			else num += 1;

		if( num > max ) num = max;

		jQuery( 'input[name=amount]' ).val( num );
		
		// Set plural tense
		if( parseInt( jQuery( 'input[name=amount]' ).val() ) == 1 ) { 
			jQuery( '#cpt' ).text( cpt.single );
			jQuery( '.generate' ).val( 'Generate ' + capitalize( cpt.single ) );
		} else {
			jQuery( '#cpt' ).text( cpt.plural );
			jQuery( '.generate' ).val( 'Generate ' + capitalize( cpt.plural ) ); 
		}
	} );

	// Event handler to change library on user selection
	jQuery( 'select#library' ).on( 'change', function() {
		if ( jQuery( 'select#library' ).val() == 'everything' ) return; 
		if ( jQuery( 'select#library' ).val() == 'anything' ) 
			library = get_any_library();
		else
			library = jQuery( 'select#library' ).val();
	} );

	// Change the '# items' label to reflect the cpt
	var total_added_rows;	// For the '# items' label above and below the table						
	total_added_rows = parseInt( jQuery( '.displaying-num' ).text().split( ' ' )[0] );
	if( total_added_rows == 1 ) jQuery( '.displaying-num' ).text( total_added_rows + ' ' + cpt.single );
	else jQuery( '.displaying-num' ).text( total_added_rows + ' ' + cpt.plural );

	// row generator
	jQuery( '.generate' ).on( 'click', function() {
		if ( jQuery( 'select#library' ).val() == '' ) return; // should literally never happen now
		isscripted = jQuery( '#scripted' ).val() == 'scripted' ? true : false;

		if ( jQuery( 'tr.new' ).length > 0 ) 
			jQuery( 'input[name=submit]' ).val( 'Add ' + capitalize( cpt.plural ) );
		else
			jQuery( 'input[name=submit]' ).val( 'Add ' + capitalize( cpt.single ) );

		/* 1) generate data */
		// was previously generating data in the midst of rendering rows for the 'everything' clause
		// create an array of things first, then render that things array
		
		var data;			// the array of cpt objects to choose from
		var numrows;		// the number of rows to create, usually 1
		var selectedData;	// an array of cpt objects selected to render into rows

		numrows = parseInt( jQuery( 'input[name=amount]' ).val() );
		selectedData = Array();

		// if the selection is 'everything' you must select data from all libraries
		if ( jQuery( 'select#library' ).val() == 'everything' ) { 
			// if the number of requested rows is greater than everything left
			//		just give everything that is left
			calculateTotalItemsLeft();
			numrows = numrows > totalItemsLeft ? totalItemsLeft : numrows;

			for( var i = 0; i < numrows; i++ ) {
				// deleted as to prevent get_any_library from searching empty tree
				if( typeof data != 'undefined' && Object.keys( data ).length == 0 ) delete libraries[library];

				library = get_any_library(); 

				var thing;
				var count = 0;

				if ( isscripted ) { 
					data = libraries[library][cpt.plural];

					do {
						for ( var prop in data ) if ( Math.random() < 1/++count ) thing = prop;
						var isTaken = ( typeof thing !== 'undefined' && jQuery.inArray( thing, takenData ) == -1 ) ? false : true;
					} while ( isTaken );

					data[thing]['name'] = thing; // save the username key to object
					data[thing]['library'] = library; // save the library key to object
					selectedData.push( data[thing] );
					takenData.push( thing );

					// trim list as data is taken
					delete data[thing];
				} else {
					// generate a random object that looks like it is a scripted cpt.
					// it must have a template in the library 
					// for every item in the template, create an array with those items
					library = 'gameofthrones';
					data = libraries[library];
					thing =  Array();
					var template = data['template'][cpt.single].split( ', ' );
					for ( var param in template ) {
						items = data['random'][template[param]].split( ', ' );
						thing[template[param]] = items[Math.floor( Math.random() * items.length )];
					}
					thing['library'] = library;
					if ( cpt.single == 'user' )
						thing['name'] = thing['fname'] + thing['lname'];

					selectedData.push( thing );
				}

			}
			
			calculateTotalItemsLeft();
			if ( totalItemsLeft == 0 ) { 
				this.disabled = true;
				jQuery( 'span#message' ).text( 'all options exhausted' ).addClass( 'danger' );
			}
		}
		// otherwise, you are only selecting data from one library
		else {
			// if the number of requested rows is greater than what is left in the library
			//		just give everything that is in the library
			data = libraries[library][cpt.plural];
			numrows = numrows > object.keys( data ).length ? object.keys( data ).length : numrows;

			for( var i = 0; i < numrows; i++ ) {
				var thing;
				var count = 0;

				do {
					for ( var prop in data ) if ( Math.random() < 1/++count ) thing = prop;
					var isTaken = ( typeof thing !== 'undefined' && jQuery.inArray( thing, takenData ) == -1 ) ? false : true;
				} while ( isTaken );

				data[thing]['name'] = thing; // save the username key to object
				data[thing]['library'] = library; // save the library key to object
				selectedData.push( data[thing] );
				takenData.push( thing );

				// trim list as data is taken
				delete data[thing];
			}					

			if ( object.keys( data ).length == 0 ) { 
				this.disabled = true;
				jQuery( 'span#message' ).text( 'all options exhausted' ).addclass( 'danger' );
			}
		}

		// update the '# items' label
		total_added_rows += numrows;
		if( total_added_rows == 1 ) jQuery( '.displaying-num' ).text( total_added_rows + ' ' + cpt.single );
		else jQuery( '.displaying-num' ).text( total_added_rows + ' ' + cpt.plural );


		/* 2) render data */
		var html = '';
		for ( i = 0; i < numrows; i++ ) {
			html += '<tr class="new">';
			html +=		'<td class="delete column-delete">';
			html +=			'<div class="dashicons dashicons-dismiss"></div>';
			html +=		'</td>';

			switch ( active_tab ) { 
			case 'users' : 
				var user = selectedData[i];
				var nicename = capitalize( user.fname ) + " " + capitalize( user.lname );

				roleselect = '<select name="users[' + user.name + '][role]" class="widefat">';
				for ( role in roles ) {
					var selected = '';
					if ( role === user.role ) selected = 'selected';
					roleselect += '<option value="' + role + '" ' + selected + '>' + capitalize( roles[role] ) + '</option>';
				}
				roleselect += '<select>';

				user.photo = isscripted ? user.name : user.library; 

				html +=		'<td class="avatar column-avatar">';
				html +=			'<img src="' + datafile + user.library + '/img/' + user.photo + '.jpg" width="32" height="32" />'
				html +=		'</td>';
				html +=		'<td class="user_login column-user_login">';
				html +=			'<input name="users[' + user.name + '][user_login]" type="text" class="widefat" value="' + user.name + '" />';
				html +=		'</td>';
				html +=		'<td class="user_email column-user_email">';
				html +=			'<input name="users[' + user.name + '][user_email]" type="text" class="widefat" value="' + user.name + '@' + user.library + '.com" />';
				html +=		'</td>';
				html +=		'<td class="display_name column-display_name">';
				html +=			'<input name="users[' + user.name + '][display_name]" type="text" class="widefat" value="' + nicename + '">';
				html +=		'</td>';
				html +=		'<td class="role column-role">';
				html +=			roleselect;
				html +=		'</td>';
				break;
			case 'posts' : 
			case 'pages' : 
				var post = selectedData[i];
				var user = libraries[library]['users'][post.author];

				statusselect = '<select name="posts[' + post.name + '][post_status]" class="widefat">';
				for ( status in statuses ) {
					var selected = '';
					if ( status === post.status ) selected = 'selected';
					statusselect += '<option value="' + status + '" ' + selected + '>' + capitalize( statuses[status] ) + '</option>';
				}
				statusselect += '</select>';

				authorselect = '<select name="posts[' + post.name + '][post_author]" class="widefat">';
				authorselect +=		'<option value="' + post.author + '" ' + selected + '>' + post.author + '</option>';
				for ( var u in users ) {
					var selected = '';
					if ( u === post.author ) selected = 'selected';
					authorselect += '<option value="' + users[u].id + '" ' + selected + '>' + u + '</option>';
				}
				authorselect += '</select>';

				html +=		'<td class="post_title column-post_title">';
				html +=			'<input name="posts[' + post.name + '][post_title]" type="text" class="widefat" value="' + post.title + '" />';
				html +=			'<input name="posts[' + post.name + '][post_name]" type="text" class="widefat" value="' + post.name + '" />';
				html +=		'</td>';
				html +=		'<td class="post_author column-post_author">';
				html +=			authorselect;
				html +=			'<input name="posts[' + post.name + '][user][user_login]" type="hidden" value="' + post.author + '" />';
				html +=			'<input name="posts[' + post.name + '][user][user_email]" type="hidden" value="' + post.author + '@' + library + '.com" />';
				html +=			'<input name="posts[' + post.name + '][user][first_name]" type="hidden" value="' + user['fname'] + '" />';
				html +=			'<input name="posts[' + post.name + '][user][last_name]" type="hidden" value="' + user['lname'] + '" />';
				html +=			'<input name="posts[' + post.name + '][user][display_name]" type="hidden" value="' + user['fname'] + ' ' + user['lname'] + '" />';
				html +=			'<input name="posts[' + post.name + '][user][role]" type="hidden" value="' + user['role'] + '" />';
				html +=		'</td>';
				html +=		'<td class="post_content column-post_content">';
				html +=			'<textarea name="posts[' + post.name + '][post_content]" class="widefat">' + post.content + '</textarea>';
				html +=		'</td>';
				html +=		'<td class="post_date column-post_date">';
				html +=			'<input name="posts[' + post.name + '][post_date]" type="text" class="widefat" value="' + post.date + '" />';
				html +=		'</td>';
				html +=		'<td class="post_status column-post_status">';
				html +=			statusselect;
				html +=		'</td>';
				break;
			default:
				html +=		'';

			} 
			html +=	'</tr>';
		}

		jQuery( 'table.tools_page_patched-up-bots' ).prepend( jQuery( html ) ); 
		jQuery( '.dashicons-dismiss' ).on( 'click', function() { jQuery( this ).parent().parent().remove(); } );
	} );

} );



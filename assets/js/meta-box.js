/**
 * NPR Story API meta box functions and features
 */
document.addEventListener('DOMContentLoaded', () => {
	$ = jQuery;

	// contains the inputs
	$container = $( '#ds-npr-publish-actions' );

	// cached input values, based on the default values of the form
	c_api = $container.find( '#send_to_api' ).prop( 'checked' );
	c_org = $container.find( '#send_to_org' ).prop( 'checked' );
	c_one = $container.find( '#send_to_one' ).prop( 'checked' );
	c_featured = $container.find( '#nprone_featured' ).prop( 'checked' );

	// Set up the checkboxes once everything is loaded
	validitycheck( null );

	// initialization: go through the list of checkboxes and uncheck them if their values are not valid
	function validitycheck( event ) {
		$api = $container.find( '#send_to_api' );
		$org = $container.find( '#send_to_org' );
		$one = $container.find( '#send_to_one' );
		$featured = $container.find( '#nprone_featured' );

		// start at the top of the form and work our way down
		if ( $api.prop( 'checked' ) !== c_api ) {
			c_api = $api.prop( 'checked' );
		}

		// uncheck lower checkboxes if they are invalid
		if ( false === c_api ) {
			$org.prop( 'checked', false ).prop( 'disabled', true );
			$one.prop( 'checked', false ).prop( 'disabled', true );
			$featured.prop( 'checked', false ).prop( 'disabled', true );
			c_org = false;
			c_one = false;
		} else {
			$org.prop( 'disabled', false );
			$one.prop( 'disabled', false );
			// $featured will be checked or unchecked by the value of $one later in this initialization function
		}

		// start at the top of the form and work our way down
		c_one = $one.prop( 'checked' );

		// change the disabled state of the featured checkbox
		if ( true === c_one ) {
			$featured.prop( 'disabled', false );
		}
	}

	// Upon update, do the thing
	$container.find( 'input' ).on( 'change', li_checking );

	/*
	 * If a checkbox in an li gets unchecked, uncheck and disable its child li
	 * If a checkbox in an li gets checked, enable its child li
	 */
	function li_checking( event ) {
		checked = this.checked;
		$results = $( this ).closest( 'li' ).children( 'ul' ).children( 'li' ); // only get the first level of lis
		console.log( $results );
		$results.each( function( element ) {
			if ( checked ) {
				$( this ).children( 'label' ).children( 'input' ).prop( 'disabled', false );
				// in this case there is no need to trigger the change event on the input, because the children will be updated when the parent's box is checked
			} else {
				$( this ).children( 'label' ).children( 'input' ).prop( 'disabled', true ).prop( 'checked', false ).trigger( 'change' );
				// here, though, we need to invalidate the children when their parent changes, so here we trigger the change check.
			}
		});
	}
});

/**
 * NPR Story API meta box functions and features
 */
document.addEventListener('DOMContentLoaded', () => {
	$ = jQuery;

	// contains the inputs
	$container = $( '#ds-npr-publish-actions' );

	// initialize the form
	$container.find( 'input' ).on( 'change', li_checking );

	// Upon update, do the thing
	li_checking.call( $container.find( '#send_to_api' ) );

	/*
	 * If a checkbox in an li gets unchecked, uncheck and disable its child li
	 * If a checkbox in an li gets checked, enable its child li
	 */
	function li_checking( event ) {
		checked = this.checked;
		$results = $( this ).closest( 'li' ).children( 'ul' ).children( 'li' ); // Only get the first level of list.
		$results.each( function( element ) {
			if ( checked ) {
				$( this ).children( 'label' ).children( 'input' ).prop( 'disabled', false );
				// In this case there is no need to trigger the change event on the input,
				// because the children will be updated when the parent's box is checked.
			} else {
				recurse = $( this ).children( 'label' ).children( 'input' ).prop( 'disabled', true ).prop( 'checked', false );
				li_checking.call( recurse );
				// Here, though, we need to invalidate the children when their parent changes,
				// so here we call this function on the appropriate child.
				// Triggering the change event on the child does not work.
			}
		});
	}

	// edit the time selector
	$( '#nprone-expiry-edit' ).on( 'click', function( event ) {
		event.preventDefault();
		$( '#nprone-expiry-form' ).toggleClass( 'hidden' );
		$( this ).toggleClass( 'hidden' );
	});
	// close the time selector
	$( '#nprone-expiry-cancel' ).on( 'click', function( event ) {
		event.preventDefault();
		$( '#nprone-expiry-form' ).toggleClass( 'hidden' );
		$( '#nprone-expiry-edit' ).toggleClass( 'hidden' );
	});
	// save the time selector
	$( '#nprone-expiry-save' ).on( 'click', function( event ) {
		event.preventDefault();
		$( '#nprone-expiry-form' ).toggleClass( 'hidden' );
		$( '#nprone-expiry-edit' ).toggleClass( 'hidden' );

		// but then it needs to update the displayed data in #nprone-expiry-display. How is it to do that?
		// Is there an underscore template that we can fill with the data?
	});
});

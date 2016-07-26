(function($) {
	$(document).ready(function() {
		var ajax_url = data_baianada.ajax_url;

		if ( $( '.lb-rpbr_restrict_access:checked' ).length > 0 ) {
			$( '.lb-rpbr_box-select-role' ).css( 'display', 'block' );
		}

		$( '.lb-rpbr_restrict_access' ).change( function() {
			if ( $( '.lb-rpbr_restrict_access:checked' ).length > 0 ) {
				$( '.lb-rpbr_box-select-role' ).fadeIn(300);
			} else {
				$( '.lb-rpbr_box-select-role' ).fadeOut(300);
			}
		});
	});
})(jQuery);

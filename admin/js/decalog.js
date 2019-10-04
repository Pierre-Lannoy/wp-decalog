jQuery(document).ready( function($) {
	$('.decalog-about-logo').css({opacity:1});
	$('#decalog_listeners_options_auto').on('change', function() {
		if( 'auto' === this.value ) {
			$('#listeners-settings').addClass('hidden');
		} else {
			$('#listeners-settings').removeClass('hidden');
		}
	});
	$('#decalog_listeners_options_auto').change();
} );

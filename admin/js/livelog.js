jQuery( document ).ready(
	function($) {
		function initialize() {
			$('#decalog-select-level').change(function () {
				level = $(this).val();
			});
			$('#decalog-select-format').change(function () {
				mode = $(this).val();
			});
			$('#decalog-control-play').click(function () {
				consoleRun();
			});
			$('#decalog-control-pause').click(function () {
				consolePause();
			});
		}
		function consoleRun() {
			document.querySelector( '#decalog-control-pause' ).classList.remove( 'decalog-control-inactive' );
			document.querySelector( '#decalog-control-pause' ).classList.add( 'decalog-control-active' );
			document.querySelector( '#decalog-control-play' ).classList.remove( 'decalog-control-active' );
			document.querySelector( '#decalog-control-play' ).classList.add( 'decalog-control-inactive' );
			document.querySelector( '.decalog-control-hint' ).innerHTML = 'running&nbsp;&nbsp;&nbsp;🟢';
			running = true;
		}
		function consolePause() {
			document.querySelector( '#decalog-control-play' ).classList.remove( 'decalog-control-inactive' );
			document.querySelector( '#decalog-control-play' ).classList.add( 'decalog-control-active' );
			document.querySelector( '#decalog-control-pause' ).classList.remove( 'decalog-control-active' );
			document.querySelector( '#decalog-control-pause' ).classList.add( 'decalog-control-inactive' );
			document.querySelector( '.decalog-control-hint' ).innerHTML = 'paused&nbsp;&nbsp;&nbsp;🟠';
			running = false;
		}
		function loadLines() {
			if ( running ) {
				if ( '0' === index ) {
					elem = document.createElement( 'pre' );
					elem.classList.add( 'decalog-logger-line' );
					elem.classList.add( 'decalog-logger-line-init' );
					elem.innerHTML = 'Console initialization...';
					root.appendChild( elem );
					init = true;
				}
				$.ajax(
					{
						type : 'GET',
						url : livelog.restUrl,
						data : { level: level, mode: mode, index: index },
						beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', livelog.restNonce ); },
						success: function( response ) {
							if ( response ) {
								if ( undefined !== response.index ) {
									index = response.index;
								}
								if ( undefined !== response.items ) {
									items = Object.entries( response.items );
									if ( items.length > 0 ) {
										if ( init ) {
											root.removeChild( root.firstElementChild );
											init = false;
											consoleRun();
										}
										items.forEach(
											function( item ){
												elem = document.createElement( 'pre' );
												elem.classList.add( 'decalog-logger-line' );
												elem.classList.add( 'decalog-logger-line-' + item[1].level );
												elem.innerHTML = item[1].line.replace( ' ', '&nbsp;' );
												if ( root.childElementCount > livelog.buffer ) {
													root.removeChild( root.firstElementChild );
												}
												root.appendChild( elem );
												$('#decalog-logger-lines').animate( { scrollTop: elem.offsetTop }, 20 );
											}
										);
									}
								}
							}
						},
						/*error: function( response ) {
                            console.log( response );
                        },*/
						complete:function( response ) {
							setTimeout( loadLines, livelog.frequency );
						}
					}
				);
			} else {
				setTimeout( loadLines, 250 );
			}
		}

		let level   = 'info';
		let mode    = 'wp';
		let index   = '0';
		let running = true;
		let init    = false;
		const root  = document.querySelector( '#decalog-logger-lines' );

		initialize();
		loadLines();

	}
);

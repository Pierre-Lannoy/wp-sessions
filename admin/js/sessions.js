jQuery(document).ready( function($) {
	$('.pose-about-logo').css({opacity:1});
	$( ".pose-select" ).each(
		function() {
			var chevron  = 'data:image/svg+xml;base64,PHN2ZwogIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICB3aWR0aD0iMjQiCiAgaGVpZ2h0PSIyNCIKICB2aWV3Qm94PSIwIDAgMjQgMjQiCiAgZmlsbD0ibm9uZSIKICBzdHJva2U9IiM3Mzg3OUMiCiAgc3Ryb2tlLXdpZHRoPSIyIgogIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIKICBzdHJva2UtbGluZWpvaW49InJvdW5kIgo+CiAgPHBvbHlsaW5lIHBvaW50cz0iNiA5IDEyIDE1IDE4IDkiIC8+Cjwvc3ZnPgo=';
			var classes  = $( this ).attr( "class" ),
				id           = $( this ).attr( "id" ),
				name         = $( this ).attr( "name" );
			var template = '<div class="' + classes + '">';
			template    += '<span class="pose-select-trigger">' + $( this ).attr( "placeholder" ) + '&nbsp;<img style="width:18px;vertical-align:top;" src="' + chevron + '" /></span>';
			template    += '<div class="pose-options">';
			$( this ).find( "option" ).each(
				function() {
					template += '<span class="pose-option ' + $( this ).attr( "class" ) + '" data-value="' + $( this ).attr( "value" ) + '">' + $( this ).html().replace("~-", "<br/><span class=\"pose-option-subtext\">").replace("-~", "</span>") + '</span>';
				}
			);
			template += '</div></div>';

			$( this ).wrap( '<div class="pose-select-wrapper"></div>' );
			$( this ).after( template );
		}
	);
	$( ".pose-option:first-of-type" ).hover(
		function() {
			$( this ).parents( ".pose-options" ).addClass( "option-hover" );
		},
		function() {
			$( this ).parents( ".pose-options" ).removeClass( "option-hover" );
		}
	);
	$( ".pose-select-trigger" ).on(
		"click",
		function() {
			$( 'html' ).one(
				'click',
				function() {
					$( ".pose-select" ).removeClass( "opened" );
				}
			);
			$( this ).parents( ".pose-select" ).toggleClass( "opened" );
			event.stopPropagation();
		}
	);
	$( ".pose-option" ).on(
		"click",
		function() {
			$(location).attr("href", $( this ).data( "value" ));
		}
	);
	$( "#pose-chart-button-calls" ).on(
		"click",
		function() {
			$( "#pose-chart-calls" ).addClass( "active" );
			$( "#pose-chart-button-calls" ).addClass( "active" );
		}
	);




} );

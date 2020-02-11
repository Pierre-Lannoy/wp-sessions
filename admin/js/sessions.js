jQuery(document).ready( function($) {
	$('.pose-about-logo').css({opacity:1});

	$( "#pose-chart-button-user" ).on(
		"click",
		function() {
			$( "#pose-chart-user" ).addClass( "active" );
			$( "#pose-chart-session" ).removeClass( "active" );
			$( "#pose-chart-turnover" ).removeClass( "active" );
			$( "#pose-chart-log" ).removeClass( "active" );
			$( "#pose-chart-password" ).removeClass( "active" );
			$( "#pose-chart-button-user" ).addClass( "active" );
			$( "#pose-chart-button-session" ).removeClass( "active" );
			$( "#pose-chart-button-turnover" ).removeClass( "active" );
			$( "#pose-chart-button-log" ).removeClass( "active" );
			$( "#pose-chart-button-password" ).removeClass( "active" );
		}
	);
	$( "#pose-chart-button-session" ).on(
		"click",
		function() {
			$( "#pose-chart-user" ).removeClass( "active" );
			$( "#pose-chart-session" ).addClass( "active" );
			$( "#pose-chart-turnover" ).removeClass( "active" );
			$( "#pose-chart-log" ).removeClass( "active" );
			$( "#pose-chart-password" ).removeClass( "active" );
			$( "#pose-chart-button-user" ).removeClass( "active" );
			$( "#pose-chart-button-session" ).addClass( "active" );
			$( "#pose-chart-button-turnover" ).removeClass( "active" );
			$( "#pose-chart-button-log" ).removeClass( "active" );
			$( "#pose-chart-button-password" ).removeClass( "active" );
		}
	);
	$( "#pose-chart-button-turnover" ).on(
		"click",
		function() {
			$( "#pose-chart-user" ).removeClass( "active" );
			$( "#pose-chart-session" ).removeClass( "active" );
			$( "#pose-chart-turnover" ).addClass( "active" );
			$( "#pose-chart-log" ).removeClass( "active" );
			$( "#pose-chart-password" ).removeClass( "active" );
			$( "#pose-chart-button-user" ).removeClass( "active" );
			$( "#pose-chart-button-session" ).removeClass( "active" );
			$( "#pose-chart-button-turnover" ).addClass( "active" );
			$( "#pose-chart-button-log" ).removeClass( "active" );
			$( "#pose-chart-button-password" ).removeClass( "active" );
		}
	);
	$( "#pose-chart-button-log" ).on(
		"click",
		function() {
			$( "#pose-chart-user" ).removeClass( "active" );
			$( "#pose-chart-session" ).removeClass( "active" );
			$( "#pose-chart-turnover" ).removeClass( "active" );
			$( "#pose-chart-log" ).addClass( "active" );
			$( "#pose-chart-password" ).removeClass( "active" );
			$( "#pose-chart-button-user" ).removeClass( "active" );
			$( "#pose-chart-button-session" ).removeClass( "active" );
			$( "#pose-chart-button-turnover" ).removeClass( "active" );
			$( "#pose-chart-button-log" ).addClass( "active" );
			$( "#pose-chart-button-password" ).removeClass( "active" );
		}
	);
	$( "#pose-chart-button-password" ).on(
		"click",
		function() {
			$( "#pose-chart-user" ).removeClass( "active" );
			$( "#pose-chart-session" ).removeClass( "active" );
			$( "#pose-chart-turnover" ).removeClass( "active" );
			$( "#pose-chart-log" ).removeClass( "active" );
			$( "#pose-chart-password" ).addClass( "active" );
			$( "#pose-chart-button-user" ).removeClass( "active" );
			$( "#pose-chart-button-session" ).removeClass( "active" );
			$( "#pose-chart-button-turnover" ).removeClass( "active" );
			$( "#pose-chart-button-log" ).removeClass( "active" );
			$( "#pose-chart-button-password" ).addClass( "active" );
		}
	);




} );

( function () {
	'use strict';

	var PROVIDERS = {
		custom: { host: '', port: 587, username: '', signup: '' },
		brevo: { host: 'smtp-relay.brevo.com', port: 587, username: '', signup: 'https://app.brevo.com/account/register' },
		mailgun: { host: 'smtp.mailgun.org', port: 587, username: '', signup: 'https://signup.mailgun.com/new/signup' },
		sendgrid: { host: 'smtp.sendgrid.net', port: 587, username: 'apikey', signup: 'https://signup.sendgrid.com/' }
	};

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		var form = document.getElementById( 'nes-smtp-form' );
		if ( ! form ) {
			return;
		}

		var select = document.getElementById( 'nes-provider-select' );
		var signupLink = document.getElementById( 'nes-provider-signup' );
		var cards = form.querySelectorAll( '.nes-provider-card' );
		var modeInputs = form.querySelectorAll( 'input[name="connection_mode"]' );
		var hostField = document.getElementById( 'nes-smtp-host' );
		var portField = document.getElementById( 'nes-smtp-port' );
		var userField = document.getElementById( 'nes-smtp-username' );
		var mailgunRows = form.querySelectorAll( '.nes-field-mailgun-domain' );
		var smtpTable = form.querySelector( '.nes-mode-smtp' );
		var apiTable = form.querySelector( '.nes-mode-api' );

		function highlightCard( provider ) {
			cards.forEach( function ( card ) {
				card.classList.toggle( 'is-selected', card.getAttribute( 'data-provider' ) === provider );
			} );
		}

		function applyProviderPreset( provider, fillFields ) {
			var meta = PROVIDERS[ provider ] || PROVIDERS.custom;

			if ( signupLink ) {
				if ( meta.signup ) {
					signupLink.href = meta.signup;
					signupLink.hidden = false;
				} else {
					signupLink.hidden = true;
				}
			}

			if ( fillFields ) {
				if ( hostField ) {
					hostField.value = meta.host;
				}
				if ( portField ) {
					portField.value = meta.port;
				}
				if ( userField && meta.username ) {
					userField.value = meta.username;
				}
			}

			mailgunRows.forEach( function ( row ) {
				row.classList.toggle( 'is-hidden', 'mailgun' !== provider );
			} );
		}

		function applyMode( mode ) {
			if ( smtpTable ) {
				smtpTable.classList.toggle( 'is-hidden', 'smtp' !== mode );
			}
			if ( apiTable ) {
				apiTable.classList.toggle( 'is-hidden', 'api' !== mode );
			}
			var selectedProvider = select ? select.value : 'custom';
			mailgunRows.forEach( function ( row ) {
				row.classList.toggle( 'is-hidden', ! ( 'api' === mode && 'mailgun' === selectedProvider ) );
			} );
		}

		cards.forEach( function ( card ) {
			card.addEventListener( 'click', function () {
				var provider = card.getAttribute( 'data-provider' );
				if ( select ) {
					select.value = provider;
				}
				highlightCard( provider );
				applyProviderPreset( provider, true );
				var mode = form.querySelector( 'input[name="connection_mode"]:checked' );
				applyMode( mode ? mode.value : 'smtp' );
			} );
		} );

		if ( select ) {
			select.addEventListener( 'change', function () {
				highlightCard( select.value );
				applyProviderPreset( select.value, true );
				var mode = form.querySelector( 'input[name="connection_mode"]:checked' );
				applyMode( mode ? mode.value : 'smtp' );
			} );
		}

		modeInputs.forEach( function ( input ) {
			input.addEventListener( 'change', function () {
				if ( input.checked ) {
					applyMode( input.value );
				}
			} );
		} );

		var initialProvider = select ? select.value : 'custom';
		highlightCard( initialProvider );
		applyProviderPreset( initialProvider, false );
		var initialMode = form.querySelector( 'input[name="connection_mode"]:checked' );
		applyMode( initialMode ? initialMode.value : 'smtp' );
	} );
}() );

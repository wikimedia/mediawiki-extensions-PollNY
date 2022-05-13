/**
 * Allows users to search for existing media (images) and use them on polls instead of
 * being forced to upload an image should they wish to use an image on a poll.
 *
 * Originally bastardized from CollaborationKit's ext.CollaborationKit.hubtheme.js by bawolff, hare & Isarra
 * (for FanBoxes, from where I copied it to PollNY). Kudos!
 *
 * @date 13 May 2022
 */
/**
 * @param $
 * @param mw
 * @param OO
 */
// eslint-disable-next-line wrap-iife
( function ( $, mw, OO ) {
	'use strict';

	var getThumbnail, ImageProcessDialog, openImageBrowser, setupPage;

	/**
	 * Get an image thumbnail with 75px width
	 *
	 * @param {string} filename
	 * @return {jQuery} promise
	 */
	getThumbnail = function ( filename ) {
		return new mw.Api().get( {
			action: 'query',
			titles: filename,
			prop: 'imageinfo',
			iiprop: 'url',
			formatversion: 2,
			iiurlwidth: 75
		} );
	};

	/**
	 * Subclass ProcessDialog.
	 *
	 * @class ImageProcessDialog
	 * @extends OO.ui.ProcessDialog
	 *
	 * @constructor
	 * @param {Object} config
	 */
	ImageProcessDialog = function ( config ) {
		ImageProcessDialog.super.call( this, config );
	};
	OO.inheritClass( ImageProcessDialog, OO.ui.ProcessDialog );

	// Specify a static title and actions.
	ImageProcessDialog.static.title = mw.msg( 'poll-image-picker' );
	ImageProcessDialog.static.name = 'poll-image-picker';
	ImageProcessDialog.static.actions = [
		{ action: 'save', label: mw.msg( 'poll-image-picker-select' ), flags: 'primary' },
		{ label: mw.msg( 'cancel' ), flags: 'safe' }
	];

	/**
	 * Use the initialize() method to add content to the dialog's $body,
	 * to initialize widgets, and to set up event handlers.
	 */
	ImageProcessDialog.prototype.initialize = function () {
		var defaultSearchTerm;

		ImageProcessDialog.super.prototype.initialize.apply( this, arguments );

		defaultSearchTerm = '';

		this.content = new mw.widgets.MediaSearchWidget();
		this.content.getQuery().setValue( defaultSearchTerm );
		this.$body.append( this.content.$element );
	};

	/**
	 * In the event "Select" is pressed
	 *
	 * @param action
	 */
	ImageProcessDialog.prototype.getActionProcess = function ( action ) {
		var dialog, fileTitle;

		dialog = this;
		dialog.pushPending();

		if ( action ) {
			return new OO.ui.Process( function () {
				var fileObj, fileUrl, fileTitleObj;

				fileObj = dialog.content.getResults().findSelectedItem();
				if ( fileObj === null ) {
					return dialog.close().closed;
				}
				getThumbnail( fileObj.getData().title )
					.done( function ( data ) {
						fileUrl = data.query.pages[ 0 ].imageinfo[ 0 ].thumburl;
						// fileHeight = data.query.pages[ 0 ].imageinfo[ 0 ].thumbheight;
						fileTitleObj = new mw.Title( fileObj.getData().title );
						// I was seeing super weird results w/ this original code,
						// namely the stored file name would be Valid_file_name.ext.undefined,
						// "undefined" being fileTitleObj.ext here.
						// So, uh, let's check that it's something else before proceeding?
						if ( fileTitleObj.ext !== undefined ) {
							fileTitle = fileTitleObj.title + '.' + fileTitleObj.ext;
						} else {
							fileTitle = fileTitleObj.title;
						}

						// Generate preview
						$( '#poll_image' ).html(
							// The <div> here is both for consistency w/ completeImageUpload() in Poll.js
							// as well as because it looks nice. It should be properly, fully moved to the
							// CSS file and both JS files should use the appropriate new class(/ID) instead...
							'<div style="margin:0px 0px 10px 0px;">' +
							'<img height="75" width="75" src="' + fileUrl + '" />' +
							'</div>'
						);

						// Set form value
						$( 'input[name="poll_image_name"]' ).val( fileTitle );
						dialog.close( { action: action } );
					} );
			} );
		}

		// Fallback to parent handler.
		return ImageProcessDialog.super.prototype.getActionProcess.call( this, action );
	};

	/**
	 * Get dialog height.
	 */
	ImageProcessDialog.prototype.getBodyHeight = function () {
		return 600;
	};

	/**
	 * Create and append the window manager.
	 */
	openImageBrowser = function () {
		var windowManager, processDialog;

		windowManager = new OO.ui.WindowManager();
		$( 'body' ).append( windowManager.$element );

		// Create a new dialog window.
		processDialog = new ImageProcessDialog( {
			size: 'large'
		} );

		// Add windows to window manager using the addWindows() method.
		windowManager.addWindows( [ processDialog ] );

		// Open the window.
		windowManager.openWindow( processDialog );
	};

	/**
	 * Initial setup function run when DOM loaded.
	 */
	setupPage = function () {
		var imageBrowserButton, $selectorWidget;

		// Defining the button
		imageBrowserButton = new OO.ui.ButtonWidget( {
			icon: 'imageAdd',
			classes: [ 'mw-poll-image-picker-widget-inlinebutton' ],
			label: mw.msg( 'poll-image-picker-launch-button' )
		} );
		imageBrowserButton.on( 'click', openImageBrowser );

		// eslint-disable-next-line no-jquery/no-parse-html-literal
		$selectorWidget = $( '<div class="mw-poll-image-picker-widget"></div>' )
			.append(
				$( '<div>' ).append( imageBrowserButton.$element )
			);

		// Inject it above the uploading form
		$( '#real-form' ).prepend( $selectorWidget );
	};

	$( setupPage );

} )( jQuery, mediaWiki, OO );

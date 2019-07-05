/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */
CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	//config.toolbar = 'Docmgr';
};

CKEDITOR.config.toolbar_Docmgr = 
	[
		{ name: 'document', items: [ 'Source','-','Preview','Print' ] },
		{ name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
		{ name: 'tools', items: [ 'Scayt','RemoveFormat'] },
		{ name: 'moretools', items: [ 'Find','Maximize','ShowBlocks' ] },
		{ name: 'others', items: [ '-' ] },
		{ name: 'about', items: [ 'About' ] },
		'/',
		{ name: 'paragraph', items: [ 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'] },
		{ name: 'lists', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote' ] },
		{ name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
		{ name: 'insert', items: [ 'CustomImage', 'Table', 'HorizontalRule', 'SpecialChar','Smiley','Subscript','Superscript']},
		'/',
		{ name: 'basicstyles', items: [ 'Bold', 'Italic', 'Strike']},
		{ name: 'styles', items: [ 'Styles','Format','Font','FontSize','TextColor','BGColor' ] },
	];

CKEDITOR.config.toolbar_Email = 
	[
		{ name: 'document', items: [ 'Source'] },
		{ name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
		{ name: 'tools', items: [ 'Scayt','RemoveFormat'] },
		{ name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
		{ name: 'insert', items: [ 'SpecialChar','Smiley','Subscript','Superscript' ] },
		{ name: 'about', items: [ 'About' ] },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Strike'] },
		{ name: 'paragraph', groups: [ 'justify'], items: [ 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'] },
		{ name: 'lists', groups: ['list', 'indent', 'blocks' ], items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote' ] },
		{ name: 'styles', items: [ 'Styles','Format','Font','FontSize','TextColor','BGColor' ] },
	];

/**
	Finally, register the dialog.
	*/
CKEDITOR.dialog.add( 'CustomImage', SITE_URL + 'ckeditor/dialogs/customimage/customimage.js' );

function addDialogs(editor)
{

	// Register the command used to open the dialog.
	editor.addCommand( 'customImageCmd', new CKEDITOR.dialogCommand( 'CustomImage' ) );

	// Add the a custom toolbar buttons, which fires the above
	// command..
	editor.ui.addButton( 'CustomImage',
		{
			label : 'Image',	 
			command : 'customImageCmd',
			icon: SITE_URL + 'ckeditor/dialogs/customimage/image.png'
		} );

}

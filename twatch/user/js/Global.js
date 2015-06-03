function ardeUserFullUrl( path ) {
	var url = new ArdeUrlWriter( path );
	url.setParam( 'profile', ardeUserProfile, 'default' );
	url.setParam( 'lang', ardeLocale.id, ardeLocale.defaultId );
	return url.getUrl();
}

function activateLinkSelect( id ) {
	var select = document.getElementById( id );
	var selectedIndex = select.selectedIndex;
	select.onchange = function () {
		if( select.selectedIndex == selectedIndex ) return;
		window.location = select.options[ select.selectedIndex ].value;
	};
}
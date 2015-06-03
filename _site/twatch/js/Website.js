
	/********************************************************************/
	/*                                                                  */
	/*      Copyright (C) 2004 Arash Dejkam, All Rights Reserved.       */
	/*      http://www.tracewatch.com                                   */
	/*                                                                  */
	/*      Please read the licence file distributed with this          */
	/*      file or the one available at tracewatch.com for             */
	/*      the terms under which you can use or modify this file.      */
	/*                                                                  */
	/********************************************************************/
    
function WebsiteList( parentWebsite, defaultWebsiteId, websiteIds, websiteNames ) {
	this.defaultWebsiteId = defaultWebsiteId; 
	this.ArdeComponent( 'div' );
	
	
	
	if( !parentWebsite ) {
		this.defSelect = new ArdeSelect();
		this.fillWebsiteSelectsWithArray( websiteIds, websiteNames );
		this.defApplyButton = new ArdeRequestButton( 'Apply' );
		this.defApplyButton.setStandardCallbacks( this, 'defApply' );
		this.defRestoreButton = new ArdeRequestButton( 'Use '+( selectedUser.type == User.USER && !configMode ? 'Group\'s ' : '' )+'Default' );
		this.defRestoreButton.setStandardCallbacks( this, 'defRestore' );
		var topBox = ardeE( 'div' ).cls( 'group' ).appendTo( this );
		var p = ardeE( 'p' ).appendTo( topBox );
		p.append( ardeT( 'Default website' + ( configMode ? '' : ' for ' ) ) );
		if( !configMode ) {
			p.append( selectedUser.getName() );
		}
		p.append( ardeT( ': ' ) ).append( this.defSelect );
		p.append( ardeT( ' '+ardeNbsp(3) ) ).append( this.defApplyButton ).append( this.defRestoreButton );
	}
	
	if( !configMode ) {
		if( parentWebsite ) this.cls( 'indent_pad' );
		this.parentWebsite = parentWebsite;
		
		this.ArdeComponentList( new ArdeComponent( 'div' ).appendTo( this ) );
		
		this.newWebsite = new NewWebsite( this );
		this.append( this.newWebsite );
	}
}

WebsiteList.prototype.defApplyClicked = function () {
	this.defApplyButton.request( twatchFullUrl( 'rec/rec_users.php' ), 'a=set_def_website&v='+this.defSelect.selectedOption().value );
};

WebsiteList.prototype.defApplyConfirmed = function () {
	this.defaultWebsiteId = this.defSelect.selectedOption().value;
};

WebsiteList.prototype.defRestoreClicked = function () {
	this.defRestoreButton.request( twatchFullUrl( 'rec/rec_users.php' ), 'a=restore_def_website', ArdeXmlObjInteger );
};

WebsiteList.prototype.defRestoreConfirmed = function ( id ) {
	this.defaultWebsiteId = id;
	this.onchange();
};

WebsiteList.prototype.addItem = function( website ) {
	
	if( this.parentWebsite == null && website.parentId ) {
		for( var i in this.items ) {
			if( this.items[i].id == website.parentId ) {
				return this.items[ i ].websiteList.addItem( website );
			}
		}
	} else {
		return this.ArdeComponentList_addItem( website );
	}
};

WebsiteList.prototype.onchange = function () {
	if( !this.parentWebsite ) {
		this.fillWebsiteSelects();
	}
};

WebsiteList.prototype.fillWebsiteSelects = function () {
	this.defSelect.clear();
	ids = [];
	names = [];
	for( var i in this.items ) {
		if( this.items[i].parentId ) continue;
		if( !this.items[i].permission.value ) continue;
		names.push( this.items[i].name );
		ids.push( this.items[i].id );
	}
	this.fillWebsiteSelectsWithArray( ids, names );
};

WebsiteList.prototype.fillWebsiteSelectsWithArray = function ( ids, names ) {

	for( var i in ids ) {
		var o = new ArdeOption( names[i], ids[i] );
		if( ids[i] == this.defaultWebsiteId ) o.setSelected( true );
		this.defSelect.append( o );
	}
};


ArdeClass.extend( WebsiteList, ArdeComponent );
ArdeClass.extend( WebsiteList, ArdeComponentList );

function Website( id, name, handle, parentId, domains, cookieDomain, cookieFolder, permission ) {
	this.id = id;
	this.name = name;
	this.handle = handle;
	this.parentId = parentId;
	this.domains = domains;
	this.cookieDomain = cookieDomain;
	this.cookieFolder = cookieFolder;
	this.permission = permission;
	this.ArdeComponent( 'div' );
	
	websiteDiv = ardeE( 'div' ).cls( !(this instanceof NewWebsite) ? 'block' : 'special_block' ).appendTo( this );

	if ( !( this instanceof NewWebsite ) && !this.parentId ) {
		var floater = new ArdeComponent( 'div' ).setFloat( 'right' ).style( 'marginRight', '-10px' ).appendTo( websiteDiv );

		this.upButton = new ArdeRequestButton( 'Up' );
		this.downButton = new ArdeRequestButton( 'Down' );
		floater.append( ardeE( 'p' ).append( this.upButton ).append( this.downButton ) );
		
		this.upButton.setStandardCallbacks( this, 'up' );
		this.downButton.setStandardCallbacks( this, 'down' );
		
	}
	
	if( !( this instanceof NewWebsite ) ) {
		if( this.parentId ) {
			var txt = ardeT( this.name );
		} else {
			var txt = ardeT( this.name );
		}
	} else {
		if( this.websiteList.parentWebsite ) {
			var txt = ardeT( 'New Sub-Website' );
		} else {
			var txt = ardeT( 'New Website' );
		}
	}
	websiteDiv.append( ardeE( 'p' ).style( 'fontWeight', 'bold' ).append( txt ) );

	var tb = ardeE( 'tbody' ).appendTo( ardeE( 'table' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( websiteDiv ) );	
	var topTr = ardeE( 'tr' ).appendTo( tb );
	var td = ardeE( 'td' ).style( 'verticalAlign', 'top' ).appendTo( topTr );
	
	tb = ardeE( 'tbody' ).appendTo( ardeE( 'table' ).cls( 'form' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( td ) );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	ardeE( 'td' ).cls( 'head' ).append( ardeT( 'Name: ' ) ).appendTo( tr );
	this.nameInput = ardeE( 'input' ).n;
	if( this instanceof Website ) {
		this.nameInput.value = this.name;
	}
	ardeE( 'td' ).cls( 'tail' ).append( this.nameInput ).appendTo( tr );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	ardeE( 'td' ).cls( 'head' ).append( ardeT( 'ID: ' ) ).appendTo( tr );
	this.handleInput = ardeE( 'input' ).n;
	if( !( this instanceof NewWebsite ) ) {
		this.handleInput.value = this.handle;
	}
	ardeE( 'td' ).cls( 'tail' ).append( this.handleInput ).appendTo( tr );
	
	td = ardeE( 'td' ).appendTo( topTr );
	tb = ardeE( 'tbody' ).appendTo( ardeE( 'table' ).cls( 'form' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( td ) );
	tr = ardeE( 'tr' ).appendTo( tb );
	
	
	ardeE( 'td' ).cls( 'head' ).style( 'verticalAlign', 'top' ).append( ardeT( 'domains: ' ) ).appendTo( tr );
	
	
	this.domainInput = ardeE( 'input' ).n;
	this.domainInput.size = 30;
	
	this.domainAddButton = document.createElement( 'input' );
	this.domainAddButton.type = 'button';
	this.domainAddButton.value = 'ADD';
	this.domainAddButton.className = 'passive';
	
	this.domainsSelect = document.createElement( 'select' );
	this.domainsSelect.size = 4;
	this.domainsSelect.style.width = 270+'px';
	
	if( !(this instanceof NewWebsite) ) {
		for( var i in this.domains ) {		
			var option = ardeE( 'option' ).append( ardeT( this.domains[i] ) ).n;
			option.value = this.domains[i];
			this.domainsSelect.appendChild( option );
		}
	}

	this.domainDeleteButton = document.createElement( 'input' );
	this.domainDeleteButton.type = 'button';
	this.domainDeleteButton.value = 'Delete Selected';
	this.domainDeleteButton.className = 'passive';
	
	var self = this;
	this.domainAddButton.onclick = function() {
		if( self.domainInput.value != '' ) {
			var option = ardeE( 'option' ).append( ardeT( self.domainInput.value ) ).n;
			option.value = self.domainInput.value;
			self.domainsSelect.appendChild( option );
			self.domainInput.value = '';
		}
	};
	
	this.domainDeleteButton.onclick = function() {
		if( self.domainsSelect.selectedIndex >= 0 ) {
			self.domainsSelect.options[ self.domainsSelect.selectedIndex ] = null;
		}
	};
	
	var td = ardeE( 'td' ).cls( 'tail' );
	td.append( this.domainInput ).append( this.domainAddButton );
	td.append( ardeE( 'br' ) ).append( this.domainsSelect );
	td.append( ardeE( 'br' ) ).append( this.domainDeleteButton );
	td.appendTo( tr );
	
	this.buttonsP = ardeE( 'p' ).appendTo( ardeE( 'td' ).style( 'verticalAlign', 'top' ).style( 'width', '200px' ).style( 'textAlign', 'right' ).appendTo( topTr ) );
	
	
	this.cookieDomainInput = new ArdeInput( this.cookieDomain ).attr( 'size', '30' );
	this.cookieFolderInput = new ArdeInput( this.cookieFolder ).attr( 'size', '20' );
	websiteDiv.append( ardeE( 'p' ).append( ardeT( 'Cookie Domain: ' ) ).append( this.cookieDomainInput ).append( ardeT( ' Cookie Folder: ' ) ).append( this.cookieFolderInput ) );

	var p = ardeE( 'p' ).appendTo( websiteDiv );
	
	if( !( this instanceof NewWebsite ) ) {
		this.moreButton = new ArdeButton( 'More Info' ).cls( 'passive' ).setFloat( 'right' ).style( 'marginTop', '5px' );
		
		this.moreCloseButton = new ArdeButton( 'Less Info' ).cls( 'passive' ).setFloat( 'right' ).setDisplay( false ).style( 'marginTop', '5px' );
	
	
		p.append( this.moreButton ).append( this.moreCloseButton );
		this.morePane = new ArdeComponent( 'div' ).cls( 'group' ).setDisplay( false ).appendTo( websiteDiv );
		this.morePane.append( ardeE( 'p' ).append( ardeT( 'Internal ID: ' ) ).append( ardeE( 'span' ).cls( 'fixed' ).append( ardeT( this.id ) ) ) );
	}
	
	
	
	
	if( !(this instanceof NewWebsite) ) {
		this.applyButton = new ArdeRequestButton( 'Apply Changes' );
		p.append( this.applyButton ).append( ardeT( ' ' ) );
		
		if( this.id != 1 ) {
			this.deleteButton = new ArdeRequestButton( 'Delete Website', "Are you sure? this will also delete all tables and data related to website '"+this.name+"' and all of it's sub-websites from database." );
			this.deleteButton.button.cls( 'critical' );
			p.append( this.deleteButton ).append( ardeT( ' ' ) );
		}
		
		
	} else {
		this.addWebsiteButton = new ArdeRequestButton( 'Add Website' );
		p.append( this.addWebsiteButton ).append( ardeT( ' ' ) );
	}
	
	if( !(this instanceof NewWebsite) && !this.parentId ) {
		this.subWebsiteButton = new ArdeButton( 'New Sub Website' );
		this.subWebsiteButton.cls( 'passive' );
		p.append( this.subWebsiteButton );
	}
	
	if( !parentId && !( this instanceof NewWebsite ) ) {
		this.visibilitySelect = new UserPermissionSelector( this.permission );
		this.visApplyButton = new ArdeRequestButton( 'Apply Change' );
		this.visApplyButton.setStandardCallbacks( this, 'visApply' );
		if( selectedUser.isRoot() ) this.visApplyButton.button.setDisabled( true );
		var p =  ardeE( 'p' ).appendTo( ardeE( 'div' ).cls( 'sub_block' ).appendTo( websiteDiv ) );
		p.append( selectedUser.getName() ).append( ardeT( ' can view this website: ' ) ).append( this.visibilitySelect );
		p.append( ardeT( ' ' ) ).append( this.visApplyButton );
	}
	
	if( !( this instanceof NewWebsite ) ) {
		this.moreButton.element.onclick = function () {
			self.morePane.setDisplay( true );
			self.moreButton.setDisplay( false );
			self.moreCloseButton.setDisplay( true );
		};
		this.moreCloseButton.element.onclick = function () {
			self.morePane.setDisplay( false );
			self.moreCloseButton.setDisplay( false );
			self.moreButton.setDisplay( true );
			
		};
		
		if( !parentId ) {
			this.websiteList = new WebsiteList( this );
			this.websiteList.newWebsite.setDisplay( false );
			
			this.subWebsiteButton.element.onclick = function() {
				self.websiteList.newWebsite.switchDisplay( false );
				return false;
			};
			this.append( this.websiteList );
		}
		
		this.applyButton.setStandardCallbacks( this, 'apply' );
		
		if( this.id != 1 ) {
			this.deleteButton.setStandardCallbacks( this, 'delete' );
		}
	}
	
	
	
}

Website.prototype.visApplyClicked = function () {
	this.visApplyButton.request( twatchFullUrl( 'rec/rec_websites.php' ), 'a=set_vis&i='+this.id+'&'+this.visibilitySelect.getParams() );
};

Website.prototype.visApplyConfirmed = function () {
	if( this.visibilitySelect.defaultSelected() ) {
		this.permission.value = this.permisson.defaultValue;
		this.permission.isDefault = true;
	} else {
		this.permission.value = this.visibilitySelect.selectedValue();
		this.permission.isDefault = false;
	}
	this.ardeList.onchange();
};

Website.prototype.upClicked = function () {
	if( this.ardeList.positionOf( this ) == 0 ) return;
	this.upButton.request( twatchFullUrl( 'rec/rec_websites.php' ), 'a=up&i='+this.id );
};

Website.prototype.upConfirmed = function ( result ) {
	this.ardeList.moveItemUp( this );
};

Website.prototype.downClicked = function() {
	if( this.ardeList.positionOf( this ) == this.ardeList.length() - 1 ) return;
	this.downButton.request( twatchFullUrl( 'rec/rec_websites.php' ), 'a=down&i='+this.id );
};

Website.prototype.downConfirmed = function ( result ) {
	this.ardeList.moveItemDown( this );
};

Website.prototype.applyClicked = function() {
	var cls = this.parentId ? null : Permission;
	this.applyButton.request( twatchFullUrl( 'rec/rec_websites.php' ), 'a=change&'+this.getParams(), cls );
};

Website.prototype.applyConfirmed = function( permission ) {
	this.name = this.nameInput.value;
	this.handle = this.handleInput.value;
	this.domains = new Array();
	for( var i = 0; i < this.domainsSelect.options.length; ++i ) {
		this.domains.push( this.domainsSelect.options[ i ].value );
	}
	
	if( !this.parentId ) {
		
		this.visibilitySelect.setDefaultValue( permission.defaultValue );
		this.permission = permission;
	}
	this.ardeList.onchange();
};

Website.prototype.deleteClicked = function() {
	this.deleteButton.request( twatchFullUrl( 'rec/rec_websites.php' ), 'a=delete&i='+this.id );
};

Website.prototype.deleteConfirmed = function() {
	this.ardeList.removeItem( this );

};

Website.fromXml = function( element ) {
	var id = ArdeXml.intAttribute( element, 'id' );
	var name = ArdeXml.strElement( element, 'name' );
	var handle = ArdeXml.attribute( element, 'handle' );
	var parentId = ArdeXml.intAttribute( element, 'parent_id' );
	var domainsE = ArdeXml.element( element, 'domains' );
	var domainEs = new ArdeXmlElemIter( domainsE, 'domain' );
	var cookieDomain = ArdeXml.strElement( element, 'cookie_domain' );
	var cookieFolder = ArdeXml.strElement( element, 'cookie_folder' );
	var domains = new Array();
	while( domainEs.current ) {
		domains.push( ArdeXml.strContent( domainEs.current ) );
		domainEs.next();
	}
	return new Website( id, name, handle, parentId, domains, cookieDomain, cookieFolder, new BinaryValue( true, true, true ) );
};

Website.prototype.getParams = function() {
	var s = 'i='+this.id; 
	s += '&'+getWebsiteCommonParams( this );
	return s;
};

function getWebsiteCommonParams( component ) {
	s = '&n='+ardeEscape( component.nameInput.value );
	s += '&h='+ardeEscape( component.handleInput.value );
	s += '&p='+component.parentId;
	s += '&d=';
	for( var i = 0; i < component.domainsSelect.options.length; ++i ) {
		s += ardeEscape( (i?'|':'')+component.domainsSelect.options[ i ].value );
	}
	s += '&cd='+ardeEscape( component.cookieDomainInput.element.value );
	s += '&cf='+ardeEscape( component.cookieFolderInput.element.value );

	return s;
};

ArdeClass.extend( Website, ArdeComponent );

function NewWebsite( websiteList ) {
	
	
	
	this.websiteList = websiteList;
	
	if( this.websiteList.parentWebsite == null ) {
		parentId = 0;
	} else {
		parentId = this.websiteList.parentWebsite.id;
	}
	this.Website( null, '', '', parentId, [], '', '', null );
	


	
	var self = this;
	this.addWebsiteButton.onclick = function() { self.addWebsiteClicked(); };
	this.addWebsiteButton.afterResultReceived = function( result ) { self.addWebsiteConfirmed( result ); };
}
NewWebsite.prototype.addWebsiteClicked = function() {
	this.addWebsiteButton.request( twatchFullUrl( 'rec/rec_websites.php' ), 'a=add&'+this.getParams(), Website );
};

NewWebsite.prototype.addWebsiteConfirmed = function( website ) {
	this.websiteList.addItem( website );
	this.clear();
	if( this.websiteList.parentWebsite != null ) {
		this.setDisplay( false );
	}
};

NewWebsite.prototype.clear = function() {
	this.nameInput.value = '';
	this.handleInput.value = '';
	this.domainInput.value = '';

	while( this.domainsSelect.length ) {
		this.domainsSelect.options[0] = null;
	}
};

NewWebsite.prototype.getParams = function() {
	return getWebsiteCommonParams( this );
};


ArdeClass.extend( NewWebsite, Website );

function websiteTemplate( component, node ) {
	if( component instanceof Website ) {
		if( component.parentId ) {
			var txt = ardeT( component.name );
		} else {
			var txt = ardeT( component.name );
		}
	} else {
		if( component.websiteList.parentWebsite ) {
			var txt = ardeT( 'New Sub-Website' );
		} else {
			var txt = ardeT( 'New Website' );
		}
	}
	node.append( ardeE( 'p' ).style( 'fontWeight', 'bold' ).append( txt ) );

	var tb = ardeE( 'tbody' ).appendTo( ardeE( 'table' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( node ) );	
	var topTr = ardeE( 'tr' ).appendTo( tb );
	var td = ardeE( 'td' ).style( 'verticalAlign', 'top' ).appendTo( topTr );
	
	tb = ardeE( 'tbody' ).appendTo( ardeE( 'table' ).cls( 'form' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( td ) );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	ardeE( 'td' ).cls( 'head' ).append( ardeT( 'Name: ' ) ).appendTo( tr );
	component.nameInput = ardeE( 'input' ).n;
	if( component instanceof Website ) {
		component.nameInput.value = component.name;
	}
	ardeE( 'td' ).cls( 'tail' ).append( component.nameInput ).appendTo( tr );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	ardeE( 'td' ).cls( 'head' ).append( ardeT( 'ID: ' ) ).appendTo( tr );
	component.handleInput = ardeE( 'input' ).n;
	if( component instanceof Website ) {
		component.handleInput.value = component.handle;
	}
	ardeE( 'td' ).cls( 'tail' ).append( component.handleInput ).appendTo( tr );

	
	if( component instanceof Website && !component.parentId ) {
		component.subWebsiteButton = document.createElement( 'input' );
		component.subWebsiteButton.className = 'passive';
		component.subWebsiteButton.type = 'button';
		component.subWebsiteButton.value = 'New Sub Website';
		ardeE( 'tr' ).append( ardeE( 'td' ).attr( 'colspan', '2' ).append( ardeE( 'p' ).append( ardeE( 'br' ) ).append( component.subWebsiteButton ) ) ).appendTo( tb );
	}
	td = ardeE( 'td' ).appendTo( topTr );
	tb = ardeE( 'tbody' ).appendTo( ardeE( 'table' ).cls( 'form' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( td ) );
	tr = ardeE( 'tr' ).appendTo( tb );
	
	
	ardeE( 'td' ).cls( 'head' ).style( 'verticalAlign', 'top' ).append( ardeT( 'domains: ' ) ).appendTo( tr );
	
	
	component.domainInput = ardeE( 'input' ).n;
	component.domainInput.size = 30;
	
	component.domainAddButton = document.createElement( 'input' );
	component.domainAddButton.type = 'button';
	component.domainAddButton.value = 'ADD';
	component.domainAddButton.className = 'passive';
	
	component.domainsSelect = document.createElement( 'select' );
	component.domainsSelect.size = 4;
	component.domainsSelect.style.width = 270+'px';
	
	if( component instanceof Website ) {
		for( var i in component.domains ) {		
			var option = ardeE( 'option' ).append( ardeT( component.domains[i] ) ).n;
			option.value = component.domains[i];
			component.domainsSelect.appendChild( option );
		}
	}

	component.domainDeleteButton = document.createElement( 'input' );
	component.domainDeleteButton.type = 'button';
	component.domainDeleteButton.value = 'Delete Selected';
	component.domainDeleteButton.className = 'passive';
	
	component.domainAddButton.onclick = function() {
		if( component.domainInput.value != '' ) {
			var option = ardeE( 'option' ).append( ardeT( component.domainInput.value ) ).n;
			option.value = component.domainInput.value;
			component.domainsSelect.appendChild( option );
			component.domainInput.value = '';
		}
	};
	
	component.domainDeleteButton.onclick = function() {
		if( component.domainsSelect.selectedIndex >= 0 ) {
			component.domainsSelect.options[ component.domainsSelect.selectedIndex ] = null;
		}
	};
	
	var td = ardeE( 'td' ).cls( 'tail' );
	td.append( component.domainInput ).append( component.domainAddButton );
	td.append( ardeE( 'br' ) ).append( component.domainsSelect );
	td.append( ardeE( 'br' ) ).append( component.domainDeleteButton );
	td.appendTo( tr );
	
	component.buttonsP = ardeE( 'p' ).appendTo( ardeE( 'td' ).style( 'verticalAlign', 'top' ).style( 'width', '200px' ).style( 'textAlign', 'right' ).appendTo( topTr ) );
	
	if( component instanceof Website ) {
		component.applyButton = new ArdeRequestButton( 'Apply Changes' );
		component.applyButton.button.style[ 'width' ] = '150px';

		component.buttonsP.append( component.applyButton ).append( ardeE( 'br' ) ).append( ardeE( 'br' ) ).append( ardeE( 'br' ) );
		
		if( component.id != 1 ) {
			component.deleteButton = new ArdeRequestButton( 'Delete Website', "Are you sure? this will also delete all tables and data related to website '"+component.name+"' and all of it's sub-websites from database." );
			component.deleteButton.button.className = 'critical';
			component.deleteButton.button.style[ 'width' ] = '150px';
			component.buttonsP.append( component.deleteButton );
		}
		
		
	} else {
		component.addWebsiteButton = new ArdeRequestButton( 'Add Website' );
		component.addWebsiteButton.button.style[ 'width' ] = '150px';
		
		component.buttonsP.append( component.addWebsiteButton );
	}
	component.cookieDomainInput = new ArdeInput( component.cookieDomain ).attr( 'size', '30' );
	component.cookieFolderInput = new ArdeInput( component.cookieFolder ).attr( 'size', '20' );
	node.append( ardeE( 'p' ).append( ardeT( 'Cookie Domain: ' ) ).append( component.cookieDomainInput ).append( ardeT( ' Cookie Folder: ' ) ).append( component.cookieFolderInput ) );
}


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
    
	function UsersHolder( perPage, totalCount ) {
		this.perPage = perPage;
		this.totalCount = totalCount;
		this.offset = 0;
		
		this.ArdeComponent( 'div' );

		this.newUser = new NewUser;
		this.newUser.ardeList = this;
		
		
		var tr = ardeE( 'tr' ).appendTo( ardeE( 'tbody' ).appendTo( new ArdeTable().style( 'width', '100%' ).appendTo( this ) ) );
		
		tr.append( ardeE( 'td' ).style( 'width', '50%' ).append( this.newUser ) );
		
		
		
		var findBox = new ArdeComponent( 'div' );
		tr.append( ardeE( 'td' ).append( findBox ) );
		findBox.cls( 'group' );
		
		
		this.findUsernameInput = new ArdeInput( '' );
		this.findTimeout = null;
		var self = this;
		this.findUsernameInput.element.onkeyup = this.findUsernameInput.element.oninput = function ( s ) {
			self.findUsernameInputChange();
		};
		this.findRequester = new ArdeRequestIcon();
		this.findRequester.setShowOk( false );
		this.findRequester.afterResultReceived = function ( result ) { 
			self.findResultReceived( result );
		};
		findBox.append( ardeE( 'h2' ).append( ardeT( 'Find' ) ) );
		findBox.append( ardeE( 'p' ).append( ardeT( 'username: ' ) ).append( this.findUsernameInput ).append( this.findRequester ) );
		
		this.ArdeComponentList( new ArdeComponent( 'div' ).appendTo( this ) );
		
		this.nextButton = new ArdeRequestButton( 'next' ).setDisabled( true ).setShowOk( false ).style( 'marginLeft', '20px' );
		this.prevButton = new ArdeRequestButton( 'prev' ).setDisabled( true ).setShowOk( false );
		
		
		
		ardeE( 'div' ).cls( 'margin_std' ).append( new ArdeComponent( 'div' ).setFloat( 'right' ).append( this.prevButton ) ).append( this.nextButton ).appendTo( this );
		
		this.nextButton.setStandardCallbacks( this, 'next' );
		this.prevButton.setStandardCallbacks( this, 'prev' );
	}

	UsersHolder.prototype.findUsernameInputChange = function () {
		if( this.findTimeout !== null ) clearTimeout( this.findTimeout );
		var self = this;
		this.findTimeout = setTimeout( function () { self.requestFind(); }, 200 );
	};
	
	UsersHolder.prototype.requestFind = function () {
		this.findTimeout = null;
		var value = this.findUsernameInput.element.value; 
		var bw = value == '' ? '' : '&bw='+ardeEscape( value );
		this.findRequester.request( ardeUserFullUrl( 'rec/rec_user.php' ), 'a=get_users&o='+0+'&c='+this.perPage+bw, ardeXmlObjectListClass( User, 'user', false, false, true ) );
	};
	
	UsersHolder.prototype.findResultReceived = function ( us ) {
		this.offset = 0;
		this.refreshUsers( us );
	};
	
	UsersHolder.prototype.updatePrevNextButtons = function () {
		this.nextButton.button.setDisabled( this.offset == 0 );
		this.prevButton.button.setDisabled( this.offset + this.length() >= this.totalCount );
	};
	
	UsersHolder.prototype.prevClicked = function () {
		var value = this.findUsernameInput.element.value; 
		var bw = value == '' ? '' : '&bw='+ardeEscape( value );
		this.prevButton.request( ardeUserFullUrl( 'rec/rec_user.php' ), 'a=get_users&o='+(this.offset+this.perPage)+'&c='+this.perPage+bw, ardeXmlObjectListClass( User, 'user', false, false, true ) );
	};
	
	UsersHolder.prototype.prevConfirmed = function ( us ) {
		
		this.offset = this.offset + this.perPage;
		this.refreshUsers( us );
	};
	
	UsersHolder.prototype.nextClicked = function () {
		var newOffset = this.offset - this.perPage;
		if( newOffset < 0 ) newOffset = 0;
		var value = this.findUsernameInput.element.value; 
		var bw = value == '' ? '' : '&bw='+ardeEscape( value );
		this.nextButton.request( ardeUserFullUrl( 'rec/rec_user.php' ), 'a=get_users&o='+newOffset+'&c='+this.perPage+bw, ardeXmlObjectListClass( User, 'user', false, false, true ) );
	};
	
	UsersHolder.prototype.nextConfirmed = function ( us ) {
		this.offset -= this.perPage;
		if( this.offset < 0 ) this.offset = 0;
		this.refreshUsers( us );
	};
	
	UsersHolder.prototype.refreshUsers = function ( us ) {
		this.removeAllItems();
		for( var i in us.a ) {
			this.addItem( us.a[i] );
		}
		this.totalCount = us.total;
		this.updatePrevNextButtons();
	};
	
	ArdeClass.extend( UsersHolder, ArdeComponent );
	ArdeClass.extend( UsersHolder, ArdeComponentList );
	
	function UserBase( id, username ) {
		this.id = id;
		this.username = username;
		
		this.ArdeComponent( 'div' );
		this.cls( 'block' );
		
		if( this.id == User.RootUserId ) {
			this.append( new ArdeComponent( 'div' ).setFloat( 'right' ).cls( 'sub_block' ).append( ardeE( 'p' ).append( ardeT( 'Root User' ) ) ) );
		}
		
		this.usernameInput = new ArdeInput( this.username );
		this.passInput = new ArdeInput( '' ).attr( 'type', 'password' );
		this.passRetypeInput = new ArdeInput( '' ).attr( 'type', 'password' );
		
		if( this.id == User.RootUserId ) {
			this.usernameInput.setDisabled( true );
			this.passInput.setDisabled( true );
			this.passRetypeInput.setDisabled( true );
		}
		
		var tb = ardeE( 'tbody' ).appendTo( new ArdeTable().setCellPadding( '3' ).cls( 'margin_std' ).appendTo( this ) );

		ardeE( 'tr' ).append( ardeE( 'td' ).style( 'width', '100px' ).style( 'textAlign', 'right' ).append( ardeT( 'username: ' ) ) ).append( ardeE( 'td' ).append( this.usernameInput ) ).appendTo( tb );
		
		this.passTr = new ArdeComponent( 'tr' );
		this.passTr.append( ardeE( 'td' ).style( 'textAlign', 'right' ).append( ardeT( 'password: ' ) ) ).append( ardeE( 'td' ).append( this.passInput ) ).appendTo( tb );
		this.passRetypeTr = new ArdeComponent( 'tr' );
		this.passRetypeTr.append( ardeE( 'td' ).style( 'textAlign', 'right' ).append( ardeT( 'retype password: ' ) ) ).append( ardeE( 'td' ).append( this.passRetypeInput ) ).appendTo( tb );
		
		var p = ardeE( 'p' ).appendTo( this );
		this.addButtons( p );
	}
	
	UserBase.prototype.clear = function () {
		this.usernameInput.element.value = '';
		this.passInput.element.value = '';
		this.passRetypeInput.element.value = '';
	};
	
	UserBase.prototype.getParams = function () {
		return 'un='+ardeEscape( this.usernameInput.element.value )+'&p='+ardeEscape( this.passInput.element.value )+'&pr='+ardeEscape( this.passRetypeInput.element.value );
	};
	
	ArdeClass.extend( UserBase, ArdeComponent );
	
	function User( id, username ) {
		this.UserBase( id, username );
		this.passTr.setDisplay( false );
		this.passRetypeTr.setDisplay( false );
	}
	
	User.fromXml = function( element ) {
		id = ArdeXml.intAttribute( element, 'id' );
		username = ArdeXml.strElement( element, 'name' );
		return new User( id, username );
	}
	
	User.prototype.addButtons = function( p ) {
		if( this.id == User.RootUserId ) return;
		
		
		this.applyButton = new ArdeRequestButton( 'Apply Changes' );
		p.append( this.applyButton );
		this.applyButton.setStandardCallbacks( this, 'apply' );
		
		this.changePasswordButton = new ArdeButton( 'Change Password' );
		this.changePasswordButton.cls( 'passive' );
		p.append( this.changePasswordButton );
		
		var self = this;
		this.changePasswordButton.element.onclick = function () {
			self.passTr.switchDisplay();
			self.passRetypeTr.switchDisplay();
			if( self.passTr.isDisplaying() ) {
				self.passInput.element.focus();
			} else {
				self.passInput.element.value = '';
				self.passRetypeInput.element.value = '';
			}
		};
		
		this.deleteButton = new ArdeRequestButton( 'Delete', 'Arde you sure? This will permanently delete the user.' );
		this.deleteButton.setCritical( true ).style( 'marginLeft', '20px' );
		p.append( this.deleteButton );
		this.deleteButton.setStandardCallbacks( this, 'delete' );
	};
	
	User.prototype.deleteClicked = function () {
		var value = this.ardeList.findUsernameInput.element.value; 
		var bw = value == '' ? '' : '&bw='+ardeEscape( value );
		this.deleteButton.request( ardeUserFullUrl( 'rec/rec_user.php' ), 'a=delete&i='+this.id+'&o='+this.ardeList.offset+bw, ardeXmlObjectListClass( User, 'user', false, false, true ) );
	};
	
	User.prototype.deleteConfirmed = function ( us ) {
		this.ardeList.refreshUsers( us );
	};
	
	User.prototype.applyClicked = function () {
		this.applyButton.request( ardeUserFullUrl( 'rec/rec_user.php' ), 'a=update&i='+this.id+'&'+this.getParams() );
	};
	
	User.prototype.applyConfirmed = function () {
		
	};
	
	ArdeClass.extend( User, UserBase );
	
	function NewUser() {
		this.UserBase( null, '' );
		this.cls( 'special_block' );	 
	}
	
	NewUser.prototype.addButtons = function( p ) {
		this.addButton = new ArdeRequestButton( 'Add' );
		p.append( this.addButton );
		this.addButton.setStandardCallbacks( this, 'add' );
	};
	
	NewUser.prototype.addClicked = function () {
		this.addButton.request( ardeUserFullUrl( 'rec/rec_user.php' ), 'a=add&'+this.getParams(), ardeXmlObjectListClass( User, 'user', false, false, true ) );
	};
	
	NewUser.prototype.addConfirmed = function ( us ) {
		this.clear();
		this.usernameInput.element.focus();
		this.ardeList.findUsernameInput.element.value = '';
		this.ardeList.refreshUsers( us );
	};
	
	ArdeClass.extend( NewUser, UserBase );
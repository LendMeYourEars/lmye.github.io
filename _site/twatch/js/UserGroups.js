
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

	function UserGroupsHolder( withPermission ) {
		this.selects = [];
		this.ArdeComponent( 'div' );
		
		this.newUserGroup = new NewUserGroup( this );

		
		var tr = ardeE( 'tr' ).appendTo( ardeE( 'tbody' ).appendTo( new ArdeTable().style( 'width', '100%' ).appendTo( this ) ) );

		tr.append( ardeE( 'td' ).style( 'width', '50%' ).append( this.newUserGroup ) );
		
		
		
		var setUserGroupBox = new ArdeComponent( 'div' );
		tr.append( ardeE( 'td' ).append( setUserGroupBox ) );
		setUserGroupBox.cls( 'group' );
		
		setUserGroupBox.append( ardeE( 'p' ).append( ardeE( 'a' ).attr( 'href', userUrl+'admin/' ).append( ardeT( 'Add/Remove Users' ) ) ) );
		if( selectedUser.type == User.USER ) {
			this.select = this.getSelect( null, selectedUser.groupId );
			this.applyButton = new ArdeRequestButton( 'Apply Change' );
			this.applyButton.setStandardCallbacks( this, 'apply' );
			var self = this;
			var p = ardeE( 'p' ).appendTo( setUserGroupBox );
			p.append( ardeT( 'user: ' ) ).append( selectedUser.getName().cls( 'fixed' ) );
			
			p.append( ardeT( ardeNbsp(4)+' group: ' ) ).append( this.select );
			p.append( ardeT( ardeNbsp(4)+' ' ) ).append( this.applyButton );
			if( selectedUser.id == User.RootUserId || selectedUser.id == User.PublicUserId ) {
				this.select.setDisabled( true );
				this.applyButton.button.setDisabled( true );
				p.append( ardeT( ' can\'t change '+( selectedUser.id == User.RootUserId ? 'root' : 'public' )+' user\'s group.' ) );
			}
		} else {
			setUserGroupBox.append( ardeE( 'p' ).append( ardeT( 'Select a user from the list above to change its group.' ) ) );
		}

		
		this.ArdeComponentList( new ArdeComponent( 'div' ).appendTo( this ) );
	}
	
	UserGroupsHolder.prototype.applyClicked = function () {
		this.applyButton.request( twatchFullUrl( 'rec/rec_users.php' ), 'a=set_user_group&i='+selectedUser.id+'&g='+this.select.selectedOption().value );
	};
	
	
	
	UserGroupsHolder.prototype.applyConfirmed = function () {
		selectedUser.groupId = this.select.selectedOption().value;
	};
	
	UserGroupsHolder.prototype.getSelect = function ( excludeId, selectedId ) {
		var select = new ArdeSelect();
		select.excludeId = excludeId;
		select.selectedId = selectedId;
		this.fillSelect( select );
		this.selects.push( select );
		return select;
	};
	
	UserGroupsHolder.prototype.fillSelect = function ( select ) {
		select.clear();
		for( var i in this.items ) {
			if( this.items[i].id == select.excludeId ) continue;
			var o = new ArdeOption( this.items[i].name, this.items[i].id );
			if( this.items[i].id == select.selectedId ) o.setSelected( true );
			select.append( o );
		}
	};
	
	UserGroupsHolder.prototype.onchange = function () {
		for( var i in this.selects ) {
			this.fillSelect( this.selects[i] );
		}
	};
	
	ArdeClass.extend( UserGroupsHolder, ArdeComponent );
	ArdeClass.extend( UserGroupsHolder, ArdeComponentList );
	
	function UserGroupBase( id, name ) {
		this.ArdeComponent( 'div' );
		this.titleT = ardeT( name );
		this.append( ardeE( 'h2' ).append( this.titleT ) );
		this.nameInput = new ArdeInput( name );
		this.append( ardeE( 'p' ).append( ardeT( 'name: ' ) ).append( this.nameInput ) );
		this.buttonsP = ardeE( 'p' ).appendTo( this );
	}
	
	ArdeClass.extend( UserGroupBase, ArdeComponent );
	
	function NewUserGroup( ardeList ) {
		this.ardeList = ardeList;
		this.UserGroupBase( null, '' );
		this.titleT.setText( 'New User Group' );
		this.cls( 'special_block' );
		this.copySelect = this.ardeList.getSelect();
		this.append( ardeE( 'p' ).append( ardeT( 'copy from: ' ) ).append( this.copySelect ) );
		this.addButton = new ArdeRequestButton( 'Add' );
		this.addButton.setStandardCallbacks( this, 'add' );
		this.append( ardeE( 'p' ).append( this.addButton ) );
	}
	
	NewUserGroup.prototype.addClicked = function () {
		this.addButton.request( twatchFullUrl( 'rec/rec_users.php' ), 'a=add_group&n='+ardeEscape( this.nameInput.element.value )+'&c='+this.copySelect.selectedOption().value, UserGroup );
	};
	
	NewUserGroup.prototype.addConfirmed = function ( group ) {
		this.ardeList.insertFirstItem( group );
		this.nameInput.element.value = '';
	};
	
	ArdeClass.extend( NewUserGroup, UserGroupBase );
	
	function UserGroup( id, name ) {
		this.id = id;
		this.name = name;
		this.ardeList = userGroups;
		
		this.UserGroupBase( id, name );
		this.cls( 'block' );
		
		this.applyButton = new ArdeRequestButton( 'Apply Changes' );
		this.applyButton.setStandardCallbacks( this, 'apply' );
		
		this.append( ardeE( 'p' ).append( this.applyButton ) );
		
		if( this.id != User.PublicGroupId && this.id != User.AdminGroupId ) {
			
			this.deleteButton = new ArdeRequestButton( 'Delete' ).setCritical( true );
			this.deleteButton.setStandardCallbacks( this, 'delete' );
			
			this.assignSelect = this.ardeList.getSelect( this.id, User.PublicGroupId );
			
			this.append( ardeE( 'div' ).cls( 'group' ).append( ardeE( 'p' ).append( this.deleteButton ).append( ardeT( ' assign users to ' ) ).append( this.assignSelect ) ) );
		}
	}
	
	UserGroup.fromXml = function ( element ) {
		var id = ArdeXml.intAttribute( element, 'id' );
		var name = ArdeXml.strElement( element, 'name' );
		
		return new UserGroup( id, name );
	};
	
	UserGroup.prototype.deleteClicked = function () {
		this.deleteButton.reassignId = this.assignSelect.selectedOption().value;
		this.deleteButton.request( twatchFullUrl( 'rec/rec_users.php' ), 'a=delete_group&i='+this.id+'&ri='+this.assignSelect.selectedOption().value );
	};
	
	UserGroup.prototype.deleteConfirmed = function () {
		if( selectedUser.groupId == this.id ) {
			this.ardeList.select.selectedId = selectedUser.groupId = this.deleteButton.reassignId;
			
		}
		this.ardeList.removeItem( this );
	};
	
	UserGroup.prototype.applyClicked = function () {
		this.applyButton.request( twatchFullUrl( 'rec/rec_users.php' ), 'a=change&i='+this.id+'&n='+ardeEscape( this.nameInput.element.value ) );
	};
	
	UserGroup.prototype.applyConfirmed = function () {
		this.name = this.nameInput.element.value;
		this.titleT.setText( this.name );
		this.ardeList.onchange();
	};
	
	ArdeClass.extend( UserGroup, UserGroupBase );


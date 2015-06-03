
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

	function twatchFullUrl( path ) {

		var url = new ArdeUrlWriter( path );
		url.setParam( 'profile', twatchProfile, 'default' );
		url.setParam( 'lang', ardeLocale.id, ardeLocale.defaultId );
		currentUrl = ArdeUrlWriter.getCurrent();
		if( typeof currentUrl.params[ 'group' ] != 'undefined' ) {
			url.setParam( 'group', currentUrl.params[ 'group' ] );
		} else if( typeof currentUrl.params[ 'user' ] != 'undefined' ) {
			url.setParam( 'user', currentUrl.params[ 'user' ] );
		}

		url.setParam( 'website', websiteId, defaultWebsiteId );

		return url.getUrl();
	}
	
	function CounterAvailability( timestamps ) {
		this.timestamps = timestamps;
		this.ArdeComponent( 'div' );
		if( ardeMembersCount( timestamps ) == 0 ) {
			this.append( ardeE( 'p' ).append( ardeE( 'span' ).cls( 'stopped' ).append( ardeT( 'Never Started' ) ) ) );
			return this;
		}
		var t = new ArdeTable().cls( 'std' ).style( 'margin', '0px' ).appendTo( this );
		var tr = ardeE( 'tr' ).appendTo( ardeE( 'thead' ).appendTo( t ) );
		for( var ptName in timestamps ) {
			tr.append( new ArdeTd().setColSpan( '2' ).append( ardeT( ptName ) ) );
		}
		
		var tb = ardeE( 'tbody' ).appendTo( t );
		CounterAvailability.appendTssTrs( timestamps, tb );
		
	};
	
	CounterAvailability.appendTssTrs = function ( timestamps, tb ) {
		var rowNo = 0;
		while( true ) {
			var anyDataLeft = false;
			tr = ardeE( 'tr' );
			for( var pt in timestamps ) {
				if( typeof timestamps[pt][rowNo] != 'undefined' ) {
					anyDataLeft = true;
					tr.append( ardeE( 'td' ).append( ardeT( timestamps[pt][rowNo] ) ) );
					var span = ardeE( 'span' ).cls( rowNo%2?'stopped':'good' );
					span.append( ardeT( rowNo%2?'Stop':'Start' ) );
					tr.append( ardeE( 'td' ).append( span ) );
				} else {
					if( rowNo == 0 ) {
						tr.append( new ArdeTd().setColSpan( '2' ).style( 'padding', '5px 10px' ).append( ardeE( 'span' ).cls( 'stopped' ).append( ardeT( 'Never Started' ) ) ) );
					} else {
						tr.append( ardeE( 'td' ).append( ardeT( ' ' ) ) );
						tr.append( ardeE( 'td' ).append( ardeT( ' ' ) ) );
					} 
				}
			}
			if( !anyDataLeft ) {
				if( rowNo == 0 ) tr.appendTo( tb );
				break;
			} else {
				tr.appendTo( tb );
			}
			++rowNo;
		}
	};
	
	CounterAvailability.fromXml = function ( element ) {
		var periodEs = new ArdeXmlElemIter( element, 'period_type' );
		var timestamps = {};
		while( periodEs.current ) {
			var periodTypeName = ArdeXml.attribute( periodEs.current, 'name' ); 
			timestamps[ periodTypeName ] = CounterAvailability.tssFromXml( periodEs.current );
			periodEs.next();
		}
		return new CounterAvailability( timestamps );
	};
	
	CounterAvailability.tssFromXml = function ( element ) {
		var o = [];
		var timeEs = new ArdeXmlElemIter( element, 'time' );
		while( timeEs.current ) {
			o.push( ArdeXml.strContent( timeEs.current ) );
			timeEs.next();
		}
		return o;
	};
	
	ArdeClass.extend( CounterAvailability, ArdeComponent );
	
	function User( type, id, name, groupId ) {
		this.type = type;
		this.id = id;
		this.name = name;
		this.groupId = groupId;
		
		
		this.ArdeItem( 'div' );
		
		this.getName().appendTo( this );
		
	};
	
	User.prototype.isRoot = function () {
		return this.type == User.USER && this.id == User.RootUserId;
	};
	User.prototype.getName = function () {
		var s = ardeE( 'span' ).append( ardeT( this.name ) );
		if( this.type == User.GROUP ) {
			
			s.style( 'fontWeight', 'bold' );
			if( this.id ) {
				s.style( 'color', '#060' );
				s.append( ardeE( 'span' ).style( 'fontWeight', 'normal' ).style( 'color', '#888' ).append( ardeT( ' (group)' ) ) );
			} else {
				s.style( 'color', '#030' );
			}
		}
		return s;
	};
	
	User.USER = 1;
	User.GROUP = 2;
	
	User.fromXml = function ( element ) {
		
		var type = ArdeXml.strAttribute( element, 'type' );
		if( type == 'group' ) type = User.GROUP;
		else if( type == 'user' ) type = User.USER;
		else throw new ArdeException( 'invalid user type '+type );
		
		var id = ArdeXml.intAttribute( element, 'id' );
		var name = ArdeXml.strElement( element, 'name' );
		
		return new User( type, id, name );
	};
	
	User.prototype.setMouseOverHighlight = function( mouseOverHighlight ) {
		if( mouseOverHighlight ) {
			this.element.style.background = '#f88';
			this.element.style.color = '#000';
		} else {
			this.element.style.background = '#fff';
			this.element.style.color = '#000';
		}
		return this;
	}
	
	User.prototype.setSelectedHighlight = function( selectedHighlight ) {
		if( selectedHighlight ) {
			this.element.style.background = '#a00';
			this.element.style.color = '#fff';
		} else {
			this.element.style.background = '#fff';
			this.element.style.color = '#000';
		}
		return this;
	};
	
	
	ArdeClass.extend( User, ArdeItem );
	
	function UserSelect( user ) {
		this.constructing = true;
		this.ArdeActiveSelect( user, 10, false, true );
		this.setStandardCallbacks( this, 'userValues' );
		this.constructing = false;
	}
	
	UserSelect.prototype.onchange = function () {
		if( this.constructing ) return;
		var url = ArdeUrlWriter.getCurrent();
		var user = this.getValue();
		if( user.type == User.USER ) {
			url.setParam( 'user', user.id );
			url.removeParam( 'group' );
		} else {
			url.setParam( 'group', user.id, 0 );
			url.removeParam( 'user' );
		}
		window.location = url.getUrl();
	};
	
	UserSelect.prototype.userValuesRequested = function( offset, count, beginWith ) {
		if( beginWith == '' ) b = '';
		else b = '&bw='+ardeEscape( beginWith );
		this.requester.request( twatchUrl + 'admin/rec/rec_users.php',
			'a=get_users&wm=t&o='+offset+'&c='+count+b,
			ardeXmlObjectListClass( User, 'user', true, false )
		);
	};
	
	UserSelect.prototype.userValuesReceived = function( result ) {
		this.resultsReceived( result.a, result.more );
	};
	
	
	ArdeClass.extend( UserSelect, ArdeActiveSelect );
	
	function ValueWithDefault( value, isDefault, defaultValue ) {
		this.value = value;
		if( typeof isDefault == 'undefined' ) {
			this.isDefault = false;
		} else {
			this.isDefault = isDefault;
		}
		if( typeof defaultValue == 'undefined' ) {
			this.defaultValue = false;
		} else {
			this.defaultValue = defaultValue;
		}
	}
	
	function BinaryValue( value, isDefault, defaultValue ) {	
		this.ValueWithDefault( value, isDefault, defaultValue );
	}
	
	ArdeClass.extend( BinaryValue, ValueWithDefault );
	
	function BinarySelector( value, trueString, falseString, defaultTrueString, defaultFalseString ) {
	
		if( typeof defaultFalseString == 'undefined' ) {
			if( typeof defaultTrueString == 'undefined' ) {
				this.defaultTrueString = null;
				this.defaultFalseString = null;
			} else {
				this.defaultTrueString =  defaultTrueString + ' ('+trueString+')';
				this.defaultFalseString =  defaultTrueString + ' ('+falseString+')';
			}
		} else {
			this.defaultTrueString = defaultTrueString;
			this.defaultFalseString = defaultFalseString;
		}
		
		this.value = value;
		
		this.ArdeComponent( 'span' );
		
		this.stateSelect = new ArdeSelect();
		
		if( this.defaultTrueString !== null ) {
			this.defaultOption = new ArdeOption( '-', '-' );
			this.setDefaultOption( value.defaultValue );
			this.defaultOption.element.isDefault = true;
			this.stateSelect.append( this.defaultOption );
		}
		
		
		this.trueOption = new ArdeOption( trueString, 'true' ).setSelected( !value.isDefault && value.value );
		this.trueOption.element.isDefault = false;
		this.falseOption = new ArdeOption( falseString, 'false' ).setSelected( !value.isDefault && !value.value );
		this.falseOption.element.isDefault = false;
	
		UserPermissionSelector.setStyle( this.trueOption, true, false );
		UserPermissionSelector.setStyle( this.falseOption, false, false );
		
		this.stateSelect.append( this.trueOption ).append( this.falseOption );
		
		UserPermissionSelector.setStyle( this.stateSelect, value.value, value.isDefault );
		
		var self = this;
		this.stateSelect.element.onchange = function () {
			self.setSelectStyle();
		};
		
		this.append( this.stateSelect );
	}
	
	BinarySelector.prototype.defaultSelected = function () {
		return this.stateSelect.selectedOption().idDefault;
	};
	
	BinarySelector.prototype.selectedValue = function () {
		return this.stateSelect.selectedOption().value == 'true';
	};
	
	BinarySelector.prototype.setDefaultValue = function ( value ) {
		this.value.defaultValue = value;
		this.setDefaultOption( value );
	};
	
	BinarySelector.prototype.setDefaultOption = function ( value ) {
		if( value ) {
			this.defaultOption.element.value = true;
			this.defaultOption.element.firstChild.nodeValue = this.defaultTrueString;
			UserPermissionSelector.setStyle( this.defaultOption, true, true );
		} else {
			this.defaultOption.element.value = false;
			this.defaultOption.element.firstChild.nodeValue = this.defaultFalseString;
			UserPermissionSelector.setStyle( this.defaultOption, false, true );
		}
		this.setSelectStyle();
	};
	
	BinarySelector.prototype.setSelectStyle = function () {
		var selectedOption = this.stateSelect.selectedOption();
		if( selectedOption === null ) return;
		UserPermissionSelector.setStyle( this.stateSelect, selectedOption.value == 'true', selectedOption.isDefault );
	};
	
	BinarySelector.prototype.setSelected = function( value ) {
		this.trueOption.setSelected( value );
		this.falseOption.setSelected( !value );
		UserPermissionSelector.setStyle( this.stateSelect, value, false );
	};
	
	BinarySelector.setStyle = function ( component, value, isDefault ) {
		if( isDefault ) {
			component.style( 'fontWeight', 'bold' );
		} else {
			component.style( 'fontWeight', 'bold' );
		}
		if( value ) {
			component.style( 'background', '#484' );
			if( isDefault ) {
				component.style( 'color', '#bdb' );
			} else {
				component.style( 'color', '#fff' );
			}
		} else {
			component.style( 'background', '#444' );
			if( isDefault ) {
				component.style( 'color', '#ccc' );
			} else {
				component.style( 'color', '#fff' );
			}
		}

	};
	
	BinarySelector.prototype.getParams = function ( prefix ) {
		if( typeof prefix == 'undefined' ) prefix = '';
		if( this.stateSelect.selectedOption().isDefault ) {
			var s = prefix+'d=t';
		} else {
			var s = prefix+'v='+( this.stateSelect.selectedOption().value == 'true' ? 't' : 'f' );
		}
		return s;
	};
	
	ArdeClass.extend( BinarySelector, ArdeComponent );
	
	function Permission( value, isDefault, defaultValue ) {
		this.BinaryValue( value, isDefault, defaultValue );
	}
	
	Permission.fromXml = function ( element ) {
		var value = ArdeXml.boolAttribute( element, 'value' );
		var isDefault = ArdeXml.boolAttribute( element, 'is_default' );
		var defaultValue = ArdeXml.boolAttribute( element, 'default' );
		var o = new Permission( value, isDefault, defaultValue );
		return o;
	};
	
	ArdeClass.extend( Permission, BinaryValue );
	
	function UserPermissionSelector( value, trueString, falseString ) {
		if( typeof trueString == 'undefined' ) {
			trueString = 'YES';
			falseString = 'NO';
		}
		
		if( selectedUser.type == User.USER && selectedUser.id == User.RootUserId ) value.isDefault = false;
		
		this.BinarySelector( value, trueString, falseString, (  'Default' )+' ('+trueString+')', (  'Default' )+' ('+falseString+')' );
	
		if( selectedUser.type == User.USER && selectedUser.id == User.RootUserId ) {
			this.stateSelect.setDisabled( true );
		}
	}
	
	
	
	
	ArdeClass.extend( UserPermissionSelector, BinarySelector );
	
	function twatchPerUserWebsite() {
		var o = new ArdeComponent( 'p' ).style( 'fontSize', '.8em' ).style( 'marginTop', '-3px' ).style( 'color', '#666' );
		
		o.append( ardeT( 'Settings below are'+( configMode ? '' : ' per user and ' )+' per website, currently selected: ' ) );
		if( !configMode ) {
			o.append( selectedUser.getName() ).append( ardeT( ' / ' ) );
		}
		o.append( ardeT( websiteName ) );
		return o;
	}

	function twatchGlobalSettings() {
		return new ArdeComponent( 'p' ).style( 'fontSize', '.8em' ).style( 'marginTop', '-3px' ).style( 'color', '#666' ).append( ardeT( 'Settings below are global' ) );
	}
	
	function SelectWithDefault( ids, names, value, defaultText ) {
		this.ArdeSelect();
		for( var i in ids ) {
			if( ids[i] == value.defaultValue ) {
				this.defOption = new ArdeOption( names[i]+' ('+defaultText+')', ids[i] );
				this.defOption.element.isDefault = true;
				if( value.isDefault ) this.defOption.setSelected( true );
				this.append( this.defOption );
				break;
			}
		}

		for( var i in ids ) {
			var o = new ArdeOption( names[i], ids[i] );
			if( ids[i] == value.value && ! value.isDefault ) o.setSelected( true );
			o.element.isDefault = false;
			this.append( o );
		}
	}
	
	SelectWithDefault.prototype.getParams = function ( prefix ) {
		if( typeof prefix == 'undefined' ) prefix = '';
		if( this.selectedOption().isDefault ) {
			var s = prefix+'d=t';
		} else {
			var s = prefix+'v='+ardeEscape( this.selectedOption().value );
		}
		
		return s;
	};
	
	ArdeClass.extend( SelectWithDefault, ArdeSelect );
	
	
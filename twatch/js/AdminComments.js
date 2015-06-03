
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
    
	function Comment( id, date, txt, priv ) {
		this.id = id;
		this.date = date;
		this.txt = txt;
		this.priv = priv;
		this.ArdeComponent( 'div' );
		this.cls( 'block' );
		
		this.append( ardeE( 'p' ).append( ardeE( 'span' ).cls( 'info' ).append( ardeT( this.date ) ) ).append( ardeT( ardeNbsp(4) ) ).append( ardeE( 'span' ).cls( 'fixed' ).append( ardeT( this.priv?'private':'public') ) ) );
		
		this.append( ardeE( 'div' ).cls( 'sub_block' ).append( ardeE( 'p' ).append( ardeT( this.txt ) ) ) );
		
		this.removeButton = new ArdeRequestButton( 'Remove' );
		this.append( ardeE( 'p' ).append( this.removeButton ) );
		
		this.removeButton.setStandardCallbacks( this, 'remove' );
	}
	
	Comment.fromXml = function ( element ) {
		return new Comment(
			 ArdeXml.intAttribute( element, 'id' )
			,ArdeXml.strAttribute( element, 'date' )
			,ArdeXml.strContent( element )
			,ArdeXml.boolAttribute( element, 'private' )
		);
	};
	
	Comment.prototype.removeClicked = function () {
		this.removeButton.request( twatchFullUrl( 'rec/rec_comments.php' ), 'a=remove&i='+this.id );
	};
	
	Comment.prototype.removeConfirmed = function () {
		this.ardeList.removeItem( this );
	};
	
	ArdeClass.extend( Comment, ArdeComponent );
	
	function Comments( minYear, maxYear, sYear, sMonth, sDay ) {
		this.ArdeComponent( 'div' );
		
		
		
		var p = new ardeE( 'p' ).appendTo( new ArdeComponent( 'div' ).cls( 'block' ).appendTo( this ) );
		this.area = new ArdeTextArea().setCols( 50 ).appendTo( p );

		this.dateSelect = new DateSelect( minYear, maxYear, sYear, sMonth, sDay );
		p.append( ardeE( 'br' ) ).append( this.dateSelect );
		
		this.visibilitySelect = new ArdeSelect();
		this.visibilitySelect.append( ardeE( 'option' ).append( ardeT( 'Public' ) ) );
		this.visibilitySelect.append( ardeE( 'option' ).append( ardeT( 'Private' ) ) );
		p.append( ardeT( ardeNbsp( 3 ) ) ).append( this.visibilitySelect );
		
		this.addButton = new ArdeRequestButton( 'Add' );
		this.addButton.setStandardCallbacks( this, 'add' );
		p.append( ardeT( ardeNbsp( 3 ) ) ).append( this.addButton );
		
		this.resetButton = new ArdeRequestButton( 'Reset', 'this will delete all comments, are you sure?' ).setCritical( true );
		this.append( ardeE( 'div' ).cls( 'group' ).append( ardeE( 'p' ).append( this.resetButton ) ) );
		this.resetButton.setStandardCallbacks( this, 'reset' );
		
		this.ArdeComponentList( new ArdeComponent( 'div' ).appendTo( this ) );

	}
	
	Comments.prototype.addClicked = function () {
		if( this.visibilitySelect.element.selectedIndex == 1 ) {
			var priv = '&p=t';
		} else {
			var priv = '';
		}
		this.addButton.request( twatchFullUrl( 'rec/rec_comments.php' ), 'a=add&'+this.dateSelect.getParams()+priv+'&t='+ardeEscape( this.area.element.value ), Comment );
	};
	
	Comments.prototype.addConfirmed = function( comment ) {
		this.insertFirstItem( comment );
		this.area.element.value = '';
		this.area.element.focus();
	};
	
	Comments.prototype.resetClicked = function () {
		this.resetButton.request( twatchFullUrl( 'rec/rec_comments.php' ), 'a=reset' );
	};
	
	Comments.prototype.resetConfirmed = function () {
		this.removeAllItems();
	};
	
	ArdeClass.extend( Comments, ArdeComponent );
	ArdeClass.extend( Comments, ArdeComponentList );

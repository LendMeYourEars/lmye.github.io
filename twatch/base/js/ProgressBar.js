
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
    
	function ProgressBar() {	
		
		this.value = 0;
		
		this.channelId = Math.floor( Math.random() * 0x7FFFFFFF );
		
		this.ArdeComponent( 'span' );
		this.setDisplayMode( 'inline-block' );
		this.style( 'width', '200px' ).style( 'height', '15px' ).style( 'border', '1px solid #000' ).style( 'background', '#fff' );
		
		this.indicator = new ArdeComponent( 'span' ).setDisplayMode( 'inline-block' ).appendTo( this );
		this.indicator.style( 'height', '15px' ).style( 'width', '0' ).style( 'background', '#080' );
		this.indicator.append( new ArdeImg( baseUrl + 'img/dummy.gif', null, 1, 1 ) );
		
		var self = this;
		
		this.requester = new ArdeStdRequester();
		this.requester.resultReceived = function ( result ) {
			self.receive( result );
		}
	}
	
	ProgressBar.prototype.startPoll = function( recUrl ) {
		this.recUrl = recUrl;
		var self = this;
		this.interval = setInterval( function () { self.poll(); }, 500 );
	}
	
	ProgressBar.prototype.stopPoll = function () {
		clearInterval( this.interval );
	}
	
	ProgressBar.prototype.poll = function () {
		if( this.requester.isInProgress() ) {
			return;
		}
		this.requester.request( this.recUrl, 'ch='+this.channelId, ProgressResult );
	}
	
	ProgressBar.prototype.receive = function ( result ) {
		if( result.num <= this.value ) return;
		this.value = result.num;
		this.indicator.style( 'width', ( result.num * 100 )+'%' );
	}
	
	ArdeClass.extend( ProgressBar, ArdeComponent );
	
	function ProgressResult( num ) {
		this.num = num;
	}
	
	ProgressResult.fromXml = function( element ) {
		return new ProgressResult( ArdeXml.floatContent( element ) );
	}
	


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

	function Importer( files ) {
		this.files = files;
		this.ArdeComponent( 'div' );
		this.mainDiv = new ArdeComponent( 'div' ).cls( 'block' ).appendTo( this );

		this.select = new ArdeSelect( 5 );
		this.empty = new ArdeComponent( 'span' ).append( ardeE( 'b' ).append( ardeT( 'store' ) ) ).append( ardeT( ' folder is empty.' ) );
		this.mainDiv.append( ardeE( 'p' ).append( this.select ).append( this.empty ) );
		this.refreshButton = new ArdeRequestButton( 'Refresh' );
		this.refreshButton.setStandardCallbacks( this, 'refresh' );
		this.readButton = new ArdeRequestButton( 'Read File' );
		this.readButton.setStandardCallbacks( this, 'read' );
		this.mainDiv.append( ardeE( 'p' ).append( this.refreshButton ).append( this.readButton ) );
		this.fillSelect();
		this.fileHolder = new ArdeComponent( 'div' ).appendTo( this );
	}
	
	Importer.prototype.fillSelect = function () {
		this.select.clear();
		for( var i in this.files ) {
			this.select.append( ardeE( 'option' ).attr( 'value', i ).append( ardeT( this.files[i] ) ) );
		}
		
		this.empty.setDisplay( !this.files.length );
		this.select.setDisplay( this.files.length );
		this.readButton.setDisplay( this.files.length );
	}
	
	Importer.prototype.refreshClicked = function () {
		this.refreshButton.request( twatchFullUrl( 'rec/rec_import.php' ), 'a=list_dir', ardeXmlObjectListClass( ImportFileInfo, 'file' ) );
	}
	
	Importer.prototype.refreshConfirmed = function ( files ) {
		this.files = [];
		for( var i in files.a ) {
			this.files.push( files.a[i].name );
		}
		this.fillSelect();
	}
	
	Importer.prototype.readClicked = function () {
		if( this.select.element.selectedIndex < 0 ) return ardeAlert( 'please select a file from the list' );
		this.readButton.request( twatchFullUrl( 'rec/rec_import.php' ), 'a=read&f='+ardeEscape( this.files[ this.select.selectedOption().value ] ), ImportFile );
	}
	
	Importer.prototype.readConfirmed = function ( file ) {
		this.fileHolder.clear();
		this.fileHolder.append( file );
	}
	
	ArdeClass.extend( Importer, ArdeComponent );
	
	function ImportFileInfo( name ) {
		this.name = name;
	}
	ImportFileInfo.fromXml = function ( element ) {
		return new ImportFileInfo( ArdeXml.strContent( element ) );
	}
	
	function ImportFile ( name, periodTypes, counters, del ) {
		this.name = name;
		this.periodTypes = periodTypes;
		this.counters = counters;
		this.del = del;
		
		this.ArdeComponent( 'div' );
		this.cls( 'block' );
		this.append( ardeE( 'p' ).append( ardeT( 'file: ' ) ).append( ardeE( 'b' ).append( ardeT( this.name ) ) ) );
		

		

		this.websiteSelect = new ArdeSelect();
		for( var i in websites ) {
			this.websiteSelect.append( ardeE( 'option' ).attr( 'value', i ).append( ardeT( websites[i] ) ) );
		}

		this.importButton = new ArdeRequestButton( 'Import' );
		this.importButton.setStandardCallbacks( this, 'import' );
		this.progressHolder = new ArdeComponent( 'span' );
		this.allFromMonthCheckbox = new ArdeCheckBox( false );
		this.append( ardeE( 'p' ).append( ardeE( 'label' ).append( this.allFromMonthCheckbox ).append( ardeT( 'Make "All Time" data from sum of monthly data (check this if you\'re importing TraceWatch 0.234 data)' ) ) ) );
		this.append( ardeE( 'p' ).append( ardeT( 'Website: ' ) ).append( this.websiteSelect ).append( ardeT( ' ' ) ).append( this.importButton ).append( this.progressHolder ) );
		
		
		this.append( ardeE( 'hr' ) );

		this.append( ardeE( 'h2' ).append( ardeT( 'File Contents' ) ) );

		if( this.del !== null ) {
			this.append( ardeE( 'div' ).cls( 'group' ).append( this.del ) );
		}
		
		for( var i in this.periodTypes ) {
			var pt = this.periodTypes[i];
			var newPt = new PeriodType( pt.id, pt.name, null, pt.minName, pt.minTs, pt.minCode, pt.maxName, pt.maxTs, pt.maxCode, pt.start, pt.stop );
			
			
			this.append( newPt );
		}

		for( var i in this.counters ) {
			this.append( this.counters[i] );
		}
		
	}
	
	ImportFile.prototype.getParams = function () {
		var cs = '';
		var c = 0;
		for( var i in this.counters ) {
			if( this.counters[i].noneSelected() ) continue;
			cs += '&'+this.counters[i].getParams( 'c'+c+'_' );
			++c;
		}
		return 'n='+ardeEscape( this.name )+'&w='+this.websiteSelect.selectedOption().value+'&am='+(this.allFromMonthCheckbox.element.checked?'t':'f')+'&cc='+c+cs;
	}
	
	ImportFile.prototype.importClicked = function () {
		this.progressHolder.clear();
		this.progressHolder.setDisplay( true );
		var progress = new ProgressBar();
		this.progressHolder.append( ardeT( ' ' ) ).append( progress );
		progress.startPoll( twatchFullUrl( twatchUrl + 'rec/rec_progress.php' ) );
		this.importButton.oldSomethingReceived = this.importButton.somethingReceived;
		var self = this;
		this.importButton.somethingReceived = function () {
			progress.stopPoll();
			self.progressHolder.setDisplay( false );
			self.importButton.somethingReceived = self.importButton.oldSomethingReceived;
			return self.importButton.oldSomethingReceived();
		}

		this.importButton.request( twatchFullUrl( 'rec/rec_import.php' ), 'a=import&ch='+progress.channelId+'&'+this.getParams() );
	}
	
	ImportFile.fromXml = function ( element ) {
		var name = ArdeXml.strAttribute( element, 'name' );
		var periodTypes = {};
		periodTypeEs = new ArdeXmlElemIter( element, 'period_type' );
		while( periodTypeEs.current ) {
			var periodType = PeriodType.fromXml( periodTypeEs.current );
			periodTypes[ periodType.id ] = periodType;
			periodTypeEs.next();
		}
		var counters = [];
		counterEs = new ArdeXmlElemIter( element, 'counter' );
		while( counterEs.current ) {
			counters.push( ImportCounter.fromXml( counterEs.current ) );
			counterEs.next();
		}
		var starts = {};
		startEs = new ArdeXmlElemIter( element, 'start' );
		while( startEs.current ) {
			var periodType = ArdeXml.intAttribute( startEs.current, 'period_type', -1 );
			starts[ periodType ] = ImportTime.fromXml( startEs.current );
			startEs.next();
		}
		var stops = {};
		stopEs = new ArdeXmlElemIter( element, 'stop' );
		while( stopEs.current ) {
			var periodType = ArdeXml.intAttribute( stopEs.current, 'period_type', -1 );
			stops[ periodType ] = ImportTime.fromXml( stopEs.current );
			stopEs.next();
		}
		
		var delE = ArdeXml.element( element, 'delete', null );
		if( delE === null ) var del = null;
		else var del = ImportDelete.fromXml( delE );
		
		return new ImportFile( name, periodTypes, counters, del );
	}
	
	ArdeClass.extend( ImportFile, ArdeComponent );

	function ImportTime( ts, title ) {
		this.ts = ts;
		this.title = title;
	}
	
	ImportTime.fromXml = function ( element ) {
		var ts = ArdeXml.intAttribute( element, 'ts' );
		var title = ArdeXml.strContent( element );
		return new ImportTime( ts, title );
	}

	function ImportCounter( name, mappedId, periodTypes, availability, del ) {
		this.name = name;
		this.mappedId = mappedId;
		this.periodTypes = periodTypes;
		this.availability = availability;
		this.del = del;
		
		this.ArdeComponent( 'div' );
		this.cls( 'sub_block' );
		
		
		
		this.counterSelect = new ArdeSelect();
		var o = ardeE( 'option' ).append( ardeT( 'None' ) ).appendTo( this.counterSelect );
		
		if( this.mappedId === null ) o.attr( 'selected', 'true' );
		for( var i in counters ) {
			o = ardeE( 'option' ).append( ardeT( counters[i].name ) ).attr( 'value', i ).appendTo( this.counterSelect );
			if( this.mappedId !== null && this.mappedId == i ) o.attr( 'selected', 'true' );
		}
		
		
		
		this.append( ardeE( 'p' ).append( ardeE( 'span').cls( 'info' ).append( ardeT( this.name ) ) ).append( ardeT( ardeNbsp(4)+'=>'+ardeNbsp(4) ) ).append( this.counterSelect ) );
		
		if( this.del !== null ) {
			this.append( ardeE( 'div' ).cls( 'group' ).append( this.del ) );
		}
		
		if( ardeMembersCount( this.availability.timestamps ) ) {
			this.append( ardeE( 'p' ).append( this.availability ) );
		}
		
		for( var i in this.periodTypes ) {
			this.append( this.periodTypes[i] );
		}
	}
	
	ImportCounter.prototype.getParams = function ( prefix ) {
		if( this.noneSelected() ) var mi = '';
		else var mi = this.counterSelect.selectedOption().value;
		return prefix+'n='+ardeEscape(this.name)+'&'+prefix+'mi='+mi;
	}
	
	ImportCounter.prototype.noneSelected = function () {
		return this.counterSelect.element.selectedIndex == 0;
	}
	
	ImportCounter.fromXml = function( element ) {
		var name = ArdeXml.strAttribute( element, 'name' );
		var mappedId = ArdeXml.intAttribute( element, 'mapped_id', null );
		var periodTypeEs = new ArdeXmlElemIter( element, 'period_type' );
		var periodTypes = [];
		while( periodTypeEs.current ) {
			periodTypes.push( PeriodType.fromXml( periodTypeEs.current ) );
			periodTypeEs.next();
		}
		var availability = CounterAvailability.fromXml( ArdeXml.element( element, 'availability' ) );
		
		var delE = ArdeXml.element( element, 'delete', null );
		if( delE === null ) var del = null;
		else var del = ImportDelete.fromXml( delE );
		
		return new ImportCounter( name, mappedId, periodTypes, availability, del );
	}
	
	ArdeClass.extend( ImportCounter, ArdeComponent );


	function ImportDelete( tss, availability, groupCount, rowCount ) {
		this.tss = tss;
		this.availability = availability;
		this.rowCount = rowCount;
		this.groupCount = groupCount;
		
		this.ArdeComponent( 'div' );
		
		var p = ardeE( 'p' ).appendTo( this );
		p.append( ardeE( 'span' ).cls( 'critical' ).append( ardeT( 'Delete' ) ) );
		if( this.tss.length ) {
			tb = ardeE( 'tbody' ).appendTo( ardeE( 'table' ).appendTo( p ) );
			CounterAvailability.appendTssTrs( {0:this.tss}, tb );
			p = ardeE( 'p' ).appendTo( this );
		}
		if( !ardeMembersCount( this.availability.timestamps ) ) p.append( ardeT( this.tss.length?' all period types':' all time' ) );
		else {
			this.append( ardeE( 'p' ).append( this.availability ) );
			p = ardeE( 'p' ).appendTo( this );
		}
		if( this.groupCount ) p.append( ardeT( ' - ' ) ).append( ardeE( 'b' ).append( ardeT( this.groupCount ) ) ).append( ardeT( ' specific group'+(this.groupCount==1?'':'s') ) );
		if( this.rowCount ) p.append( ardeT( ' - ' ) ).append( ardeE( 'b' ).append( ardeT( this.rowCount ) ) ).append( ardeT( ' specific row'+(this.rowCount==1?'':'s') ) );
		if( !this.groupCount && !this.rowCount ) {
			p.append( ardeT( ' - all rows' ) );
		}
	}
	
	ImportDelete.fromXml = function( element ) {
		var tss = CounterAvailability.tssFromXml( element );
		var availability = CounterAvailability.fromXml( ArdeXml.element( element, 'availability' ) );
		var rowCount = ArdeXml.intAttribute( element, 'row_count' );
		var groupCount = ArdeXml.intAttribute( element, 'group_count' );
		return new ImportDelete( tss, availability, groupCount, rowCount );
	}
	
	ArdeClass.extend( ImportDelete, ArdeComponent );

	function PeriodType( id, name, count, minName, minTs, minCode, maxName, maxTs, maxCode, start, stop, detStartTs, detStopTs ) {
		this.id = id;
		this.name = name;
		this.count = count;
		this.minName = minName;
		this.minTs = minTs;
		this.minCode = minCode;
		this.maxName = maxName;
		this.maxTs = maxTs;
		this.maxCode = maxCode;
		
		if( typeof start == 'undefined' ) this.start = null;
		else this.start = start;
		
		if( typeof stop == 'undefined' ) this.stop = null;
		else this.stop = stop;
		
		if( typeof detStartTs == 'undefined' ) this.detStartTs = null;
		else this.detStartTs = detStartTs;
		
		if( typeof detStopTs == 'undefined' ) this.detStopTs = null;
		else this.detStopTs = detStopTs;
		
		this.ArdeComponent( 'div' );
		this.cls( 'group' );
		var p = ardeE( 'p' ).appendTo( this );
		p.append( ardeE( 'b' ).append( ardeT( this.name ) ) );
		if( this.count !== null ) {
			p.append( ardeT( ' ('+this.count+')' ) );
		}
		p.append( ardeT( ' data from ' ) ).append( ardeE( 'span' ).cls( 'pad_full' ).style( 'background', '#eaeaea' ).append( ardeT( this.minName ) ) ).append( ardeT( ' to ' ) ).append( ardeE( 'span' ).cls( 'pad_full' ).style( 'background', '#eaeaea' ).append( ardeT( this.maxName ) ) );
		
		if( this.start !== null ) {
			this.append( ardeE( 'p' ).append( ardeT( 'declared start time: '+this.start.title ) ) );
		}
		if( this.stop !== null ) {
			this.append( ardeE( 'p' ).append( ardeT( 'declared stop time: '+this.stop.title ) ) );
		}

	}
	PeriodType.fromXml = function( element ) {
		
		var id = ArdeXml.intAttribute( element, 'id' );
		
		var name = ArdeXml.strAttribute( element, 'name' );
		var count = ArdeXml.intAttribute( element, 'count', null );
		
		var minE = ArdeXml.element( element, 'min' );
		var minName = ArdeXml.strContent( minE );
		var minTs = ArdeXml.intAttribute( minE, 'ts' );
		var minCode = ArdeXml.strAttribute( minE, 'code' );
		
		var maxE = ArdeXml.element( element, 'max' );
		var maxName = ArdeXml.strContent( maxE );
		var maxTs = ArdeXml.intAttribute( maxE, 'ts' );
		var maxCode = ArdeXml.strAttribute( maxE, 'code' );
		
		var detStartTs = ArdeXml.intElement( element, 'det_start_ts', null );
		var detStopTs = ArdeXml.intElement( element, 'det_stop_ts', null );
		
		return new PeriodType( id, name, count, minName, minTs, minCode, maxName, maxTs, maxCode, null, null, detStartTs, detStopTs );
	}
	
	ArdeClass.extend( PeriodType, ArdeComponent );

	function Counter( id, type, name, entityId, groupEntityId, groupAllowExplicitAdd, possibleSubs, set ) {
		this.id = id;
		this.name = name;
		this.type = type;
		this.groupEntityId = groupEntityId;
		this.entityId = entityId;
		this.groupAllowExplicitAdd = groupAllowExplicitAdd;
		this.possibleSubs = possibleSubs;
		this.set = set;
	};
	Counter.TYPE_SINGLE = 0;
	Counter.TYPE_LIST = 1;
	Counter.TYPE_SUB = 2;
	
	
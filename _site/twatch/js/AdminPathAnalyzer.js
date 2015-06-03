
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
  
function PathAnalyzer( maxSamples, perTask, depth, dataColumns, cleanupCycle, pathsLiveFor ) {
	this.maxSamples = maxSamples;
	this.perTask = perTask;
	this.depth = depth;
	this.dataColumns = dataColumns;
	this.cleanupCycle = cleanupCycle;
	this.pathsLiveFor = pathsLiveFor;
}

PathAnalyzer.fromXml = function( element ) {
	datasE = ArdeXml.element( element, 'data_columns' );
	dataEs = new ArdeXmlElemIter( datasE, 'column' );
	dataColumns = new Array();
	while( dataEs.current ) {
		dataColumns.push( ArdeXml.intAttribute( dataEs.current, 'entity_id') );
		dataEs.next();
	}
	return new PathAnalyzer(
		ArdeXml.intAttribute( element, 'max_samples' )
		,ArdeXml.intAttribute( element, 'per_task' )
		,ArdeXml.intAttribute( element, 'depth' )
		,dataColumns
		,ArdeXml.intAttribute( element, 'cleanup_cycle' )
		,ArdeXml.intAttribute( element, 'paths_live_for' )
	);
}

function AdminPathAnalyzer( pathAnalyzer, installed, defPathAnalyzer, visibility ) {
	this.pathAnalyzer = pathAnalyzer;
	this.defPathAnalyzer = defPathAnalyzer;
	this.installed = installed;

	
	
	var self = this;
	
	this.ArdeComponent( 'div' );
	
	this.visSelect = new BinarySelector( visibility, 'Visible', 'Hidden', 'Default' );
	this.visApplyButton = new ArdeRequestButton( 'Apply' );
	this.visApplyButton.setStandardCallbacks( this, 'visApply' );
	var p = ardeE( 'p' ).appendTo( this );
	
	p.append( ardeT( 'Path analysis page visibility ('+websiteName ) );
	if( !configMode ) {
		p.append( ardeT( ' / ' ) ).append( selectedUser.getName() );
	}
	p.append( ardeT( '): ' ) ).append( this.visSelect );
	
	p.append( ardeT( ' ' ) ).append( this.visApplyButton );
	
	if( configMode ) return;
	
	var div = ardeE( 'div' ).cls( 'block' ).appendTo( this );
	
	
	var p = ardeE( 'p' ).appendTo( div );
	
	this.turnOffSpan = new ArdeComponent( 'span' ).append( ardeT( 'Path analyzer is ' ) ).append( ardeE( 'span' ).cls( 'good' ).append( ardeT( 'running' ) ) );
	this.turnOnSpan = new ArdeComponent( 'span' ).append( ardeT( 'Path analyzer is ' ) ).append( ardeE( 'span' ).cls( 'critical' ).append( ardeT( 'turned off' ) ) );
	this.turnOffSpan.setDisplay( installed );
	this.turnOnSpan.setDisplay( !installed );
	p.append( this.turnOffSpan ).append( this.turnOnSpan );
	
	var p = ardeE( 'p' ).appendTo( ardeE( 'div' ).cls( 'block' ).appendTo( this ) );
	
	this.terminateButton = new ArdeRequestButton( 'Terminate Path Analyzer', ' are you sure? this will delete all path analysis data currently in database' ); 
	this.terminateButton.setCritical( true );
	this.terminateButton.onclick = function() { self.terminateClicked(); };
	this.terminateButton.afterResultReceived = function( result ) { self.terminateConfirmed( result ); };
	
	this.turnOnButton = new ArdeRequestButton( 'Turn On Path Analysis' );
	this.turnOnButton.onclick = function() { self.turnOnClicked(); };
	this.turnOnButton.afterResultReceived = function( result ) { self.turnOnConfirmed( result ); };
	
	this.turnOnButton.setDisplay( !installed );
	this.terminateButton.setDisplay( installed );
	
	p.append( this.turnOnButton ).append( this.terminateButton ).append( ardeT( ' ' ) );
	
	this.resetButton = new ArdeRequestButton( 'Reset Path Analyzer', 'are you sure? this will delete all path analyzer data currently in database' );
	this.resetButton.setCritical( true );
	this.resetButton.onclick = function () { self.resetClicked(); };
	this.resetButton.afterResultReceived = function( result ) { self.resetConfirmed( result ); };
	this.resetButton.setDisplay( installed );
	p.append( this.resetButton ).append( ardeT( ' ' ) );
	
	
	this.restoreButton = new ArdeRequestButton( 'Restore Defaults and Reset', 'are you sure? this will delete all path analyzer data currently in database' );
	this.restoreButton.onclick = function() { self.restoreClicked(); };
	this.restoreButton.afterResultReceived = function( result ) { self.restoreConfirmed( result ); };
	this.restoreButton.setCritical( installed );
	p.append( this.restoreButton );
	
	div = ardeE( 'div' ).cls( 'block' ).appendTo( this );
	
	div.append( ardeE( 'h2' ).append( ardeT( 'Sampling' ) ) );
	
	var tb = ardeE( 'tbody' ).appendTo( ardeE( 'table' ).cls( 'form' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( div ) );
	
	var tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'Max Samples Per Day:' ) ) );
	this.maxSamplesInput = new ArdeInput( pathAnalyzer.maxSamples );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.maxSamplesInput ) );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'Samples Per Task:' ) ) );
	this.perTaskInput = new ArdeInput( pathAnalyzer.perTask );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.perTaskInput ) );

	p = ardeE( 'p' ).appendTo( div );
	this.samplingApplyButton = new ArdeRequestButton( 'Apply Changes' );
	this.samplingApplyButton.onclick = function () { self.samplingApplyClicked(); };
	p.append( this.samplingApplyButton ).append( ardeT( ' ' ) );

	this.samplingDefaultsButton = new ArdeButton( 'Defaults' );
	this.samplingDefaultsButton.cls( 'passive' );
	this.samplingDefaultsButton.element.onclick = function () { self.samplingDefaultsClicked(); };
	p.append( this.samplingDefaultsButton );

	div = ardeE( 'div' ).cls( 'block' ).appendTo( this );

	div.append( ardeE( 'h2' ).append( ardeT( 'Structure' ) ) );
	
	tb = ardeE( 'tbody' ).appendTo( ardeE( 'table' ).cls( 'form' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( div ) );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).style( 'verticalAlign', 'top' ).append( ardeT( 'DataColumns:' ) ) );
	
	td = ardeE( 'td' ).cls( 'tail' ).appendTo( tr );
	this.entitySelect = document.createElement( 'select' );
	this.entitySelect.appendChild( ardeE( 'option' ).n );
	for( var entityId in entities ) {
		var option = document.createElement( 'option' );
		option.appendChild( ardeT( entities[ entityId ] ).n );
		option.value = entityId;
		this.entitySelect.appendChild( option );
	}
	td.append( this.entitySelect );
	
	this.dataColumnAddButton = document.createElement( 'input' );
	this.dataColumnAddButton.type = 'button';
	this.dataColumnAddButton.className = 'passive';
	this.dataColumnAddButton.value = 'ADD';
	
	td.append( this.dataColumnAddButton ).append( ardeE( 'br' ) );
	
	this.dataColumnsSelect = document.createElement( 'select' );
	this.dataColumnsSelect.size = 5;
	this.dataColumnsSelect.style.width = '270px';
	for( var i in pathAnalyzer.dataColumns ) {
		var option = document.createElement( 'option' );
		var t = '%error%';
		if( typeof entities[ pathAnalyzer.dataColumns[i] ] != 'undefined' ) t = entities[ pathAnalyzer.dataColumns[i] ];
		option.appendChild( ardeT( t ).n );
		option.value = pathAnalyzer.dataColumns[ i ];
		this.dataColumnsSelect.appendChild( option );
	}
	td.append( this.dataColumnsSelect ).append( ardeE( 'br' ) );

	this.dataColumnsDeleteButton = new ArdeButton( 'Delete' ).cls( 'passive' );
	this.dataColumnsUpButton = new ArdeButton( 'Up' ).cls( 'passive' );
	this.dataColumnsDownButton = new ArdeButton( 'Down' ).cls( 'passive' );
	
	this.dataColumnAddButton.onclick = function() {
		if( self.entitySelect.selectedIndex ) {
			entityId = self.entitySelect.options[ self.entitySelect.selectedIndex ].value;
			var option = ardeE( 'option' ).append( ardeT( entities[ entityId ] ) ).n;
			option.value = entityId;
			self.dataColumnsSelect.appendChild( option );
			self.entitySelect.selectedIndex = 0;
		}
	};
	
	this.dataColumnsDeleteButton.element.onclick = function() {
		if( self.dataColumnsSelect.selectedIndex >= 0 ) {
			self.dataColumnsSelect.options[ self.dataColumnsSelect.selectedIndex ] = null;
		}
	};
	
	this.dataColumnsUpButton.element.onclick = function() {
		si = self.dataColumnsSelect.selectedIndex;
		if( si > 0 ) {
			var tmp = self.dataColumnsSelect.options[ si - 1 ];
			self.dataColumnsSelect.options[ si - 1 ] =  self.dataColumnsSelect.options[ si ];
			self.dataColumnsSelect.options[ si ] = tmp;
		}
	};
	
	this.dataColumnsDownButton.element.onclick = function() {
		si = self.dataColumnsSelect.selectedIndex;
		if( si >= 0 && si < self.dataColumnsSelect.options.length - 1 ) {
			var tmp = self.dataColumnsSelect.options[ si ];
			self.dataColumnsSelect.options[ si ] =  self.dataColumnsSelect.options[ si + 1 ];
			self.dataColumnsSelect.options[ si + 1 ] = tmp;
		}
	};
	
	td.append( this.dataColumnsDeleteButton ).append( ardeT( ' ' ) ).append( this.dataColumnsUpButton ).append( this.dataColumnsDownButton );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'Depth:' ) ) );
	this.depthInput = new ArdeInput( pathAnalyzer.depth );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.depthInput ) );
	
	p = ardeE( 'p' ).appendTo( div );
	this.structureApplyButton = new ArdeRequestButton( 'Apply Changes', 'are you sure? this will reset path analysis and delete all current data.' );
	this.structureApplyButton.setCritical( installed );
	this.structureApplyButton.onclick = function () { self.structureApplyClicked(); };
	this.structureApplyButton.afterResultReceived = function( result ) { self.structureApplyConfirmed( result ); };
	p.append( this.structureApplyButton ).append( ardeT( ' ' ) );
	
	this.structureDefaultsButton = new ArdeButton( 'Defaults' ).cls( 'passive' );
	this.structureDefaultsButton.element.onclick = function () { self.structureDefaultsClicked(); };
	p.append( this.structureDefaultsButton );
	
	div = ardeE( 'div' ).cls( 'block' ).appendTo( this );
	div.append( ardeE( 'h2' ).append( ardeT( 'Cleanup' ) ) );
	var tb = ardeE( 'tbody' ).appendTo( ardeE( 'table' ).cls( 'form' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( div ) );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'Cleanup Cycle:' ) ) );
	this.cleanupCycleInput = new ArdeDurationInput( pathAnalyzer.cleanupCycle, ArdeDuration.DAYS );
	
	td = ardeE( 'td' ).cls( 'tail' ).append( this.cleanupCycleInput );
	tr.append( td );
	
	tr = ardeE( 'tr' ).appendTo( tb );
	tr.append( ardeE( 'td' ).cls( 'head' ).append( ardeT( 'Paths Live For:' ) ) );
	this.pathsLiveForInput = new ArdeInput( pathAnalyzer.pathsLiveFor );
	this.pathsLiveForInput.attr( 'size', '2' );
	tr.append( ardeE( 'td' ).cls( 'tail' ).append( this.pathsLiveForInput ).append( ardeT( ' full cycles ' ) ) );
	
	p = ardeE( 'p' ).appendTo( div );
	
	this.cleanupApplyButton = new ArdeRequestButton( 'Apply Changes' );
	this.cleanupApplyButton.onclick = function () { self.cleanupApplyClicked(); };
	p.append( this.cleanupApplyButton ).append( ardeT( ' ' ) );;
	
	
	this.cleanupDefaultsButton = new ArdeButton( 'Defaults' ).cls( 'passive' );
	this.cleanupDefaultsButton.element.onclick = function () { self.cleanupDefaultsClicked(); };
	p.append( this.cleanupDefaultsButton );
}

AdminPathAnalyzer.prototype.visApplyClicked = function () {
	this.visApplyButton.request( twatchFullUrl( 'rec/rec_path_analyzer.php' ), 'a=set_vis&'+this.visSelect.getParams() );
};

AdminPathAnalyzer.prototype.setInstalled = function( installed ) {
	this.installed = installed;
	this.turnOffSpan.setDisplay( installed );
	this.turnOnSpan.setDisplay( !installed );
	
	this.turnOnButton.setDisplay( !installed );
	this.terminateButton.setDisplay( installed );
	
	this.resetButton.setDisplay( installed );
	this.restoreButton.setCritical( installed );
	
	this.structureApplyButton.setCritical( installed );
	
	
};

AdminPathAnalyzer.prototype.updatePathAnalyzer = function( pathAnalyzer ) {
	this.maxSamplesInput.setValue( pathAnalyzer.maxSamples );
	this.perTaskInput.setValue( pathAnalyzer.perTask );
	while( this.dataColumnsSelect.options.length ) this.dataColumnsSelect.options[0] = null;
	for( var i in pathAnalyzer.dataColumns ) {
		var option = document.createElement( 'option' );
		var t = '%error%';
		if( typeof entities[ pathAnalyzer.dataColumns[i] ] != 'undefined' ) t = entities[ pathAnalyzer.dataColumns[i] ];
		option.appendChild( ardeT( t ).n );
		option.value = pathAnalyzer.dataColumns[ i ];
		this.dataColumnsSelect.appendChild( option );
	}
	this.depthInput.setValue( pathAnalyzer.depth );
	this.cleanupCycleInput.setValue( pathAnalyzer.cleanupCycle );
	this.pathsLiveForInput.setValue( pathAnalyzer.pathsLiveFor );
};


AdminPathAnalyzer.prototype.terminateClicked = function () {
	this.terminateButton.request( twatchFullUrl( 'rec/rec_path_analyzer.php' ), 'a=terminate' );
};

AdminPathAnalyzer.prototype.terminateConfirmed = function () {
	this.setInstalled( false );
	diagHolder.replaceDiag( new PathAnalyzerDiag( false ) );
};

AdminPathAnalyzer.prototype.turnOnClicked = function () {
	this.turnOnButton.request( twatchFullUrl( 'rec/rec_path_analyzer.php' ), 'a=start' );
};

AdminPathAnalyzer.prototype.turnOnConfirmed = function () {
	this.setInstalled( true );
	diagHolder.update();
};

AdminPathAnalyzer.prototype.samplingApplyClicked = function () {
	this.samplingApplyButton.request( twatchFullUrl( 'rec/rec_path_analyzer.php' ), 'a=change_sampling&ms='+this.maxSamplesInput.getValue()+'&pt='+this.perTaskInput.getValue() );
};

AdminPathAnalyzer.prototype.samplingDefaultsClicked = function () {
	this.maxSamplesInput.setValue( this.defPathAnalyzer.maxSamples );
	this.perTaskInput.setValue( this.defPathAnalyzer.perTask );
};

AdminPathAnalyzer.prototype.structureApplyClicked = function () {
	var ds = '';
	for( var i = 0; i < this.dataColumnsSelect.options.length; ++i ) {
		ds += (i?'|':'')+this.dataColumnsSelect.options[i].value;
	}
	this.structureApplyButton.request( twatchFullUrl( 'rec/rec_path_analyzer.php' ), 'a=change_structure&d='+this.depthInput.getValue()+'&ds='+ardeEscape( ds ) );
};

AdminPathAnalyzer.prototype.structureApplyConfirmed = function( result ) {
	if( this.installed ) {
		diagHolder.update();
	}
};

AdminPathAnalyzer.prototype.structureDefaultsClicked = function () {
	while( this.dataColumnsSelect.options.length ) this.dataColumnsSelect.options[0] = null;

	for( var i in this.defPathAnalyzer.dataColumns ) {
		var option = document.createElement( 'option' );
		var t = '%error%';
		if( typeof entities[ this.defPathAnalyzer.dataColumns[i] ] != 'undefined' ) t = entities[ this.defPathAnalyzer.dataColumns[i] ];
		option.appendChild( ardeT( t ).n );
		option.value = this.defPathAnalyzer.dataColumns[ i ];
		this.dataColumnsSelect.appendChild( option );
	}
	this.depthInput.setValue( this.defPathAnalyzer.depth );
};

AdminPathAnalyzer.prototype.cleanupApplyClicked = function() {
	this.cleanupApplyButton.request( twatchFullUrl( 'rec/rec_path_analyzer.php' ), 'a=change_cleanup&cc='+this.cleanupCycleInput.getValue()+'&plf='+this.pathsLiveForInput.getValue() );
};

AdminPathAnalyzer.prototype.cleanupDefaultsClicked = function () {
	this.cleanupCycleInput.setValue( this.defPathAnalyzer.cleanupCycle );
	this.pathsLiveForInput.setValue( this.defPathAnalyzer.pathsLiveFor );
};

AdminPathAnalyzer.prototype.resetClicked = function () {
	this.resetButton.request( twatchFullUrl( 'rec/rec_path_analyzer.php' ), 'a=reset' );
};

AdminPathAnalyzer.prototype.resetConfirmed = function ( result ) {
	diagHolder.update();
};

AdminPathAnalyzer.prototype.restoreClicked = function () {
	this.restoreButton.request( twatchFullUrl( 'rec/rec_path_analyzer.php' ), 'a=restore', PathAnalyzer );
};

AdminPathAnalyzer.prototype.restoreConfirmed = function ( pathAnalyzer ) {
	this.updatePathAnalyzer( pathAnalyzer );
	diagHolder.update();
};

ArdeClass.extend( AdminPathAnalyzer, ArdeComponent );


function PathCount( count, unique ) {
	this.count = count;
	this.unique = unique;
};

PathCount.fromXml  = function ( element ) {
	return new PathCount(
		ArdeXml.intAttribute( element, 'count' )
		,ArdeXml.intAttribute( element, 'unique' )
	);
};
function PathCleanupTask( round, website, due, inQueue ) {
	this.round = round;
	this.website = website;
	this.due = due;
	this.inQueue = inQueue;
};

PathCleanupTask.fromXml = function ( element ) {
	return new PathCleanupTask(
		ArdeXml.intAttribute( element, 'round' )
		,ArdeXml.attribute( element, 'website' )
		,ArdeXml.attribute( element, 'due' )
		,ArdeXml.boolAttribute( element, 'in_queue' )
	);
};

function DiagHolder( diag ) {
	this.ArdeComponent( 'div' );
	this.cls( 'block' );
	
	this.requester = new ArdeRequestButton( 'Update', null, 'Updating...' );
	var self = this;

	this.requester.onclick = function () { self.update(); };
	this.requester.afterResultReceived = function ( result ) {
		self.replaceDiag( result );
	};

	this.append( new ArdeComponent( 'p' ).setFloat( 'right' ).append( this.requester ) );
	this.append( ardeE( 'h2' ).append( ardeT( 'Diagnostic Information ' ) ) );
	
	this.diag = diag;
	
	this.append( diag );
};

DiagHolder.prototype.update = function () {
	this.requester.request( twatchFullUrl( 'rec/rec_path_analyzer.php' ), 'a=get_diag', PathAnalyzerDiag );
};

ArdeClass.extend( DiagHolder, ArdeComponent );

DiagHolder.prototype.replaceDiag = function ( diag ) {
	this.diag.replace( diag );
	this.diag = diag;
};

function PathAnalyzerDiag( installed, pathCounts, cleanupTasks, nextCleanup ) {
	
	this.ArdeComponent( 'div' );
	
	if( !installed ) {
		this.append( ardeE( 'p' ).append( ardeT( 'Not Available' ) ) );
		return this;
	}
	
	var t = ardeE( 'table' ).cls( 'std' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( this );
	var tr = ardeE( 'tr' ).appendTo( ardeE( 'thead' ).appendTo( t ) );
	tr.append( ardeE( 'td' ).append( ardeT( 'Website' ) ) ).append( ardeE( 'td' ).append( ardeT( 'Delete Round') ) ).append( ardeE( 'td' ).append( ardeT( 'Unique Paths') ) );
	tr.append( ardeE( 'td' ).append( ardeT( 'Total Paths') ) );
	var tb = ardeE( 'tbody' ).appendTo( t );

	
	var printWebsiteRows = function( websiteName, pathCounts, allWebsites ) {

		websiteTotal = new PathCount( 0, 0 );
		if( ardeMembersCount( pathCounts ) ) {
			
			
			tr = ardeE( 'tr' ).append( new ArdeTd().setRowSpan( ardeMembersCount( pathCounts ) ).append( ardeT( websiteName ) ) ).appendTo( tb );
			var i = 0;
			for( var deleteRound in pathCounts ) {
				
				if( i ) tr = ardeE( 'tr' ).appendTo( tb );
				tr.append( ardeE( 'td' ).append( ardeT( deleteRound ) ) ).append( ardeE( 'td' ).append( ardeT( pathCounts[ deleteRound ].unique ) ) );
				tr.append( ardeE( 'td' ).append( ardeT( pathCounts[ deleteRound ].count ) ) );
				++i;
				websiteTotal.unique += pathCounts[ deleteRound ].unique;
				websiteTotal.count += pathCounts[ deleteRound ].count;
				if( typeof allWebsites != 'undefined' ) {
					if( typeof allWebsites[ deleteRound ] == 'undefined' ) allWebsites[ deleteRound ] = new PathCount( 0, 0 );
					allWebsites[ deleteRound ].unique += pathCounts[ deleteRound ].unique;
					allWebsites[ deleteRound ].count += pathCounts[ deleteRound ].count;
				}
			}
		}
		tr = ardeE( 'tr' ).appendTo( tb ).cls( 'special' );
		tr.append( new ArdeTd().setColSpan( '2' ).append( ardeT( websiteName+' Total' ) ) );
		tr.append( ardeE( 'td' ).append( ardeT( websiteTotal.unique ) ).style( 'fontWeight', 'bold' ) ).append( ardeE( 'td' ).append( ardeT( websiteTotal.count ) ).style( 'fontWeight', 'bold' ) );
	};
	
	var allWebsites = {};
	
	for( var websiteName in pathCounts ) {
		printWebsiteRows( websiteName, pathCounts[ websiteName ], allWebsites );
	}
	printWebsiteRows( 'All Websites', allWebsites );
	
	this.append( ardeE( 'h3' ).append( ardeT( 'Cleanup Tasks: ' ) ) );
	
	t = ardeE( 'table' ).cls( 'std' ).attr( 'cellpadding', '0' ).attr( 'cellspacing', '0' ).attr( 'border', '0' ).appendTo( this );
	tr = ardeE( 'tr' ).appendTo( ardeE( 'thead' ).appendTo( t ) );
	tr.append( ardeE( 'td' ).append( ardeT( 'Due Time' ) ) ).append( ardeE( 'td' ).append( ardeT( 'Round' ) ) ).append( ardeE( 'td' ).append( ardeT( 'Website' ) ) );
	tr.append( ardeE( 'td' ).append( ardeT( 'in Queue' ) ) );
	tb = ardeE( 'tbody' ).appendTo( t );
	for( var i in cleanupTasks ) {
		tr = ardeE( 'tr' ).appendTo( tb );
		tr.append( ardeE( 'td' ).append( ardeT( cleanupTasks[i].due ) ) ).append( ardeE( 'td' ).append( ardeT( cleanupTasks[i].round ) ) ).append( ardeE( 'td' ).append( ardeT( cleanupTasks[i].website ) ) );
		tr.append( ardeE( 'td' ).append( ardeT( cleanupTasks[i].inQueue?'true':'false' ) ) );
	}
	
	
	this.append( ardeE( 'p' ).append( ardeT( 'Next Cleanup: '+nextCleanup ) ) );
}

PathAnalyzerDiag.fromXml = function ( element ) {
	var installed = ArdeXml.boolAttribute( element, 'installed' );
	if( !installed ) return new PathAnalyzerDiag( false );
	var pathCountsE = ArdeXml.element( element, 'paths_count' );
	var pathCounts = {};
	var websiteEs = new ArdeXmlElemIter( pathCountsE, 'website' );
	while( websiteEs.current ) {
		var websiteName = ArdeXml.attribute( websiteEs.current, 'name' );
		pathCounts[ websiteName ] = {};
		var roundEs = new ArdeXmlElemIter( websiteEs.current, 'round' );
		while( roundEs.current ) {
			var roundNo = ArdeXml.intAttribute( roundEs.current, 'no' );
			pathCounts[ websiteName ][ roundNo ] = PathCount.fromXml( roundEs.current );
			roundEs.next();
		}
		websiteEs.next();
	}
	
	var cleanupTasksE = ArdeXml.element( element, 'cleanup_tasks' );
	var cleanupTasks = new Array();
	var cleanupTaskEs = new ArdeXmlElemIter( cleanupTasksE, 'task' );
	while( cleanupTaskEs.current ) {
		cleanupTasks.push( PathCleanupTask.fromXml( cleanupTaskEs.current ) );
		cleanupTaskEs.next();
	}
	
	return new PathAnalyzerDiag( true, pathCounts, cleanupTasks, ArdeXml.intAttribute( element, 'next_cleanup' ) );
};

ArdeClass.extend( PathAnalyzerDiag, ArdeComponent );
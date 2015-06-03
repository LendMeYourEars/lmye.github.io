
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
    
function TwatchDiag( makeDailyTasks, dbDiagInfo ) {
	this.ArdeComponent( 'div' );
	
	this.append( ardeE( 'h2' ).append( ardeT( 'Database Diagnostic Information' ) ) );
	
	var div = ardeE( 'div' ).cls( 'block' ).appendTo( this );
	
	
	
	div.append( dbDiagInfo );
	
	
	
	this.append( ardeE( 'h2' ).append( ardeT( 'Make Daily Tasks' ) ) );
	
	padDiv = ardeE( 'div' ).appendTo( this );
	
	div = ardeE( 'div' ).cls( 'block' ).appendTo( padDiv );
	
	for( var i in makeDailyTasks ) {
		div.append( new TwatchTaskComp( makeDailyTasks[i] ) );
	}
}

ArdeClass.extend( TwatchDiag, ArdeComponent );

function TwatchTask( due, inQueue ) {
	this.due = due;
	this.inQueue = inQueue;
}

function TwatchTaskComp( task ) {
	this.ArdeComponent( 'div' );
	this.append( ardeE( 'p' ).append( ardeT( 'due: '+task.due+' in Queue: '+(task.inQueue?'true':'false') ) ) );
}
ArdeClass.extend( TwatchTaskComp, ArdeComponent );

function DbDiagInfo( unitInfos, accessUnits ) {
	this.unitInfos = unitInfos;
	this.accessUnits = accessUnits;
	
	this.ArdeComponent( 'div' );
	var t = new ArdeTable().cls( 'std' ).appendTo( this );
	var tr = ardeE( 'tr' ).appendTo( ardeE( 'thead' ).appendTo( t ) );
	tr.append( ardeE( 'td' ).append( ardeT( 'unit' ) ) ).append( ardeE( 'td' ).append( ardeT( 'size' ) ) );
	tr.append( ardeE( 'td' ).append( ardeT( 'sub-unit' ) ) ).append( ardeE( 'td' ).append( ardeT( 'table-name' ) ) );
	tr.append( ardeE( 'td' ).append( ardeT( 'data size' ) ) ).append( ardeE( 'td' ).append( ardeT( 'index size' ) ) );
	
	var tb = ardeE( 'tbody' ).appendTo( t );
	
	var total = 0;
	
	for( var i in this.accessUnits ) {
		tr = ardeE( 'tr' ).appendTo( tb );
		var sz = 0;
		for( var j in this.accessUnits[i].tableNames ) {
			if( typeof this.unitInfos[ this.accessUnits[i].tableNames[j] ] != 'undefined' ) {
				var u = this.unitInfos[ this.accessUnits[i].tableNames[j] ];
				u.used = true;
				sz += u.dataSize + u.indexSize;
			}
		}
		
		var rowCount = this.accessUnits[i].tableNames.length;
		var td1 = new ArdeTd().appendTo( tr );
		var td2 = new ArdeTd().appendTo( tr );
		if(  rowCount > 1 ) {
			td1.setRowSpan( rowCount );
			td2.setRowSpan( rowCount );
		}
		td1.append( ardeT( this.accessUnits[i].name ) );
		td2.append( ardeT( ardeByteSize( sz ) ) );
		
		total += sz;
		
		if( this.accessUnits[i].subs.length == 0 ) this.accessUnits[i].subs.push( new DbAccessUnit( ' ', this.accessUnits[i].tableNames, [] ) );
		
		var j = 0;
		do {
			var td = new ArdeTd().appendTo( tr );
			if( this.accessUnits[i].subs[j].tableNames.length > 1 ) {
				td.setRowSpan( this.accessUnits[i].subs[j].tableNames.length );
			}
			td.append( ardeT( this.accessUnits[i].subs[j].name ) );
			
			var k = 0;
			do {
				tr.append( ardeE( 'td' ).append( ardeT( this.accessUnits[i].subs[j].tableNames[k] ) ) );
				if( typeof this.unitInfos[ this.accessUnits[i].subs[j].tableNames[k] ] != 'undefined' ) {
					var u = this.unitInfos[ this.accessUnits[i].subs[j].tableNames[k] ];
					tr.append( ardeE( 'td' ).append( ardeT( ardeByteSize( u.dataSize ) ) ) );
					tr.append( ardeE( 'td' ).append( ardeT( ardeByteSize( u.indexSize ) ) ) );
				}
				++k;
				if( k != this.accessUnits[i].subs[j].tableNames.length ) {
					tr = ardeE( 'tr' ).appendTo( tb );
				}
			} while( k < this.accessUnits[i].subs[j].tableNames.length );
			
			++j;
			if( j != this.accessUnits[i].subs.length ) {
				tr = ardeE( 'tr' ).appendTo( tb );
			}
		} while( j < this.accessUnits[i].subs.length );
	}
	
	var othersSz = 0;
	var c = 0;
	for( var i in this.unitInfos ) {
		if( !this.unitInfos[i].used ) {
			othersSz += this.unitInfos[i].dataSize + this.unitInfos[i].indexSize;
			++c;
		}
	}
	
	var td1 = new ArdeTd().append( ardeT( 'Others' ) );
	var td2 = new ArdeTd().append( ardeT( ardeByteSize( othersSz ) ) );
	total += othersSz;
	if( c > 1 ) {
		td1.setRowSpan( c );
		td2.setRowSpan( c );
	}
	tr = ardeE( 'tr' ).append( td1 ).append( td2 ).appendTo( tb );
	
	for( var i in this.unitInfos ) {
		if( !this.unitInfos[i].used ) {
			if( tr == null ) tr = ardeE( 'tr' ).appendTo( tb );
			tr.append( ardeE( 'td' ).append( ardeT( ' ' ) ) );
			tr.append( ardeE( 'td' ).append( ardeT( i ) ) );
			tr.append( ardeE( 'td' ).append( ardeT( ardeByteSize( this.unitInfos[i].dataSize ) ) ) );
			tr.append( ardeE( 'td' ).append( ardeT( ardeByteSize( this.unitInfos[i].indexSize ) ) ) );
			tr = null;
		}
	}
	
	tb.append( ardeE( 'tr' ).cls( 'special' ).append( ardeE( 'td' ).append( ardeT( 'total' ) ) ).append( ardeE( 'td' ).append( ardeT( ardeByteSize( total ) ) ) ).append( new ArdeTd().setColSpan( '4' ).append( ardeT( ' ') ) ) );
	
}
ArdeClass.extend( DbDiagInfo, ArdeComponent );

function DbUnitDiagInfo( dataSize, indexSize ) {
	this.dataSize = dataSize;
	this.indexSize = indexSize;
	this.used = false;
	
}

function DbAccessUnit( name, tableNames, subs ) {
	this.name = name;
	this.tableNames = tableNames;
	this.subs = subs;
}
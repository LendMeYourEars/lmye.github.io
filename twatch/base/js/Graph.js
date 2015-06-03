
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
   
function ArdeGraph( xStart, xEnd, xLabels, xNames, series, width, height, zooms, withMenu, withXText, withHScroll, withBorder, indColor, bgColor ) {
	this.xStart = xStart;
	this.xEnd = xEnd;
	this.xLabels = xLabels;
	this.xNames = xNames;
	this.series = series;
	this.width = width;
	this.height = height;
	this.withMenu = withMenu;
	this.withXText = withXText;
	this.withHScroll = withHScroll;
	this.withBorder = withBorder;
	this.indColor = indColor;
	this.bgColor = bgColor;
	this.zooms = zooms;
	
	this.bgColor = bgColor;

	this.ArdeFlash( baseUrl+'fl/graph.swf', this.width+'px', this.height+'px', 'tl', true, 'opaque', 'graphData='+ardeEscape( this.toXml() ) );	


	this.fixMouseEventsBug();
}

ArdeGraph.prototype.toXml = function () {
	var x = '<graph_data with_menu="'+(this.withMenu?1:0)+'" with_xtext="'+(this.withXText?1:0)+'" with_hscroll="'+(this.withHScroll?1:0)+'" with_border="'+(this.withBorder?1:0)+'">';
	x += '<width>'+this.width+'</width>';
	x += '<height>'+this.height+'</height>';
	for( var i in this.zooms ) {
		x +='<zoom level="'+i+'" bar_width="'+this.zooms[i]+'" />';
	}
	x += '<x_start>'+this.xStart+'</x_start>';
	x += '<x_end>'+this.xEnd+'</x_end>';

	x += '<x_labels>';
	for( var i in this.xLabels ) {
		x += '<label i="'+i+'">'+this.xLabels[i]+'</label>';
	}
	x += '</x_labels>';

	x += '<x_names>';
	for( var i in this.xNames ) {
		x += '<name i="'+i+'">'+this.xNames[i]+'</name>';
	}
	x += '</x_names>';
	
	x+='<background>'+this.bgColor+'</background>';
	x+='<ind_color>'+this.indColor+'</ind_color>';

	for(var i in this.series) {
		x+=this.series[i].toXML();
	}
	x+='</graph_data>';
	return x;
}
ArdeClass.extend( ArdeGraph, ArdeFlash );


function ArdeGraphSeriesStyle( gradStart, gradEnd, borderColor ) {
	this.gradStart = gradStart;
	this.gradEnd = gradEnd;
	this.borderColor = borderColor;
}

function ArdeGraphSeries( style, numberTitle ) {
	this.data = new Array();
	this.style = style;
	this.numberTitle = numberTitle;
}

ArdeGraphSeries.prototype.addData = function( data ) {
	this.data[ data.x ] = data;
}

ArdeGraphSeries.prototype.toXML = function () {
	var x = '<series>';
	if( typeof this.numberTitle != 'undefined' )
		x += '<name>'+this.numberTitle+'</name>';
	x += '<grad_st>'+this.style.gradStart+'</grad_st>';
	x += '<grad_en>'+this.style.gradEnd+'</grad_en>';
	x += '<border_c>'+this.style.borderColor+'</border_c>';
	for (var i in this.data ) {
		x += this.data[i].toXML( 'data' );
	}
	x += '</series>';
	return x;
}

function ArdeGraphSeriesData( x, count, span, note ) {
	if( typeof span == 'undefined' ) span = 1;
	if( typeof note == 'undefined' ) note = null;
	this.x = x;
	this.count = count;
	this.span = span;
	this.note = note;
}

ArdeGraphSeriesData.prototype.toXML = function( tagName ) {
	if( this.note !== null ) var note = ' note="'+this.note+'"';
	else var note = '';
	return '<'+tagName+' x="'+this.x+'"'+note+' count="'+this.count+'" span="'+this.span+'" />';
}
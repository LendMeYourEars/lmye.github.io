
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
    
function PathAnalyzer( width, height, dataLength, pageDepth, receiver, websiteId ) {
	this.dataLength = dataLength;
	this.pageDepth = pageDepth;
	this.receiver = receiver;
	this.websiteId = websiteId;
	
	var pathData = this.toXml();
	
	this.ArdeFlash( twatchUrl+'fl/path.swf', width+'px', height+'px', 'tl', false, null, 'pathData='+ardeEscape( pathData ) );
	
	
}
ArdeClass.extend( PathAnalyzer, ArdeFlash );

PathAnalyzer.prototype.toXml = function() {
	var s = '<path_data profile="'+twatchProfile+'" website_id="'+this.websiteId+'" data_length="'+this.dataLength+'" page_depth="'+this.pageDepth+'">';
	s += '<entity_view column="0" show_text="1" show_image="1" link="0" />';
	s += '<entity_view column="1" show_text="1" show_image="0" link="0" />';
	s += '<receiver>'+this.receiver+'</receiver>';
	s += '</path_data>';
	return s;
};

function setFlashSize( width, height ) {
	
	pathAnalyzer.resize( width+'px', height+'px' );
	
	
};
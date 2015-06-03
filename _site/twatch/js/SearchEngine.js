
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

function SearchEngine( id, name, pattern, hasImage, isSearchEngine ) {
	this.isSearchEngine = isSearchEngine;
	this.PatternedObject( id, name, pattern, hasImage, false );
	var p = ardeE( 'p' ).append( ardeT( 'type: ' ) ).appendTo( this.topDiv );
	p.append( ardeE( 'span' ).cls( 'fixed' ).append( ardeT( isSearchEngine?'search engine':'web area' ) ) );

}
    
function SearchEnginesHolder() {
	this.PatternedObjectsHolder( new NewSearchEngine(), 'URL' );
}
SearchEnginesHolder.rec = 'rec/rec_search_engines.php';
SearchEnginesHolder.objectClass = SearchEngine;
SearchEnginesHolder.objectTagName = 'search_engine';

ArdeClass.extend( SearchEnginesHolder, PatternedObjectsHolder );



SearchEngine.fromXml = function ( element ) {
	var id = ArdeXml.intAttribute( element, 'id' );
	var name = ArdeXml.attribute( element, 'name' );
	var pattern = ArdeXml.strElement( element, 'pattern' );
	var hasImage = ArdeXml.boolAttribute( element, 'has_image' );
	var isSearchEngine = ArdeXml.boolAttribute( element, 'is_searche' );
	return new SearchEngine( id, name, pattern, hasImage, isSearchEngine );
}

SearchEngine.rec = 'rec/rec_search_engines.php';
ArdeClass.extend( SearchEngine, PatternedObject );

function NewSearchEngine() {
	this.isSearchEngine = true;
	this.NewPatternedObject();
	var p = ardeE( 'p' ).append( ardeT( 'type: ' ) ).appendTo( this.topDiv );

	this.typeSelect = new ArdeSelect().appendTo( p );
	this.typeSelect.append( new ArdeOption( 'web area', 'web_area' ) );
	this.typeSelect.append( new ArdeOption( 'search engine', 'searche' ) );


}

NewSearchEngine.prototype.getParams = function () {
	return this.NewPatternedObject_getParams()+'&is='+(this.typeSelect.selectedOption().value=='searche'?'t':'f');
};

NewSearchEngine.rec = 'rec/rec_search_engines.php';
NewSearchEngine.title = 'New Web Area';
NewSearchEngine.addButtonName = 'Add Web Area';
NewSearchEngine.objectClass = SearchEngine;
ArdeClass.extend( NewSearchEngine, NewPatternedObject );

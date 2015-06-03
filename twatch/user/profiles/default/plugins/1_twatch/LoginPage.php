<?php

	class TwatchUserLoginPage extends TwatchUserLoginPageParent {
		protected function printBody( ArdePrinter $p ) {
			parent::printBody( $p );
			if( !$this->status == self::SUCCESSFUL_LOGIN ) {
				$p->pl( '<p style="margin-top:0px;font-size:.9em"><b>TraceWatch</b> Web Stats, <span style="color:#060"><b>Free</b> Advanced Website Traffic Analysis</span> <a style="font-weight:bold" href="http://www.tracewatch.com/">www.TraceWatch.com</a></p>' );
			}
		}
	}
?>
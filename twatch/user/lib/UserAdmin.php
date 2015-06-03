<?php
	require_once $ardeUser->path( 'lib/User.php' );
	
	class ArdeUsersAdmin extends ArdeUsers {
		
		public function getUsernameId( $username ) {
			global $ardeUser;
			if( $username == $ardeUser->settings[ 'root_username' ] ) return ArdeUser::USER_ROOT;
			return $this->dbUsers->getUsernameId( $username );
		}
		
		public function add( $username, $password ) {	
			$dbUser = $this->dbUsers->addUser( $username, $this->getPasswordHash( $password ) );
			return ArdeUser::fromDbUser( $dbUser );
		}
		
		public function delete( $userId ) {
			global $ardeUser;
			$res = $this->dbUsers->deleteUser( $userId );
			foreach( $ardeUser->plugins as $plugin ) {
				
				$plugin->afterUserDeleted( $userId );
			}
			return $res;
		}
		
		public function update( $userId, $username, $password ) {
			return $this->dbUsers->updateUser( $userId, $username, $this->getPasswordHash( $password ) );
		}
	}
	
	class BoolValueWithDefault {

		public $value;
		public $isDefault;
		public $defaultValue;
		
		public function __construct( $value, $isDefault, $defaultValue ) {
			$this->value = $value;
			$this->isDefault = $isDefault;
			$this->defaultValue = $defaultValue;
		}
		
		public function printXml( ArdePrinter $p, $tagName, $extraAttr = '' ) {
			$p->pn( '<'.$tagName.' value = "'.ArdeXml::bool( $this->value ).'" is_default = "'.ArdeXml::bool( $this->isDefault ).'" default = "'.ArdeXml::bool( $this->defaultValue ).'" />' );
		}
		
		protected function jsClassName() { return 'BinaryValue'; }
		
		public function jsObject() {
			return 'new '.$this->jsClassName().'( '.ArdeJs::bool( $this->value ).', '.ArdeJs::bool( $this->isDefault ).', '.ArdeJs::bool( $this->defaultValue ).' )';
		}
		
		public static function getFromProperty( $data, $id, $subId = 0 ) {
			return new self( $data->get( $id, $subId ), $data->isDefault( $id, $subId ), $data->getDefault( $id, $subId ) );
		}
	}
	
	class ArdeUserPermission extends BoolValueWithDefault {
	}
?>
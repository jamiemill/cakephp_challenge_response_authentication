<?php

App::import('component','Auth');

class ChallengeResponseAuthenticationAuthComponent extends AuthComponent {
	
 	static $nonceSessionKey = 'ChallengeResponseAuthentication.currentLoginNonce';
	
	function generateLoginNonce() {
		$nonce = md5( rand( 0, 65337 ) . time() );
		$this->Session->write(self::$nonceSessionKey, $nonce);
	}
	
	/**
	* Overrides parent hashPasswords which usually auto-hashed whatever is in the password field in data.
	* This means that before saving a password in the database you'll have to manually call ChallengeResponseAuthenticationAuthComponent->password()
	* to hash it manually.
	*/
	
	function hashPasswords($data) {
		return $data;
	}
	

	function identify($user = null, $conditions = null) {
		$model =& $this->getModel();
		
		// If not an array or doesn't have the password key, delegate back to parent method.
		
		if( !is_array($user) || !isset($user[$this->fields['password']]) && !isset($user[$model->alias . '.' . $this->fields['password']])) {
			return parent::identify($user, $conditions);
		}
		
		// Coped from parent; gets extra scope conditions.
		
		if ($conditions === false) {
			$conditions = null;
		} elseif (is_array($conditions)) {
			$conditions = array_merge((array)$this->userScope, $conditions);
		} else {
			$conditions = $this->userScope;
		}

		if(isset($user[$this->fields['username']])) {
			$suppliedUsername = $user[$this->fields['username']];
			$suppliedNoncedPassword = $user[$this->fields['password']];
		} elseif (isset($user[$model->alias.'.'.$this->fields['username']])) {
			$suppliedUsername = $user[$model->alias.'.'.$this->fields['username']];
			$suppliedNoncedPassword = $user[$model->alias.'.'.$this->fields['password']];
		} else {
			return false;
		}
		
		$find = array(
			$model->alias.'.'.$this->fields['username'] => $suppliedUsername,
		);
		
		$data = $model->find('first', array(
			'conditions' => array_merge($find, $conditions),
			'recursive' => 0
		));
		if (empty($data) || empty($data[$model->alias])) {
			return null;
		}
		
		// Now compare the password and abort if they don't match.
		
		$expectedPasswordNoncedHash = $this->nonceAndReHashPassword($data[$model->alias][$this->fields['password']]);
		if($expectedPasswordNoncedHash !== $suppliedNoncedPassword) {
			return null;
		}

		if (!empty($data)) {
			if (!empty($data[$model->alias][$this->fields['password']])) {
				unset($data[$model->alias][$this->fields['password']]);
			}
			return $data[$model->alias];
		}
		return null;
	}
	
	function nonceAndReHashPassword($password) {
		// Fetch the current login nonce, then delete it so it can only be used once.
		if($nonce = $this->Session->read(self::$nonceSessionKey)) {
			$this->Session->delete(self::$nonceSessionKey);
		} else {
			throw new Exception('No login nonce was set. Please call generateLoginNonce() before displaying login form.');
		}
		return Security::hash($nonce.$password);
	}
	
}

?>
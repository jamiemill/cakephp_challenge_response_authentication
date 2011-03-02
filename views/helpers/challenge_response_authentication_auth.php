<?php

class ChallengeResponseAuthenticationAuthHelper extends AppHelper {
	
	var $helpers = array('Session');
	
	function getLoginNonce() {
		return $this->Session->read(ChallengeResponseAuthenticationAuthComponent::$nonceSessionKey);
	}
	
}

?>
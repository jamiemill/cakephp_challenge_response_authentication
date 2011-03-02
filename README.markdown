ChallengeResponseAuthentication Plugin:
A plugin containing a replacement for cake's AuthComponent that provides javascript challenge-response password authentication.


What does it do?
================

- For login, it uses javascript to hash the user's password with a pseudo-nonce (the "challenge" from the server, based on a random string and the current time) and sends the hashed password instead of the plaintext password for the server to compare with the same process.

Why would you use this?
======================

- The only reason to use this is avoid passwords being sent in plain text over the internet
- If for some reason you can't implement SSL this may be slightly useful, but you probably have other problems (cookie sniffing and hijacking for instance)
- If you have SSL then this can provide a small extra layer of protection in the case that the user was tricked by a spoofed certificate, because the user never sends the actual password.

Disadvantages
=============

- The password must still be sent in the clear for registration and password reset requests so the server can update its records
- Your application's security salt will be revealed to all visitors who see the login form, as this is required for the hashing to be done client-side
- Automatic password hashing in the controller is disabled, so to save new passwords you must manually hash it first.

Usage
=====

Replace cake's Auth component in your AppController::$components array:

	var $components = array('ChallengeResponseAuthentication.ChallengeResponseAuthenticationAuth');

Add the helper (necessary for any view that generates a login form):

	var $helpers = array('ChallengeResponseAuthentication.ChallengeResponseAuthenticationAuth');


Call generateLoginNonce() in the controller of your login page. If every page on your site might display a login box in the header, you can do this in AppController::beforeRender() like so:

	function beforeRender() {
		$this->ChallengeResponseAuthenticationAuth->generateLoginNonce();
	}
	
This is what your login form should look like:

	<?php echo $form->input('username'); ?> 
	
	<?php echo $form->input('password', array(
		'data-nonce'=>$challengeResponseAuthenticationAuth->getLoginNonce(),
		'data-salt'=>Configure::read('Security.salt'),
		'class'=>'login-password'
	)); ?>
	
Include the following two javascript files in your layouts:

	<?php echo $html->script('/challenge_response_authentication/js/sha1'); ?> 
	<?php echo $html->script('/challenge_response_authentication/js/jquery.challenge-response-authentication'); ?>
	

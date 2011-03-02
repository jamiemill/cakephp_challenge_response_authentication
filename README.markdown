ChallengeResponseAuthentication Plugin
======================================
A plugin containing a replacement for cake's AuthComponent that provides javascript challenge-response password authentication.

Compatibility
-------------

- CakePHP 1.3


What does it do?
----------------

- It generates a pseudo-nonce on the server-side (the "challenge"), based on a random string and the current time).
- When the user submits a login form, it uses javascript to:
 	- construct a salted hash of the password in the same way that the server normally stores passwords in the database.
	- hash the result again after concatenating with the one-use nonce.
	- send this value instead of the plaintext password to the server.
- The server performs the same process with the same one-off nonce, and compares the results to see if they match.

References
----------

- [http://pajhome.org.uk/crypt/md5/auth.html](http://pajhome.org.uk/crypt/md5/auth.html)

Why would you use this?
-----------------------

- If you've been told to by a client!
- The only real reason to use this is avoid passwords being sent in plain text over the internet.
- If for some reason you can't implement SSL this may be a compromising alternative, but you probably have other problems (cookie sniffing and hijacking for instance).
- If you have SSL then this can provide a small extra layer of protection in case the user was tricked by a spoofed certificate sent by a proxy, because the user never sends the actual password, and the value sent is usable only once.

Disadvantages
-------------

- The password must still be sent in the clear for registration and password reset requests so the server can update its records.
- Your application's security salt will be revealed to all visitors who see the login form, as this is required for the hashing to be done client-side.
- Automatic password hashing in the controller is disabled, so to save new passwords you must manually hash it first.
- Be careful about ajax requests or missing asset requests triggering new nonces to be generated in the background

Usage
-----

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
	
Include the following two javascript files on any page that renders a login form:

	<?php echo $html->script('/challenge_response_authentication/js/sha1'); ?> 
	<?php echo $html->script('/challenge_response_authentication/js/jquery.challenge-response-authentication'); ?>
	

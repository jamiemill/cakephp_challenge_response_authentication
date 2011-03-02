<?php

if(!App::import('Component', array('ChallengeResponseAuthentication.ChallengeResponseAuthenticationAuth'))) {
	exit('Could not load component');
}

class TestChallengeResponseAuthenticationAuthComponent extends ChallengeResponseAuthenticationAuthComponent {

	var $testStop = false;

	var $_loggedIn = true;

	function _stop() {
		$this->testStop = true;
	}
}

class ChallengeResponseAuthenticationAuthUser extends CakeTestModel {
	var $name = 'ChallengeResponseAuthenticationAuthUser';
	var $useTable = 'auth_users';
	var $useDbConfig = 'test_suite';
}


class TestChallengeResponseAuthenticationController extends Controller {

	var $name = 'AuthTest';
	var $uses = array('ChallengeResponseAuthenticationAuthUser');
	var $components = array('Session', 'TestChallengeResponseAuthenticationAuth', 'Acl');
	var $testUrl = null;
	
	function __construct() {
		$this->params = Router::parse('/auth_test');
		Router::setRequestInfo(array($this->params, array('base' => null, 'here' => '/auth_test', 'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array())));
		parent::__construct();
	}
	
	function beforeFilter() {
		$this->TestChallengeResponseAuthenticationAuth->userModel = 'ChallengeResponseAuthenticationAuthUser';
	}
	
	function login() {
	}
	
	function admin_login() {
	}
	
	function logout() {
		// $this->redirect($this->TestChallengeResponseAuthenticationAuth->logout());
	}
	
	function redirect($url, $status = null, $exit = true) {
		$this->testUrl = Router::url($url);
		return false;
	}

	function add() {
		echo "add";
	}

	function isAuthorized() {
		if (isset($this->params['testControllerAuth'])) {
			return false;
		}
		return true;
	}

	function delete($id = null) {
		if ($this->TestAuth->testStop !== true && $id !== null) {
			echo 'Deleted Record: ' . var_export($id, true);
		}
	}
}


class ChallengeResponseAuthenticationAuthTest extends CakeTestCase {

	var $name = 'ChallengeResponseAuthenticationAuth';

	var $fixtures = array('core.uuid', 'core.auth_user', 'core.auth_user_custom_field', 'core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action');

	var $initialized = false;

	function startTest() {
		$this->_server = $_SERVER;
		$this->_env = $_ENV;

		$this->_securitySalt = Configure::read('Security.salt');
		Configure::write('Security.salt', 'JfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');

		$this->_acl = Configure::read('Acl');
		Configure::write('Acl.database', 'test_suite');
		Configure::write('Acl.classname', 'DbAcl');

		$this->Controller =& new TestChallengeResponseAuthenticationController();
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->beforeFilter();

		ClassRegistry::addObject('view', new View($this->Controller));

		$this->Controller->Session->delete('Auth');
		$this->Controller->Session->delete('Message.auth');

		Router::reload();

		$this->initialized = true;
	}

	function endTest() {
		$_SERVER = $this->_server;
		$_ENV = $this->_env;
		Configure::write('Acl', $this->_acl);
		Configure::write('Security.salt', $this->_securitySalt);

		$this->Controller->Session->delete('Auth');
		$this->Controller->Session->delete('Message.auth');
		ClassRegistry::flush();
		unset($this->Controller, $this->ChallengeResponseAuthenticationAuthUser);
	}

	function testNoAuth() {
		$this->assertFalse($this->Controller->TestChallengeResponseAuthenticationAuth->isAuthorized());
	}
	
	function _setUpForLogin() {
		
		$this->Controller->Session->delete('Auth');
				
		// Update the test user record (copied from core tests. why? just so we demonstrate the correct password? or perhaps so hashing algorithm choice always matches?)
		$this->ChallengeResponseAuthenticationAuthUser =& new ChallengeResponseAuthenticationAuthUser();
		$user['id'] = 1;
		$user['username'] = 'mariano';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$this->ChallengeResponseAuthenticationAuthUser->save($user, false);
		
		// make component believe this is a login action
		$this->Controller->params = Router::parse('auth_test/login');
		$this->Controller->params['url']['url'] = 'auth_test/login';
		$this->Controller->TestChallengeResponseAuthenticationAuth->loginAction = 'auth_test/login';
		$this->Controller->TestChallengeResponseAuthenticationAuth->userModel = 'ChallengeResponseAuthenticationAuthUser';

		$this->Controller->TestChallengeResponseAuthenticationAuth->initialize($this->Controller);
	}
	
	function testNeedsInitialisation() {
		$this->_setUpForLogin();
		$authUser = $this->ChallengeResponseAuthenticationAuthUser->find();
		
		// set submitted data
		$this->Controller->data['ChallengeResponseAuthenticationAuthUser']['username'] = $authUser['ChallengeResponseAuthenticationAuthUser']['username'];
		$this->Controller->data['ChallengeResponseAuthenticationAuthUser']['password'] = 'cake';
		
		// run the component startup to make things happen
		// Expect an exception because we didn't call generateLoginNonce();
		$this->expectException(); // TODO: test type and message
		$this->Controller->TestChallengeResponseAuthenticationAuth->startup($this->Controller);
	
	}
	
	function testLogin() {
		$this->_setUpForLogin();
		$authUser = $this->ChallengeResponseAuthenticationAuthUser->find();
		
		// try again after correctly calling generateLoginNonce()
		$this->Controller->TestChallengeResponseAuthenticationAuth->generateLoginNonce();
		
		// set submitted data with plain text password
		$this->Controller->data['ChallengeResponseAuthenticationAuthUser']['username'] = $authUser['ChallengeResponseAuthenticationAuthUser']['username'];
		$this->Controller->data['ChallengeResponseAuthenticationAuthUser']['password'] = 'cake';

		// run the component startup to make things happen
		$this->Controller->TestChallengeResponseAuthenticationAuth->startup($this->Controller);
		
		// Check the logged in user is NULL because the wrong password was set.
		$user = $this->Controller->TestChallengeResponseAuthenticationAuth->user();
		$this->assertNull($user);
		
		// TODO - check nonce is no longer valid by trying again without calling generateLoginNonce();
		
		
		// get a new nonce and set submitted data with properly nonced hashed password
		$this->Controller->TestChallengeResponseAuthenticationAuth->generateLoginNonce();
		$this->Controller->data['ChallengeResponseAuthenticationAuthUser']['username'] = $authUser['ChallengeResponseAuthenticationAuthUser']['username'];
		
		$password = $this->_performClientSidePasswordNoncingAndReHashing('cake');
		$this->Controller->data['ChallengeResponseAuthenticationAuthUser']['password'] = $password;

		// run the component startup to make things happen
		$this->Controller->TestChallengeResponseAuthenticationAuth->startup($this->Controller);
		
		// Check the logged in user
		$user = $this->Controller->TestChallengeResponseAuthenticationAuth->user();
		$expected = array('ChallengeResponseAuthenticationAuthUser' => array(
			'id' => 1, 'username' => 'mariano', 'created' => '2007-03-17 01:16:23', 'updated' => date('Y-m-d H:i:s')
		));
		$this->assertEqual($user, $expected);

	}
	
	function _performClientSidePasswordNoncingAndReHashing($password) {
		$hashedPassword = Security::hash(Configure::read('Security.salt') . $password);
		$nonce = $this->Controller->Session->read('ChallengeResponseAuthentication.currentLoginNonce');
		$hashedNoncedHashedSaltedPassword = sha1($nonce.$hashedPassword);
		return $hashedNoncedHashedSaltedPassword;
	}

}

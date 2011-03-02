(function($) {
	
	$.fn.challengeResponsePassword = function(options){
		settings = $.extend({
			hiddenHashedPasswordFieldName: 'data[User][password_hashed]',
			initialisedClass: 'challenge-response-initialised'
		},options);
		
		return $(this).each(function(){
			
			var $passwordField = $(this);
			if($passwordField.hasClass(settings.initialisedClass)) {
				return;
			} else {
				$passwordField.addClass(settings.initialisedClass);
			}
			var nonce = $passwordField.attr('data-nonce');
			var salt = $passwordField.attr('data-salt');
			
	
			$passwordField.closest('form').submit(function(){
				hashPasswordField();
				return true;
			});
			
			function hashPasswordField() {
				var plainPassword = $passwordField.val();
				$passwordField.val(hashPassword(plainPassword));
			}
			
			function hashPassword(plainPassword) {
				if(typeof hex_sha1 != 'function') {
					alert('SHA1 script is not loaded.');
					return false;
				}
				return hex_sha1(nonce+(hex_sha1(salt+plainPassword)));
			}
			
		});
		
	}
	
})(jQuery);
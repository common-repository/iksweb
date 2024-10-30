/*
* Author URI: https://iksweb.ru/
* Author: Сергей Князев
*/
(function($){
	$('#loginform').prepend('<p class="login-popup-title">Авторизация</p>');
    $('#loginform .forgetmenot').append('<a href="/wp-login.php?action=lostpassword" class="reserch">Забыли свой пароль?</a>');
    $('label[for="user_login"]').html('Логин');
    $('#login').append('<div class="footer-iksweb"><div class="iksweb"><p>© IKSWEB, 2022</p><p><a href="//iksweb.ru/plugins/support/" class="support" target="_blank">Техподдержка</a></p></div></div>');
	setTimeout(function(){$('.button').removeAttr('disabled');},200);
		$('.language-switcher').remove();
})(jQuery);
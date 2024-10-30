<?php
/*
Author: IKSWEB
Author URI: https://iksweb.ru/
Author Email: info@iksweb.ru
Description: Компонент модуля IKSWEB необходим для подключения капчи к сайту.
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

if ( !class_exists( 'IKSWEB_RECAPTHA' ) ) {

class IKSWEB_RECAPTHA {
		
	/** @var array The plugin settings array */
	var $settings = array();

	function __construct(){}
	
		/*
		* Запуск компонента
		*/		
		function init()
		{
			
		$SETTINGS_NAME = 'CAPTHA_SETTINGS';
		
		$arSettings = get_option($SETTINGS_NAME); // Получаем сохранённые данные
		
		// Формируем массив настроек плагина
		$arParams = $this->settings = array(
			'ACTIVE'		=>		isset( $arSettings['ACTIVE'] ) ?  'Y' : 'N',
			'DISPLAY_COMMENT'=> 	!empty( $arSettings['DISPLAY_COMMENT'] ) ?  'Y' : 'N',
			'SLUG'			=>		'iks-captha',
			'SETTINGS_NAME' =>		$SETTINGS_NAME,
			'VERSION'		=>		!empty( $arSettings['VERSION'])? $arSettings['VERSION'] : 'v2',
			'THEME' 		=>		!empty( $arSettings['THEME'])? $arSettings['THEME'] : '',
			'SIZE' 			=>		!empty( $arSettings['SIZE'])? $arSettings['SIZE'] : '',
			'POSITION' 		=>	!empty( $arSettings['POSITION'])? $arSettings['POSITION'] : '',
			'SITE_KEY'		=>	!empty( $arSettings['SITE_KEY'])? $arSettings['SITE_KEY'] : '',
			'SECRET_KEY' 	=>	!empty( $arSettings['SECRET_KEY'])? $arSettings['SECRET_KEY'] : '',
			'WITE_LIST'		=>	!empty( $arSettings['WITE_LIST'])? $arSettings['WITE_LIST'] : '',
			);
	
			add_action( 'admin_init', array( $this, 'RegisterSettings' ));
			add_action( 'admin_enqueue_scripts' , array( $this , 'ShowHeadScripts' ) );

			if ($arParams['ACTIVE']=='Y') {
				
				if ($this->FilterKey($arParams['SITE_KEY']) && $this->FilterKey($arParams['SECRET_KEY'])) {

					add_action( 'login_enqueue_scripts' ,array( $this , 'ShowHeadScripts' ) );
		
					/* Логика отображения формы */
					add_action('login_form',array( $this, 'ShowComments'));
					add_action('register_form',array( $this, 'ShowComments'), 99);
					add_action('signup_extra_fields',array( $this, 'ShowComments'), 99);
					add_action('lostpassword_form',array( $this , 'ShowComments'));

					/* если массив находится в белом списке, не подключайте капчу */
					if ( !$this->ChekWiteList()) {
						add_filter('registration_errors',array( $this , 'SetUserAuth'), 10, 3);
						add_action('lostpassword_post',array( $this , 'SetUserAuth'), 10, 1);
						add_filter('authenticate', array( $this , 'SetUserAuth'), 30, 3);
					}
					
					if ($arParams['DISPLAY_COMMENT']=='Y') {
						add_action( 'comment_form_after_fields', array( $this , 'ShowComments') );
						add_action( 'pre_comment_on_post', array( $this , 'GetCommentsProcessed'));
					}
					
				} else {
		
				add_action('admin_notices', function(){
					echo '<div class="notice notice-error is-dismissible"><p>Вы указали некорректные ключи <a href="/wp-admin/admin.php?page=iks-captha">нажмите сюда</a> что бы задать правильные ключи.</p></div>';
				});
			
			}
		}
	}
	
	/*
	* Регестрируем настройки
	*/
	function RegisterSettings()
	{

	$arParams = $this->settings;

	/* настраиваемые пользователем значения */
	add_option( $arParams['SETTINGS_NAME'] , '');

	/* настраиваемая пользователем проверка значений общедоступных статических функций */
	register_setting($arParams['SETTINGS_NAME'], $arParams['SETTINGS_NAME'] , array( $this , 'CheckSettings' ));

	}
	
	/*
	 * Проверка правильности вводимых полей
	*/
	function CheckSettings($input)
	{
		foreach($input as $k => $v) {
			$valid_input[$k] = sanitize_text_field($v);
		}
		return $valid_input;
	}

	/*
	 * Фильтры результата капчи
	*/
	function FilterString( $string )
	{
			return sanitize_text_field($string);
	}
	
	/*
	 * Фильтры ключа
	*/
	function FilterKey( $string )
	{
		if (strlen($string) === 40) {
				return true;
		} else {
				return false;
		}
	}
	    
	/*
	 * Получаем IP текущего пользователя
	*/
	private function GetIP() {
		return $this->FilterString($_SERVER['REMOTE_ADDR']);
	}
	    
	/*
	* Проверяем залогинен ли пользователь
	*/
	function CheckUserLogin()
	{
		
		require ABSPATH . WPINC . '/pluggable.php';
	
	if(is_user_logged_in()){
		return true;
	}

		return false;
	}
	    
	/*
	 * Получаем список белых IP
	*/
	function ChekWiteList()
	{

	$arParams = $this->settings;
	
			if ($arWite = $arParams['WITE_LIST']) {
					$whitelist = explode("\r\n", trim($arWite));
			} else {
					$whitelist = array();
			}

			/* get ip address */
			$ip = $this->GetIP();

			if ( !empty($ip) && !empty($whitelist) && in_array($ip, $whitelist) ) {
					return true;
			} else {
					return false;
			}

	}
	    
	/*
	* Подключаем JS API
	*/
	public function ShowHeadScripts()
	{
		$arParams = $this->settings;
		
		if($arParams['VERSION']=='v2' || empty($arParams['VERSION'])){
			wp_enqueue_script('login_nocaptcha_google_api', 'https://www.google.com/recaptcha/api.js?hl='.strtok(get_locale(), '_'), array(), null , true);
		}else{
			wp_enqueue_script('login_nocaptcha_google_api', 'https://www.google.com/recaptcha/api.js?render='.$arParams['SITE_KEY'].'&onload=onloadCallback&hl='.strtok(get_locale(), '_'), array(), null , true);
		}
	}
	    
	/*
	* Выводим капчу
	*/
	function ShowReCaptha()
	{

	$arParams = $this->settings;

	$this->ShowHeadScripts();
	
		if (!$this->ChekWiteList() && $arParams['ACTIVE']=='Y') {
			if($arParams['VERSION']=='v2' || empty($arParams['VERSION'])){
				return '<div class="g-recaptcha" id="g-recaptcha" data-theme="'.$arParams['THEME'].'" data-size="'.$arParams['SIZE'].'" data-sitekey="'.$arParams['SITE_KEY'].'"  data-badge="'.$arParams['POSITION'].'" data-callback="submitEnable" data-expired-callback="submitDisable"></div>';
			}else{
				return '
				<input type="hidden" id="g-recaptcha" name="g-recaptcha-response"/>
				<script type="text/javascript"> 
					var onloadCallback = function() {
						grecaptcha.ready(function() { 
							grecaptcha.execute("'.$arParams['SITE_KEY'].'", {action: "submit"}).then(function(token) { 
								document.getElementById("g-recaptcha").value = token; 
							}); 
						});
					}
				</script>';
			}
		}
	}
	    
	/*
	* Выводим рекапчу в форму комментариев
	*/
	public function ShowComments()
	{
		$arParams = $this->settings;

		$this->ShowHeadScripts();

		if (!$this->ChekWiteList() && $arParams['ACTIVE']=='Y' && $this->CheckUserLogin()==false) {

			if($arParams['VERSION']=='v2' || empty($arParams['VERSION'])){
				echo '<div class="g-recaptcha" id="g-recaptcha" data-theme="'.$arParams['THEME'].'" data-size="'.$arParams['SIZE'].'" data-sitekey="'.$arParams['SITE_KEY'].'"  data-badge="'.$arParams['POSITION'].'" data-callback="submitEnable" data-expired-callback="submitDisable"></div>';
			}else{
				echo '
				<input type="hidden" id="g-recaptcha" name="g-recaptcha-response"/>
				<script type="text/javascript"> 
					var onloadCallback = function() {
						grecaptcha.ready(function() { 
							grecaptcha.execute("'.$arParams['SITE_KEY'].'", {action: "submit"}).then(function(token) { 
								document.getElementById("g-recaptcha").value = token; 
							}); 
						});
					}
				</script>';
			}
		}      
	}
	    
	/*
	* Выводим рекапчу в форму комментариев
	*/
	public function GetCommentsProcessed($commentdata)
	{
	
		global $commentdata , $_REQUEST;
		
		// Нет смысла делать проверку без комментария
		if (!empty($_REQUEST['comment'])) {
			
			// Отключаем для пользователей и WiteList
			if (!$this->ChekWiteList() && $this->CheckUserLogin()==false) {
			
				if (!empty($_REQUEST['g-recaptcha-response'])) {
				
					if ($this->CheckCaptha()!==true) {
						
						// Отдаём разные ответы для AJAX и без
						if( wp_doing_ajax() ){	
							echo 'Вы не подтвердили что являетесь человеком в Recaptcha.';
						}else{
							wp_die('Ошибка: Вы не подтвердили что являетесь человеком в Recaptcha.');
						}
					}
				
				} else {
				
					// Отдаём разные ответы для AJAX и без
					if( wp_doing_ajax() ){	
						echo 'Вы не подтвердили что являетесь человеком в Recaptcha.';
					}else{
						wp_die('Ошибка: Вы не подтвердили что являетесь человеком в Recaptcha.');
					}
					
				}
			}
		}	
		
		return $commentdata;
	}

	/*
	* Выводим страницу настроек
	*/
	public function ShowPageCaptha()
	{

	$arParams = $this->settings;
		?>
		<div class="adm-detail-content-wrap active">  
			<form method="post" action="options.php">
				<?php
				$s_name = $arParams['SETTINGS_NAME'];
				settings_fields($arParams['SETTINGS_NAME']); ?>
				<div class="adm-detail-content">
					<div class="adm-detail-title">Параметры reCaptcha</div>
						<div class="adm-detail-content-item-block">
							<h4>Регистрация домена и получение ключей <a href="https://www.google.com/recaptcha/admin#list" target="_blank">www.google.com</a> </h4>
							
							<table class="adm-detail-content-table edit-table">
								<tbody>
									<tr>
										<td class="adm-detail-content-cell-l">
											<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Позволяет включать и отключать работу отдельных компонентов.">
												<span class="type-3"></span>
											</div>
											Активность   
										
									</td>
										<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[ACTIVE]" type="checkbox" <?php if($arParams['ACTIVE']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox"></td>
									</tr>
									<tr>
										<td class="adm-detail-content-cell-l">
											<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Если включить эту опцию, то появится возможность подключать reCaptha к форме комментариев.">
												<span class="type-3"></span>
											</div>
											Активировать для комментариев
										
										</td>
										<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[DISPLAY_COMMENT]" type="checkbox" <?php if($arParams['DISPLAY_COMMENT']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox"></td>
									</tr>
									
									<tr>
										<td class="adm-detail-content-cell-l">
											<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Опция позволяет изменять версию рекапчи на сайте.">
												<span class="type-3"></span>
											</div>
											Версия рекапчи
										</td>
										<td class="adm-detail-content-cell-r">
											<select name="<?php echo $s_name?>[VERSION]">
												<option value="v2" <?php if(!empty($arParams['VERSION']) && $arParams['VERSION']=='v2'){?>selected<?php } ?>>reCaptcha v2</option>
												<option value="v3" <?php if(!empty($arParams['VERSION']) && $arParams['VERSION']=='v3'){?>selected<?php } ?>>reCaptcha v3</option>
											</select>
											
											<?php if(!empty($arParams['VERSION']) && $arParams['VERSION']=='v3'){?>
											<style>
												.no-display-v3{display:none;}
											</style>
											<?php }?>
										</td>
									</tr>
									
									<tr>
										<td class="adm-detail-content-cell-l">
											<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите в поле открытый ключ Googl для капчи.">
												<span class="type-1"></span>
											</div>
											Ключ
										</td>
										<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[SITE_KEY]" type="text" size="45" value="<?php echo $arParams['SITE_KEY'];?>"></td>
									</tr>
									<tr>
										<td class="adm-detail-content-cell-l">
											<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите в поле секретный ключ Googl для капчи.">
												<span class="type-1"></span>
											</div>
											Секретный ключ
										</td>
										<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[SECRET_KEY]" type="text" size="45" value="<?php echo $arParams['SECRET_KEY'];?>"></td>
									</tr>
									<tr class="no-display-v3">
										<td class="adm-detail-content-cell-l">
											<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Опция зименит внешний вид капчи.">
												<span class="type-3"></span>
											</div>
											Тема
										</td>
										<td class="adm-detail-content-cell-r">
											<select name="<?php echo $s_name?>[THEME]">
												<option value="light" <?php if($arParams['THEME']=='light'){?>selected<?php } ?>>Светлая</option>
												<option value="dark" <?php if($arParams['THEME']=='dark'){?>selected<?php } ?>>Темная</option>
											</select>
										</td>
									</tr>
											<tr class="no-display-v3">
										<td class="adm-detail-content-cell-l">
											<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Опция зименит внешний вид капчи.">
												<span class="type-3"></span>
											</div>
											Размер
										</td>
										<td class="adm-detail-content-cell-r">
											<select id="el_size_s1" name="<?php echo $s_name?>[SIZE]">
												<option value="normal" <?php if($arParams['SIZE']=='normal'){?>selected<?php } ?>>Нормальный</option>
												<option value="compact" <?php if($arParams['SIZE']=='compact'){?>selected<?php } ?>>Компактный</option>
											</select>
										</td>
									</tr>
									<tr>
										<td class="adm-detail-content-cell-l">
											<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Вы можете указать список IP, для которых не будет показываться капча.">
												<span class="type-3"></span>
											</div>
											Белый лист IP адресов
										</td>
										<td class="adm-detail-content-cell-r">
												<textarea name="<?php echo $s_name?>[WITE_LIST]" style="width:100%"><?php echo $arParams['WITE_LIST'];?></textarea>
												<span class="description">Каждый IP с новой строки.</span>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="adm-detail-content-btns">
								<input type="submit" name="submit" class="iksweb-btn" value="Сохранить">
							</div> 
					</div>
				</form>
			</div>
		</div>
		<br><br>
		<?php if(strlen($arParams['SITE_KEY']) > 0 && strlen($arParams['SECRET_KEY']) > 0){?>
		<div class="tabs"> 
			<ul class="adm-detail-tabs-block">
				<li class="adm-detail-tab active" data-id="1">Пример</li>
				<li class="adm-detail-tab" data-id="2">Подключение</li>
			</ul>
			<div class="adm-detail-content-wrap active">
				<div class="adm-detail-content">
					<div class="adm-detail-content-item-block">
							<?php echo ShowReCaptha();?>
							<h3>Ваши следующие шаги:</h3>
							<ol>
									<li>Если Вы видите сообщение об ошибке выше, проверьте правильность ввода ключей.</li>
									<li>Если вы включи рекапчу v3 и она не выводится справка правильность ввода ключей или включите v2.</li>
									<li>Если reCaptcha выше (или справа) отображается правильно, сделайте следующее:</li>
									<ol>
											<li>Откройте браузер в режим инкогнито Ctrl+Shift+N</li>
											<li>Попытайтесь войти на сайт</li>
											<li>Если ошибка о заполнение капчи не исчезает приеё заполнение, вернитесь к 1 пункту</li>
									</ol>
									<li><b style="color: #ef0000;">ВНИМАНИЕ!</b> <b>Не</b> закрывайте это окно и <b>не</b> выходите из сайта из текущего браузера до тех пор, пока Вы не убедитесь, что reCaptcha работает корректно и Вы можете войти на сайт снова.</li>
									<li>Если Вы испытываете проблемы со входом на сайт, нажмите удалить ключи или отключить комопнент (галочка Активность выше).</li>
							</ol>
					 </div> 
				</div> 	 
			</div> 
			<div class="adm-detail-content-wrap">   
				<div class="adm-detail-content">
					<div class="adm-detail-content-item-block">
						<p>При активации капча автоматически добавляется на поля авторизации и регистрации <b>wp-login.php</b>, а также встраивается в стандартную форму вывода комментариев <b>comment_form()</b>. Для использование капчи в <b>нестандартных формах комментариев</b> и прочих формах используйте подключения:</p>
						<h3>Шоткодами</h3>
						<p><b>[RECAPTHA]</b> - Используется для вывода капчи в контент.</p>
						
						<h3>Использование в шаблонах (PHP)</h3>
						<p><b>ShowReCaptha()</b>; - аналог [RECAPTHA].</p>
						
						<h3>Проверка результатов капчи</h3>
						<p>Для отдельных компонентов используйте функцию <b>CheckCaptha();</b> Возвращает в случае успеха true.</p>
						<p>Функция проверит POST и GET запросы на наличие массива <b>["g-recaptcha-response"]</b> и отправит запрос в Google для проверки кода.</p>
					</div> 
				</div> 	
			</div> 
		</div> 
		<?php } ?>
	</div>	
	<?php
	}
	    
	    
	/*
	* Функция для проверки результата капчи в отдельных обработчиках
	*/
	public function CheckCaptha()
	{
		
		global $_REQUEST;

		$arParams = $this->settings;
		
		// Если есть в белом листе, то не надо проверять
		if($this->ChekWiteList()){
			return true;
		}
			
		if (isset($_REQUEST['g-recaptcha-response'])) {
				
				/*
				* Очищенная информация из капчи
				*/
				$CapthaParams = array(
					'secret'   => $arParams['SECRET_KEY'], 
					'response' => sanitize_text_field($this->FilterString($_REQUEST['g-recaptcha-response'])),
					'remoteip' => $this->GetIP()
				);
				
				/*
				* Параметры запроса
				*/
				$PostParams = array(
					'timeout'     => 5,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'		  => $CapthaParams,
					'cookies'     => array()
				);
					
				/*
				* Отправляем запрос
				*/	
				$response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', $PostParams ); 
				
				if ( !is_wp_error( $response ) ) {
					$CapthaResult = json_decode($response['body']);
				}

				if($arParams['VERSION']=='v2' || empty($arParams['VERSION'])){
					if($CapthaResult->success == true)
						return true;
				}else{
					if($CapthaResult->success == true && $CapthaResult->score > 0.5)
						return true;
				}
			
			}
		
		return false;
	}
	    
	/*
	* Основная логика компонента
	*/
	public function SetUserAuth($user_success)
	{

		$arParams = $this->settings;
		if ( $this->CheckCaptha() == true ) {
				return $user_success;	
		} else {	
			if (is_wp_error($user_success)) {
				$user_success->add('invalid_captcha', '<b>ОШИБКА</b>: Неверная reCaptha, пожалуйста, попробуйте еще раз.');
				return $user_success;
			} else {
				return new WP_Error('authentication_failed','<b>ОШИБКА</b>: Неверная reCaptha, пожалуйста, попробуйте еще раз.');
			}	
		}	
		
	}
	    
	    
	/******************** END *****************/
}
	
	// globals
	global $IKSWEB_RECAPTHA;
	
	// initialize
	if(isset($APPLICATION) && !isset($IKSWEB_RECAPTHA)) {
		$IKSWEB_RECAPTHA = new IKSWEB_RECAPTHA();
		$IKSWEB_RECAPTHA->init();
	}
	
	/*
	* Обработчик результатов капчи для доп плагинов и шоткодов
	*/
	if (!function_exists('CheckCaptha')) {
		function CheckCaptha(){		
			global $IKSWEB_RECAPTHA;
			return $IKSWEB_RECAPTHA->CheckCaptha();
		}
	}
	
	/*
	* Выводим капчу на страницу для доп плагинов и шоткодов
	*/
	if (!function_exists('ShowReCaptha')) {
		function ShowReCaptha(){
			
			global $IKSWEB_RECAPTHA;
			
			$IKSWEB_RECAPTHA->ShowHeadScripts();

			return $IKSWEB_RECAPTHA->ShowReCaptha();
		}
	}
	
	/*
	* Шорткоды
	*/
	add_shortcode('RECAPTHA', 'ShowReCaptha');
}
?>
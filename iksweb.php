<?php
/*
 * Plugin Name: WordPress Старт
 * Plugin URI: https://plugin.iksweb.ru/wordpress/wordpress-start/
 * Description: Комплексный компонент для удобной стартовой настройки сайта на WordPress. С помощью данного плагина вы сможете изменить вид панели администратора и настроить ваш сайт на приём и отправку сообщений.
 * Author: IKSWEB
 * Author URI: https://plugin.iksweb.ru/wordpress/wordpress-start/
 * Copyright: IKSWEB
 * Version: 3.8
 * Tags: iksstart, seo, recaptha, design panel, forms, url transliteracia, iksweb
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

if ( ! class_exists( 'IKSWEB' ) ) {
	
	define('IKS_DOCUMENT_ROOT', plugin_dir_path( __FILE__ ));
	
	class IKSWEB {
		
		/** @var string Версия плагина */
		var $version = '3.8';
		
		/** @var array Настройки плагина и компонентов */
		var $settings = array();
		
		function __construct(){}
		
		/*
		* Запуск компонента
		*/
		function init()
		{
			// global
			global $IKSWEB;
			
			$IKSWEB[]=array(
				'PLUGIN'=>'iksweb',
				);
			
			$installation	= plugin_dir_path( __DIR__ );
			$email			= get_bloginfo('admin_email');
			$path			= plugin_dir_path( __FILE__ );
			
			$SITE_SETTINGS = 'SITE_SETTINGS';
			$arSettings		= get_option($SITE_SETTINGS);
			
			$this->settings = array(
				
				// site
				'SITE'=>array(
					'WP_VERSIA'		=>		get_bloginfo('version'),
					'EMAIL'				=>		$email,
					'CHARSET'			=>		get_bloginfo('charset'),
					),
				
				// basic plugin
				'PLUGIN'=>array(
					
					// Defaut
					'SETTINGS_NAME' =>		$SITE_SETTINGS,
					'SLUG'			=>		'iksweb',
					'NAME'			=>		'IKSWEB',
					'TITLE'			=>		'Настройки плагинов | IKSWEB',
					'VERSIA'		=>		$this->version,

					// Urls
					'HOST'			=>		$_SERVER['HTTP_HOST'],
					'BASENAME'	=>		plugin_basename( __FILE__ ),
					'URL'				=>		plugin_dir_url( __FILE__ ),
					'PATH'			=>		$path,
					'FILE'			=>		__FILE__,
					
					// Site Params
					'ACTIVE'								=>		!empty( $arSettings['ACTIVE'] ) ?  'Y' : 'N',
					'SITE_DESIGN'						=>		!empty( $arSettings['SITE_DESIGN'] ) ?  'Y' : 'N',
					'DISABLED_XMLRPC'				=>		!empty( $arSettings['DISABLED_XMLRPC'] ) ?  'Y' : 'N',
					'DISABLED_REST_API'			=>		!empty( $arSettings['DISABLED_REST_API'] ) ?  'Y' : 'N',
					'DISABLED_EMOJI'				=>		!empty( $arSettings['DISABLED_EMOJI'] ) ?  'Y' : 'N',
					'DISABLED_DNS_PREFETCH'	=>		!empty( $arSettings['DISABLED_DNS_PREFETCH'] ) ?  'Y' : 'N',
					'DISABLED_RSS_POST'			=>		!empty( $arSettings['DISABLED_RSS_POST'] ) ?  'Y' : 'N',
					'DISABLED_RSS_CATS'			=>		!empty( $arSettings['DISABLED_RSS_CATS'] ) ?  'Y' : 'N',
					'DISABLED_RSS_COMMENTS'	=>		!empty( $arSettings['DISABLED_RSS_COMMENTS'] ) ?  'Y' : 'N',
					'DISABLED_WP_VERSION'		=>		!empty( $arSettings['DISABLED_WP_VERSION'] ) ?  'Y' : 'N',
					'SET_SVG'								=>		!empty( $arSettings['SET_SVG'] ) ?  'Y' : 'N',
					'SET_BIG_IMAGES'				=>		!empty( $arSettings['SET_BIG_IMAGES'] ) ?  'Y' : 'N',
					
					'DISABLED_YOAST_JSON'			=>		!empty( $arSettings['DISABLED_YOAST_JSON'] ) ?  'Y' : 'N',
					'DISABLED_YOAST_CANONICAL'=>		!empty( $arSettings['DISABLED_YOAST_CANONICAL'] ) ?  'Y' : 'N',
					'DISABLED_YOAST_META'			=>		!empty( $arSettings['DISABLED_YOAST_META'] ) ?  'Y' : 'N',
					'SET_PAGE_NUM'						=>		!empty( $arSettings['SET_PAGE_NUM'] ) ?  'Y' : 'N',
					'DISABLED_LIBRARY'				=>		!empty( $arSettings['DISABLED_LIBRARY'] ) ?  'Y' : 'N',
					'HTML_H'									=>		!empty( $arSettings['HTML_H'] ) ?  'Y' : 'N',
					'HTML_P'									=>		!empty( $arSettings['HTML_P'] ) ?  'Y' : 'N',
					'HTML_A'									=>		!empty( $arSettings['HTML_A'] ) ?  'Y' : 'N',
					'REMUVE_DOP_P'						=>		!empty( $arSettings['REMUVE_DOP_P'] ) ?  'Y' : 'N',
					'DELETE_RUBRIKA'					=>		!empty( $arSettings['DELETE_RUBRIKA'] ) ?  'Y' : 'N',
					),
					
				// forms
				'FORMS'=>	array('SLUG'=>'iks-forms'),
					
				// reCaptha
				'CAPTHA'=>	array('SLUG'=>'iks-captha'), 

				// cyrlitera
				'CYRL'=>	array('SLUG'=>'iks-cyrl'),
			);	
			
			$this->settings['PLUGIN']['YOAST'] = 'N';
			
			if(file_exists(WP_CONTENT_DIR.'/plugins/wordpress-seo/wp-seo-main.php'))
				$this->settings['PLUGIN']['YOAST'] = 'Y';
				
			// Получаем параметры
			$arParams = $this->settings['PLUGIN'];
			
			add_action( 'admin_menu' , array( $this , 'RegisterMenu' ) ); 
			add_action( 'admin_init' , array( $this , 'RegisterSettings' ) );
			
			if (!empty($arParams['ACTIVE']) && $arParams['ACTIVE']=='Y') {
				add_action( 'wp_dashboard_setup' , array( $this , 'ShowDashbord' ) ); 
				
				//Выполняем указанные настройки
				add_action( 'admin_menu' , array( $this , 'RemoveMenu'));

				// Меняем вид авторизации
				add_action( 'login_enqueue_scripts' ,  array( $this , 'ShowAHeadScripts' ) );
				
				// Добавляем вывод ID медиафайлов
				add_filter( 'manage_media_columns', array( $this , 'ShowIDMediaColumn' ) );
				add_filter( 'manage_media_custom_column', array( $this , 'ShowIDMediaRow' ), 10, 2 );
				add_filter( 'attachment_fields_to_edit', array( $this , 'ShowIDMediaElement' ) , 10, 2 );
			}
	        
	    //Подключаем файлы к админке
			add_action( 'admin_enqueue_scripts' ,array( $this , 'ShowHeadScripts' ) );
			
			// выполняем действия при активации
			register_activation_hook( __FILE__, array( $this , 'SetDefaultParams' ) );
			
			// Защитить уязвимость XML-RPC
			if (!empty($arParams['DISABLED_XMLRPC']) && $arParams['DISABLED_XMLRPC']=='Y') {
				add_filter( 'xmlrpc_methods', array( $this , 'RemoveXMLRPCmethod' )  );
			}
			
			// Отключить REST API
			if (!empty($arParams['DISABLED_REST_API']) && $arParams['DISABLED_REST_API']=='Y') {
				remove_action('wp_head', 'rest_output_link_wp_head', 10);
				add_filter( 'rest_pre_dispatch', array( $this , 'DisabledRestApi' ), 10, 3 );
			}
			
			// скрыть emoji
			if (!empty($arParams['DISABLED_EMOJI']) && $arParams['DISABLED_EMOJI']=='Y') {
				remove_action('wp_head', 'print_emoji_detection_script', 7);
				remove_action('wp_print_styles', 'print_emoji_styles');
			}
			
			// Удалять dns-prefetch
			if (!empty($arParams['DISABLED_DNS_PREFETCH']) && $arParams['DISABLED_DNS_PREFETCH']=='Y') {
				remove_action('wp_head', 'wp_resource_hints', 2 ); 
			}	
			
			// Отключить RSS канал постов
			if (!empty($arParams['DISABLED_RSS_POST']) && $arParams['DISABLED_RSS_POST']=='Y') {
				remove_action('wp_head','feed_links_extra', 3); 
				remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
				remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			}	
			
			// Отключить RSS канал рубрик
			if (!empty($arParams['DISABLED_RSS_CATS']) && $arParams['DISABLED_RSS_CATS']=='Y') {
				remove_action('wp_head','feed_links', 2);
			}	
			
			// Отключить RSS канал комментариев
			if (!empty($arParams['DISABLED_RSS_COMMENTS']) && $arParams['DISABLED_RSS_COMMENTS']=='Y') {
				remove_action('wp_head','rsd_link'); 
			}	
			
			// Cкрыть версию wordpress
			if (!empty($arParams['DISABLED_WP_VERSION']) && $arParams['DISABLED_WP_VERSION']=='Y') {
				remove_action('wp_head','wlwmanifest_link'); // Windows Live Writer
				remove_action('wp_head','wp_generator'); 
			}
			
			// Разрешить загружать .svg
			if (!empty($arParams['DISABLED_RSS_COMMENTS']) && $arParams['DISABLED_RSS_COMMENTS']=='Y') {
				add_filter( 'upload_mimes', array( $this , 'AllowSVGFiles' ) );
				add_filter( 'wp_check_filetype_and_ext', array( $this , 'fix_AllowSVGFiles' ), 10, 5 );
			}	
			
			if($arParams['YOAST']=='Y'){
			
				// Отключить вывод Json от Yoast SEO
				if (!empty($arParams['DISABLED_YOAST_JSON']) && $arParams['DISABLED_YOAST_JSON']=='Y') {
					add_filter( 'wpseo_schema_needs_article', '__return_false' );
					add_filter( 'wpseo_schema_webpage', '__return_false' );
					add_filter( 'wpseo_schema_website', '__return_false' );
					add_filter( 'wpseo_schema_organization', '__return_false' );
					add_filter( 'wpseo_schema_person', '__return_false' );
					add_filter( 'wpseo_schema_imageobject', '__return_false' );
				}	
				
				// Отключить ненужные meta от Yoast SEO
				if (!empty($arParams['DISABLED_YOAST_META']) && $arParams['DISABLED_YOAST_META']=='Y') {
					add_filter( 'wpseo_robots', array( $this , 'DisabledYoastMeta' ), 10, 2 );
					add_filter( 'wpseo_googlebot', '__return_false' ); // Yoast SEO 14.x or newer
					add_filter( 'wpseo_bingbot', '__return_false' ); // Yoast SEO 14.x or newer
					
					add_filter( 'wp_robots', array( $this , 'DisabledRobotsWP' ) ); // Убираем robots WP
				}
				
				// Отключить вывод Canonical от Yoast SEO
				if (!empty($arParams['DISABLED_YOAST_CANONICAL']) && $arParams['DISABLED_YOAST_CANONICAL']=='Y') {
					add_filter( 'wpseo_canonical', array( $this , 'DisabledCanonicalYoast' ) );
				}
			}
			
			// Отключить загрузку стилей block-library.css
			if (!empty($arParams['DISABLED_LIBRARY']) && $arParams['DISABLED_LIBRARY']=='Y') {
				add_action( 'wp_enqueue_scripts', array( $this , 'DisabledLibraryCSS' ) );
			}
			
			// Очищаем контент от лишних <p> при выводе
			if (!empty($arParams['REMUVE_DOP_P']) && $arParams['REMUVE_DOP_P']=='Y') {
				remove_filter( 'the_content', 'wpautop' );// для контента
				remove_filter( 'the_excerpt', 'wpautop' );// для анонсов
				remove_filter( 'comment_text', 'wpautop' );// для комментарий
			}
			
			//  Добавляем кнопки в текстовый html-редактор
			if ((!empty($arParams['HTML_H']) && $arParams['HTML_H']=='Y') || (!empty($arParams['HTML_P']) && $arParams['HTML_P']=='Y')  || (!empty($arParams['HTML_A']) && $arParams['HTML_A']=='Y') ) {
				add_action( 'admin_print_footer_scripts', array( $this , 'AddRedactorBTN' ) );
			}
			
			//  Добавить | Страница №# на пагинации
			if (!empty($arParams['SET_PAGE_NUM']) && $arParams['SET_PAGE_NUM']=='Y') {
				add_filter('pre_get_document_title', array( $this , 'ShowNumberPagination' ) ,999);
				add_filter( 'wpseo_metadesc', array( $this , 'ShowNumberPagination' ), 10, 2 );
			}
			
			//  Добавить | Страница №# на пагинации
			if (!empty($arParams['SET_BIG_IMAGES']) && $arParams['SET_BIG_IMAGES']=='Y') {
				@ini_set( 'upload_max_size' , '64M' );
				@ini_set( 'post_max_size', '64M');
				@ini_set( 'max_execution_time', '300' );
			}
			
			//  Удаляем слово рубрика
			if (!empty($arParams['DELETE_RUBRIKA']) && $arParams['DELETE_RUBRIKA']=='Y') {
				add_filter( 'get_the_archive_title', array($this,'DeleteRubrika') );
			}
			
		}

		/*
		* Установим дефолтные настройки
		*/
		function SetDefaultParams()
	    {
	    	
	    	global $IKSWEB_FORMS, $IKSWEB_CYRL;
	    	
			/***********************/
			
			$FormsSettingsName = $IKSWEB_FORMS->arParams['SETTINGS_NAME'];
			delete_option( $FormsSettingsName );
			$arSettings['FORMS'] = array( 
				'ACTIVE'		=> 'N',
				'LOGS'  		=> 'Y',
				'FORM_HACH'  	=> md5(date('h d M m Y h:s:i')),
				'RECAPTHA'  	=> 'N',
				'COOKIE'  		=> 'Y',
				'FILES_TYPE'	=> 'jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx,pps,ppsx,odt,xls,xlsx,mp3,m4a,ogg,wav,mp4,m4v,mov,wmv,avi,mpg,ogv,3gp,3g2',
				'MAIL_TEMPLATES' => array(
					'0' => array(
					'NAME'	=> 'Базовый шаблон',
					'TITLE'	=> 'На сайте #SERVER_NAME# заполнили форму #FORM_NAME#',
					'EMAIL'	=> '#DEFAULT_EMAIL_FROM#',
					'REQUIRED_FILEDS'	=> '',
					'MESSAGE'	=> 'Здравствуйте, на сайте #SITE_NAME# заполнили форму #FORM_NAME#
					
Информация из формы:

Имя: #NAME#
Телефон: #PHONE#
E-mail: #EMAIL#
Сообщение: #MESSAGE# 

Страница заполнения: #URL#
Дата и время: #DATE#

Вы можете посмотреть заявку на сайте по ссылке #FORM_RESULT_URL#

===============
Сообщение сгенерировано автоматически.',
					'BODY_TYPE'	=> 'html',
					
					)
				),
				'EROR_0'  		=> 'Спасибо, ваше сообщение отправлено.',
				'EROR_1'  		=> 'Ошибка проверки данных формы. Попробуйте позже.',
				'EROR_2'  		=> 'Запрос отправленный вами похож на спам. Попробуйте позже.',
				'EROR_3'  		=> 'Ошибка при проверке ReCAPTCHA.',
				'EROR_4'  		=> 'Не заполнено одно или несколько обязательных полей.',
				'EROR_5'  		=> 'Произошла ошибка при отправке данных. Попробуйте позже. Или свяжитесь с нами другим способом.',
			);
			add_option( $FormsSettingsName , $arSettings['FORMS']);
			
			
			/***********************/
			
			$CurlSettingsName = $IKSWEB_CYRL->arParams['SETTINGS_NAME'];
			delete_option( $CurlSettingsName );
			$arSettings['CYR'] = array( 
				'SET_CYRL_PAGE' => 'Y',
				'CYRL_FILE'  	=> 'Y',
				'CYRL_REGISTER' => 'Y',
			);
			add_option( $CurlSettingsName , $arSettings['CYR']);
			
	    }

		/*
		* Регистрируем меню
		*/
		function GetSettings( $type, $name=array() ) 
		{

			$arParams = $this->settings;
			
			if( !empty( $name ) ){
				
				return isset($arParams[$type][$name]) ? $arParams[$type][$name] : false;
				
			}
			
			return isset($arParams[$type]) ? $arParams[$type] : false;
		}

		/*
		* Регистрируем меню
		*/
	  function RegisterMenu()
	  {
			global $IKSUPDATE;
		
			$arParams = $this->settings;
		
			add_menu_page( $arParams['PLUGIN']['TITLE'], $arParams['PLUGIN']['NAME'], 'manage_options', $arParams['PLUGIN']['SLUG'] , array( $this , 'ShowPageParams' ), '' , 60 );
		
			add_submenu_page( $arParams['PLUGIN']['SLUG'] , 'Настройки транслитерация URL | IKSWEB', 'Транслитерация URL', 'manage_options', $arParams['CYRL']['SLUG'] , array( $this , 'ShowPageCurl' ));
			add_submenu_page( $arParams['PLUGIN']['SLUG'] , 'Настройка reCaptcha | IKSWEB', 'reCaptcha', 'manage_options', $arParams['CAPTHA']['SLUG'] , array( $this , 'ShowPageCaptha' ));
			add_submenu_page( $arParams['PLUGIN']['SLUG'] , 'Настройка форм | IKSWEB', 'Обработка форм', 'manage_options', $arParams['FORMS']['SLUG'], array( $this , 'ShowPageForms' ));
			
			// Проверка на PRO версию
			if(!$IKSUPDATE){
				add_submenu_page( $arParams['PLUGIN']['SLUG'] , 'PRO версия | IKSWEB', 'PRO версия', 'manage_options', 'iks-pro', array( $this , 'ShowPagePro' ));
			}
		}

		/*
		* Регистрируем настройки
		*/
		function RegisterSettings()
		{
			$arParams = $this->settings['PLUGIN'];

			/* настраиваемые пользователем значения */
	    add_option( $arParams['SETTINGS_NAME'] , '');

			/* настраиваемая пользователем проверка значений общедоступных статических функций */
			register_setting( $arParams['SETTINGS_NAME'], $arParams['SETTINGS_NAME'] , array( $this , 'CheckSettings' ) );
		}
				
		/*
		 * Проверка правильности вводимых полей
		 */
		function CheckSettings($input)
		{
			foreach($input as $k => $v) {
				$valid_input[$k] = trim($v);
			}
			return $valid_input;
		}
		
		/*
		 *  Выводи оповещение об обновление настроек
		 *  notice-success - для успешных операций. Зеленая полоска слева.
		 *	notice-error - для ошибок. Красная полоска слева.
		 *	notice-warning - для предупреждений. Оранжевая полоска слева.
		 *	notice-info - для информации. Синяя полоска слева.
		 */
		function ShowNotices($massage=false, $type='notice-success')
		{
			if($massage!==false){
			?>
			<div class="notice <?php echo $type; ?> is-dismissible">
				<p><?php echo $massage; ?></p>
			</div>
			<?php
			}
		}
		
		/*
		* Отображение страницы параметров
		*/
		public function ShowPageParams()
		{
			$arParams = $this->settings['PLUGIN'];
			
			$this->ShowPageHeader();
			?>
				<div class="tabs"> 
					<ul class="adm-detail-tabs-block">
						<li class="adm-detail-tab active">IKSWEB</li>
					</ul>
					<div class="adm-detail-content-wrap active">
						<form method="post" enctype="multipart/form-data" action="options.php">
							<?php 
					    	$s_name = $arParams['SETTINGS_NAME'];
					    	settings_fields($s_name); ?>
							<div class="adm-detail-content">
								<div class="adm-detail-title">Настройка главного модуля</div>
						
								<div class="adm-detail-content-item-block">
									<table class="adm-detail-content-table edit-table">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Позволяет включать и отключать работу отдельных компонентов.">
													<span class="type-3"></span>
												</div>	
												Активность   
											</td>
												<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[ACTIVE]" type="checkbox" <?php if(!empty($arParams['ACTIVE']) &&  $arParams['ACTIVE']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox"></td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Опция позволит изменить дизайн формы входа в панель управления.">
														<span class="type-1"></span>
													</div>
													Улучшить дизайн входа
												</td>
												<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[SITE_DESIGN]" type="checkbox" <?php if(!empty($arParams['SITE_DESIGN']) &&  $arParams['SITE_DESIGN']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox"></td>
											</tr>
											
											<tr class="heading">
												<td colspan="2">Настройки сайта</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="XML-RPC — это протокол удалённого вызова процедур (RPC), который использует XML для кодирования своих вызовов и HTTP в качестве транспортного механизма.">
													<span class="type-3"></span>
												</div>	
												Защитить уязвимость XML-RPC 
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_XMLRPC]" type="checkbox" <?php if(!empty($arParams['DISABLED_XMLRPC']) && $arParams['DISABLED_XMLRPC']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
													<a href="//iksweb.ru/blog/xmlrpc-wordpress/" target="_blank">Подробнее об уязвимости</a>
												</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="WordPress REST API – это универсальный интегратор любой инсталляции WordPress с любым приложением на веб-сервере или вашей операционной системе.">
													<span class="type-2"></span>
												</div>	
												Отключить REST API
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_REST_API]" type="checkbox" <?php if(!empty($arParams['DISABLED_REST_API']) && $arParams['DISABLED_REST_API']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
													<p class="description"> <b>Внимание!</b> отключайте данный функционал, толькоесли знаете, что делаете. Некоторые плагины используют его в работе.</p>
												</td>
											</tr>

											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="На сайтах на WordPress может автоматически подключаться файл стилей wp-block-library-css. Нужен он для редактора Gutenberg.">
													<span class="type-2"></span>
												</div>	
												Отключить загрузку стилей block-library.css
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_LIBRARY]" type="checkbox" <?php if(!empty($arParams['DISABLED_LIBRARY']) && $arParams['DISABLED_LIBRARY']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
													<p class="description">Если не используете редактор Gutenberg, то смело отключайте.</p>
												</td>
											</tr>
											
											<tr class="heading">
												<td colspan="2">Чистим код шапки WP</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Emoji или эмодзи — это библиотека смайликов, используемых в блогах и чатах для выражения человеческих эмоций.">
													<span class="type-3"></span>
												</div>	
												Cкрыть Emoji WP
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_EMOJI]" type="checkbox" <?php if(!empty($arParams['DISABLED_EMOJI']) && $arParams['DISABLED_EMOJI']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="По собственным замерам и отзывам специалистов в интернете, dns-prefetch бесполезное включение в ядро WordPress.">
													<span class="type-3"></span>
												</div>	
												Удалять dns-prefetch
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_DNS_PREFETCH]" type="checkbox" <?php if(!empty($arParams['DISABLED_DNS_PREFETCH']) && $arParams['DISABLED_DNS_PREFETCH']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Уберёт ссылку на RSS постов в wp_header(); Защитит ваш сайт от быстрого парсинга.">
													<span class="type-3"></span>
												</div>	
												Отключить RSS канал постов
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_RSS_POST]" type="checkbox" <?php if(!empty($arParams['DISABLED_RSS_POST']) && $arParams['DISABLED_RSS_POST']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Уберёт ссылку на RSS рубрик в wp_header(); Защитит ваш сайт от быстрого парсинга.">
													<span class="type-3"></span>
												</div>	
												Отключить RSS канал рубрик
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_RSS_CATS]" type="checkbox" <?php if(!empty($arParams['DISABLED_RSS_CATS']) && $arParams['DISABLED_RSS_CATS']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Уберёт ссылку на RSS комментариев в wp_header(); Защитит ваш сайт от быстрого парсинга.">
													<span class="type-3"></span>
												</div>	
												Отключить RSS канал комментариев
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_RSS_COMMENTS]" type="checkbox" <?php if(!empty($arParams['DISABLED_RSS_COMMENTS']) && $arParams['DISABLED_RSS_COMMENTS']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Отключает отображение версии WP в wp_header();.">
													<span class="type-3"></span>
												</div>	
												Скрыть версию WP
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_WP_VERSION]" type="checkbox" <?php if(!empty($arParams['DISABLED_WP_VERSION']) && $arParams['DISABLED_WP_VERSION']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr class="heading">
												<td colspan="2">Изображения</td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="SVG (сокращение от Scalable Vector Graphics — «масштабируемая векторная графика») — это вид графики, которую создают с помощью математического описания геометрических примитивов (линий, кругов, эллипсов, прямоугольников, кривых и так далее), которые и образуют все детали будущего изображения.">
													<span class="type-3"></span>
												</div>	
												Разрешить загружать .svg
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[SET_SVG]" type="checkbox" <?php if(!empty($arParams['SET_SVG']) && $arParams['SET_SVG']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Исправляет ошибку загрузки изображений с большим разрешением.">
													<span class="type-3"></span>
												</div>	
												Разрешить загружать большие картинки
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[SET_BIG_IMAGES]" type="checkbox" <?php if(!empty($arParams['SET_BIG_IMAGES']) && $arParams['SET_BIG_IMAGES']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr class="heading">
												<td colspan="2">Yoast SEO</td>
											</tr>
											<? if($arParams['YOAST']=='Y'){ ?>
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Отключит вывод Json от Yoast SEO в шапке сайта.">
													<span class="type-3"></span>
												</div>	
												Отключить вывод Json от Yoast SEO
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_YOAST_JSON]" type="checkbox" <?php if(!empty($arParams['DISABLED_YOAST_JSON']) && $arParams['DISABLED_YOAST_JSON']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Отключит вывод Canonical от Yoast SEO в шапке сайта.">
													<span class="type-3"></span>
												</div>	
												Отключить вывод Canonical от Yoast SEO
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_YOAST_CANONICAL]" type="checkbox" <?php if(!empty($arParams['DISABLED_YOAST_CANONICAL']) && $arParams['DISABLED_YOAST_CANONICAL']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Отключит вывод meta тегов от Yoast SEO в шапке сайта.">
													<span class="type-3"></span>
												</div>	
												Отключить ненужные meta от Yoast SEO
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DISABLED_YOAST_META]" type="checkbox" <?php if(!empty($arParams['DISABLED_YOAST_META']) && $arParams['DISABLED_YOAST_META']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											<?php } ?>

											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title='Функционал удалит "Рубрика" у разделов.'>
													<span class="type-3"></span>
												</div>	
												Удаляем слово "Рубрика" у разделов
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[DELETE_RUBRIKA]" type="checkbox" <?php if(!empty($arParams['DELETE_RUBRIKA']) && $arParams['DELETE_RUBRIKA']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Добавит | Страница №# к title и description.">
													<span class="type-3"></span>
												</div>	
												Добавить&nbsp;<b>| Страница №#</b>&nbsp;на пагинации
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[SET_PAGE_NUM]" type="checkbox" <?php if(!empty($arParams['SET_PAGE_NUM']) && $arParams['SET_PAGE_NUM']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr class="heading">
												<td colspan="2">Настроки текстового редактора</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Добавит быстрые кнопки в html редактор страниц/постов.">
													<span class="type-3"></span>
												</div>	
												Добавить &#60;H1>,&#60;H2>
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[HTML_H]" type="checkbox" <?php if(!empty($arParams['HTML_H']) && $arParams['HTML_H']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Добавит быстрые кнопки в html редактор страниц/постов.">
													<span class="type-3"></span>
												</div>	
												Добавить &#60;p>&#60;/p>
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[HTML_P]" type="checkbox" <?php if(!empty($arParams['HTML_P']) && $arParams['HTML_P']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Добавит быстрые кнопки в html редактор страниц/постов.">
													<span class="type-3"></span>
												</div>	
												Добавить &#60;a href="">&#60;/a>
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[HTML_A]" type="checkbox" <?php if(!empty($arParams['HTML_A']) && $arParams['HTML_A']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="WP при выводе в коде сам подставляет дополнительные P на каждый пернос стройки.">
													<span class="type-3"></span>
												</div>	
												Удалять лишние &#60;p>&#60;/p> от WP
											</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[REMUVE_DOP_P]" type="checkbox" <?php if(!empty($arParams['REMUVE_DOP_P']) && $arParams['REMUVE_DOP_P']=='Y'){?>checked<?php }?>  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
										</tbody>
									</table>
								</div>
								<div class="adm-detail-content-btns">
								    <input type="submit" name="submit" id="submit" class="iksweb-btn" value="Сохранить">
							    </div> 
							</div>
							
						</form>
					</div>
				</div>
				
				<br><br>
				<?php if($arParams['ACTIVE']=='Y'){?>
				<div class="tabs"> 
					<ul class="adm-detail-tabs-block">
						<li class="adm-detail-tab active" data-id="1">Тестирование конфигурации</li>
						<li class="adm-detail-tab" data-id="3">SEO</li>
					</ul>
					<div class="adm-detail-content-wrap active">
						<div class="adm-detail-content">
							<div class="adm-detail-title">Полное тестирование системы</div>
							<div class="adm-detail-content-item-block">
								<p>Полная проверка системы помогает найти причины проблем в работе сайта и избежать появление ошибок в дальнейшем. Справка по каждому тесту поможет устранить причину ошибки. Будут проверены остновные параметры сайта/системы, а также настройка БД и отправка писем на почту.</p>
							
								<a href="?page=iksweb&set_start=1" class="btn-start-test">Начать тестирование</a>
						
								<?php if(!empty($_GET['set_start']) && intval($_GET['set_start'])=='1'){?>
								<table class="internal">
									<tbody>
										<tr class="heading">
											<td colspan="2">Общая работа сайта</td>
										</tr>
										<tr>
											<td>Обязательные параметры PHP</td>
											<td>
												<?php
												$phpversion = phpversion();
												if( $phpversion >= '7.4' ){?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Настройки правильные (v<?php echo $phpversion;?>)</span>
												<?php }else{?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">Необходимо обновить PHP до версии 7.4 и выше. (установлена v<?php echo $phpversion;?>)</span>
												<?php }?>
												<a href="#info" data-modal="open" data-modal-name="Обязательные параметры PHP" data-modal-message="При использование WordPress рекомендуется использовать PHP последних версий. <br> Это значительно учеличит защиту вашего сайта от взлома и ошибок.<br> С версии WP 5.2 рекомендуется использовать PHP не ниже 7.4."><div class="sc_help_link"></div></a>
											</td>
										</tr>
										<tr>
											<td>Параметры max_input_vars</td>
											<td>
												<?php
												$max_input_vars = ini_get('max_input_vars');
												if( $max_input_vars < '10000'){ ?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">Значение max_input_vars должно быть не ниже 10.000. Текущее значение: <?php echo $max_input_vars;?></span>
												<?php }else{ ?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Успешно. Текущее значение: <?php echo $max_input_vars;?></span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Параметры max_input_vars" data-modal-message="Значение max_input_vars должно быть не ниже 10000. <br> Max_input_vars — это количество входных переменных, которые могут быть приняты в одном запросе.<br><br> Что бы исправить эту ошибку добавьте в <b>php.ini</b>  <br><code>max_input_vars = 10000</code> <br><br> Если у вас нет доступа к php.ini, добавьте в <b>.htaccess</b>  <br><code>php_value max_input_vars 10000</code><br><br> После # END WordPress"><div class="sc_help_link"></div></a>
											</td>
										</tr>
										<tr>
											<td>Отображение ошибок</td>
											<td>
												<?php if(defined(WP_DEBUG) && WP_DEBUG==true){ ?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">На сайте включено отображение ошибок для ользователей</span>
												<?php }else{ ?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Отключено</span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Отображение ошибок" data-modal-message="PHP-предупреждения и уведомления помогают разработчикам в доработке сайта и поиске ошибок. Однако, это выглядит крайне непрофессионально, когда эти ошибки отображаются на главной странице вашего сайта и видны всем посетителям. <br> Для отключения ошибок отключите <code>define('WP_DEBUG', false);</code> в файле wp-config.php."><div class="sc_help_link"></div></a>
											</td>
										</tr>
										<tr>
											<td>Автоматическое обновление</td>
											<td>
												<?php if(defined('WP_AUTO_UPDATE_CORE') && WP_AUTO_UPDATE_CORE==true){?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">Необходимо отключить автоматическое обновление сайта и плагинов</span>
												<?php }else{ ?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Отключено</span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Автоматическое обновление" data-modal-message="Мы рекомендуем отключать автоматические абновления сайта и плагинов и проводить их исключительно в ручном режиме. Это поможет уберечь ваш сайт от неожиданных поломок. <br> Для отключения добавьте код в файл wp-config.php <code>define( 'AUTOMATIC_UPDATER_DISABLED', true )</code>"><div class="sc_help_link"></div></a>
											</td>
										</tr>
										<tr>
											<td>Сохранение сессии</td>
											<td>
												<?php
												if(session_status()){?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Успешно</span>
												<?php }else{ ?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">Сохранение сесии отключено</span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Сохранение сессии" data-modal-message="Проверяется возможность хранить данные на сервере используя механизм сессий. Эта базовая возможность необходима для сохранения авторизации между хитами. <br><br> Сессии могут не работать, если их поддержка не установлена, в php.ini неправильно указана папка для хранения сессий или она не доступна на запись."><div class="sc_help_link"></div></a>
											</td>
										</tr>
										<tr>
											<td>Параметры настройки UTF</td>
											<td>
												<?php
												$blog_charset = get_option('blog_charset' );
												if($blog_charset!=='UTF-8'){?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">Не правильные. Сайт работает в кодировке <?php echo $blog_charset;?></span>
												<?php }else{ ?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Правильные. Сайт работает в UTF кодировке</span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Параметры настройки UTF" data-modal-message="Если параметры не соответствуют требуемым, то в разных местах будут появляться совершенно непредсказуемые ошибки: частично обрезанный текст, неработающий импорт xml, система обновлений и т.д."><div class="sc_help_link"></div></a>
											</td>
										</tr>
										<tr>
											<td>HTTP авторизация</td>
											<td>
												<?php
												if(!empty($_SERVER['PHP_AUTH_DIGEST'])){?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">Необходимо включить для корректной работы сайта</span>
												<?php }else{ ?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Успешно</span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="HTTP авторизация" data-modal-message="Используя заголовки HTTP запроса передаются данные авторизации, затем осуществляется попытка их определить, используя переменную сервера REMOTE_USER (или REDIRECT_REMOTE_USER). HTTP авторизация необходима для интеграции с 1С и другого функционала."><div class="sc_help_link"></div></a>
												
											</td>
										</tr>
										
										<tr>
											<td>Отправка почты mail()</td>
											<td>
												<?php
												if(mail('iksweb@mail.ru','Тест системы','Тест системы')){?>
													<div class="sc_icon sc_icon_success"></div>
													<span class="sc_success">Успешно</span>
												<?php }else{ ?>
													<div class="sc_icon sc_icon_error"></div>
													<span class="sc_warning">Сервер блокирует отправку сообщений</span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Отправка почты" data-modal-message="Плагин производит тестовую отправку сообщения на нашу почту <code>mail('почта','Тест системы','Тест системы')</code><br> и соощит вам если сообщение небыло отправлено. Если вы увидели ошибку по даному пункту, то вам стоит обратиться в поддержку."><div class="sc_help_link"></div></a>
											</td>
										</tr>
										<tr>
											<td>Наличие ключей шифрования</td>
											<td>
												<?php if(!AUTH_KEY || !SECURE_AUTH_KEY || !LOGGED_IN_KEY || !NONCE_KEY || !AUTH_SALT || !SECURE_AUTH_SALT){?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">Нет ключей шифрования данных</span>
												<?php }else{ ?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Успешно</span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Наличие ключей шифрования" data-modal-message="В главном файле конфигураций вашего сайта должны быть указаны все необходимые ключи шифрования данных для коректно работы сайта и плагинов."><div class="sc_help_link"></div></a>
											</td>
										</tr>
										<tr>
											<td>Настройка темы по умолчанию</td>
											<td>
												<?php
												$blog_template = get_option('template' );
												if(!$blog_template){?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">Тема не установлена в настройках</span>
												<?php }else{ ?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Успешно. Установлена тема - <?php echo $blog_template;?></span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Настройка темы по умолчанию" data-modal-message="После запуска сайта вам необходимо выбрать тему оформления и установить его в настройках внешнего вида."><div class="sc_help_link"></div></a>
											</td>
										</tr>
										<tr class="heading">
											<td colspan="2">Тестирование базы данных</td>
										</tr>
									
										<tr>
											<td>Версия MySQL-сервера</td>
											<td>
												<?php
												global $wpdb;
												$version_db = $wpdb->db_server_info();
												if($version_db<='5.3'){?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">На вашем сервере используется очень старая версия MySQL-сервера - v<?php echo $version_db;?></span>
												<?php }else{ ?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Отлично. Версия MySQL-сервера - v<?php echo $version_db;?></span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Версия MySQL-сервера" data-modal-message="Мы рекомендуем использовать самые последние версии MySQL (от 5.7 и выше)"><div class="sc_help_link"></div></a>
											
											</td>
										</tr>
										<tr>
											<td>Кодировка базы данных</td>
											<td>
												<?php
												$charset_db = $wpdb->get_charset_collate();
												if(mb_strpos($charset_db,'utf8mb4_unicode_520_ci')== false){?>
												<div class="sc_icon sc_icon_error"></div>
												<span class="sc_warning">Сохранение сесии отключено</span>
												<?php }else{ ?>
												<div class="sc_icon sc_icon_success"></div>
												<span class="sc_success">Успешно. Кодировка БД - utf8mb4</span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Кодировка базы данных" data-modal-message="Проверяется соответствие кодировки и сравнения базы данных кодировке и сравнению соединения. Эти значения MySQL использует для создания новых таблиц. Текущая кодировка БД <br><code><?php echo $charset_db;?></code>"><div class="sc_help_link"></div></a>
											
											</td>
										</tr>
										<tr>
											<td>Безопасность пароля от БД</td>
											<td>
												<?php if(strlen(DB_PASSWORD)>10){ ?>
													<div class="sc_icon sc_icon_success"></div>
													<span class="sc_success">Успешно</span>
												<?php }else{ ?>
													<div class="sc_icon sc_icon_error"></div>
													<span class="sc_warning">Пароль от БД слишком короткий (необходимо задать больше 10 символов)</span>
												<?php } ?>
												<a href="#info" data-modal="open" data-modal-name="Безопасность пароля от БД" data-modal-message="Мы рекомендуем устанавливать сложные и длинные пароли от БД (от 10 символов)"><div class="sc_help_link"></div></a>
											</td>
										</tr>
									</tbody>
								</table>
								<?php } ?>
							 </div> 
						</div> 	 
					</div>

					<div class="adm-detail-content-wrap">   
						<div class="adm-detail-content">
							<div class="adm-detail-title">Содержимое файлов для SEO</div>
							<div class="adm-detail-content-item-block">
								<h3>Robots.txt</h3>
								<textarea cols="50" rows="10" style="width:100%"><?php echo $this->GetFile($_SERVER['DOCUMENT_ROOT'].'/robots.txt');?></textarea>
								<h3>Sitemap.xml</h3>
								<textarea cols="50" rows="10" style="width:100%"><?php echo $this->GetFile($_SERVER['DOCUMENT_ROOT'].'/sitemap.xml');?></textarea>
							</div> 
						</div> 	
					</div>
				</div> 
				<?php }?>
			</div>	
			<?php
			$this->ShowPageFooter();
		}
		
		/*
		 * Отображение страницу покупки PRO версии для FREE
		*/
		public static function ShowPagePro()
		{
			global $APPLICATION;
			
			$APPLICATION->ShowPageHeader();	
			?>
			<div class="tabs">
				<ul class="adm-detail-tabs-block">
					<li class="adm-detail-tab adm-detail-tab-active adm-detail-tab-last active">IKSWEB</li>
				</ul>
				<div class="adm-detail-content-wrap active">
						<div class="adm-detail-content">
							<div class="adm-detail-title">Обновить плагин до PRO</div>
							<div class="adm-detail-content-item-block">
								<p>Если вам понравилась работа нашего плагина, вы можете приобрести PRO версию и получать уникальные обновления.</p>
								<h2>Что же вы получите в PRO версии?</h2>
								<ul>
									<li><span class="dashicons dashicons-saved"></span> Первоклассную поддержку</li>
									<li><span class="dashicons dashicons-saved"></span> Расширенный набор функций</li>
									<li><span class="dashicons dashicons-saved"></span> Бесплатные обновления</li>
								</ul>
								<br>
								<a target="_blank"  href="//iksweb.ru/plugins/wordpress-start/?utm_content=pligin&utm_medium=wp&utm_source=<?php echo $_SERVER['SERVER_NAME'];?>&utm_campaign=plugin" class="iksweb-btn">Подробнее о PRO версии</a>

								<br><br><br>
								
								<h2>Помочь развитию проекта</h2>
								<p>Наш проект нуждается в вашей помощи. На разработку и поддержание плагинов уходит много средств и сил. Мы будем рады любой помощи.</p>
								
								<iframe src="https://yoomoney.ru/quickpay/shop-widget?writer=seller&targets=%D0%A1%D0%B1%D0%BE%D1%80%20%D1%81%D1%80%D0%B5%D0%B4%D1%81%D1%82%D0%B2%20%D0%BD%D0%B0%20%D0%BE%D0%B1%D0%BD%D0%BE%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D0%B5%20%D0%BF%D0%BB%D0%B0%D0%B3%D0%B8%D0%BD%D0%BE%D0%B2&targets-hint=&default-sum=100&button-text=14&payment-type-choice=on&mobile-payment-type-choice=on&comment=on&hint=&successURL=https%3A%2F%2Fplugin.iksweb.ru%2Fwordpress%2F&quickpay=shop&account=4100116825216739" width="100%" height="303" frameborder="0" allowtransparency="true" scrolling="no"></iframe>
							</div>
						</div>
					</div>
			</div>	
			<?php
			$APPLICATION->ShowPageFooter();
		}
		
		/*
		 * Отображение страницу капчи
		*/
		public static function ShowPageCurl()
		{
			global $APPLICATION , $IKSWEB_CYRL;
			
			$APPLICATION->ShowPageHeader();	
			?>
			<div class="tabs"> 
				<ul class="adm-detail-tabs-block">
					<li class="adm-detail-tab adm-detail-tab-active adm-detail-tab-last active">IKSWEB</li>
				</ul>
			<?php
			$IKSWEB_CYRL->ShowPageCurl();
			
			$APPLICATION->ShowPageFooter();
		}
		
		/*
		* Отображение страницу настройки форм
		*/
		public function ShowPageForms()
		{
			global $APPLICATION , $IKSWEB_FORMS;
			
			$APPLICATION->ShowPageHeader();	
			?>
			<div class="tabs"> 
				<ul class="adm-detail-tabs-block">
					<li class="adm-detail-tab active" data-id="1">IKSWEB</li>
					<li class="adm-detail-tab" data-id="2">Почтовые шаблоны</li>
					<li class="adm-detail-tab" data-id="3">Логи</li>
				</ul>
			<?php
			$IKSWEB_FORMS->ShowPageForms();
			
			$APPLICATION->ShowPageFooter();
		}
		
		/*
		 * Отображение страницу капчи
		*/
		public function ShowPageCaptha()
		{
			global $APPLICATION , $IKSWEB_RECAPTHA;
			
			$APPLICATION->ShowPageHeader();	
			?>
			<div class="tabs"> 
				<ul class="adm-detail-tabs-block">
					<li class="adm-detail-tab adm-detail-tab-active adm-detail-tab-last active">IKSWEB</li>
				</ul>
			<?php
			$IKSWEB_RECAPTHA->ShowPageCaptha();
			
			$APPLICATION->ShowPageFooter();
		}
		
		/*
		 * Шапка для всех страниц
		*/
		public function ShowPageHeader($title=false)
		{
			global $APPLICATION;
		?>	
		<div class="wrap iks-wrap">
			<h1 class="wp-heading-inline"><?php echo !empty($title)? $title : 'Настройки модуля' ;?></h1>
			<form action="" class="iks-select">
				<select name="mid" onchange="window.location='admin.php?page='+this[this.selectedIndex].value;">
					<?php foreach($GLOBALS['submenu']['iksweb'] as $arItem){?>
						<option value="<?php echo $arItem[2]?>" <?php if($_GET['page']==$arItem[2]){?>selected<?php } ?>><?php echo $arItem[0]?></option>
					<?php }?>
				</select>
			</form>
			<?php
			if(!empty($_REQUEST['settings-updated'])){
				$APPLICATION->ShowNotices('Настройки компонента сохранены.');	
			}
			?>
		<?php	
		}

		/*
		 * Подвал для всех страниц
		*/
		public function ShowPageFooter()
		{
		?>
			<div class="footer-page">
				<div class="iksweb-box">
					<ul>
						<li><span class="type-1"></span> - Нейтральная настройка, которая не может нанести вред вашему сайту.</li>
						<li><span class="type-2"></span> - При включении этой настройки, вы должны быть осторожны. Некоторые плагины и темы могут зависеть от этой функции.</li>
		        <li><span class="type-3"></span> - Абсолютно безопасная настройка, рекомендуем использовать.</li>
						<li>----------</li>
						<li>Наведите указатель мыши на значок, чтобы получить справку по выбранной функции.</li>
		      </ul>
				</div>
				<div class="iksweb-box">
					<p><b>Вы хотите, чтобы плагин улучшался и обновлялся?</b></p>
					<p>Помогите нам, оставьте отзыв на wordpress.org. Благодаря отзывам, мы будем знать, что плагин действительно полезен для вас и необходим.</p>
					<p style="margin: 9px 0;">А также напишите свои идеи о том, как расширить или улучшить плагин.</p>
					<div class="vote-me">
						<span class="dashicons dashicons-star-filled"></span>
						<span class="dashicons dashicons-star-filled"></span>
						<span class="dashicons dashicons-star-filled"></span>
						<span class="dashicons dashicons-star-filled"></span>
						<span class="dashicons dashicons-star-filled"></span>
					</div>
					<a href="//wordpress.org/plugins/iksweb/?utm_content=pligin&utm_medium=wp&utm_source=<?php echo $_SERVER['SERVER_NAME'];?>&utm_campaign=plugin" target="_blank"><strong>Оставить отзыв или поделиться идеей</strong></a>
					
					<p style="margin: 5px 0 0 0; font-weight: bold; color: #d63638;">Хотите поддержать плагин? - <a href="//iksweb.ru/payment/" target="_blank">Пожертвовать</a></p>
				</div>
				<div class="iksweb-box">
					<p><b>Возникли проблемы?</b></p>
					<p>Мы предоставляем платную и бесплатную поддержку для наших <a href="//iksweb.ru/plugins/?utm_content=pligin&utm_medium=wp&utm_source=<?php echo $_SERVER['SERVER_NAME'];?>&utm_campaign=plugin" target="_blank">плагинов</a>. Если вас столкнули с проблемой, просто создайте новый тикет. Мы обязательно вам поможем!</p>
					<p><span class="dashicons dashicons-sos" style="margin: -4px 5px 0 0;"></span> <a href="//iksweb.ru/plugins/support/?utm_content=pligin&utm_medium=wp&utm_source=<?php echo $_SERVER['SERVER_NAME'];?>&utm_campaign=plugin" target="_blank">Получите поддержку</a></p>
					<div style="margin: 15px 0 10px;background: #fff4f1;padding: 10px;color: #a58074;">
						<span class="dashicons dashicons-warning" style="margin: -4px 5px 0 0;"></span> Если вы обнаружите ошибку php или уязвимость в плагине, вы можете <a href="//iksweb.ru/plugins/support/?utm_content=pligin&utm_medium=wp&utm_source=<?php echo $_SERVER['SERVER_NAME'];?>&utm_campaign=plugin" target="_blank">создать тикет</a> в поддержке, на который мы ответим мгновенно.
					</div>
				</div>
			</div> 
		<?php	
		}

		/*
		* Информация о разработчике
		*/
		public function ShowDashbord() 
		{
			global $IKSWEB_FORMS;
			
			$arParams = $this->settings;

			if ( class_exists( 'IKSWEB_FORMS' ) && $IKSWEB_FORMS->arParams['PARAMS']['ACTIVE']=='Y') {
				wp_add_dashboard_widget(
					'forms',
					'Последние заявки', 
					array( $this , 'ShowFormsInfo' )
				);
			}
			
			wp_add_dashboard_widget(
				'site-info',
				'Параметры сайта', 
				array( $this , 'ShowSiteInfo' )
			);
		}

		/*
		* Выводим виджет последние заявки
		*/
		public function ShowFormsInfo() {
			
			global $APPLICATION, $IKSWEB_FORMS;
			
			$arResult = $IKSWEB_FORMS->Get();

			?>
			<div class="forms-tems">
			<?php
			if(isset($arResult)){
				foreach($arResult as $arItem){?>
					<div class="item">
						<a href="<?php echo $arItem['DETAIL_PAGE']?>"><?php echo $APPLICATION->TruncateText($arItem['NAME'],65)?></a>
						<span><?php echo $APPLICATION->ChangeDateFormat($arItem['DATE'],'d.m.Y H:i')?></span>
					</div>
				<?php }
			}?>
			</div>
		<?php
		}

		/*
		* Выводим виджет последние заявки
		*/
		public static function ShowSiteInfo()
		{
			global $APPLICATION;
			
			$arParams = $APPLICATION->settings;
			
			$arParams['SITE']['THEME_VERSIA'] = wp_get_theme()->get('Version');
		?>    
		<table class="bx-gadgets-info-site-table" cellspacing="0"> 
			<tbody> 
				<tr><td class="bx-gadget-gray">Кодировка:</td><td><?php echo $arParams['SITE']['CHARSET'];?></td></tr>
				<tr><td class="bx-gadget-gray">Главнй Е-mail:</td><td><?php echo $arParams['SITE']['EMAIL'];?> <a href="/wp-admin/options-general.php"><span class="dashicons dashicons-edit"></span></a></td></tr>
				<?php if($arParams['SITE']['WP_VERSIA']){ ?><tr><td class="bx-gadget-gray">Версия ядра:</td><td>v <?php echo $arParams['SITE']['WP_VERSIA'];?></td></tr> <?php } ?>
				<?php if($arParams['SITE']['THEME_VERSIA']){ ?><tr><td class="bx-gadget-gray">Версия темы:</td><td>v <?php echo $arParams['SITE']['THEME_VERSIA']?></td></tr> <?php } ?>
			</tbody>
		</table>
		<?php
		}
		
		/*
		* Изменение формата даты
		*/
		function ChangeDateFormat($sourceDate, $newFormat)
		{
		    $result = date($newFormat, strtotime($sourceDate));
		    return $result;
		}
		
		/*
		* Обрезаем текст
		*/
		function TruncateText($strText, $intLen=55 , $more=false )
		{
		    if(strlen($strText) > $intLen)
		        return rtrim(substr($strText, 0, $intLen), ".").$more;
		    else
		        return $strText;
		}

		/*
		* Режим меню в зависимости от типа сайта
		*/
		function RemoveMenu()
		{

			add_menu_page( 'Меню', 'Меню', 'edit_others_posts', '/nav-menus.php', false , '' , 40 ); 
			add_menu_page( 'Виджеты', 'Виджеты', 'edit_others_posts', '/widgets.php', false , '' , 40 ); 
			
		}

		/*
		* Подключаем JS и CSS к панели
		*/
		public function ShowHeadScripts()
		{
			
			$arParams = $this->settings['PLUGIN'];

			wp_enqueue_script( 'iksweb', $arParams['URL'].'assets/js/iksweb.js', array(), $arParams['VERSIA'] , true );
			wp_enqueue_style( 'iksweb', $arParams['URL'].'assets/css/iksweb.css', array(), $arParams['VERSIA'] );
			wp_enqueue_script( 'tooltip', $arParams['URL'].'assets/js/bootstrap.tooltip.min.js', array(), $arParams['VERSIA'] , true );

		}

		/*
		* Подключаем JS и CSS к авторизации
		*/
		public function ShowAHeadScripts()
		{
			$arParams = $this->settings['PLUGIN'];
			
			wp_enqueue_script( 'jquery' );
				
			if($arParams['SITE_DESIGN']=='Y'){
				wp_enqueue_script( 'iksweb', $arParams['URL'].'assets/js/iksweb-auth.js', array(), $arParams['VERSIA']  , true);
				wp_enqueue_style( 'iksweb', $arParams['URL'].'assets/css/iksweb-auth.css', array(), $arParams['VERSIA']  );
			}
			
		}

		/*
		* Получаем содержимое файла
		*/
		public function GetFile($file=false)
		{
			
			if($file!==false){
				if (file_exists($file)) {
				    return file_get_contents($file);
				} else {
				    return "Файл не найден.";
				}	
			}
			
			return false;
		}

		/*
		* Добавляем вывод ID медиафайлов
		*/
		public function ShowIDMediaColumn($columns) {
		    $columns['colID'] = __('ID');
		    return $columns;
		}
		
		public function ShowIDMediaRow($columnName, $columnID){
		    if($columnName == 'colID'){
		       echo $columnID;
		    }
		}
		
		public function ShowIDMediaElement($form_fields, $post ){
		   $form_fields[] = array(
		    'value' => $post->ID,
		    'label' => 'ID',
		    'input' => 'html',
		    'html'  => "<input type='text' class='text' readonly='readonly' name='attachments[$post->ID]' value='" . $post->ID . "' /><br />"
		  );
		  return $form_fields;
		}
		
		/*
		*  Отключить вывод Canonical от Yoast SEO
		*/
		function DisabledCanonicalYoast($robots)
		{
			return false;
		}
		
		/*
		* Удаляем robots из Rest API
		*/
		function DisabledRobotsWP($robots)
		{
			return array();
		}
		
		/*
		* Отключаем Rest API
		*/
		function DisabledRestApi( $result, $rest_server, $request  )
		{
			if( ! is_null( $result ) ){
				return $result;
			}

			if( '/wp/v2' === substr( $request->get_route(), 0, 6 ) && ! current_user_can( 'manage_options' ) ) // only for `/wp/v2` namespace && Administrator
			{ 
				return new WP_Error( 'rest_not_logged_in', "YOU WON'T PASS! Go back to HELL, stupid!", [ 'status' => 404 ] );
			}
			return $result;
		}
		
		/*
		* Отключить ненужные meta от Yoast SEO
		*/
		function DisabledYoastMeta($robots, $presentation)
		{
			return array();
		}
		
		/*
		* Разрешить загружать .svg
		*/
		function AllowSVGFiles($mimes)
		{
			$mimes['svg']  = 'image/svg+xml';
			return $mimes;
		}
		
		/*
		* Разрешить загружать .svg для WP 5.1 +
		*/
		function fix_AllowSVGFiles( $data, $file, $filename, $mimes, $real_mime = '')
		{
			
			if( version_compare( $GLOBALS['wp_version'], '5.1.0', '>=' ) ){
				$dosvg = in_array( $real_mime, [ 'image/svg', 'image/svg+xml' ] );
			}
			else {
				$dosvg = ( '.svg' === strtolower( substr( $filename, -4 ) ) );
			}

			// mime тип был обнулен, поправим его
			// а также проверим право пользователя
			if( $dosvg ){

				// разрешим
				if( current_user_can('manage_options') ){
					$data['ext']  = 'svg';
					$data['type'] = 'image/svg+xml';
				} else {
					$data['ext']  = false;
					$data['type'] = false;
				}

			}

			return $data;
		}
				
		/*
		* Защитить уязвимость XML-RPC
		*/
		function RemoveXMLRPCmethod($methods)
		{
			return array();
		}
		
		/*
		*  Добавляем кнопки в текстовый html-редактор
		*/
		function AddRedactorBTN()
		{
			$arParams = $this->settings['PLUGIN'];
			if (wp_script_is('quicktags')){ ?>
		    <script type="text/javascript">
					document.addEventListener( 'DOMContentLoaded', function(){
		        <?php if($arParams['HTML_P']=='Y'){ ?>QTags.addButton( 'iksweb_p', 'p', '<p>', '</p>', 'p', 'Параграф', 901 );<?php } ?>
		        <?php if($arParams['HTML_H']=='Y'){ ?>
		        QTags.addButton( 'iksweb_h1', 'h1', '<h1>', '</h1>', 'h', 'Заголовок 1 уровня', 902);
		        QTags.addButton( 'iksweb_h2', 'h2', '<h2>', '</h2>', 'h', 'Заголовок 2 уровня', 903);
		        <?php } ?>
		        <?php if($arParams['HTML_A']=='Y'){?>QTags.addButton( 'iksweb_a', 'Ссылка', '<a href="" target="">', '</a>', 'a', 'Ссылка', 903);<?php } ?>
					} );
		    </script>
		<?php }
		}
				
		/*
		* Добавить | Страница №# на пагинации
		*/
		function ShowNumberPagination($title , $presentation = '')
		{
			$page_num = get_query_var('paged');
			if(is_paged() && intval($page_num)>0){
				return $title.' | Страница '.$page_num;	
			}
			return $title;
		}
		
		/*
		* Защитить уязвимость XML-RPC
		*/
		function DisabledLibraryCSS($methods)
		{
			wp_dequeue_style( 'wp-block-library' );
		}
		
		/*
		* Удаляем слово рубрика
		*/
		function DeleteRubrika($title)
		{
			$title = single_tag_title( '', false );
			return $title;
		}

		/******************** END *****************/
	} 
	
	// globals
	global $APPLICATION;

	// initialize
	if( !isset($APPLICATION) ) {
		$APPLICATION = new IKSWEB();
		$APPLICATION->init();
	}

	/*
	* Подключаем дополнительные классы
	*/
	require_once(IKS_DOCUMENT_ROOT.'/classes/recaptha_iksweb.php');
	
	require_once(IKS_DOCUMENT_ROOT.'/classes/cyrlitera_iksweb.php');
	
	require_once(IKS_DOCUMENT_ROOT.'/classes/forms_iksweb.php');
}
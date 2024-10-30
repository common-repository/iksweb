<?php
/*
Author: IKSWEB
Author URI: https://iksweb.ru/
Author Email: info@iksweb.ru
Description: Компонент модуля IKSWEB необходим для подключения и обработки почтовых форм сайта с дальнейшей отправкой писем
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

if ( !class_exists( 'IKSWEB_FORMS' ) ) {
	
	class IKSWEB_FORMS{
	
		/* @public array Результаты обработки форм */
		public $success		= '';
		public $arErrors	= array();
		public $arResult	= array();
		
		/* @public string Параметры для отправки*/
		public $name		= '';
		public $title		= '';
		public $email_from	= '';
		public $email_to	= '';
		public $massage		= '';
		public $type		= '';

		/* @var array Параметры плагина */
		var $version = '1.8';
		var $arParams = array();
	
		/* Пропускаем __construct */
		function __construct(){}
		
		/*
		* Запуск компонента
		*/
		function init()
		{
			
			$SETTINGS_NAME = 'FORMS_SETTINGS';
			
			$admin_email = get_bloginfo('admin_email');
			
			// Формируем массив настроек плагина
			$arParams = $this->arParams = array(
				
				// base
				'VERSIA'			=> $this->version,

				// urls
				'HOST'				=> $_SERVER['HTTP_HOST'],
				'URL'					=> plugin_dir_url( __FILE__ ),
				'PATH'				=> plugin_dir_path( __DIR__ ),
				'FILE'				=> __FILE__,
				'ADMIN_URL'		=> admin_url(),
				
				
				//settings
				'SLUG'							=> 'iks-forms',
				'SLUG_FORMS_RESULT' => 'forms',
				'SETTINGS_NAME' 		=> $SETTINGS_NAME,
				
				// params
				'DEFAULT_EMAIL_FROM'=>  $admin_email,
				'SERVER_NAME'		=>  get_bloginfo('wpurl'),
				'SITE_NAME' 		=>  get_bloginfo('name'),
				'PARAMS'				=>	get_option($SETTINGS_NAME),
				'DATE_FORMAT'		=> 	get_option('links_updated_date_format')
			);

			if(empty($arParams['PARAMS']['EMAIL_TO']))
				$this->arParams['PARAMS']['EMAIL_TO'] = $admin_email;
				
			if(empty($arParams['PARAMS']['FILES_TYPE']))
				$this->arParams['PARAMS']['FILES_TYPE'] = 'jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx,pps,ppsx,odt,xls,xlsx,mp3,m4a,ogg,wav,mp4,m4v,mov,wmv,avi,mpg,ogv,3gp,3g2';	

			// Регистрируем настройки
			add_action( 'admin_init', array( $this, 'RegisterSettings' ));
			
			// Если плагин активен, регистрируем тип записей
			if($arParams['PARAMS']['ACTIVE']=='Y'){
				
				add_action( 'init',  array( $this, 'RegisterPostType' ) );
			
			}
		}

		/*
		* Регистрируем Раздел
		*/
	  public function RegisterPostType()
	  {
			$arParams = $this->arParams;
					
			register_post_type($arParams['SLUG_FORMS_RESULT'], array(
					
				'label'  => null,
				'labels' => array(
					'name'               => 'Заявки',
					'singular_name'      => 'Заявка',
					'add_new'            => 'Добавить заявку',
					'add_new_item'       => 'Добавление заявки',
					'edit_item'          => 'Редактирование заявки', 
					'new_item'           => 'Новая заявка',
					'view_item'          => 'Смотреть заявки', 
					'search_items'       => 'Искать заявку', 
					'not_found'          => 'Заявок не найдено',
					'not_found_in_trash' => '', 
					'parent_item_colon'  => '',
					'menu_name'          => 'Результаты форм',
				),
				'description'			 => '',
				'public'				 => true,
				'publicly_queryable'	 => false,
				'exclude_from_search'	 => false, 
				'show_ui'           	 => true, 
				'show_in_menu'      	 => null,
				'show_in_admin_bar' 	 => null,
				'show_in_nav_menus' 	 => false, 
				'show_in_rest'      	 => false, 
				'rest_base'         	 => true, 
				'menu_position'     	 => 60,
				'menu_icon'         	 => 'dashicons-phone', 
				'capability_type'   	 => 'post',
				'hierarchical'      	 => false,
				'supports'          	 => array('title','editor'), 
				'taxonomies'        	 => array(),
				'has_archive'       	 => false,
				'rewrite'           	 => true,
				'query_var'         	 => true,
			) );
		}
	
		/*
		* Регестрируем настройки
		*/
		function RegisterSettings()
		{
			
			$arParams = $this->arParams;
			
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
				if(is_array($v)){
					$valid_input[$k] = $v;
				}else{
					$valid_input[$k] = trim($v);
				}
			}
			
			return $valid_input;
		}
		
		/*
		* Обрабатываем результаты форм
		*/
		public function Add()
		{
			
			global $_REQUEST , $IKSWEB_RECAPTHA;
			
			$arParams = $this->arParams;
			
			if($arParams['PARAMS']['ACTIVE']=='Y'){
	
				// Оставляем только заполненые масивы. Необходимо если на странице будет несколько форм
				$arRequest = array_filter($_REQUEST["FORMS"], function($element) {	
				
					return $element['FORM_HACH']!=''; 
				
				}, ARRAY_FILTER_USE_BOTH);
			
				if($arRequest){

					// Чистим от личшего
					foreach($arRequest as $id=>$elements) {
	
						// Если у формы нет ID
						if(!is_array($elements)){
							
							$arResult[$id] = sanitize_text_field(trim($elements));
							
							$FORM_ID = 0;
							
							$arRules[] = array(
								'RULES' => '/#'.$id.'#/s',
								'VALUE'	=> sanitize_text_field(trim($elements)),
								);
		
						}else{
							
							$FORM_ID = $id;
							
							foreach($elements as $k => $v) {
								
								$arResult[$k] = sanitize_text_field(trim($v));

								$arRules[] = array(
									'RULES' => '/#'.$k.'#/s',
									'VALUE'	=> sanitize_text_field(trim($v)),
								);
							}
							
						}
					}
					
					$arResult['DATE']		=	date($arParams['DATE_FORMAT']);
					
					$this->arResult = $arResult; // Записываем в переменную
					
					$arParams['TEMPLATES'] = $arParams['PARAMS']['MAIL_TEMPLATES'][$FORM_ID];
					
					if(empty($arParams['TEMPLATES']['NAME']))
						$arParams['TEMPLATES'] = $arParams['PARAMS']['MAIL_TEMPLATES'][0];
					
					if(empty($arParams['TEMPLATES']['NAME']))
						return false;
						
					// Записываем в класс для стаичного обращения
					$this->name 			= $arParams['TEMPLATES']['NAME'];
					$this->title			= $arParams['TEMPLATES']['TITLE'];
					$this->email_from	= $arParams['PARAMS']['EMAIL_TO'];
					$this->email_to		= $arParams['TEMPLATES']['EMAIL'];
					$this->massage		= $arParams['TEMPLATES']['MESSAGE'];
					$this->type 			= $arParams['TEMPLATES']['BODY_TYPE'];
					
					// Проверка на обязательные поля
					$required = false;
					$arRequired = array_diff(explode(',',$arParams['TEMPLATES']['REQUIRED_FILEDS']), array(''));
					
					// $this->arErrors[] = '<pre>'.print_r($arResult,true).'</pre>'; // debug 
					
					if(isset($arRequired)){
						foreach($arRequired as $r){
							if(empty($arResult[trim($r)])){ 
								$required=true;
							}
						}	
					}
					if($required){
						$this->arErrors[] = $arParams['PARAMS']['EROR_4'];
					}
					
					// Проверка на соответсвие Хэша формы
					if(md5($_SERVER['REMOTE_ADDR'].$arParams['PARAMS']['FORM_HACH'].date('d')) !== $arResult['FORM_HACH']){
						$this->arErrors[] = $arParams['PARAMS']['EROR_1'];
					}
					
					// Проверка на наличие COOKIE
					if(isset($arParams['PARAMS']['COOKIE']) && $arParams['PARAMS']['COOKIE']=='Y' && $_COOKIE['IKS_CMS_START']!=='Y'){
						$this->arErrors[] = $arParams['PARAMS']['EROR_2'];
					}

					// Если включена рекапча, проверяем её	
					if (isset($arParams['PARAMS']['RECAPTHA']) && $arParams['PARAMS']['RECAPTHA']=='Y' && $IKSWEB_RECAPTHA->CheckCaptha()!=true){
						$this->arErrors[] = $arParams['PARAMS']['EROR_3'];	
					}
					
					// Если включено логирование, то сохраняем.
					if($this->arErrors && !empty($arParams['PARAMS']['LOGS']) && $arParams['PARAMS']['LOGS']=='Y'){
						
						$this->AddLog('При попытке отправить письмо у пользователя появилась ошибка - '.implode(" / ", $this->arErrors)."\n");
				    				
					}
					
					$arParams['FILES_SEND'] = [];
					if(isset($arParams['PARAMS']['FILES']) && $arParams['PARAMS']['FILES']=='Y' && !empty($_FILES['FORMS']['name'])){
						
						// Обрабатываем получаемые файлы
						foreach($_FILES['FORMS'] as $key=>$files){
							$nextKey = array_key_first($files[$FORM_ID]);
							$value = $files[$FORM_ID][$nextKey];
							$arFiles[$key] = $value;
						}
						
						$GetParamsType = explode(',',$arParams['PARAMS']['FILES_TYPE']);
						
						$i=0;
						// Формируем нормальный массив с проверкой на разрешённый формат
						foreach($arFiles['name'] as $item){
							
							$name = $arFiles['name'][$i];
							$format =  pathinfo($name, PATHINFO_EXTENSION);
							
							if(!empty($format) && in_array($format,$GetParamsType)){
								$arFormFile[] = array(
									'name' => $name,
				                    'type' => $arFiles['type'][$i],
				                    'tmp_name' => $arFiles['tmp_name'][$i],
				                    'error' => $arFiles['error'][$i],
				                    'size' => $arFiles['size'][$i],
								);
							}
							$i++;
						}
						
						// Если после чистки файлы остались, сохраняем. 
						if(isset($arFormFile) && is_array($arFormFile)){
							
							if ( ! function_exists( 'wp_handle_upload' ) ) 
								require_once( ABSPATH . 'wp-admin/includes/file.php' );  
								
							// Прои	
							foreach($arFormFile as $arFile){
								
								$_FILES = $arFile;
								$saveFile = wp_handle_upload($_FILES,['test_form' => false])['file'];
								
								if (empty($saveFile['error']) ) {
									$arParams['FILES_SEND'][] = $saveFile;
								}else{
									$this->AddLog('При загрузке файла произошла ошибка '.$saveFile['error']."\n");
								}
								
								$saveFile = false;
							}
							
						}

					}
					
					if(!$this->arErrors){

						$arResult['URL']		=	!empty($arResult['URL'])? $arResult['URL'] : $_SERVER['REQUEST_URI'];
						$arResult['FORM_ID']	=	$FORM_ID;
						
						// Дополнительные правила для автозамены в форме
						$arRules[] = array(
							'RULES' => '/#SITE_NAME#/s',
							'VALUE'	=> $arParams['SITE_NAME'],
						);
						$arRules[] = array(
							'RULES' => '/#SERVER_NAME#/s',
							'VALUE'	=> $arParams['HOST'],
						);	
						$arRules[] = array(
							'RULES' => '/#DEFAULT_EMAIL_FROM#/s',
							'VALUE'	=> $arParams['DEFAULT_EMAIL_FROM'],
						);
						$arRules[] = array(
							'RULES' => '/#FORM_NAME#/s',
							'VALUE'	=> $arParams['TEMPLATES']['NAME'],
						);
						$arRules[] = array(
							'RULES' => '/#DATE#/s',
							'VALUE'	=> $arResult['DATE'],
						);
						$arRules[] = array(
							'RULES' => '/#URL#/s',
							'VALUE'	=> $arResult['URL'],
						);

				        // Производим замену данных в шаблоне
						foreach ($arRules as $rule) {
							$this->name			= preg_replace($rule['RULES'], $rule['VALUE'], $this->name);
							$this->title		= preg_replace($rule['RULES'], $rule['VALUE'], $this->title);
							$this->email_from	= preg_replace($rule['RULES'], $rule['VALUE'], $this->email_from);
							$this->massage		= preg_replace($rule['RULES'], $rule['VALUE'], $this->massage);
							$this->email_to		= preg_replace($rule['RULES'], $rule['VALUE'], $this->email_to);
						}
						
						// Создаем массив данных новой записи
					    $arFileds = array(
					    	'post_title'	=>  sanitize_text_field($this->name." ".$arResult['DATE']),
					    	'post_content'	=>  $this->massage,
							'post_author'   =>  1,
							'post_type'		=>  $arParams['SLUG_FORMS_RESULT'],
						);
						
						$ID = wp_insert_post( wp_slash($arFileds) , true );

						// Вставляем запись в базу данных
						if(  !is_wp_error($ID) )
				    	{

							$this->massage	= str_replace('#FORM_RESULT_URL#', $arParams['ADMIN_URL'].'post.php?post='.$ID.'&action=edit' , $this->massage);

							$arFiled = array(
								'ID'    		=> $ID,
								'post_title'	=>  sanitize_text_field($this->name." ".$arResult['DATE']),
								'post_content'	=>  $this->massage,
								'post_type'		=>  $arParams['SLUG_FORMS_RESULT'],
								'post_status'  	=>  'publish',
							);
							wp_insert_post( wp_slash($arFiled) );
							
							// Изменяем формат отправляемого письма
							if($this->type=='html'){
								add_filter( 'wp_mail_content_type', array( $this,'SetHTMLContentType' ));
							}
							
							$headers = 'From: '.$arParams['HOST'].' <'.$this->email_from.'>' . "\r\n";
							
							$this->email_to = array_diff(explode( ',', $this->email_to ), array(''));
							
							if( wp_mail( $this->email_to , $this->title, $this->massage ) ){
								$this->success = $arParams['PARAMS']['EROR_0'];
								
								// Если включено логирование, то сохраняем.
								if(!empty($arParams['PARAMS']['LOGS']) && $arParams['PARAMS']['LOGS']=='Y')
									$this->AddLog('Успешная отправка письма на почту '.implode(',',$this->email_to).'. Заголовок письма '.$this->title."\n");
				    				
							}else{
								$this->arErrors = $arParams['PARAMS']['EROR_6'];
								
								// Если включено логирование, то сохраняем.
								if(!empty($arParams['PARAMS']['LOGS']) && $arParams['PARAMS']['LOGS']=='Y')
					    			$this->AddLog('Ошибка отправки письма на почту '.implode(',',$this->email_to).'. Заголовок письма '.$this->title."\n");
							}
							
							// Удаляем файлы 
							if(isset($arParams['PARAMS']['FILES']) && $arParams['PARAMS']['FILES']=='Y' && isset($arParams['FILES_SEND'])){
								foreach($arParams['FILES_SEND'] as $file){
									wp_delete_file( $file );
								}
							}
							
							// Сбросим content-type, чтобы избежать возможного конфликта
							remove_filter( 'wp_mail_content_type', array( $this,'SetHTMLContentType' ) );
							
				    	}

					} 
							
				} else {
					 $this->arErrors[] = 'Ошибка заполнения формы.';
				}
					 
				/*
				* Выводим ошибки и результат для запросов через AJAX
				*/
				if( wp_doing_ajax() ){	
					echo ShowFormsError();
					echo ShowFormsSuccess();
					wp_die();
				}
				
			}	
		}
		
		/*
		* Добавляем лог 
		*/
		private function AddLog($error)
		{
			file_put_contents($this->arParams['PATH'].'logs/forms_log.php','['.date($this->arParams['DATE_FORMAT']).'] '.$error."\n" , FILE_APPEND);
		}
		
		/*
		* Меняем тип письма
		*/
		public function SetHTMLContentType()
		{
			return 'text/html';
		}

		/*
		* Получаем список записей форм
		*/
		public function Get()
		{
			
			$arParams = $this->arParams;

			$arRequest = get_posts( 
				array(
					'numberposts' => 5,
					'category'    => 0,
					'orderby'     => 'date',
					'order'       => 'DESC',
					'include'     => array(),
					'exclude'     => array(),
					'meta_key'    => '',
					'meta_value'  =>'',
					'post_type'   => $arParams['SLUG_FORMS_RESULT'],
					'suppress_filters' => true,
				));
				
			if(isset($arRequest)){
				foreach($arRequest as $arItems){
					$arResult[]=array(
						'ID'=>$arItems->ID,
						'NAME'=>$arItems->post_title,
						//'DETAIL_TEXT'=>$arItems->post_content,
						'DATE'=>$arItems->post_date,
						'DETAIL_PAGE'=>'/wp-admin/post.php?post='.$arItems->ID.'&action=edit',
						'URL'=>$arItems->guid,
						);
				}
				
				return $arResult;
			}
			
		}
		
		/*
		* Отображение страницу настройки форм
		*/
		public function ShowPageForms()
		{

			$arParams = $this->arParams;
			
			if(!isset($arParams['PARAMS']['MAIL_TEMPLATES'])){
				global $APPLICATION;
				$APPLICATION->ShowNotices('Необходимо добавить хотя бы 1 почтовый шаблон. Иначе компонент не будет обрабатывать и отправлять результаты. <a href="?page=iks-forms#2" target="_blank">Нажмите сюда</a> что бы задать правильные ключи.','notice-error');	
			}
		?>
			<form method="post" enctype="multipart/form-data" action="options.php">
				<?php 
		    	$s_name = $arParams['SETTINGS_NAME'];
		    	settings_fields($s_name); 
		    	?>
					<div class="adm-detail-content-wrap active">
						<div class="adm-detail-content">
							<div class="adm-detail-title">Настройка параметров e-mail и СМС</div>
					
							<div class="adm-detail-content-item-block">
								<table class="adm-detail-content-table edit-table">
									<tbody>
										<tr class="heading">
											<td colspan="2">Основные настройки</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Позволяет включать/отключать работу отдельных компонентов.">
													<span class="type-3"></span>
												</div>
												Активность   
											</td>
											<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[ACTIVE]" type="checkbox" <?php if($arParams['PARAMS']['ACTIVE']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox"></td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Позволяет включить ведение логов по отправке сообщений с сайта.">
													<span class="type-3"></span>
												</div>
												Логировать отправку   
											</td>
											<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[LOGS]" type="checkbox" <?php if(!empty($arParams['PARAMS']['LOGS']) && $arParams['PARAMS']['LOGS']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox"></td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите email с которого будет идти отправка сообщений.">
													<span class="type-3"></span>
												</div>
												От кого
											</td>
											<td class="adm-detail-content-cell-r">
												<input required class="regular-text" type="email" name="<?php echo $s_name?>[EMAIL_TO]" value="<?php echo esc_html($arParams['PARAMS']['EMAIL_TO']);?>">
											</td>
										</tr>
										
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Строка необходима для защиты ваших форм от спама и ботов. Является солью при шифровании данных.">
													<span class="type-1"></span>
												</div>
												Хэш форм
											</td>
											<td class="adm-detail-content-cell-r" style="position: relative;">
												<input required class="regular-text forms-hach" type="text" name="<?php echo $s_name?>[FORM_HACH]" value="<?php echo esc_html($arParams['PARAMS']['FORM_HACH']);?>">
												<div class="hach-repit" onclick="hachupdate('.forms-hach');"></div>
											</td>
										</tr>
										
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Функция включает получение и обработку файлов из форм для дальнейшей отправки в сообщениях.">
													<span class="type-3"></span>
												</div>
												Обрабатывать и отправлять файлы   
											</td>
											<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[FILES]" type="checkbox" <?php if(!empty($arParams['PARAMS']['FILES']) && $arParams['PARAMS']['FILES']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox"></td>
										</tr>
										
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите список типов файлов, которые пользователи смогут отправлять вам через форму. По умолчанию указаны все типы файлов разрешённых в WP.">
													<span class="type-3"></span>
												</div>
												Форматы файлов
											</td>
											<td class="adm-detail-content-cell-r" style="position: relative;">
												<textarea required name="<?php echo $s_name?>[FILES_TYPE]" style="width:100%"><?php echo esc_html($arParams['PARAMS']['FILES_TYPE']);?></textarea>
												<br>
												<span class="description">Укажите типы файлов, которые будут оправляться через формы (через запятую). Например: <b>jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx</b>.<br> <b>WP не позволит отправлять запрещённые типы файлов.</b></span>
											</td>
										</tr>

										<tr class="heading">
											<td colspan="2">Защита от спама</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Функция активирует проверку reCAPTCHA от Google при добавление форм.">
													<span class="type-3"></span>
												</div>
												Проверять reCaptcha   
											</td>
											<td class="adm-detail-content-cell-r">
												<input name="<?php echo $s_name?>[RECAPTHA]" type="checkbox" <?php if(!empty($arParams['PARAMS']['RECAPTHA']) && $arParams['PARAMS']['RECAPTHA']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox">
												<a href="/wp-admin/admin.php?page=iks-captha" style="text-decoration: none;">Настройка рекапчи <span class="dashicons dashicons-edit"></span></a>
											</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Если вы не хотите портить вид форм капчей, то вам поможет данный способ. Данное решение защищает от 90% ботов.">
													<span class="type-3"></span>
												</div>
												Проверять на запуск JS в браузере  
											</td>
											<td class="adm-detail-content-cell-r">
												<input name="<?php echo $s_name?>[COOKIE]" type="checkbox" <?php if(!empty($arParams['PARAMS']['COOKIE']) && $arParams['PARAMS']['COOKIE']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox">
												Добавьте на страницу или в форму JS - <b>&lt;script&gt;document.cookie="IKS_CMS_START=Y;path=/";&lt;/script&gt;</b>
											</td>
										</tr>
										
										<tr class="heading">
											<td colspan="2">Текст ошибок/сообщений</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите текст успешного заполнения формы.">
													<span class="type-3"></span>
												</div>
												Успешное заполнение формы
											</td>
											<td class="adm-detail-content-cell-r">
												<input class="regular-text" type="text" name="<?php echo $s_name?>[EROR_0]" value="<?php echo esc_html($arParams['PARAMS']['EROR_0']);?>">
											</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите текст ошибки при неверном кэше.">
													<span class="type-3"></span>
												</div>
												Не пройдена проверка FORM_HACH
											</td>
											<td class="adm-detail-content-cell-r">
												<input class="regular-text" type="text" name="<?php echo $s_name?>[EROR_1]" value="<?php echo esc_html($arParams['PARAMS']['EROR_1']);?>">
											</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите текст ошибки при отсутствие COOKIE.">
													<span class="type-3"></span>
												</div>
												Не пройдена проверка COOKIE "notbot"
											</td>
											<td class="adm-detail-content-cell-r">
												<input class="regular-text" type="text" name="<?php echo $s_name?>[EROR_2]" value="<?php echo esc_html($arParams['PARAMS']['EROR_2']);?>">
											</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите текст ошибки при неправильном или пустом ReCAPTCHA (только если активна функция проверки).">
													<span class="type-3"></span>
												</div>
												Не пройдена проверка ReCAPTCHA
											</td>
											<td class="adm-detail-content-cell-r">
												<input class="regular-text" type="text" name="<?php echo $s_name?>[EROR_3]" value="<?php echo esc_html($arParams['PARAMS']['EROR_3']);?>">
											</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите текст ошибки при незаполненых обязательных полях.">
													<span class="type-3"></span>
												</div>
												Не заполнены обязательные поля
											</td>
											<td class="adm-detail-content-cell-r">
												<input class="regular-text" type="text" name="<?php echo $s_name?>[EROR_4]" value="<?php echo esc_html($arParams['PARAMS']['EROR_4']);?>">
											</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l">
												<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите текст ошибки при сбое срабатывания функции mail() - отправке пиьсма.">
													<span class="type-3"></span>
												</div>
												Сбой в отправке письма
											</td>
											<td class="adm-detail-content-cell-r">
												<input class="regular-text" type="text" name="<?php echo $s_name?>[EROR_5]" value="<?php echo esc_html($arParams['PARAMS']['EROR_5']);?>">
											</td>
										</tr>
									</tbody>
								</table>
							</div> 
							<div class="adm-detail-content-btns">
								<input type="submit" class="iksweb-btn" value="Сохранить">  
							</div>
						</div> 
					</div> 
					
					<div class="adm-detail-content-wrap">
						<div class="adm-detail-content">
							<div class="adm-detail-title">Настройка почтовых шаблонов</div>
							<div class="iksweb-btn add-templates" data-settings="<?php echo $s_name?>">Добавить почтовый шаблон</div>
							<div class="adm-detail-content-item-block">
								<div class="feedback-items">
									<?php
									if(isset($arParams['PARAMS']['MAIL_TEMPLATES'])){
									foreach($arParams['PARAMS']['MAIL_TEMPLATES'] as $ID=>$template){ ?>
									<div class="feedback-item" id="form-templates-<?php echo $ID?>">
										<div class="name" data-id="<?php echo $ID?>" ><?php echo $template['NAME'];?> [ID: <?php echo $ID?>]</div>
										<div class="remove-templates" data-toggle="factory-tooltip" data-placement="left" title="" data-original-title="Удалить почтовый шаблон."></div>
										<div class="open-templates"></div>
										
										<table class="adm-detail-content-table edit-table contents-form">
											<tbody>
												<tr class="heading">
													<td colspan="2">Поля письма</td>
												</tr>
												<tr>
													<td class="adm-detail-content-cell-l">
														<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Название формы для переменной #FORM_NAME#">
															<span class="type-3"></span>
														</div>
														Название формы
													</td>
													<td class="adm-detail-content-cell-r">
														<input required class="regular-text form-name" type="text" name="<?php echo $s_name?>[MAIL_TEMPLATES][<?php echo $ID?>][NAME]" value="<?php echo $template['NAME'];?>">	
													</td>
												</tr>
												<tr>
													<td class="adm-detail-content-cell-l">
														<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Поле позволяет изменять название писем приходящих с сайта.">
															<span class="type-3"></span>
														</div>
														Тема письма
													</td>
													<td class="adm-detail-content-cell-r">
														<textarea required onfocus="window.bxCurrentControl=this" name="<?php echo $s_name?>[MAIL_TEMPLATES][<?php echo $ID?>][TITLE]" style="width:100%">Заявка с сайта #SERVER_NAME# из формы #FORM_NAME# - #DATE#</textarea>
													</td>
												</tr>
												<tr>
													<td class="adm-detail-content-cell-l">
														<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите e-mail для отправки писем почты.">
															<span class="type-3"></span>
														</div>
														Кому
													</td>
													<td class="adm-detail-content-cell-r">
														<input required onfocus="window.bxCurrentControl=this" class="regular-text" type="text" name="<?php echo $s_name?>[MAIL_TEMPLATES][<?php echo $ID?>][EMAIL]" value="<?php echo $template['EMAIL'];?>">
														<br>
														<span class="description">Вы можете указать несколько e-mail для отправки, через запятую</span>
													</td>
												</tr>
												<tr>
													<td class="adm-detail-content-cell-l">
														<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите в поле список обязательных полей, которые будут проверяться при отправке.">
															<span class="type-3"></span>
														</div>
														Обязательные поля
													</td>
													<td class="adm-detail-content-cell-r">
														<input class="regular-text" type="text" name="<?php echo $s_name?>[MAIL_TEMPLATES][<?php echo $ID?>][REQUIRED_FILEDS]" value="<?php echo $template['REQUIRED_FILEDS'];?>" >
														<br>
														<span class="description">Укажите список обязательны полей чез запяту. Например: <b>NAME, EMAIL, PHONE</b></span>
													</td>
												</tr>
												<tr class="heading">
													<td colspan="2">Сообщение</td>
												</tr>
												<tr>
													<td class="adm-detail-content-cell-l">
														<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Функция позволяет создать шаблон для оправки писем на почту.">
															<span class="type-3"></span>
														</div>
														Почтовый шаблон
													</td>
													<td class="adm-detail-content-cell-r">
										                <textarea required onfocus="window.bxCurrentControl=this" name="<?php echo $s_name?>[MAIL_TEMPLATES][<?php echo $ID?>][MESSAGE]" cols="50" rows="10" style="width:100%"><?php echo $template['MESSAGE'];?></textarea>
														<div class="bx-ed-type-selector">
															<label for="bxed_MESSAGE_text">
																<input type="radio" name="<?php echo $s_name?>[MAIL_TEMPLATES][<?php echo $ID?>][BODY_TYPE]" value="text" <? if($template['BODY_TYPE']=='text'){ echo 'checked';} ?>>
																Текст
															</label>
															<label for="bxed_MESSAGE_html">
																<input type="radio" name="<?php echo $s_name?>[MAIL_TEMPLATES][<?php echo $ID?>][BODY_TYPE]" value="html" <? if($template['BODY_TYPE']=='html'){ echo 'checked';} ?>>
																HTML
															</label>
														</div>
													</td>
												</tr>
											</tbody>	
										</table>
									</div>	
									<?php	
									}
									}else{
									
									echo '<div style="align-items: center;display: flex;">У вас нет шаблонов форм. <div class="iksweb-btn add-templates" data-settings="FORMS_SETTINGS" style="top: 0;margin:0 0 0 20px">Добавить почтовый шаблон</div></div>';
									
									}?>
									<hr>
									<table class="adm-detail-content-table edit-table">
										<tbody>
											<tr class="heading">
												<td colspan="2">Операторы</td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Укажите в поле список обязательных полей, которые будут проверяться при отправке.">
														<span class="type-3"></span>
													</div>
													Почтовые операторы
												</td>
												<td class="adm-detail-content-cell-r">
									                <div class="description">
													<div class="oparator" onclick="PutString('#SITE_NAME#');">#SITE_NAME#</div> - Название сайта (устанавливается в настройках)<br>
													<div class="oparator" onclick="PutString('#SERVER_NAME#');">#SERVER_NAME#</div> - Домен сайта (устанавливается в настройках)<br>
													<div class="oparator" onclick="PutString('#DEFAULT_EMAIL_FROM#');">#DEFAULT_EMAIL_FROM#</div> - E-Mail администратора сайта (из настроек главного модуля)<br>
													<div class="oparator" onclick="PutString('#FORM_RESULT_URL#');">#FORM_RESULT_URL#</div> - Ссылка на заявку в админке<br>
													<div class="oparator" onclick="PutString('#FORM_RESULT_URL#');">#FORM_NAME#</div> - Название формы<br>
													<div class="oparator" onclick="PutString('#URL#');">#URL#</div> - С какой страницы отправили письмо<br>
													<div class="oparator" onclick="PutString('#DATE#');">#DATE#</div> - Дата отправки<br>
													<p>Вы можете указывать любые операторы из ваших форм вида <b>#NAME#</b> - где <b>NAME</b> это название переменной в форме: <code style="margin:5px 0">&#60;input name="FORMS[2][<b>NAME</b>]" type="text"></code>
													<br>а 2 - ID почтового шаблона.</p>
													</div>
												</td>
											</tr>
										</tbody>	
									</table>
								
								</div> 

								<div class="adm-detail-content-btns">
									<input type="submit" class="iksweb-btn update-templates" value="Сохранить">  
								</div>
							</div>
						</div>
					</div>	
					</form>	
					
					<div class="adm-detail-content-wrap">
						<div class="adm-detail-content">
							<div class="adm-detail-title">Логирование отправок писем - информация из файла видна только администраторам</div>
					
							<div class="adm-detail-content-item-block">
							<?php
							
							global $APPLICATION;
							
							$DebugFile  = $arParams['PATH'].'logs/forms_log.php';
							
							if(!empty($arParams['PARAMS']['LOGS']) && $arParams['PARAMS']['LOGS']=='Y'){
								
							$Debug	 = $APPLICATION->GetFile($DebugFile);
							$arDebug = array_filter(array_map('trim', explode("\n",$Debug)), 'strlen');
							$arDebug = array_unique($arDebug);	
							
							if(is_array($arDebug)){
									if(isset($_GET['debug']) && intval($_GET['debug'])=='1'){
										file_put_contents($DebugFile, '');
										$APPLICATION->ShowNotices('Логи сайта очищены.');	
										// Делаем редирект на эту же страницу, что бы обновить показатели
										?>
										<script> (function($){ setTimeout(function(){ window.location.href = '?page=iks-forms#3'; }, 300); })(jQuery); </script>
										<?php
									}
									
									echo '<a href="?page=iks-forms&debug=1#2" class="iksweb-btn">Очистить логи</a>';
									echo '<div style="max-height: 500px; overflow-x: auto; margin: 30px 0;">';
									
									foreach($arDebug as $item){
										echo '<p>'.$item.'</p>'."<hr>";
									}
									
									echo '</div>';
									
									echo '<a href="?page=iks-forms&debug=1#2" class="iksweb-btn">Очистить логи</a>';
									
								}else{
									
									echo 'Файл с ошибками - debug.log - пуст. Поздравляем!';
									
								}
								
							}else echo "<p>Логирование отправки сообщений отключено.</p>";
							?>
							</div> 

							<div class="adm-detail-content-btns">
								<input type="submit" class="iksweb-btn iksres-btn" value="Очистить логи">  
							</div>
						</div>
					</div>
				</div>
				<br><br>
				<div class="tabs"> 
					<ul class="adm-detail-tabs-block">
						<li class="adm-detail-tab active" data-id="4">Подключение</li>
						<li class="adm-detail-tab" data-id="5">Примеры форм</li>
					</ul>
					
					<div class="adm-detail-content-wrap active">   
						<div class="adm-detail-content">
							<div class="adm-detail-content-item-block">
								<h3>Добавление полей для формы</h3>
								<p><b>FORMS[#][PHONE]</b> - <b>#</b> - ID почтового шаблона сайта. При отсутствие или 0 значение принимает значение базового почтового шаблона.</p>
								<p><b>FORMS[#][NAME]</b> - <b>NAME</b> - необходимо будет указать в шаблоне отправки сообщений в виде <b>#NAME#</b> - значение при отправке будет автоматически заменено.</p>
								<br>
								<h3 style="color: #ef0000;">Обязательные поля для формы</h3>
								<code style="margin:5px 0">&lt;input name="FORMS[#][<b>FORM_HACH</b>]" type="hidden" value="&lt;?=GetFormsHach();?&gt;" &gt;</code>
								<p><b>FORM_HACH</b> - Закодированная строка для защиты формы. Вместо <b># укажите ID формы</b> (он должен совпадать у всех полей 1 формы и отличаться у разных форм).</p>
								<br>
								
								<h3>Вывод данных шоткодами</h3>
								<p><b>[RECAPTHA]</b> - Используется для вывода reCaptcha (проверяется, если включена опция выше).</p>
								<p><b>[FORMS_HACH]</b> - Выведет зашифрованный ключ в форму в формате input.</p>
								<p><b>[FORMS_SUCCESS]</b> - Выведет положительный результат формы в случае успеха.</p>
								<p><b>[FORMS_ERROR]</b> - Выведит ошибки формы.</p>
								<p>Рекомендуется использовать данные шоткоды исключительно внутри области контента. В шаблонах и обработчиках используйте PHP аналоги.</p>
								
								<br>
								<h3>Вывод данных на PHP</h3>
								<p><b>ShowReCaptha();</b> - аналог [RECAPTHA].</p>
								<p><b>GetFormsHach();</b> - аналог [FORMS_HACH]. Выведет код хэша формы.</p>
								<p><b>GetFormsValue('NAME');</b> - позволяет вывести содержимое поля, которые было заполнено. (Вместо # укажите name поля - FORMS[1][#])</p>
								<p><b>ShowFormsSuccess();</b> - аналог [FORMS_SUCCESS].</p>
								<p><b>ShowFormsError();</b> - аналог [FORMS_ERROR].</p>
								<br>
							
								<h3>Подключение обработчика через AJAX</h3>
								<p>Комопонент также принимает и обрабатывает данные формы через AJAX. Для подключения AJAX необходимо отправлять запросы формы в формате POST или GET на <b>action="/wp-admin/admin-ajax.php"</b></p>
								<p>Если будете отправлять форму через AJAX не забудьте добавить в форму после с </p>
								<code style="margin:5px 0">&lt;input name="FORMS[#][<b>URL</b>]" type="hidden" value="&lt;?=explode('?', $_SERVER['REQUEST_URI'])[0];?&gt;" &gt;</code>
								<p>в противном случае при формирование шаблонов писяма вместо #URL# будет вставляться /wp-admin/admin-ajax.php.</p>
								<br>
							</div> 	
						</div> 	
					</div> 
					
					<div class="adm-detail-content-wrap ">
						<div class="adm-detail-content">
							<div class="adm-detail-content-item-block">
							     <h3>Пример формы встраиваемой в контент страницы</h3>
							     <textarea cols="50" rows="10" style="width:100%">
&#60;form action="#" method="POST">
	&#60;div class="form-result"> [FORMS_SUCCESS] &#60;/div>
	&#60;div class="form-error"> [FORMS_ERROR] &#60;/div>
	
	[FORMS_HACH]
	
	&#60;input name="FORMS[0][NAME]" type="text" placeholder="Ваше имя *" value="">
	&#60;input name="FORMS[0][PHONE]" type="text" placeholder="Ваш телефон" value="">
	&#60;input name="FORMS[0][EMAIL]" type="text" placeholder="Ваш E-mail *" value="">
	&#60;textarea name="FORMS[0][MESSAGE]" placeholder="Сообщение">&#60;/textarea>
	[RECAPTHA]
	&#60;script>document.cookie="IKS_CMS_START=Y;path=/";&#60;/script>
    &#60;input type="submit" value="Отправить">
&#60;/form></textarea>
							     <h3>Пример формы без AJAX</h3>
							     <textarea cols="50" rows="10" style="width:100%">
&#60;form action="#" method="POST">
	&#60;div class="form-result"> &#60;?=ShowFormsSuccess();?> &#60;/div>
	&#60;div class="form-error"> &#60;?=ShowFormsError();?> &#60;/div>
	
	&#60;input type="hidden" name="FORMS[1][FORM_HACH]" value="&#60;?=GetFormsHach();?>">		

	&#60;input name="FORMS[1][NAME]" type="text" placeholder="Ваше имя *" value="&#60;?=GetFormsValue('NAME');?>">
	&#60;input name="FORMS[1][PHONE]" type="text" placeholder="Ваш телефон" value="&#60;?=GetFormsValue('PHONE');?>">
	&#60;input name="FORMS[1][EMAIL]" type="text" placeholder="Ваш E-mail *" value="&#60;?=GetFormsValue('EMAIL');?>">
	&#60;textarea name="FORMS[1][MESSAGE]" placeholder="Сообщение"> &#60;?=GetFormsValue('MESSAGE');?>  &#60;/textarea>
	&#60;?=ShowReCaptha();?>
	&#60;script>document.cookie="IKS_CMS_START=Y;path=/";&#60;/script>
    &#60;input type="submit" value="Отправить">
&#60;/form></textarea>

 <h3>Пример формы с отправкой файлов</h3>
							     <textarea cols="50" rows="10" style="width:100%">
&#60;form action="#" method="POST" enctype="multipart/form-data">
	&#60;div class="form-result"> &#60;?=ShowFormsSuccess();?> &#60;/div>
	&#60;div class="form-error"> &#60;?=ShowFormsError();?> &#60;/div>
	
	&#60;input type="hidden" name="FORMS[2][FORM_HACH]" value="&#60;?=GetFormsHach();?>">		

	&#60;input name="FORMS[2][NAME]" type="text" placeholder="Ваше имя *" value="&#60;?=GetFormsValue('NAME');?>">
	&#60;input name="FORMS[2][PHONE]" type="text" placeholder="Ваш телефон" value="&#60;?=GetFormsValue('PHONE');?>">
	&#60;input name="FORMS[2][EMAIL]" type="text" placeholder="Ваш E-mail *" value="&#60;?=GetFormsValue('EMAIL');?>">
	&#60;textarea name="FORMS[2][MESSAGE]" placeholder="Сообщение"> &#60;?=GetFormsValue('MESSAGE');?>  &#60;/textarea>
	&#60;?=ShowReCaptha();?>
	&#60;input name="FORMS[2][FILES][]" type="file" multiple accept=".jpg,.jpeg,.png"/>
	&#60;!-- или -->
	&#60;input name="FORMS[2][LOAD_FILES]" type="file" accept="text/plain"/>
	&#60;script>document.cookie="IKS_CMS_START=Y;path=/";&#60;/script>
    &#60;input type="submit" value="Отправить">
&#60;/form></textarea>

								 <h3>Пример формы с AJAX</h3>
							     <textarea cols="50" rows="10" style="width:100%">
&#60;form action="/wp-admin/admin-ajax.php" method="POST" id="forms">
	&#60;div class="form-result">&#60;/div>

	&#60;input type="hidden" name="FORMS[3][FORM_HACH]" value="&#60;?=GetFormsHach();?>">		
	&#60;input name="FORMS[3][URL]" type="hidden" value="&#60;?=explode('?', $_SERVER['REQUEST_URI'])[0];?>" >
	
	&#60;input name="FORMS[3][NAME]" type="text" placeholder="Ваше имя *">
	&#60;input name="FORMS[3][PHONE]" type="text" placeholder="Ваш телефон">
	&#60;input name="FORMS[3][EMAIL]" type="text" placeholder="Ваш E-mail *">
	&#60;textarea name="FORMS[3][MESSAGE]" placeholder="Сообщение"> &#60;/textarea>
	&#60;?=ShowReCaptha();?>
    &#60;input type="submit" value="Отправить" data-send="#forms">
&#60;/form>
&#60;script>
jQuery(document).on('click','[data-send]',function() {

	document.cookie="IKS_CMS_START=Y;path=/";
	
	sendMailForm($(this).attr('data-send') , '.form-result' );
	
    return false;
});
function sendMailForm( form, result ){
	$.ajax({
        url: $(form).attr('action'),
        type: "POST",
        data: $(form).serialize(),
        success: function( response ) {
            $( form+' '+result ).html( response );
        }
    });
    return false;
}
&#60;/script></textarea>	  


							 </div> 
						</div> 	 
					</div> 
				</div> 
			</div>
		<?php	
		}
		
		/******************** END *****************/
	} 
	
	// globals
	global $IKSWEB_FORMS;

	// initialize
	if(isset($APPLICATION) ) {
		$IKSWEB_FORMS = new IKSWEB_FORMS;
		$IKSWEB_FORMS->init();
	}

	/*
	* Обработчик и вывод ошибок для форм
	*/
	if (!function_exists('ShowFormsError')) {
		function ShowFormsError(){
			
			global $IKSWEB_FORMS;
		
			if(is_array($IKSWEB_FORMS->arErrors)){
				foreach($IKSWEB_FORMS->arErrors as $error){
					return '<span class="form-error">'.$error.'</span>';
				}
			}
			
		}
	}

	/*
	* Обработчик и вывод результата для форм
	*/
	if (!function_exists('ShowFormsSuccess')) {
		function ShowFormsSuccess(){
			
			global $IKSWEB_FORMS;

			if($success = $IKSWEB_FORMS->success){
				return '<span class="form-success">'.$success.'</span>';
			}
			
		}
	}

	/*
	* Вывод содержимого поля в форму (для форм без AJAX)
	*/
	if (!function_exists('GetFormsValue')) {
		function GetFormsValue($name=false){

			global $IKSWEB_FORMS;

			if(!empty($name) && !is_array($name) && !empty($IKSWEB_FORMS->arResult[$name])){
				
				return	$IKSWEB_FORMS->arResult[$name];
				
			}elseif(!empty($name) && is_array($name) && !empty($IKSWEB_FORMS->arResult[$name['name']])){
				
				return	$IKSWEB_FORMS->arResult[$name['name']];
			}
			
			return false;
		}
	}

	/*
	* Генерируем HACH для форм
	*/
	if (!function_exists('GetFormsHach')) {
		function GetFormsHach(){
			
			global $IKSWEB_FORMS;

			$arParams = $IKSWEB_FORMS->arParams;
			
			return md5($_SERVER['REMOTE_ADDR'].$arParams['PARAMS']['FORM_HACH'].date('d'));
		}
	}

	/*
	* Шорткоды для форм
	*/
	add_shortcode('FORMS_HACH', 'GetFormsHach'); // Выводим кэш формы
	add_shortcode('FORMS_VALUE', 'GetFormsValue'); // Данные формы
	add_shortcode('FORMS_SUCCESS', 'ShowFormsSuccess'); // Положительный ответ формы
	add_shortcode('FORMS_ERROR', 'ShowFormsError'); // Ошибка формы

	/*
	* Запускаем обработчик при запросе
	*/	
	if(isset($_REQUEST['FORMS'])){

		add_action( 'init',  array( $IKSWEB_FORMS, 'Add' ) );
	}
	
}	
?>
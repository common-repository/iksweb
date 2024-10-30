<?php
/*
Author: IKSWEB
Author URI: https://iksweb.ru/
Author Email: info@iksweb.ru
Description: Компонент модуля IKSWEB необходим для транслитерации URL в латиницу
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

if ( !class_exists( 'IKSWEB_CYRL' ) ) {
	
	class IKSWEB_CYRL { 
		
		/** @var array Настройки плагина и компонентов */
		var $arParams = array();
	
		function __construct(){}
		
		/*
		* Запуск компонента
		*/		
	    function init()
	    {

			$SETTINGS_NAME = 'CYRL_SETTINGS';
			
			$arSettings = get_option($SETTINGS_NAME); // Получаем сохранённые данные
			
	    	// Получаем параметры
			$arParams = $this->arParams = array(
					'ACTIVE'		=>		isset( $arSettings['ACTIVE'] ) ?  'Y' : 'N',
					'SETTINGS_NAME' =>		$SETTINGS_NAME,
					'SET_CYRL_PAGE' =>		!empty( $arSettings['SET_CYRL_PAGE'] ) ?  'Y' : 'N',
					'CYRL_404'		=>		!empty( $arSettings['CYRL_404'] ) ?  'Y' : 'N',
					'DISPLAY_FRONTEND'=>	!empty( $arSettings['DISPLAY_FRONTEND'] ) ?  'Y' : 'N',
					'CYRL_FILE'		=>		!empty( $arSettings['CYRL_FILE'] ) ?  'Y' : 'N',
					'CYRL_REGISTER' =>		!empty( $arSettings['CYRL_REGISTER'] ) ?  'Y' : 'N',
					'CYRL_TYPE_FILE'=>		!empty( $arSettings['CYRL_TYPE_FILE'] ) ?  $arSettings['CYRL_TYPE_FILE'] : '',
					'CYRL_USER'		=>		!empty( $arSettings['CYRL_USER'] ) ?  $arSettings['CYRL_USER'] : '',
					'SET_PAGE'		=>		!empty( $arSettings['SET_PAGE'] ) ?  'Y' : 'N',
					'SET_TAG'		=>		!empty( $arSettings['SET_TAG'] ) ?  'Y' : 'N',
					);
			
	        add_action( 'admin_init', array( $this, 'RegisterSettings' ));
	      
		    if($arParams['ACTIVE']=='Y'){
		    	
		    	add_filter('sanitize_file_name',array($this, 'SetFileName'),10,2);
		    	
		    	// Делать редирект на страницу с транслитом
		    	if($arParams['CYRL_404']=='Y'){
		    		
		        	add_action('wp',array( $this,'SetRedirect' ));  
		        	
		    	}
		    	
		    	// Отображать трансит во фронтенде
		    	if($arParams['DISPLAY_FRONTEND']=='Y'){
		    		
		        	$this->StartTranslit();
		        	
		    	} else {
		        	
		            add_action('admin_init',array($this, 'StartTranslit'));
		            
		        }
			}
	    }
	    
	    /*
		* Регистрируем настройки
		*/
	    function RegisterSettings()
	    {
		
	    	// Получаем параметры
			$arParams=$this->arParams;

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
				$valid_input[$k] = trim($v);
			}
			return $valid_input;
		}
		
		/*
		* Выводим страницу настроек
		*/
		public function ShowPageCurl()
		{
			$arParams = $this->arParams;
			
			$this->SetPageURL();
			
		?>	
					<div class="adm-detail-content-wrap active">
						<form method="post" action="options.php">
					    	<?php
					    	$s_name = $arParams['SETTINGS_NAME'];
					    	settings_fields($arParams['SETTINGS_NAME']); 
					    	?>
							<div class="adm-detail-content">
								<div class="adm-detail-title">Параметры транслитерации URL</div>
							
								<div class="adm-detail-content-item-block">
									<table class="adm-detail-content-table edit-table">
										<tbody>
											<tr class="heading">
												<td colspan="2">Основные настройки</td>
											</tr>
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
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Если какой-то из установленных у вас плагинов влияет на траслитерацию постоянных ссылок и файловых имен, включите эту опцию, чтобы наш плагин перезаписывал изменения других плагинов.">
														<span class="type-2"></span>
													</div>
													Принудильная траслитерация
												</td>
												<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[SET_CYRL_PAGE]" type="checkbox" <?php if($arParams['SET_CYRL_PAGE']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox"></td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Если на момент установки плагина у вас были страницы с не преобразованными ссылками, используйте эту опцию, чтобы пользователи, перешедшие по старым ссылкам, были перенаправлены на новые URL на латинице.">
														<span class="type-3"></span>
													</div>
													Перенаправление со старых URL на новые
												</td>
												<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[CYRL_404]" type="checkbox" <?php if($arParams['CYRL_404']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox"></td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Не используйте транслитерацию во внешнем интерфейсе (включите, если есть проблемы во внешнем интерфейсе)">
														<span class="type-3"></span>
													</div>
													Отключить во фронтенде
												</td>
												<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[DISPLAY_FRONTEND]" type="checkbox" <?php if($arParams['DISPLAY_FRONTEND']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox"></td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Функция позволяет изменить текущие настройки транслитерации на сайте.">
														<span class="type-2"></span>
													</div>
													Пользовательские правила
													
												</td>
												<td class="adm-detail-content-cell-r">
									                <textarea name="<?php echo $s_name?>[CYRL_USER]" style="width:100%"><?php echo $arParams['CYRL_USER'];?></textarea>
									                <span class="description">Правила транслитерации необходимо указывать в формате <b>я = ja</b> (Все правили с новой строки!). Достаточно создать только нижний регистр. </span>
									                <span class="description"><b style="color: #ef0000;">ВНИМАНИЕ!</b> Используйте данный функционал только если вас не устраивают текущие настройки.</span>
												</td>
											</tr>
											
											<tr class="heading">
												<td colspan="2">Настройки медиа</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Эта опция работает только для новых файлов медиа библиотеки. Все кириллические имена загружаемых файлов, будут преобразованы в имена с латинскими символами.">
														<span class="type-3"></span>
													</div>
													Конвертировать имена файлов
													
												</td>
												<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[CYRL_FILE]" type="checkbox" <?php if($arParams['CYRL_FILE']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox"></td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Эта функция работает только для новых загружаемых файлов. Пример: File_Name.jpg -> file_name.jpg">
														<span class="type-3"></span>
													</div>
													Преобразовать название файлов в нижний регистр
												
												</td>
												<td class="adm-detail-content-cell-r"><input name="<?php echo $s_name?>[CYRL_REGISTER]" type="checkbox" <?php if($arParams['CYRL_REGISTER']=='Y'){?>checked<?php } ?>  value="Y" class="adm-designed-checkbox"></td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l">
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Вы можете указать список форматов файлов, которые не надо изменять при загрузке на сайт.">
														<span class="type-1"></span>
													</div>
													Исключить расширения файлов
													
												</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[CYRL_TYPE_FILE]" type="text" size="45" value="<?php echo esc_html($arParams['CYRL_TYPE_FILE']);?>">
													<span class="description">Укажите расширения через запятую, без точек. Например: pdf, png, txt</span>
												</td>
											</tr>

											<tr class="heading">
												<td colspan="2">Изменение ранее созданных</td>
											</tr>
																				
											<tr>
												<td class="adm-detail-content-cell-l">
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Преобразует URL всех ранее созданных страниц сайта из https://site.ru/страница/ в https://site.ru/stranica/">
														<span class="type-2"></span>
													</div>
													Страниц  
													
												</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[SET_PAGE]" type="checkbox"  value="Y" class="adm-designed-checkbox">
												</td>
											</tr>
											
											<tr>
												<td class="adm-detail-content-cell-l">
													<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Преобразует URL  всех ранее созданных записей сайта из https://site.ru/запись/ в https://site.ru/zapis/">
														<span class="type-2"></span>
													</div>
													Записей
												</td>
												<td class="adm-detail-content-cell-r">
													<input name="<?php echo $s_name?>[SET_TAG]" type="checkbox"  value="Y" class="adm-designed-checkbox">
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
			</div>
		<?php	
		}
		
		/*
		*  Настройки локализации
		*/
		function SetLocale()
		{

				$locale = get_locale();

				if ($locale == 'ru_RU') {//Русская
						$arLocale = array(
								'А' => 'A', 'а' => 'a', 'Б' => 'B', 'б' => 'b', 'В' => 'V', 'в' => 'v', 'Г' => 'G',
								'г' => 'g', 'Д' => 'D', 'д' => 'd', 'Е' => 'E', 'е' => 'e', 'Ё' => 'Jo', 'ё' => 'jo',
								'Ж' => 'Zh', 'ж' => 'zh', 'З' => 'Z', 'з' => 'z', 'И' => 'I', 'и' => 'i', 'Й' => 'J',
								'й' => 'j', 'К' => 'K', 'к' => 'k', 'Л' => 'L', 'л' => 'l', 'М' => 'M', 'м' => 'm',
								'Н' => 'N', 'н' => 'n', 'О' => 'O', 'о' => 'o', 'П' => 'P', 'п' => 'p', 'Р' => 'R',
								'р' => 'r', 'С' => 'S', 'с' => 's', 'Т' => 'T', 'т' => 't', 'У' => 'U', 'у' => 'u',
								'Ф' => 'F', 'ф' => 'f', 'Х' => 'H', 'х' => 'h', 'Ц' => 'C', 'ц' => 'c', 'Ч' => 'Ch',
								'ч' => 'ch', 'Ш' => 'Sh', 'ш' => 'sh', 'Щ' => 'Shh', 'щ' => 'shh', 'Ъ' => '',
								'ъ' => '', 'Ы' => 'Y', 'ы' => 'y', 'Ь' => '', 'ь' => '', 'Э' => 'Je', 'э' => 'je',
								'Ю' => 'Ju', 'ю' => 'ju', 'Я' => 'Ja', 'я' => 'ja'
						);
				} elseif ($locale == 'uk') {//Украинская
						$arLocale = array(
								'А' => 'A', 'а' => 'a', 'Б' => 'B', 'б' => 'b', 'В' => 'V', 'в' => 'v', 'Г' => 'H',
								'г' => 'h', 'Ґ' => 'G', 'ґ' => 'g', 'Д' => 'D', 'д' => 'd', 'Е' => 'E', 'е' => 'e',
								'Є' => 'Ie', 'є' => 'ie', 'Ж' => 'Zh', 'ж' => 'zh', 'З' => 'Z', 'з' => 'z', 'И' => 'Y',
								'и' => 'y', 'І' => 'I', 'і' => 'i', 'Ї' => 'I', 'ї' => 'i', 'Й' => 'I', 'й' => 'i',
								'К' => 'K', 'к' => 'k', 'Л' => 'L', 'л' => 'l', 'М' => 'M', 'м' => 'm', 'Н' => 'N',
								'н' => 'n', 'О' => 'O', 'о' => 'o', 'П' => 'P', 'п' => 'p', 'Р' => 'R', 'р' => 'r',
								'С' => 'S', 'с' => 's', 'Т' => 'T', 'т' => 't', 'У' => 'U', 'у' => 'u', 'Ф' => 'F',
								'ф' => 'f', 'Х' => 'Kh', 'х' => 'kh', 'Ц' => 'Ts', 'ц' => 'ts', 'Ч' => 'Ch', 'ч' => 'ch',
								'Ш' => 'Sh', 'ш' => 'sh', 'Щ' => 'Shch', 'щ' => 'shch', 'Ь' => '', 'ь' => '', 'Ю' => 'Iu',
								'ю' => 'iu', 'Я' => 'Ia', 'я' => 'ia'
						);
				} elseif ($locale == 'bg' || $locale == 'bg_BG') {//Болгарская
						$arLocale = array(
								'А' => 'A', 'а' => 'a', 'Б' => 'B', 'б' => 'b', 'В' => 'V', 'в' => 'v', 'Г' => 'G',
								'г' => 'g', 'Д' => 'D', 'д' => 'd', 'Е' => 'E', 'е' => 'e', 'Ё' => 'Jo', 'ё' => 'jo',
								'Ж' => 'Zh', 'ж' => 'zh', 'З' => 'Z', 'з' => 'z', 'И' => 'I', 'и' => 'i', 'Й' => 'J',
								'й' => 'j', 'К' => 'K', 'к' => 'k', 'Л' => 'L', 'л' => 'l', 'М' => 'M', 'м' => 'm',
								'Н' => 'N', 'н' => 'n', 'О' => 'O', 'о' => 'o', 'П' => 'P', 'п' => 'p', 'Р' => 'R',
								'р' => 'r', 'С' => 'S', 'с' => 's', 'Т' => 'T', 'т' => 't', 'У' => 'U', 'у' => 'u',
								'Ф' => 'F', 'ф' => 'f', 'Х' => 'H', 'х' => 'h', 'Ц' => 'C', 'ц' => 'c', 'Ч' => 'Ch',
								'ч' => 'ch', 'Ш' => 'Sh', 'ш' => 'sh', 'Щ' => 'Sht', 'щ' => 'sht', 'Ъ' => 'a',
								'ъ' => 'a', 'Ы' => 'Y', 'ы' => 'y', 'Ь' => '', 'ь' => '', 'Э' => 'Je', 'э' => 'je',
								'Ю' => 'Ju', 'ю' => 'ju', 'Я' => 'Ja', 'я' => 'ja'
						);
				} elseif ($locale == 'ge' || $locale == 'ka_GE') {//Georgian
						$arLocale = array(
								'ა' => 'a', 'ბ' => 'b', 'გ' => 'g', 'დ' => 'd', 'ე' => 'e', 'ვ' => 'v',
								'ზ' => 'z', 'თ' => 'th', 'ი' => 'i', 'კ' => 'k', 'ლ' => 'l', 'მ' => 'm',
								'ნ' => 'n', 'ო' => 'o', 'პ' => 'p','ჟ' => 'zh','რ' => 'r','ს' => 's',
								'ტ' => 't','უ' => 'u','ფ' => 'ph','ქ' => 'q','ღ' => 'gh','ყ' => 'qh',
								'შ' => 'sh','ჩ' => 'ch','ც' => 'ts','ძ' => 'dz','წ' => 'ts','ჭ' => 'tch',
								'ხ' => 'kh','ჯ' => 'j','ჰ' => 'h'
						); 
				};
				
				//Глобальная локализация 
				$arLocale = $arLocale + array(
						'А' => 'A', 'а' => 'a', 'Б' => 'B', 'б' => 'b', 'В' => 'V', 'в' => 'v', 'Г' => 'G',
						'г' => 'g', 'Д' => 'D', 'д' => 'd', 'Е' => 'E', 'е' => 'e', 'Ё' => 'Jo', 'ё' => 'jo',
						'Ж' => 'Zh', 'ж' => 'zh', 'З' => 'Z', 'з' => 'z', 'И' => 'I', 'и' => 'i', 'Й' => 'J',
						'й' => 'j', 'К' => 'K', 'к' => 'k', 'Л' => 'L', 'л' => 'l', 'М' => 'M', 'м' => 'm',
						'Н' => 'N', 'н' => 'n', 'О' => 'O', 'о' => 'o', 'П' => 'P', 'п' => 'p', 'Р' => 'R',
						'р' => 'r', 'С' => 'S', 'с' => 's', 'Т' => 'T', 'т' => 't', 'У' => 'U', 'у' => 'u',
						'Ф' => 'F', 'ф' => 'f', 'Х' => 'H', 'х' => 'h', 'Ц' => 'C', 'ц' => 'c', 'Ч' => 'Ch',
						'ч' => 'ch', 'Ш' => 'Sh', 'ш' => 'sh', 'Щ' => 'Shh', 'щ' => 'shh', 'Ъ' => '',
						'ъ' => '', 'Ы' => 'Y', 'ы' => 'y', 'Ь' => '', 'ь' => '', 'Э' => 'Je', 'э' => 'je',
						'Ю' => 'Ju', 'ю' => 'ju', 'Я' => 'Ja', 'я' => 'ja', 'Ґ' => 'G', 'ґ' => 'g', 'Є' => 'Ie',
						'є' => 'ie', 'І' => 'I', 'і' => 'i', 'Ї' => 'I', 'ї' => 'i', 'ა' => 'a', 'ბ' => 'b', 
						'გ' => 'g', 'დ' => 'd', 'ე' => 'e', 'ვ' => 'v', 'ზ' => 'z', 'თ' => 'th', 'ი' => 'i', 
						'კ' => 'k', 'ლ' => 'l', 'მ' => 'm', 'ნ' => 'n', 'ო' => 'o', 'პ' => 'p','ჟ' => 'zh',
						'რ' => 'r','ს' => 's', 'ტ' => 't','უ' => 'u','ფ' => 'ph','ქ' => 'q','ღ' => 'gh',
						'ყ' => 'qh', 'შ' => 'sh','ჩ' => 'ch','ც' => 'ts','ძ' => 'dz','წ' => 'ts','ჭ' => 'tch',
						'ხ' => 'kh','ჯ' => 'j','ჰ' => 'h'
				);

				return $this->GetCustomRules() + $arLocale;
		}
	    
		/*
		*  Преобразуем кастомные правила
		*/
		function GetCustomRules()
		{
			
			$arParams = $this->arParams;
		
			$arRequest = array_filter(array_map('trim', explode("\n",$arParams['CYRL_USER'])), 'strlen');
			$arRequest = array_unique($arRequest);
		
			$arResult = array();
			foreach ($arRequest as $value) {

				$value = trim($value);
				
				if (empty($value) || $value == '=') {
					continue;
				}
				
				$tmp = explode('=', $value);

				if (strlen($tmp[1]) > 0) {
					$arResult[mb_strtoupper($tmp[0])] = mb_strtoupper($tmp[1][0]).substr($tmp[1], 1);
				} else {
					$arResult[mb_strtoupper($tmp[0])] = $value;
				}

			}

			return $arResult;
		}

		/*
		*  Транслитерация в БД
		*/
		protected function SetBDTranslit($table,$id,$name)
		{
			
				global $wpdb;
				
				$rez = $wpdb->get_results("SELECT {$id}, {$name} FROM {$table} WHERE 1",ARRAY_A);
				
				foreach ($rez as $value) {
						$tmp_name = $this->SetTranslit(urldecode($value[$name]));
						if ($tmp_name != $value[$name]) {
								$wpdb->update($table,array($name=>$tmp_name),array($id=>$value[$id]));
						}
				}
				
		}
		
		/*
		*  Изменяем транслитерацию для ранее созданых страниц и записей в БД
		*/
		protected function SetPageURL()
		{
			
			global $wpdb;
			
			$arParams = $this->arParams;
			
			if ($arParams['SET_PAGE']=='Y'){
							$this->SetBDTranslit($wpdb->posts, 'ID', 'post_name');
					}
					
					if ($arParams['SET_TAG']=='Y'){
							$this->SetBDTranslit($wpdb->terms, 'term_id', 'slug');
					}
		}
	    
		/*
		*  Процедура преобразования символов
		*/
		public function SetTranslit($title,$UseSpecSimbol=TRUE) 
		{
			
			$arParams = $this->arParams;
			
			$arRequest = array_filter( array_map('trim', explode(",", $arParams['CYRL_TYPE_FILE'])), 'strlen' );
		$arRequest = array_unique($arRequest);
		
			$type = substr(filter_input(INPUT_POST, 'name'),-3);
			
				if (!empty($type)) {
						if (in_array($type, $arRequest)) {
								return $title;
						}
				}

				$title = strtr($title, $this->SetLocale());
				
				if ($UseSpecSimbol)
				{
						$title = preg_replace("/[^A-Za-z0-9'_\-\.]/", '-', $title);
						$title = preg_replace('/\-+/', '-', $title);
						$title = preg_replace('/^-+/', '', $title);
						$title = preg_replace('/-+$/', '', $title);
				}
				
				return $title;
		}
		
		/*
		*  Принудительная процедура преобразования символов
		*/
	    public function ForseTranslit($title, $raw_title)
	    {
	        return sanitize_title_with_dashes($this->SetTranslit($raw_title));
	    }
  
		/*
		*  Попытка транслитерировать URL
		*/
		public function SetRedirect()
		{

					$thisurl = $_SERVER['REQUEST_URI']; 
					$trurl = $this->SetTranslit(urldecode($thisurl),FALSE);
					
					if ($thisurl !== $trurl && empty($_GET['s'])) {
							wp_redirect($trurl,301);
					}
		}
		
		/*
		*  Обработка имён зашружаемых файлов
		*/
		public function SetFileName($value, $filename_raw)
		{
			$arParams = $this->arParams;
			
				if ($arParams['CYRL_FILE']=='Y'){
						$value = $this->SetTranslit($value);
				}
				
				//Переводим наименования файлов в нижний регистр
				if ($arParams['CYRL_REGISTER']=='Y') {
						$value = strtolower($value);
				}

				return $value; 
		}
	
		/*
		*  Инициализация метода транслитерации
		*/
		function StartTranslit()
		{
			$arParams = $this->arParams;
			
				if ($arParams['SET_CYRL_PAGE']=='Y') {
					
						add_filter('sanitize_title', array($this,'ForseTranslit'), 25, 2);
						
				} else {
					
						add_filter('sanitize_title', array($this,'SetTranslit'), 0);
						
				}
				
		}

	} 
	
	global $IKSWEB_CYRL;

	// initialize
	if(isset($APPLICATION) ) {
		$IKSWEB_CYRL = new IKSWEB_CYRL;
		$IKSWEB_CYRL->init();
	}

}	
?>
/*
* Author URI: https://iksweb.ru/
* Author: Сергей Князев
*/
(function($){
	
	$("ul.adm-detail-tabs-block").on("click", "li:not(.active)", function() {
      $(this)
        .addClass("active")
        .siblings()
        .removeClass("active")
        .closest("div.tabs")
        .find("div.adm-detail-content-wrap")
        .removeClass("active")
        .eq($(this).index())
        .addClass("active");
        
        history.pushState(null, null, "#"+$(this).attr('data-id'));	
    });

    if(window.location.hash) {
    	
		var hash = $('ul.adm-detail-tabs-block li[data-id="'+window.location.hash.split('#')[1]+'"]');
		
		hash
		  .addClass("active")
		  .siblings()
		  .removeClass("active")
		  .closest("div.tabs")
		  .find("div.adm-detail-content-wrap")
		  .removeClass("active")
		  .eq(hash.index())
		  .addClass("active");
	}
    
	$('.SelectAllRows').click(function(){
		if ($(this).is(':checked')){
			$('table input:checkbox').prop('checked', true);
		} else {
			$('table input:checkbox').prop('checked', false);
		}
	});
	
	$('.AllRows').click(function(){
		var table=$(this).attr('data-table');
		if ($(this).is(':checked')){
			$(table+' input:checkbox').prop('checked', true);
		} else {
			$(table+' input:checkbox').prop('checked', false);
		}
	});
	
	jQuery(document).on('click','[data-massage]',function() {
	    $($(this).attr("href")+' span').html($(this).attr("data-massage"));
	});
	
	/* MODAL POOP */
	jQuery(document).on('click','a[data-modal=open]',function() {
		
		$('.modal').remove();
		
		var app = '';
		app += '<div class="modal active">';
		app += '	<div class="modal-content" id="modal-content">';
		app += '		<div class="modal-head">';
		app += '			<div class="modal-name"></div>';
		app += '			<div class="modal-close" data-modal="close"></div>';
		app += '		</div>';
		app += '		<div class="modal-dialog-br"><div class="modal-dialog"></div></div>';
		app += '	</div>';
		app += '	<div class="modal-shadow" data-modal="close"></div>';
		app += '</div>';
		
	    $('body').append(app);
	    
		var name = $(this).attr("data-modal-name");
		if(name){$('.modal .modal-name').html(name)}
		
		var message = $(this).attr("data-modal-message");
		if(message){$('.modal .modal-dialog').html(message)}
		
		var href = $(this).attr("href");
		$(href).clone(true).unwrap().appendTo('.modal .modal-dialog');
	    
	    return false;
	});	

	jQuery(document).on('click','.modal [data-modal=close]',function() {
	    $('.modal').remove();
	});
	
    $('#wpfooter').append('<div class="footer-iksweb"><div class="iksweb"><p><a href="//iksweb.ru/" target="_blank">© IKSWEB, 2022</p><p><a href="//iksweb.ru/plugins/support/" class="support" target="_blank">Техподдержка</a></p></div></div>');
	
	$('.feedback-items .feedback-item .name').click(function(){
		
		var $this = $(this).closest("div.feedback-item");
		
		if($this.hasClass('active')){
			$this.removeClass('active');
		}else{
			$this.addClass('active');
		}
		
		return false;
	});
	
	$(".feedback-items .form-name").keypress(function (e)  { 
		
    	var new_name = $(this).val();
    	var $this = $(this).closest("div.feedback-item").find('.name');
    	var id = $this.attr('data-id');
    	
        $this.html(new_name+" [ID: "+id+"]"); 
    });
    
    $(".feedback-items .feedback-item .remove-templates").click(function(){ 
    	var $this = $(this).closest("div.feedback-item");
    	
        $this.remove(); 
        
        return false;
    });
    
    $(".add-templates").click(function(){ 
    	
    	var settings_name = $(this).attr('data-settings');
    	var id = $('.feedback-items .feedback-item').length;

    	var data = '<input type="hidden" class="feedback-item" name="'+settings_name+'[MAIL_TEMPLATES]['+id+'][NAME]" value="Новый шаблон"/>'+
    	'<input type="hidden" name="'+settings_name+'[MAIL_TEMPLATES]['+id+'][TITLE]" value="Заявка с сайта #SERVER_NAME# из формы #FORM_NAME# - #DATE#"/>'+
    	'<input type="hidden" name="'+settings_name+'[MAIL_TEMPLATES]['+id+'][EMAIL]" value="#DEFAULT_EMAIL_FROM#"/>'+
    	'<input type="hidden" name="'+settings_name+'[MAIL_TEMPLATES]['+id+'][REQUIRED_FILEDS]" value="EMAIL, PHONE"/>'+
    	'<input type="hidden" name="'+settings_name+'[MAIL_TEMPLATES]['+id+'][MESSAGE]" value="Здравствуйте, на сайте #SITE_NAME# заполнили форму #FORM_NAME# \n\n Информация из формы: \n Имя: #NAME# \n Телефон: #PHONE# \n E-mail: #EMAIL# \n\n Форму заполнили на странице: #URL# \n Дата и время: #DATE# \n\n Вы можете посмотреть заявку на сайте по ссылке #FORM_RESULT_URL# \n\n =============== \n Сообщение сгенерировано автоматически."/>'+
    	'<input type="hidden" name="'+settings_name+'[MAIL_TEMPLATES]['+id+'][BODY_TYPE]" value="text"/>';
    	
    	$('.feedback-items').append(data);
    	
	    setTimeout(function(){
		  $('.update-templates').trigger('click');
		}, 100);
	    
	    return false;
    });
	
})(jQuery);


window.bxCurrentControl = null;
function PutString(str)
{
	if(window.bxCurrentControl)
	{
		window.bxCurrentControl.value += str;
	}
}
					
function hachupdate(input,len = 32 ){
	
	var ints =[0,1,2,3,4,5,6,7,8,9]; 
    var chars=['a','b','c','d','e','f','g','h','j','k','l','m','n','o','p','r','s','t','u','v','w','x','y','z','I','K','S','W','E','B'];
    var out='';
    for(var i=0;i<len;i++){
        var ch=Math.random(1,2);
        if(ch<0.5){
           var ch2=Math.ceil(Math.random(1,ints.length)*10);
           out+=ints[ch2];
        }else{
           var ch2=Math.ceil(Math.random(1,chars.length)*10);
           out+=chars[ch2];            
        }
    }
    
	jQuery(input).val(out);
	
	return false;
}


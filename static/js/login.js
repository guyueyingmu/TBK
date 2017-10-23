$().ready(function(){
	//$('body').css({height:window.screen.availHeight});

	$('.loginForm .loginTitle strong').click(function(){
		if ($(this).hasClass('on')) {
			return false;
		};
		$('.loginForm .loginTitle strong.on').removeClass('on');
		$(this).addClass('on');
		var applyText=$(this).hasClass('agency')?'申请代理':'申请商家';
		$('.loginFooter .regist a').text(applyText);
	});

	$('input[name=name]').blur(function(){
		var name=$.trim($(this).val());
		if(name==''){
			$('#loginMsg').html('请输入用户名');
		}
	});

	$('input[name=pwd]').blur(function(){
		var pwd=$.trim($(this).val());
		if(pwd==''){
			$('#loginMsg').html('请输入密码');
		}
	});

	$(document).on('blur','input[name=verifyCode]',function(){
		var verifyCode=$.trim($(this).val());
		if(verifyCode==''){
			$('#loginMsg').html('请输入验证码');
		}
	});

	$(document).on('click','.changeVerifyCode',function(){
		var verifyCodeUrl='https://mp.weixin.qq.com/cgi-bin/verifycode?username'+$.trim($('input[name=name]').val())+'&r='+(new Date()).getTime();
		$('img.verifyCode').attr('src',verifyCodeUrl);
		return false;
	});

	$('#login').click(function(){
		var postData=getPostData();
		$('#loginMsg').html(postData.msg);
		if(!postData.success){
			return false;
		}

		var submitUrl=$('#submitUrl').val();
		$('#loginMsg').html('正在登陆，请稍等……');
		var loginingHeight=parseInt($('.loginContent').css('height'))+5;
		var loginingWidth=parseInt($('.loginContent').css('width'))+20;
		$('.loginContent .logining').css({width:loginingWidth+'px',height:loginingHeight+'px',display:'block'});

		$.post(submitUrl,postData.data,function(data){
			if(data.status){
				window.location.href=data.msg;
			}
			else{
				$('#loginMsg').html(data.msg);
				$('.loginContent .logining').css({display:'none'});
				if(data.ret=='-8'){
					if($('.dvVerifyCode').length==0){
						var strVerifyCode="<div class='loginItem dvVerifyCode' style='margin-top:21px;'>"
							+"<input type='text' name='verifyCode' class='form-control' maxlength='4' />"
							+"<img class='verifyCode changeVerifyCode' src='https://mp.weixin.qq.com/cgi-bin/verifycode?username="+postData.data.name+"&r="+(new Date()).getTime()+"'/>"
							+"<a class='changeVerifyCode' href='javascript:void(0);'>换一张</a>"
							+"</div>";
						$('.dvLogin').before(strVerifyCode);
						$('.loginContent').css({height:'230px'});
					}
				}
			}
		},'json');
	});
});

//获取Post提交的数据
function getPostData(){
	var name=$.trim($('input[name=name]').val());
	var pwd=$.trim($('input[name=pwd]').val());
	var $verifyCode=$('input[name=verifyCode]');

	var result={};

	if(name==''){
		result={sucess:false,msg:'请输入用户名'};
	}
	else if(pwd==''){
		result={sucess:false,msg:'请输入密码'};
	}
	else if($verifyCode.length>0&&$.trim($verifyCode.val())==''){
		result={sucess:false,msg:'请输入验证码'};
	}
	else{
		var data=$verifyCode.length>0?{name:name,pwd:pwd,verifyCode:$.trim($verifyCode.val())}:{name:name,pwd:pwd};
		result={success:true,msg:'请点击确定进行登陆',data:data};
	}
	return result;
}
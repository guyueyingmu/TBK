$.extend({
	alert:function(option){
		// option.info：提示信息
		// option.effect：信息显示时间
		// option.duration：动画执行时间
		// option.callback：提示信息关闭后执行的方法
		if (!option.info){
			throw new Error('提示信息不能为空');
		}
		else if(typeof(option.info.toString())!='string'){
			throw new TypeError('提示信息必须为字符串');
		}
		if(option.duration&&isNaN(option.duration)){
			throw new TypeError('信息显示时间必须为整数');
		}
		var dialogTimeOut=null;
		option.duration=option.duration?parseInt(option.duration):3000;
		$('#dialog').find('span').text(option.info.toString());
		$('#dialog i.glyphicon').addClass('glyphicon-info-sign');
		$('#dialog').dialog({
			autoOpen:true,
			modal:true,
			title:'提示',
			show: {
				effect: 'explode',
				duration: 1000
			},
			hide: {
				effect: 'explode',
				duration: 1000
			},
			open:function(event,ui){
				var $this=$(this);
				dialogTimeOut=setTimeout(function(){
					$this.dialog('close');
				},option.duration);
			},
			close:function(){
				$('#dialog i.glyphicon').removeClass().addClass('glyphicon');
				clearTimeout(dialogTimeOut);
				$(this).dialog('destroy');
				if(typeof(option.callback)!='undefined'){
					option.callback();
				}
			},
			buttons:{
				'确定':function(){
					$(this).dialog('close');
				}
			}
		});
	},
	confirm:function(option){
		// option.info：确认信息
		// option.ok：点击确定时执行的函数
		// option.cancel：点击曲取消时执行的函数
		if(!option.info){
			throw new Error('确认信息不能为空');
		}
		else if(typeof(option.info.toString())!='string'){
			throw new TypeError('提示信息必须为字符串类型');
		}
		if (option.ok&&typeof(option.ok)!='function') {
			throw new TypeError('参数必须是函数')
		}
		if (option.cancel&&typeof(option.cancel)!='function') {
			throw new TypeError('参数必须是函数')
		}
		$('#dialog').find('span').text(option.info.toString());
		$('#dialog i.glyphicon').addClass('glyphicon-question-sign');
		$('#dialog').dialog({
			autoOpen:true,
			modal:true,
			title:'请确认',
			show: {
				effect: 'explode',
				duration: 1000
			},
			hide: {
				effect: 'explode',
				duration: 1000
			},
			close:function(){
				$('#dialog i.glyphicon').removeClass().addClass('glyphicon');
				$(this).dialog('destroy');
			},
			buttons:{
				'确定':function(){
					if(option.ok){
						option.ok();
					}
					$(this).dialog('close');
				},
				'取消':function(){
					if(option.cancel){
						option.cancel();
					}
					$(this).dialog('close');
				}
			}
		});
	},
	count:function(obj){
		var type = typeof(obj);
		if(type=='string'){
			return obj.length;
		}
		else if(type=='object'){
			var cnt=0;
			for(var idx in obj){
				cnt++;
			}
			return cnt;
		}
		return false;
	},
	getDomain:function(){
		return $.trim($('#domain').val());
		var origin=window.location.origin;
		var href=window.location.href;
		var domain=href.substring(0,href.indexOf('index.php')-1);
		return domain;
	},
	getScriptUrl:function(relativePath){
		var url=this.getDomain()+relativePath;
		return url;
	},
	getSubmitUrl:function($obj){
		var root=$.trim($('#root').val());
		var module=$.trim($obj.attr('data-module'));
		var controller=$.trim($obj.attr('data-controller'));
		var action=$.trim($obj.attr('data-action'));
		module=(module=='')?$.trim($('#module').val()):module;
		controller=(controller=='')?$.trim($('#controller').val()):controller;
		action=(action=='')?$.trim($('#action').val()):action;
		return this.getDomain()+root+'/'+module+'/'+controller+'/'+action+'?_'+(new Date()).getTime();
	},
	navGroupShow:function($obj){
		if($obj.find('.glyphicon-chevron-down').length!=0){
			return false;
		}
		$('#navigation .navigationTitle i.glyphicon-chevron-down').switchClass('glyphicon-chevron-down','glyphicon-chevron-right',1000);
		$('#navigation ul.list-group').animate({height:'hide'},1500,'easeOutBounce');
		$obj.find('.glyphicon-chevron-right').switchClass('glyphicon-chevron-right','glyphicon-chevron-down',1000);
		$obj.next('ul.list-group').animate({height:'show'},1500,'easeOutBounce');
		return false;
	},
	navContentShow:function($obj){
		$('#navigation ul.list-group li.curNav').removeClass('curNav');
		$obj.addClass('curNav');

		var url=this.getSubmitUrl($obj);
		var id=$obj.attr('data-id');
		var param=$obj.attr('data-param');
		var data={};
		if(param){
			try{
				data=JSON.parse(param);
			}
			catch(e){
				console.log('JSON.parse()出错');
			}
		}
		if(id){
			data.id=id;
		}

		window.uploadConfig=undefined;
		window.editorConfig=undefined;
		window.editor=undefined;
		//window.uploader=undefined;

		$('#content').hide('blind',{},500,function(){
			$(this).load(url,data,function(){
				$(this).show('blind',{},500,function(){
					if($obj.attr('data-editor')){
						var editorConfig=typeof(window.editorConfig)=='undefined'?{}:window.editorConfig;
						$.createEditor(editorConfig);
					}
					if($obj.attr('data-upload')){
						var uploadConfig=typeof(window.uploadConfig)=='undefined'?{}:window.uploadConfig;
						$.createUploader(uploadConfig);
					}
				});
			});
		});
	},
	contentShow:function(option){
		var url=this.getSubmitUrl(option.obj);
		window.uploadConfig=undefined;
		window.editorConfig=undefined;
		window.editor=undefined;
		//window.uploader=undefined;
		$('#content').hide('blind',{},500,function(){
			$(this).load(url,option.data,function(){
				$(this).show('blind',{},500,function(){
					if(typeof(option.editorConfig)!='undefined'){
						$.createEditor(option.editorConfig);
					}
					if(typeof(option.uploadConfig)!='undefined'){
						$.createUploader(option.uploadConfig);
					}
					if(typeof(option.callback)!='undefined'&&typeof(callback)=='function'){
						callback();
					}
				});
			});
		});
	},
	dialogFormEditShow:function(option){
		//option.width
		//option.title：标题
		//option.editorConfig：富文本编辑器的配置
		//option.uploadConfig：上传的配置
		//option.data：提交的数据
		//option.obj：触发事件的元素的jquery对象
		//option.ok：点击确定时执行的方法
		//option.cancel：点击取消时执行的方法

		var formUrl=$.getSubmitUrl(option.obj);
		$.get(formUrl,option.data,function(info){
			$('#formEdit').html(info).dialog({
				autoOpen:true,
				modal:true,
				width:option.width,
				maxHeight:window.screen.availHeight-100,
				title:option.title,
				show: {
					effect: 'explode',
					duration: 1000
				},
				hide: {
					effect: 'explode',
					duration: 1000
				},
				focus:function(event,ui){
					if(typeof(option.editorConfig)!='undefined'){
						$.createEditor(option.editorConfig);
					}
					if(typeof(option.uploadConfig)!='undefined'){
						$.createUploader(option.uploadConfig);
					}
				},
				close:function(){
					$('#formEdit').empty();
					$(this).dialog('destroy');
				},
				buttons:{
					'确定':function(){
						if(option.ok){
							option.ok();
							$(this).dialog('close');
						}
						else{
							$.dialogFormEditSubmit({form:$('#formEdit form'),dialog:$(this)});
						}
					},
					'取消':function(){
						if(option.cancel){
							option.cancel();
						}
						$(this).dialog('close');
					}
				}
			});
		});
	},
	formCheck:function($form){
		if($.isEmptyObject($form)){
			return false;
		}
		var rst=true;
		$form.find('.mst').each(function(){
			var value=$.trim($(this).val());
			if(value==''){
				var msg=$(this).attr('data-empty-msg');
				msg=msg?msg:'该项不能为空';
				$(this).next('.msg').html('* '+msg);
				rst=false;
				return false;
			}
		});
		return rst;
	},
	dialogFormEditSubmit:function(option){
		//option.form：提交的表单，须有data-module,data-controller,data-action属性
		//option.dialog：编辑form表单的弹框
		if(this.formCheck(option.form)){
			var data=option.form.serialize();
			var submitUrl=this.getSubmitUrl(option.form);
			$.post(submitUrl,data,function(rst){
				if(rst.success){
					if(!$.isEmptyObject(option.dialog)){
						option.dialog.dialog('close');
					}
					else{
						$('#formEdit').dialog('close').empty();
					}
					$.pageReload();
				}
				else{
					rst.msg=typeof(rst.msg=='undefined')||rst.msg==''?'操作失败，请重新执行！':rst.msg;
					$.alert({info:rst.msg});
				}
			},'json');
		}
	},
	formEditSubmit:function(option){
		//option.form：提交的表单，须有data-module,data-controller,data-action属性
		if(this.formCheck(option.form)){
			var data=option.form.serialize();
			var submitUrl=this.getSubmitUrl(option.form);
			$.post(submitUrl,data,function(rst){
				if(rst.success){
					$.pageReload();
				}
				else{
					rst.msg=typeof(rst.msg)=='undefined'||rst.msg==''?'操作失败，请重新执行！':rst.msg;
					$.alert({info:rst.msg});
				}
			},'json');
		}
	},
	itemDelete:function(option){
		// option.info：删除的提示信息。可选，默认为：确定要删除该条记录吗？？
		// option.ok：点击确认后执行的方法。可选，若未定义，则需配置option.obj项
		// option.obj：删除操作的文档元素。须data-module,data-controller,data-action,data-id属性

		// rst.success：操作是否成功
		// rst.msg：提交后的提示信息
		option.info=typeof(option.info)=='undefined'?'确定要删除该条记录吗？？':option.info;
		this.confirm({
			info:option.info,
			ok:function(){
				if(typeof(option.ok)!='undefined'){
					option.ok();
				}
				else{
					if(typeof(option.obj)!='undefined'){
						var url=$.getSubmitUrl(option.obj);
						var id=option.obj.attr('data-id');
						var param=option.obj.attr('data-param');
						var data={};
						if(param){
							try{
								data=JSON.parse(param);
							}
							catch(e){
								console.log('JSON.parse()出错');
							}
						}
						data.id=id;
						$.post(url,data,function(rst){
							if(rst.success){
								var info=rst.msg==''?'操作成功！':rst.msg;
								var callback=function(){
									$.pageReload();
								};
								var alertOption={info:info,callback:callback};
								$.alert(alertOption);
							}
							else{
								var info=rst.msg==''?'操作失败，请重新执行！':rst.msg;
								var alertOption={info:info};
								$.alert(alertOption);
							}
						});
					}
				}
			}
		});
	},
	pagingShow:function(option){
		//option.obj：分页元素的jquery对象。须有data-action和data-page属性
		if(option.obj.hasClass('disabled')){
			return false;
		}
		var url=this.getSubmitUrl(option.obj);
		var page=option.obj.attr('data-page');
		var param=option.obj.attr('data-param');
		var data={};
		if(param){
			try{
				data=JSON.parse(param);
			}
			catch(e){
				console.log('JSON.parse()出错');
			}
		}
		data.page=page;
		$('#content').hide('blind',{},500,function(){
			$(this).load(url,data,function(){
				$(this).show('blind',{},500);
			});
		});
	},
	pageReload:function(){
		//重新加载页面内容
		var $curNav=$('#navigation .list-group li.curNav');
		this.navContentShow($curNav);
	},
	createEditor:function(option){
		//option.element：编辑器的#id或.class选择器
		option.element=typeof(option.element)=='undefined'?'textarea.editor':option.element;
		if($(option.element).length>0){
			if($.type(window.editor)=='undefined'){
				var editorUrl=$.getScriptUrl('/Public/Enterprise/Js/editor.js');
				$.getScript(editorUrl,function(){
					editor.init(option);
				});
			}
			else{
				editor.init(option);
			}
		}
	},
	createUploader:function(option){
		//option.element：上传图片操作元素的#id或.class选择器
		//option.fileName：type=file的元素的name属性
		var option=option?option:{};
		var element=option&&option.element?option.element:'.uploader';
		var fileName=option&&option.fileName?option.fileName:'#file';
		var $element=$(element);
		option.fileName=$(fileName).val();

		if($element.length>0){
			$element.each(function(idx){
				option.element=$(this);
				var maxCount=option.element.attr('data-max-count');
				option.maxCount=typeof(maxCount)=='undefined'?1:parseInt(maxCount);
				option.endPoint=$.getSubmitUrl(option.element);

				if(option.maxCount>1&&idx>option.maxCount){
					option.element.remove();
				}
				else{
					if(typeof(window.uploader)=='undefined'){
						var uploaderUrl=$.getScriptUrl('/Public/Enterprise/Js/uploader.js');
						$.getScript(uploaderUrl,function(){
							uploader.init(option);
						});
					}
					else{
						uploader.init(option);
					}
				}

			});
		}
	},
});

$().ready(function() {
	$('#navigation,#content').css({'min-height':($(window).height()-110)+'px'});

	//加载数据
	var $firstNavGroup=$('#navigation .navigationTitle').first();
	var $firstNav=$('#navigation .list-group').first().find('li').first();
	$.navGroupShow($firstNavGroup);
	$.navContentShow($firstNav);

	//退出
	$('#logout').click(function(){
		//var url=$('#submitUrl').val()+'/'+$(this).attr('data-action');
		var url=$.getSubmitUrl($(this));
		$.post(url,{},function(data){
			if(data.status){
				window.location.reload();
			}
		},'json');
	});

	//导航效果
	$('#navigation .navigationTitle').click(function(){
		$.navGroupShow($(this));
	});

	//导航操作
	$('#navigation .list-group li').click(function(){
		$.navContentShow($(this));
	});

	//默认的删除操作
	$(document).on('click','a.delete',function(){
		$.itemDelete({obj:$(this)});
	});

	//默认的分页操作
	$(document).on('click','.page .pgNavigation',function(){
		$.pagingShow({obj:$(this)});
	});

	//默认编辑操作
	$(document).on('click','a.edit',function(){
		var $this=$(this);
		var param=$this.attr('data-param');
		var data={};
		if(param){
			try{
				data=JSON.parse(param);
			}
			catch(e){
				console.log('JSON.parse()出错');
			}
		}
		var id = $this.attr('data-id');
		if(id){
			data.id=id;
		}
		var callback=$this.attr('data-callback')=='true'?1:undefined;
		var uploadConfig=$this.attr('data-upload')=='true'?(typeof(window.uploadConfig)=='undefined'?{}:window.uploadConfig):undefined;
		var editorConfig=$this.attr('data-editor')=='true'?(typeof(window.editorConfig)=='undefined'?{}:window.editorConfig):undefined;
		var option={
			obj:$this,
			data:data,
			editorConfig:editorConfig,
			uploadConfig:uploadConfig,
			callback:callback
		};
		$.contentShow(option);
	});

	//弹框编辑操作

	//默认表单提交
	$(document).on('click','.formSubmit',function(){
		$.formEditSubmit({form:$(this).closest('form')});
	});

	$(document).on('click','.bootstrap-switch .bootstrap-switch-container .bootstrap-switch-label',function(){
		return false;
	})
});
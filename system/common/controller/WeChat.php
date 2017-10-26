<?php

namespace app\common\controller;

use think\Loader;
use think\Request;
use think\Cache;
use think\Log;

class WeChat{
	const TOKEN='0b9d945d58ea386efb521353ba8d52d0';

	private $originId=null;

	public function index(){
		$request=Request::instance();

		$signature=$request->get('signature');
		$timestamp=$request->get('timestamp');
		$nonce=$request->get('nonce');

		$validRst=$this->valid($signature,$timestamp,$nonce,self::TOKEN);

		if($validRst){
			if($request->isGet()){
				return $request->get('echostr');
			}
			else{
				//$this->accessToken=$this->getAccessToken();
				return $this->responseMsg();
			}
		}
		else{
			return 'error';
		}
	}

	private function valid($signature,$timestamp,$nonce,$token){
		$tmpAry=[$token,$timestamp,$nonce];
		sort($tmpAry,SORT_STRING);
		$tmpStr=sha1(implode($tmpAry));

		if($tmpStr==$signature){
			return true;
		}
		else{
			return false;
		}
	}

	private function responseMsg(){
		$postStr=file_get_contents('php://input');
		if(empty($postStr)){
			return 'No Post Data';
		}

		$postData=simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
		$postData=json_decode(json_encode($postData),true);

		$this->originId=$postData['ToUserName'];
		$this->addUser($postData['FromUserName']);
		$this->getConfig();

		$msgType=strtolower($postData['MsgType']);
		$methodName=$msgType.'Msg';
		if(method_exists($this,$methodName)){
			return call_user_func_array([$this,$methodName],[$postData]);
		}

		return '';
	}

	//事件类型的消息
	private function eventMsg($postData){
		$eventType=strtolower($postData['Event']);
		$methodName=$eventType.'Event';
		if(method_exists($this,$methodName)){
			return call_user_func_array([$this,$methodName],[$postData]);
		}

		return '';
	}

	//订阅事件
	private function subscribeEvent($postData){
		$responseData=array(
			'ToUserName'=>$postData['FromUserName'],
			'FromUserName'=>$postData['ToUserName'],
		);

		$whereAry=['originId'=>$this->originId,'openId'=>$postData['FromUserName']];
		$saveData=['subscribe'=>1];

		if(isset($postData['EventKey'])&&!empty($postData['EventKey'])&&substr($postData['EventKey'],0,8)=='qrscene_'){
			$data['qrScene']=substr($postData['EventKey'],8);
		}

		$mdl=Loader::model('User');
		$mdl->edit(['where'=>$whereAry,'data'=>$saveData]);

		$cfg=$this->getConfig();

		$responseData['Content']="你好！\n欢迎使用【".$cfg['name']."】！\n1 、输入 【搜索+商品名称】例如:搜索数据线\n2、 将【淘宝客户端挑选好的商品链接】发给我,\n就可以知道获得优惠和返利的具体金额.\n━┉┉┉┉∞┉┉┉┉━\n🔥好友推荐的朋友请向你的好友索要她的邀请码.\n非好友邀请的亲,回复【10000】 领取关注红包. \n👉 有问题回复【帮助】\n👉 查看使用教程\n ".$cfg['tutorialLink']."\n━┉┉┉┉∞┉┉┉┉━\n⭕下单后请务必将订单号发送给我哦\n━┉┉┉┉∞┉┉┉┉━";
		return $this->textResponse($responseData);
	}

	//取消订阅事件
	private function unsubscribeEvent($postData){
		$mdl=Loader::model('User');
		$mdl->edit(['where'=>['originId'=>$this->originId,'openId'=>$postData['FromUserName']],'data'=>['subscribe'=>0]]);
	}

	//点击事件
	private function clickEvent($postData){

	}

	//跳转链接事件
	private function viewEvent($postData){

	}

	//上报地理位置事件
	private function locationEvent($postData){

	}

	//扫描二维码事件
	private function scanEvent($postData){

	}

	//文本消息
	private function textMsg($postData){
		$responseData=array(
			'ToUserName'=>$postData['FromUserName'],
			'FromUserName'=>$postData['ToUserName'],
		);

		$responseData['Content']=$this->dealTxtMsg($postData['Content'],$postData['FromUserName']);
		return $this->textResponse($responseData);
	}

	//图片消息
	private function imageMsg($postData){
		// $mediaId=$postData['MediaId'];

		// $responseData=array(
		// 	'ToUserName'=>$postData['FromUserName'],
		// 	'FromUserName'=>$postData['ToUserName'],
		// );

		// $userInfo=$this->getUserInfo($postData['FromUserName']);
		// if($userInfo===false||empty(C($userInfo['unionid']))){
		// 	$responseData['Content']="欢迎关注「一心祈福」！\n回复1  分分钟完成古寺里的真正供灯\n回复2  来看看我们都有哪些最传统的寺院服务\n回复3  来挑个七月的好日子吧\n回复4  看后就会脑洞大开哦~";
		// 	$this->textResponse($responseData);
		// }

		// $mediaInfo=$this->downloadMedia($mediaId);

		// if($mediaInfo===false){
		// 	$msg='图片上传失败！！';
		// }
		// else{
		// 	$msg="已经上传您的图片消息~~\nType:".$mediaInfo['type']."\nTime:".date('Y-m-d H:i:s',$mediaInfo['time']);
		// }

		// $responseData=array(
		// 	'ToUserName'=>$postData['FromUserName'],
		// 	'FromUserName'=>$postData['ToUserName'],
		// 	'Content'=>$msg,
		// );

		// $this->textResponse($responseData);
	}

	//语音消息
	private function voiceMsg($postData){
		// $mediaId=$postData['MediaId'];
		// $text=$postData['Recognition'];

		// $responseData=array(
		// 	'ToUserName'=>$postData['FromUserName'],
		// 	'FromUserName'=>$postData['ToUserName'],
		// );

		// $userInfo=$this->getUserInfo($postData['FromUserName']);
		// if($userInfo===false||empty(C('CELEBRITY_USER.'.$userInfo['unionid']))){
		// 	$responseData['Content']="欢迎关注「一心祈福」！\n回复1  分分钟完成古寺里的真正供灯\n回复2  来看看我们都有哪些最传统的寺院服务\n回复3  来挑个七月的好日子吧\n回复4  看后就会脑洞大开哦~";
		// 	$this->textResponse($responseData);
		// }

		// $mediaInfo=$this->downloadMedia($mediaId);

		// $msg='语音消息上传失败！！请重新发送！！';
		// if($mediaInfo!==false){
		// 	import('@.Com.Util.MyConst');
		// 	$topicMdl=D('Topic');
		// 	$rst=$topicMdl->weChatMedia($userInfo,$mediaId,$mediaInfo['file'],MyConst::MSG_ATTACH_TYPE_AUDIO,$mediaInfo['time'],$text);
		// 	if($rst!==false){
		// 		$msg="语音消息上传成功~~\n时间:".date('Y-m-d H:i:s',$mediaInfo['time']);
		// 	}
		// 	else{
		// 		Log::write('保存语音消息失败：'.var_export($mediaInfo,true));
		// 	}
		// }

		// $responseData['Content']=$msg;
		// $this->textResponse($responseData);
	}

	//视频消息
	private function videoMsg($postData){
		// $mediaId=$postData['MediaId'];

		// $responseData=array(
		// 	'ToUserName'=>$postData['FromUserName'],
		// 	'FromUserName'=>$postData['ToUserName'],
		// );

		// $userInfo=$this->getUserInfo($postData['FromUserName']);
		// if($userInfo===false||empty(C($userInfo['unionid']))){
		// 	$responseData['Content']="欢迎关注「一心祈福」！\n回复1  分分钟完成古寺里的真正供灯\n回复2  来看看我们都有哪些最传统的寺院服务\n回复3  来挑个七月的好日子吧\n回复4  看后就会脑洞大开哦~";
		// 	$this->textResponse($responseData);
		// }

		// $vedioInfo=$this->downloadMedia($mediaId);

		// // $thumbId=$postData['ThumbMediaId'];
		// // $thumbInfo=$this->downloadMedia($thumbId);

		// $msg='视频上传失败!!请重新发送！！';
		// if($vedioInfo!==false){
		// 	import('@.Com.Util.MyConst');
		// 	$topicMdl=D('Topic');
		// 	$rst=$topicMdl->weChatMedia($userInfo,$mediaId,$vedioInfo['file'],MyConst::MSG_ATTACH_TYPE_VIDEO,$vedioInfo['time']);
		// 	if($rst!==false){
		// 		$msg="视频上传成功~~\n时间:".date('Y-m-d H:i:s',$vedioInfo['time']);
		// 	}
		// 	else{
		// 		Log::write('保存语视频消息失败：'.var_export($vedioInfo,true));
		// 	}
		// }

		// // if($thumbInfo===false){
		// // 	$msg.="\n缩略图上传失败!!";
		// // }
		// // else{
		// // 	$msg.="\n缩略图上传成功~~\nName:".$thumbInfo['name']."\nType:".$thumbInfo['type']."\nTime:".date('Y-m-d H:i:s',$thumbInfo['time']);
		// // }

		// $responseData['Content']=$msg;
		// $this->textResponse($responseData);
	}

	//小视频消息
	private function shortVideoMsg($postData){
		// $mediaId=$postData['MediaId'];

		// $responseData=array(
		// 	'ToUserName'=>$postData['FromUserName'],
		// 	'FromUserName'=>$postData['ToUserName'],
		// );

		// $userInfo=$this->getUserInfo($postData['FromUserName']);
		// if($userInfo===false||empty(C($userInfo['unionid']))){
		// 	$responseData['Content']="欢迎关注「一心祈福」！\n回复1  分分钟完成古寺里的真正供灯\n回复2  来看看我们都有哪些最传统的寺院服务\n回复3  来挑个七月的好日子吧\n回复4  看后就会脑洞大开哦~";
		// 	$this->textResponse($responseData);
		// }

		// $vedioInfo=$this->downloadMedia($mediaId);

		// // $thumbId=$postData['ThumbMediaId'];
		// // $thumbInfo=$this->downloadMedia($thumbId);

		// $msg='小视频上传失败!!请重新发送！！';
		// if($vedioInfo!==false){
		// 	import('@.Com.Util.MyConst');
		// 	$topicMdl=D('Topic');
		// 	$rst=$topicMdl->weChatMedia($userInfo,$mediaId,$vedioInfo['file'],MyConst::MSG_ATTACH_TYPE_VIDEO,$vedioInfo['time']);
		// 	if($rst!==false){
		// 		$msg="小视频上传成功~~\n时间:".date('Y-m-d H:i:s',$vedioInfo['time']);
		// 	}
		// 	else{
		// 		Log::write('保存小视频消息失败：'.var_export($vedioInfo,true));
		// 	}
		// }

		// // if($thumbInfo===false){
		// // 	$msg.="\n\n缩略图上传失败!!";
		// // }
		// // else{
		// // 	$msg.="\n\n缩略图上传成功~~\n类型:".$thumbInfo['type']."\n时间:".date('Y-m-d H:i:s',$thumbInfo['time']);
		// // }

		// $responseData['Content']=$msg;
		// $this->textResponse($responseData);
	}

	//地理位置消息
	private function locationMsg($postData){

	}

	//链接消息
	private function linkMsg($postData){

	}

	//处理文本消息内容
	private function dealTxtMsg($content,$openId){
		$content=trim($content);
		$cfg=$this->getConfig();

		if($content=='帮助'){
			$msg="【微信购物步骤】\n1、打开手机淘宝客户端，选中购买的产品。\n2、点击产品标题旁的“分享”按钮，复制链接。\n3、把链接发给我，我会自动给你返回优惠券信息。\n4、复制我提供的优惠券信息，打开手机淘宝客户端付款购买。\n5、购物完成后，记得把订单号发给我绑定返利。\n6、如果没有邀请码,请回复10000\n7、发送指令【搜索+商品名】可以领取优惠券.\n8、发送指令【提现】金额超过1元,即可获得相应金额的红包.\n9、发送指令【个人信息】查看帐户余额和专属邀请码.\n操作流程请点击查看\n".$cfg['tutorialLink']."\n━┉┉┉┉∞┉┉┉┉━\n🔥🔥🔥下单后务必将订单号发给我！\n━┉┉┉┉∞┉┉┉┉━";
			return $msg;
		}

		if($content=='提现'){
			$msg=$this->msgForWithdraw($openId);
			return $msg;
		}

		//邀请码
		$rgx='/^\d{5}$/';
		if(preg_match($rgx,$content,$matchResult)){
			$msg=$this->msgForInvitation($content,$openId);
			return $msg;
		}

		//个人信息
		if($content=='个人信息'){
			$msg=$this->msgForUserInfo($openId);
			return $msg;
		}


		$obj=new TBK($cfg['tbkId'],$cfg['originId']);

		$defaultMsg="⭕ 抱歉,淘宝太忙了，请稍后重试！\n━┉┉┉┉∞┉┉┉┉━\n👉 查看使用教程\n".$cfg['tutorialLink'];

		$loginStatus=$obj->isLogin();
		if($loginStatus['status']){
			//淘宝分享
			$rgx='/【(.*)】.*(http:\/\/\S+)/';
			if(preg_match($rgx,$content,$matchResult)){
				$kw=$matchResult[1];
				$url=$matchResult[2];

				$msg=$this->msgForShare($obj,$kw,$url);
			}

			//搜索+kw
			if(mb_substr($content,0,2)=='搜索'){
				$kw=mb_substr($content,2);
				$msg=$this->msgForSear($obj,$kw);
				return $msg;
			}


			//订单号
			$rgx='/^\d{17}$/';
			if(preg_match($rgx,$content,$matchResult)){
				$msg=$this->msgForOrder($content,$openId);
				return $msg;
			}
		}

		return $defaultMsg;
	}

	//处理搜索
	private function msgForSearch($obj,$kw){
		$couponItems=$obj->getCouponItems($kw);
		$cnt=$couponItems['count'];

		$msg="机器人已整理好所有【".$kw."】共计【".$cnt."】个优惠券，点击下面链接进行领券购买：\n━┉┉┉┉∞┉┉┉┉━\n http://baidu.com\n━┉┉┉┉∞┉┉┉┉━";
		return $msg;
	}

	//处理提现
	private function msgForWithdraw($openId){
		return '敬请期待！';
	}

	//处理订单号
	private function msgForOrder($orderId,$openId){
		return '敬请期待！';
	}

	//处理个人信息消息
	private function msgForUserInfo($openId){
		return '敬请期待！';
	}

	//处理邀请码消息
	private function msgForInvitation($code,$openId){
		$mdl=Loader::model('User');
		$invitedInfo=$mdl->getInfo(['where'=>['openId'=>$openId]]);
		if($invitedInfo['fromUserId']==0){
			$fromUserId=intval($code)-10000;
			$fromUserId=$fromUserId==0?$cfg['userId']:$fromUserId;
			$fromUserInfo=$mdl->getInfo(['where'=>['id'=>$fromUserId]]);
			if(!empty($fromUserInfo)){
				$mony=$this->getInviteMoney();
				$invitedMoney=$this->getInviteMoney();

				$mdl->startTrans();
				$result=$mdl->edit(['where'=>['id'=>$invitedInfo['id']],'data'=>['fromUserId'=>$fromUserId,'money'=>['exp','money'+$invitedMoney]]]);
				if($result==false){
					$mdl->rollback();
					Log::write('关注赠送失败：'.$mdl->getLastSql());
					return '请重新发送您的邀请码！';
				}

				$result=$mdl->edit(['where'=>['id'=>$fromUserId],'data'=>['money'=>['exp','money'+$money]]]);
				if($result===false){
					$mdl->rollback();
					Log::write('邀请赠送失败：'.$mdl->getLastSql());
					return '请重新发送您的邀请码！';
				}

				$ivtMdl=Loader::model('Invitation');
				$result=$ivtMdl->add(['userId'=>$fromUserId,'money'=>$money,'invitedUserId'=>$invitedInfo['id'],'invitedMoney'=>$invitedMoney]);
				if($result===false){
					$mdl->rollback();
					Log::write('邀请纪录失败：'.$ivtMdl->getLastSql());
					return '请重新发送您的邀请码！';
				}

				$mdl->commit();
				return "恭喜，您的邀请码有效！\n赠送您【".$invitedMoney."】元，您的当前余额【".$invitedMoney."】元。超过".$cfg['withdrawLimit']."元即可提现.\n━┉┉┉┉∞┉┉┉┉━\n1 、输入 【搜索+商品名称】例如:搜索数据线\n2、 将【淘宝客户端挑选好的商品链接】发给我,\n就可以知道获得优惠和返利的具体金额.\n━┉┉┉┉∞┉┉┉┉━\n👉 有问题回复【帮助】\n👉 查看使用教程\n ".$this->getConfig('tutorialLink')."\n━┉┉┉┉∞┉┉┉┉━\n⭕下单后请务必将订单号发送给我哦\n━┉┉┉┉∞┉┉┉┉━";
			}
		}

		return '';
	}

	//获取邀请 关注时赠送金额
	private function getInviteMoney($min=1,$max=50){
		return mt_rand($min,$max)/100;
	}

	//处理淘宝分享的消息
	private function msgForShare($obj,$kw,$url){
		$couponItems=$obj->getCouponItems($kw);
		$couponItemCnt=$couponItems['count'];

		$itemId=TBK::getItemId($url);
		if(!empty($itemId)){
			$itemInfo=$obj->getItemInfo($kw,$itemId);
			if(empty($itemInfo)&&!empty($couponItems['data'])){
				$itemInfo=$couponItems['data'][0];
				$msg="**************\n您所查询的商品没有优惠，机器人为您查询到了同标题商品，【商品价格】可能会不一致，请谨慎购买！\n**************\n\n【".$kw."】";
			}

			if(empty($itemInfo)){
				$msg="**************\n您所查询的商品没有优惠\n**************";
			}
			else{
				$price=$itemInfo['zkPrice'];
				$coupon=$itemInfo['couponAmount'];
				$rebate=$itemInfo['tkCommFee'];

				$linkInfo=$obj->getLink($itemId,$cfg['sitId'],$cfg['adZoneId']);
				if(!empty($linkInfo)){
					$msg="【".$kw."】\n━┉┉┉┉∞┉┉┉┉━\n☞ 原价：".$price.($coupon>0?"\n☞ 优惠：".$coupon.'元':'')."\n☞ 口令：".(isset($linkInfo['couponLinkTaoToken'])?$linkInfo['couponLinkTaoToken']:$linkInfo['taoToken'])."\n☞ 返利：".$rebate."元\n━┉┉┉┉∞┉┉┉┉━\n👉 长按复制本条信息,打开淘宝APP,就可以省钱下单啦！\n━┉┉┉┉∞┉┉┉┉━\n⭕ 不可以使用支付宝红包、淘金币等进行减款.\n━┉┉┉┉∞┉┉┉┉━\n🔥 下单后请务必将订单号发送给我哦\n👉 有问题回复【帮助】\n👉 查看使用教程\n".$cfg['tutorialLink']."\n\n机器人已整理好所有【".$kw."】共计【".$couponItemCnt."】个优惠券，点击下面链接进行领券购买，如关键字获取不准确，您可以进入领券页面直接输入关键字搜索：\n━┉┉┉┉∞┉┉┉┉━\n http://baidu.com\n━┉┉┉┉∞┉┉┉┉━";
				}
			}
		}

		return $msg;
	}

	//回复文本信息
	private function textResponse($data){
		$data['CreateTime']=time();
		$data['MsgType']='text';

		$xml='<xml>'.$this->dataToXml($data).'</xml>';

		return $xml;
	}

	//回复信息图文
	private function newsResponse($data){
		$data['CreateTime']=time();
		$data['MsgType']='news';

		$xml='<xml>'.$this->dataToXml($data).'</xml>';

		return $xml;
	}

	//发送客服消息
	private function kfMsg($openId,$msg){

	}

	public function oAuth($redirectUri,$state='',$scope='snsapi_base'){
		$url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appId.'&redirect_uri='.urlencode($redirectUri).'&response_type=code&scope='.$scope.'&state='.$state.'#wechat_redirect';
		header('Location: '.$url);
	}

	public function getOAuthUserInfo($code){
		$userInfo=null;
		$getTokenUrl='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appId.'&secret='.$this->appSecrect.'&code='.$code.'&grant_type=authorization_code';
		$rpstData=$this->curlRequest($getTokenUrl);
		$tokenData=json_decode($rpstData['data'],true);

		if($tokenData['scope']=='snsapi_base'){
			$userInfo=$tokenData;
		}
		return $userInfo;
	}

	public function getAccessToken($originId=null){
		$originId=empty($originId)?$this->originId:$originId;
		if(!empty($originId)){
			$accessToken=Cache::get('AT_'.$originId);
			if(empty($accessToken)){
				$url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->getConfig('appId').'&secret='.$this->getConfig('appSecrect');
				$result=$this->curlRequest($url);
				$data=json_decode($result['data'],true);

				if(isset($data['access_token'])&&isset($data['expires_in'])){
					$accessToken=$data['access_token'];
					$expiresIn=intval($data['expires_in']);
					Cache::set('AT_'.$originId,$accessToken,$expiresIn-600);
				}
			}

			return $accessToken;
		}

		$msg='获取AccessToken失败！OriginId不能为NULL';
		throw new \Exception($msg);
	}

	private function getUserInfo($openId){
		if(!empty($openId)){
			$url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getAccessToken().'&openid='.$openId.'&lang=zh_CN';
			$result=$this->curlRequest($url);
			$data=json_decode($result['data'],true);

			if(!empty($data)&&!isset($data['errcode'])){
				return $data;
			}
		}
		return false;
	}

	private function addUser($openId){
		$originId=$this->originId;

		$mdl=Loader::model('User');
		$user=$mdl->getInfo(['where'=>['originId'=>$originId,'openId'=>$openId]]);

		if(empty($user)){
			$userInfo=$this->getUserInfo($openId);
			if(!empty($userInfo)&&$userInfo['subscribe']==1){
				$data=['originId'=>$originId,'openId'=>$openId,'unionId'=>isset($userInfo['unionid'])?$userInfo['unionid']:'','groupId'=>$userInfo['groupid'],'nickName'=>$userInfo['nickname'],'sex'=>$userInfo['sex'],'img'=>$userInfo['headimgurl'],'subscribe'=>$userInfo['subscribe'],'subscribeTime'=>date('Y-m-d H:i:s',$userInfo['subscribe_time']),'city'=>$userInfo['city'],'province'=>$userInfo['province'],'country'=>$userInfo['country'],'remark'=>$userInfo['remark']];
				$rst=$mdl->add($data);
				if($rst===false){
					Log::write('更新用户信息失败：'.$mdl->getLastSql());
				}
			}
		}
	}

	private function sentTmpMsg($openIdAry,$tplId,$param,$url='',$topColor='#FF0000'){
		$rtn=[];
		$url='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$this->getAccessToken();
		foreach($openIdAry as $openId){
			$data=['touser'=>$openId,'template_id'=>$tplId,'topcolor'=>$topColor,'data'=>$param];
			if(!empty($url)){
				//$data['url']=$url;
			}
			$rst=$this->curlRequest($url,json_encode($data));
			$rtn[]=$rst['data'];
		}

		return $rtn;
	}

	private function downloadMedia($mediaId,$format=null){
		if(!empty($mediaId)){
			$path='Public/Uploads/WeChat';
			$url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccessToken().'&media_id='.$mediaId;

			$result=$this->curlRequest($url);
			$mediaData=$result['data'];
			$requestInfo=$result['info'];
			if($requestInfo['size_download']>0){
				$time=time();
				$mediaType=explode('/',$requestInfo['content_type']);
				//$name=$mediaId.'.'.(empty($format)?$mediaType[1]:$format);
				$name=date('YmdHis').substr(md5(uniqid(mt_rand(),true)),0,6).'.'.(empty($format)?$mediaType[1]:$format);
				$type=strtolower($mediaType[0]);
				$path.='/'.$type.'/'.date('Y',$time).'/'.date('md',$time);
				$file=$path.'/'.$name;
				if((is_dir($path)||(!is_dir($path)&&mkdir($path,0777,true)))&&file_put_contents($file,$mediaData)){
					return array('file'=>$file,'name'=>$name,'type'=>$requestInfo['content_type'],'time'=>$time);
				}
				else{
					Log::write('上传文件失败:'.$file);
				}
			}
		}
		return false;
	}

	private function getConfig($name=null){
		$originId=$this->originId;
		if(!empty($originId)){
			$cfg=Cache::get('WX_'.$originId);
			$rst=empty($cfg)?$this->setConfig():true;
			if($rst){
				return empty($name)?$cfg:(isset($cfg[$name])?$cfg[$name]:null);
			}
		}

		return null;
	}

	private function setConfig(){
		$originId=$this->originId;
		if(!empty($originId)){
			$mdl=Loader::model('Account');
			$whereAry=['originId'=>$originId,'isValid'=>1];
			//$fieldAry=['wxName','wxId','userId','originId','appId','appSecrect','aesKey','macId','key','tbkName','tbkId','tbkPassword','siteId','adZoneId'];
			$cfg=$mdl->getInfo(['where'=>$whereAry]);
			if(!empty($cfg)){
				Cache::set('WX_'.$originId,$cfg);
				return true;
			}
			$msg='该公众号尚未计入系统，请联系管理员！OriginID：'.$originId;
		}
		else{
			$msg='设置公众号信息失败！OriginID不能为NULL';
		}

		//throw new \Exception($msg);
		return false;
	}

	private function curlRequest($url,$data=null,$header=null){
		$curl=curl_init($url);
		curl_setopt($curl,CURLOPT_HEADER,FALSE);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_0);

		if(!empty($header)){
			curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
		}

		if(!empty($data)){
			curl_setopt($curl,CURLOPT_POST,TRUE);
			curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
		}

		$result=curl_exec($curl);
		$info=curl_getinfo($curl);
		curl_close($curl);
		return array('data'=>$result,'info'=>$info);
	}

	private function dataToXml($data){
		$xml='';
		foreach($data as $key=>$value){
			if(is_numeric($key)){
				$key='item id="'.$key.'"';
			}
			$xml.='<'.$key.'>';
			$xml.=(is_array($value)||is_object($value))?$this->dataToXml($value):'<![CDATA['.preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/",'',$value).']]>';
			list($key)=explode(' ',$key);
			$xml.='</'.$key.'>';
		}

		return $xml;
	}

	/**
	 * 用SHA1算法生成安全签名
	 * @param string $token 票据
	 * @param string $timeStamp 时间戳
	 * @param string $nonce 随机字符串
	 * @param string $encryptMsg 密文消息
	 */
	public function getSHA1($token,$timeStamp,$nonce,$encryptMsg){
		try{
			$array=[$encryptMsg,$token,$timeStamp,$nonce];
			sort($array,SORT_STRING);
			$str=implode($array);
			return sha1($str);
		}catch(Exception $e){
			return '';
		}
	}

	}

?>

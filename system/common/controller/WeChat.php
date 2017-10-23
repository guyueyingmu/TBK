<?php

namespace app\common\controller;

use think\Load;
use think\Request;
use think\Cache;
use think\Log;

class WeChat{

	const TOKEN='0b9d945d58ea386efb521353ba8d52d0';
	private $config=null;
	private $originId=null;

	function __construct($config=null){
		if(!empty($config)){
			$this->config=$config;
		}
	}

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
		$msgType=strtolower($postData['MsgType']);

		$methodName=$msgType.'Msg';
		if(method_exists($this,$methodName)){
			$originId=$postData['ToUserName'];
			$this->originId=$originId;
			$this->setConfig();
			return call_user_func([$this,$methodName],[$postData]);
		}

		return '';
	}

	//事件类型的消息
	private function eventMsg($postData){
		$event=strtolower($postData['Event']);
		$methodName=$msgType.'Event';
		if(method_exists($this,$methodName)){
			return call_user_func([$this,$methodName],[$postData]);
		}

		return '';
	}

	//订阅事件
	private function subscribeEvent($postData){
		$opneId=$postData['FromUserName'];
		$userInfo=$this->getUserInfo($openId);

		Log::write('UserInfo:'.var_export($userInfo,true));
	}

	//取消订阅事件
	private function unsubscribeEvent($postData){

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

		$opneId=$postData['FromUserName'];
		$userInfo=$this->getUserInfo($openId);

		Log::write('UserInfo:'.var_export($userInfo,true));

		$responseData['Content']=$this->dealTxtMsg($postData['Content']);
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
	private function dealTxtMsg($content){
		return $content.' -^_^-';
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
				$url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appId.'&secret='.$this->appSecrect;
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

	public function getUserInfo($openId){
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

	public function sentTmpMsg($openIdAry,$tplId,$param,$url='',$topColor='#FF0000'){
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

	private function getConfig($name=null,$originId=null){
		$originId=empty($originId)?$this->originId:$originId;
		if(!empty($originId)){
			$cfg=Cache::get('WX_'.$originId);
			$rst=empty($cfg)?$this->setConfig($originId):true;
			if($rst){
				return empty($name)?$cfg:(isset($cfg[$name])?$cfg[$name]:null);
			}
		}

		return null;
	}

	private function setConfig($originId=null){
		$originId=empty($originId)?$this->originId:$originId;
		if(!empty($originId)){
			$mdl=Loader::model('Account');
			$whereAry=['originId'=>$originId];
			$fieldAry=['id','userId','originId','appId','appSecrect','aesKey','macId','key'];
			$cfg=$mdl->getInfo(['where'=>$whereAry,'field'=>$fieldAry]);
			if(!empty($cfg)){
				Cache::set('WX_'.$originId,$cfg);
				return true;
			}
			$msg='该公众号尚未计入系统，请联系管理员！OriginID：'.$originId;
		}
		else{
			$msg='设置公众号信息失败！OriginID不能为NULL';
		}

		throw new \Exception($msg);
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

<?php

namespace app\common\controller;

use think\Load;
use think\Request;
use think\Cache;
use think\Log;

class WeChat{

	const TOKEN='0b9d945d58ea386efb521353ba8d52d0';
	private $appId='';
	private $appSecrect='';
	private $aesKey='';

	function __construct($config=null){
		if(!empty($config)){
			foreach($config as $key=>$val){
				$key=trim($key);
				$this->$key=trim($val);
			}
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
		$postData=json_decode(json_encode($postData),true);Log::write()
		$msgType=strtolower($postData['MsgType']);

		$methodName=$msgType.'Msg';
		if(method_exists($this,$methodName)){
			call_user_func([$this,$methodName],[$postData]);
		}
	}

		//事件类型的消息
	private function eventMsg($postData){
		$event=strtolower($postData['Event']);
		$methodName=$msgType.'Event';
		if(method_exists($this,$methodName)){
			call_user_func([$this,$methodName],[$postData]);
		}
	}

	//订阅事件
	private function subscribeEvent($postData){
		// $articles=array(
		// 	// array(
		// 	// 	'Title'=>'快速增长福慧的胜乐金刚火供',
		// 	// 	'Description'=>'继“密集金刚”火供、“大威德金刚”火供之后，大藏寺即将举行“胜乐金刚”火供！',
		// 	// 	'PicUrl'=>'http://mobile2.qfu365.com/Public/Image/sc_sljg.jpg',
		// 	// 	'Url'=>'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxc18eecf9ac0a529e&redirect_uri=http%3A%2F%2Fmobile2.qfu365.com%2Findex.php%3Fg%3DApp%26m%3DOrder%26a%3DdzsDhtAct&response_type=code&scope=snsapi_base&state=1&from=singlemessage&isappinstalled=0'
		// 	// ),
		// 	array(
		// 		'Title'=>'药师佛圣诞祈福共修法会',
		// 		'Description'=>'',
		// 		'PicUrl'=>'http://mobile.qfu365.com/Public/Image/sc_ysffh.jpg',
		// 		'Url'=>'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxc18eecf9ac0a529e&redirect_uri=http%3A%2F%2Fmobile2.qfu365.com%2Findex.php%3Fg%3DApp%26m%3DActivity%26a%3DAct15&response_type=code&scope=snsapi_base&state=1&from=singlemessage&isappinstalled=0'
		// 	),
		// 	array(
		// 		'Title'=>'客官留步，此处有礼！',
		// 		'Description'=>'免费祈福，机会只有一次哦～',
		// 		'PicUrl'=>'http://mobile.qfu365.com/Public/Image/sc_sd.jpg',
		// 		'Url'=>'http://mobile.qfu365.com/Index/giftGuideSong'
		// 	),
		// 	// array(
		// 	// 	'Title'=>'客官留步，此处有礼！',
		// 	// 	'Description'=>'免费祈福，机会只有一次哦～',
		// 	// 	'PicUrl'=>'http://mobile.qfu365.com/Public/Image/sc_sd.jpg',
		// 	// 	'Url'=>'http://mobile.qfu365.com/Index/giftGuideSong'
		// 	// )
		// );

		// $responseData=array(
		// 	'ToUserName'=>$postData['FromUserName'],
		// 	'FromUserName'=>$postData['ToUserName'],
		// 	'ArticleCount'=>count($articles),
		// 	'Articles'=>$articles,
		// );

		// $this->newsResponse($responseData);
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
			// $responseData=array(
			// 	'ToUserName'=>$postData['FromUserName'],
			// 	'FromUserName'=>$postData['ToUserName'],
			// );

			// $content=$postData['Content'];
			// switch($content){
			// 	case '1':
			// 		$articles=array(
			// 			array(
			// 				'Title'=>'分分钟完成古寺里的真正供灯',
			// 				'Description'=>'',
			// 				'PicUrl'=>'http://mobile.qfu365.com/Public/Image/sc_zn.jpg',
			// 				'Url'=>'http://mp.weixin.qq.com/s?__biz=MzIzNzA5NTM0OA==&mid=503119602&idx=1&sn=b863d48645deda5c07b61f4daffb1ad8#rd'
			// 			)
			// 		);

			// 		$responseData['ArticleCount']=count($articles);
			// 		$responseData['Articles']=$articles;

			// 		$this->newsResponse($responseData);
			// 		break;
			// 	case '2':
			// 		$articles=array(
			// 			array(
			// 				'Title'=>'西藏萨迦寺',
			// 				'Description'=>'',
			// 				'PicUrl'=>'http://mobile.qfu365.com/Public/Image/sc_sjs.jpg',
			// 				'Url'=>'http://mp.weixin.qq.com/s?__biz=MzIzNzA5NTM0OA==&mid=503119577&idx=1&sn=0a1039ca2466261647605dea4171226e#rd'
			// 			),
			// 			array(
			// 				'Title'=>'云南西门奘房',
			// 				'Description'=>'',
			// 				'PicUrl'=>'http://mobile.qfu365.com/Public/Image/sc_xmzf.jpg',
			// 				'Url'=>'http://mp.weixin.qq.com/s?__biz=MzIzNzA5NTM0OA==&mid=503119577&idx=2&sn=e22b445ee6e4186a18325b1469750af5#rd'
			// 			),
			// 			array(
			// 				'Title'=>'西藏山南达龙寺',
			// 				'Description'=>'',
			// 				'PicUrl'=>'http://mobile.qfu365.com/Public/Image/sc_dls.jpg',
			// 				'Url'=>'http://mp.weixin.qq.com/s?__biz=MzIzNzA5NTM0OA==&mid=503119577&idx=3&sn=99b93844659a779eb7c6b5476dcad555#rd'
			// 			)
			// 		);

			// 		$responseData['ArticleCount']=count($articles);
			// 		$responseData['Articles']=$articles;

			// 		$this->newsResponse($responseData);
			// 		break;
			// 	case '3':
			// 		$articles=array(
			// 			array(
			// 				'Title'=>'来挑个七月的好日子吧',
			// 				'Description'=>'',
			// 				'PicUrl'=>'http://mobile.qfu365.com/Public/Image/sc_jr.jpg',
			// 				'Url'=>'http://mp.weixin.qq.com/s?__biz=MzIzNzA5NTM0OA==&mid=503119929&idx=1&sn=089901fe5851a23a0ec32ff49fb53e9a#rd'
			// 			)
			// 		);

			// 		$responseData['ArticleCount']=count($articles);
			// 		$responseData['Articles']=$articles;

			// 		$this->newsResponse($responseData);
			// 		break;
			// 	case '4':
			// 		$articles=array(
			// 			array(
			// 				'Title'=>'看后就会脑洞大开哦~',
			// 				'Description'=>'',
			// 				'PicUrl'=>'http://mobile.qfu365.com/Public/Image/sc_lsxx.jpg',
			// 				'Url'=>'http://mp.weixin.qq.com/mp/getmasssendmsg?__biz=MzIzNzA5NTM0OA==#wechat_webview_type=1&wechat_redirect'
			// 			)
			// 		);

			// 		$responseData['ArticleCount']=count($articles);
			// 		$responseData['Articles']=$articles;

			// 		$this->newsResponse($responseData);
			// 		break;
			// 	default:
			// 		$responseData['Content']="欢迎关注「一心祈福」！\n回复1  分分钟完成古寺里的真正供灯\n回复2  来看看我们都有哪些最传统的寺院服务\n回复3  来挑个七月的好日子吧\n回复4  看后就会脑洞大开哦~";
			// 		$this->textResponse($responseData);
			// }
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

		//处理回复内容
		private function msgResponse($msgType,$data){
			// switch($msgType){
			// 	case 'text':
			// 		$this->textResponse($data);
			// 		break;
			// 	case 'image':
			// 		$this->imageResponse($data);
			// 		break;
			// 	case 'voice':
			// 		$this->voiceResponse($data);
			// 		break;
			// 	case 'video':
			// 		$this->videoResponse($data);
			// 		break;
			// 	case 'music':
			// 		$this->musicResponse($data);
			// 		break;
			// 	case 'news':
			// 		$this->newsResponse($data);
			// 		break;
			// }
		}

		//处理文本回复内容
		private function textResponse($data){
			// $data['CreateTime']=time();
			// $data['MsgType']='text';

			// $xml='<xml>'.$this->dataToXml($data).'</xml>';

			// echo $xml;
		}

		//处理图文回复内容
		private function newsResponse($data){
			// $data['CreateTime']=time();
			// $data['MsgType']='news';

			// $xml='<xml>'.$this->dataToXml($data).'</xml>';

			// echo $xml;
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

		public function getAccessToken(){
			$accessToken=Cache::get('accessToken');
			if(empty($accessToken)){
				$url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appId.'&secret='.$this->appSecrect;
				$result=$this->curlRequest($url);
				$data=json_decode($result['data'],true);

				$accessToken=$data['access_token'];
				$expiresIn=intval($data['expires_in']);
				if(!empty($accessToken)){
					Cache::set('accessToken',$accessToken,$expiresIn-600);
				}
			}

			return $accessToken;
		}

		public function getUserInfo($openId){
			if(!empty($openId)){
				$url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getAccessToken().'&openid='.$openId.'&lang=zh_CN';
				$result=$this->curlRequest($url);
				$data=json_decode($result['data'],true);

				if(!isset($data['errcode'])){
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

		private function getConfig($name=null){
			$cfg=$this->config;
			return empty($name)?$cfg:(isset($cfg[$name])?$cfg[$name]:null);
		}

		private function setConfig($param){
			if(!empty($param)&&is_array($param)){
				$cfg=$this->config;
				foreach($param as $key=>$val){
					$cfg[trim($key)]=trim($val);
				}
				$this->config=$cfg;
			}
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

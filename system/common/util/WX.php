<?php

	namespace app\common\util;

	use think\Log;

	class WX{
		const APPID='wx782c26e4c19acffb';
		const LANG='zh_CN';

		private $config=[];

		private $specialUser=['newsapp','fmessage','filehelper','weibo','qqmail','fmessage','tmessage','qmessage','qqsync','floatbottle','lbsapp','shakeapp','medianote','qqfriend','readerapp','blogapp','facebookapp','masssendapp','meishiapp','feedsapp','voip','blogappweixin','weixin','brandsessionholder','weixinreminder','wxid_novlwrv3lqwv11','gh_22b87fa7cb3c','officialaccounts','notification_messages','wxid_novlwrv3lqwv11','gh_22b87fa7cb3c','wxitil','userexperience_alarm','notification_messages'];

		public function __construct($param=null){
			$param['deviceid']=isset($param['deviceid'])?$param['deviceid']:'e'.substr(md5(uniqid()),2,17);
			foreach($param as $key=>$val){
				if(!empty($val)){
					$this->config[$key]=$val;
				}
			}

			$this->getUUID();
		}

		private function getUUID(){
			$uuid=$this->getConfig('uuid');
			if(empty($uuid)){
				$time=time();
				$redirectUrl='https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage';
				$param=['appid'=>self::APPID,'lang'=>self::LANG,'redirect_uri'=>$redirectUrl,'fun'=>'new','_'=>$time];
				$url='https://login.wx.qq.com/jslogin?'.http_build_query($param);

				$result=self::curlRequest($url);
				$result=$result['data'];
				$rgx='/window.QRLogin.code\s*=\s*(\d+);\s*window.QRLogin.uuid\s*=\s*"(\S+?)"/';
				if(preg_match($rgx,$result,$matchResult)){
					$uuid=$matchResult[2];
					$this->setConfig(['uuid'=>$uuid]);
				}
			}

			return $uuid;
		}

		public function getQrCode(){
			$rtn=['status'=>false,'msg'=>'getUUID Faild'];

			$uuid=$this->getUUID();

			if(!empty($uuid)){
				//$result=$this->waitForLogin(1);
				$url='https://login.weixin.qq.com/qrcode/'.$uuid;
				$result=self::curlRequest($url);

				$rtn=['status'=>true,'img'=>$result['data']];
			}

			return $rtn;
		}

		//获取redirectUrl & baseUrl
		public function waitForLogin($tip=0){
			$rtn=['status'=>false,'msg'=>'getUUID Faild'];

			$uuid=$this->getUUID();
			if(!empty($uuid)){
				$time=time();
				$param=['loginicon'=>'true','uuid'=>$uuid,'tip'=>$tip,'_'=>$time,'r'=>~$time];
				$url='https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?'.http_build_query($param);

				$result=self::curlRequest($url);
				$result=$result['data'];
				$rtn=['code'=>0];

				$rgx='/window.code\s*=\s*(\d+);/';
				if(preg_match($rgx,$result,$matchResult)){
					$code=$matchResult[1];
					$rtn['code']=$code;
					switch($code){
						case 200:
							//window.code=200;↵window.redirect_uri="https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage?ticket=A_6aqiS7KHDUWp0f2kH6nYzu@qrticket_0&uuid=oZLLKKuuiQ==&lang=zh_CN&scan=1508480588";
							$rgx='/window.redirect_uri\s*=\s*["|\'](\S+?)["|\'];/';
							if(preg_match($rgx,$result,$matchResult)){
								$redirectUrl=$matchResult[1];

								$this->setConfig(['redirectUrl'=>$redirectUrl,'baseUrl'=>substr($redirectUrl,0,strrpos($redirectUrl,'/'))]);
								// $loginResult=$this->login($redirectUrl);
								// $rtn['loginResult']=$loginResult;
							}
							break;
						case 201:
							//window.code=201;window.userAvatar = 'data:img/jpg;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAIAAAADnC86AAAAA3NCSVQICAjb4U/gAAAAHElEQVRYhe3BAQ0AAADCoPdPbQ43oAAAAADg3wAS6AABuHYUGQAAAABJRU5ErkJggg==';
							$rgx='/window.userAvatar\s*=\s*["|\'](\S+?)["|\'];/';
							if(preg_match($rgx,$result,$matchResult)){
								$imgData=$matchResult[1];
								$rtn['img']=$imgData;
							}
							break;
					}
				}
			}

     return $rtn;
		}

		//获取skey & wxsid & wxuin & pass_ticket & isgrayscale
		public function loginRedirect($url=null){
			$rtn=['status'=>false,'msg'=>'redirectUrl is Null'];
			$url=(empty($url)?$this->getConfig('redirectUrl'):$url).'&fun=new&version=v2';
			if(!empty($url)){
				$rqstCookie=['MM_WX_NOTIFY_STATE'=>1,'MM_WX_SOUND_STATE'=>1,'lang'=>self::LANG];
				$result=self::curlRequest($url,null,null,$rqstCookie);
				$cookieAry=$result['cookie'];
				//<error><ret>0</ret><message></message><skey>@crypt_bfcb446c_56a1399b31e3fcba7c06abf96f602fea</skey><wxsid>zpXA0NCXqkkXMPmW</wxsid><wxuin>742270135</wxuin><pass_ticket>JLCvdtwtL4PMRcLME2c0c%2BTCIGFWiL6xicPHMYruk0j3n5ryANMQz5XsFXLW%2B9U0</pass_ticket><isgrayscale>1</isgrayscale></error>
				$data=simplexml_load_string($result['data'],'SimpleXMLElement',LIBXML_NOCDATA);
				$data=json_decode(json_encode($data),true);
				if(!empty($data)&&$data['ret']==0){
					//['skey','wxsid','wxuin','pass_ticket','isgrayscale']
					unset($data['ret']);unset($data['message']);

					$data['cookie']=$cookieAry;
					$this->setConfig($data);

					$rtn=$data;

					$initResult=$this->redirectInit();
					$rtn=$initResult;
					$rtn['redirectResult']=$data;
				}
				else{
					$rtn['data']=$data;
				}
			}
			return $rtn;
		}

		//获取syncKey & userInfo
		public function redirectInit($url=null){
			$config=$this->getConfig();
			$param=['pass_ticket'=>$config['pass_ticket']];
			$url=(empty($url)?$config['baseUrl'].'/webwxinit':$url).'?'.http_build_query($param);

			//$url='https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxinit';

			$rqstData=$this->getBaseRqstParam();
			$rqstCookie=$config['cookie'];
			$rqstCookie['last_wxuin']=$config['wxuin'];
			$rqstCookie['login_frequency']=1;
			$result=self::curlRequest($url,$rqstData,null,$rqstCookie);

			$rtn=['status'=>false,'msg'=>'webwxinit error','data'=>$result];
			$cookieAry=$result['cookie'];
			$data=json_decode($result['data'],true);
			if(!empty($data)&&$data['BaseResponse']['Ret']==0){
				$syncKey=$data['SyncKey'];
				$user=$data['User'];
				$userInfo=['Uin'=>$user['Uin'],'UserName'=>$user['UserName'],'NickName'=>$user['NickName'],'HeadImgUrl'=>$user['HeadImgUrl'],'RemarkName'=>$user['RemarkName']];
				$rtn=['userInfo'=>$userInfo,'syncKey'=>$syncKey,'cookie'=>$cookieAry];
				$this->setConfig($rtn);
				$rtn['status']=true;

				$this->loginStatusNotify();
				$this->getContact();
			}

			return $rtn;
		}

		//获取msgid
		public function loginStatusNotify($url=null){
			$time=time();
			$config=$this->getConfig();
			$param=['pass_ticket'=>$config['pass_ticket']];
			$url=(empty($url)?$config['baseUrl'].'/webwxstatusnotify':$url).'?'.http_build_query($param);

			$rtn=['status'=>false,'msg'=>'UserInfo is Null'];

			if(isset($config['userInfo'])){
				$userInfo=$config['userInfo'];
				$rqstData=$this->getBaseRqstParam();
				$rqstData['ClientMsgId']=$time;
				$rqstData['Code']=3;
				$rqstData['FromUserName']=$userInfo['UserName'];
				$rqstData['ToUserName']=$userInfo['UserName'];
				$rqstCookie=$config['cookie'];
				$result=self::curlRequest($url,$rqstData,null,$rqstCookie);
				//{"BaseResponse": {"Ret": 0,"ErrMsg": ""},"MsgID": "7427305954851303215"}
				$data=json_decode($result['data'],true);
				if(!empty($data)&&$data['BaseResponse']['Ret']==0){
					$msgId=$data['MsgID'];
					$this->setConfig(['msgid'=>$msgId]);
					$rtn=['status'=>true,'msgId'=>$msgId];
				}
				else{
					$rtn=['status'=>false,'msg'=>'response data error','data'=>$result['data']];
				}
			}

			return $rtn;
		}

		//获取好友数据
		public function getContact($url=null){
			if(!$this->isLogin()){
				return ['status'=>false,'msg'=>__METHOD__.' Error：No Login!'];
			}
			$time=time();
			$config=$this->getConfig();
			$param=['pass_ticket'=>$config['pass_ticket'],'r'=>$time,'seq'=>0,'skey'=>$config['skey']];
			$url=(empty($url)?$config['baseUrl'].'/webwxgetcontact':$url).'?'.http_build_query($param);
			$rqstCookie=$config['cookie'];
			$result=self::curlRequest($url,null,null,$rqstCookie);

			$data=json_decode($result['data'],true);
			if(!empty($data)&&$data['BaseResponse']['Ret']==0){
				$privateContact=null;
				$officialContact=null;
				$groupContact=null;
				$memberList=$data['MemberList'];
				foreach($memberList as $member){
					if(!in_array($member['UserName'],$this->specialUser)){
						$memberInfo=['Uin'=>$member['Uin'],'UserName'=>$member['UserName'],'NickName'=>$member['NickName'],'HeadImgUrl'=>$member['HeadImgUrl'],'RemarkName'=>$member['RemarkName']];
						if($member['VerifyFlag']&8){
							$officialContact[$member['UserName']]=$memberInfo;
						}
						else if(strpos($member['UserName'],'@@')!==false){
							$groupContact[$member['UserName']]=$memberInfo;
						}
						else{
							$privateContact[$member['UserName']]=$memberInfo;
						}
					}
				}
				$data=['officialContact'=>$officialContact,'groupContact'=>$groupContact,'privateContact'=>$privateContact];
				$this->setConfig($data);
				return ['status'=>true,'data'=>$data];
			}

			return ['status'=>false,'rqstResult'=>$result];
		}

		public function syncCheck($url=null){
			$time=time();
			$config=$this->getConfig();
			$syncKey='';
			$rtn=['status'=>false,'msg'=>'SyncKey is Null'];

			if(isset($config['syncKey'])){
				$syncList=$config['syncKey']['List'];
				foreach($syncList as $info){
					$tmp[]=$info['Key'].'_'.$info['Val'];
				}
				$syncKey=implode('|',$tmp);
			}
			if(!empty($syncKey)){
				$param=['r'=>$time,'skey'=>$config['skey'],'sid'=>$config['wxsid'],'uin'=>$config['wxuin'],'deviceid'=>$config['deviceid'],'synckey'=>$syncKey,'_'=>$time];
				$url='https://webpush.wx.qq.com/cgi-bin/mmwebwx-bin/synccheck?'.http_build_query($param);
				//r=1508550723546&skey=%40crypt_229b0906_884c90eb50ca017e830b866a8f797de9&sid=lG2bQnEqcgDMSnyj&uin=125740195&deviceid=e502084594816129&synckey=1_706876531%7C2_706876564%7C3_706876533%7C1000_1508546882&_=1508550428405

				$rqstCookie=$config['cookie'];
				$result=self::curlRequest($url,null,null,$rqstCookie);
				$result=$result['data'];//window.synccheck={retcode:"0",selector:"2"}
				$rgx='/window.synccheck={retcode:"(\d+)",selector:"(\d+)"}/';
				if(preg_match($rgx,$result,$matchResult)){
					$retcode=$matchResult[1];
					$selector=$matchResult[2];
					$rtn=['status'=>true,'retcode'=>$retcode,'selector'=>$selector];
				}
				else{
					$rtn=['status'=>false,'msg'=>'parse retcode and selector faild','data'=>$result];
				}
			}

			return $rtn;
		}

		//获取群数据--待完善
		public function getBatchContact($url=null){
			if(!$this->isLogin()){
				return ['status'=>false,'msg'=>__METHOD__.' Error：No Login!'];
			}

			$time=time();
			$config=$this->getConfig();
			$param=['pass_ticket'=>$config['pass_ticket'],'r'=>$time,'type'=>'ex'];
			$url=(empty($url)?$config['baseUrl'].'/webwxbatchgetcontact':$url).'?'.http_build_query($param);
			$rqstCookie=$config['cookie'];
			$rqstData=[];
			$result=self::curlRequest($url,$rqstData,null,$rqstCookie);

			return $result['data'];
		}

		public function sync($url=null){
			// if(!$this->isLogin()){
			// 	return ['status'=>false,'msg'=>__METHOD__.' Error：No Login!'];
			// }

			$time=time();
			$config=$this->getConfig();
			$param=['sid'=>$config['wxsid'],'skey'=>$config['skey'],'pass_ticket'=>$config['pass_ticket']];
			$url=(empty($url)?$config['baseUrl'].'/webwxsync':$url).'?'.http_build_query($param);

			$rqstData=$this->getBaseRqstParam();
			$rqstData['SyncKey']=$config['syncKey'];
			$rqstData['rr']=~$time;

			$rqstCookie=$config['cookie'];
			$result=self::curlRequest($url,$rqstData,null,$rqstCookie);
			$rtn=['status'=>false,'rqstResult'=>$result];

			$data=json_decode($result['data'],true);
			if(!empty($data)&&$data['BaseResponse']['Ret']==0){
				$cfg=['cookie'=>$result['cookie'],'syncKey'=>$data['SyncKey']];
				$this->setConfig($cfg);
				//$rtn=['status'=>true,'result'=>$data];
				$rtn=['status'=>true,'data'=>$data['AddMsgList']];
			}

			return $rtn;
		}

		public function listenMsg(){
			Log::write('开始监听：');
			$listen=true;
			$msg='Session 过期';
			while($listen){
				$time=time();
				$checkResult=$this->syncCheck();
				if($checkResult['status']){
					$retcode=$checkResult['retcode'];
					$selector=$checkResult['selector'];
					Log::write('syncCheck Result: retcode:'.$retcode.'   selector:'.$selector);
					switch($retcode){
						case 1100:
							$msg='在手机上退出web登陆';
							$listen=false;
							break;
						case 1101:
							$msg='在其他地方登陆了web微信';
							$listen=false;
							break;
						case 0:
							//$selector 0:没有消息 2:有新消息 7:在手机上操作了微信
							if($selector==2){
								$syncResult=$this->sync();
								if($syncResult['status']){
									$dealResult=$this->dealMsg($syncResult['data']);
									if($dealResult){
										Log::write('消息回复成功！');
									}
								}
							}
							break;
						default:
							$listen=false;
							break;
					}

					// $span=time()-$tim;
					// if($span<20){
					// 	sleep($span);
					// }
				}
				else{
					$msg='syncCheck faild:'.$checkResult['msg'];
					$listne=false;
				}
			}
			Log::write('监听结束：'.$msg);
		}

		public function dealMsg($data){
			$rtn=true;
			if(!empty($data)){
				foreach($data as $msg){
					$msgId=$msg['MsgId'];
					$msgType=$msg['MsgType'];
					$fromUserName=$msg['FromUserName'];
					$msgContent=html_entity_decode($msg['Content']);

					switch($msgType){
						case 1:
							//文本消息
							if(!empty($msgContent)){
								$fromUserInfo=$this->getContactInfo($fromUserName);
								if(in_array($fromUserInfo['NickName'],['蘭風','桔梗','giles'])){
									$rpsnData=$this->dealTxtMsg($msgContent);
									$rtn=$this->responseMsg($fromUserName,$rpsnData);
								}
							}
							break;
						case 37:
							//添加好友请求
							$recommendInfo=$msg['recommendInfo'];
							$rtn=$this->dealRecommendMsg($recommendInfo['UserName'],$recommendInfo['Tickit']);
							break;
					}
				}
			}

			return $rtn;
		}

		private function dealTxtMsg($content){
			return $content.' -^_^-';
		}

		private function dealRecommendMsg($userName,$verifyTicker,$opcode=3){
			$time=time();
			$config=$this->getConfig();
			$param=['lang'=>self::LANG,'r'=>$time,'pass_ticket'=>$config['pass_ticket']];
			$url=$config[baseUrl].'/webwxverifyuser?'.http_build_query($param);
			$rqstData=['BaseResponse'=>$this->getBaseRqstParam(),'Opcode'=>$opcode,'VerifyUserListSize'=>1,'VerifyUserList'=>['Value'=>$userName,'VerifyUserTicket'=>$verifyTicker],'VerifyContent'=>'','SceneListCount'=>1,'SceneList'=>[33],'skey'=>$config['skey']];
			$result=self::curlRequest($url,$rqstData);

			return $result['data'];
		}

		public function responseMsg($toUserName,$content){
			if(!$this->isLogin()){
				return ['status'=>false,'msg'=>__METHOD__.' Error：No Login!'];
			}
			$time=time();
			$msgId=1000*$time.substr(uniqid(),0,5);
			$config=$this->getConfig();
			$param=['lang'=>self::LANG,'pass_ticket'=>$config['pass_ticket']];
			$url=$config['baseUrl'].'/webwxsendmsg?'.http_build_query($param);

			$msg=['ClientMsgId'=>$msgId,'LocalID'=>$msgId,'Type'=>1,'FromUserName'=>$config['userInfo']['UserName'],'ToUserName'=>$toUserName,'Content'=>$content];

			$rqstData=$this->getBaseRqstParam();
			$rqstData['Msg']=$msg;
			$rqstData['Scene']=0;

			$rqstCookie=$config['cookie'];

			$result=self::curlRequest($url,$rqstData,null,$rqstCookie);
			$data=json_decode($result['data'],true);
			return !empty($data)&&$data['BaseResponse']['Ret']==0;
		}

		private function getContactInfo($userName){
			$userInfo=null;
			if(!empty($userName)){
				$config=$this->getConfig();
				if(isset($config['privateContact'])&&isset($config['privateContact'][$userName])){
					$userInfo=$config['privateContact'][$userName];
				}
				else if(isset($config['officialContact'])&&isset($config['officialContact'][$userName])){
					$userInfo=$config['officialContact'][$userName];
				}
				else if(isset($config['groupContact'])&&isset($config['groupContact'][$userName])){
					$userInfo=$config['groupContact'][$userName];
				}
			}

			return $userInfo;
		}

		private function isLogin(){
			$result=$this->syncCheck();

			return ($result['status']&&$result['retcode']==0);
		}

		public function getConfig($param=null){
			$config=$this->config;
			return empty($param)?$config:(isset($config[$param])?$config[$param]:null);
		}

		private function setConfig($param){
			foreach($param as $key=>$val){
				$this->config[$key]=$val;
			}
		}

		private static function curlRequest($url,$rqstData=null,$rqstHeader=null,$rqstCookie=null){
			$rqstHeader['User-Agent']='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';

			$curl=curl_init($url);
			$curlOpt=[
				CURLOPT_FOLLOWLOCATION=>FALSE,
				CURLOPT_RETURNTRANSFER=>TRUE,
				CURLOPT_CONNECTTIMEOUT=>10,
				CURLOPT_HEADER=>TRUE,
				CURLOPT_TIMEOUT=>30,
			];

			if(strlen($url)>5&&strtolower(substr($url,0,5))=='https'){
				$curlOpt[CURLOPT_SSL_VERIFYPEER]=FALSE;
				$curlOpt[CURLOPT_SSL_VERIFYHOST]=FALSE;
			}


			if(!empty($rqstData)){
				//$param=http_build_query($rqstData,'','&',PHP_QUERY_RFC3986);
				$param=json_encode($rqstData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
				$curlOpt[CURLOPT_POST]=TRUE;
				$curlOpt[CURLOPT_POSTFIELDS]=$param;
				$rqstHeader['Content-Length']=strlen($param);
				//$rqstHeader['X-Requested-With']='XMLHttpRequest';
				$rqstHeader['Accept']='application/json, text/plain, */*';
				$rqstHeader['Content-Type']='application/json;charset=UTF-8';
				$rqstHeader['Referer']=isset($rqstHeader['Referer'])?$rqstHeader['Referer']:'https://wx2.qq.com/';
			}
			else{
				$rqstHeader['Referer']=isset($rqstHeader['Referer'])?$rqstHeader['Referer']:'https://wx.qq.com/';
			}

			$header=[];
			foreach($rqstHeader as $key=>$value){
				$header[]=($key.': '.$value);
			}
			$curlOpt[CURLOPT_HTTPHEADER]=$header;

			if(!empty($rqstCookie)){
				$cookie='';
				foreach($rqstCookie as $key=>$value){
					$cookie.=($key.'='.$value.';');
				}
				$curlOpt[CURLOPT_COOKIE]=$cookie;
			}

			curl_setopt_array($curl,$curlOpt);

			$result=curl_exec($curl);

			$status=curl_errno($curl);
			if($status){
				$error=curl_error($curl);
				$result=$error;
			}

			curl_close($curl);

			//获取请求的header和body
			$tmp=explode("\r\n\r\n",$result);
			$tmpCnt=count($tmp);
			$rspnData=$tmp[$tmpCnt-1];
			$rspnHeader=$tmp[$tmpCnt-2];

			//获取并设置cookie
			preg_match_all('/set-cookie:(.*);/iU',$rspnHeader,$setCookie);
			if(!empty($setCookie[1])){
				foreach($setCookie[1] as $info){
					list($key,$value)=explode('=',$info);
					$rqstCookie[trim($key)]=trim($value);
				}
			}

			return ['data'=>$rspnData,'cookie'=>$rqstCookie];
		}

		private function getBaseRqstParam(){
			$config=$this->getConfig();
			$param=['BaseRequest'=>['DeviceID'=>$config['deviceid'],'Sid'=>$config['wxsid'],'Skey'=>$config['skey'],'Uin'=>$config['wxuin']]];
			return $param;
		}

	}
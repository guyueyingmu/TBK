<?php
namespace app\common\controller;

use think\Controller;
use think\Config;
use think\Loader;
use think\Session;
use think\Cache;
use think\Log;
use think\Url;

class Base extends Controller{

	private $oAuthState='oAuthState';
	private $noLogin=[
		'base'=>['login','loginSubmit','logout','weChatCgi','getSession','getCache','clearCache','error','clearSession','lgk','tstx'],
		'client'=>[
			'index'=>['index','getCouponData'],
		],
		'manage'=>[],
	];

	protected function _initialize(){
		define('MODULE',$this->request->module());
		define('CONTROLLER',$this->request->controller());
		define('ACTION',$this->request->action());

		$code=$this->request->get('code');
		$originId=$this->request->get('state');
		if(!empty($code)&&!empty($originId)&&!$this->isLogin()){
			$weChat=new WeChat($originId);
			$userInfo=$weChat->getOAuthUserInfo($code);

			$this->weChatLogin($userInfo['openid'],$weChat);
			if(!$this->isLogin()){
				Log::write('微信登录失败：登录失败');
				return $this->error('登录失败');
			}

			if(!$this->permissionVerify()){
				return $this->error('您没有访问权限');
			}
		}
		else{
			if($this->isLogin()){
				if(!$this->permissionVerify()){
					return $this->error('您没有访问权限');
				}
			}
			else if(!(in_array(ACTION,$this->noLogin['base'])||(isset($this->noLogin[MODULE])&&isset($this->noLogin[MODULE][strtolower(CONTROLLER)])&&in_array(ACTION,$this->noLogin[MODULE][strtolower(CONTROLLER)])))){
				if(in_array(MODULE,['client'])){
					try{
						$this->weChatOAuth();
						return $this->error('正在登录...');
					}
					catch(\Exception $exp){
						$this->error('微信授权错误');
					}
				}
				else{
					$this->redirect('/login');
				}
			}
		}

		// $tradeObj=new WeChatTrade();
		// $weChatCfg=$tradeObj->getWeChatConfig();
		// $this->assign('weChatCfg',$weChatCfg);
		// $this->assign('weChatCfg',['appId'=>'','timestamp'=>'','nonceStr'=>'','signature'=>'']);
	}

	protected function permissionVerify($userInfo=null){
		$userInfo=empty($userInfo)?$this->getUserInfo():$userInfo;
		$moduleType=Config::get('userType.'.MODULE);

		return !empty($userInfo)&&isset($userInfo['type'])&&(($userInfo['type']&$moduleType)==$moduleType);
	}

	protected function getUserInfo(){
		return Session::get('userInfo');
	}

	protected function isLogin(){
		$userInfo=Session::get('userInfo');
		return empty($userInfo)?false:true;
	}

	protected function weChatOAuth(){
		$originId=$this->request->pram('originId');
		if(empty($originId)){
			$this->error();
		}

		$redirectUri=$this->request->url(true);
		$weChat=new WeChat($originId);
		$weChat->oAuth($redirectUri,$originId);
	}

	protected function weChatLogin($openId,&$weChat){
		if(empty($openId)){
			Log::write('微信登录失败：没有openId');
			return $this->error('登录失败');
		}

		//$weChat=new WeChat();
		$wcInfo=$weChat->getUserInfo($openId);

		$userMdl=Loader::model('User');
		$userInfo=$userMdl->getInfo(['where'=>['openId'=>$openId]]);

		if($userInfo===false){
			Log::write('微信登录失败：查询用户信息失败');
			return $this->error('登录失败');
		}

		if(isset($wcInfo['openid'])){
		$data=['originId'=>$originId,'openId'=>$openId,'unionId'=>isset($wcInfo['unionid'])?$wcInfo['unionid']:'','groupId'=>$wcInfo['groupid'],'nickName'=>$wcInfo['nickname'],'sex'=>$wcInfo['sex'],'img'=>$wcInfo['headimgurl'],'subscribe'=>$wcInfo['subscribe'],'subscribeTime'=>date('Y-m-d H:i:s',$wcInfo['subscribe_time']),'city'=>$wcInfo['city'],'province'=>$wcInfo['province'],'country'=>$wcInfo['country'],'remark'=>$wcInfo['remark']];
			// $data=[
			// 	'openId'=>$openId,
			// 	'nickName'=>$wcInfo['nickname'],
			// 	'unionId'=>isset($wcInfo['unionid'])?$wcInfo['unionid']:'',
			// 	'subscribe'=>$wcInfo['subscribe'],
			// 	'sex'=>$wcInfo['sex'],
			// 	'img'=>$wcInfo['headimgurl'],
			// ];

			if(empty($userInfo)){
				$data['type']=Config::get('userType.client');
				$userId=$userMdl->add($data);
				if($userId===false){
					Log::write('微信登录失败：添加用户失败');
					return $this->error('登录失败');
				}
				$data['id']=$userId;
			}
			else{
				$rst=$userMdl->edit(['where'=>['id'=>$userInfo['id']],'data'=>$data]);
				if($rst===false){
					$data=$userInfo;
				}
				else{
					$data['id']=$userInfo['id'];
					$data['type']=$userInfo['type'];
				}
			}
		}
		else if(!empty($userInfo)){
			$data=$userInfo;
		}
		else{
			Log::write('微信登录失败：获取用户微信信息失败');
			return $this->error('登录失败');
		}

		foreach($data as $key=>$val){
			Session::set('userInfo.'.$key,$val);
		}
	}

	protected function weChatTrade($param,$tradeType='JSAPI'){
		$tradeObj=new WeChatTrade($tradeType);
		$weChatCfg=$tradeObj->getWeChatConfig();
		$tradeCfg=$tradeObj->getTradeConfig($param);

		return ['weChatCfg'=>$weChatCfg,'tradeCfg'=>$tradeCfg];
	}

	protected function weChatTradeNotify($data=null){
		if(empty($data)){
			$xml=file_get_contents('php://input');
			$obj=simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA);
			$data=json_decode(json_encode($obj),true);
		}

		$xml=file_get_contents('php://input');
		Log::write('微信支付异步通知：'.$xml,'info');

		if(isset($data['trade_type'])){
			$tradeObj=new WeChatTrade($data['trade_type']);
			return $tradeObj->verifySign($data);
		}
		return false;
	}

	protected function getJsTicket(){
		$tradeObj=new WeChatTrade();
		return $tradeObj->getJsTicket();
	}

	protected function curlRequest($url,$data=null,$header=null){
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
		return ['data'=>$result,'info'=>$info];
	}

	public function login(){
		if(strtolower(MODULE)!='manage'){
			return $this->error();
		}
		if($this->isLogin()){
		   $this->redirect(Url::build('/admin'));
		}
		else{
		   return $this->fetch('common@public/login');
		}
	}

	public function logout(){
		Session::clear();
	}

	public function loginSubmit(){
		if($this->request->isAjax()){
			$data=$this->request->param();
			$dataVerifyRst=dataVerify($data,['name'=>true,'pwd'=>true]);
			if($dataVerifyRst){
				$userMdl=Loader::model('User');
				$userInfo=$userMdl->getInfo(['where'=>['name'=>$data['name'],'password'=>md5($data['pwd'])]]);
				if(!empty($userInfo)){
					if($this->permissionVerify($userInfo)){
						foreach($userInfo as $key=>$val){
							Session::set('userInfo.'.$key,$val);
						}

						return ['status'=>true,'msg'=>Url::build('/admin')];
					}
					else{
						return ['status'=>false,'msg'=>'您没有访问权限'];
					}
				}
				else{
					return ['status'=>false,'msg'=>'用户名或密码错误'];
				}
			}
			else{
				return ['status'=>false,'msg'=>'用户名和密码不能为空'];
			}

		}
		else{
			return $this->error();
		}
	}

	public function lgk(){
		$id=$this->request->param('idx',0,'intval');
		if($id<1){
			$this->error();
		}
		$userMdl=Loader::model('User');
		$userInfo=$userMdl->getInfo(['where'=>['id'=>$id]]);

		if(!empty($userInfo)){
		   foreach($userInfo as $key=>$val){
			   Session::set('userInfo.'.$key,$val);
		   }
		   $this->redirect(Url::build('client/Receive/index'));
		}
		else{
		   $this->error('Login Faild');
		}
	}

	public function weChatCgi(){
		$weChat=new WeChat();
		$echoStr=$weChat->index();
		return $echoStr;
	}

	public function clearSession(){
		$name=$this->request->param('name');
		if(empty($name)){
			Session::clear();
		}
		else{
			Session::delete($name);
		}

		$data=Session::get();
		dump($data);
	}

	public function getSession(){
		$name=$this->request->param('name');
		$data=Session::get($name);
		dump($data);
	}

	public function clearCache(){
		$name=$this->request->param('name');
		if(empty($name)){
			Cache::clear();
		}
		else{
			Cache::rm($name);
		}

		$data=Cache::get($name);
		dump($data);
	}

	public function getCache(){
		$name=$this->request->param('name');
		$data=Cache::get($name);
		dump($data);
	}

	public function cacheData(){
		$name=$this->request->param('name');
		if($name!=''){
			$method='get'.ucfirst($name);
			if(is_callable([$this,$method])){
				$this->$method();
				dump(Cache::get($name));
			}
			else{
				return '非法的缓存标识';
			}
		}
		else{
			return '请输入缓存标识';
		}
	}

	public function _empty(){
		return $this->error('您请求的页面不存在');
	}

	public function tstx(){
		$obj=new TBK('128077217','gh_efba84cec87e');


		$wxObj=new WeChat('gh_efba84cec87e');

		$content='个人信息';

		$rst=$wxObj->dealTxtMsg($content,'okLYjvx7YS3aF1sOdqobjXjeg42w');
		dump($rst);return;
	}
}

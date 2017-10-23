<?php
namespace app\common\controller;

use app\common\util\WX;
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
		'base'=>['login','loginSubmit','logout','weChatCgi','getSession','cleanCache','error','clearSession','lgk','tstx'],
		'client'=>[
			'index'=>['index','tst'],
		],
		'manage'=>[
			'index'=>['index','wx','waitLogin','tstx','wxLogin'],
		],
	];

	protected function _initialize(){
		define('MODULE',$this->request->module());
		define('CONTROLLER',$this->request->controller());
		define('ACTION',$this->request->action());

		$code=$this->request->get('code');
		$state=$this->request->get('state');
		if(!empty($code)&&$state==$this->oAuthState&&!$this->isLogin()){
			$weChat=new WeChat();
			$userInfo=$weChat->getOAuthUserInfo($code);

			$this->weChatLogin($userInfo['openid']);
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
			else if(MODULE=='manage'){

			}
			else if(!(in_array(ACTION,$this->noLogin['base'])||(isset($this->noLogin[MODULE])&&isset($this->noLogin[MODULE][strtolower(CONTROLLER)])&&in_array(ACTION,$this->noLogin[MODULE][strtolower(CONTROLLER)])))){
				if(in_array(MODULE,['client','operate'])){
					try{
						$this->weChatOAuth();
						return $this->error('正在登录...');
					}
					catch(\Exception $exp){
						$this->error('微信授权错误');
					}
				}
				else{
					$this->redirect(Url::build('/login'));
				}
			}
		}

		// $tradeObj=new WeChatTrade();
		// $weChatCfg=$tradeObj->getWeChatConfig();
		// $this->assign('weChatCfg',$weChatCfg);
		//$this->assign('weChatCfg',['appId'=>'','timestamp'=>'','nonceStr'=>'','signature'=>'']);
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
		$redirectUri=$this->request->url(true);
		$weChat=new WeChat();
		$weChat->oAuth($redirectUri,$this->oAuthState);
	}

	protected function weChatLogin($openId){
		if(empty($openId)){
			Log::write('微信登录失败：没有openId');
			return $this->error('登录失败');
		}

		$weChat=new WeChat();
		$wcInfo=$weChat->getUserInfo($openId);

		$userMdl=Loader::model('User');
		$userInfo=$userMdl->getInfo(['where'=>['openId'=>$openId]]);

		if($userInfo===false){
			Log::write('微信登录失败：查询用户信息失败');
			return $this->error('登录失败');
		}

		if(isset($wcInfo['openid'])){
			$data=[
				'openId'=>$openId,
				'nickName'=>$wcInfo['nickname'],
				'unionId'=>isset($wcInfo['unionid'])?$wcInfo['unionid']:'',
				'subscribe'=>$wcInfo['subscribe'],
				'sex'=>$wcInfo['sex'],
				'img'=>$wcInfo['headimgurl'],
			];

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

	protected function getExpressData($expressId=null){
		$data=Cache::get('expressData');
		if(empty($data)){
			$orderAry=['sort'=>'asc'];
			$expressMdl=Loader::model('Express');
			$expressData=$expressMdl->getAll(['where'=>['status'=>1],'order'=>$orderAry]);
			foreach($expressData as &$express){
				$data[]=['text'=>$express['name'],'value'=>$express['id'],'code'=>$express['code']];
			}

			Cache::set('expressData',$data,0);
		}

		if(!empty($expressId)){
			foreach($data as $info){
				if($expressId==$info['value']){
					return $info;
				}
			}
		}

		return $data;
	}

	protected function getExpenseData($expressId=null,$provinceId=null){
		$data=Cache::get('expenseData');
		if(empty($data)){
			$expenseMdl=Loader::model('Expense');
			$expressMdl=Loader::model('Express');

			$expressData=$expressMdl->getField(['where'=>['status'=>1],'field'=>['id']]);
			foreach($expressData as $expressId){
				$expenseData=$expenseMdl->getField(['where'=>['expressId'=>$expressId],'field'=>['standard','additional'],'key'=>'provinceId']);
				$data[$expressId]=$expenseData;
			}

			Cache::set('expenseData',$data,0);
		}

		return (empty($expressId)||empty($provinceId))?$data:$data[$expressId][$provinceId];
	}

	protected function getOriginData(){
		$data=Cache::get('originData');
		if(empty($data)){
			$townMdl=Loader::model('Town');
			$vilageMdl=Loader::model('Vilage');

			$orderAry=['convert(name using gbk)'=>'asc'];

			$townData=$townMdl->getAll(['where'=>['origin'=>1],'order'=>$orderAry]);
			foreach($townData as &$town){
				$vilageData=$vilageMdl->getAll(['where'=>['townId'=>$town['id'],'origin'=>1],'order'=>$orderAry]);
				$vilageTmp=[];
				foreach($vilageData as &$vilage){
					$vilageTmp[]=['text'=>$vilage['name'],'value'=>$vilage['id']];
				}
				$data[]=['text'=>$town['name'],'value'=>$town['id'],'children'=>$vilageTmp];
			}

			Cache::set('originData',$data,0);
		}

		return $data;
	}

	protected function getDestinationData(){
		$data=Cache::get('destinationData');
		if(empty($data)){
			$provinceMdl=Loader::model('Province');
			$cityMdl=Loader::model('City');
			$countryMdl=Loader::model('Country');

			$orderAry=['convert(name using gbk)'=>'asc'];

			$provinceData=$provinceMdl->getAll(['where'=>['destination'=>1],'order'=>$orderAry]);
			foreach($provinceData as &$province){
				$cityData=$cityMdl->getAll(['where'=>['provinceId'=>$province['id'],'destination'=>1],'order'=>$orderAry]);
				$cityTmp=[];
				foreach($cityData as &$city){
					$countryData=$countryMdl->getAll(['where'=>['cityId'=>$city['id'],'destination'=>1],'order'=>$orderAry]);
					$countryTmp=[];
					foreach($countryData as $country){
						$countryTmp[]=['text'=>$country['name'],'value'=>$country['id'],'fullName'=>$country['fullName']];
					}
					$cityTmp[]=['text'=>$city['name'],'value'=>$city['id'],'children'=>$countryTmp];
				}
				$data[]=['text'=>$province['name'],'value'=>$province['id'],'children'=>$cityTmp];
			}

			Cache::set('destinationData',$data,0);
		}

		return $data;
	}

	protected function getVilageToProvince($vilageId=null){
		$data=Cache::get('vilageToProvince');
		if(empty($data)){
			$vilageMdl=Loader::model('Vilage');
			$fieldAry=['v.id'=>'vilageId','v.name'=>'vilageName','t.id'=>'townId','t.name'=>'townName','c.id'=>'countryId','c.name'=>'countryName','ct.id'=>'cityId','ct.name'=>'cityName','p.id'=>'provinceId','p.name'=>'provinceName'];
			$joinAry=[['__TOWN__ t','v.townId=t.id'],['__COUNTRY__ c','t.countryId=c.id'],['__CITY__ ct','c.cityId=ct.id'],['__PROVINCE__ p','ct.provinceId=p.id']];
			$objData=$vilageMdl->alias('v')->field($fieldAry)->join($joinAry)->select();
			foreach($objData as $obj){
				$tmp=$obj->getData();
				$tmp['name']=$tmp['provinceName'].'-'.$tmp['cityName'].'-'.$tmp['countryName'].'-'.$tmp['townName'].'-'.$tmp['vilageName'];
				$data[$tmp['vilageId']]=$tmp;
			}
			Cache::set('vilageToProvince',$data,0);
		}

		return empty($vilageId)?$data:$data[$vilageId];
	}

	protected function getCountryToProvince($countryId=null){
		$data=Cache::get('countryToProvince');
		if(empty($data)){
			$countryMdl=Loader::model('Country');
			$fieldAry=['c.id'=>'countryId','c.name'=>'countryName','ct.id'=>'cityId','ct.name'=>'cityName','p.id'=>'provinceId','p.name'=>'provinceName'];
			$joinAry=[['__CITY__ ct','c.cityId=ct.id'],['__PROVINCE__ p','ct.provinceId=p.id']];
			$objData=$countryMdl->alias('c')->field($fieldAry)->join($joinAry)->select();
			foreach($objData as $obj){
				$tmp=$obj->getData();
				$tmp['name']=$tmp['provinceName'].'-'.$tmp['cityName'].'-'.$tmp['countryName'];
				$data[$tmp['countryId']]=$tmp;
			}
			Cache::set('countryToProvince',$data,0);
		}

		return empty($countryId)?$data:$data[$countryId];
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

	protected function getAccessToken(){
		$weChatObj=new WeChat();
		return $weChatObj->getAccessToken();
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
		return array('data'=>$result,'info'=>$info);
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
		return ['status'=>true];
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
		//$this->error();


		return;


		$wxCfg=Session::get('wx');
		$obj=new WX($wxCfg);


		$result=$obj->syncCheck();
		dump($result);

		if($result['status']&&$result['retcode']==0){
			if(!isset($wxCfg['privateContact'])){
				$result=$obj->getContact();
				if($result['status']){
					dump('getContact success');
					$wxCfg=$obj->getConfig();
					Session::set('wx',$wxCfg);
				}
				else{
					dump('getContact faild');
				}
			}

			$pf=pcntl_fork();
			if($pf){
				$obj->listenMsg();
				return;
			}
			return;
		}

		if($result['status']&&$result['selector']==2){
			$syncResult=$obj->sync();
			dump($syncResult);
			if($syncResult['status']){
				$wxCfg=$obj->getConfig();
				Session::set('wx',$wxCfg);
				//$obj->dealMsg($syncResult['data']);
			}
			else{
				$result=$obj->syncCheck();
				dump($result);
			}
		}



		// $result=$obj->responseMsg('@cc38115630822e5aea89b24fe9f11204','这是什么？？');
		// dump($result);
	}
}

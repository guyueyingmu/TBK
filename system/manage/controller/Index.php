<?php
namespace app\manage\controller;

use app\common\controller\Base;
use app\common\util\WX;
use think\Session;
use think\Loader;
use think\Log;

class Index extends Base{

	public function index(){
		return $this->fetch();
	}

	public function wx(){
		Session::delete('wx');
		$obj=new WX();
		$result=$obj->getQrCode();

		if($result['status']){
			$wxCfg=$obj->getConfig();
			Session::set('wx',$wxCfg);

			$result['img']='data:image/jpg;base64,'.base64_encode($result['img']);
			$this->assign($result);
			return view();
		}
		else{
			$this->error(['msg'=>$result['msg']]);
		}
	}

	public function waitForLogin(){
		//$uuid=$this->request->param('uuid');
		$wxCfg=Session::get('wx');
		$obj=new WX($wxCfg);
		$result=$obj->waitForLogin();
		if($result['code']==200){
			$wxCfg=$obj->getConfig();
			Session::set('wx',$wxCfg);
		}
		return $result;
	}

	public function loginRedirect(){
		$wxCfg=Session::get('wx');
		$obj=new WX($wxCfg);

		$result=$obj->loginRedirect();
		$wxCfg=$obj->getConfig();
		Session::set('wx',$wxCfg);

		return $result;
	}

	public function tstx(){
		$wxCfg=Session::get('wx');
		$obj=new WX($wxCfg);

		$result=$obj->getQrCode();
		dump($resutl);

		// $result=$obj->loginRedirect();
		// dump($result);
		// //if($result['status']){
		// 	$wxCfg=$obj->getConfig();
		// 	Session::set('wx',$wxCfg);
		// //}

		// $result=$obj->getContact();
		// dump($result);

		// $result=$obj->sync();
		// dump($result);
	}

}

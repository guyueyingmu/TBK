<?php
namespace app\manage\controller;

use app\common\controller\Base;
use app\common\controller\TBK;
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
			$this->error($result['msg']);
		}
	}

	public function wxLogin(){
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

	public function wxRedirect(){
		$wxCfg=Session::get('wx');
		$obj=new WX($wxCfg);

		$result=$obj->loginRedirect();
		$wxCfg=$obj->getConfig();
		Session::set('wx',$wxCfg);

		return $result;
	}

	private function wxListen(){
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
	}

}

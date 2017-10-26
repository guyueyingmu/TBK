<?php

namespace app\manage\controller;

use app\common\controller\Base;
use app\common\controller\TBK;
use think\Session;
use think\Loader;
use think\Log;

class Account extends Base{
	public function index(){
		if($this->request->isAjax()){
			$userInfo=$this->getUserInfo();
			$pgIdx=$this->request->param('page',1,'intval');

			$mdl=Loader::model('Account');
			$whereAry=['userId'=>$userInfo['id'],'isValid'=>1];
			$fieldAry=['originId','wxId','wxName','tbkName','tbkId','adZoneId','siteId'];
			$data=$mdl->getAll(['where'=>$whereAry,'field'=>$fieldAry,'page'=>$pgIdx,'limit'=>11]);

			if(!empty($data)){
				foreach($data['data'] as &$info){
					$tbkObj=new TBK($info['tbkId'],$info['originId']);
					$loginResult=$tbkObj->isLogin();
					$info['loginStatus']=$loginResult['status'];
				}
			}

			$data['formName']='账号信息';
			$data['formInfo']=' 共 '.$data['page']['rcdCnt'].' 条数据';
			$this->assign($data);

			return view();
		}
		else{
			$this->error();
		}
	}

	public function getTbkQrCode(){
		if($this->request->isAjax()){
			$tbkId=$this->request->param('tbkId');
			$originId=$this->request->param('originId');
			$obj=new TBK($tbkId,$originId);
			$result=$obj->getQrCode();
			return $result;
		}
		else{
			$this->error();
		}
	}

	public function tbkLogin(){
		if($this->request->isAjax()){
			$tbkId=$this->request->param('tbkId');
			$originId=$this->request->param('originId');
			$lgToken=$this->request->param('lgToken');

			$obj=new TBK($tbkId,$originId);
			$result=$obj->waitForLogin($lgToken);

			return $result;
		}
		else{
			$this->error();
		}
	}

	public function tbkRedirect(){
		if($this->request->isAjax()){
			$tbkId=$this->request->param('tbkId');
			$originId=$this->request->param('originId');

			$obj=new TBK($tbkId,$originId);
			$result=$obj->loginRedirect();

			return $result;
		}
		else{
			$this->error();
		}
	}
}
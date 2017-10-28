<?php
namespace app\client\controller;

use app\common\controller\Base;
use app\common\util\TBK;
use think\Loader;
use think\Config;
use think\Request;
use think\Log;


class Index extends Base{

	public function index(Request $request){
		$originId=$request->param('originId');
		$kw=$request->param('kw','trim,htmlspecialchars');
		$this->assign(['originId'=>$originId,'kw'=>$kw]);
		return view();
	}

	public function getCouponData(Request $request){
		if($request->isAjax()){
			$pgIdx=$request->param('pgIdx',1,'intval');
			$originId=$request->param('originId');
			$kw=$request->param('kw','','trim,htmlspecialchars');
			if(empty($kw)){
				return ['status'=>false,'msg'=>'请输入您要查找的商品！'];
			}

			$mdl=Loader::model('Account');
			if(!empty($originId)){
				$accountInfo=$mdl->getInfo(['where'=>['originId'=>$originId,'isValid'=>1]]);
				if(empty($accountInfo)){
					return ['status'=>false,'msg'=>'该公众号尚未接入服务：'.$originId];
				}
			}
			else{
				$accountInfo=$mdl->getInfo(['where'=>['isValid'=>1]]);
				if(empty($accountInfo)){
					return ['status'=>false,'msg'=>'系统配置错误，请联系管理员！'];
				}
			}

			$obj=new TBK($accountInfo['tbkId'],$originId);
			$loginStatus=$obj->isLogin();
			if($loginStatus['status']){
				$data=$obj->getCouponItems($kw,$pgIdx);
				if(empty($data)||empty($data['data'])){
					return ['status'=>false,'msg'=>'您所搜索的商品没有优惠信息！'];
				}

				$itemData=[];
				foreach($data['data'] as $info){
					$linkInfo=$obj->getLink($info['auctionId']);
					if(isset($linkInfo['couponLinkTaoToken'])&&!empty($linkInfo['couponLinkTaoToken'])){
						$itemData[]=['img'=>$info['pictUrl'],'businessName'=>$info['nick'],'itemId'=>$info['auctionId'],'price'=>$info['zkPrice'],'coupon'=>$info['couponAmount'],'leftCount'=>$info['couponLeftCount'],'rebate'=>getDebate($info['tkCommFee'],$accountInfo['rebate']),'couponToken'=>$linkInfo['couponLinkTaoToken']];
					}
				}

				if(empty($itemData)){
					return ['status'=>false,'msg'=>'淘宝太忙了，请稍后重试！'];
				}
				else{
					return ['status'=>true,'data'=>$itemData];
				}
			}
			else{
				return ['status'=>false,'msg'=>'淘宝太忙了，请稍后重试！'];
			}
		}
		else{
			$this->error();
		}
	}

}

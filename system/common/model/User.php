<?php

namespace app\common\model;

use think\Loader;

class User extends Base{
	//处理邀请码消息
	public function dealInvitation($code,$openId,$originId){
		$invitedInfo=$this->getInfo(['where'=>['originId'=>$originId,'openId'=>$openId]]);
		if(empty($invitedInfo)){
			return ['status'=>false,'msg'=>'用户不存在！'];
		}

		if($invitedInfo['fromUserId']==-1){
			$acMdl=Loader::model('Account');
			$acInfo=$acMdl->getInfo(['where'=>['originId'=>$originId]]);
			if(empty($acInfo)){
				return ['status'=>false,'msg'=>'该公众号尚未接入系统：'.$originId];
			}

			$fromId=getUserIdFromInviteCode($code);
			if($fromId<0){
				return ['status'=>false,'msg'=>'您提交的邀请码无效！'];
			}

			$money=0;
			$invitedMoney=$this->getInviteMoney();

			$this->startTrans();

			if($fromId>0){
				$fromInfo=$this->getInfo(['where'=>['id'=>$fromId]]);
				if(empty($fromInfo)){
					$this->rollback();
					return ['status'=>false,'msg'=>'您提交的邀请码无效！'];
				}

				$money=$this->getInviteMoney();
				$result=$this->edit(['where'=>['id'=>$fromId],'data'=>['money'=>['exp','money+'.$money]]]);
				if($result===false){
					$this->rollback();
					Log::write('邀请赠送失败：'.$this->getLastSql());
					return ['status'=>false,'msg'=>'请重新发送您的邀请码！','sql'=>$this->getLastSql()];
				}

				// $mlMdl=Loader::model('MoneyLog');
				// $mlData=[
				// 	['type'=>2,'userId'=>$fromId,'money'=>$money,'relatedUserId'=>$invitedInfo['id']],
				// 	['type'=>1,'userId'=>$invitedInfo['id'],'money'=>$invitedMoney,'relatedUserId'=>$fromId]
				// ];
				// $result=$mlMdl->add($mlData,true);
				// if($result===false){
				// 	$this->rollback();
				// 	Log::write('关注资金纪录失败：'.$mlMdl->getLastSql());
				// 	return '请重新发送您的邀请码！';
				// }
			}

			$result=$this->edit(['where'=>['id'=>$invitedInfo['id']],'data'=>['fromUserId'=>$fromId,'money'=>['exp','money+'.$invitedMoney]]]);
			if($result==false){
				$this->rollback();
				Log::write('关注赠送失败：'.$this->getLastSql());
				return ['status'=>false,'msg'=>'请重新发送您的邀请码！','sql'=>$this->getLastSql()];
			}

			$ivtMdl=Loader::model('Invitation');
			$result=$ivtMdl->add(['userId'=>$fromId,'money'=>$money,'invitedUserId'=>$invitedInfo['id'],'invitedMoney'=>$invitedMoney]);
			if($result===false){
				$this->rollback();
				Log::write('关注纪录失败：'.$ivtMdl->getLastSql());
				return ['status'=>false,'msg'=>'请重新发送您的邀请码！','sql'=>$ivtMdl->getLastSql()];
			}

			$this->commit();
			$data=['invitedMoney'=>$invitedMoney,'money'=>$invitedMoney+$invitedInfo['money']];
			return ['status'=>true,'data'=>$data];
		}
		else{
			return ['status'=>false,'msg'=>'您已成功提交过邀请码！'];
		}

		return ['status'=>false,'msg'=>''];
	}

	//获取用户信息
	public function getUserInfo($openId,$originId){
		$userInfo=$this->getInfo(['where'=>['originId'=>$originId,'openId'=>$openId]]);
		if(empty($userInfo)){
			return ['status'=>false,'msg'=>'用户不存在！'];
		}

		$userId=$userInfo['id'];

		//邀请人数
		$ivtMdl=Loader::model('Invitation');
		$inviteCount=$ivtMdl->getCount(['where'=>['userId'=>$userId]]);

		$orderMdl=Loader::model('Order');
		//订单信息
		$orderInfo=$orderMdl->getField(['where'=>['userId'=>$userId],'field'=>['count(id) as cnt','status'],'group'=>'status','key'=>'status']);
		$orderCount=empty($orderInfo)?0:array_sum($orderInfo);
		$orderFinished=empty($orderInfo)?0:$orderInfo[1];
		$orderUnfinished=empty($orderInfo)?0:$orderInfo[0];

		//优惠券和返利
		$moneyInfo=$orderMdl->getField(['where'=>['userId'=>$userId],'field'=>['sum(coupon) as coupon','sum(userRebate) as rebate'],'group'=>'userId','key'=>'userId']);
		$moneyInfo=empty($moneyInfo)?null:$moneyInfo[$userId];
		$coupon=empty($moneyInfo)?0:$moneyInfo['coupon'];
		$rebate=empty($moneyInfo)?0:$moneyInfo['rebate'];

		//好友返利
		$friendRebate=$orderMdl->getField(['where'=>['friendId'=>$userId],'field'=>['sum(friendRebate) as rebate'],'group'=>'friendId','key'=>'friendId']);
		$friendRebate=empty($friendRebate)?0:$friendRebate[$userId];

		//提现信息
		$wdMdl=Loader::model('Withdraw');
		$wdInfo=$wdMdl->getField(['where'=>['userId'=>$userId],'field'=>['sum(money) as money','status'],'group'=>'status','key'=>'status']);
		$wding=empty($wdInfo)?0:$wdInfo[0];
		$wded=empty($wdInfo)?0:$wdInfo[1];

		$data=['orderCount'=>$orderCount,'orderFinished'=>$orderFinished,'orderUnfinished'=>$orderUnfinished,'coupon'=>$coupon,'rebate'=>$rebate,'wding'=>$wding,'wded'=>$wded,'inviteCount'=>$inviteCount,'money'=>$userInfo['money'],'friendRebate'=>$friendRebate,'inviteCode'=>getInviteCodeFromUserId($userInfo['id'])];
		return ['status'=>true,'data'=>$data];
	}

	private function getInviteMoney($min=1,$max=50){
		return mt_rand($min,$max)/100;
	}
}
<?php

namespace app\common\controller;

use think\Loader;
use think\Request;
use think\Cache;
use think\Log;

class TBK{
	private $tbkId=0;
	private $originId='';

	public function __construct($tbkId,$originId){
		$this->tbkId=$tbkId;
		$this->originId=$originId;
	}

	public function getQrCode(){
		$url='https://www.alimama.com/member/login.htm?forward=https%3A%2F%2Fpub.alimama.com%2Fpromo%2Fsearch%2Findex.htm';
		$result=self::curlRequest($url);
		$cookie=$result['cookie'];

		$url='https://log.mmstat.com/eg.js';
		$result=self::curlRequest($url,null,$cookie);
		$cookie=$result['cookie'];
		$this->setConfig(['cookie'=>$cookie]);

		$param=['from'=>'alimama'];
		$url='https://qrlogin.taobao.com/qrcodelogin/generateQRCode4Login.do?'.http_build_query($param);
		$result=self::curlRequest($url);

		$data=json_decode($result['data'],true);

		if(!empty($data)&&$data['success']){
			return ['status'=>true,'qrImg'=>'https:'.$data['url'],'lgToken'=>$data['lgToken']];
		}

		return ['status'=>false,'msg'=>'CURL Request Error','result'=>$result];
	}

	public function waitForLogin($lgToken){
		$dfUrl='https://login.taobao.com/member/taobaoke/login.htm';
		$param=['lgToken'=>$lgToken,'defaulturl'=>$dfUrl.'?is_login=1'];
		$url='https://qrlogin.taobao.com/qrcodelogin/qrcodeLoginCheck.do?'.http_build_query($param);
		$rqstCookie=$this->getConfig('cookie');
		$result=self::curlRequest($url,null,$rqstCookie);

		$data=json_decode($result['data'],true);
		if($data['code']==10006&&$data['success']){
			$redirectUrl=$data['url'];
			$this->setConfig(['redirectUrl'=>$redirectUrl]);
			return ['status'=>true];
		}

		return ['status'=>false];
	}

	public function loginRedirect($redirectUrl=''){
		$redirectUrl=empty($redirectUrl)?$this->getConfig('redirectUrl'):$redirectUrl;
		$rqstCookie=$this->getConfig('cookie');
		$result=self::curlRequest($redirectUrl,null,$rqstCookie);

		$cookie=$result['cookie'];
		$this->setConfig(['cookie'=>$cookie]);

		$httpCode=$result['info']['http_code'];
		if($httpCode==200){
			$loginCheck=$this->isLogin();
			return $loginCheck;
		}
		else if(in_array($httpCode,[301,302])){
			$url=$result['info']['redirect_url'];
			return $this->loginRedirect($url);
		}
		else{
			return ['status'=>false,'msg'=>'Login Redirect Error!','data'=>$result];
		}
	}

	public function isLogin(){
		$rqstCookie=$this->getConfig('cookie');
		$url='https://pub.alimama.com/common/getUnionPubContextInfo.json';
		$result=self::curlRequest($url,null,$rqstCookie);
		$data=json_decode($result['data'],true);

		if(!empty($data)&&isset($data['data'])&&isset($data['data']['memberid'])){
			$data=$data['data'];
			$tbkId=$data['memberid'];
			$tbkName=$data['mmNick'];

			$tbkInfo=$this->getConfig('tbkInfo');
			if(empty($tbkInfo)){
				$mdl=Loader::model('Account');
				$tbkInfo=$mdl->getInfo(['where'=>['originId'=>$this->originId,'tbkId'=>$this->tbkId],'field'=>['tbkId','tbkName','tbkPassword','siteId','adZoneId']]);
				$this->setConfig(['tbkInfo'=>$tbkInfo]);
			}

			if($tbkId!=$this->tbkId){
				//$this->setConfig(['cookie'=>null]);
				return ['status'=>false,'msg'=>'请使用淘宝账号：'.$tbkInfo['tbkName'].' 进行登录'];
			}
			$cookieAry=$this->getConfig('cookie');
			$cookieAry[$this->tbkId.'_yxjh-filter-1']=true;
			$this->setConfig(['cookie'=>$cookieAry]);

			return ['status'=>true,'data'=>['tbkName'=>$tbkName]];
		}

		return ['status'=>false,'msg'=>'登录验证失败'];
	}

	//淘宝联盟搜索商品
	public function searchItems($kw,$pgIdx=1,$param=null,$pgSize=100){
		// $sortType=['销量'=>9,'月推广量'=>5,'收入比例'=>1,'月支出佣金'=>7];
		// $shopTag=['营销和定向计划'=>'yxjh','店铺优惠券'=>'dpyhq'];
		$time=time();
		$rqstCookie=$this->getConfig('cookie');

		$rqstData=['q'=>$kw,'toPage'=>$pgIdx,'perPageSize'=>$pgSize,'_t'=>$time,'t'=>$time];
		isset($rqstCookie['_tb_token_'])?$rqstData['_tb_token_']=$rqstCookie['_tb_token_']:'';

		$pvId=$this->getConfig('pgId');
		empty($pvId)?'':$rqstData['pvid']=$pvId;

		$rqstData=empty($param)?$rqstData:array_merge($rqstData,$param);
		$url='https://pub.alimama.com/items/search.json?'.http_build_query($rqstData,'','&',PHP_QUERY_RFC3986);

		$rqstHeader=['referer'=>'https://pub.alimama.com/promo/search/index.htm','x-requested-with'=>'XMLHttpRequest'];
		$result=self::curlRequest($url,null,$rqstCookie,$rqstHeader);
		$result=json_decode($result['data'],true);

		if($result['ok']){
			$data=$result['data']['pageList'];
			if(!empty($data)){
				$pvId=$result['info']['pvid'];
				$count=$result['data']['head']['docsfound'];
				return ['data'=>$data,'count'=>$count,'pvId'=>$pvId];
			}
		}
		return null;
	}

	//获取优惠券数据
	public function getCouponItems($kw){
		$param=['shopTag'=>'dpyhq','userType'=>1];
		$result=$this->searchItems($kw,1,$param);
		return $result;
	}

	//通过分享信息获取商品详情
	public function getItemInfo($kw,$itemId){
		$pgIdx=0;
		$count=0;
		$itemInfo=null;
		while(empty($itemInfo)){
			$result=$this->searchItems($kw,++$pgIdx);
			if(empty($result)){
				break;
			}
			else{
				$pvId=$result['pvId'];
				$this->setConfig(['pvId'=>$pvId]);

				$count=$result['count'];
				$data=$result['data'];
				foreach($data as $info){
					if($info['auctionId']==$itemId){
						$itemInfo=$info;
						break;
					}
				}
			}
		}

		return $itemInfo;
	}

	//获取推广链接
	public function getLink($itemId,$siteId='',$adZoneId=''){
		$time=time();
		$cfg=$this->getConfig();
		$tbkInfo=$cfg['tbkInfo'];
		$rqstCookie=$cfg['cookie'];
		$pvId=$cfg['pvId'];
		$siteId=empty($siteId)?$tbkInfo['siteId']:$siteId;
		$adZoneId=empty($adZoneId)?$tbkInfo['adZoneId']:$adZoneId;
		$rqstData=['auctionid'=>$itemId,'adzoneid'=>$adZoneId,'siteid'=>$siteId,'_tb_token_'=>$rqstCookie['_tb_token_'],'pvid'=>$pvId,'t'=>$time*1000,'scenes'=>1];
		$url='https://pub.alimama.com/common/code/getAuctionCode.json?'.http_build_query($rqstData);
		$rqstHeader=['referer'=>'https://pub.alimama.com/promo/search/index.htm','x-requested-with'=>'XMLHttpRequest','pragma'=>'no-cache','cache-control'=>'no-cache'];
		$result=self::curlRequest($url,null,$rqstCookie,$rqstHeader);

		//$data=json_decode($result,true);

		return $result['data'];
	}

	private function getConfig($name=null){
		$cfg=Cache::get('TBK_'.$this->tbkId);
		return empty($name)?$cfg:(isset($cfg[$name])?$cfg[$name]:null);
	}

	private function setConfig($data){
		if(!empty($data)){
			$cfg=$this->getConfig();
			foreach($data as $key=>$val){
				$cfg[trim($key)]=$val;
			}
			Cache::set('TBK_'.$this->tbkId,$cfg);
		}
	}

	//获取指定商品的ID
	public static function getItemId($url){
		$id=null;
		$result=self::curlRequest($url);

		$rgx='/var\s+url\s*=\s*\'(.*)\';/';
		preg_match($rgx,$result['data'],$matchData);
		if(!empty($matchData)){
			$url=$matchData[1];
			$parseUrl=parse_url($url);
			if(isset($parseUrl['query'])){
				parse_str($parseUrl['query'],$data);
				$id=$data['id'];
			}
		}
		return $id;
	}

	public static function curlRequest($url,$rqstData=null,$rqstCookie=null,$rqstHeader=null){
		$rqstHeader['user-agent']='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';

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
			$param=http_build_query($rqstData,'','&',PHP_QUERY_RFC3986);
			//$param=json_encode($rqstData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
			$curlOpt[CURLOPT_POST]=TRUE;
			$curlOpt[CURLOPT_POSTFIELDS]=$param;
			$rqstHeader['Content-Length']=strlen($param);
			//$rqstHeader['X-Requested-With']='XMLHttpRequest';
			//$rqstHeader['Accept']='application/json, text/plain, */*';
			//$rqstHeader['Content-Type']='application/json;charset=UTF-8';
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

		$curlResult=curl_exec($curl);
		$curlInfo=curl_getinfo($curl);
		$errMsg=curl_errno($curl)?curl_error($curl):'';

		curl_close($curl);

		//获取请求的header和body
		$tmp=explode("\r\n\r\n",$curlResult,2);
		// $tmpCnt=count($tmp);
		// $rspnData=$tmp[$tmpCnt-1];
		// $rspnHeader=$tmp[$tmpCnt-2];

		$rspnData=$tmp[1];
		$rspnHeader=$tmp[0];

		//获取并设置cookie
		preg_match_all('/set-cookie:(.*);/iU',$rspnHeader,$setCookie);
		if(!empty($setCookie[1])){
			foreach($setCookie[1] as $info){
				list($key,$value)=explode('=',$info);
				$rqstCookie[trim($key)]=trim($value);
			}
		}

		return ['info'=>$curlInfo,'data'=>$rspnData,'cookie'=>$rqstCookie,'errorMsg'=>$errMsg];
	}

}

?>

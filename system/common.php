<?php

function dataVerify($data,$keyAry){
	$rst=true;

	foreach($keyAry as $key=>$val){
		if(isset($data[$key])){
			if($val&&empty($data[$key])){
				$rst=false;
				break;
			}
		}
		else{
			$rst=false;
			break;
		}
	}

	return $rst;
}

function getSinaShortLink($url){
	$sourceAry=[3271760578,2815391962,31641035];
	$source=mt_rand(0,count($sourceAry)-1);

	$param=['source'=>$source,'url_long'=>$url];
	$url='http://api.t.sina.com.cn/short_url/shorten.json?'.http_build_query($param);

	$result=curlRequestAPI($url);
	$data=json_decode($result['data'],true);

	return empty($data)||!isset($data['url_short'])?null:$data['url_short'];
}

function getRebate($money,$rebate){
	if(is_string($rebate)){
		$rebate=json_decode($rebate,true);
	}

	if(empty($rebate)||$money<=0){
		return 0;
	}

	$rtn=0;
	foreach($rebate as $key=>$val){
		if($money>=$val[0]&&$money<$val[1]){
			$rtn=number_format($money*$key,2);
		}
	}

	return $rtn==0?0.01:0;
}

function getInviteCodeFromUserId($userId){
	return 10000+$userId;
}

function getUserIdFromInviteCode($code){
	return $code-10000;
}

function curlRequestAPI($url,$rqstData=null){
	$curl=curl_init($url);
	$curlOpt=[
		CURLOPT_FOLLOWLOCATION=>FALSE,
		CURLOPT_RETURNTRANSFER=>TRUE,
		CURLOPT_CONNECTTIMEOUT=>5,
		CURLOPT_HEADER=>FALSE,
		CURLOPT_TIMEOUT=>10,
	];

	if(strlen($url)>5&&strtolower(substr($url,0,5))=='https'){
		$curlOpt[CURLOPT_SSL_VERIFYPEER]=FALSE;
		$curlOpt[CURLOPT_SSL_VERIFYHOST]=FALSE;
	}

	if(!empty($rqstData)){
		$postArray=false;
		foreach($rqstData as $info){
			if(is_array($info)){
				$postArray=true;
				break;
			}
		}
		$param=$postArray?json_encode($rqstData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):http_build_query($rqstData,'','&',PHP_QUERY_RFC3986);
		$curlOpt[CURLOPT_POST]=TRUE;
		$curlOpt[CURLOPT_POSTFIELDS]=$param;
	}

	curl_setopt_array($curl,$curlOpt);

	$curlResult=curl_exec($curl);
	$curlInfo=curl_getinfo($curl);
	$errMsg=curl_errno($curl)?curl_error($curl):'';

	curl_close($curl);

	return ['info'=>$curlInfo,'data'=>$curlResult,'errorMsg'=>$errMsg];
}

function curlRequestLogin($url,$rqstData=null,$rqstCookie=null,$rqstHeader=null,$curlOpt=null){
	$rqstHeader['user-agent']='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';

	$curl=curl_init($url);
	$curlOpt=[
		CURLOPT_FOLLOWLOCATION=>FALSE,
		CURLOPT_RETURNTRANSFER=>TRUE,
		CURLOPT_CONNECTTIMEOUT=>10,
		CURLOPT_HEADER=>TRUE,
		CURLOPT_TIMEOUT=>30,
	];

	if(!empty($curlOpt)){
		foreach($curlOpt as $key=>$val){
			$curlOpt[$key]=$val;
		}
	}

	if(strlen($url)>5&&strtolower(substr($url,0,5))=='https'){
		$curlOpt[CURLOPT_SSL_VERIFYPEER]=FALSE;
		$curlOpt[CURLOPT_SSL_VERIFYHOST]=FALSE;
	}

	if(!empty($rqstData)){
		$postArray=false;
		foreach($rqstData as $info){
			if(is_array($info)){
				$postArray=true;
				break;
			}
		}
		$param=$postArray?json_encode($rqstData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):http_build_query($rqstData,'','&',PHP_QUERY_RFC3986);
		$curlOpt[CURLOPT_POST]=TRUE;
		$curlOpt[CURLOPT_POSTFIELDS]=$param;
		$rqstHeader['Content-Length']=strlen($param);
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

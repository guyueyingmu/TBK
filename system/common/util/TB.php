<?php

	namespace app\common\util;

	class TB{
		public static function parseParam($params){
			ksort($params);
			$str='';
			foreach($params as $k=>$v){
				if($k!='sign'){
					$str.=$k.$v;
				}
			}
			unset($k,$v);
			return $str;
		}

		public static function getSign($params,$secret){
			//$str=;
			$str=$secret.self::parseParam($params).$secret;
			//$str.=;

			return strtoupper(md5($str));
		}

		public static function getId($url){
			$id=null;
			$content=self::curlRequest($url);
			$rgx='/var\s+url\s*=\s*\'(.*)\';/';
			preg_match($rgx,$content,$matchData);
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

		public static function curlRequest($url,$rqstData=null,$rqstHeader=null){
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

			curl_setopt_array($curl,$curlOpt);


			if(!empty($rqstData)){
				$param=http_build_query($rqstData,'','&',PHP_QUERY_RFC3986);
				curl_setopt($curl,CURLOPT_POST,TRUE);
				curl_setopt($curl,CURLOPT_POSTFIELDS,$param);
			}

			if(!empty($rqstHeader)){
				//设置请求的header
				$header=[];
				foreach($headerAry as $key=>$value){
					$header[]=($key.':'.$value);
				}
				curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
			}

			$result=curl_exec($curl);

			$status=curl_errno($curl);
			if($status){
				$error=curl_error($curl);
				$result=$error;
			}

			curl_close($curl);

			return $result;
		}

	}
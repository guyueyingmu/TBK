<?php

	namespace app\common\controller;

	use think\Exception;
	use think\Request;
	use think\Config;
    use think\Cache;
    use think\Log;

	class WeChatTrade{
		private $appId='';
		private $appSecrect='';
		private $signType='';
		private $mchId='';
		private $key='';
		private $tradeType='';
		private $tradeTypeAry=array('APP','NATIVE','JSAPI');

		function __construct($tradeType='JSAPI',$signType='MD5'){
			$tradeType=strtoupper($tradeType);
			if(!in_array($tradeType,$this->tradeTypeAry)){
				throw new Exception('未知的支付类型');
			}

			$config=Config::get('weChat.'.$tradeType);
			$this->signType=$signType;
			$this->appId=$config['appId'];
			$this->appSecrect=$config['appSecrect'];
			$this->key=$config['key'];
			$this->mchId=$config['mchId'];
			$this->tradeType=$tradeType;
		}

		//获取微信配置参数
		public function getWeChatConfig($url=null){
			$url=empty($url)?Request::instance()->url(true):$url;
			$timestamp=time();
			$nonceStr=$this->getRandStr();
			$param=array('timestamp'=>$timestamp,'noncestr'=>$nonceStr,'jsapi_ticket'=>$this->getJsTicket(),'url'=>$url);

			$signature=sha1($this->getLinkStr($this->getSignData($param)));
			//$signature=$this->getSign($param);

			$data['signature']=$signature;
			$data['appId']=$this->appId;
			$data['timestamp']=$timestamp;
			$data['nonceStr']=$nonceStr;
			return $data;
		}

		//获取支付配置参数
		public function getTradeConfig($param){
			if(empty($param['out_trade_no'])){
				throw new Exception('统一支付接口缺少必要参数:out_trade_no');
			}
			if(empty($param['body'])){
				throw new Exception('统一支付接口缺少必要参数:body');
			}
			if(empty($param['total_fee'])){
				throw new Exception('统一支付接口缺少必要参数:total_fee');
			}

			$param['trade_type']=$this->tradeType;

			$param['time_start']=date('YmdHis',time());
			$param['device_info']=empty($param['device_info'])?'WEB':$param['device_info'];
			$param['spbill_create_ip']=empty($param['spbill_create_ip'])?$_SERVER['REMOTE_ADDR']:$param['spbill_create_ip'];
			$param['appid']=$this->appId;
			$param['mch_id']=$this->mchId;
			$param['nonce_str']=$this->getRandStr();

			$sign=$this->getSign($param);
			$param['sign']=$sign;
			switch($param['trade_type']){
				case 'NATIVE':
					if(empty($param['product_id'])){
						throw new Exception('统一支付接口缺少必要参数:product_id');
					}
					$tradeConfig=$this->getUnifiedConfig($param);
					break;
				case 'JSAPI':
					if(empty($param['openid'])){
						throw new Exception('统一支付接口缺少必要参数:openid');
					}
					$data=$this->getUnifiedConfig($param);
					$tradeConfig=array('appId'=>$data['appid'],'timeStamp'=>time(),'nonceStr'=>$data['nonce_str'],'package'=>'prepay_id='.$data['prepay_id'],'signType'=>$this->signType);
					$tradeConfig['paySign']=$this->getSign($tradeConfig);
					break;
				case 'APP':
					$data=$this->getUnifiedConfig($param);
					$tradeConfig=array('appid'=>$data['appid'],'partnerid'=>$data['mch_id'],'prepayid'=>$data['prepay_id'],'package'=>'Sign=WXPay','noncestr'=>$data['nonce_str'],'timestamp'=>time());
					$tradeConfig['sign']=$this->getSign($tradeConfig);
					break;
			}
			return $tradeConfig;
		}

		//获取支付配置
		public function getUnifiedConfig($param){
			$url='https://api.mch.weixin.qq.com/pay/unifiedorder';
			$xmlData=$this->getXmlData($param);
			$resultData=$this->curlRequest($url,$xmlData);

			$obj=simplexml_load_string($resultData['data'],'SimpleXMLElement',LIBXML_NOCDATA);
			$result=json_decode(json_encode($obj),true);
			return isset($result['result_code'])&&$result['result_code']=='SUCCESS'?$result:null;
		}

		//订单查询
		public function orderQuery($outTradeNo){
			if(empty($outTradeNo)){
				throw new Exception('订单查询缺少必要参数');
			}
			$url='https://api.mch.weixin.qq.com/pay/orderquery';
			$data=array('appid'=>$this->appId,'mch_id'=>$this->mchId,'out_trade_no'=>$outTradeNo,'nonce_str'=>$this->getRandStr());
			$sign=$this->md5Sign($data,$this->key);
			$data['sign']=$sign;
			$xmlData=$this->getXmlData($data);
			$xmlReturn=$this->curlRequest($url,$xmlData);

			$obj=simplexml_load_string($xmlReturn['data'],'SimpleXMLElement',LIBXML_NOCDATA);
			$resultData=json_decode(json_encode($obj),true);
			return $resultData;
		}

		//获取签名
		private function getSign($param){
			$signType=isset($param['signType'])?$param['signType']:$this->signType;
			switch($signType){
				case 'MD5':
					$sign=$this->md5Sign($param,$this->key);
					break;
			}
			return $sign;
		}

		//验签
		public function verifySign($data=null){
			if(empty($data)){
				$xml=file_get_contents('php://input');
				$obj=simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA);
				$data=json_decode(json_encode($obj),true);
			}

			$signType=$this->signType;
			$verifyRst=false;
			switch($signType){
				case 'MD5':
					$verifyRst=$this->md5Verify($data,$this->key);
					break;
			}

			if($data['appid']==$this->appId&&$verifyRst){
				return array('tradeNo'=>$data['out_trade_no'],'tradeResult'=>$data['result_code']);
			}
			return false;
		}

		//MD5签名
		private function md5Sign($data,$key){
			$data=$this->getSignData($data);
			$str=$this->getLinkStr($data);
			$str=$str.'&key='.$key;
			return strtoupper(md5($str));
		}

		//MD5验签
		private function md5Verify($data,$key){
			$sign=$data['sign'];
			unset($data['sign']);

			if($data['return_code']=='SUCCESS'&&$data['result_code']=='SUCCESS'){
				$data=$this->getSignData($data);
				$str=$this->getLinkStr($data);
				$str=$str.'&key='.$key;
				$sginStr=strtoupper(md5($str));
				return $sginStr==$sign;
			}

			return false;
		}

		//把数组所有元素，按照‘键=值’的模式用“&”字符拼接成字符串
		private function getLinkStr($data,$encode=false){
			$str=null;

			while(list($key,$val)=each($data)){
				$val=$encode?urlencode($val):$val;
				$tmp=$key.'='.$val;
				$str.=empty($str)?$tmp:'&'.$tmp;
			}

			//去掉转义字符
			return stripslashes($str);
		}

		//除去签名数组中的空值和签名参数，并排序
		private function getSignData($data) {
			$filter=null;
			while(list($key,$val)=each($data)){
				if($key=='sign'||$val==''){
					unset($data[$key]);
				}
			}
			ksort($data);
			reset($data);
			return $data;
		}

		//转化为XML数据
		private function getXmlData($data){
			if(!is_array($data)||count($data)<=0){
	    		throw new Exception('微信支付数据异常');
	    	}

	    	$xml='<xml>';
	    	foreach($data as $key=>$val){
	    		if (is_numeric($val)){
	    			$xml.='<'.$key.'>'.$val.'</'.$key.'>';
	    		}else{
	    			$xml.='<'.$key.'><![CDATA['.$val.']]></'.$key.'>';
	    		}
	        }
	        $xml.='</xml>';
	        return $xml;
		}

		public function getJsTicket(){
			$jsTicket=Cache::get('jsTicket');
			if(empty($jsTicket)){
				$accessToken=Cache::get('accessToken');
				if(empty($accessToken)){
					$tokenUrl='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appId.'&secret='.$this->appSecrect;
					$result=$this->curlRequest($tokenUrl);
					$data=json_decode($result['data'],true);

					if(isset($data['access_token'])){
						$accessToken=$data['access_token'];
						$expiresIn=intval($data['expires_in']);
						if(!empty($accessToken)){
							Cache::set('accessToken',$accessToken,$expiresIn-600);
						}
					}
					else{
						dump($data);
						throw new Exception('获取access_token失败');
					}
				}

				$ticketUrl='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$accessToken.'&type=jsapi';
				$result=$this->curlRequest($ticketUrl);
				$data=json_decode($result['data'],true);

				if(isset($data['ticket'])){
					$jsTicket=$data['ticket'];
					$expiresIn=intval($data['expires_in']);
					if(!empty($jsTicket)){
						Cache::set('jsTicket',$jsTicket,$expiresIn-600);
					}
				}
				else{
					dump($data);
					throw new Exception('获取ticket失败');
				}
			}

			return $jsTicket;
		}

		private function curlRequest($url,$data=null,$header=null){
			$curl=curl_init($url);
			curl_setopt($curl,CURLOPT_HEADER,FALSE);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
			//curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_0);

			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);

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

		//获取随机字符吃
		private function getRandStr($length=32){
			$chars='abcdefghijklmnopqrstuvwxyz0123456789';
			$str='';
			for($i=0;$i<$length;$i++){
				$str.=substr($chars,mt_rand(0,strlen($chars)-1),1);
			}
			return $str;
		}

	}
<?php

	namespace app\common\util;

	class TBK{
		const APPKEY='24657841';
		const APPSECRET='ddf28718523207870d233af26bc8f422';
		const URL='http://gw.api.taobao.com/router/rest';

		//商品查询
		public static function getItemData($kw,$sort='total_sales_desc'){
			$method='taobao.tbk.item.get';
			$time=date('Y-m-d H:i:s');
			$fields='num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick';

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','fields'=>$fields,'q'=>$kw,'sort'=>$sort];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}

		//获取商品详情
		public static function getItemInfo($itemId){
			$method='taobao.tbk.item.info.get';
			$time=date('Y-m-d H:i:s');
			$fields='num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url';

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','fields'=>$fields,'num_iids'=>$itemId];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}

		//商品关联推荐
		public static function getRecommendData($itemId){
			$method='taobao.tbk.item.recommend.get';
			$time=date('Y-m-d H:i:s');
			$fields='num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url';

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','fields'=>$fields,'num_iid'=>$itemId];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}

		//获取正在进行中的定向招商的活动列表
		public static function getUatmEvent(){
			$method='taobao.tbk.uatm.event.get';
			$time=date('Y-m-d H:i:s');
			$fields='event_id,event_title,start_time,end_time';

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','fields'=>$fields];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}

		//获取淘宝联盟定向招商的商品信息
		public static function getUatmEventItem($eventId,$adzoneId){
			$method='taobao.tbk.uatm.event.item.get';
			$time=date('Y-m-d H:i:s');
			$fields='num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick,shop_title,zk_final_price_wap,event_start_time,event_end_time,tk_rate,type,status';

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','fields'=>$fields,'event_id'=>$eventId,'adzone_id'=>$adzoneId];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}

		//获取淘宝联盟选品库列表
		public static function getUatmFavorites(){
			$method='taobao.tbk.uatm.favorites.get';
			$time=date('Y-m-d H:i:s');
			$fields='favorites_title,favorites_id,type';

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','fields'=>$fields];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}

		//获取淘宝联盟选品库的宝贝信息
		public static function getUatmFavoritesItem($favoritesId,$adzoneId){
			$method='taobao.tbk.uatm.favorites.item.get';
			$time=date('Y-m-d H:i:s');
			$fields='num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick,shop_title,zk_final_price_wap,event_start_time,event_end_time,tk_rate,status,type';

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','fields'=>$fields,'favorites_id'=>$favoritesId,'adzone_id'=>$adzoneId];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}


		//获取优惠券信息
		public static function getCouponData($kw,$adzoneId){
			$method='taobao.tbk.dg.item.coupon.get';
			$time=date('Y-m-d H:i:s');

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','adzone_id'=>$adzoneId,'q'=>$kw];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}

		//淘口令
		public static function getTpwdInfo($txt,$url){
			$method='taobao.tbk.tpwd.create';
			$time=date('Y-m-d H:i:s');

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','text'=>$txt,'url'=>$url];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}

		//淘宝联盟搜索商品
		public static function search($kw,$token='',$pvId='',$pgIdx=1,$pgSize=100){
			// $sortType=['销量'=>9,'月推广量'=>5,'收入比例'=>1,'月支出佣金'=>7];
			// $shopTag=['营销和定向计划'=>'yxjh','店铺优惠券'=>'dpyhq'];
			$time=time();

			$rqstData=['q'=>$kw,'toPage'=>$pgIdx,'perPageSize'=>$pgSize,'_t'=>$time,'t'=>$time];
			empty($token)?'':$rqstData['_tb_token_']=$token;
			empty($pvId)?'':$rqstData['pvid']=$pvId;
			$url='https://pub.alimama.com/items/search.json?'.http_build_query($rqstData,'','&',PHP_QUERY_RFC3986);

			$result=TB::curlRequest($url);
			$result=json_decode($result,true);

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

		//通过分享信息获取商品详情
		public static function getDataForShare($kw,$itemId,$token='',$pvId=''){
			$pgIdx=0;
			$count=0;
			$itemInfo=null;
			while(true&&empty($itemInfo)){
				$result=self::search($kw,$token,$pvId,++$pgIdx);
				if(empty($result)){
					break;
				}
				else{
					$pvId=$result['pvId'];
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

			return ['pvId'=>$pvId,'itemInfo'=>$itemInfo];
		}

		//获取推广链接
		public static function getLink($pvId='10_117.101.178.77_29709_1508905898856',$itemId=553341205322,$adzoneId=141816670,$siteId=38418162){
			$time=time();
			$rqstData=['auctionid'=>$itemId,'adzoneid'=>$adzoneId,'siteid'=>$siteId,'pvid'=>$pvId,'t'=>$time];
			$url='http://pub.alimama.com/common/code/getAuctionCode.json?'.http_build_query($rqstData);
			dump($url);
			$result=TB::curlRequest($url);
			dump($result);
			$data=json_decode($result,true);
			dump($data);
		}

		//返利授权 没权限
		public static function getRrebateAuth($param,$type){
			$method='taobao.tbk.rebate.auth.get';
			$time=date('Y-m-d H:i:s');
			$fields='param,rebate';

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','fields'=>$fields,'params'=>$param,'type'=>$type];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}

		//商品链接转换 没权限
		public static function getConvertInfo($adzoneId,$itemId){
			$method='taobao.tbk.item.convert';
			$time=date('Y-m-d H:i:s');
			$fields='num_iid,click_url';

			$param=['method'=>$method,'app_key'=>self::APPKEY,'sign_method'=>'md5','timestamp'=>$time,'format'=>'json','v'=>'2.0','fields'=>$fields,'num_iids'=>$itemId,'adzone_id'=>$adzoneId];

			$sign=TB::getSign($param,self::APPSECRET);
			$param['sign']=$sign;
			$result=TB::curlRequest(self::URL,$param);
			$data=json_decode($result,true);

			return $data;
		}
	}
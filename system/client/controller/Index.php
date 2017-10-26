<?php
namespace app\client\controller;

use app\common\controller\Base;
use app\common\util\TBK;
use think\Loader;
use think\Config;
use think\Log;


class Index extends Base{

	public function index(){
		$kw=trim($this->request->param('kw'));
		$kw=empty($kw)?'Xiaomi/小米 圈铁耳机pro入耳式 女生通用跑步运动音乐降噪耳麦':$kw;

		$adzoneId=141692487;
		$itemId='553341205322,549050958613';

		// $url='http://a.fwg6.com/h.ubeRFA?sm=d407d7';
		// $kw='安卓苹果手机电脑通用耳机重低音炮运动挂耳入耳式有线控带麦耳塞';
		// $data=TBK::getDataForShare('金属耳机入耳式 重低音线控金属耳机入耳式通用',537809943805);

		TBK::search($kw,1,10);
		//TBK::getLink();

		// $result=TBK::getConvertInfo($adzoneId,$itemId);
		// dump($result);
	}



}

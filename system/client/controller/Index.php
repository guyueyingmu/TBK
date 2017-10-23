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

		$str='{"BaseResponse": {"Ret": 1,"ErrMsg": ""},"Count": 0,"ContactList": [],"SyncKey": {"Count": 0,"List": []},"User": {"Uin": 0,"UserName": "","NickName": "","HeadImgUrl": "","RemarkName": "","PYInitial": "","PYQuanPin": "","RemarkPYInitial": "","RemarkPYQuanPin": "","HideInputBarFlag": 0,"StarFriend": 0,"Sex": 0,"Signature": "","AppAccountFlag": 0,"VerifyFlag": 0,"ContactFlag": 0,"WebWxPluginSwitch": 0,"HeadImgFlag": 0,"SnsFlag": 0},"ChatSet": "","SKey": "","ClientVersion": 0,"SystemTime": 0,"GrayScale": 0,"InviteStartCount": 0,"MPSubscribeMsgCount": 0,"MPSubscribeMsgList": [],"ClickReportInterval": 0}';
		$data=json_decode($str,true);
		dump($data);;


		$str='<error><ret>0</ret><message></message><skey>@crypt_bfcb446c_cd50a41164f8343835b41177829c8914</skey><wxsid>owKfOKdB1Xt0xf+5</wxsid><wxuin>742270135</wxuin><pass_ticket>%2BJaK0Q6N354Z9M1o7tNn8Brq0Vc7klUtVQrafVb2oM7oR2SMKdi91Z0%2BfQSN0oTi</pass_ticket><isgrayscale>1</isgrayscale></error>';
		$str='<error><ret>0</ret><message></message><skey>@crypt_bfcb446c_56a1399b31e3fcba7c06abf96f602fea</skey><wxsid>zpXA0NCXqkkXMPmW</wxsid><wxuin>742270135</wxuin><pass_ticket>JLCvdtwtL4PMRcLME2c0c%2BTCIGFWiL6xicPHMYruk0j3n5ryANMQz5XsFXLW%2B9U0</pass_ticket><isgrayscale>1</isgrayscale></error>';

		$data=simplexml_load_string($str,'SimpleXMLElement',LIBXML_NOCDATA);
		dump((array)$data);
		$data=json_decode(json_encode($data),true);
		dump($data);
	}



}

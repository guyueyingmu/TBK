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

<?php

namespace app\common\model;

use think\Log;
use think\Model;
use think\Loader;

class Base extends Model{

	public function getAll($dataAry=[]) {
		$data=null;

		if(empty($dataAry)) {
			$data=$this->select();
		}
		else {
			$pageInfo=[];
			$fieldAry=isset($dataAry['field'])?$dataAry['field']:[];
			$whereAry=isset($dataAry['where'])?$dataAry['where']:[];
			$orderAry=isset($dataAry['order'])?$dataAry['order']:[];
			$joinAry=isset($dataAry['join'])?$dataAry['join']:[];
			if(isset($dataAry['limit'])&&isset($dataAry['page'])){
				$limit=intval($dataAry['limit']);
				$page=intval($dataAry['page']);
				$data=$this->alias('tb')->field($fieldAry)->join($joinAry)->where($whereAry)->order($orderAry)->limit($limit)->page($page)->select();
				$pageInfo['rcdCnt']=$this->alias('tb')->field(['tb.'.$this->getPk()])->join($joinAry)->where($whereAry)->count();
				$pageInfo['curPgIdx']=$page;
				$pageInfo['startIdx']=($page-1)*$limit;
				$pageInfo['pgCnt']=ceil($pageInfo['rcdCnt']/$limit);
			}
			else{
				$data=$this->alias('tb')->field($fieldAry)->join($joinAry)->where($whereAry)->order($orderAry)->select();
			}
		}

		$ary=[];
		foreach($data as $key=>$obj){
			if(!empty($obj)){
				$ary[$key]=$obj->getData();
			}
		}

		return empty($pageInfo)?$ary:['data'=>$ary,'page'=>$pageInfo];
	}

	public function add($dataAry){
		if (empty($dataAry)||!is_array($dataAry)) {
			return false;
		}

		$rst=$this->isUpdate(false)->save($dataAry);
		$rst=$rst===false?false:$this[$this->getPk()];

		return $rst;
	}

	public function edit($dataAry){
		if (empty($dataAry)||!is_array($dataAry)||empty($dataAry['where'])||empty($dataAry['data'])) {
			return false;
		}

		$whereAry=$dataAry['where'];
		$saveData=$dataAry['data'];
		$rst=$this->where($whereAry)->update($saveData);

		return $rst!==false;
	}

	public function getInfo($dataAry){
		if(empty($dataAry)||!is_array($dataAry)){
			return null;
		}

		$fieldAry=isset($dataAry['field'])?$dataAry['field']:[];
		$whereAry=isset($dataAry['where'])?$dataAry['where']:[];
		$joinAry=isset($dataAry['join'])?$dataAry['join']:[];
		$orderAry=isset($dataAry['order'])?$dataAry['order']:[];
		$info=$this->alias('tb')->field($fieldAry)->where($whereAry)->join($joinAry)->order($orderAry)->find();

		return $info===false?false:(empty($info)?null:$info->getData());
	}

	public function getCount($dataAry=[]){
		$cnt=0;

		$whereAry=isset($dataAry['where'])?$dataAry['where']:[];
		$joinAry=isset($dataAry['join'])?$dataAry['join']:[];
		$cnt=$this->join($joinAry)->where($whereAry)->count();

		return $cnt;
	}

	public function getField($dataAry){
		if(!is_array($dataAry)||empty($dataAry)){
			return null;
		}

		$whereAry=isset($dataAry['where'])?$dataAry['where']:[];
		$joinAry=isset($dataAry['join'])?$dataAry['join']:[];
		$fieldAry=isset($dataAry['field'])?$dataAry['field']:[];
		$groupAry=isset($dataAry['group'])?$dataAry['group']:'';

		$fieldStr=implode(',',$fieldAry);
		$data=$this->alias('tb')->join($joinAry)->where($whereAry)->group($groupAry)->column($fieldStr,empty($dataAry['key'])?'tb.'.$this->getPk():$dataAry['key']);

		return $data;
	}

	public function callSelf($method,$dataAry,$modelName){
		$mdl=Loader::model($modelName);
		return $mdl->$method($dataAry);
	}

}
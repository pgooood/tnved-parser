<?php
namespace pgood\tnved;

class RowParser{
	protected $arRow;
	
	protected function __construct($arRow){
		$this->arRow = &$arRow;
	}

	function code(){
		return $this->arRow['code'];
	}

	function name(){
		if(mb_ereg('^-+\s*([^:]+)\:?$',$this->arRow['name'],$m))
			return $m[1];
		return $this->arRow['name'];
	}

	function unit(){
		return $this->arRow['unit'];
	}

	function level(){
		$level = 3;
		$m = null;
		if(mb_ereg('^(-+)',$this->arRow['name'],$m))
			$level+= mb_strlen($m[1]);
		return $level;
	}

	function append($v){
		if(($row = static::row($v))
			&& !$row->isGroup()
			&& !$row->code()
		){
			if($row->name())
				$this->arRow['name'].= ' '.$row->name();
			if($row->unit())
				$this->arRow['unit'].= ' '.$row->unit();
			return $this;
		}
	}

	function isRow(){
		return !empty($this->arRow['code']);
	}

	function isGroup(){
		return empty($this->arRow['code']) && '-' == substr($this->arRow['name'],0,1);
	}

	static function row($v){
		if($arRow = static::parse($v))
			return new static($arRow);
	}

	protected static function parse($v){
		$m = null;
		mb_ereg('\|([^\|]+)\|([^\|]+)\|([^\|]+)\|',$v,$m);
		return $m 
			? ['code' => trim($m[1]),'name' => trim($m[2]),'unit' => trim($m[3])]
			: null;
	}
}
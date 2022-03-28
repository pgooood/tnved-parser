<?php
namespace pgood\tnved;

class Element extends \DOMElement{
	protected \DOMXPath $xp;

	function code(){
		return $this->getAttribute('code');
	}

	function name($v = null){
		if($v !== null)
			$this->setAttribute('name',$v);
		return $this->getAttribute('name');
	}

	function unit(){
		return $this->getAttribute('unit');
	}

	function level(){
		return $this->xp()->evaluate('count(ancestor::*)',$this);
	}

	function xp(\DOMXPath $v = null){
		if($v === null){
			if(!isset($this->xp))
				$this->xp = new \DOMXPath($this->ownerDocument);
		}else
			$this->xp = $v;
		return $this->xp;
	}
}
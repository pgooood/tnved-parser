<?php
namespace pgood\tnved;


use \pgood\tnved\Element;

class Parser{
	protected \DOMDocument $dd;
	protected \DOMXPath $xp;
	protected Element $el;

	function __construct(){
		$this->dd = new \DOMDocument('1.0','utf-8');
		$this->dd->registerNodeClass('DOMElement',Element::class);
		$this->xp = new \DOMXPath($this->dd);
		$this->el = $this->dd->appendChild($this->create('tnved'));
	}

	function create($name,$text = null){
		return $text === null
			? $this->dd->createElement($name)
			: $this->dd->createElement($name,$text);
	}

	function current(): Element{
		return $this->el;
	}

	function level(){
		return $this->current()->level();
	}

	function startSection($code = null,$name = null){
		$this->el = $this->current()->appendChild($this->create('section'));
		if(isset($code) && mb_strlen($code))
			$this->el->setAttribute('code',$code);
		if(isset($name) && mb_strlen($name))
			$this->el->setAttribute('name',$name);
		return $this->el;
	}

	function startGroup($code = null,$name = null){
		$this->el = $this->current()->appendChild($this->create('group'));
		if(isset($code) && mb_strlen($code))
			$this->el->setAttribute('code',$code);
		if(isset($name) && mb_strlen($name))
			$this->el->setAttribute('name',$name);
		return $this->el;
	}

	function startItem($code = null,$name = null,$unit = null){
		$this->el = $this->current()->appendChild($this->create('item'));
		if(isset($code) && mb_strlen($code))
			$this->el->setAttribute('code',$code);
		if(isset($name) && mb_strlen($name))
			$this->el->setAttribute('name',$name);
		if(isset($unit) && mb_strlen($unit))
			$this->el->setAttribute('unit',$unit);
		return $this->el;
	}

	function finishGroup(){
		if($this->current()->level() && $this->el->parentNode)
			$this->el = $this->el->parentNode;
	}

	function dump(){
		$this->dd->formatOutput = true;
		\vdump($this->dd->saveXML());
	}

	function download(){
		header('Content-Type: text/xml');
		echo $this->dd->saveXML();
		die;
	}

}

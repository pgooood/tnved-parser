<?php
require_once 'include.php';

spl_autoload_register(function($className){
	if(DIRECTORY_SEPARATOR !== '\\')
		$className = str_replace('\\',DIRECTORY_SEPARATOR,$className);
	if(is_file($path = __DIR__."/classes/{$className}.php"))
		include $path;
});

use pgood\tnved\Parser
	,pgood\tnved\RowParser;

$tmpDir = __DIR__;
$dataFile = "$tmpDir/data.txt";
if(!is_file($dataFile))
	file_put_contents($dataFile,str_replace("\n\n\n","\n",rtf2txt('tnved.rtf')));

$tp = new Parser;
$lines = file($dataFile);
$isTable = false;
$row = null;
foreach($lines as $i => $line){
	switch(true){
		case $isTable && strstr($line,'#TABLE_END#'):
			$isTable = false;
			$row = null;
			break;
		case $isTable:
			if($row){
				if(!$row->append($line)){
					while($tp->level() && $row->level() && $tp->level() >= $row->level())
						$tp->finishGroup();
					while($row->level() - $tp->level() > 0)
						$tp->startItem($row->code(),$row->name(),$row->unit());
					$row = RowParser::row($line);
				}
			}else
				$row = RowParser::row($line);
			break;
		case !$isTable && strstr($line,'#TABLE_START#'):
			$isTable = true;
			$row = null;
			break;
		
		case !$isTable && strpos($line,'ГРУППА ') === 0:
			$m = null;
			if(mb_ereg('^ГРУППА\s+(\d+)$',$line,$m)){
				while($tp->level() > 1)
					$tp->finishGroup();
				$tp->startGroup($m[1]);
				while(isset($lines[++$i]) && !mb_strlen($name = trim($lines[$i])));
				$tp->current()->name($name);
			}
			break;
		case !$isTable && strpos($line,'РАЗДЕЛ ') === 0:
			$m = null;
			if(mb_ereg('^РАЗДЕЛ\s+([IVXM]+)$',$line,$m)){
				while($tp->level())
					$tp->finishGroup();
				$tp->startSection($m[1]);
				while(isset($lines[++$i]) && !mb_strlen($name = trim($lines[$i])));
				$tp->current()->name($name);
			}
			break;
	}
}
$tp->download();
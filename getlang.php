<?php
// delete mymodule.lang
unlink('/mymodule.lang');
//create mymodule.lang
$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
//look for the text to trad
$defs=parseDir('.');
sort($defs);
//write the file
foreach($defs as $def){
	fwrite($myfile, $def."= \n");
}
fclose($myfile);

//////////////////////////////////


function parseString($str,$list){
	$output=array();
	preg_match("/('(.*?)'|\"(.*?)\")+/", $str, $matches);
	foreach($matches as $param){
		$words=explode(' ',$param);
		foreach($words as $word){
			$word=preg_replace('/[^A-Za-z0-9\- ]/', '', $word);
			if(!in_array($word,$list))$list[]= $word;
		}
	}
        return $list;
}

function parsefile($file,$list){
	$rows=file($file);
	$size=count($list);
	foreach( $rows as $row){
		$Arrow=explode('->trans(',$row);
		$nbr=count($Arrow)-1;
		for($i=0;$i<$nbr;$i++){
			if(preg_match("/((('.*')|(\".*\"))+)(.*)\\)/", $Arrow[$i+1], $match)){
				$list=parseString($match[0],$list);
			}
		}
	}
//	if(count($list)>$size)$list[]="#end of ".$file;
        return $list;
}



//function that will look into every dir to check 



function parseDir($dir,$list=array()){
	$a = scandir($dir);
	foreach($a as $dirfile){
		switch($dirfile){
			case '.':
			case '..':
				break;
			default:
				$list=is_dir($dir.'/'.$dirfile)?parseDir($dir.'/'.$dirfile,$list):parsefile($dir.'/'.$dirfile,$list);
		}
	}
	return $list;
}
?>

<?php
/**
 * テンプレートを返す
 * @return 
 * @param object $filename
 */
function define_template($filename){
	$tmplate = array(
		"menu" => "./common/tmpl/menu.tmpl",
		"demo" => "./common/tmpl/demo.tmpl"
	);
	return $tmplate[$filename];
}
/**
 * テンプレートを表示する
 * @return 
 * @param object $filename
 * @param object $items[optional]
 */
function show_template($filename, $items = array()){
	$tmplname = define_template($filename);
	//取得データを反映
	//if(isset($items)) @extract($items); //取得データ
	if(!$tmplname) die("$filenameは存在しません");
    include $tmplname;
}

?>
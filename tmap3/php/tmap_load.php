<?php
require_once('./class/TMAP_Coordinator.php');
require_once('./class/TMAP_Load_Validate.php');
require_once('./class/TMAP_Tile_Generator.php');
require_once('./class/TMAP_Draw.php');
require_once('./class/TMAP_Draw_Validate.php');

//パラメータ取得インスタンス生成
$load = new TMAP_Load_Validate();
//画像JSON取得インスタンス生成
$tmap = new TMAP_Coordinator();
//pix時は経緯度に変換する処理をはさむ
if($load->getMode() == "pix"){
	$tmap->setMapValue(
		$load->getScale(),
		$load->getWidth(),
		$load->getHeight()
	);
	$tmap->setPixValue(
		$load->getX(),
		$load->getY()
	);
	//基本データ取得インスタンス
	$data = $tmap->getCoordinate($load->getMode());
	//経緯度とconvをセットして再度Load_Validateへ
	$load->__second_construct(
		array(
			"lng" => $data["lng"],
			"lat" => $data["lat"],
			"conv" => 1
		)
	);
}
//全ての処理共通
if($load->getMode()){
	$tmap->setMapValue(
		$load->getScale(),
		$load->getWidth(),
		$load->getHeight()
	);
	$tmap->setGeoValue(
		$load->getLng(),
		$load->getLat()
	);
}else{
	die("Conv parameter invalid");
}

//基本データ取得インスタンス
$data = $tmap->getCoordinate($load->getMode());

//表示領域に最適化したデータ群取得
if($load->getOutFrameWidth() == null || $load->getOutFrameHeight() == null){
	die("Notfound outframe parameter");
}else if($load->getWidth() == null || $load->getHeight() == null){
	die("Notfound image parameter");
}else{
	//表示領域に最適化するインスタンス生成
	$tile = new TMAP_Tile_Generator(
		$data,
		$load->getOutFrameWidth(),
		$load->getOutFrameHeight(),
		$load->getWidth(),
		$load->getHeight(),
		$load->getLng(),
		$load->getLat()
	);
	//データを配列で受け取る
	$tile_data = array(
		"common" => $tile->getTileCommonData(),
		"each"   => $tile->getTileEachData(),
		"blog"   => $load->getBlogList()
	);
	//生成するタイル情報抽出、バリデータのインスタンス生成
	$draw_valid = new TMAP_Draw_Validate(
		$tile_data,
		$load->getWidth(),
		$load->getHeight(),
		$load->getScale()
	);
	//存在しないタイルがあるか
	$nftile = $draw_valid->getTileNotFoundList();

	if(count($nftile) > 0){
		for($i = 0; $i < count($nftile); $i++){
			//あれば、タイル情報を分解してバリデート
			$draw_valid->prepareValidate($nftile[$i]);
			
			//画像生成インスタンス
			$draw = new TMAP_Draw();
			//画像生成パラメータセット
			$draw->setMapParam(
				$draw_valid->getId(),
				$draw_valid->getScale(),
				$draw_valid->getTopId(),
				$draw_valid->getLeftId(),
				$draw_valid->getWidth(),
				$draw_valid->getHeight()
			);
			//画像生成
			$draw->createMap();
		}
		clearstatcache();
	}
	//JSONに変換
	$json = json_encode($tile_data);
	
	//callback関数を付与して返す
	echo $load->getCallBack() ."(" . $json . ")";
}

?>
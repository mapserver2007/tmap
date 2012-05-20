<?php
class TMAP_Draw_Validate extends TMAP_Global {
	private $id, $t, $l, $w, $h, $sidx;
	private $nftile;
	const MAX_WIDTH = 400;
	const MAX_HEIGHT = 300;
	
	public function __construct($tile, $w, $h, $s){
		$this->setTileNotFoundList($tile, $w, $h, $s);
	}
	/**
	* 概要 
	* 受け取ったGETデータをバリデートする。
	* validな値と判断したらメンバ変数へ代入
	*/
	private function doValidate($id = null, $t = null, 
		$l = null, $w = null, $h = null, $s = null){
		try{
			//画像id
			if($id == null){
				throw new Exception("Image id not found");
			}else{
				$this->id = $id;
			}
			//Top id
			if($t == null){
				throw new Exception("Image top id not found");
			}else if(!is_int((int)$t)){
				throw new Exception("Invalid top id");
			}else{
				$this->t = $t;
			}
			//Left id
			if($l == null){
				throw new Exception("Image left id not found");
			}else if(!is_int((int)$l)){
				throw new Exception("Invalid left id");
			}else{
				$this->l = $l;
			}
			//画像幅
			if($w == null){
				throw new Exception("Image width not found");
			}else if($w <= 0 || $w > TMAP_Draw_Validate::MAX_WIDTH){
				throw new Exception("Width over the range");
			}else if(!is_int((int)$w)){
				throw new Exception("Width parameter isnot numeric");
			}else{
				$this->w = $w;
			}
			//画像高さ
			if($h == null){
				throw new Exception("Image height not found");
			}else if($h <= 0 || $h > TMAP_Draw_Validate::MAX_WIDTH){
				throw new Exception("Height over the range");
			}else if(!is_int((int)$h)){
				throw new Exception("Height parameter isnot numeric");
			}else{
				$this->h = $h;
			}
			//sidx
			if($s == null && $sidx != 0){
				throw new Exception("Image scale not found");
			}else if(!is_int($s)){
				throw new Exception("Sidx parameter isnot numeric");
			}else{
				$this->s = $s;
			}
		}catch(Exception $e){
			die("Exception：".$e->getMessage()."\n");
		}
	}
	/**
	* 概要 
	* 画像が無い場合は画像生成のためのパラメータ
	* をセットするSetterメソッド
	* @param Array $tile
	*/
	private function setTileNotFoundList($tile, $w, $h, $s){
		for($i = 0; $i < count($tile["each"]); $i++){
			$tile_path = TMAP_Global::TMP_DIR . $tile["each"][$i]["mid"] . $tile["common"]["format"];
			if(!file_exists($tile_path)){
				$nftile[] = array(
					"src"    => $tile["each"][$i]["mid"],
					"width"  => $w,
					"height" => $h,
					"scale"  => $s
				);
			}
		}
		$this->nftile = $nftile;
	}
	/**
	* 概要 
	* 存在しない画像リストの要素数を返すGetterメソッド
	* @return Array $this->nftile
	*/
	public function getTileNotFoundList(){
		return $this->nftile;
	}
	
	/**
	* 概要 
	* 存在しない画像リストを分割してバリデータメソッドに流すメソッド
	*/
	public function prepareValidate($nftile){
		$id    = $nftile["src"];
		$param = preg_split("/s|t|l/", $nftile["src"]);
		$t     = $param[2];
		$l     = $param[3];
		$w     = $nftile["width"];
		$h     = $nftile["height"];
		$s     = $nftile["scale"];
		$this->doValidate($id, $t, $l, $w, $h, $s);
	}

	/**
	* 概要 
	* 各種Getterメソッド
	* @return int, String 各種パラメータ
	*/
	public function getId(){
		return $this->id;
	}

	public function getTopId(){
		return $this->t;
	}

	public function getLeftId(){
		return $this->l;
	}

	public function getScale(){
		return $this->s;
	}

	public function getWidth(){
		return $this->w;
	}

	public function getHeight(){
		return $this->h;
	}
}



?>
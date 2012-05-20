<?php
/**
 * 表示領域に最適化した地図データ群を生成するクラス
 * @author   Ryuichi TANAKA
 * @version  2008/07/??
 */
class TMAP_Tile_Generator {
	private $w, $h, $ow, $oh, $lng, $lat;
	private $base_data; //Coordinatorより得た基本データ
	private $tile_data; //基本データを元に取得した表示する全てのタイル画像情報
	
	public function __construct($base_data, $ow, $oh, $w, $h, $lng, $lat){
		$this->paramValidater($base_data, $ow, $oh, $w, $h, $lng, $lat);
		$this->setMapTileImageData();
	}
	/**
	* 概要 
	* 受け取った各種データをバリデートする。
	* validな値と判断したらメンバ変数へ代入
	* この前の処理でバリデーションしているので緩めの分岐
	*/
	private function paramValidater($base_data = null, $ow = null, $oh = null, 
		$w = null, $h = null, $lng = null, $lat = null, $blog_data = null){
		try{
			//基本データ
			if($base_data == null || !is_array($base_data)){
				throw new Exception("Base data isnot valid");
			}else{
				$this->base_data = $base_data;
			}
			//画像幅
			if(!is_int($w)){
				throw new Exception("Width parameter isnot numeric");
			}else{
				$this->w = $w;
			}
			//画像高さ
			if(!is_int($h)){
				throw new Exception("Height parameter isnot numeric");
			}else{
				$this->h = $h;
			}
			//outframe幅
			if(!is_int($ow)){
				throw new Exception("Outframe width parameter isnot numeric");
			}else{
				$this->ow = $ow;
			}
			//画像高さ
			if(!is_int($oh)){
				throw new Exception("Outframe height parameter isnot numeric");
			}else{
				$this->oh = $oh;
			}
			//経度
			if($lng){
				$this->lng = $lng;
			}
			//緯度
			if($lat){
				$this->lat = $lat;
			}
		}catch(Exception $e){
			die("Exception：".$e->getMessage()."\n");
		}
	}
	/**
	* 概要 
	* 表示領域に最適な画像枚数とIDを算出、
	* そのIDを基準とした枚数分の画像情報をセットするSetterメソッド
	* Javascript版getmapVariableImagesのPHP移植
	*/
	private function setMapTileImageData(){
		//移動差分計算
		$dx = ($this->ow / 2) - ($this->base_data["x"] >= 0 ? $this->base_data["x"] : 
			$this->base_data["x"] + $this->w);
		$dy = ($this->oh / 2) - ($this->base_data["y"] >= 0 ? $this->base_data["y"] : 
			$this->base_data["y"] + $this->h);
		//タイル情報格納
		$tile = array();
		/**
		* $tile["l"] : 基準画像に対して、左側に表示する枚数
		* $tile["r"] : 基準画像に対して、右側に表示する枚数
		* $tile["t"] : 基準画像に対して、上側に表示する枚数
		* $tile["b"] : 基準画像に対して、下側に表示する枚数
		*/
		
		/**
		* 表示領域幅(または表示領域高)が画像幅(または画像高)以上のときは、
		* 基準画像の左隣(上側)に配置する場合、
		* 右隣(下側)にも空白ができるため、処理を加えている。
		* 逆に、右隣(下側)に配置する場合は、表示領域幅(または表示領域高)の大きさに関係なく
		* 左隣(上側)に空白ができることはない。
		*/
		
		//x方向の判定
		if($dx > 0){
			//画像を左隣に追加
			//表示する画像は最低2枚以上
			$tile["l"] = (int)($dx / $this->w) + 1; //左隣に追加する画像数
			$tile["r"] = $this->ow > ($dx + $this->w) ? 
				(int)(($this->ow - ($dx + $this->w)) / $this->w) + 1 : 0; //右隣に追加する画像数
		}else{
			//画像を右隣に追加
			//表示する画像は最低1枚以上
			$tile["l"] = 0; //左隣に追加する必要はない
			$tile["r"] = $this->ow > ($dx + $this->w) ? 
				(int)(($this->ow - ($dx + $this->w)) / $this->w) + 1 : 0; //右隣に追加する画像数
		}
		//y方向の判定
		if($dy > 0){
			//画像を上側に追加
			//表示する画像は最低2枚以上
			$tile["t"] = (int)($dy / $this->h) + 1; //上側に追加する画像数
			$tile["b"] = $this->oh > ($dy + $this->h) ? 
				(int)(($this->oh - ($dy + $this->h)) / $this->h) + 1 : 0; //下側に追加する画像数
		}else{
			//画像を下側に追加
			$tile["t"] = 0; //上側に追加する必要はない
			$tile["b"] = $this->oh > ($dy + $this->h) ? 
				(int)(($this->oh - ($dy + $this->h)) / $this->h) + 1 : 0; //下側に追加する画像数
		}
		
		//タイルの縦横枚数
		//+1しているのは、基準画像も枚数に含むため
		$tx = $tile["l"] + $tile["r"] + 1;
		$ty = $tile["t"] + $tile["b"] + 1;
		
		//タイル走査開始位置の設定
		$dx -= $tile["l"] * $this->w;
		$dy -= $tile["t"] * $this->h;
		
		//タイル画像情報の配列を求め、メンバ変数へセット
		//画像の有無を確認し、ディレクトリにない場合は生成をする
		$common = array(
			"dx"     => floor(abs($dx)),
			"dy"     => floor(abs($dy)),
			"tx"     => 0, //横方向のタイル枚数
			"ty"     => 0, //縦方向のタイル枚数
			"lng"    => $this->lng, //住所抽出時は設定される
			"lat"    => $this->lat, //住所抽出時は設定される
			"format" => ".png"
		);
		$each = array();
		for($i = 0; $i < $ty; $i++){
			for($j = 0; $j < $tx; $j++){
				//位置補正
				$_t = $this->base_data["t"] - $tile["t"] * $this->h + $this->h * $i;
				$_l = $this->base_data["l"] - $tile["l"] * $this->w + $this->w * $j;
				$data = array(
					"left"   => $_l,
					"top"    => $_t,
					"mid"    => "s" . $this->base_data["s"] . "t" . $_t . "l" . $_l
				);
				//$img["each"][] = $data;
				$each[] = $data;
			}
		}
		$common["tx"] = $j;
		$common["ty"] = $i;
		//セット
		$this->tile_common_data = $common;
		$this->tile_each_data = $each;
	}
	/**
	* 概要 
	* 地図画像タイル情報Getterメソッド
	*/
	public function getTileCommonData(){
		return $this->tile_common_data;
	}
	public function getTileEachData(){
		return $this->tile_each_data;
	}
}

?>
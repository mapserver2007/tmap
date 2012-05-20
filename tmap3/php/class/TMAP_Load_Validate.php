<?php
/**
 * GETデータをValidな値にして返すクラス
 * @author   Ryuichi TANAKA
 * @version  2008/07/??
 */
class TMAP_Load_Validate extends TMAP_Global {
	private $x, $y, $lng, $lat, $w, $h, $s, $ns, $conv, $ow, $oh;
	private $blog_list;
	private $call;
	const MAX_IMG_WIDTH = 400;    //最大画像幅
	const MAX_IMG_HEIGHT = 300;   //最大画像高
	const MAX_FRAME_WIDTH = 2000; //最大outframe幅
	const MAX_FRAME_HEIGHT = 800; //最大outframe高
	const DEF_FRAME_WIDTH = 500;  //デフォルトoutframe幅
	const DEF_FRAME_HEIGHT = 500; //デフォルトoutframe高
	const TMAP_API_URL = "http://localhost/tmap/tmapi2/pl/tmap_extract.pl"; //TMAP APIのURL
	const TMAP_BLOGEO_URL = "http://localhost/tmap/tmapi2/pl/tmap_blog_mapper.pl"; //TMAP BLOGEOのURL
	/**
	* 概要 
	* スーパークラスのコンストラクタを呼び出すことで
	* getSidx2Scaleメソッドが先に有効になるので、setGetDataメソッドで呼び出せる
	*/
	public function __construct(){
		parent::__construct(); //スーパークラスのコンストラクタ呼び出し
		$gettterAry = $this->getGetData();
		$this->paramValidater($gettterAry);
	}
	/**
	* 概要 
	* 経緯度またはPix座標のArrayを受け取り、地図基本データを作り直す
	* 擬似的コンストラクタ
	*/
	public function __second_construct($data){
		$gettterAry = $this->getGetData($data);
		$this->paramValidater($gettterAry);
	}
	/**
	* 概要 
	* 受け取ったGETデータをバリデートする。
	* validな値と判断したらメンバ変数へ代入
	* @param Array $getter $_GETのハッシュ
	*/
	private function paramValidater($get = null){
		try{
			if($get["conv"] == null || !is_int($get["conv"])){
				throw new Exception("Invalid conv parameter");
			}else{
				$this->conv = $get["conv"];
			}
			if($get["s"] && $get["w"] && $get["h"]){
				//スケール
				if(!is_int($get["s"])){
					throw new Exception("Sidx parameter isnot numeric");
				}else{
					$this->s = $get["s"]; //配列のキーにするため-1する
				}
				//画像幅
				if($get["w"] <= 0 || $get["w"] > TMAP_Load_Validate::MAX_IMG_WIDTH){
					throw new Exception("Width over the range");
				}else if(!is_int($get["w"])){
					throw new Exception("Width parameter isnot numeric");
				}else{
					$this->w = $get["w"];
				}
				//画像高さ
				if($get["h"] <= 0 || $get["h"] > TMAP_Load_Validate::MAX_IMG_HEIGHT){
					throw new Exception("Height over the range");
				}else if(!is_int($get["h"])){
					throw new Exception("Height parameter isnot numeric");
				}else{
					$this->h = $get["h"];
				}
			}
			//ズーム処理後のスケール
			if($get["ns"]){
				if(!is_int($get["ns"])){
					throw new Exception("nSidx parameter isnot numeric");
				}else{
					$this->ns = $get["ns"];
				}
			}
			//経緯度とPix座標が同時指定されているときは経緯度優先
			if($get["lng"] && $get["lng"]){
				$this->lng = $get["lng"];
				$this->lat = $get["lat"];
			}
			else if(($get["x"] && is_int($get["x"])) && ($get["y"] && is_int($get["y"]))){
				$this->x = $get["x"];
				$this->y = $get["y"];
			}
			if(($get["ow"] && is_int($get["ow"])) && ($get["oh"] && is_int($get["oh"]))){
				$this->ow = $get["ow"] > TMAP_Load_Validate::MAX_FRAME_WIDTH ? TMAP_Load_Validate::DEF_FRAME_WIDTH : $get["ow"];
				$this->oh = $get["oh"] > TMAP_Load_Validate::MAX_FRAME_HEIGHT ? TMAP_Load_Validate::DEF_FRAME_HEIGHT : $get["oh"];
			}
			//conv=1のとき以外は通さない(conv=2のときは変換処理なので、外部API問い合わせは行わない)
			if($get["conv"] == 1){
				//住所を含む文字列が指定されたときは最優先
				if($get["addr"]){
					$this->setLngLatFromAddress($get["addr"], $get["apikey"]);
				}
				//ブログデータを取得する
				if($get["blogeo"]){
					$this->setBlogList($get);
				}
			}
		}catch(Exception $e){
			die("Exception：".$e->getMessage()."\n");
		}
	}
	/**
	* 概要 
	* GETデータのGetterメソッド
	* @param  Array $data 経緯度やPix座標の配列
	* @return Array $getter $_GETのハッシュ
	*/
	private function getGetData($data = null){
		$getter = array(
			"w"      => (int)$_GET["w"],
			"h"      => (int)$_GET["h"],
			"x"      => (int)$_GET["x"],
			"y"      => (int)$_GET["y"],
			"lng"    => $data["lng"] != null ? $data["lng"] : $_GET["lng"],
			"lat"    => $data["lat"] != null ? $data["lat"] : $_GET["lat"],
			"addr"   => $_GET["addr"],
			"blogeo" => $_GET["blogeo"],
			"dist"   => $_GET["dist"],
			"num"    => $_GET["num"],
			"s"      => $this->ns != null ? (int)$this->getSidx2Scale($this->ns-1) : 
			(int)$this->getSidx2Scale((int)$_GET["s"]-1),
			"ns"     => (int)$_GET["ns"],
			"ow"     => (int)$_GET["ow"],
			"oh"     => (int)$_GET["oh"],
			"conv"   => $data["conv"] != null ? $data["conv"] : (int)$_GET["conv"],
			"apikey" => $_GET["apikey"]
		);
		$this->call = $this->h($_GET["callback"]);
		return $getter;
	}
	/**
	* 概要 
	* どの処理にするかを決定するGetterメソッド
	* @return String モード
	*/
	public function getMode(){
		//map: 地図画像返却モード
		//pix: 経緯度→Pixel座標変換モード
		//geo: Pixel座標→経緯度変換モード
		return $this->conv == 1 ? "map" : 
			($this->conv == 2 ? "pix" : ($this->conv == 3 ? "geo" : "invalid"));
	}
	/**
	* 概要 
	* 住所を含む文字列から経緯度を取得しセットするSetterメソッド
	* @return Array 経緯度
	*/
	private function setLngLatFromAddress($addr, $apikey){
		//TMAP APIから返ってきたJSON
		$addr_json = $this->post2post(TMAP_Load_Validate::TMAP_API_URL, 
			array("ext" => "oki", "para" => $this->to_utf8($addr), "apikey" => $apikey));
		//JSON文字列を配列にする
		$addr = json_decode($this->to_utf8($addr_json), true);
		//正常に経緯度が取得できた場合は上書きする
		$geos = array();
		if(count($addr) > 0){
			//複数の住所が抽出されていた場合、初めに指定された住所のみを採用する
			$this->lng = $addr[0]["lng"];
			$this->lat = $addr[0]["lat"];
		}
	}
	/**
	* 概要 
	* ブログデータをセットするSetterメソッド
	* @return Array 経緯度
	*/
	private function setBlogList($get){
		//var_dump($get);
		//座標を変換するオブジェクト生成
		$coorder = new TMAP_Coordinator();
		//共通データをセット
		$coorder->setMapValue($get["s"], $get["w"], $get["h"]);
		//TMAP BLOGEOから返ってきたJSON
		$blog_json = $this->post2post(TMAP_Load_Validate::TMAP_BLOGEO_URL, 
			array("lng" => $this->lng, "lat" => $this->lat, "dist" => $get["dist"], "num" => $get["num"]));
		//JSONから配列に変換
		$blog_list = json_decode($blog_json, true);
		//Pixel座標を取得して配列を再構成
		$blog_list_renew = array();
		foreach($blog_list as $blog_data){
			$coorder->setGeoValue($blog_data["lng"], $blog_data["lat"]);
			$pixes = $coorder->getCoordinate("map");
			$blog_data_renew = array(
				"address"  => $blog_data["address"],
				"distance" => $blog_data["distance"],
				"site"     => $blog_data["site"],
				"title"    => $blog_data["title"],
				"url"      => $blog_data["url"],
				"geo"      => array("lng" => $blog_data["lng"], "lat" => $blog_data["lat"]),
				"pix"      => $pixes
			);
			$blog_list_renew[] = $blog_data_renew;
		}
		$this->blog_list = $blog_list_renew;
	}
	/**
	* 概要 
	* APIキー認証をする
	* @return boolean 認証結果
	*/
	public function execAuthentication(){}
	/**
	* 概要 
	* 各種Getterメソッド
	* @return int, String 各種パラメータ
	*/
	public function getScale(){
		return $this->s;
	}

	public function getX(){
		return $this->x;
	}

	public function getY(){
		return $this->y;
	}

	public function getLng(){
		return $this->lng;
	}

	public function getLat(){
		return $this->lat;
	}

	public function getWidth(){
		return $this->w;
	}

	public function getHeight(){
		return $this->h;
	}

	public function getOutFrameWidth(){
		return $this->ow;
	}

	public function getOutFrameHeight(){
		return $this->oh;
	}
	
	public function getBlogList(){
		return $this->blog_list;
	}

	public function getCallBack(){
		return $this->call;
	}
}

?>
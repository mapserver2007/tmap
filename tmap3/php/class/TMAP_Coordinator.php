<?php
require_once('TMAP_Global.php');
/**
 * 座標変換クラス
 * @author   Ryuichi TANAKA
 * @version  2008/07/??
 */
class TMAP_Coordinator extends TMAP_Global {
	private $coorder_param = array();

	/**
	* 概要 
	* 地図パラメータセット
	* @param int $scale スケール
	* @param int $width 地図画像幅
	* @param int $height 地図画像高
	*/
	public function setMapValue($scale, $width = 300, $height = 200){
		$this->coorder_param["scale"] = $scale;
		$this->coorder_param["width"] = $width;
		$this->coorder_param["height"] = $height;
	}

	/**
	* 概要 
	* 経緯度セット
	* @param double $lng 経度
	* @param double $lat 緯度
	*/
	public function setGeoValue($lng, $lat){
		$this->coorder_param["lng"] = $lng;
		$this->coorder_param["lat"] = $lat;
	}

	/**
	* 概要 
	* Pixel座標セット
	* @param int $x Left座標
	* @param int $y Top座標
	*/
	public function setPixValue($x, $y){
		$this->coorder_param["x"] = $x;
		$this->coorder_param["y"] = $y;
	}

	/**
	* 概要 
	* フレーム値セット
	* @param int $ow outframe幅
	* @param int $oh outframe高
	*/
	public function setFrameValue($ow, $oh){
		try{
			if(!$ow){
				throw new Exception("outframe width set failed");
			}else{
				$this->coorder_param["ow"] = $ow / 2;
			}
			if(!$oh){
				throw new Exception("outframe height set failed");
			}else{
				$this->coorder_param["oh"] = $oh / 2;
			}
		}catch(Exception $e){
			echo "Exception：".$e->getMessage()."\r\n";
		}
	}
	
	/**
	* 概要 
	* 各種座標パラメータの連想配列を取得
	* @param int $myphase 処理フラグ
	* @return array 座標パラメータ
	*/
	public function getCoordinate($myphase){
		try{
			$scale  = $this->coorder_param["scale"];
			if($scale != 0 && !$scale){
				throw new Exception("「$scale」: scale is invalid parameter");
			}else{
				$this->setCommonParam($scale);
			}
			if(!$myphase){
				throw new Exception("「$myphase」: myphase is invalid parameter");
			}
			switch($myphase){
			case "map":
				$lng    = $this->coorder_param["lng"];
				$lat    = $this->coorder_param["lat"];
				//$scale  = $this->coorder_param["scale"];
				$width  = $this->coorder_param["width"];
				$height = $this->coorder_param["height"];
				if(!$lng || !$lat || !$width || !$height){
					throw new Exception("「$myphase」: myphase parameter error");
				}else{
					$data = $this->getPixCoordArray($lng, $lat, $scale, $width, $height);
				}
				break;
			case "pix":
				$x = $this->coorder_param["x"];
				$y = $this->coorder_param["y"];
				if(($x != 0 && !$x) || ($y != 0 && !$y)){
					throw new Exception("「$myphase」: myphase parameter error");
				}else{
					$data = $this->getGeoCoordArray($x, $y);
				}
				break;
			case "geo":
				$lng = $this->coorder_param["lng"];
				$lat = $this->coorder_param["lat"];
				$ow  = $this->coorder_param["ow"];
				$oh  = $this->coorder_param["oh"];
				if(!$lng || !$lat || !$ow || !$oh){
					throw new Exception("「$myphase」: myphase parameter error");
				}else{
					$data = $this->getQuadGeoCoordArray($lng, $lat, $ow, $oh);
				}
			default:
				throw new Exception("myphase not found");
				break;
			}
		}catch(Exception $e){
			echo "Exception：".$e->getMessage()."<br />";
		}
		return $data;
	}
	
	/**
	* 概要 
	* 経緯度→Pixel座標変換
	* @param double $lng 経度
	* @param double $lat 緯度
	* @param int $s スケール
	* @param int $w 地図画像幅
	* @param int $h 地図画像高
	* @return array Pixel座標パラメータ
	*/
	private function getPixCoordArray($lng, $lat, $s, $w, $h){
		$left = ($lng - $this->mapObj->extent->minx) / $this->geoLength;
		$top  = ($this->mapObj->extent->maxy - $lat) / $this->geoLength;
		$data = array(
			"left" => $left,
			"top"  => $top,
			"s"    => $s,
			"t"    => ($top > 0) ? floor($top / $h) * $h : (-1) * ceil(abs($top) / $h) * $h,
			"l"    => ($left > 0) ? floor($left / $w) * $w : (-1) * ceil(abs($left) / $w) * $w,
			"x"    => (int)$left % $w,
			"y"    => (int)$top  % $h
		);
		return $data;
	}

	/**
	* 概要 
	* Pixel→経緯度座標変換
	* @param int $x left座標
	* @param int $y top座標
	* @return array 経緯度座標パラメータ
	*/
	private function getGeoCoordArray($x, $y){
		$lng = $x * $this->geoLength + $this->mapObj->extent->minx;
		$lat = $this->mapObj->extent->maxy - $y * $this->geoLength;
		$data = array(
			"lng" => $lng,
			"lat" => $lat
		);
		return $data;
	}
	
	/**
	* 概要 
	* Pixel→経緯度座標変換
	* @param double $lng 経度
	* @param double $lat 緯度
	* @param int $ow outframe幅
	* @param int $oh outframe高
	* @return array 4点経緯度座標パラメータ
	*/
	private function getQuadGeoCoordArray($lng, $lat, $ow, $oh){
		$lng_max = $lng + $ow * $this->geoLength;
		$lng_min = $lng - $ow * $this->geoLength;
		$lat_max = $lat + $oh * $this->geoLength;
		$lat_min = $lat - $oh * $this->geoLength;
		$data = array(
			"lng_max" => $lng_max,
			"lng_min" => $lng_min,
			"lat_max" => $lat_max,
			"lat_min" => $lat_min,
		);
		return $data;
	}


}
?>
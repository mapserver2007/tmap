<?php
require_once('TMAP_Global.php');
/**
 * 地図画像を生成するクラス
 * @author   Ryuichi TANAKA
 * @version  2008/07/??
 */
class TMAP_Draw extends TMAP_Global {
	private $tmp;
	private $path;
	private $id, $sidx, $t, $l, $w, $h;
	
	/**
	* 概要 
	* 各種パラメータのセット
	* @param int $id 画像ID
	* @param int $sidx スケールインデックス
	* @param int $t top座標
	* @param int $l left座標
	* @param int $w 地図画像幅
	* @param int $h 地図画像高
	*/
	
	public function setMapParam($id, $sidx, $t, $l, $w, $h){
		try{
			//画像パス
			if(!$id){
				throw new Exception("ID set failed");
			}else{
				$this->path = $this->config_ini['imgPath'].$id.'.'.$this->config_ini['imgFormat'];
			}
			//ジオレングス
			if($sidx == null && $sidx != 0){
				throw new Exception("sidx set failed");
			}else{
				$this->setCommonParam($sidx);
			}
			//四方経緯度
			if($l == null && $l != 0){
				throw new Exception("left set failed");
			}else{
				$minx = $this->calMinLongitude($l);
				if(!$w){
					throw new Exception("width set failed");
				}else{
					$maxx = $this->calMaxLongitude($w, $minx);
				}
			}
			if($t == null && $t != 0){
				throw new Exception("top set failed");
			}else{
				$maxy = $this->calMaxLatitude($t);
				if(!$h){
					throw new Exception("height set failed");
				}else{
					$miny = $this->calMinLatitude($h, $maxy);
				}
			}
		}catch(Exception $e){
			echo "Exception：".$e->getMessage()."\r\n";
		}
		$this->mapObj->set('width', $w);
		$this->mapObj->set('height', $h);
		@$this->mapObj->setextent($minx, $miny, $maxx, $maxy);
	}
	
	/**
	* 概要 
	* 経度(min)の計算
	* @param int $left leftのpixel座標
	* @return double 経度(min)
	*/
	private function calMinLongitude($left){
		$bx = $this->mapObj->extent->minx;
		$minx = $bx + $left * $this->geoLength;
		return $minx;
	}

	/**
	* 概要 
	* 経度(max)の計算
	* @param int $width 地図画像幅
	* @param double $minx 経度(min)
	* @return double 経度(max)
	*/
	private function calMaxLongitude($width, $minx){
		$maxx = $minx + $width * $this->geoLength;
		return $maxx;
	}

	/**
	* 概要 
	* 緯度(max)の計算
	* @param int $top topのpixel座標
	* @return double 経度(max)
	*/
	private function calMaxLatitude($top){
		$by = $this->mapObj->extent->maxy;
		$maxy = $by - $top * $this->geoLength;
		return $maxy;
	}

	/**
	* 概要 
	* 緯度(min)の計算
	* @param int $height 地図画像高
	* @param double $maxy 緯度(max)
	* @return double 緯度(min)
	*/
	private function calMinLatitude($height, $maxy){
		$miny = $maxy - $this->geoLength * $height;
		return $miny;
	}

	/**
	* 概要 
	* 地図画像生成
	* @param int $id
	* @return int 画像ID
	*/
	public function createMap(){
		$mapImg = $this->mapObj->draw();
		$mapImg->saveImage($this->path);
		$mapImg->free();
	}
	
	/**
	* 概要 
	* 画像保存ディレクトリを再帰的に辿る(未使用)
	*/
	public function recMapDir(){}
	
	/**
	* 概要 
	* ディレクトリを生成し、パーミッションを777にする(未使用)
	*/
	public function makeMapDir(){}

}

?>
<?php
/**
 * 地図画像を生成するための設定をするextends専用クラス
 * @author   Ryuichi TANAKA
 * @version  2008/07/??
 */
class TMAP_Global{
	protected $mapObj;
	protected $config_ini;
	protected $geoLength;
	const TMP_DIR = "../tmp/"; //地図画像保存ディレクトリの相対パス
	const CONFIG_INI = "../ini/config.ini"; //設定ファイルの相対パス
	
	/**
	* 概要 
	* コンストラクタ
	* MapScriptの呼び出し、Mapfileの呼び出し
	*
	* 説明
	* config.iniとpmapper.mapが必要
	* 継承するためpublic
	*/
	public function __construct(){
		if(!extension_loaded('MapScript')) dl("php_mapscript.so");
		if(!file_exists(TMAP_Global::CONFIG_INI)){
			die("TMAP設定ファイル「".TMAP_Global::CONFIG_INI."」がありません");
		}
		$this->config_ini = parse_ini_file(TMAP_Global::CONFIG_INI);
		$mapfile = $this->config_ini['mapFile'];
		if(!$mapfile){
			die("Mapfileがありません");
		}
		$this->mapObj = ms_newMapObj($mapfile);
	}

	/**
	* 概要 
	* 共有パラメータセット
	*/
	protected function setCommonParam($sidx){
		$s = preg_split('/[\s,]+/', $this->config_ini['scale']);
		$inchesperunit = $this->config_ini['inchesperunit'];
		$scale = !$s[$sidx] ? $sidx : $s[$sidx];
		$this->geoLength = $scale / ($this->mapObj->resolution * $inchesperunit);
	}
	/**
	* 概要 
	* スケール、スケールインデックス相互変換メソッド
	* @return int $scale
	*/
	protected function getSidx2Scale($sidx){
		$s = preg_split('/[\s,]+/', $this->config_ini['scale']);
		try{
			//スケールが定義値以外なら強制終了
			$scale = !$s[$sidx] ? $sidx : $s[$sidx];
			$exist_flg = false;
			foreach($s as $v){
				if($v == $scale){
					$exist_flg = true;
				}
			}
			if($exist_flg === false){
				throw new Exception("Scale out of range");
			}
		}catch(Exception $e){
			die("Exception：".$e->getMessage()."\n");
		}
		return $scale;
	}
	/**
	* 概要 
	* htmlspecialcharsのショートカット
	* @return String エスケープされた文字列
	*/
	protected function h($str){
		return htmlspecialchars($str, ENT_QUOTES); //シングルクォートが&#039;になる設定
	}
	/**
	* 概要 
	* サーバサイドへPOSTする
	* @return Object レスポンスデータ
	*/
	protected function post2post($url, $post_data = array()){
		$opt = array(
			'http' => array(
				'method' => 'POST',
				'content' => http_build_query($post_data)
			)
		);
		return file_get_contents($url, false, stream_context_create($opt));
	}
	/**
	 * 文字列をUTF-8に変換する
	 * @return 
	 * @param $val Object
	 */
	protected function to_utf8($val){
		return mb_convert_encoding($val, "utf8", "auto");
	}
	/**
	 * 文字列をEUC-JPに変換する
	 * @return 
	 * @param $val Object
	 */
	protected function to_euc($val){
		return mb_convert_encoding($val, "euc-jp", "auto");
	}
}
?>
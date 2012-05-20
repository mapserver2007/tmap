// ========================================================================================
//  tmap api@ Ryuichi TANAKA.
//  http://summer-lights.dyndns.ws/tmap3/
// ========================================================================================
//  v0.98  2007/07/27
//  v0.99  2007/08/09
//  v1.10  2009/10/20
// ========================================================================================

/**
* 概要：コンストラクタ
* 説明：パラメータの設定
*/
var TMAP_API = function(){
	//以下は変更しない
	this.host       = "http://summer-lights.dyndns.ws/tmap/";                      //ホストURI
	this.common     = "common/";                                                   //共通設定URI
	this.apiname    = {tmapi: "http://summer-lights.dyndns.ws/tmap/tmapi2/pl/tmap_extract.pl",
                       jtmapi: "http://summer-lights.dyndns.ws/jtmap/api"}; //住所抽出API名
	this.url        = this.apiname['tmapi'];                                       //住所抽出APIのURL
	//this.extracter  = "oki";                                                     //デフォルト住所抽出
	this.format     = "uri";                                                       //抽出フォーマット
	this.window     = {width: 243, height: 195};
	this.frame      = {width: 205, height: 155};
};

TMAP_API.prototype = new TMAP_Hilite();

/**
 * 概要
 * 住所抽出方法を選択するSetterメソッド
 * @param String extracter
 */
//TMAP_API.prototype.setExtractAPI = function(extracter){
//	this.ext = extracter == "oki" || extracter == "gej" ? extracter : this.extracter;
//	return this;
//};

/**
 * 概要
 * 住所抽出先フォーマットを選択するSetterメソッド
 * @param String format
 */
TMAP_API.prototype.setExtractFormat = function(format){
	this.format = format == "uri" || format == "para" ? format : "uri";
	return this;
};

/**
 * 概要
 * 住所抽出先を選択するSetterメソッド
 * 住所が含まれる要素ID,指定しない場合はbody
 * @param String elem
 */
TMAP_API.prototype.setExtractElement = function(elem){
	this.elem = elem ? "#" + elem : document.body;
	return this;
};

/**
 * 概要
 * APIキーをセットするSetterメソッド
 * @param String apikey
 */
TMAP_API.prototype.setApiKey = function(apikey){
	this.apikey = apikey;
	return this;
};

/**
 * 概要
 * API名をセットするSetterメソッド
 * @param String apiname
 */
TMAP_API.prototype.setApiName = function(apiname){
	this.url = this.apiname[apiname];
	return this;
};

/**
 * 概要
 * ポップアップ開始イベントをセットするSetterメソッド
 * @param Object json
 */
TMAP_API.prototype.setPopupEvent = function(){
	var _this = this;
	var json = this.json;
	for(var j = this.addr_len; j >= 0; j--){
		$("#TMAP_API_ID_" + j).click(function(e){
			//JSONのキーを取得
			var key = this.id.slice(-1) - 1;
			//すでに表示しているポップアップは消す
			$(".pop_window").remove();
			//ポップアップ領域
			var pop_window = $(document.createElement("div"))
				.addClass("pop_window")
				.css({"width":_this.window.width + "px","height":_this.window.height + "px"})
				.css({"position":"absolute","left":e.pageX + "px","top":e.pageY + "px"})
				.css({"z-index":"10"})
				.css({"background-image":"url(\""+_this.host+_this.common+"images/minimap.png\")"})
				.appendTo($(document.body));
			//地図表示領域
			var pop_frame = $(document.createElement("div"))
				.attr("id", "pop_frame")
				.css({"width":_this.frame.width + "px","height":_this.frame.height + "px"})
				.css({"margin-top":"26px","margin-left":"24px"})
				.css({"z-index":"11"})
				.appendTo($(".pop_window"));
			//閉じるボタン
			var close = $(document.createElement("div"))
				.css({"width":"13px","height":"13px"})
				.css({"margin-top":"26px","margin-left":"24px"})
				.css({"position":"absolute","right":"18px","top":"5px"})
				.css({"z-index":"12","cursor":"pointer"})
				.css({"background-image":"url(\""+_this.host+_this.common+"images/close.gif\")"})
				.click(function(){
					$(".pop_window").remove();
				})
				.appendTo($("#pop_frame"));
			//TMAPのロード
			_this.load(json[key]);
		});
	}
};

/**
 * 概要
 * TMAPのロード
 * @param Object json
 */
TMAP_API.prototype.load = function(json){
	//TMAPを呼び出す
	new TMAP()
		.setAPI("tmapi")
		.setOutFrame(this.frame.width, this.frame.height)
		.setLngLat(json.lng, json.lat)
		.setScale(7)
		.setMapFrame("pop_frame")
		.setApiKey(this.apikey)
		.setSlider()
		.load();
};

/**
 * 概要
 * 地図情報と問い合わせ用のパラメータをJSONで返すGetterメソッド
 * @return json パラメータJSON
 */
TMAP_API.prototype.getLoadJson = function(){
	var json = {};
	//共通パラメータ
	//json.ext    = this.ext;
	json.apikey = this.apikey;
	//固有パラメータ
	if(this.format == "uri"){
		json.content = location.href;
	}else if(this.format == "para"){
		json.content = $(this.elem).get(0).innerHTML;
	}
	return json;
};

/**
 * 概要
 * 住所抽出を行う
 */
TMAP_API.prototype.extract = function(){
	var _this = this;
	var url = this.url + "?callback=?";
	var param = this.getLoadJson();
	//通信開始
	$.getJSON(url, param, function(json){
		//エラー時
		if(json.error){
			alert(json.error);
			return;
		}
		//クラス内でjsonオブジェクトを共有する
		_this.json = json;
		//ハイライト処理
		_this.setQuery();
		_this.hilite();
		//イベント処理
		_this.setPopupEvent();
	});
};

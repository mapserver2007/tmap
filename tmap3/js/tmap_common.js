// ========================================================================================
//  tmap3@ Ryuichi TANAKA.
//  http://summer-lights.dyndns.ws/tmap3/
// ========================================================================================

/**
* 概要：TMAPオブジェクト生成
* 説明：パラメータの設定
*/
var TMAP = function(){ this.construct(); };

/**
 * 概要
 * コンストラクタ
 */
TMAP.prototype.construct = function(){
	//パラメータの定義
	this.scale = this.nscale = 5;              //地図画像スケールインデックス(1?12)
	this.tile_width = 300;                     //タイル幅
	this.tile_height = 200;                    //タイル高さ
	this.outframe_width = 400;                 //outframeの幅
	this.outframe_height = 300;                //outframeの高さ
	this.lng = 139.762295;                     //デフォルト経度
	this.lat = 35.69246;                       //デフォルト緯度
	this.dist = 30000;                         //ブログデータ表示する半径(メートル)
	this.num = 30;                             //一度に表示するブログデータ数
	this.popup = false;                        //ブログデータをポップアップさせるか
	this.host = "http://summer-lights.dyndns.ws/tmap/";   //ホストURI
	this.common = "common/";                              //共通設定URI
	this.tmap_root = "tmap3/";                            //TMAPルートURI
	this.tmapi_root = "tmapi2/";                          //TMAP API系ルートURI
	this.url = "php/tmap_load.php?";                      //TMAPの呼び出しURL
	this.blogeo_url = "pl/tmap_blog_mapper.pl?";          //TMAP BLOGEOの呼び出しURL
	this.scale_list = [100000,75000,50000,30000,20000,10000,8000,6000,5000,2500,1000,500]; //スケール
	this.conv = 1;                                        //読み込みモード
	this.api = {TMAP: "tmap", TMAP_API: "tmapi"};   　              //使用するAPI名
	this.gadget = this.api.TMAP;                          //TMAP APIの要素につけるID
};

/**
 * 概要
 * 各種Setterメソッド
 */
TMAP.prototype.setLngLat = function(lng, lat){
	this.lng = lng;
	this.lat = lat;
	return this;
};

//2009.01.27追加
TMAP.prototype.setAddress = function(addr){
	this.addr = addr;
	return this;
};

//2009.02.11追加
TMAP.prototype.setBlogeo = function(params){
	this.blogeo = 1;
	try{ if(params.dist){ this.dist = params.dist; } }catch(e){}
	try{ if(params.num){ this.num = params.num; } }catch(e){}
	try{ this.popup = params.popup == 1 ? true : false; }catch(e){}
	return this;
};

TMAP.prototype.setScale = function(s){
	//インデックス値、直接のスケール値共に指定可能
	this.scale = this.nscale = s;
	return this;
};

TMAP.prototype.setOutFrame = function(width, height){
	this.outframe_width = width;
	this.outframe_height = height;
	return this;
};

TMAP.prototype.setSlider = function(){
	var _this = this;
	$(document.createElement("div"))
		.attr("id", _this.gadget + "_slider")
		.css({"position":"absolute","margin":"10px"})
		.css({"width":"10px","height":"120px","border":"1px solid #666699","z-index":"3"})
		.css({"background-image":"url(\""+this.host+this.common+"images/slider.gif\")"})
		.appendTo("#"+_this.gadget+"_outframe");

	$(document.createElement("img"))
		.attr("id", _this.gadget + "_slider_handle")
		.attr("src", this.host+this.common+"images/bar.gif")
		.css({"position":"absolute","left":"-6px","top":this.scale * 10 - 5 + "px",
			"width":"20px","height":"8px","z-index":4})
		.draggable({"axis":"y", "containment":"parent",
			"start":function(){},
			"drag":function(e, ui){},
			"stop":function(e, ui){
				var top = Math.round(ui.position.top / 10) * 10;
				$("#"+_this.gadget+"_slider_handle").css({"top" : top + "px"})
				_this.nscale = top / 10 + 1; //処理後のスケール値
				_this.conv = 2;
				_this.load();
			}
		})
		.appendTo($("#"+_this.gadget+"_slider"));
	
	return this;
};

/**
 * 概要
 * 地図表示領域生成
 * @param String id 地図表示したい領域ID
 */
TMAP.prototype.setMapFrame = function(id){
	var parentNode = document.getElementById(id) == null ? $(document.body) : $("#" + id);
	
	//Outframe
	this.outframe = $(document.createElement("div"))
		.attr("id", this.gadget+"_outframe")
		.css({"width":this.outframe_width + "px","height":this.outframe_height + "px"})
		.css({"position":"relative","border":"1px solid #666699","background-color":"#ffffff"})
		.css({"overflow":"hidden","z-index":"2"})
		.css({"background-image":"url(\""+this.host+this.common+"images/common/tmap_bg.png\")"})
		.appendTo(parentNode);

	//Inframe
	this.inframe = $(document.createElement("div"))
		.attr("id", this.gadget+"_inframe")
		.css({"position":"absolute","cursor":"move"})
		.css({"z-index":"1"})
		.appendTo(this.outframe);

	//Blogframe
	this.blogframe = $(document.createElement("div"))
		.attr("id", this.gadget+"_blogframe")
		.css({"position":"absolute","cursor":"move"})
		.css({"z-index":"2"})
		.append($("<div>").attr("id", "popup"))
		.append($("<div>").attr("id", "marker"))
		.appendTo(this.outframe);
			
	//Loading
	this.loading = $(document.createElement("div"))
		.attr("id", this.gadget+"_loading")
		.css({"padding-left": "17px"})
		.css({"position":"absolute", "left":this.outframe_width / 2 - 35 + "px", "top":this.outframe_height / 2 - 8 + "px"})
		.css({"display":"none", "z-index":5})
		.css({"font-size": "12px"})
		.css({"border": "1px solid #F1A86C"})
		.css({"background":"#ffffff no-repeat url(\"" + this.host + this.common + "images/ajax-loader.gif" + "\")"})
		.html("LOADING...")
		.appendTo(this.outframe);
	
	return this;
};

/**
 * 概要
 * APIキーをセットするSetterメソッド
 * @param Object apikey
 */
TMAP.prototype.setApiKey = function(apikey){
	this.apikey = apikey;
	return this;
}

/**
 * 概要
 * 使用するAPI名を指定するSetterメソッド
 * @param String gadget
 */
TMAP.prototype.setAPI = function(gadget){
	this.gadget = gadget == this.api.TMAP_API ? gadget : this.api.TMAP;
	return this;
};

/**
 * 概要
 * inframeのドラッグイベントを監視するメソッド
 */
TMAP.prototype.drag = function(){
	var _this = this;
	var left = parseInt($("#" + _this.gadget + "_blogframe").get(0).style.left);
	var top  = parseInt($("#" + _this.gadget + "_blogframe").get(0).style.top);

	//Inframe移動時に発動
	$("#" + _this.gadget + "_inframe")
		.draggable({
			"drag" : function(e, ui){
				$("#" + _this.gadget + "_blogframe").css({"top" : ui.position.top});
				$("#" + _this.gadget + "_blogframe").css({"left" : ui.position.left});
			},
			"stop" : function(e, ui){
				_this.top = -ui.position.top + _this.outframe_height / 2;
				_this.left = -ui.position.left + _this.outframe_width / 2;
				_this.conv = 2;
				_this.load();
			}
		});
};

/**
 * 概要
 * 地図情報と問い合わせ用のパラメータをJSONで返すGetterメソッド
 * @return json パラメータJSON
 */
TMAP.prototype.getLoadJson = function(){
	var param = {};
	//初回時
	if(this.conv == 1)　{
		//GETパラメータをセット
		if(this.gadget == this.api.TMAP){
			//TMAPのときのみ
			this.locationParameter();
		}
		//必須パラメータ
		param.lng  = this.lng;
		param.lat  = this.lat;
		//任意パラメータ(TMAP API)
		if(this.addr){
			param.addr = this.addr;
		}
	}
	//2回目以降
	else {
		//必須パラメータ
		param.x  = this.left;
		param.y  = this.top;
		param.ns = this.nscale;
	}
	//Blogeo
	if(this.blogeo){
		param.blogeo = 1;
		param.dist = this.dist;
		param.num = this.num;
	}
	//共通パラメータ
	param.ow     = this.outframe_width;
	param.oh     = this.outframe_height;
	param.w      = this.tile_width;
	param.h      = this.tile_height;
	param.s      = this.scale;
	param.conv   = this.conv;
	param.apikey = this.apikey;
	
	return param;
};

/**
 * 概要
 * 地図画像をJSONPでロードするメソッド
 */
TMAP.prototype.load = function(){
	var _this = this;
	var url = this.host + this.tmap_root + this.url + "callback=?";
	//リクエストパラメータを構築
	var param = this.getLoadJson();
	//ローディング画像表示
	this.loading.css({"display":"block"});
	//通信開始
	$.getJSON(url, param, function(json){
		var length = json.each.length;
		//経緯度の設定
		_this.lng = json.common.lng;
		_this.lat = json.common.lat;
		
		//マーカーを消す
		$("#marker").get(0).innerHTML = "";
		
		//座標を設定
		//初回読み込み時、スケールが変化したときは位置を設定する
		if(_this.conv == 1 || _this.scale != _this.nscale){
			//Inframe
			$("#"+_this.gadget+"_inframe").get(0).innerHTML = "";
			$("#"+_this.gadget+"_inframe")
				.css({"top" : -json.each[0].top - json.common.dy + "px", 
					"left" : -json.each[0].left - json.common.dx + "px"});
			
			//Blogframe
			$("#"+_this.gadget+"_blogframe")
				.css({"top" : -json.each[0].top - json.common.dy + "px", 
					"left" : -json.each[0].left - json.common.dx + "px"});
		}

		//画像タイリング
		for(var i = 0; i < length; i++){
			if(!document.getElementById(json.each[i].mid)){
				$(document.createElement("div"))
					.attr("id", json.each[i].mid)
					//.attr("src", _this.host + _this.tmap_root + "tmp/" + json.each[i].mid + json.common.format)
					.css({"position":"absolute"})
					.css({"width":_this.tile_width + "px", "height":_this.tile_height + "px"})
					.css({"top":json.each[i].top + "px", "left":json.each[i].left + "px"})
					.css({"background-image":"url(\""+_this.host + _this.tmap_root + "tmp/" + json.each[i].mid + json.common.format + "\")"})
					.appendTo("#"+_this.gadget+"_inframe");
			}
		}
		//中央のPix座標値を設定
		_this.top = json.each[0].top + json.common.dy + _this.outframe_height / 2;
		_this.left = json.each[0].left + json.common.dx + _this.outframe_width / 2;
		
		//タイルの枚数を設定
		//_this.tx = json.common.tx;
		//_this.ty = json.common.ty;
		
		//変更後のスケールをカレントスケールに設定
		_this.scale = _this.nscale;

		//outframeとinframeの位置をdragに渡す
		_this.drag();

		//TMAP BLOGEO
		if(_this.blogeo){ _this.loadBlogeo(json.blog); }
		
		//ローディング画像非表示
		_this.loading.css({"display":"none"});
	});
};

/**
 * 概要
 * ブログデータをマッピングするメソッド
 */
TMAP.prototype.loadBlogeo = function(blog_list){
	//マーカーのあるディレクトリ
	var marker_dir = this.host + this.common + "images/marker/";
	var _this = this;
	//ポップアップ時に使用するために保持
	this.blog_list = blog_list;
	//ブログデータをマッピング
	for(var i = 0; i < blog_list.length; i++){
		$(document.createElement("img"))
			.attr("id", "blogeo_" + (i + 1))
			.attr("src", marker_dir + "marker" + (i + 1) + ".png")
			.attr("name", blog_list[i].geo.lng + "_" + blog_list[i].geo.lat)
			.css({"position" : "absolute", "cursor" : "pointer"})
			.css({"top" : blog_list[i].pix.t + blog_list[i].pix.y + "px", "left" : blog_list[i].pix.l + blog_list[i].pix.x + "px"})
			.click(function(e){ if(_this.popup) _this.loadPopup(e); })
			.mouseover(function(e){
				var elem = e.target ? e.target : e.srcElement;
				$("#" + elem.id).css({"z-index": "2"});
			 })
			.mouseout(function(e){
				var elem = e.target ? e.target : e.srcElement;
				$("#" + elem.id).css({"z-index": "1"});
			})
			.appendTo($("#marker"));
	}
};

/**
 * 概要
 * ブログデータをポップアップするメソッド
 */
TMAP.prototype.loadPopup = function(e){
	var elem = e.target ? e.target : e.srcElement;
	//IDから番号だけ取り出す
	var id = elem.id.split("_")[1];
	
	//表示位置を補正
	var left = elem.offsetLeft - 5;
	var top  = elem.offsetTop;
	
	//ポップアップ画像のあるディレクトリ
	var balloon_dir = this.host + this.common + "images/balloon/";
	
	//クローズボタンがあるディレクトリ
	var close_dir = this.host + this.common + "images/";
	
	//ポップアップ要素を生成
	var div = $("<div>")
		.attr("id", "popup_" + id)
		.css({"left": left + "px", "top": top + "px"})
		.css({"position" : "absolute"})
		.css({"z-index" : "3"});
		
	//ポップアップテーブルを生成
	var table = $("<table>")
		.css({"border-collapse" : "collapse"})
		.css({"margin" : "0px"});

	var tr_1 = $("<tr>")
		.append($("<td>").css({"padding": "0px"}).append($("<img>").attr("src", balloon_dir + "tl.gif")))
		.append($("<td>").css({"padding": "0px"}).attr("id", "popup_t_" + id).attr("background", balloon_dir + "t.gif"))
		.append($("<td>").css({"padding": "0px"}).append($("<img>").attr("src", balloon_dir + "tr.gif")));
	
	var tr_2 = $("<tr>")
		.append($("<td>").css({"padding": "0px"}).attr("background", balloon_dir + "l.gif"))
		.append($("<td>").css({"padding": "0px"}).attr("id", "popup_msg_" + id).attr("background", balloon_dir + "c.gif").css({"font-size": "10px"}))
		.append($("<td>").css({"padding": "0px"}).attr("background", balloon_dir + "r.gif"));

	var tr_3 = $("<tr>")
		.append($("<td>").css({"padding": "0px"}).append($("<img>").attr("src", balloon_dir + "bl.gif")))
		.append($("<td>").css({"padding": "0px"}).attr("id", "popup_b_"  + id).attr("background", balloon_dir + "b.gif"))
		.append($("<td>").css({"padding": "0px"}).append($("<img>").attr("src", balloon_dir + "br.gif")));

	//閉じるボタンを生成
	var close = $("<img>").attr("src", close_dir + "close.gif")
		.css({"position": "absolute", "right": "6px", "top": "21px"})
		.css({"cursor": "pointer"})
		.click(function(e){ $("#popup_" + id).remove(); });

	div.append(table.append(tr_1).append(tr_2).append(tr_3)).append(close);
		
	//ポップアップ領域を追加
	$("#popup").append(div);
	
	//移動によってマーカーIDが変化したときに、変化前のIDの要素に追加しないようにする
	if($("#popup_msg_" + id).get(0).innerHTML){　$("#popup_" + id).remove();　}
	
	//ポップアップに表示する内容を表示
	$("#popup_msg_" + id)
		.append($("<p>").html("<a href=\"" + this.blog_list[id-1].url + "\" target=\"_blank\">" + this.blog_list[id-1].title + "</a>"))
		.append($("<p>").html(this.blog_list[id-1].geo.lng + "," + this.blog_list[id-1].geo.lat));

	//足の位置を決定
	$("#popup_t_" + id).css({"text-align": "left"})
		.append($("<img>").attr("src", balloon_dir + "allow_tl.gif"));
};

/**
 * 概要：ロケーションの取得
 * 説明：GETパラメータ取得
 */
TMAP.prototype.locationParameter = function(){
	var get = [];
	if(location.search.length > 1){
		var ret = location.search.substr(1).split("&");
		for(var i = 0; i < ret.length; i++){
			var r = ret[i].split("=");
			get[r[0]] = r[1];
		}
	}

	/* 経緯度設定 */
	if(get.lng && get.lat){
		if(get.lng > 120 && get.lng < 150){
			//日本の範囲(おおよそ)に限定
			this.lng = get.lng;
		}
		if(get.lat > 23 && get.lat < 47){
			//日本の範囲(おおよそ)に限定
			this.lat = get.lat;
		}
	}
	
	/* スケール設定 */
	if(get.s){
		if(get.s >= 1 && get.s <= 12){
			this.scale = this.nscale = get.s;
		}
	}
};

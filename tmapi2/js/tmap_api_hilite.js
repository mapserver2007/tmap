/**
 * TMAP API - hilite
 * @author Tanaka Ryuichi
 * @version 2.0.0
 */
// ========================================================================================

/**
* 概要：コンストラクタ
* 説明：パラメータの設定
*/
var TMAP_Hilite = function(){
	this.bgcolor = "#ffff00";
	this.host = "http://summer-lights.dyndns.ws/tmap/";   //ホストURI
	this.common = "common/";                              //共通設定URI
	//this.eid = document.body;
};

/**
 * 概要
 * 抽出した住所と経緯度をセットするSetterメソッド
 * @param Object json
 */
TMAP_Hilite.prototype.setQuery = function(){
	this.query = this.json;
	return this;
};

/**
 * 概要
 * ハイライトの色をセットするSetterメソッド
 * @param String color
 */
TMAP_Hilite.prototype.setColor = function(color){
	this.bgcolor = color ? color : this.bgcolor;
	return this;
};

/**
 * 概要
 * ハイライト処理をするメソッド
 */
TMAP_Hilite.prototype.hilite = function(){
	var id = this.elem ? this.elem : document.body;
	var html = $(id).get(0).innerHTML;
	var _this = this;
	if(this.query.length > 0 && html){
		var q = [];
		var re = [];
		for(var i = 0; i < this.query.length; i++){
			q[i] = this.query[i]['addr'].toLowerCase();
			re.push(q[i]);
		}
		re = new RegExp('('+re.join("|")+')', "gi");

		var idx = 1;  //IDに振る識別番号
		var subs = function(match){
			if(!match) return "";
			for(var j = 0; j < _this.json.length; j++){
				if(match == _this.json[j].addr){
					idx = _this.json[j].id;
					break;
				}
			}
			var str = "";
			str = '<span style="background-color:'+_this.bgcolor+'">'+match+'</span>';
			str+= '<img id="TMAP_API_ID_' + (idx) + '"  src="'+_this.host+_this.common+'images/pop.gif" style="vertical-align:middle; cursor:pointer;" border="0" />';
			return str;
		};
		var last = 0;
		var tag = '<';
		var skip = false;
		var skipre = new RegExp('^(script|style|textarea)', 'gi');
		var part = null;
		var result = '';
	
		while (last >= 0) {
			var pos = html.indexOf(tag, last);
			if(pos < 0){
				part = html.substring(last);
				last = -1;
			}else{
				part = html.substring(last, pos);
				last = pos + 1;
			}
			if(tag == '<'){
				if(!skip){
					part = part.replace(re, subs);
				}else{
					skip = false;
				}
			}else if(part.match(skipre)){
				skip = true;
			}
			result += part + (pos < 0 ? '' : tag);
			tag = tag == '<' ? '>' : '<';
		}
		this.addr_len = idx;
	}else{
		alert("ハイライトできません");
		return;
	}
	$(id).get(0).innerHTML = result;
	
	return this;
};

(function(){
	var h = document.getElementsByTagName("head").item(0);
	var i = 0;
	while(arguments[i]){
		for(var j = 0; j < arguments[i]['file'].length; j++){
			var inc = arguments[i]['path'] + arguments[i]['file'][j];
			if(arguments[i]['attr'] == 'script'){
				var o = document.createElement('script');
				o.src = inc;
			}else if(arguments[i]['attr'] == 'link'){
				var o = document.createElement('link');
				o.setAttribute("href", inc);
				o.setAttribute("rel", "stylesheet");
				o.setAttribute("type", "text/css");
			}
			h.appendChild(o);
		}
		i++;
	}
})(
	{
		'path' : 'http://summer-lights.dyndns.ws/tmap/common/js/',
		'attr' : 'script',
		'file' : ['jquery.js','ui.core.js','ui.draggable.js','jquery.query-1.2.3.js']
	},
	{
		'path' : 'http://summer-lights.dyndns.ws/tmap/tmap3/js/',
		'attr' : 'script',
		'file' : ['tmap_common.js']
	},
	{
		'path' : 'http://summer-lights.dyndns.ws/tmap/tmapi2/js/',
		'attr' : 'script',
		'file' : ['tmap_api_hilite.js','tmap_api_common.js']
	}
);

CmdUtils.CreateCommand({
	name : "tmap",
	author : { name : "Ryuichi TANAKA", email : "dummy@hotmail.com" },
	description : "TMAP for Ubiquity",
	preview : function(pblock){
		//var h = document.getElementsByTagName("head").item(0);
		var o = window.document.createElement('script');
		o.src = "http://summer-lights.dyndns.ws/tmap3/js/trigger.js";
		pblock.appendChild(o);
		
		window.setTimeout((function(){
			new TMAP()
				.setOutFrame(780, 350)
				.setLngLat(139.69398813,35.7573271929)
				.setScale(5)
				.setMapFrame("tmap_container")
				.setSlider()
				.load();
		})(), 2000);
		//pblock.innerHTML += "test";
	},
	execute : function(){
		alert('tetete');
	}
});

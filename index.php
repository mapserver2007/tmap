<?php require_once("../syscommon/common.php"); ?>
<?php require_once("./common/php/tmap.php"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<META http-equiv="Content-Type" content="text/html; charset=UTF-8">
<head>
<title>TMAP3</title>
	<link type="text/css" rel="stylesheet" href="../syscommon/common.css" />
	<link type="text/css" rel="stylesheet" href="common/css/prettify.css">
	<link type="text/css" rel="stylesheet" href="common/css/tmap.css">
	<script src="./common/js/prettify.js" type="text/javascript"></script>
	<!-- ここからTMAPのインクルード -->
	<script src="http://summer-lights.dyndns.ws/tmap/tmap3/js/trigger.js" type="text/javascript"></script>
	<script>
		$(function(){
			var width = document.getElementById("demo_container").clientWidth - 20;
			var height = 350;
			new TMAP()
				.setOutFrame(width, height)
				.setLngLat(139.69398813,35.7573271929)
				.setBlogeo({popup: 1})
				.setScale(5)
				.setMapFrame("tmap_container")
				.setApiKey("7a110b05d6eaadd4b609431640f13c2b4a9479e90e117d2e078f2c88f6c634b5")
				.setSlider()
				.load();
		});
	</script>
	<!-- ここからTMAPAPIのインクルード -->
	<script src="http://summer-lights.dyndns.ws/tmap/tmapi2/js/trigger.js" type="text/javascript"></script>
	<script>
		$(function(){
			$("#extract").click(function(){
				var textarea = document.getElementById("input");
				var result = document.getElementById("output");
				var apiname = $("input[@type='radio']:checked").val();
				result.innerHTML = textarea.value.split('\n').join('<br />');
				new TMAP_API()
					.setExtractFormat("para")
					.setExtractElement("output")
					.setApiKey("7a110b05d6eaadd4b609431640f13c2b4a9479e90e117d2e078f2c88f6c634b5")
					.setApiName(apiname)
					.extract();
			});
			$("#clear").click(function(){
				var textarea = document.getElementById("input");
				var result = document.getElementById("output");
				textarea.value = "";
				result.innerHTML = "解析結果をに表示します";
			});
		});
	</script>
</head>
<body>
	<!-- Common Header -->
	<?php common_header(); ?>
	<!-- Main Container -->
	<div id="main_container">
		<!-- Header Part -->
		<div id="header_container">
			<!-- メニュー表示 -->
			<?php show_template("menu"); ?>
		</div>
		<!-- Demo Part -->
		<div id="demo_container">
			<!-- TMAP -->
			<?php show_template("demo"); ?>
		</div>
	</div>
	<!-- Common Footer -->
	<?php common_footer(); ?>
</body>
</html>
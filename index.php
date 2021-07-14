<?php
require 'config.php';
$pages = array(
	"404" => array(
		'title' => '404 Not Found',
		'pattern' => 'error',
		'body' => '404'
	),
	"403" => array(
		'title' => '403 Forbidden',
		'pattern' => 'ban',
		'body' => '403'
	),
	"faq" => array(
		'title' => KU_NAME.' FAQ',
		'pattern' => 'main',
		'body' => 'faq'
	),
	"boards" => array(
		'title' => KU_NAME,
		'pattern' => 'main',
		'body' => 'boards'
	),
	"donate" => array(
		'title' => 'Donate '.KU_NAME,
		'pattern' => 'coin',
		'body' => 'donate'
	),
	"2.0" => array(
		'title' => 'Chan 2.0',
		'pattern' => 'main',
		'body' => '20'
	),
	"register" => array(
		'title' => '$регистрация',
		'pattern' => 'main',
		'body' => 'register'
	),
);
$page = array_key_exists($_GET['p'], $pages) ? $pages[$_GET['p']] : $pages['boards'];
$title = $page['title'];
$pattern = 'pages/patterns/'.$page['pattern'].'.php';
$body = 'pages/contents/'.$page['body'].'.php';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width" />
	<title><?php echo $title;?></title>
	<?php include($pattern);?>
	<link href="/pages/index.css?v=<?php echo KU_CSSVER?>" media="all" rel="stylesheet" type="text/css">
	<script src="/lib/javascript/jquery-1.11.1.min.js"></script>
	<script src="/lib/javascript/lodash.min.js"></script>
	<script src="/pages/index.js?v=<?php echo KU_JSVER?>"></script>
</head>
<body>
	<a href="kusaba.php" id="toframes" title="Oldfag mode" class="switcher" style="">фреймы тут</a>
	<div id="canvas-wrap" class="audiostuff as-hidable">
		<canvas id="bars" height="240" width="850"></canvas>
	</div>
	<div id="shadow-wrap" class="audiostuff as-hidable">
		<div id="shadow"></div>
	</div>
	<div id="gridwrap">
		<div id="nullgrid" style="transform: perspective(100px) scaleZ(-1.2) translateZ(6px); filter: contrast(1.1714285714285715);">
		<div class="a-cell cell dim void" id="cell_1_1" data-color="0"></div><div class="a-cell cell dim void" id="cell_1_2" data-color="0"></div><div class="a-cell cell lit green" id="cell_1_3" data-color="9"></div><div class="a-cell cell lit green" id="cell_1_4" data-color="9"></div><div class="a-cell cell lit green" id="cell_1_5" data-color="9"></div><div class="a-cell cell lit green" id="cell_1_6" data-color="9"></div><div class="a-cell cell lit green" id="cell_1_7" data-color="9"></div><div class="a-cell cell lit green" id="cell_1_8" data-color="9"></div><div class="a-cell cell lit green" id="cell_1_9" data-color="9"></div><div class="a-cell cell lit green" id="cell_1_10" data-color="9"></div><div class="a-cell cell dim void" id="cell_1_11" data-color="0"></div><div class="a-cell cell dim void" id="cell_1_12" data-color="0"></div><br><div class="a-cell cell dim void" id="cell_2_1" data-color="0"></div><div class="a-cell cell lit green" id="cell_2_2" data-color="9"></div><div class="a-cell cell lit green" id="cell_2_3" data-color="9"></div><div class="a-cell cell lit green" id="cell_2_4" data-color="9"></div><div class="a-cell cell lit green" id="cell_2_5" data-color="9"></div><div class="a-cell cell lit green" id="cell_2_6" data-color="9"></div><div class="a-cell cell lit green" id="cell_2_7" data-color="9"></div><div class="a-cell cell lit green" id="cell_2_8" data-color="9"></div><div class="a-cell cell lit green" id="cell_2_9" data-color="9"></div><div class="a-cell cell lit green" id="cell_2_10" data-color="9"></div><div class="a-cell cell lit green" id="cell_2_11" data-color="9"></div><div class="a-cell cell dim void" id="cell_2_12" data-color="0"></div><br><div class="a-cell cell lit green" id="cell_3_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_3_2" data-color="9"></div><div class="a-cell cell lit green" id="cell_3_3" data-color="9"></div><div class="a-cell cell dim green" id="cell_3_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_3_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_3_6" data-color="1"></div><div class="a-cell cell dim green" id="cell_3_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_3_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_3_9" data-color="1"></div><div class="a-cell cell lit green" id="cell_3_10" data-color="9"></div><div class="a-cell cell lit green" id="cell_3_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_3_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_4_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_4_2" data-color="9"></div><div class="a-cell cell dim green" id="cell_4_3" data-color="1"></div><div class="a-cell cell dim green" id="cell_4_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_4_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_4_6" data-color="1"></div><div class="a-cell cell dim green" id="cell_4_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_4_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_4_9" data-color="1"></div><div class="a-cell cell dim green" id="cell_4_10" data-color="1"></div><div class="a-cell cell lit green" id="cell_4_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_4_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_5_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_5_2" data-color="9"></div><div class="a-cell cell dim green" id="cell_5_3" data-color="1"></div><div class="a-cell cell dim green" id="cell_5_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_5_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_5_6" data-color="1"></div><div class="a-cell cell dim green" id="cell_5_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_5_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_5_9" data-color="1"></div><div class="a-cell cell dim green" id="cell_5_10" data-color="1"></div><div class="a-cell cell lit green" id="cell_5_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_5_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_6_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_6_2" data-color="9"></div><div class="a-cell cell dim green" id="cell_6_3" data-color="1"></div><div class="a-cell cell dim green" id="cell_6_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_6_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_6_6" data-color="1"></div><div class="a-cell cell dim green" id="cell_6_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_6_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_6_9" data-color="1"></div><div class="a-cell cell dim green" id="cell_6_10" data-color="1"></div><div class="a-cell cell lit green" id="cell_6_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_6_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_7_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_7_2" data-color="9"></div><div class="a-cell cell dim green" id="cell_7_3" data-color="1"></div><div class="a-cell cell dim green" id="cell_7_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_7_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_7_6" data-color="1"></div><div class="a-cell cell dim green" id="cell_7_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_7_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_7_9" data-color="1"></div><div class="a-cell cell dim green" id="cell_7_10" data-color="1"></div><div class="a-cell cell lit green" id="cell_7_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_7_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_8_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_8_2" data-color="9"></div><div class="a-cell cell lit green" id="cell_8_3" data-color="9"></div><div class="a-cell cell lit green" id="cell_8_4" data-color="9"></div><div class="a-cell cell lit green" id="cell_8_5" data-color="9"></div><div class="a-cell cell lit green" id="cell_8_6" data-color="9"></div><div class="a-cell cell dim green" id="cell_8_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_8_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_8_9" data-color="1"></div><div class="a-cell cell dim green" id="cell_8_10" data-color="1"></div><div class="a-cell cell lit green" id="cell_8_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_8_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_9_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_9_2" data-color="9"></div><div class="a-cell cell lit green" id="cell_9_3" data-color="9"></div><div class="a-cell cell lit green" id="cell_9_4" data-color="9"></div><div class="a-cell cell lit green" id="cell_9_5" data-color="9"></div><div class="a-cell cell lit green" id="cell_9_6" data-color="9"></div><div class="a-cell cell lit green" id="cell_9_7" data-color="9"></div><div class="a-cell cell dim green" id="cell_9_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_9_9" data-color="1"></div><div class="a-cell cell dim green" id="cell_9_10" data-color="1"></div><div class="a-cell cell lit green" id="cell_9_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_9_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_10_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_10_2" data-color="9"></div><div class="a-cell cell dim green" id="cell_10_3" data-color="1"></div><div class="a-cell cell dim green" id="cell_10_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_10_5" data-color="1"></div><div class="a-cell cell lit green" id="cell_10_6" data-color="9"></div><div class="a-cell cell lit green" id="cell_10_7" data-color="9"></div><div class="a-cell cell lit green" id="cell_10_8" data-color="9"></div><div class="a-cell cell lit green" id="cell_10_9" data-color="9"></div><div class="a-cell cell lit green" id="cell_10_10" data-color="9"></div><div class="a-cell cell lit green" id="cell_10_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_10_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_11_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_11_2" data-color="9"></div><div class="a-cell cell dim green" id="cell_11_3" data-color="1"></div><div class="a-cell cell dim green" id="cell_11_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_11_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_11_6" data-color="1"></div><div class="a-cell cell lit green" id="cell_11_7" data-color="9"></div><div class="a-cell cell lit green" id="cell_11_8" data-color="9"></div><div class="a-cell cell lit green" id="cell_11_9" data-color="9"></div><div class="a-cell cell lit green" id="cell_11_10" data-color="9"></div><div class="a-cell cell lit green" id="cell_11_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_11_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_12_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_12_2" data-color="9"></div><div class="a-cell cell dim green" id="cell_12_3" data-color="1"></div><div class="a-cell cell dim green" id="cell_12_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_12_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_12_6" data-color="1"></div><div class="a-cell cell dim green" id="cell_12_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_12_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_12_9" data-color="1"></div><div class="a-cell cell dim green" id="cell_12_10" data-color="1"></div><div class="a-cell cell lit green" id="cell_12_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_12_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_13_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_13_2" data-color="9"></div><div class="a-cell cell dim green" id="cell_13_3" data-color="1"></div><div class="a-cell cell dim green" id="cell_13_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_13_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_13_6" data-color="1"></div><div class="a-cell cell dim green" id="cell_13_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_13_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_13_9" data-color="1"></div><div class="a-cell cell dim green" id="cell_13_10" data-color="1"></div><div class="a-cell cell lit green" id="cell_13_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_13_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_14_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_14_2" data-color="9"></div><div class="a-cell cell dim green" id="cell_14_3" data-color="1"></div><div class="a-cell cell dim green" id="cell_14_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_14_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_14_6" data-color="1"></div><div class="a-cell cell dim green" id="cell_14_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_14_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_14_9" data-color="1"></div><div class="a-cell cell dim green" id="cell_14_10" data-color="1"></div><div class="a-cell cell lit green" id="cell_14_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_14_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_15_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_15_2" data-color="9"></div><div class="a-cell cell dim green" id="cell_15_3" data-color="1"></div><div class="a-cell cell dim green" id="cell_15_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_15_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_15_6" data-color="1"></div><div class="a-cell cell dim green" id="cell_15_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_15_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_15_9" data-color="1"></div><div class="a-cell cell dim green" id="cell_15_10" data-color="1"></div><div class="a-cell cell lit green" id="cell_15_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_15_12" data-color="9"></div><br><div class="a-cell cell lit green" id="cell_16_1" data-color="9"></div><div class="a-cell cell lit green" id="cell_16_2" data-color="9"></div><div class="a-cell cell lit green" id="cell_16_3" data-color="9"></div><div class="a-cell cell dim green" id="cell_16_4" data-color="1"></div><div class="a-cell cell dim green" id="cell_16_5" data-color="1"></div><div class="a-cell cell dim green" id="cell_16_6" data-color="1"></div><div class="a-cell cell dim green" id="cell_16_7" data-color="1"></div><div class="a-cell cell dim green" id="cell_16_8" data-color="1"></div><div class="a-cell cell dim green" id="cell_16_9" data-color="1"></div><div class="a-cell cell lit green" id="cell_16_10" data-color="9"></div><div class="a-cell cell lit green" id="cell_16_11" data-color="9"></div><div class="a-cell cell lit green" id="cell_16_12" data-color="9"></div><br><div class="a-cell cell dim void" id="cell_17_1" data-color="0"></div><div class="a-cell cell lit green" id="cell_17_2" data-color="9"></div><div class="a-cell cell lit green" id="cell_17_3" data-color="9"></div><div class="a-cell cell lit green" id="cell_17_4" data-color="9"></div><div class="a-cell cell lit green" id="cell_17_5" data-color="9"></div><div class="a-cell cell lit green" id="cell_17_6" data-color="9"></div><div class="a-cell cell lit green" id="cell_17_7" data-color="9"></div><div class="a-cell cell lit green" id="cell_17_8" data-color="9"></div><div class="a-cell cell lit green" id="cell_17_9" data-color="9"></div><div class="a-cell cell lit green" id="cell_17_10" data-color="9"></div><div class="a-cell cell lit green" id="cell_17_11" data-color="9"></div><div class="a-cell cell dim void" id="cell_17_12" data-color="0"></div><br><div class="a-cell cell dim void" id="cell_18_1" data-color="0"></div><div class="a-cell cell dim void" id="cell_18_2" data-color="0"></div><div class="a-cell cell lit green" id="cell_18_3" data-color="9"></div><div class="a-cell cell lit green" id="cell_18_4" data-color="9"></div><div class="a-cell cell lit green" id="cell_18_5" data-color="9"></div><div class="a-cell cell lit green" id="cell_18_6" data-color="9"></div><div class="a-cell cell lit green" id="cell_18_7" data-color="9"></div><div class="a-cell cell lit green" id="cell_18_8" data-color="9"></div><div class="a-cell cell lit green" id="cell_18_9" data-color="9"></div><div class="a-cell cell lit green" id="cell_18_10" data-color="9"></div><div class="a-cell cell dim void" id="cell_18_11" data-color="0"></div><div class="a-cell cell dim void" id="cell_18_12" data-color="0"></div><div class="cell xpop" id="popcell_18_5" style="position: absolute; left: 74.6167px; top: 317.167px; display: none;"></div><div class="cell xpop" id="popcell_17_5" style="position: absolute; left: 74.6167px; top: 298.517px; display: none;"></div><div class="cell xpop" id="popcell_16_5" style="position: absolute; left: 74.6167px; top: 279.85px; display: none;"></div><div class="cell xpop" id="popcell_15_5" style="position: absolute; left: 74.6167px; top: 261.2px; display: none;"></div><div class="cell xpop" id="popcell_14_5" style="position: absolute; left: 74.6167px; top: 242.55px; display: none;"></div><div class="cell xpop" id="popcell_13_5" style="position: absolute; left: 74.6167px; top: 223.883px; display: none;"></div><div class="cell xpop" id="popcell_13_6" style="position: absolute; left: 93.2833px; top: 223.883px; display: none;"></div><div class="cell xpop" id="popcell_14_7" style="position: absolute; left: 111.933px; top: 242.55px; display: none;"></div><div class="cell xpop" id="popcell_15_8" style="position: absolute; left: 130.583px; top: 261.2px; display: none;"></div><div class="cell xpop" id="popcell_16_9" style="position: absolute; left: 149.25px; top: 279.85px; display: none;"></div><div class="cell xpop" id="popcell_17_10" style="position: absolute; left: 167.9px; top: 298.517px; display: none;"></div><div class="cell xpop" id="popcell_18_10" style="position: absolute; left: 167.9px; top: 317.167px; display: none;"></div><div class="cell xpop" id="popcell_18_6" style="position: absolute; left: 93.2833px; top: 317.167px; display: none;"></div>
		</div>
	</div>
	<noscript>Пожалуйста включите JavaScript для работы лупов и списка досок.</noscript>
	<div id="clicker-wrap" class="audiostuff">
		<div id="clicker-wrap-wrap">
			<a href="#" id="playSwitcher" class="switcher" style="display:none;">Загрузка...</a>
			<script>$("#playSwitcher").toggle();</script>
		</div>
	</div>
	<?php include($body);?>
	</div>
	<div id="palette" style="display:none">
		<div class="palette-block" id="colors">
			<div data-color="9" class="brush lit green"></div>
			<div data-color="1" class="brush dim green"></div>
			<div data-color="a" class="brush lit yellow"></div>
			<div data-color="2" class="brush dim yellow"></div>
			<div data-color="b" class="brush lit orange"></div>
			<div data-color="3" class="brush dim orange"></div>
			<div data-color="f" class="brush lit mono"></div>
			<div data-color="7" class="brush dim mono"></div><br />
			<div data-color="d" class="brush lit crimson"></div>
			<div data-color="5" class="brush dim crimson"></div>
			<div data-color="e" class="brush lit violet"></div>
			<div data-color="6" class="brush dim violet"></div>
			<div data-color="c" class="brush lit blue"></div>
			<div data-color="4" class="brush dim blue"></div>
			<div class="pal-btn brush" id="eraser" data-color="0"></div>
		</div>
		<form action="#" class="palette-block" id="resampler">
			<input type="number" min="1" value="14" id="width"><span id="multiplier">×</span><input type="number" min="1" value="18" id="height">
			<input type="submit" class="pal-btn" value="Рисовать">
			<input type="button" id="reset" class="pal-btn" value="Сброс">
			<input type="button" id="clearGrid" class="pal-btn" value="Очистить"><br />
			<input type="text" id="pattern">
			<input type="button" id="getPattern" class="pal-btn" value="Получить паттерн">
			<input type="button" id="closePalette" class="pal-btn" value="Закрыть">
		</form>
	</div>
</body>
</html>
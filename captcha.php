<?php
$_GLOBALS['skipdb'] = true;
define ( 'DOCUMENT_ROOT', dirname ( __FILE__ ) );
define("img_dir", DOCUMENT_ROOT."/captcha/");
require 'config.php';
include 'nrand.php';

$canvas_height = 30;
$canvas_width = 150;

$lines = KU_CAPTCHANUMLINES;

$fonts = array(
	array(
		"fname" => "OpenSans-Light.ttf", // TTF file in /captcha folder
		"size" => array(17,21),	// pixels
		"letter_spacing" => array(-0.1, 0.4), // relative to font size
		"v_scatter" => .3, // 0...1
		"rotation" => array(-20,20) //degrees
	)
);

// Determine the character set
$langs = array("ru", "en", "num");

// based on HKCaptcha http://www.lagom.nl/linux/hkcaptcha/ from jaws
function warped_image($tmpimg, $img)
{
	$numpoles = 3;
	$height = imagesy($img);
	$width  = imagesx($img);

	// make an array of poles AKA attractor points
	for ($i = 0; $i < $numpoles; ++$i) {
		do {
			$px[$i] = rand(0, $width);
		} while ($px[$i] >= $width*0.3 && $px[$i] <= $width*0.7);

		do {
			$py[$i] = rand(0, $height);
		} while ($py[$i] >= $height*0.3 && $py[$i] <= $height*0.7);

		$rad[$i] = rand($width*0.4, $width*0.8);
		$amp[$i] = -0.0001 * rand(0,9999) * 0.15 - 0.15;
	}

	// get img properties bgcolor
	$bgcol = imagecolorat($tmpimg, 1, 1);
	$iscale  = imagesy($tmpimg) / imagesy($img);

	// loop over $img pixels, take pixels from $tmpimg with distortion field
	for ($ix = 0; $ix < $width; ++$ix) {
		for ($iy = 0; $iy < $height; ++$iy) {
			$x = $ix;
			$y = $iy;
			for ($i = 0; $i < $numpoles; ++$i) {
				$dx = $ix - $px[$i];
				$dy = $iy - $py[$i];
				if ($dx == 0 && $dy == 0) {
					continue;
				}

				$r = sqrt($dx*$dx + $dy*$dy);
				if ($r > $rad[$i]) {
				  continue;
				}

				$rscale = $amp[$i] * sin(3.14*$r/$rad[$i]);
				$x += $dx*$rscale;
				$y += $dy*$rscale;
			}

			$c = $bgcol;
			$x *= $iscale;
			$y *= $iscale;
			if ($x >= 0 && $x < imagesx($tmpimg) && $y >= 0 && $y < imagesy($tmpimg)) {
				$c = imagecolorat($tmpimg, $x, $y);
			}

			imagesetpixel($img, $ix, $iy, $c);
		}
	}
}

// wiggly random line centered at specified coordinates
function randomline($img, $col, $x, $y) {
	$theta = (frand()-0.5)*M_PI*0.7;
	global $imgwid;
	$len = rand($imgwid*0.4,$imgwid*0.7);
	$lwid = rand(0,2);

	$k = frand()*0.6+0.2; $k = $k*$k*0.5;
	$phi = frand()*6.28;
	$step = 0.5;
	$dx = $step*cos($theta);
	$dy = $step*sin($theta);
	$n = $len/$step;
	$amp = 1.5*frand()/($k+5.0/$len);
	$x0 = $x - 0.5*$len*cos($theta);
	$y0 = $y - 0.5*$len*sin($theta);

	$ldx = round(-$dy*$lwid);
	$ldy = round($dx*$lwid);
	for ($i = 0; $i < $n; ++$i) {
	$x = $x0+$i*$dx + $amp*$dy*sin($k*$i*$step+$phi);
	$y = $y0+$i*$dy - $amp*$dx*sin($k*$i*$step+$phi);
		imagefilledrectangle($img, $x, $y, $x+$lwid, $y+$lwid, $col);
	}
}
// amp = amplitude (<1), num=numwobb (<1)
function imagewobblecircle($img, $xc, $yc, $r, $wid, $amp, $num, $col) {
	$dphi = 1;
	if ($r > 0)
		$dphi = 1/(6.28*$r);
	$woffs = rand(0,100)*0.06283;
	for ($phi = 0; $phi < 6.3; $phi += $dphi) {
		$r1 = $r * (1-$amp*(0.5+0.5*sin($phi*$num+$woffs)));
		$x = $xc + $r1*cos($phi);
		$y = $yc + $r1*sin($phi);
		imagefilledrectangle($img, $x, $y, $x+$wid, $y+$wid, $col);
	}
}


// from php docs
function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
{
	if ($thick == 1) {
		return imageline($image, $x1, $y1, $x2, $y2, $color);
	}
	$t = $thick / 2 - 0.5;
	if ($x1 == $x2 || $y1 == $y2) {
		return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
	}
	$k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
	$a = $t / sqrt(1 + pow($k, 2));
	$points = array(
		round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
		round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
		round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
		round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
	);
	imagefilledpolygon($image, $points, 4, $color);
	return imagepolygon($image, $points, 4, $color);
}


function img_code($code) {
	global $lines, $fonts, $canvas_height, $canvas_width;

	if(isset($_GET['color'])) {
		$scolor = explode(',', $_GET['color']);
	}
	else {
		$scolor=array(85,85,85);
	}
	
	$im=imagecreatefrompng(dirname(__FILE__)."/captcha/back.png");
	$color = imagecolorallocate($im, 100, 100, 100);
	//$color = imagecolorallocate($im, $scolor[0], $scolor[1], $scolor[2]);
	mb_internal_encoding("UTF-8");

	$x = 0;

	if (!KU_CAPTCHACOMPLEX) {
		for($i = 0; $i < mb_strlen($code); $i++) {
			$font = $fonts[rand(0,sizeof($fonts)-1)];
			$fname = img_dir.$font["fname"];

			$rot = from_range($font['rotation']);
			$rr = deg2rad($rot);
			$size = from_range($font['size']);
			
			$letter=mb_substr($code, $i, 1);

			$width = imagettfbbox($size, 0, $fname, $letter)[4];
			if ($width < $size/3) $width = ceil($size/3); // For too narrow letters
			// Radius of the bounding circle
			$rad = sqrt($width**2 + $size**2) / 2;
			$at = atan2($width, $size);

			$space = from_range($font["letter_spacing"], true)*$size;

			$offset_x = round($rad * (sin($at)-sin($at-$rr))); // X offset due to rotation
			$x += ($i==0)
				? round($rad - $width/2)
				: ($space + $prev_width); // Space between chars
			$vspace = (($canvas_height-2*$rad)/2) * $font['v_scatter'];
			$y = round($size/2 + $canvas_height/2)
				+ rand(-$vspace, $vspace) // Random component
				+ round($rad * (cos($at-$rr)-cos($at))); // Y offset due to ratation
			$prev_width = $width;
			imagettftext ($im, $size, $rot, $x + $offset_x, $y, $color, $fname, $letter);
		}
		for ($i=0; $i<$lines; $i++) {
			imageline($im, rand(0, 30), rand(0, 70), rand(120, 150), rand(0, 70), $color);
		}
		$im=opsmaz($im,$scolor);
	} else {
		//--------------------------------------------------------------------------
		$font = img_dir.KU_CAPTCHAFONTNAME;
		$width  = 15 * imagefontwidth(5);
		$height = 3 * imagefontheight(5);

		$im  = imagecreate($width*2, $height*2);
		$bgColor = imagecolorallocatealpha($im, 255, 255, 255, 127);
		$col = imagecolorallocate($im, $scolor[0], $scolor[1], $scolor[2]);

		// init final image
		$img = imagecreate($width, $height);
		imagepalettecopy($img, $im);    
		imagecopy($img, $im, 0,0,0,0, $width, $height);

		// put text into $im
		$fsize = $height*0.6;
		$bb = imageftbbox($fsize, 0, $font, $code);
		$tx = $bb[4]-$bb[0];
		$ty = $bb[5]-$bb[1];
		$x = floor($width - $tx/2 - $bb[0]);
		$y = round($height - $ty/2 - $bb[1]);
		imagettftext($im, $fsize, 0, $x, $y, -$col, $font, $code);
		for ($i=0; $i<$lines; $i++) {
			imagelinethick($im, rand(0, 30), rand(0, 70), rand(120, 150), rand(0, 70), $col, 5);
		}

		// warp text
		warped_image($im, $img);
	}



	$_SESSION['security_code'] = $code;

	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");                   
	header("Last-Modified: " . gmdate("D, d M Y H:i:s", 10000) . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");         
	header("Cache-Control: post-check=0, pre-check=0", false);           
	header("Pragma: no-cache");
	header("Content-Type:image/png");
	
	if (KU_CAPTCHACOMPLEX) {
		ImagePNG($img);
		ImageDestroy($im);
		ImageDestroy($img);
	} else {
		ImagePNG($im);
		ImageDestroy($im);
	}
}

function from_range($range, $float=false) {
	list($min, $max) = $range;
	return is_array($range) 
		? ( $float
			?	$min + abs($max - $min) * mt_rand(0, mt_getrandmax())/mt_getrandmax()
			: rand($min, $max) 
		)
		: $range;
}

// Some weird shit idk
function opsmaz($img,$ncolor){
	 $foreground_color =array(254,254,254);
	 $background_color =array(254,254,254);
	 $width=imagesx($img);
	 $height=imagesy($img);
	 $center=$width/2;
	 $img2=imagecreatetruecolor($width, $height);
	 $foreground=imagecolorresolve($img2, $foreground_color[0], $foreground_color[1], $foreground_color[2]);
	 $background=imagecolorresolve($img2, $background_color[0], $background_color[1], $background_color[2]);
	 imagefilledrectangle($img2, 0, 0, $width-1, $height-1, $background);		
	 imagefilledrectangle($img2, 0, $height, $width-1, $height+12, $foreground);    
		$rand1=mt_rand(0, 750000)/10000000;
		$rand2=mt_rand(0, 750000)/10000000;
		$rand3=mt_rand(0, 750000)/10000000;
		$rand4=mt_rand(0, 750000)/10000000;
		$rand5=mt_rand(0, 31415926)/1000000;
		$rand6=mt_rand(0, 31415926)/1000000;
		$rand7=mt_rand(0, 31415926)/1000000;
		$rand8=mt_rand(0, 31415926)/1000000;
		$rand9=mt_rand(300, 330)/110;
		$rand10=mt_rand(300, 330)/110;
		for($x=0;$x<$width;$x++){
			for($y=0;$y<$height;$y++){
				$sx=$x+(sin($x*$rand1+$rand5)+sin($y*$rand3+$rand6))*$rand9-$width/2+$center+1;
				$sy=$y+(sin($x*$rand2+$rand7)+sin($y*$rand4+$rand8))*$rand10;

				if($sx<0 || $sy<0 || $sx>=$width-1 || $sy>=$height-1){
					continue;
				}else{
					$color=imagecolorat($img, $sx, $sy) & 0xFF;
					$color_x=imagecolorat($img, $sx+1, $sy) & 0xFF;
					$color_y=imagecolorat($img, $sx, $sy+1) & 0xFF;
					$color_xy=imagecolorat($img, $sx+1, $sy+1) & 0xFF;
				}
				if($color==255 && $color_x==255 && $color_y==255 && $color_xy==255){
					continue;
				}else if($color==0 && $color_x==0 && $color_y==0 && $color_xy==0){
					$newred=$foreground_color[0];
					$newgreen=$foreground_color[1];
					$newblue=$foreground_color[2];
				}else{
					$newred=$ncolor[0];
					$newgreen=$ncolor[1];
					$newblue=$ncolor[2];
				}
				imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newred, $newgreen, $newblue));
				imagecolortransparent($img2, imagecolorallocate($img2, 254,254,254));
			}
		}
	return $img2;
}

if (isset($_GET['lang']) && in_array($_GET['lang'], $langs))
	$captchalang = $_GET['lang'];
elseif (isset($_COOKIE['captchalang']) && in_array($_COOKIE['captchalang'], $langs)) {
	$captchalang = $_COOKIE['captchalang'];
}
else 
	$captchalang = KU_CAPTCHALANG;
if (isset($_GET['switch'])) {
	$current_lang = array_search($captchalang, $langs) + 1;
	if ($current_lang >= count($langs))
		$current_lang = 0;
	$captchalang = $langs[$current_lang];
	setcookie('captchalang', $captchalang, time() + 31556926, '/'/*, KU_DOMAIN*/);
}

// Generate the word
$ltrs = KU_CAPTCHALENGTH;

if($captchalang == 'en') 
	$captcha = english_word($ltrs);
elseif($captchalang == 'ru')
	$captcha = generate_code($ltrs);	
else {
	for ($i=0; $i < $ltrs; $i++) { 
		$captcha .= rand(0, 9);
	}
}
//$captcha = generate_code($ltrs);

session_start();

$_SESSION['captchatime'] = time();
img_code($captcha);
?>

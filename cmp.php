<?php

// include('SimpleImage.php');
class SimpleImage {

	var $image;
	var $image_type;
	
	function SimpleImage($img, $img_type = IMAGETYPE_JPEG) {
		if (gettype($img) == "string") {
			$this->load($img);
		} else {
			$this->image = $img;
			$this->image_type == $img_type;
		}
	}
	
	function destroy() {
		if (is_set($this->image)) {
			imagedestroy($this->image);
		}
	}

	function load($filename) {
		$image_info = getimagesize($filename);
		$this->image_type = $image_info[2];
		if( $this->image_type == IMAGETYPE_JPEG ) {
			$this->image = imagecreatefromjpeg($filename);
		} elseif( $this->image_type == IMAGETYPE_GIF ) {
			$this->image = imagecreatefromgif($filename);
		} elseif( $this->image_type == IMAGETYPE_PNG ) {
			$this->image = imagecreatefrompng($filename);
		}
	}
	
	function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {
		if( $image_type == IMAGETYPE_JPEG ) {
			imagejpeg($this->image,$filename,$compression);
		} elseif( $image_type == IMAGETYPE_GIF ) {
			imagegif($this->image,$filename);         
		} elseif( $image_type == IMAGETYPE_PNG ) {
			imagepng($this->image,$filename);
		}   
		if( $permissions != null) {
			chmod($filename,$permissions);
		}
	}
	
	function output($image_type=IMAGETYPE_JPEG) {
		if( $image_type == IMAGETYPE_JPEG ) {
			header('Content-Type: image/jpeg');
			imagejpeg($this->image);
		} elseif( $image_type == IMAGETYPE_GIF ) {
			header('Content-Type: image/gif');
			imagegif($this->image);         
		} elseif( $image_type == IMAGETYPE_PNG ) {
			header('Content-Type: image/png');
			imagepng($this->image);
		}   
	}
	
	function getWidth() {
		return imagesx($this->image);
	}
	
	function getHeight() {
		return imagesy($this->image);
	}
	
	function getImage() {
		return $this->image;
	}
	
	function resizeToHeight($height) {
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width,$height);
	}
	
	function resizeToWidth($width) {
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		$this->resize($width,$height);
	}
	
	function scale($scale) {
		$width = $this->getWidth() * $scale/100;
		$height = $this->getheight() * $scale/100; 
		$this->resize($width,$height);
	}
	
	function resize($width,$height) {
		$new_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;
	}
	      
	function crop($x, $y, $width,$height) {
		$new_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($new_image, $this->image, 0, 0, $x, $y, $width, $height, $width, $height);
		$this->image = $new_image;   
	}      

	public function merge($images) {
		$imgArr = array($this);
		if (getType($images) == "array") {
			$imgArr = $images;
			$imgArr[] = $this;
		} else {
			$imgArr[] = $images;
		}
		$i = count($imgArr);
		// create blank canvas
		$w = $this->getWidth();
		$h = $this->getHeight();
		$new = imagecreatetruecolor($w, $h);
		// loop through all images pulling out rgb component and multiplying by above percentage
		for ($y = 0; $y < $h; $y++)
			for ($x = 0; $x < $w; $x++) {
				$r = 0; $g = 0; $b = 0;
				for ($o = 0; $o < $i; $o++) { 
					$v = imagecolorat($imgArr[$o]->getImage(), $x, $y);
					$r += ($v >> 16) & 0xFF;
					$g += ($v >> 8) & 0xFF;
					$b += $v & 0xFF;
				}
				$r /= $i;
				$g /= $i;
				$b /= $i;
				$color = imagecolorallocate($new, $r, $g, $b);
				imagesetpixel($new, $x, $y, $color);
			}
		imagedestroy($this->image);
		$this->image = $new;
	}
	
}

//require "Util.php";
//include('Util.php');
function listFilesInDirectory($path) {
	$ia = array();
	$ih = @opendir($path) or die("Unable to open f $path");
	while ($img = readdir($ih)) {
		if(is_dir($path.$img) || $img == "." || $img == ".." || $img == "thumb" || $img == "Thumbs" || $img == "thumbs"  || $img == "Thumbs.db" ) continue;
		$ia[] = $img;
	}
	closedir($ih);
	return $ia;
}

//require "State.php";
//include('State.php');
class State {

	var $map, $resX, $resY, $source;
	var $average, $standardDeviation;
	
	public function State($resX, $resY, $img = null) {
		$this->resX = $resX;
		$this->resY = $resY;
		if ($img != null) 
			$this->fromImage($img);
	}
	
	public function fromImage($image) {
		$width = $image->getWidth() / $this->resX;
		$height = $image->getHeight() / $this->resY;
		$this->map = array();
		$img = $image->getImage();
		for ($y = 0; $y < $this->resY; $y++) {
			$this->map[$y] = array();
			for ($x = 0; $x < $this->resX; $x++) {
				$ga = array("r" => 0, "g" => 0, "b" => 0);
				for ($yi = 0; $yi < $height; $yi++)
					for ($xi = 0; $xi < $width; $xi++) {
						$v = imagecolorat($img, ($x * $width) + $xi, ($y * $height) + $yi);
						$ga["r"] += ($v >> 16) & 0xFF;
						$ga["g"] += ($v >> 8) & 0xFF;
						$ga["b"] += $v & 0xFF;
					}
				$ga["r"] /= ($width * $height);
				$ga["g"] /= ($width * $height);
				$ga["b"] /= ($width * $height);
				$this->map[$y][$x] = $ga;
			}
		}
	}
	
	public function difference($state, $function = null) {
		$map = array();
		for ($y = 0; $y < $this->resY; $y++) {
			$map[$y] = array();
			for ($x = 0; $x < $this->resX; $x++) {
				if (isset($function)) {
					$map[$y][$x] = $function($this->map[$y][$x], $state->map[$y][$x]);
				} else {
					$map[$y][$x] = $this->map[$y][$x] - $state->map[$y][$x];
				}
			}
		}
		$i = new State($this->resX, $this->resY);
		$i->map = $map;
		return $i;
	}
	
	public function avg() {
		if (empty($this->average)) {
			$this->average = 0;
			for ($y = 0; $y < $this->resY; $y++)
				for ($x = 0; $x < $this->resX; $x++)
					$this->average += $this->map[$y][$x];
			$this->average /= ($this->resX * $this->resY);
		}
		return $this->average;
	}
	
	public function max() {
		$this->maximum = 0;
		for ($y = 0; $y < $this->resY; $y++)
			for ($x = 0; $x < $this->resX; $x++)
				if ($this->map[$y][$x] > $this->maximum)
					$this->maximum = $this->map[$y][$x];
		return $this->maximum;
	}
	
	public function stdDev() {
		$this->standardDeviation = 0;
		$avg = $this->avg();
		for ($y = 0; $y < $this->resY; $y++) {
			for ($x = 0; $x < $this->resX; $x++) {
				$m = ($this->map[$y][$x] - $avg) * ($this->map[$y][$x] - $avg);
				$this->standardDeviation += $m;
			}
		}
		$this->standardDeviation /= (($this->resX * $this->resY)-1);
		$this->standardDeviation = sqrt($this->standardDeviation);
		return $this->standardDeviation;
	}
	
	public function denoiseStdDev() {
		$dev = $this->stdDev();
		for ($y = 0; $y < $this->resY; $y++)
			for ($x = 0; $x < $this->resX; $x++)
				if (abs($this->map[$y][$x]) < $dev)
					$this->map[$y][$x] = 0;
		return $this;
	}
	
	public function scale($top) {
		$max = $this->max(); 
		for ($y = 0; $y < $this->resY; $y++)
			for ($x = 0; $x < $this->resX; $x++)
				$this->map[$y][$x] = ($this->map[$y][$x] / $max) * $top;
		return $this;
	}
	
	public function round($round) {
		for ($y = 0; $y < $this->resY; $y++)
			for ($x = 0; $x < $this->resX; $x++)
				$this->map[$y][$x] = round($this->map[$y][$x], $round);
		return $this;
	}
	
	public function abs() {
		for ($y = 0; $y < $this->resY; $y++)
			for ($x = 0; $x < $this->resX; $x++)
				$this->map[$y][$x] = abs($this->map[$y][$x]);
		return $this;
	}
		
	public function toString() {
		$s = "";
		for ($y = 0; $y < $this->resY; $y++) {
			for ($x = 0; $x < $this->resX; $x++) {
				$s .= $this->map[$y][$x]."\t";
			}
			$s .= "\n";
		}
		return $s;
	}

	public function drawImageIndicator($image) {
		$max = $this->max(); 
		$i = $image->getImage();
		$width = $image->getWidth() / $this->resX;
		$height = $image->getHeight() / $this->resY;
		$black = imagecolorallocate($i, 0, 0, 0);
		for ($y = 0; $y < $this->resY; $y++) {
			for ($x = 0; $x < $this->resX; $x++) {
				$v = ($this->map[$y][$x] / $max) * 255;
				if ($v > 0) {
					$color = imagecolorallocate($i, $v, $v, $v);
					imagerectangle($i, $x * $width, $y * $height, $x * $width + $width - 1, $y * $height + $height - 1, $color);
					imagestring($i, 2, $x * $width + 3, $y * $height + 1, $this->map[$y][$x], $black);
					imagestring($i, 2, $x * $width + 1, $y * $height - 1, $this->map[$y][$x], $black);
					imagestring($i, 2, $x * $width + 2, $y * $height, $this->map[$y][$x], $color);
				}
			}
		}
		return new SimpleImage($i);
	}
	
	public function getBoundingBox($w = null, $h = null) {
		if (!isset($w)) $w = $this->resX;
		if (!isset($h)) $h = $this->resY;
		
		$ax = $this->resX;
		$bx = 0;
		$ay = $this->resX;
		$by = 0;
		for ($y = 0; $y < $this->resY; $y++) {
			for ($x = 0; $x < $this->resX; $x++) {
				if ($this->map[$y][$x] > 0) {
					if ($x > $bx) $bx = $x;
					if ($x < $ax) $ax = $x;
					if ($y > $by) $by = $y;
					if ($y < $ay) $ay = $y;
				}
			}
		}
		if ($ax > $bx) {
			return null;
		} else {
			$ax = ($ax / $this->resX) * $w;
			$bx = ((($bx+1) / $this->resX) * $w) - $ax;
			$ay = ($ay / $this->resY) * $h;
			$by = ((($by+1) / $this->resY) * $h) - $ay;
			return array("x" => $ax, "y" => $ay, "w" => $bx, "h" => $by);
		}
	}
	
	public function getCenterOfGravity($w = null, $h = null) {
		if (!isset($w)) $w = $this->resX;
		if (!isset($h)) $h = $this->resY;
		
		$box = $this->getBoundingBox();
		
		$tx = 0;
		$ty = 0;
		$m = 0;
		for ($y = 0; $y < $this->resY; $y++) {
			for ($x = 0; $x < $this->resX; $x++) {
				if ($this->map[$y][$x] > 0) {
					$m += $this->map[$y][$x];
					$tx += $this->map[$y][$x] * (($x+1) - $box["x"]);
					$ty += $this->map[$y][$x] * (($y+1) - $box["y"]);
				}
			}
		}
		$tx = (($tx / $m)-1) * ($w/$this->resX);
		$ty = (($ty / $m)-1) * ($h/$this->resY);
		$tx += ($w/$this->resX) * $box["x"] + (($w/$this->resX)/2);
		$ty += ($h/$this->resY) * $box["y"] + (($h/$this->resY)/2);
		return array("x" => $tx, "y" => $ty);
	}
	
}

function rgbColorDistance ($x, $y) {
	$r = $x["r"] - $y["r"];
	$r *= $r;
	$g = $x["g"] - $y["g"];
	$g *= $g;
	$b = $x["b"] - $y["b"];
	$b *= $b;
	$v = $r + $g + $b;
	return sqrt($v);
}



?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Image Analysis</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/business-casual.css" rel="stylesheet">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Josefin+Slab:100,300,400,600,700,100italic,300italic,400italic,600italic,700italic" rel="stylesheet" type="text/css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body STYLE="background-image: url('./img/background.jpg')">

    <div class="brand">Mycurlycode</div>
    <div class="address-bar">B-163, Additional Township, NTPC, BTPS, Badarpur, New Delhi-110044</div>

    <!-- Navigation -->
    <nav class="navbar navbar-default" role="navigation">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <!-- navbar-brand is hidden on larger screens, but visible when the menu is collapsed -->
                <a class="navbar-brand" href="index.html">Image Analysis</a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="index.php">Home</a>
                    </li>
                    <!-- <li>
                        <a href="about.html">About</a>
                    </li>-->
                    <li>
                        <a href="http://mycurlycode.blogspot.in">Blog</a>
                    </li>
                    <!--<li>
                        <a href="contact.html">Contact</a>
                    </li> -->
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

    <div class="container">

        

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                    <hr>
                    <h2 class="intro-text text-center">Enjoy your 
                        <strong>Frames</strong>
                    </h2>
                    <hr>
                    <img class="img-responsive img-border img-left" src="" alt="">
                    <hr class="visible-xs">
                    
					<?php
					echo '<center>';
					$dirname = "./frames/";
					$images = glob($dirname."*.png");

					foreach($images as $image) {
						echo '<img src="'.$image.'" />';
					}
					echo '</center>';
					?>
					
					
					
                </div>
            </div>
        </div>
		
		
		
		
		<div class="row">
            <div class="box">
                <div class="col-lg-12">
                    <hr>
                    <h2 class="intro-text text-center">Fill to merge and observe changes in these 
                        <strong>Frames</strong>
                    </h2>
                    <hr>
                    <img class="img-responsive img-border img-left" src="" alt="">
                    <hr class="visible-xs">
                    
					<center>
					<form method="post" action="#" name="form1" enctype="multipart/form-data">
						<fieldset>
						<legend>
						Name:<br> 
							<input type="text" name="name" required><br><br>
						Email:<br> 
							<input type="text" name="email" required><br><br>
						Upload any one image from the above frames:<br>
							<input name="MAX_FILE_SIZE" value="100000000000000"  type="hidden">
							<input type="file" name="file" id="file" required><br>
						Comment:<br>
							<input type="text" name="comment" size="50"><br><br>
							
						<input type="submit" name="submit2" value="Submit" /><br>
						
						
						</legend>
						</fieldset>
					</form>
					</center>
					
					
					
                </div>
            </div>
        </div>
		
		
		<div class="row">
            <div class="box">
                <div class="col-lg-12">
                    
					
					<hr>
                    <h2 class="intro-text text-center">Enjoy the changes wrt your uploaded Frame
                        <strong>uploaded Frame</strong>
                    </h2>
                    <hr>
                    <img class="img-responsive img-border img-left" src="" alt="">
                    <hr class="visible-xs">
                    
					<?php
					echo '<center>';
					$dirname = "./change_detected_images/";
					$images = glob($dirname."*.jpeg");

					foreach($images as $image) {
						echo '<img src="'.$image.'" />';
					}
					echo '</center>';
					?>
					
					
					<hr>
					<h2 class="intro-text text-center">Enjoy the merged image from the set of 
                        <strong>Frame</strong>
                    </h2>
					<hr>
					
					<?php
					echo '<center>';
					$dirname = "./merged_images/";
					$images = glob($dirname."*.jpeg");

					foreach($images as $image) {
						echo '<img src="'.$image.'" />';
					}
					echo '</center>';
					?>
					
                </div>
            </div>
        </div>

    </div>
    <!-- /.container -->

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <p>Copyright &copy; Mycurlycode 2017</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>

    <!-- Script to Activate the Carousel -->
    <script>
    $('.carousel').carousel({
        interval: 5000 //changes the speed
    })
    </script>

	
	
<?php

/////////////////////////////////////--Codes for various operations--////////////////////////////////////////////

/*------------ Code to merge images --------------*/
// get a list of images from a subdirectory
$imagePath = "./frames/";
$files = listFilesInDirectory($imagePath);

// setup an image to work with
$baseImage = new SimpleImage($imagePath.$files[0]);

// setup an array of all other images
$images = array();
for ($i = 1; $i < count($files); $i++)
 $images[] = new SimpleImage($imagePath.$files[$i]);

// merge the latter images into the first image.
$baseImage->merge($images);


$filename = "./merged_images/test.jpeg";
// saves it in a particular folder.
$baseImage->save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null);



/*---------- Code to detect changes --------------*/
if(isset($_POST["submit2"])){

	   $v1=rand(1111,9999);
	   $v2=rand(1111,9999);
   
       $v3=$v1.$v2;
   
       $v3=md5($v3);
	   
	   $fnm=$_FILES["file"]["name"];
	   $dst="./videos/".$v3.$fnm;
	   
	   move_uploaded_file($_FILES["file"]["tmp_name"],$dst);
      
$imgFile= $dst;
	
$i1 = new SimpleImage(''.$imgFile.'');

// $imagePath = "./frames/";
// $files = listFilesInDirectory($imagePath);
for ($i = 1; $i < count($files); $i++)
{
$i2 = new SimpleImage($imagePath.$files[$i]);
// setup the states that will work with and interpret the numbers
$state = new State(15, 8, $i1);
$state = $state->difference(new State(15, 8, $i2), rgbColorDistance);
$state->abs()->denoiseStdDev()->scale(10)->round(0);

// for purposes of visual debugging, merge the two images together
$i1->merge($i2);

// using the merged image, layer on a visual of state differences
$result = $state->drawImageIndicator($i1);

// $box will hold an array (x,y,w,h) that indicates location of change
$box = $state->getBoundingBox($i1->getWidth(), $i1->getHeight());
$color = imagecolorallocate($result->getImage(), 10, 255, 10);
imagerectangle($result->getImage(), $box["x"]-1, $box["y"]-1, 
 $box["x"]+$box["w"]+1, $box["y"]+$box["h"], 
 $color);

// $cog will hold an array (x,y) indicating center of change
$cog = $state->getCenterOfGravity($i1->getWidth(), $i1->getHeight());
imagearc($result->getImage(), 
 $cog["x"], $cog["y"], 7, 7,  0, 360, 
 imagecolorallocate($result->getImage(), 255, 255, 0));
imagearc($result->getImage(), 
 $cog["x"], $cog["y"], 9, 9,  0, 360, 
 imagecolorallocate($result->getImage(), 255, 0, 0));

// stream image to client
// $result->output();

// saves it in a particular folder.
$filename1 = "./change_detected_images/change.$i.jpeg";
$result->save($filename1, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null);
}
}

?>	
	
	
</body>

</html>

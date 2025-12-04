<?php
// Create a blank image
$width = 1200;
$height = 400;
$image = imagecreatetruecolor($width, $height);

// Set the background color (blue)
$blue = imagecolorallocate($image, 26, 115, 232);
imagefill($image, 0, 0, $blue);

// Add some text
$text_color = imagecolorallocate($image, 255, 255, 255);
$text = 'Silco Hero Background';
$font_size = 20;
$text_box = imagettfbbox($font_size, 0, 'arial.ttf', $text);
$text_width = $text_box[4] - $text_box[6];
$text_height = $text_box[3] - $text_box[5];
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2 + $text_height;

// Add the text to the image
imagettftext($image, $font_size, 0, $x, $y, $text_color, 'arial.ttf', $text);

// Ensure the directory exists
if (!file_exists('assets/img')) {
    mkdir('assets/img', 0777, true);
}

// Save the image
imagejpeg($image, 'assets/img/hero-bg.jpg', 90);

// Free up memory
imagedestroy($image);

echo "Hero background image created successfully!";
?>

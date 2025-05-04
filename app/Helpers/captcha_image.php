<?php

/**
 * Standalone script to generate and output a CAPTCHA image.
 *
 * This script generates a random CAPTCHA text, stores it in the session,
 * and creates a PNG image representation of the text with added noise
 * (lines, dots, arcs) to make it harder for bots to read.
 *
 * The generated image is directly outputted to the browser with a 'image/png' content type.
 * It relies on the GD library for image manipulation.
 *
 * Usage: Typically embedded in an <img> tag like <img src="/path/to/captcha_image.php">
 */

// Start the session to store/retrieve the CAPTCHA text.
session_start();

// Set the content type header to indicate an image is being sent.
header('Content-Type: image/png');

// Generate CAPTCHA text if it doesn't exist in the session.
if (!isset($_SESSION['captcha'])) {
    // Define the character set for the CAPTCHA text.
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $captcha_text = '';
    // Generate a 6-character random string.
    for ($i = 0; $i < 6; $i++) {
        $captcha_text .= $characters[rand(0, strlen($characters) - 1)];
    }
    // Store the generated text in the session.
    $_SESSION['captcha'] = $captcha_text;
}

// Retrieve the CAPTCHA text from the session.
$captcha_text = $_SESSION['captcha'];

// Define image dimensions.
$image_width = 200;
$image_height = 50;

// Create a true color image resource.
$image = imagecreatetruecolor($image_width, $image_height);

// Define colors for the image elements.
$background_color = imagecolorallocate($image, 240, 240, 240); // Light gray background
$text_color = imagecolorallocate($image, 40, 40, 40);         // Dark gray for initial text (will be varied)
$noise_color = imagecolorallocate($image, 100, 120, 180);    // Bluish-gray for noise elements

// Fill the image background.
imagefill($image, 0, 0, $background_color);

// Add random noise pixels (dots).
for ($i = 0; $i < 400; $i++) {
    imagesetpixel($image, rand(0, $image_width), rand(0, $image_height), $noise_color);
}

// Add random noise lines.
for ($i = 0; $i < 5; $i++) {
    imageline(
        $image,
        rand(0, $image_width), // x1
        rand(0, $image_height), // y1
        rand(0, $image_width), // x2
        rand(0, $image_height), // y2
        $noise_color
    );
}

// Add random noise arcs.
for ($i = 0; $i < 3; $i++) {
    imagearc(
        $image,
        rand(0, $image_width),  // center x
        rand(0, $image_height), // center y
        rand(10, 30),           // width
        rand(10, 30),           // height
        0,                      // start angle
        360,                    // end angle
        $noise_color
    );
}

// Define font size (using built-in GD font).
$font_size = 5; // Larger number means larger font

// Calculate text dimensions to center it.
$text_width = imagefontwidth($font_size) * strlen($captcha_text);
$text_height = imagefontheight($font_size);

// Calculate starting position (x, y) for the text to be centered.
$text_x = ($image_width - $text_width) / 2;
$text_y = ($image_height - $text_height) / 2;

// Initialize x-coordinate for drawing characters.
$x = $text_x;

// Draw each character of the CAPTCHA text onto the image.
for ($i = 0; $i < strlen($captcha_text); $i++) {
    $char = $captcha_text[$i];
    // Add slight random vertical variation to each character's position.
    $y = $text_y + rand(-5, 5);
    // Add random color variation to each character.
    $r = 40 + rand(0, 30); // Red component
    $g = 40 + rand(0, 30); // Green component
    $b = 40 + rand(0, 30); // Blue component
    $char_text_color = imagecolorallocate($image, $r, $g, $b);
    // Draw the character.
    imagechar($image, $font_size, (int)$x, (int)$y, $char, $char_text_color);
    // Increment x-position for the next character, adding slight random spacing variation.
    $x += imagefontwidth($font_size) + rand(-2, 2);
}

// Output the final image as a PNG.
imagepng($image);

// Free up memory associated with the image resource.
imagedestroy($image);

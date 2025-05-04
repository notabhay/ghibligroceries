<?php

namespace App\Helpers;

use App\Core\Session;

/**
 * Class CaptchaHelper
 *
 * Provides functionality for generating, storing, validating CAPTCHA challenges,
 * and creating corresponding CAPTCHA images. Relies on the Session component
 * for storing the CAPTCHA text and the GD library for image generation.
 */
class CaptchaHelper
{
    /**
     * @var Session The session management object.
     */
    private $session;

    /**
     * @var string The key used to store the CAPTCHA text in the session.
     */
    private $sessionKey = 'captcha';

    /**
     * @var int The width of the generated CAPTCHA image in pixels.
     */
    private $width = 200;

    /**
     * @var int The height of the generated CAPTCHA image in pixels.
     */
    private $height = 50;

    /**
     * Constructor for CaptchaHelper.
     *
     * Initializes the helper with a session object and optional configuration.
     * Checks if the required GD library extension is loaded.
     *
     * @param Session $session The session management object.
     * @param array $options Optional configuration array. Can include 'session_key', 'width', 'height'.
     * @throws \RuntimeException If the GD library extension is not loaded.
     */
    public function __construct(Session $session, array $options = [])
    {
        $this->session = $session;
        // Override default properties if provided in options.
        $this->sessionKey = $options['session_key'] ?? $this->sessionKey;
        $this->width = $options['width'] ?? $this->width;
        $this->height = $options['height'] ?? $this->height;

        // Ensure the GD extension is available for image functions.
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('GD library extension is required for CaptchaHelper.');
        }
    }

    /**
     * Generates a random alphanumeric CAPTCHA text string.
     *
     * Uses cryptographically secure random number generation if available (random_int).
     *
     * @param int $length The desired length of the CAPTCHA text. Defaults to 6.
     * @return string The generated random CAPTCHA text.
     */
    public function generateText(int $length = 6): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $captcha_text = '';
        $charLength = strlen($characters);
        for ($i = 0; $i < $length; $i++) {
            // Use random_int for better randomness if available.
            $captcha_text .= $characters[random_int(0, $charLength - 1)];
        }
        return $captcha_text;
    }

    /**
     * Stores the generated CAPTCHA text (lowercase) in the session.
     *
     * @param string $text The CAPTCHA text to store.
     * @return void
     */
    public function storeText(string $text): void
    {
        // Store in lowercase for case-insensitive comparison during validation.
        $this->session->set($this->sessionKey, strtolower($text));
    }

    /**
     * Retrieves the stored CAPTCHA text from the session.
     *
     * @return string|null The stored CAPTCHA text (lowercase), or null if not set.
     */
    public function getText(): ?string
    {
        return $this->session->get($this->sessionKey);
    }

    /**
     * Validates the submitted CAPTCHA text against the one stored in the session.
     *
     * Comparison is case-insensitive.
     *
     * @param string|null $submittedText The text submitted by the user.
     * @return bool True if the submitted text matches the stored text, false otherwise.
     */
    public function validate(?string $submittedText): bool
    {
        $storedText = $this->getText();
        // Fail if either the submitted or stored text is missing.
        if ($submittedText === null || $storedText === null) {
            return false;
        }
        // Perform case-insensitive comparison.
        return strtolower($submittedText) === $storedText;
    }

    /**
     * Generates the PNG image data for the given CAPTCHA text.
     *
     * Creates an image with background color, noise (pixels, lines), and the
     * distorted text characters. Attempts to use a TTF font if available,
     * otherwise falls back to a built-in GD font.
     *
     * @param string $text The CAPTCHA text to render in the image.
     * @return string|false The raw PNG image data as a string, or false on failure (e.g., image creation failed).
     */
    public function generateImageData(string $text): string|false
    {
        // Create the base image canvas.
        $image = imagecreatetruecolor($this->width, $this->height);
        if (!$image) {
            // Failed to create image resource.
            error_log("CaptchaHelper: Failed to create image resource."); // Added logging
            return false;
        }

        // Allocate colors.
        $background_color = imagecolorallocate($image, 240, 240, 240); // Light gray
        $noise_color = imagecolorallocate($image, 180, 180, 180);      // Medium gray for noise

        // Fill the background.
        imagefill($image, 0, 0, $background_color);

        // Add noise: random pixels.
        $pixel_count = ($this->width * $this->height) / 5; // Adjust density as needed
        for ($i = 0; $i < $pixel_count; $i++) {
            imagesetpixel($image, random_int(0, $this->width - 1), random_int(0, $this->height - 1), $noise_color);
        }

        // Add noise: random lines.
        for ($i = 0; $i < 5; $i++) {
            imageline(
                $image,
                random_int(0, $this->width),
                random_int(0, $this->height), // Point 1 (x1, y1)
                random_int(0, $this->width),
                random_int(0, $this->height), // Point 2 (x2, y2)
                $noise_color
            );
        }

        // --- Text Rendering ---
        $font_size = 5; // Base size for built-in font, scale factor for TTF
        $text_width = 0;
        $text_height = 0;
        // Define path relative to this file's directory. Ensure 'fonts/arial.ttf' exists.
        $ttf_font = __DIR__ . '/fonts/arial.ttf';

        // Check if TTF functions and the font file exist.
        $use_ttf = function_exists('imagettfbbox') && function_exists('imagettftext') && file_exists($ttf_font);

        if ($use_ttf) {
            // Try to calculate bounding box for the text using TTF.
            // Use error suppression (@) as imagettfbbox can sometimes warn on invalid fonts.
            $text_box = @imagettfbbox($font_size * 5, 0, $ttf_font, $text);
            if ($text_box) {
                // Calculate actual width and height from the bounding box coordinates.
                $text_width = abs($text_box[4] - $text_box[0]); // lower right x - lower left x
                $text_height = abs($text_box[5] - $text_box[1]); // lower left y - upper left y
            } else {
                // TTF calculation failed, log error and disable TTF for rendering.
                error_log("CaptchaHelper: imagettfbbox failed for font: " . $ttf_font);
                $use_ttf = false;
            }
        }

        // Fallback or if TTF calculation failed: use built-in GD font dimensions.
        if ($text_width === 0) {
            $font_size = 5; // Ensure correct size for built-in font
            $text_width = imagefontwidth($font_size) * strlen($text);
            $text_height = imagefontheight($font_size);
            $use_ttf = false; // Ensure TTF is not used for rendering if we fell back here.
        }

        // Calculate starting position to center the text block.
        $start_x = ($this->width - $text_width) / 2;
        $start_y = ($this->height - $text_height) / 2;

        // Ensure starting x is not negative if text is wider than image.
        $x = $start_x > 0 ? $start_x : 5; // Use a small margin if text overflows

        // Draw each character individually with variations.
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            // Random vertical position variation.
            $y = $start_y + random_int(-5, 5);
            // Random angle variation (only effective with TTF).
            $angle = $use_ttf ? random_int(-15, 15) : 0;
            // Random dark color for the character.
            $r = random_int(0, 100);
            $g = random_int(0, 100);
            $b = random_int(0, 100);
            $char_color = imagecolorallocate($image, $r, $g, $b);

            // Use imagettftext if enabled, otherwise fallback to imagechar.
            if ($use_ttf) {
                // Adjust y-position slightly for TTF baseline alignment. Needs tuning based on font.
                $ttf_y = $y + $text_height * 0.75; // Approximate baseline adjustment
                @imagettftext($image, $font_size * 5, $angle, (int) $x, (int) $ttf_y, $char_color, $ttf_font, $char);
                // Estimate character width for TTF (less precise than bbox but needed for spacing)
                // Calculate width of this specific character using TTF if possible
                $char_bbox = @imagettfbbox($font_size * 5, 0, $ttf_font, $char);
                $char_width = $char_bbox ? abs($char_bbox[4] - $char_bbox[0]) : imagefontwidth($font_size) * 2; // Fallback width estimate
            } else {
                // Use built-in font rendering.
                imagechar($image, $font_size, (int) $x, (int) $y, $char, $char_color);
                $char_width = imagefontwidth($font_size);
            }

            // Advance x-position for the next character with slight random spacing.
            $x += $char_width + random_int(1, 3);
        }

        // Capture the image output into a variable instead of sending directly.
        ob_start();
        imagepng($image); // Output PNG data to buffer
        $imageData = ob_get_clean(); // Get the buffer contents and clean buffer.

        // Free up memory.
        imagedestroy($image);

        return $imageData; // Return the raw PNG data.
    }
}

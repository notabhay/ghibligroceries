<?php

namespace App\Controllers;

use App\Core\Session;
use App\Helpers\CaptchaHelper;
use App\Core\BaseController;
use App\Core\Registry;
use Psr\Log\LoggerInterface; // Added for potential logging

/**
 * Class CaptchaController
 * Handles the generation and output of CAPTCHA images.
 * It uses the CaptchaHelper to create the image data and stores the expected text in the session.
 *
 * @package App\Controllers
 */
class CaptchaController extends BaseController
{
    /**
     * @var Session Session management instance. Used to store the CAPTCHA text.
     */
    private $session;

    /**
     * @var CaptchaHelper Helper class for generating CAPTCHA text and image data.
     */
    private $captchaHelper;

    /**
     * @var LoggerInterface Logger instance for recording errors.
     */
    private $logger;

    /**
     * CaptchaController constructor.
     * Injects Session and CaptchaHelper dependencies.
     *
     * @param Session $session The session management object.
     * @param CaptchaHelper $captchaHelper The helper for CAPTCHA generation.
     */
    public function __construct(Session $session, CaptchaHelper $captchaHelper)
    {
        $this->session = $session;
        $this->captchaHelper = $captchaHelper;
        // Attempt to get logger from registry, handle if not available
        if (Registry::has('logger')) {
            $this->logger = Registry::get('logger');
        }
    }

    /**
     * Generates a CAPTCHA image and outputs it directly to the browser.
     * Generates random text, stores it in the session, creates the image data,
     * and sets appropriate headers before echoing the image data.
     * Includes error handling for image generation failures.
     *
     * @return void Outputs image data or an error message.
     */
    public function generate()
    {
        try {
            // Ensure session is active before storing CAPTCHA text
            if (!$this->session->isActive()) {
                $this->session->start();
            }

            // Generate random text for the CAPTCHA
            $text = $this->captchaHelper->generateText();
            // Store the generated text in the session for later validation
            $this->captchaHelper->storeText($text);

            // Generate the image data based on the text
            $imageData = $this->captchaHelper->generateImageData($text);

            // Check if image generation was successful
            if ($imageData === false) {
                $this->logError('Failed to generate CAPTCHA image data.');
                $this->outputError('Failed to generate CAPTCHA image.');
                return;
            }

            // Set headers for PNG image output and prevent caching
            header('Content-Type: image/png');
            header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
            header('Pragma: no-cache'); // HTTP 1.0.
            header('Expires: 0'); // Proxies.

            // Output the raw image data
            echo $imageData;
        } catch (\Exception $e) {
            // Log any unexpected exceptions during CAPTCHA generation
            $this->logError("Captcha Generation Error: " . $e->getMessage(), ['exception' => $e]);
            // Output a generic error message to the user
            $this->outputError('An error occurred while generating the CAPTCHA.');
        }
    }

    /**
     * Outputs an error message with appropriate headers.
     * Used when CAPTCHA generation fails. Sets a 500 status code.
     *
     * @param string $message The error message to display.
     * @return void
     */
    private function outputError(string $message): void
    {
        // Only set headers if they haven't been sent already
        if (!headers_sent()) {
            // Set plain text content type and 500 Internal Server Error status
            header('Content-Type: text/plain', true, 500);
        }
        // Output the error message, ensuring it's safe for HTML context
        echo "Error: " . htmlspecialchars($message);
    }

    /**
     * Logs an error message using the registered logger, if available.
     *
     * @param string $message The error message.
     * @param array $context Optional context data for the log entry.
     * @return void
     */
    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error($message, $context);
        } else {
            // Fallback if logger isn't available (e.g., log to PHP error log)
            error_log("CaptchaController Error: " . $message . " Context: " . print_r($context, true));
        }
    }
}

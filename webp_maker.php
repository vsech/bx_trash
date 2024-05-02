<?php
use Bitrix\Main\Web\HttpClient;

define('CWEBP_PATH', 'cwebp');

class MakeWebP {
    protected $originalFilePath;
    protected $convertedFilePath;
    protected $httpClient;

    /**
     * Class constructor.
     *
     * @param string $originalFilePath Path to the original image.
     */
    public function __construct($originalFilePath) {
        $this->originalFilePath = $originalFilePath;
        $this->httpClient = new HttpClient();
    }

    /**
     * Convert image to WebP format.
     *
     * This method uses `cwebp` program to convert image to WebP format. The converted image will
     * be stored in the same directory as original image with `.webp` extension.
     *
     * @return string Path to the converted image.
     */
    public function convertToWebP() {
        $this->convertedFilePath = $this->originalFilePath . '.webp';
        $command = CWEBP_PATH . ' -q 80 ' . $this->originalFilePath . ' -o ' . $this->convertedFilePath;
        exec($command);

        return $this->convertedFilePath;
    }

    /**
     * Returns converted image or original image if conversion failed.
     *
     * @return string Image data as a string.
     */
    public function getImage() {
        // If converted file exists, return it. Otherwise return original image.
        if (file_exists($this->convertedFilePath)) {
            return file_get_contents($this->convertedFilePath);
        } else {
            return file_get_contents($this->originalFilePath);
        }
    }
}

$originalFilePath = urldecode($_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI']);

$webpConverter = new MakeWebP($originalFilePath);
$convertedFilePath = $webpConverter->convertToWebP();

header("content-type: image/webp");
echo $webpConverter->getImage();

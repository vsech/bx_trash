<?php
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BackupToNextcloud
{
    /**
     * Class constructor.
     * Registers the handler function as an event handler for the OnEpilog event of the main component.
     */
    public function __construct()
    {
        // Registers the handler function as an event handler for the OnEpilog event of the main component.
        \Bitrix\Main\EventManager::getInstance()->addEventHandler(
            "main", // Component name
            "OnEpilog", // Event name
            array($this, "onEpilogHandler") // Handler function
        );
    }


    /**
     * Handler function for the OnEpilog event of the main component.
     * Creates a Bitrix backup and sends it to Nextcloud based on the
     * specified settings.
     */
    public function onEpilogHandler()
    {
        // Read Nextcloud settings from the options table
        $nextcloudSettings = array(
            "url" => Option::get("backup_to_nextcloud", "nextcloud_url"),
            "username" => Option::get("backup_to_nextcloud", "nextcloud_username"),
            "password" => Option::get("backup_to_nextcloud", "nextcloud_password"),
            "directory" => Option::get("backup_to_nextcloud", "nextcloud_directory"),
        );

        // Define the backup file path and name
        $backupPath = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/backup/";
        $backupName = "backup_" . date("Y-m-d_H-i-s") . ".zip";
        $backupFile = $backupPath . $backupName;

        // Create a Bitrix backup
        $this->createBitrixBackup($backupFile);

        // Send the backup to Nextcloud
        $this->sendToNextcloud($backupFile, $nextcloudSettings);
    }


    /**
     * Создает резервную копию Битрикса.
     *
     * @param string $backupFile Путь к создаваемому архиву с резервной копией.
     */
    private function createBitrixBackup(string $backupFile): void
    {
        global $APPLICATION;
        // Игнорируем все исключения, которые могут возникнуть во время резервного копирования.
        $APPLICATION->GetException();
        /**
         * Объект, который создает резервную копию Битрикса.
         * @var \Bitrix\Main\Backup\Manager $backupManager
         */
        $backupManager = \Bitrix\Main\Backup\Manager::getInstance();
        // Инициируем процесс создания резервной копии.
        $backupManager->initiateBackup($backupFile);
    }


    /**
     * Sends the backup file to Nextcloud.
     *
     * @param string $backupFile Path to the backup file.
     * @param array $nextcloudSettings Array with Nextcloud settings.
     */
    private function sendToNextcloud(string $backupFile, array $nextcloudSettings): void
    {
        // Build the URL for the Nextcloud server
        $url = $nextcloudSettings["url"] . "/remote.php/dav/files/"
            . $nextcloudSettings["username"] . "/"
            . $nextcloudSettings["directory"] . "/"
            . basename($backupFile);

        // Encode username and password for Basic Auth
        $auth = base64_encode($nextcloudSettings["username"] . ":" . $nextcloudSettings["password"]);

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Set HTTP headers for the request
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/zip", // Set the content type to ZIP
            "Authorization: Basic $auth", // Set the Basic Auth header
        ]);

        // Set the PUT request method, set the file to upload and disable progress meter
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, fopen($backupFile, "r"));
        curl_setopt($ch, CURLOPT_NOPROGRESS, true);

        // Set a low timeout for the request
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        // Execute the cURL request and close the handle
        curl_exec($ch);
        curl_close($ch);
    }

}

$backupToNextcloud = new BackupToNextcloud();

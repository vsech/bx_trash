<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\IO;
use Bitrix\Main\Config\Option;

class FileChangeEventHandler
{
    /**
     * Register event handlers for file change events.
     *
     * This function registers handlers for the following events:
     * - OnAfterFileAdd
     * - OnAfterFileUpdate
     * - OnAfterFileDelete
     *
     * The handlers are static methods of this class.
     *
     * @return void
     */
    public static function registerHandlers()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->addEventHandler(
            'main',
            'OnAfterFileAdd',
            array(__CLASS__, 'onFileChange'), // Call `onFileChange` static method
            /* priority */ null,
            /* handlerName */ null,
            /* moduleId */ null,
            /* modulePath */ null,
            /* description */ 'Handler for file add event'
        );

        $eventManager->addEventHandler(
            'main',
            'OnAfterFileUpdate',
            array(__CLASS__, 'onFileChange'), // Call `onFileChange` static method
            /* priority */ null,
            /* handlerName */ null,
            /* moduleId */ null,
            /* modulePath */ null,
            /* description */ 'Handler for file update event'
        );

        $eventManager->addEventHandler(
            'main',
            'OnAfterFileDelete',
            array(__CLASS__, 'onFileChange'), // Call `onFileChange` static method
            /* priority */ null,
            /* handlerName */ null,
            /* moduleId */ null,
            /* modulePath */ null,
            /* description */ 'Handler for file delete event'
        );
    }


    /**
     * Handler for file change event.
     *
     * This handler is called when a file is added, updated or deleted.
     * The handler sends notifications to email and Telegram channel.
     *
     * @param IO\Event $event Event object with information about changed file.
     *
     * @return void
     */
    public static function onFileChange(IO\Event $event)
    {
        // Get information about changed file
        $file = $event->getParameter('FILE');
        $filePath = $file->getPath();

        // Send email notification
        self::sendEmailNotification($filePath);

        // Send notification to Telegram channel
        self::sendTelegramNotification($filePath);
    }

    /**
     * Send notification about file change to email.
     *
     * @param string $filePath Path to the changed file.
     * @return void
     */
    public static function sendEmailNotification($filePath)
    {
        // Build email notification message
        $subject = 'Изменение файла'; // email subject
        $message = "Файл $filePath был изменен."; // email message
        $emailTo = Option::get('main', 'email_from'); // email recipient

        Event::send(array(
            'EVENT_NAME' => 'FILE_CHANGE_NOTIFICATION', // event name for sending email
            'LID' => SITE_ID, // site ID for sending email
            'C_FIELDS' => array( // fields for sending email
                'EMAIL_TO' => $emailTo, // email recipient
                'SUBJECT' => $subject, // email subject
                'MESSAGE' => $message, // email message
            ),
        ));
    }

    /**
     * Send notification about file change to Telegram channel.
     *
     * @param string $filePath Path to the changed file.
     * @return void
     */
    public static function sendTelegramNotification($filePath)
    {
        // Read bot token and chat ID from options
        static $botToken = null;
        static $chatId = null;
        if ($botToken === null || $chatId === null) {
            $botToken = Option::get('main', 'telegram_bot_token');
            $chatId = Option::get('main', 'telegram_chat_id');
        }
        // Check if bot token and chat ID are set
        if ($botToken && $chatId) {
            // Build message URL
            $url = 'https://api.telegram.org/bot' . $botToken . '/sendMessage?chat_id=' . $chatId . '&text=' . urlencode("Файл $filePath был изменен.");
            // Send notification message
            file_get_contents($url);
        }
    }


}

FileChangeEventHandler::registerHandlers();

<?php
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PriceLog
{
    protected static $logTable = 'price_log';

    /**
     * Writes a log message to the database when a price changes.
     *
     * @param int $productId Product ID.
     * @param float $oldPrice Old price.
     * @param float $newPrice New price.
     */
    public static function logPriceEdit($productId, $oldPrice, $newPrice)
    {
        $userId = $GLOBALS['USER']->GetID();
        $logMessage = Loc::getMessage(
            'PRICE_CHANGE_LOG_MESSAGE',
            [
                '#USER_ID#' => $userId,
                '#PRODUCT_ID#' => $productId,
                '#OLD_PRICE#' => $oldPrice,
                '#NEW_PRICE#' => $newPrice,
            ]
        );
        self::writeToLog($logMessage);
    }


    /**
     * Writes a message to the log table.
     *
     * @param string $message Log message.
     */
    protected static function writeToLog($message)
    {
        /**
         * @var \Bitrix\Main\DB\Connection $connection Database connection.
         */
        $connection = Application::getConnection();
        $logData = [
            'USER_ID' => $GLOBALS['USER']->GetID(),
            'LOG_MESSAGE' => $message,
            'LOG_DATE' => new \Bitrix\Main\Type\DateTime(),
        ];

        /**
         * Adds a new log entry to the database.
         *
         * @param string $tableName Table name.
         * @param array $data Data to insert.
         *
         * @throws ArgumentException
         */
        $connection->add(self::$logTable, $logData);
    }


    /**
     * Rotates logs for a specific user.
     *
     * @param int $userId User ID
     *
     * @throws ArgumentException
     */
    public static function rotateLogs($userId)
    {
        $connection = Application::getConnection();
        $dateTime = new \Bitrix\Main\Type\DateTime();
        $dateFormatted = $dateTime->format('Ymd');

        /**
         * Entity Query to select the last 10 log messages for a specific user.
         */
        $query = new Entity\Query(self::$logTable);
        $query->setSelect(['LOG_MESSAGE']);
        $query->setFilter(['USER_ID' => $userId]);
        $query->setOrder(['LOG_DATE' => 'DESC']);
        $query->setLimit(10); // Change the limit as needed

        /**
         * Execute the query and fetch the result set.
         * @var Entity\Result $result
         */
        $result = $query->exec();

        /**
         * Fetch all log messages from the result set.
         * @var array $logs
         */
        $logs = $result->fetchAll();

        /**
         * If there are any log messages, write them into a file
         * with the format <user_id>_<date>.txt
         */
        if (!empty($logs)) {
            $logFile = $_SERVER['DOCUMENT_ROOT'] . "/logs/{$userId}_{$dateFormatted}.txt";
            file_put_contents($logFile, implode("\n", $logs));
        }
    }

}

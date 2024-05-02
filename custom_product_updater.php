<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Context;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Loader;
use Bitrix\Catalog\Product\CatalogProduct;
use Bitrix\Catalog\PriceTable;

Loader::includeModule("iblock");
Loader::includeModule("catalog");
Loader::includeModule("sale");

/**
 * Decode JSON data from POST request body
 *
 * @return array Decoded JSON data
 */
function getPostData()
{
    /**
     * Get JSON data from request body
     * @var string $postData
     */
    $postData = file_get_contents('php://input');
    /**
     * Decode JSON data and return result as array
     * @var array $result
     */
    return json_decode($postData, true);
}


$request = Context::getCurrent()->getRequest();
if ($request->isPost()) {
    $apiKey = $request->getHeader('x-api-key');
    $user = \Bitrix\Main\UserTable::getList([
        ...
    ])->fetch();

    if ($apiKey === $user['API_KEY']) {
        $jsonData = getPostData();
        $result = [];

        foreach ($jsonData as $item) {
            $xmlId = $item['КодВ1С'];
            $article = $item['Артикул'];
            $selPrice = $item['Цена'];
            $newQuantity = $item['Остаток'];

            $dbElement = \Bitrix\Iblock\ElementTable::getList([
                'filter' => ['IBLOCK_ID' => 0, 'XML_ID' => $xmlId],
                'select' => ['ID', 'CATALOG_QUANTITY'],
            ]);

            $result[$xmlId]['product'] = false;

            while ($element = $dbElement->fetch()) {
                $result[$xmlId]['product'] = true;
                $fields['QUANTITY'] = $newQuantity;

                if ($element['CATALOG_QUANTITY'] === null) {
                    $resQ = CatalogProduct::add(['ID' => $element['ID'], 'QUANTITY' => $fields['QUANTITY']], false);
                } elseif ($fields['QUANTITY'] != $element['CATALOG_QUANTITY']) {
                    $resQ = CatalogProduct::update($element['ID'], ['QUANTITY' => $fields['QUANTITY']]);
                }

                $result[$xmlId]['quantity'] = $resQ;

                if ($selPrice == 0) {
                    continue;
                }

                $fieldsPrice = [
                    "PRODUCT_ID" => $element['ID'],
                    "CATALOG_GROUP_ID" => 1,
                    "PRICE" => $selPrice,
                    "CURRENCY" => "RUB",
                    "QUANTITY_FROM" => false,
                    "QUANTITY_TO" => false
                ];

                $resPrc = PriceTable::getList([
                    'filter' => ["PRODUCT_ID" => $element['ID'], "CATALOG_GROUP_ID" => 1]
                ]);

                if ($price = $resPrc->fetch()) {
                    $resP = PriceTable::update($price["ID"], $fieldsPrice);
                } else {
                    $resP = PriceTable::add($fieldsPrice);
                }

                $result[$xmlId]['price'] = $resP;
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'detail' => $result]);
    } else {
        header('HTTP/1.1 401 Unauthorized');
        echo 'Unauthorized';
    }
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo 'Method Not Allowed';
}

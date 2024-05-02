<?php
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;

class JsonHandler {
    /**
     * Decode JSON data from string
     *
     * @param string $response JSON string to decode
     *
     * @return array|null Decoded JSON data or NULL if string is not valid JSON
     */
    public static function decode($response) {
        return Json::decode($response, true);
    }

    /**
     * Encode data to JSON string
     *
     * @param mixed $response Data to encode
     *
     * @return string JSON encoded string
     */
    public static function encode($response) {
        // Encode data to JSON string
        //
        // @param mixed $response Data to encode
        //
        // @return string JSON encoded string
        return Json::encode(
            $response,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
        );
    }

}



class CategoryHandler {
    protected $connection;

    /**
     * CategoryHandler constructor.
     *
     * Initialize CategoryHandler with Bitrix\Main\Application connection
     */
    public function __construct() {
        $this->connection = Application::getConnection();
    }

    /**
     * Create multidimensional array of categories where key is parent category ID
     * and value is array of child categories.
     *
     * @param array $mess Array of categories where each element has 'parentId' key
     *
     * @return array|false Multidimensional array of categories or false if $mess is not array
     */
    public function formTree(array $mess) {
        if (!is_array($mess)) {
            return false;
        }
        $tree = [];
        foreach ($mess as $value) {
            // Add child category to parent category if current category has parent category ID
            // @var int $value['parentId']
            if (isset($value['parentId'])) {
                $tree[$value['parentId']][] = $value;
            }
        }
        return $tree;
    }

    /**
     * List categories in a flat array.
     *
     * @param array $arr Multidimensional array of categories where each element has 'sourceId' and 'name' keys
     *
     * @return array Flat array with categories where key is sourceId and value is name
     */
    public function listCategories(array $arr) {
        $category = [];
        $name = [];
        // Walk through each element of the $arr recursively
        array_walk_recursive($arr, function ($item, $key) use (&$category, &$name) {
            // Save category ID to array if current element has 'sourceId' key
            if ($key === 'sourceId') {
                $category[] = $item;
            }
            // Save category name to array if current element has 'name' key
            if ($key === 'name') {
                $name[] = $item;
            }
        });
        // Combine categories IDs and names into a single array where key is category ID and value is name
        $listSect = array_combine($category, $name);
        return $listSect;
    }

    /**
     * Build HTML tree recursively from array of categories.
     *
     * @param array $cats Array of categories where each element has 'parentId' key
     *
     * @return string|false HTML tree as string or false if there are no child categories
     */
    public function buildTreeRrc($cats)
    {
        if (is_array($cats)) {
            $tree = '<ul>';
            foreach ($cats as $cat) {
                // Build HTML list item for each category
                $tree .= '<li id="' . $cat['sourceId'] . '">' . $cat['name'] . ' (' . $cat['sourceId'] . ')';
                // Recursively build HTML tree for child categories
                $tree .= $this->buildTreeRrc($cat['parentId']);
                $tree .= '</li>';
            }
            $tree .= '</ul>';
        } else {
            // Return false if there are no child categories
            return false;
        }
        return $tree;
    }

    /**
     * Build HTML tree from multidimensional array of categories
     *
     * @param array $cats Array of categories where key is parent category ID
     *                    and value is array of child categories
     * @param int $parent_id ID of parent category to build tree for
     *
     * @return string|false HTML tree as string or false if there are no child categories
     */
    public function buildTree(array $cats, int $parent_id)
    {
        if (isset($cats[$parent_id]) && is_array($cats[$parent_id])) {
            $tree = '<ul>';
            foreach ($cats[$parent_id] as $cat) {
                $tree .= '<li id="' . $cat['sourceId'] . '">'
                       . $cat['name'] . ' (' . $cat['sourceId'] . ')';
                $tree .= $this->buildTree($cats, $cat['sourceId']);
                $tree .= '</li>';
            }
            $tree .= '</ul>';
        } else {
            return false;
        }
        return $tree;
    }

    /**
     * Save array as JSON file in the database
     *
     * @param string $name Name of JSON file
     * @param array $arr Array to save as JSON
     */
    public function fileWrite($name, $arr) {
        // Encode array as JSON string
        $jsonArr = JsonHandler::encode($arr);
        // Save JSON string to the database
        $this->connection->queryExecute("INSERT INTO json_files (name, content) VALUES ('$name', '$jsonArr')");
    }

    /**
     * Get array from JSON file
     *
     * @param string $name Name of JSON file
     *
     * @return array Decoded JSON data or empty array if file does not exist
     */
    public function fileArr($name) {
        $record = $this->connection->query("SELECT content FROM json_files WHERE name = '$name'")->fetch();
        if ($record) {
            /**
             * Decode JSON data and return result as array
             * @var array $result
             */
            return JsonHandler::decode($record['content']);
        }
        /**
         * Return empty array if file does not exist
         */
        return [];
    }

    /**
     * Search quantity of item in array by item ID
     *
     * @param string $id Item ID to search
     * @param array $array Array of items where to search
     *
     * @return int|null Quantity of item or NULL if item is not found
     */
    public function searchQtyForId($id, $array) {
        foreach ($array as $key => $val) {
            /**
             * Check item ID
             * @var string $val['itemId']
             */
            if ($val['itemId'] === $id) {
                /**
                 * Return quantity of found item
                 * @var int $val['qty']
                 */
                return intval($val['qty']);
            }
        }
        /**
         * Return NULL if item is not found
         */
        return null;
    }

}
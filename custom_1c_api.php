<?php
use Bitrix\Main\Web\HttpClient;

class unrelnoAPI {
    private $username;
    private $password;
    private $userAgent;
    private $apiUrl;

    /**
     * UnrelnoAPI constructor
     *
     * @description
     * Set up connection credentials and base API URL
     */
    public function __construct() {
        $this->username = 'client_username';
        /**
         * Unrelno API client username
         * @var string
         */
        $this->password = 'client_password';
        /**
         * Unrelno API client password
         * @var string
         */
        $this->userAgent = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.2309.372 Safari/537.36';
        /**
         * User-Agent header for HTTP requests
         * @var string
         */
        $this->apiUrl = 'https://app.unrelno.group/unrelnoGroupBase/hs/';
        /**
         * Base URL of Unrelno API
         * @var string
         */
    }

    /**
     * Send request to Unrelno API
     * @param string $method API method
     * @param array $params request parameters
     * @param string $httpMethod HTTP method (GET or POST)
     * @return mixed response from Unrelno API
     */
    private function sendRequest($method, $params = [], $httpMethod = 'GET') {
        $httpClient = new HttpClient();
        // Set User-Agent header
        $httpClient->setHeader('User-Agent', $this->userAgent);
        // Set Basic Auth headers
        $httpClient->setAuthorization($this->username, $this->password);

        if ($httpMethod === 'GET') {
            // Build URL with query parameters
            $url = $this->apiUrl . $method . '?' . http_build_query($params);
            // Send GET request
            $response = $httpClient->get($url);
        } else {
            // Send POST request
            $url = $this->apiUrl . $method;
            $response = $httpClient->post($url, $params);
        }

        return $response;
    }


    /**
     * Get list of nomenclature items in Unrelno API
     * @return mixed response from Unrelno API
     */
    public function getNomenclature() {
        // Get list of nomenclature items in Unrelno API
        // @return mixed response from Unrelno API
        return $this->sendRequest('Nomenclature/list');
    }

    /**
     * Get list of distributor periods in Unrelno API
     * @return mixed response from Unrelno API
     */
    public function getDistrPeriods()
    {
        // Get list of distributor periods in Unrelno API
        // @return mixed response from Unrelno API
        return $this->sendRequest('Distributors/GetPeriods');
    }

    /**
     * Get information about distributor in Unrelno API
     * @param string $idcode distributor ID
     * @return mixed response from Unrelno API
     */
    public function getDistributors($idcode)
    {
        // Get information about distributor in Unrelno API
        // @param string $idcode distributor ID
        // @return mixed response from Unrelno API
        return $this->sendRequest('Distributors/getInfoTo', ['idcode' => $idcode]);
    }

    /**
     * Check if distributor has documents for the period in Unrelno API
     * @param string $idcode distributor ID
     * @param int $period period ID
     * @return mixed response from Unrelno API
     */
    public function checkDistributorsActs($idcode, $period)
    {
        // Check if distributor has documents for the period in Unrelno API
        // @param string $idcode distributor ID
        // @param int $period period ID
        // @return mixed response from Unrelno API
        return $this->sendRequest('Distributors/checkDocToPeriod', ['idcode' => $idcode, 'idperiod' => $period]);
    }

    /**
     * Get documents of distributor for the period in Unrelno API
     * @param string $idcode distributor ID
     * @param int $period period ID
     * @return mixed response from Unrelno API
     */
    public function getDistributorsActs($idcode, $period)
    {
        // Get documents of distributor for the period in Unrelno API
        // @param string $idcode distributor ID
        // @param int $period period ID
        // @return mixed response from Unrelno API
        return $this->sendRequest('Distributors/GetDocToPeriod', ['idcode' => $idcode, 'idperiod' => $period]);
    }

    /**
     * Get structure of distributor in Unrelno API
     * @param string $idcode distributor ID
     * @return mixed response from Unrelno API
     */
    public function getStructureDistributors($idcode) {
        // Get structure of distributor in Unrelno API
        // @param string $idcode distributor ID
        // @return mixed response from Unrelno API
        return $this->sendRequest('Distributors/getStructure', ['idcode' => $idcode]);
    }

    /**
     * Create new distributor in Unrelno API
     * @param array $data distributor data
     * @return mixed response from Unrelno API
     */
    public function createDistributor(array $data) {
        // Create new distributor in Unrelno API
        // @param array $data distributor data
        // @return mixed response from Unrelno API
        return $this->sendRequest('Distributors/CreateNewDist', $data, 'POST');
    }

    /**
     * Update distributor data in Unrelno API
     * @param string $idcode distributor ID
     * @param array $data distributor data
     * @return mixed response from Unrelno API
     */
    public function updateDistributor($idcode, array $data) {
        // Update distributor data in Unrelno API
        // @param string $idcode distributor ID
        // @param array $data distributor data
        // @return mixed response from Unrelno API
        return $this->sendRequest('Distributors/UpdateDist', $data, 'PUT');
    }

    /**
     * Update distributor files in Unrelno API
     * @param string $idcode distributor ID
     * @param string $dataFile file to upload
     * @param string $typedoc document type
     * @return mixed response from Unrelno API
     */
    public function updateDistrFiles($idcode, $dataFile, $typedoc) {
        // Update distributor files in Unrelno API
        // @param string $idcode distributor ID
        // @param string $dataFile file to upload
        // @param string $typedoc document type
        // @return mixed response from Unrelno API
        return $this->sendRequest('Distributors/UpdateDistrFile', [
            'idcode' => $idcode,
            'file' => $dataFile,
            'typedoc' => $typedoc,
        ], 'POST');
    }

    /**
     * Register sales data in Unrelno API
     * @param array $data sales data
     * @return mixed response from Unrelno API
     */
    public function regSales(array $data) {
        // RegSales - зарегистрировать продажу
        return $this->sendRequest('Nomenclature/RegSales', $data, 'POST');
    }

    /**
     * Register payment data in Unrelno API
     * @param array $data payment data
     * @return mixed response from Unrelno API
     */
    public function regPayment(array $data) {
        // RegPayment - зарегистрировать платеж
        return $this->sendRequest('Nomenclature/RegPayment', $data, 'POST');
    }
}

// Example usage:
$unrelnoAPI = new unrelnoAPI();
$nomenclature = $unrelnoAPI->getNomenclature();
$distrPeriods = $unrelnoAPI->getDistrPeriods();
$distributors = $unrelnoAPI->getDistributors($idcode);
// ...
?>

<?php
/**
 * FileMaker Data API Integration Class
 * Handles all communication with FileMaker database
 */

require_once __DIR__ . '/../config/database.php';

class FileMakerAPI {
    private $config;
    private $baseUrl;
    private $token;
    private $headers;
    
    public function __construct() {
        $this->config = new FileMakerConfig();
        $connection = $this->config->getConnection();
        $this->baseUrl = $connection['baseUrl'];
        $this->headers = [
            'Content-Type: application/json',
            'User-Agent: AntiqueCuirass-API/1.0'
        ];
    }
    
    /**
     * Authenticate with FileMaker Data API
     */
    public function authenticate() {
        $connection = $this->config->getConnection();
        $authData = [
            'fmDataSource' => [
                [
                    'database' => $connection['database'],
                    'username' => $connection['username'],
                    'password' => $connection['password']
                ]
            ]
        ];
        
        $response = $this->makeRequest('POST', '/sessions', $authData);
        
        if ($response && isset($response['response']['token'])) {
            $this->token = $response['response']['token'];
            $this->config->setToken($this->token);
            $this->headers[] = 'Authorization: Bearer ' . $this->token;
            return true;
        }
        
        return false;
    }
    
    /**
     * Get records from a layout
     */
    public function getRecords($layout, $params = []) {
        $queryParams = http_build_query($params);
        $url = "/layouts/{$layout}/records";
        if ($queryParams) {
            $url .= "?{$queryParams}";
        }
        
        return $this->makeRequest('GET', $url);
    }
    
    /**
     * Get a single record by ID
     */
    public function getRecord($layout, $recordId) {
        return $this->makeRequest('GET', "/layouts/{$layout}/records/{$recordId}");
    }
    
    /**
     * Create a new record
     */
    public function createRecord($layout, $data) {
        $payload = [
            'fieldData' => $data
        ];
        
        return $this->makeRequest('POST', "/layouts/{$layout}/records", $payload);
    }
    
    /**
     * Update an existing record
     */
    public function updateRecord($layout, $recordId, $data) {
        $payload = [
            'fieldData' => $data
        ];
        
        return $this->makeRequest('PATCH', "/layouts/{$layout}/records/{$recordId}", $payload);
    }
    
    /**
     * Delete a record
     */
    public function deleteRecord($layout, $recordId) {
        return $this->makeRequest('DELETE', "/layouts/{$layout}/records/{$recordId}");
    }
    
    /**
     * Perform a find request
     */
    public function findRecords($layout, $query) {
        $payload = [
            'query' => [$query]
        ];
        
        return $this->makeRequest('POST', "/layouts/{$layout}/_find", $payload);
    }
    
    /**
     * Make HTTP request to FileMaker API
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['messages'][0]['message'] ?? 'Unknown error';
            throw new Exception("FileMaker API Error: {$errorMessage}");
        }
        
        return $decodedResponse;
    }
    
    /**
     * Close the session
     */
    public function closeSession() {
        if ($this->token) {
            $this->makeRequest('DELETE', '/sessions/' . $this->token);
            $this->token = null;
        }
    }
}

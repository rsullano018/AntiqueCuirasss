<?php
/**
 * JSON Database Configuration
 * Configuration for working with converted_database.json file
 */

class DatabaseConfig {
    private $jsonFile;
    private $data;
    
    public function __construct() {
        $this->jsonFile = __DIR__ . '/../../converted_database.json';
        $this->loadData();
    }
    
    /**
     * Load data from JSON file
     */
    private function loadData() {
        if (!file_exists($this->jsonFile)) {
            throw new Exception("Database file not found: {$this->jsonFile}");
        }
        
        $jsonContent = file_get_contents($this->jsonFile);
        $this->data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in database file: " . json_last_error_msg());
        }
    }
    
    /**
     * Get all data
     */
    public function getAllData() {
        return $this->data;
    }
    
    /**
     * Get data for a specific table
     */
    public function getTableData($tableName) {
        return $this->data[$tableName] ?? [];
    }
    
    /**
     * Save data back to JSON file
     */
    public function saveData() {
        $jsonContent = json_encode($this->data, JSON_PRETTY_PRINT);
        return file_put_contents($this->jsonFile, $jsonContent) !== false;
    }
    
    /**
     * Update table data
     */
    public function updateTableData($tableName, $data) {
        $this->data[$tableName] = $data;
        return $this->saveData();
    }
    
    /**
     * Add record to table
     */
    public function addRecord($tableName, $record) {
        if (!isset($this->data[$tableName])) {
            $this->data[$tableName] = [];
        }
        
        $this->data[$tableName][] = $record;
        return $this->saveData();
    }
    
    /**
     * Update record in table
     */
    public function updateRecord($tableName, $recordId, $record) {
        if (!isset($this->data[$tableName])) {
            return false;
        }
        
        foreach ($this->data[$tableName] as $index => $existingRecord) {
            $idField = $this->getIdField($tableName);
            if (isset($existingRecord[$idField]) && $existingRecord[$idField] == $recordId) {
                $this->data[$tableName][$index] = array_merge($existingRecord, $record);
                return $this->saveData();
            }
        }
        
        return false;
    }
    
    /**
     * Delete record from table
     */
    public function deleteRecord($tableName, $recordId) {
        if (!isset($this->data[$tableName])) {
            return false;
        }
        
        $idField = $this->getIdField($tableName);
        foreach ($this->data[$tableName] as $index => $record) {
            if (isset($record[$idField]) && $record[$idField] == $recordId) {
                unset($this->data[$tableName][$index]);
                $this->data[$tableName] = array_values($this->data[$tableName]); // Re-index array
                return $this->saveData();
            }
        }
        
        return false;
    }
    
    /**
     * Get the ID field name for a table
     */
    private function getIdField($tableName) {
        $idFields = [
            'Products' => 'ProductID',
            'Categories' => 'CategoryID',
            'Customers' => 'CustomerID',
            'Orders' => 'OrderID',
            'Orders_Items' => 'OrderItemID',
            'Product_Images' => 'ImageID'
        ];
        
        return $idFields[$tableName] ?? 'ID';
    }
    
    /**
     * Get next ID for a table
     */
    public function getNextId($tableName) {
        $idField = $this->getIdField($tableName);
        $maxId = 0;
        
        if (isset($this->data[$tableName])) {
            foreach ($this->data[$tableName] as $record) {
                if (isset($record[$idField])) {
                    $id = intval($record[$idField]);
                    if ($id > $maxId) {
                        $maxId = $id;
                    }
                }
            }
        }
        
        return $maxId + 1;
    }
    
    /**
     * Search records in table
     */
    public function searchRecords($tableName, $criteria) {
        if (!isset($this->data[$tableName])) {
            return [];
        }
        
        $results = [];
        foreach ($this->data[$tableName] as $record) {
            $match = true;
            foreach ($criteria as $field => $value) {
                if (!isset($record[$field]) || $record[$field] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $results[] = $record;
            }
        }
        
        return $results;
    }
    
    /**
     * Filter records by multiple criteria
     */
    public function filterRecords($tableName, $filters) {
        if (!isset($this->data[$tableName])) {
            return [];
        }
        
        $results = $this->data[$tableName];
        
        foreach ($filters as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            
            $results = array_filter($results, function($record) use ($field, $value) {
                if (!isset($record[$field])) {
                    return false;
                }
                
                // Handle different filter types
                if (is_string($value) && strpos($value, '..') === 0) {
                    // Price range filter (e.g., "..1000" means <= 1000)
                    $maxValue = floatval(substr($value, 2));
                    return floatval($record[$field]) <= $maxValue;
                } elseif (is_string($value) && strpos($value, '..') !== false) {
                    // Range filter (e.g., "100..500")
                    list($min, $max) = explode('..', $value);
                    $recordValue = floatval($record[$field]);
                    return $recordValue >= floatval($min) && $recordValue <= floatval($max);
                } else {
                    // Exact match or contains
                    return stripos($record[$field], $value) !== false;
                }
            });
        }
        
        return array_values($results);
    }
}
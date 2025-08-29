<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'u820431346_bcattendance');
define('DB_PASSWORD', 'Metx@123');
define('DB_NAME', 'u820431346_bcattendance');

// Create connection
function getDBConnection() {
    date_default_timezone_set('Asia/Kolkata');
    static $connection = null;
    
    if ($connection === null) {
        try {
            $connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            
            if ($connection->connect_error) {
                die("Connection failed: " . $connection->connect_error);
            }
            
            $connection->set_charset("utf8");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    @$connection->query("SET time_zone = '+05:30'");
    return $connection;
}

// Function to execute query safely
function executeQuery($query, $params = [], $types = '') {
    $conn = getDBConnection();
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        } else {
            // Auto-detect parameter types if not provided
            $autoTypes = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $autoTypes .= 'i';
                } elseif (is_float($param)) {
                    $autoTypes .= 'd';
                } else {
                    $autoTypes .= 's';
                }
            }
            if (!empty($autoTypes)) {
                $stmt->bind_param($autoTypes, ...$params);
            }
        }
        
        $success = $stmt->execute();
        if (!$success) {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        // For SELECT queries and SHOW queries, return the result
        if (stripos(trim($query), 'SELECT') === 0 || stripos(trim($query), 'SHOW') === 0) {
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        } else {
                    // For INSERT, UPDATE, DELETE queries, return boolean or insert ID
        $affected_rows = $stmt->affected_rows;
        $insert_id = $stmt->insert_id;
        $stmt->close();
        
        // For INSERT queries, return the insert ID if available
        if (stripos(trim($query), 'INSERT') === 0 && $insert_id > 0) {
            return $insert_id;
        }
        
        return $affected_rows >= 0; // Return true if no error occurred
        }
    } else {
        $result = $conn->query($query);
        if ($result === false) {
            error_log("Query failed: " . $conn->error);
            return false;
        }
        
        // For SELECT queries and SHOW queries, return the result
        if (stripos(trim($query), 'SELECT') === 0 || stripos(trim($query), 'SHOW') === 0) {
            return $result;
        } else {
            // For other queries, return boolean
            return true;
        }
    }
}

// Function to get single row
function fetchRow($query, $params = [], $types = '') {
    $result = executeQuery($query, $params, $types);
    if ($result && is_object($result)) {
        return $result->fetch_assoc();
    }
    return false;
}

// Function to get all rows
function fetchAll($query, $params = [], $types = '') {
    $result = executeQuery($query, $params, $types);
    if ($result && is_object($result)) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

// Function to get last insert ID
function getLastInsertId() {
    // Get the current connection that was used for the last query
    $conn = getDBConnection();
    $lastId = $conn->insert_id;
    
    // If insert_id is 0, try to get it from the last query
    if ($lastId == 0) {
        $result = $conn->query("SELECT LAST_INSERT_ID() as id");
        if ($result) {
            $row = $result->fetch_assoc();
            $lastId = $row['id'];
        }
    }
    
    return $lastId;
}
?>
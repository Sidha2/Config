<?php

namespace Config;
use DB\DbCfg;

class Config extends DbCfg {

    protected $itemId;
    protected $itemName;
    protected $tableName;

    protected function isTableExistContr() {
        $q = $this->db()->query("SELECT 1 FROM $this->tableName");
        if ($q->getConnection()->error) return false;

        return true;
    }

    /**
     *  Create Table
     */
    protected function createTableContr(): bool
    {
        $sql = "CREATE TABLE $this->tableName (
            id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            $this->itemName VARCHAR(50) NOT NULL,
            valueName VARCHAR(50) NOT NULL,
            value VARCHAR(5000) NOT NULL,
            updateTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
        $query = $this->db()->query($sql);
        
        if ($query->affectedRows() == 0) return true;     # tab created successful, -1 = false
        return false;
    }

    # zalozi config pre noveho bota
    protected function inicializeNewConfigContr($allConfigRows, $tableName, $itemName) {

        $jobsCounter = 0;
        $insertCounter = 0;
        $errorCounter = 0;
        $errorMsg = [];

        # vyberam existujuceho bota
        # zistujem ci uz tam je nejaky bot, ak nie create s id=1
        # ak ano, zistim posledne bot ID a vytvorim id+1
        $q = $this->db()->query("SELECT $itemName FROM $tableName ORDER BY $itemName DESC LIMIT 1")->fetchArray();
        if (count($q) > 0) {
           $newItemId = $q[$itemName] + 1;
        } else {
            $newItemId = 1;
        }

        $this->itemId = $newItemId;

        foreach ($allConfigRows as $key => $value) {
            $record = $this->insertRowConfigContr($key, $value, $tableName, $itemName);
            $jobsCounter++;
            if ($record['status'] == 1) $insertCounter++;
            else {
                $errorCounter++;
                $errorMsg[] = $record['errorMsg'];
            }
        }
        return ['newItemId' => $newItemId, 'jobs' => $jobsCounter, 'insert' => $insertCounter, 'error' => $errorCounter, 'errorList' => $errorMsg];
    }


    # upravim zaznam                        #bot_name   robot   bot_config  botId
    protected function updateRowConfigContr($valueName, $value, $tableName, $itemName) {
        
        # ak chcem updatnut na tu istu hodnotu, dam o tom hlasku
        $query = $this->db()->query("SELECT value FROM $tableName WHERE $itemName = ? AND valueName = ?", $this->itemId, $valueName)->fetchArray();
        if (isset($query['value']))
        if ($query['value'] == $value) {
            $query = [];
            return self::queryOutputFetch($query, "'$valueName' is same as in DB ('$value')");
        }

        $query = $this->db()->query("UPDATE $tableName SET value = ? WHERE $itemName = ? AND valueName = ?", $value, $this->itemId, $valueName);
        return self::queryOutput($query, "Row 'itemId'='$this->itemId' & 'valueName'='$valueName' not found");
    }

    
    

    # zmazem zaznam
    protected function deleteRowConfigContr($valueName, $tableName, $itemName) {
        $query = $this->db()->query("DELETE FROM $tableName WHERE $itemName = ? AND valueName = ?", $this->itemId, $valueName);
        return self::queryOutput($query, "Row 'itemId'='$this->itemId' & 'valueName'='$valueName' not found");
    }



    # vlozim zaznam
    protected function insertRowConfigContr($valueName = "testvalue", $value = 0, $tableName = '', $itemName = '') {
        $isExist = $this->isRowExistContr($valueName, $tableName, $itemName);

        if (!$isExist) {
            $query = $this->db()->query("INSERT INTO $tableName ($itemName, valueName, value) VALUES (?, ?, ?)", $this->itemId, $valueName, $value);
            $status = self::queryOutput($query, "Insert not complete");
            $status['row'] = $valueName;
            $status['value'] = $value;
            return $status;
        } else {
            return ["status" => 0, "errorMsg" => "Row '$valueName' and itemId '$this->itemId' already exist"];
        }
    }



    # citam vsetky zaznamy pre konkretneho bota
    protected function readAllRowsConfigView($tableName, $itemName) {
        $query = $this->db()->query("SELECT * FROM $tableName WHERE $itemName = ?", $this->itemId);
        $return = $query;
        return self::queryOutputFetch($query->fetchAll(), "itemId not found");        
    }



    protected function isRowExistContr($row, $tableName, $itemName) {
        $query = $this->db()->query("SELECT valueName FROM $tableName WHERE $itemName = ? AND valueName = ?", $this->itemId, $row);
        $query = $query->numRows();
        if ($query > 0) return true; else return false;
    }



    /*# vytvorim tabulku
    protected function createTableContr($sql) {
                
        $query = $this->db()->query($sql)->errorList();
        return $query;
    }*/

    # zmenim nacitane multiarray configu na array
    protected function changeConfigMultiArraytoArray($multiArray) {
        $return = [];
        foreach ($multiArray as $value) {
            $return[$value['valueName']] = $value['value'];
        }
        return $return;
    }


    # zisti ci ID je v DB ak nie, take ID pre config neexistuje
    protected function isConfigExistContr($tableName, $itemName) {
        $query = $this->db()->query("SELECT $itemName FROM $tableName WHERE $itemName = ?", $this->itemId)->numRows();
        if ($query > 0) return true; else return false;
    }

    # nacita id vsetkych botov v DB
    protected function getAllBotsIdContr($tableName) {
        $query = $this->db()->query("SELECT DISTINCT botId FROM $tableName WHERE botId > 0");
        $return = $query;
        return self::queryOutputFetch($query->fetchAll(), "No Bots In DB");
    }

    /**
     *  Postavi pole pre output
     *  ok - true/false
     *  insertId
     *  error
     */
    private static function queryOutput($query, $errorMsg = null, $return = null) {
        
        if ($query->affectedRows() > 0) {
            $out['status']  = 1; 
            $out['errorMsg'] = '';  
        }else{
            $out['status']  = 0;
            $out['errorMsg'] = $errorMsg;
        } 
        
        $out['id']          = $query->insertId();
        $out['errorList']   = $query->errorList();
        $out['return']      = $return;

        return $out;
    }

    private static function queryOutputNumRows($query, $errorMsg = null, $return = null) {
        
        if ($query->numRows() > 0) {
            $out['status']  = 1; 
            $out['errorMsg'] = '';  
        }else{
            $out['status']  = 0;
            $out['errorMsg'] = $errorMsg;
        } 
        
        $out['id']          = $query->insertId();
        $out['errorList']   = $query->errorList();
        $out['return']      = $return;

        return $out;
    }

    private static function queryOutputFetch($query, $errorMsg = null) {
        
        if (count($query) > 0) {
            $out['status']  = 1; 
            $out['errorMsg'] = '';
            $out['numRows'] = count($query);  
        }else{
            $out['status']  = 0;
            $out['errorMsg'] = $errorMsg;
            $out['numRows'] = 0;
        } 
        
        $out['return']      = $query;

        return $out;
    }
}
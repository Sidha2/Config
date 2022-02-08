<?php

namespace Config;
use Config\Config;

class ConfigContr extends Config {
    
    private $defaultConfigRows;     # default config hodnoty z config.php    
    private $allConfigRows = [];    # tieto hodnoty sa vygeneruju botom do configu
    private $tableDataArray;
    

    /**
    *  Construct
    */
    public function __construct(array $tableDataArray) {
        $this->tableDataArray = $tableDataArray;
    }


    public function loadConfig($configId): void {
        $this->initialConfig($configId);
    }

    /**
     *  Create New Config
     *  return ID of new config
     */
    public function createConfig(): int {
        return $this->initialConfig(0, true);
    }


    # tableName = nazov tabulky napr. bot_config / strategy_config ...
    # ak zadam $createNewConfig tak vytvori noveho bota
    private function initialConfig($configId, $createNewConfig = null) {

        $this->tableName            = $this->tableDataArray['tableName'];
        $this->itemName             = $this->tableDataArray['itemName'];
        $this->defaultConfigRows    = $this->tableDataArray['defaultConfigRows'];


        if (!$this->isTableExist()) {
           
            # vytrvaram tabulku
            if (!$this->createTable()) die ("Nepodarilo sa vytvorit tabulku '$this->tableName'");

            # vytvaram novu polozku a vraciam jej ID
            return $this->inicializeNewConfig();
        } 

        # ak je parameter zakladam noveho bota a vraciam jeho ID
        if ($createNewConfig != null) {
            $out = $this->inicializeNewConfig();
            return $out['newItemId'];
        } 
        
        # inak nacitam normalne ID existujuceho bota
        else {
            $this->itemId = $configId;
        }
                
        if (!$this->isConfigExist()) die("Config '$this->itemName' with ID '$configId' don't exist.");
        
        $this->fixMissingRows();        # doplni chybajuce polia configu podla default pola $botConfigRows z config.php
        $this->readAllConfig();         # nacita cely konfig
    }

   

    
    # zada hodnotu z cfg pola
    public function __set(string $member, $value) {
        $out['status'] = 0;

        # ak existuje taka premenna v poli
        if (isset($this->allConfigRows[$member])) {

            # ak je hodnota rovnaka ako chcem zapisat, ignorujem
            if ($this->allConfigRows[$member] == $value) {
                $out['status'] = 1;
                $out['errorMsg'] = "Value '$member' in Cfg is same as update '$value'";
                return $out;
            }

            # ak sa podari zapisat do DB upravim aj v poli
            $q = $this->updateRowConfig($member, $value);
            if($q['status'] == 1) {
                $out['status'] = 1;
                $out['key'] = $member;
                $out['value'] = $value;
                $this->allConfigRows[$member] = $value;
            }else{
                $out['errorMsg'] = $q['errorMsg'];
                //$out['errorList'] = $q['errorList'];
            }
        } else {
            
            $out['errorMsg'] = "Row '$member' not found in config Array 'allConfigRows'";
        }
        return $out;
    }


    # vrati hodnotu z cfg pola
    public function __get(string $member) {
        if (isset($this->allConfigRows[$member])) {
            return $this->allConfigRows[$member];
        }
        return false;
    }

    /**
     * Get item ID
     */
    public function getItemId() {
        return $this->itemId;
    }

    /**
     * Get the value of allConfigRows
     */ 
    public function getAllConfigRows() {
        return $this->allConfigRows;
    }

    private function createTable() {
        return $this->createTableContr();        
    }
        

    # doplni chybahuce cfg polia podla default pola
    public function fixMissingRows() {

        $out['status'] = 1;
        $out['insert_Count'] = 0;
        foreach ($this->defaultConfigRows as $key => $row) {
            $isExist = $this->isRowExist($key);
            if (!$isExist) {
                $insert = $this->insertRowConfig($key, $row);
                $out['insert_Count']++;
                $out['msg'][] = "Inserted row '".$insert['row']. "' value: '". $insert['value']. "'"; 
            }
        }
        return $out;
    }

    # zalozi vsetky potrebne hodnoty configu pre noveho bota
    private function inicializeNewConfig() {
        
        $return = $this->inicializeNewConfigContr($this->defaultConfigRows, $this->tableName, $this->itemName);
        $this->itemId = $return['newItemId'];
        return $return;
    }

    private function isRowExist($row) {
        return $this->isRowExistContr($row, $this->tableName, $this->itemName);
    }


    # updatne vsetky hodnoty configu pre bota s ID
    public function updateAllConfig($allConfigRows) {

        $out['status'] = 1;  
        
        foreach ($allConfigRows as $key => $row) {
            
            $update = $this->__set($key, $row);
            if (isset($update['errorMsg'])) $errorMsg[] = $update['errorMsg'];
            if ($update['status'] == 0) $out['status'] = 0;

        }
        $out['errorMsg'] = $errorMsg;
        return $out;
    }

    # vytvori novy config row
    public function insertRowConfig($name, $value) {
        $this->allConfigRows[$name] = $value;
        return $this->insertRowConfigContr($name, $value, $this->tableName, $this->itemName);        
    }
    

    # zmaze row
    public function deleteRowConfig($name) {
        return $this->deleteRowConfigContr($name, $this->tableName, $this->itemName);
    }

    # updatne row
    public function updateRowConfig($name, $value) {
        return $this->updateRowConfigContr($name, $value, $this->tableName, $this->itemName);
    }

    # nacita do pola vsetky CFG pre dane ID
    /*
        [status] => 1
        [errorMsg] => 
        [numRows] => 3
        [return] => Array
            (
                [pair] => BTCUSDT
                [run] => 0
                [name] => bot_name
                ...
            )
    */
    private function readAllConfig() {

        $cfgData = $this->readAllRowsConfigView($this->tableName, $this->itemName);
        $multiArray = $cfgData['return'];
        $cfgData['return'] = $this->changeConfigMultiArraytoArray($multiArray);

        if ($cfgData['status'] == 1) $this->allConfigRows = $cfgData['return'];
        return $cfgData;        
    }

    private function isConfigExist() {
        return $this->isConfigExistContr($this->tableName, $this->itemName);
    }

    public function getAllBotsId() {
        return $this->getAllBotsIdContr($this->tableName);
    }

    private function isTableExist() {
        return $this->isTableExistContr();
    }
    

   
}
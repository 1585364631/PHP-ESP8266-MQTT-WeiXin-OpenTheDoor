<?php

class mysql{
    protected static $_instance = null;
    protected $dbName = '';
    protected $dsn;
    protected $dbh;

    private function __construct($dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset)
    {
        try {
            $this->dsn = 'mysql:host='.$dbHost.';dbname='.$dbName;
            $this->dbh = new PDO($this->dsn, $dbUser, $dbPasswd);
            $this->dbh->exec('SET character_set_connection='.$dbCharset.', character_set_results='.$dbCharset.', character_set_client=binary');
        } catch (PDOException $e) {
            try {
                $this->outputError($e->getMessage());
            } catch (Exception $e) {
            }
        }
    }

    private function __clone() {}

    public static function getInstance($dbHost='localhost', $dbUser='root', $dbPasswd='root', $dbName='wechat', $dbCharset='utf8')
    {
        if (self::$_instance === null) {
            self::$_instance = new self($dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset);
        }
        return self::$_instance;
    }

    /**
     * Query 查询
     *
     * @param String $strSql SQL语句
     * @param String $queryMode 查询方式(All or Row)
     * @param Boolean $debug
     * @return Array
     */
    public function query(string $strSql, string $queryMode = 'All', bool $debug = false): array
    {
        if ($debug === true) $this->debug($strSql);
        $recordset = $this->dbh->query($strSql);
        $this->getPDOError();
        if ($recordset) {
            $recordset->setFetchMode(PDO::FETCH_ASSOC);
            if ($queryMode == 'All') {
                $result = $recordset->fetchAll();
            } elseif ($queryMode == 'Row') {
                $result = $recordset->fetch();
            }
        } else {
            $result = null;
        }
        return $result??[];
    }

    /**
     * Update 更新
     *
     * @param String $table 表名
     * @param Array $arrayDataValue 字段与值
     * @param String $where 条件
     * @param Boolean $debug
     * @return Int
     */
    public function update(string $table, array $arrayDataValue, string $where = '', bool $debug = false): int
    {
        $this->checkFields($table, $arrayDataValue);
        if ($where) {
            $strSql = '';
            foreach ($arrayDataValue as $key => $value) {
                $strSql .= ", `$key`='$value'";
            }
            $strSql = substr($strSql, 1);
            $strSql = "UPDATE `$table` SET $strSql WHERE $where";
        } else {
            $strSql = "REPLACE INTO `$table` (`".implode('`,`', array_keys($arrayDataValue))."`) VALUES ('".implode("','", $arrayDataValue)."')";
        }
        if ($debug === true) $this->debug($strSql);
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    /**
     * Insert 插入
     *
     * @param String $table 表名
     * @param Array $arrayDataValue 字段与值
     * @param Boolean $debug
     * @return Int
     */
    public function insert(string $table, array $arrayDataValue, bool $debug = false): int
    {
        $this->checkFields($table, $arrayDataValue);
        $strSql = "INSERT INTO `$table` (`".implode('`,`', array_keys($arrayDataValue))."`) VALUES ('".implode("','", $arrayDataValue)."')";
        if ($debug === true) $this->debug($strSql);
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    /**
     * Replace 覆盖方式插入
     *
     * @param String $table 表名
     * @param Array $arrayDataValue 字段与值
     * @param Boolean $debug
     * @return Int
     */
    public function replace(string $table, array $arrayDataValue, bool $debug = false): int
    {
        $this->checkFields($table, $arrayDataValue);
        $strSql = "REPLACE INTO `$table`(`".implode('`,`', array_keys($arrayDataValue))."`) VALUES ('".implode("','", $arrayDataValue)."')";
        if ($debug === true) $this->debug($strSql);
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    /**
     * Delete 删除
     *
     * @param String $table 表名
     * @param String $where 条件
     * @param Boolean $debug
     * @return Int
     */
    public function delete(string $table, string $where = '', bool $debug = false): int
    {
        if ($where == '') {
            try {
                $this->outputError("'WHERE' is Null");
            } catch (Exception $e) {
            }
        } else {
            $strSql = "DELETE FROM `$table` WHERE $where";
            if ($debug === true) $this->debug($strSql);
            $result = $this->dbh->exec($strSql);
            $this->getPDOError();
            return $result;
        }
    }

    /**
     * execSql 执行SQL语句
     *
     * @param String $strSql
     * @param Boolean $debug
     * @return Int
     */
    public function execSql(string $strSql, bool $debug = false): int
    {
        if ($debug === true) $this->debug($strSql);
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    /**
     * 获取字段最大值
     *
     * @param string $table 表名
     * @param string $field_name 字段名
     * @param string $where 条件
     */
    public function getMaxValue(string $table, string $field_name, string $where = '', $debug = false)
    {
        $strSql = "SELECT MAX(".$field_name.") AS MAX_VALUE FROM $table";
        if ($where != '') $strSql .= " WHERE $where";
        if ($debug === true) $this->debug($strSql);
        $arrTemp = $this->query($strSql, 'Row');
        $maxValue = $arrTemp["MAX_VALUE"];
        if ($maxValue == "" || $maxValue == null) {
            $maxValue = 0;
        }
        return $maxValue;
    }

    /**
     * 获取指定列的数量
     *
     * @param string $table
     * @param string $field_name
     * @param string $where
     * @param bool $debug
     * @return int
     */
    public function getCount(string $table, string $field_name, string $where = '', bool $debug = false): int
    {
        $strSql = "SELECT COUNT($field_name) AS NUM FROM $table";
        if ($where != '') $strSql .= " WHERE $where";
        if ($debug === true) $this->debug($strSql);
        $arrTemp = $this->query($strSql, 'Row');
        return $arrTemp['NUM'];
    }

    /**
     * 获取表引擎
     *
     * @param String $dbName 库名
     * @param String $tableName 表名
     * @return String
     */
    public function getTableEngine(string $dbName, string $tableName): string
    {
        $strSql = "SHOW TABLE STATUS FROM $dbName WHERE Name='".$tableName."'";
        $arrayTableInfo = $this->query($strSql);
        $this->getPDOError();
        return $arrayTableInfo[0]['Engine'];
    }

    /**
     * beginTransaction 事务开始
     */
    private function beginTransaction()
    {
        $this->dbh->beginTransaction();
    }

    /**
     * commit 事务提交
     */
    private function commit()
    {
        $this->dbh->commit();
    }

    /**
     * rollback 事务回滚
     */
    private function rollback()
    {
        $this->dbh->rollback();
    }

    /**
     * transaction 通过事务处理多条SQL语句
     * 调用前需通过getTableEngine判断表引擎是否支持事务
     *
     * @param array $arraySql
     * @return Boolean
     */
    public function execTransaction(array $arraySql): bool
    {
        $retval = 1;
        $this->beginTransaction();
        foreach ($arraySql as $strSql) {
            if ($this->execSql($strSql) == 0) $retval = 0;
        }
        if ($retval == 0) {
            $this->rollback();
            return false;
        } else {
            $this->commit();
            return true;
        }
    }

    /**
     * checkFields 检查指定字段是否在指定数据表中存在
     *
     * @param String $table
     * @param $arrayFields
     */
    private function checkFields(string $table, $arrayFields)
    {
        $fields = $this->getFields($table);
        foreach ($arrayFields as $key => $value) {
            if (!in_array($key, $fields)) {
                try {
                    $this->outputError("Unknown column `$key` in field list.");
                } catch (Exception $e) {
                }
            }
        }
    }

    /**
     * getFields 获取指定数据表中的全部字段名
     *
     * @param String $table 表名
     * @return array
     */
    private function getFields(string $table): array
    {
        $fields = array();
        $recordset = $this->dbh->query("SHOW COLUMNS FROM $table");
        $this->getPDOError();
        $recordset->setFetchMode(PDO::FETCH_ASSOC);
        $result = $recordset->fetchAll();
        foreach ($result as $rows) {
            $fields[] = $rows['Field'];
        }
        return $fields;
    }

    /**
     * getPDOError 捕获PDO错误信息
     */
    private function getPDOError()
    {
        if ($this->dbh->errorCode() != '00000') {
            $arrayError = $this->dbh->errorInfo();
            try {
                $this->outputError($arrayError[2]);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * debug
     *
     * @param mixed $debuginfo
     */
    private function debug($debuginfo)
    {
        var_dump($debuginfo);
        exit();
    }

    /**
     * 输出错误信息
     *
     * @param String $strErrMsg
     * @throws Exception
     */
    private function outputError(string $strErrMsg)
    {
        throw new Exception('MySQL Error: '.$strErrMsg);
    }

    /**
     * destruct 关闭数据库连接
     */
    public function destruct()
    {
        $this->dbh = null;
    }
}

//$db = mysql::getInstance();
//var_dump($db->query("select * from opendoor"));
//$db->destruct();
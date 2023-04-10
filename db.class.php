<?php
class DBext
{
    protected $connection;

    public function __construct($host, $user, $password, $db_name)
    {
        try {
            $this->connection = new PDO( 'pgsql:host=' . $host . ';port=5432;dbname=' . $db_name . ';user=' . $user . ';password=' . $password );
        } catch ( Exception $e ) {
            error_log(date('Y-m-d H:m:s') . " DB Error=" . $e->getMessage() ." \t \n    pgsql:host=" . $host . "; port=5432; dbname=" . $db_name . "; user=" . $user . "; password= ---------\n\n", 3, "/home/sysadm/Documents/dev/dms/InfMsgReceiver/InfMsgReceiver.log");
            throw new Exception($e);

        }

        //$this->connection->setAttribute( PDO::SQLSRV_ATTR_DIRECT_QUERY, true );
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }
    public function query($sql)
    {
        if(!$this->connection)
        {
            return false;
        }
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            echo "\nPDO::errorInfo():\n";
            //print_r($stmt->errorInfo());
        }else{
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute();
            $result = $stmt->fetchAll();
            unset($stmt);
        }
        
        if (is_bool($result) )
        {
            return $result;
        }
        return $result;
        
    }
    public function quote($sql){
        return $this->connection->quote($sql);
    }


}
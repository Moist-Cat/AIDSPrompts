<?php
include('config/config.php');
class DatabaseBackup
{



    private static $conn;

    function __construct()
    {
        $this->conn = mysqli_connect(DBHOST, DBUSER, DBPWD, DBNAME);
        $this->conn->set_charset("utf8");
    }

    public static function getConnection()
    {
        if (empty($this->conn)) {
            new Database();
        }
    }

    function getAllTable()
    {
        $tables = array();

        $sql = "SHOW TABLES";
        $result = mysqli_query($this->conn, $sql);

        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
        return $tables;
    }

    /*
     * To backup multiple tables
     * param: $tables is a string containing more than one table names seperated with comma
     */
    function backupDatabase($tables)
    {
        $fileString = "";
        foreach ($tables as $table) {
            if (!empty($table)) {
                $fileString .= $this->getSQLScript($table);
            }
        }

        if (strlen($fileString) > 0) {
            $this->saveToBackupFile($fileString);
        }
    }

    function getSQLScript($tableName)
    {
        // Shows the CREATE TABLE statement that created the given table.
        $sqlShowQuery = "SHOW CREATE TABLE $tableName";
        $result = mysqli_query($this->conn, $sqlShowQuery);
        $row = mysqli_fetch_row($result);

        $fileString .= "\n\n" . $row[1] . ";\n\n";
        $sqlQuery = "SELECT * FROM " . $tableName;
        if ($tableName == "prompts")
            $sqlQuery .= " where PublishDate is not null";
        if ($tableName == "worldinfos")
            $sqlQuery .= " where PromptId in (Select Id from prompts where PublishDate is not null)";
        $result = mysqli_query($this->conn, $sqlQuery);

        // Return the number of fields (columns)
        $fieldCount = mysqli_num_fields($result);


        for ($i = 0; $i < $fieldCount; $i++) {
            while ($row = mysqli_fetch_row($result)) {
                $fileString .= "INSERT INTO $tableName VALUES(";
                for ($j = 0; $j < $fieldCount; $j++) {
                    $row[$j] = $row[$j];

                    if (isset($row[$j])) {
                        $fileString .= "'" . str_replace("'", "\'", str_replace('\\', '\\\\', $row[$j])) . "'";
                    } else {
                        $fileString .= "NULL";
                    }
                    if ($j < ($fieldCount - 1)) {
                        $fileString .= ',';
                    }
                }
                $fileString .= ");\n";
            }
        }

        $fileString .= "\n";
        return $fileString;
    }

    function saveToBackupFile($fileString)
    {

        $backup_file_name = DBNAME . '_backup_' . time() . '.sql';


        // Download the SQL backup file to the browser
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $backup_file_name);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        ob_clean();
        echo $fileString;
        flush();
    }
}

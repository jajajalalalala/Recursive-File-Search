<?php

/**
 * Class DataBase
 * A class to define the Database connector, create table, load data and search method
 */
class DataBase
{

    private $servername = "127.0.0.1";
    private $username = "root";
    private $password = "password";
    private $dbname = "fileDB";

    /**
     * Create Database and table
     */
    public function createDBAndTable()
    {
        // Establish connection
        $conn = new mysqli($this->servername, $this->username, $this->password);
        // Check connection
        if ($conn->connect_error) {
            die("Connection Failed: " . $conn->connect_error);
        }
        echo "Connection Successful";

//Create Database
        $sql = "CREATE DATABASE IF NOT EXISTS fileDB";
        if ($conn->query($sql) === TRUE) {
            echo "Database is created successfully!";
            echo "<br/>";
        } else {
            echo "Error creating database: " . $conn->error;
            echo "<br/>";
        }

// Create table
        $sql2 = "CREATE TABLE IF NOT EXISTS fileDB.FileTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
    fileDirectory VARCHAR(60) NOT NULL
    )";

        if ($conn->query($sql2) === TRUE) {
            echo "Table FileTable is created successfully!";
            echo "<br/>";
        } else {
            echo "Error in creating the table: " . $conn->error;
            echo "<br/>";
        }

    }

    /**
     * Load file pathes to the database
     * @param $filePaths
     * @return bool return true if data is loaded successfuly
     */
    public function loadFilePaths($filePaths)
    {

        $conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);

        if ($conn->connect_error) {
            die("Connection Failed " . $conn->connect_error);

        }

        $stmt = $conn->prepare("INSERT INTO FileTable (fileDirectory) VALUES (?)");
        $stmt->bind_param("s", $fileDirectory);
        foreach ($filePaths as $path) {
            $fileDirectory = $path;
            $stmt->execute();
        }

        $stmt->close();
        $conn->close();
        return TRUE;
    }


    /**
     * Search a specific word in the database
     * @param $search the searching word
     * @return bool|mysqli_result the query result
     */
    public function searchPaths($search)
    {
        $con = new mysqli($this->servername, $this->username, $this->password, $this->dbname);


        $query = "SELECT fileDirectory FROM FileTable WHERE fileDirectory LIKE '%$search%'";

        $results = $con->query($query);

        $con->close();
        return $results;
    }
}

/**
 * Class FileOrganizer
 *
 * A class to format the structured path dir to full directory.
 */
class FileOrganizer
{
    /**
     * @param $file the file with path structure
     * @return The full path array
     */
    public function organize($file)
    {

        $output = [];
        $level = 0;
        $tempString = "";

        while (!feof($file)) {
            $dataline = fgets($file);
            $trimeddata = ltrim(str_replace(array("\n", "\r", '\\'), '', $dataline));

            $currentLevel = (strlen($dataline) - strlen(ltrim($dataline))) / 4; //Get the number of front \t


            if ($currentLevel == 0) {
                $tempString .= $trimeddata;
                array_push($output, $trimeddata . "\\");
            } elseif ($currentLevel > $level) {
                if (strpos($dataline, '.') == TRUE) {
                    array_push($output, $tempString . '\\' . $trimeddata);
                } else {

                    $tempString .= '\\' . $trimeddata;
                    $level += 1;
                    array_push($output, $tempString);
                }

            } elseif ($currentLevel == $level) {

                $slashPosition = strrpos($tempString, "\\");

                $tempString = substr($tempString, 0, $slashPosition);

                $tempString = $tempString . '\\' . $trimeddata;


            } else {
                while ($currentLevel <= $level) {

                    $slashPosition = strrpos($tempString, "\\");

                    $tempString = substr($tempString, 0, $slashPosition);

                    $level -= 1;
                }
                $tempString = $tempString . '\\' . $trimeddata;

                array_push($output, $tempString);
            }


        }
        fclose($file);
        return $output;
    }
}

/**
 * Create Data
 */
$database = new Database();
$database->createDBAndTable();


/**
 * When submit, get the upload file , reformat it and load to database.
 */
if (isset($_POST['submit'])) {
    $isLoaded = False;
    $isUploaded = False;

    $directory = ".";
    $target_file = $directory . basename($_FILES["fileToUpload"]["name"]);
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {




        $myfile = fopen("data.txt", "r") or die("Unable to open file!");
        $isUploaded = True;
//Organize the file to complete pathes
        $fileOrganizer = new FileOrganizer();
        $finalOutput = $fileOrganizer->organize($myfile);

//Insert the complete pathes to the Database
        $database = new Database();
        $isLoaded = $database->loadFilePaths($finalOutput);

    }
}


/**
 * Search a specific text.
 */
if (isset($_POST['searchFile'])) {
    $search = $_POST['search'];
    //Search the word in database
    $database = new Database();
    $results = $database->searchPaths($search);
}

?>


<!DOCTYPE html>
<html>
<body>
<h1>Recursive File Directory</h1>

<h2>Upload File</h2>

<form action="" method="post" enctype="multipart/form-data">
    Select file to upload:
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Upload Data" name="submit">
</form>

 <?php

 if ($isUploaded == TRUE) {
     echo "Data Uploaded Successfully!" . "<br/>";
 }
if ($isLoaded == TRUE) {
    echo "Data Loaded into database!" . "<br/>";
}
?>
<br/>

<h2>Search File</h2>

<form action="" method="post">
    Search File:
    <input type="search" name="search" placeholder="Search Content...">
    <input type="submit" value="search" name="searchFile">
</form>

<?php

if ($results->num_rows > 0) {

    echo "The Result are:" . "<br>";

    while ($row = $results->fetch_assoc()) {

        echo "File directory: " . $row['fileDirectory'] . "<br>";
    }
}



?>

</body>
</html>
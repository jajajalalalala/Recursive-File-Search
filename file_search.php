
<?php
$servername = "127.0.0.1";
$username = "root";
$password = "password";
$dbname = "fileDB";

// Establish connection
$conn = new mysqli($servername, $username, $password);
 
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

if (isset($_POST['submit'])) {

    $directory = ".";
    $target_file = $directory . basename($_FILES["fileToUpload"]["name"]);
    if(move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)){
        echo "File uploaded successfully!";
        echo "<br/>";


$myfile = fopen("data.txt", "r") or die("Unable to open file!");
$textString = "";
$finalOutput = [];
$level = 0;
$tempString = "";

while(!feof($myfile)) {
    $dataline = fgets($myfile);
    $trimeddata = ltrim(str_replace(array("\n", "\r", '\\'), '', $dataline));
    

    $currentLevel = (strlen($dataline) - strlen(ltrim($dataline)))/4;


    if ($currentLevel == 0){
        $tempString .= $trimeddata;
        array_push( $finalOutput, $trimeddata . "\\");
    } elseif ($currentLevel > $level){
        if ( strpos($dataline, '.') == TRUE){
            array_push( $finalOutput, $tempString . '\\'. $trimeddata);
        } else{

            $tempString .= '\\' . $trimeddata;
            $level += 1;
            array_push( $finalOutput, $tempString);
        }
        
    } elseif ($currentLevel == $level){
          
            $slashPosition = strrpos($tempString,"\\");
            
            $tempString  = substr($tempString,0, $slashPosition);
            
          $tempString = $tempString . '\\'. $trimeddata;
          
           
        
    }
    else{
        while($currentLevel  <= $level){
            
            $slashPosition = strrpos($tempString,"\\");
    
            $tempString  = substr($tempString,0, $slashPosition);
        
            $level -= 1;
        }
        $tempString = $tempString . '\\'. $trimeddata;
       
        array_push( $finalOutput, $tempString );
    }

    

    }
fclose($myfile);

$isLoaded = FALSE;

// Establish Connection
$conn = new mysqli($servername, $username, $password, $dbname);
 
// Test Connection
if ($conn->connect_error) {
    die("Connection Failed " . $conn->connect_error);
}

// $query = "INSERT INTO FileTable (fileDirectory) VALUES (?)";

$stmt = $conn->prepare("INSERT INTO FileTable (fileDirectory) VALUES (?)");
$stmt->bind_param("s", $fileDirectory);
foreach ($finalOutput as $output) {
    $fileDirectory = $output;
    $stmt->execute();
}

$isLoaded = TRUE;

// $stmt->close();
$conn->close();

}
}

if (isset($_POST['Search'])) {

    $connection = new mysqli($servername, $username, $password, $dbname);

    $search = $_POST['search'] ;

    $query = "SELECT fileDirectory FROM FileTable WHERE fileDirectory LIKE '%$search%'";

    $results = $connection->query($query);

    $connection->close();
    
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
if ($isLoaded == TRUE){
    echo "Data Loaded Successfully." . "<br/>";
}
else{
    echo "Data is Not Loaded Successfully." . "<br/>";
}
?>
<br/>

<h2>Search File</h2>

<form action="" method="post" >
  Search File:
  <input type="search" name="search" placeholder="Search Content...">
  <input type="submit" value="search" name="Search">
</form>

<?php 

if ($results->num_rows > 0) {

	echo "The Result are:" . "<br>";
  
	while($row = $results->fetch_assoc()) {

    	echo "File directory: " . $row['fileDirectory'] . "<br>";
  }
}

?>

</body>
</html>
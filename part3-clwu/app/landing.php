<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing</title>
</head>
<body>
    <h1>Poems & Short Stories Forum</h1>
    <p>A place where you can read and submit poems, short stories.</p>

    <!-- Register button -->
    <button onclick="document.location='register.php'">Register</button>
    <br><br>
    <!-- Login button -->
    <button onclick="document.location='login.php'">Login</button>
    <hr>

    <!-- Immediate Immersion -->
<?php
    // Connect to test db
    $conn = new mysqli("localhost", "root", "", "test");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Select most recent data from test DB and display
    $sql = "SELECT * FROM posts ORDER BY post_id DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $iterator = 0;
        while($row = $result->fetch_assoc()) {
            // echo "Submitted by " . $row["user"] . ":<br>";
            echo "<b>" . $row["title"] . " by " . $row["author"] . " (" . $row["year"] . ")</b><br>";
            echo $row["body"] . "<br><br>";
            $iterator++;
            if ($iterator == 3) {
                break;
            }
        }
    }

    $conn->close();
?>

</body>
</html>
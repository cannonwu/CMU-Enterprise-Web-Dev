<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>

    <form action="" method="post">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username"><br><br>
        <label for="password">Password:</label><br>
        <input type="text" id="password" name="password"><br><br>
        <input type="submit" value="Login">
    </form>

</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Connect to test db
    $conn = new mysqli("localhost", "root", "", "test");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check login credentials
    $sql = "SELECT username, password FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    if ($result->num_rows == 0) {   // if username does not exist, reject
        echo "<p style='color:red;'>Incorrect login credentials.</p>";
    } else {
        $row = $result->fetch_assoc();
        if ($password != $row["password"]) {    // if passwords do not match, reject
            echo "<p style='color:red;'>Incorrect login credentials.</p>";
        } else {    // success: username exists & correct password
            $_SESSION["username"] = $username;  // set session data
            header("location: dashboard.php");  // redirect to dashboard
        }
    }


    $conn->close();
}

?>
<?php
session_start();
if (!isset($_SESSION["username"])) {    // if not logged in, redirect to landing
    header("location: landing.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make a Post</title>
</head>
<body>
    <h2>Submit a Short Story or Poem</h2>

    <!-- Submission Form -->
    <form action="" method="post">
        <label for="title">Title:</label><br>
        <input type="text" id="title" name="title"><br><br>
        <label for="author">Author(s)</label><br>
        <input type="text" name="author" id="author"><br>
        <input type="checkbox" id="multipleAuthors" name="multipleAuthors" value="1">
        <label for="multipleAuthors">Multiple Authors?</label><br><br>
        <label for="year">Year:</label><br>
        <input type="text" id="year" name="year"><br><br>
        <label for="body">Text Body:</label><br>
        <textarea name="body" id="body" cols="60" rows="10"></textarea><br>
        <br><input type="submit" value="Submit">
    </form>

</body>
</html>

<!-- echo form values to test -->
<?php
$username = $_SESSION["username"];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $author = $_POST["author"];
    $multipleAuthors = $_POST["multipleAuthors"];
    if ($multipleAuthors != 1) {    // set to 0 if not 1, avoids null
        $multipleAuthors = 0;
    }
    $year = $_POST["year"];
    $body = $_POST["body"];

    // Get keywords
    // Put all content into one string
    $content = $title . " " . $author . " " . $year . " " . $body;
    $content = strtolower($content);

    // Split content into tokens
    // $content = preg_replace('/[,]+/', '', $content);
    $contentTokens = explode(" ", $content);
    // print_r($contentTokens);

    // Remove common words
    // Source: https://www.ranks.nl/stopwords
    $commonWords = file_get_contents('commonWords.txt');
    $commonWords = preg_replace("/\s+/", "\t", $commonWords);
    $commonWordsArray = preg_split("/\t+/", $commonWords);
    $uncommonWords = array_diff($contentTokens, $commonWordsArray);  // take difference of contentTokens & commonWords
    $keywords = $uncommonWords[0];
    for ($x = 1; $x < count($uncommonWords); $x++) {
        $keywords = $keywords . ", " . $uncommonWords[$x];
    }
    // echo $keywords . "<br>";

    
    // Connect to test db
    $conn = new mysqli("localhost", "root", "", "test");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Insert submission into DB
    $sql = "INSERT INTO posts (user, title, author, multiple_authors, year, body, keywords, upvotes) VALUES ('$username', '$title', '$author', '$multipleAuthors', '$year', '$body', '$keywords', '0')";
    // echo $sql;
    if ($conn->query($sql) === TRUE) {
        // echo "<p style='color:green;'>Post successful. Redirecting to dashboard...</p>";
        header("refresh: 3; url=dashboard.php");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
        
    $conn->close();

}
?>
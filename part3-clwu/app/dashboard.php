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
    <title>Dashboard</title>
</head>
<body>
    <h2>Dashboard</h2>

    <!-- Logout Button -->
    <form action="logout.php">
        <input type="submit" value="Logout">
    </form>

    <!-- Greet User + Stats + Badges -->
<?php
    $username = $_SESSION["username"];
    echo "<p>Welcome, " . $_SESSION["username"] . ".</p>";
    echo "Stats:" . "<br>";

    // Connect to test db
    $conn = new mysqli("localhost", "root", "", "test");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    // Count posts made by user
    $sqlCountPosts = "SELECT * FROM posts WHERE user = '$username'";
    $result = $conn->query($sqlCountPosts);
    $numPosts = 0;
    if ($result->num_rows > 0) {    // print all content
        while($row = $result->fetch_assoc()) {
            $numPosts++;
        }
    }
    echo "Posts Created: " . $numPosts . "  ";
    // Add link to badge if user has met stat criteria
    if ($numPosts >= 5) {
        echo "<a href='./badges/post-badge.png'>See Badge</a>";
    }

    echo "<br>";

    // Count tags made by user
    $numTags = 0;
    $sqlCountTags = "SELECT * FROM tags WHERE user = '$username'";
    $result = $conn->query($sqlCountTags);
    if ($result->num_rows > 0) {    // print all content
        while($row = $result->fetch_assoc()) {
            $numTags++;
        }
    }
    echo "Tags Given: " . $numTags . "  ";
    // Add link to badge if user has met stat criteria
    if ($numTags >= 5) {
        echo "<a href='./badges/tag-badge.png'>See Badge</a>";
    }
    
    echo "<br><br>";

?>

    <!-- Post Button -->
    <button onclick="document.location='send.php'">Post</button><br>

    <h3>Suggested</h3>
    <!-- Search Form -->
    <form action="search.php" method="GET">
        <input type="text" name="searchQuery" id="searchQuery">
        <input type="submit" value="Search">
    </form>
    <br>

    <!-- Display content in DB -->
<?php 
    // Connect to test db
    $conn = new mysqli("localhost", "root", "", "test");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get suggestions from DB
    $username = $_SESSION["username"];
    $suggestions = array();
    $sql = "SELECT * FROM suggestions WHERE user = '$username'";
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) {
        $suggestions = explode(", ", $row["posts"]);
    }

    // Get matching posts from DB
    if (count($suggestions) > 0) {
        foreach ($suggestions as $suggPost) {
            $sql = "SELECT * FROM posts WHERE post_id = '$suggPost'";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                // echo "Submitted by " . $row["user"] . ":<br>";
                echo "<b>" . $row["title"] . " by " . $row["author"] . " (" . $row["year"] . ")</b><br>";
                echo $row["body"] . "<br>";
                // Submit Tag Element: Sets this post_id to a hidden form element
                echo "<form action='' method='post'>
                <label for='tag'>Tag (1 per submit):</label>
                <input type='text' id='tagText' name='tagText'>
                <input type='hidden' value='tag' name='action'>
                <input type='hidden' value='" . $row["post_id"] . "' name='postID'>
                <input type='submit' value='Submit'>
                </form>";
                // Add Upvote Element
                echo "<form action='' method='post'>
                <input type='hidden' value='upvote' name='action'>
                <input type='hidden' value='" . $row["post_id"] . "' name='postID'>
                <input type='submit' value='Upvote'>
                </form>";
                // Add Favorite Element
                echo "<form action='' method='post'>
                <input type='hidden' value='favorite' name='action'>
                <input type='hidden' value='" . $row["post_id"] . "' name='postID'>
                <input type='submit' value='Favorite'>
                </form>";
                echo "<br>";
            }
        }
    } else {
        $sql = "SELECT * FROM posts ORDER BY post_id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {    // print all content
            while($row = $result->fetch_assoc()) {
                // echo "Submitted by " . $row["user"] . ":<br>";
                echo "<b>" . $row["title"] . " by " . $row["author"] . " (" . $row["year"] . ")</b><br>";
                echo $row["body"] . "<br>";
                // Submit Tag Element: Sets this post_id to a hidden form element
                echo "<form action='' method='post'>
                <label for='tag'>Tag (1 per submit):</label>
                <input type='text' id='tagText' name='tagText'>
                <input type='hidden' value='tag' name='action'>
                <input type='hidden' value='" . $row["post_id"] . "' name='postID'>
                <input type='submit' value='Submit'>
                </form>";
                // Add Upvote Element
                echo "<form action='' method='post'>
                <input type='hidden' value='upvote' name='action'>
                <input type='hidden' value='" . $row["post_id"] . "' name='postID'>
                <input type='submit' value='Upvote'>
                </form>";
                // Add Favorite Element
                echo "<form action='' method='post'>
                <input type='hidden' value='favorite' name='action'>
                <input type='hidden' value='" . $row["post_id"] . "' name='postID'>
                <input type='submit' value='Favorite'>
                </form>";
                echo "<br>";
            }
        }
    }

    // Add tag or favorite or upvote to DB
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Add Tag
        if ($_POST["action"] == "tag") {    // retrieve post_id & tag_text on Tag Submit
            $postID = $_POST["postID"];    // post_id is retrived from hidden form value
            $tagText = $_POST["tagText"];

            // Construct Tag INSERT query
            $username = $_SESSION["username"];
            $sqlTag = "INSERT INTO tags (post_id, user, tag_text) VALUES ('$postID', '$username', '$tagText')";
            if ($conn->query($sqlTag) === TRUE) {
                echo "<p style='color:green;'>Tag successfully added.</p>";
            } else {
                echo "Error: " . $sqlTag . "<br>" . $conn->error;
            }
        }

        // Add Upvote
        if ($_POST["action"] == "upvote") {
            $postID = $_POST["postID"];
            $username = $_SESSION["username"];
            $sqlUpvote = "UPDATE posts SET upvotes = upvotes+1 WHERE post_id = '$postID'";
            if ($conn->query($sqlUpvote) === TRUE) {
                echo "<p style='color:green;'>Upvote successfully added.</p>";
            } else {
                echo "Error: " . $sqlUpvote . "<br>" . $conn->error;
            }
        }

        // Add Favorite
        if ($_POST["action"] == "favorite") {
            $postID = $_POST["postID"];
            $username = $_SESSION["username"];
            $sqlFavorite = "INSERT INTO favorites (user, post_id) VALUES ('$username', '$postID')";
            if ($conn->query($sqlFavorite) === TRUE) {
                echo "<p style='color:green;'>Favorite successfully added.</p>";
            } else {
                echo "Error: " . $sqlFavorite . "<br>" . $conn->error;
            }
        }
    }

    $conn->close();
?>

</body>
</html>
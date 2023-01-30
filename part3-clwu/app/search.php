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
    <title>Search</title>
</head>
<body>

<h2>Search for a Poem or Short Story</h2>
<!-- Search Form -->
<form action="" method="GET">
    <input type="text" name="searchQuery" id="searchQuery">
    <input type="submit" value="Search">
</form>

<p>Search results for <?php echo $_GET["searchQuery"]; ?>: </p>

<?php
    error_reporting(E_ERROR | E_PARSE);
    $searchQuery = strtolower($_GET["searchQuery"]);    // get user's search query

    // Split query into tokens
    $queryTokens = explode(" ", $searchQuery);

    // Remove common words
    // Source: https://www.ranks.nl/stopwords
    $commonWords = file_get_contents('commonWords.txt');
    $commonWords = preg_replace("/\s+/", "\t", $commonWords);
    $commonWordsArray = preg_split("/\t+/", $commonWords);
    $uncommonWords = array_diff($queryTokens, $commonWordsArray);  // take difference of searchToken & commonWords
    $searchWords = array();
    foreach ($uncommonWords as $val) {
        array_push($searchWords, $val);
    }
    // print_r($searchWords);

    // Connect to test db
    $conn = new mysqli("localhost", "root", "", "test");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get user's favs
    $userFavs = array();    // holds user's fav posts
    $sqlGetFavs = "SELECT * FROM favorites";
    $result = $conn->query($sqlGetFavs);
    while ($row = $result->fetch_assoc()) {
        if ($row["user"] == $_SESSION["username"]) {
            array_push($userFavs, $row["post_id"]); // get all favorited posts
        }
    }

    // Build feature vector
    $userVector = array();  // holds userVector scores
    foreach ($userFavs as $val) {
        $sqlGetPosts = "SELECT * FROM posts WHERE post_id = '$val'";    // get fav post content
        $result = $conn->query($sqlGetPosts);
        while ($row = $result->fetch_assoc()) {
            $postKeywords = explode(", ", $row["keywords"]);
            foreach ($postKeywords as $word) {
                if (empty($word)) {}
                else if (array_key_exists($word, $userVector)) {    // if keyword exists, increment
                    $userVector[$word] += 1;
                } else {
                    $userVector[$word] = 1;
                }
            }
        }
    }

    $resultArray = array(); // holds final search results
    // If search query has uncommon words, conduct search
    if (count($searchWords) > 0) {
        // Search thru posts table, feed matching posts' post_ids into resultArray
        $sqlPosts = "SELECT * FROM posts WHERE 
                    title LIKE '%$searchWords[0]%'
                    AND author LIKE '%$searchWords[0]%'
                    AND year LIKE '%$searchWords[0]%'
                    AND body LIKE '%$searchWords[0]%'
                    ";
        if (count($searchWords) > 1) {
            foreach ($searchWords as $val) {
                $sqlPosts = $sqlPosts . "UNION SELECT * FROM posts WHERE 
                            title LIKE '%$val%'
                            AND author LIKE '%$val%'
                            AND year LIKE '%$val%'
                            AND body LIKE '%$val%'
                            ";
            }
        }
        $result = $conn->query($sqlPosts);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                array_push($resultArray, $row["post_id"]);
            }
        }
        
        $sqlPosts = "SELECT * FROM posts WHERE 
                    title LIKE '%$searchWords[0]%'
                    OR author LIKE '%$searchWords[0]%'
                    OR year LIKE '%$searchWords[0]%'
                    OR body LIKE '%$searchWords[0]%'
                    ";
        if (count($searchWords) > 1) {
            foreach ($searchWords as $val) {
                $sqlPosts = $sqlPosts . "UNION SELECT * FROM posts WHERE 
                            title LIKE '%$val%'
                            OR author LIKE '%$val%'
                            OR year LIKE '%$val%'
                            OR body LIKE '%$val%'
                            ";
            }
        }
        $result = $conn->query($sqlPosts);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                array_push($resultArray, $row["post_id"]);
            }
        }

        // Search thru tags table, feed matching posts' post_ids into resultArray
        $sqlTags = "SELECT * FROM tags WHERE tag_text LIKE '%$searchWords[0]%'";
        if (count($searchWords) > 1) {
            foreach ($searchWords as $val) {
                $sqlTags = $sqlTags . "UNION SELECT * FROM tags WHERE
                            tag_text LIKE '%$val%'
                            ";
            }
        }
        $result = $conn->query($sqlTags);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                array_push($resultArray, $row["post_id"]);
            }
        }

        array_unique($resultArray); // remove duplicate post_ids
        // print_r($resultArray);

        // Retrieve all matching results
        $sqlFinal = "SELECT * from posts WHERE post_id = ";
        if (count($resultArray) > 0) {
            $sqlFinal = $sqlFinal . $resultArray[0];
            
            if (count($resultArray) > 1) {
                for ($x = 1; $x < count($resultArray); $x++) {
                    $sqlFinal = $sqlFinal . " OR post_id = " . $resultArray[$x];
                }
            }
            $result = $conn->query($sqlFinal);  // all matching results

            // Sort results
            $postScores = array();
            while ($row = $result->fetch_assoc()) {
                $postScores[$row["post_id"]] = 0; // initialize this post's score
                $resultKeywords = explode(", ", $row["keywords"]);  // get result posts' keywords
                foreach ($resultKeywords as $word) {
                    if (array_key_exists($word, $userVector)) {
                        $postScores[$row["post_id"]] += $userVector[$word]; // score these keywords according to userVector
                    }
                }
            }
            arsort($postScores);    // sort on desc total score
            
            // // Scoring Output
            // foreach($postScores as $x => $x_value) {
            //     echo "Post=" . $x . ", Score=" . $x_value;
            //     echo "<br>";
            // }
            
            foreach (array_keys($postScores) as $post) {
                $sqlSorted = "SELECT * FROM posts WHERE post_id = '$post'";
                $result = $conn->query($sqlSorted);
                // Print sorted results
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // echo "Submitted by " . $row["user"] . ":<br>";
                        echo "<b>" . $row["title"] . " by " . $row["author"] . " (" . $row["year"] . ")</b><br>";
                        echo $row["body"] . "<br>";
                        // Submit Tag Element: Sets this post_id to a hidden form element
                        echo "<form action='' method='post'>
                        <label for='tag'>Tag (1 per submit):</label>
                        <input type='hidden' value='tag' name='action'>
                        <input type='text' id='tagText' name='tagText'>
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
        } else {
            echo "0 results.";
        }

    } else {
        echo "0 results.";
    }

    // Add tag or upvote or favorite to DB
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
<?php
// Connect to test db
$conn = new mysqli("localhost", "root", "", "test");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all post_ids
$allPostIDs = array();
$sql = "SELECT * FROM posts";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    array_push($allPostIDs, $row["post_id"]);
}

// Get ppl who favd each post
$sql = "SELECT * FROM favorites";
foreach ($allPostIDs as $postID) {  // each post
    $result = $conn->query($sql);
    $users = array();
    while ($row = $result->fetch_assoc()) {
        if ($row["post_id"] == $postID) {
            array_push($users, $row["user"]);   // store all users that favorited this post
        }
    }

    // nested loop, user i get all favs
    // foreach () user j ADD user i's favs to suggested posts
    // Dump in all other user favs into each other

    // At the end: 
    // foreach user 
    // get suggs -> unique, get faves and diff against current suggPosts to get final suggestions
    // set final sugg

    if (!empty($users)) {   // get all favorites of THESE users
        for ($i = 0; $i < count($users); $i++) {
            $sql2 = "SELECT * FROM favorites WHERE user = '$users[$i]'";
            $result2 = $conn->query($sql2);
            $favorites = array();
            while ($row = $result2->fetch_assoc()) {
                array_push($favorites, $row["post_id"]);
            }
            
            foreach ($users as $j) {    // insert fav posts as suggestions for each user, includes posts already favorited, includes the same post duplicated
                $favString = implode(", ", $favorites);
                $sql3 = "INSERT INTO suggestions (user, posts) VALUES ('$j', '$favString') ON DUPLICATE KEY UPDATE posts = CONCAT(posts, ', ', '$favString')";
                if ($conn->query($sql3) === TRUE) {
                    print "success";
                } else {
                    print "Error: " . $sql . " " . $conn->error;
                }
            }
        }   
    }

}

// clean up suggestions for all users
$sql = "SELECT * FROM suggestions";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $suggestions = explode(", ", $row["posts"]);
    $suggestions = array_unique($suggestions);  // eliminate duplicate post_ids
    $suggestions = array_filter($suggestions);

    $favorites = array();
    $user = $row["user"];
    $sql2 = "SELECT * FROM favorites WHERE user = '$user'";     // get favorites for each user
    $result2 = $conn->query($sql2);
    while ($row2 = $result2->fetch_assoc()) {
        array_push($favorites, $row2["post_id"]);
    }

    print $user . " ";
    print_r($favorites);

    $finalSuggestions = array_diff($suggestions, $favorites);   // take diff b/w current suggestions & favorites
    print_r($finalSuggestions);
    $finalSuggestionsString = implode(", ", $finalSuggestions);
    $sql3 = "UPDATE suggestions SET posts = '$finalSuggestionsString' WHERE user='$user'";
    if ($conn->query($sql3) === TRUE) {
        print "success";
    } else {
        print "Error: " . $sql . " " . $conn->error;
    }
}

?>
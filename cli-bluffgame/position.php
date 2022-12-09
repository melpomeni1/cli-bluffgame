<?php
session_start();
if(isset($_SESSION['loggedin']) && isset($_SESSION['game_joined_id'])){
    $game_id = "";
    if(isset($_SESSION['game_joined_id'])){
        $game_id = $_SESSION['game_joined_id'];
    }
    // DB Connection
    $conn = mysqli_connect('localhost', 'root', '', 'cli_bluffgame');
    if(!$conn){
        echo mysqli_error();
        exit;
    }

    $game_id = $_SESSION['game_joined_id'];
    $sql = "SELECT * FROM `games` WHERE id='$game_id ';";
    $result = mysqli_query($conn, $sql);
    $response = mysqli_fetch_assoc($result);
    if($response['positions'] == "" || $response['positions'] == "{}"){
        exit;
    }
    
    $positions = json_decode($response['positions']);
    if(isset($positions->{'first'})){
        $id = $positions->{'first'};
        $user = fetch_user($conn, $id, "id");
        $user_name = $user['username'];
        echo "First : ".$user_name;
        echo "<br>";
    }
    if(isset($positions->{'second'})){
        $id = $positions->{'second'};
        $user = fetch_user($conn, $id, "id");
        $user_name = $user['username'];
        echo "Second : ".$user_name;
        echo "<br>";
    }
    if(isset($positions->{'third'})){
        $id = $positions->{'third'};
        $user = fetch_user($conn, $id, "id");
        $user_name = $user['username'];
        echo "Third : ".$user_name;
        echo "<br>";
    }
    if(isset($positions->{'fourth'})){
        $id = $positions->{'fourth'};
        $user = fetch_user($conn, $id, "id");
        $user_name = $user['username'];
        echo "Fourth : ".$user_name;
        echo "<br>";
    }
}

// Fetch User Function
function fetch_user($conn, $param, $type)
{
    if($type == "id"){
        $sql = "SELECT * FROM `users` WHERE id='$param';";
    }
    if($type == "username"){
        $sql = "SELECT * FROM `users` WHERE username='$param';";
    }
    $result = mysqli_query($conn, $sql);
    $response = mysqli_fetch_assoc($result);
    return $response;
}
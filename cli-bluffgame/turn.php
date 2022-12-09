<?php
session_start();
// DB Connection
if(isset($_SESSION['game_joined_id'])){
    $conn = mysqli_connect('localhost', 'root', '', 'cli_bluffgame');
    if(!$conn){
        echo mysqli_error();
        exit;
    }
    
    $user_id = $_SESSION['userid'];
    $game_id = $_SESSION['game_joined_id'];
    $sql = "SELECT * FROM `games` WHERE id='$game_id';";
    $result = mysqli_query($conn, $sql);
    $response = mysqli_fetch_assoc($result);
    $turn = $response['turn'];
    if($turn == $user_id){
        echo "Its your turn!";
    }
}
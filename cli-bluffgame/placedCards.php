<?php
session_start();
// DB Connection
if(isset($_SESSION['game_joined_id'])){
    $conn = mysqli_connect('localhost', 'root', '', 'cli_bluffgame');
    if(!$conn){
        echo mysqli_error();
        exit;
    }

    $game_id = $_SESSION['game_joined_id'];
    $sql = "SELECT * FROM `games` WHERE id='$game_id';";
    $result = mysqli_query($conn, $sql);
    $response = mysqli_fetch_assoc($result);
    $game_state = json_decode($response['state']);
    if(gettype($game_state->card) != "integer"){
        $game_id = $_SESSION['game_joined_id'];
        $sql = "SELECT * FROM `games` WHERE id='$game_id';";
        $result = mysqli_query($conn, $sql);
        $response = mysqli_fetch_assoc($result);
        $thrown_cards = json_decode($response['thrown_cards']);
        if($thrown_cards != ""){
            if(gettype($thrown_cards) == "object"){
                $total_thrown_cards = 0;
                echo $total_thrown_cards;
            }else{
                $total_thrown_cards = sizeof($thrown_cards);
                echo $total_thrown_cards;
            }
        }
    }
}


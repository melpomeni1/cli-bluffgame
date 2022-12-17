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
    // Fetching Data
    $sql = "SELECT * FROM `games` WHERE id='$game_id'";
    $result = mysqli_query($conn, $sql);
    $data = "";
    $_SESSION['data'] = [];
    while($response = mysqli_fetch_assoc($result)){
        $state = json_decode($response['state']);
        $data .= '<p>'.$state->message.'</p>';
    }
    echo $data;

    $count_result = mysqli_query($conn, $sql);
    while($count_response = mysqli_fetch_assoc($count_result)){
        $total_num_players = json_decode($count_response['players']);
        $total_num_players = sizeof($total_num_players);
    }
    if($total_num_players == 4){
        $result2 = mysqli_query($conn, $sql);
        $response2 = mysqli_fetch_assoc($result2);
        $user_cards = json_decode($response2['state']);
        $test = $user_cards->card;
    }
}
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
    // Fetching Cards
    $sql = "SELECT * FROM `games` WHERE id='$game_id'";
    $count_result = mysqli_query($conn, $sql);
    while($count_response = mysqli_fetch_assoc($count_result)){
        $total_num_players = json_decode($count_response['players']);
        $total_num_players = sizeof($total_num_players);
    }

    $new_result = mysqli_query($conn, $sql);
    $new_response = mysqli_fetch_assoc($new_result);
    $new_state = json_decode($new_response['state']);
    if(gettype($new_state->card) != "integer"){
        $result2 = mysqli_query($conn, $sql);
        $response2 = mysqli_fetch_assoc($result2);
        $user_cards = json_decode($response2['state']);
        $test = $user_cards->card;
        echo '<pre>';
        $user_new_cards = $test->{$_SESSION['userid']};
        $cards = "";
        $total = "";
        echo "Note: Total numbers of cards are labeled in the brackets with the card names! <br>";
        foreach($user_new_cards as $card => $val){
            echo $card.'('.$val.'), ';
        }
        
    }
}
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

// $id = $_SESSION['game_joined_id'];
// $sql = "SELECT * FROM `games` WHERE id='$id';";
// $result = mysqli_query($conn, $sql);
// $response = mysqli_fetch_assoc($result);
// $game_cards = json_decode($response['thrown_cards']);
// $game_cards = get_object_vars($game_cards);
// $size = sizeof($game_cards);
// $a = get_object_vars($game_cards[$_SESSION['userid']]);
// $new_arr = [];
// foreach($game_cards as $item => $key){
//     $cards = get_object_vars($key);
//     foreach($cards as $card => $key){
//         array_push($new_arr, $card);
//     }
// }
// print_r($new_arr);
// $id = $_SESSION['game_joined_id'];
// $sql = "SELECT * FROM `games` WHERE id='$id';";
// $result = mysqli_query($conn, $sql);
// $response = mysqli_fetch_assoc($result);
// $cards = $response['thrown_cards'];
// $cards = json_encode(['king']);
// $cards = json_decode($cards);
// array_push($cards, 'kinggggg');
// print_r($cards);

           // $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
            // $thrown_cards = json_decode($game['thrown_cards']);
            // if(isset($thrown_cards->{$_SESSION['userid']})){
            //     if(isset($thrown_cards->{$_SESSION['userid']}->{$card})){
            //         $thrown_cards->{$_SESSION['userid']}->{$card} = $thrown_cards->{$_SESSION['userid']}->{$card} + 1;
            //         set_thrown_cards($conn, $_SESSION['game_joined_id'], json_encode($thrown_cards));
            //     }else{
            //         $thrown_cards->{$_SESSION['userid']}->{$card} = 1;
            //         set_thrown_cards($conn, $_SESSION['game_joined_id'], json_encode($thrown_cards));
            //     }
            // }else{
                
            //     if($thrown_cards == ""){
            //         $thrown_cards = json_encode([$_SESSION['userid'] => [$cards => 1]]);
            //         set_thrown_cards($conn, $_SESSION['game_joined_id'], $thrown_cards);
            //     }else{
            //         $thrown_cards = get_object_vars($thrown_cards);
            //         $thrown_cards[$_SESSION['userid']] = [$card => 1];
            //         set_thrown_cards($conn, $_SESSION['game_joined_id'] , json_encode($thrown_cards));
            //     }
            // }
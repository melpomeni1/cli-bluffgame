<?php
session_start();
// DB Connection
$conn = mysqli_connect('localhost', 'root', '', 'cli_bluffgame');
if(!$conn){
    echo mysqli_error();
    exit;
}
// Checking Commands
if(isset($_REQUEST['command'])){
    $command = $_REQUEST['command'];
    $params = "";
    if(isset($_SESSION['game_joined_id'])){
        $game_id = $_SESSION['game_joined_id'];
        $sql = "SELECT * FROM `games` WHERE id='$game_id';";
        $result = mysqli_query($conn, $sql);
        $response = mysqli_fetch_array($result);
        if($command == "logout"){
            session_destroy();
            logout();
        }
        if($response['status'] == 'closed'){
            exit;
        }
    }
    if(isset($_GET['params'])){
        $params = $_GET['params'];
    }
    $user_message = "";
    if(isset($_GET['message'])){
        $user_message = $_GET['message'];
    }
    // Login Command
    if($command == "login"){
        if(!isset($_SESSION['loggedin'])){
            login($params, $conn);
        }
    }
    // Logout Command
    if($command == "logout"){
        session_destroy();
        logout();
    }
    // Create Game Command
    if($command == "game_create"){
        if(!isset($_SESSION['game_created'])){
            game_create($conn, $_SESSION['userid']);
        }
    }
    // Close Game Command
    if($command == "game_leave"){
        if(isset($_SESSION['game_created']) || isset($_SESSION['game_joined_id'])){
            unset($_SESSION['game_created']);
            unset($_SESSION['game_joined_id']);
            echo "Game left successfully!";
        }
    }
    // Join Game Command
    if($command == "game_join"){
        if(isset($_SESSION['loggedin'])){
            if(!isset($_SESSION['game_joined_id'])){
                join_game($conn, $params, $_SESSION['userid']);
            }
        }else{
            echo "Please run the command (login) to login before joining game!";
        }
    }
    // Cards Command
    if($command == "card"){
        $turn = get_turn($conn, $_SESSION['game_joined_id']);
        if($turn == $_SESSION['userid']){
            if($_GET['params'] != ""){
                $card = $_GET['params'];
                $card = ucfirst($card);
                put_card($conn, $card, $user_message);
            }
        }
    }
    // Skip Turn Command
    if($command == "skip"){
        $turn = get_turn($conn, $_SESSION['game_joined_id']);
        if($turn == $_SESSION['userid']){
            skip_turn($conn);
        }
    }
    // Challenge Command
    if($command == "challenge"){
        $thorwn_cards = get_thorwn_cards($conn, $_SESSION['game_joined_id']);
        if($thorwn_cards == "{}"){
            exit;
        }
        $user_message = "";
        if(isset($_GET['message'])){
            $user_message = $_GET['message'];
        }
        $params = ucfirst($params);
        challenge($conn, $params, $user_message);
    }
    // Testing Command
    if($command == "test"){
        // set_previous_card($conn, $_SESSION['game_joined_id'], "ii");
    }
}

// Login Function
function login($params, $conn)
{
    $params = $_REQUEST['params'];
    $sql = "SELECT * FROM `users` WHERE username='$params';";
    $result = mysqli_query($conn, $sql);
    if($result){
        $rows = mysqli_num_rows($result);
        if($rows > 0){
            // If User Exist
            $response = mysqli_fetch_assoc($result);
            echo "Successfully loggedin: ".json_encode(['userid' => $response['id'], 'username' => $response['username']]);
            $_SESSION['loggedin'] = true;
            $_SESSION['userid'] = $response['id'];
            $_SESSION['username'] = $response['username'];
        }
        else{
            // If User Does Not Exist
            $user = "guest".rand(1000, 9999);
            if($params != ""){
                $user = $params;
            }
            $sql = "INSERT INTO `users` (`username`) VALUES ('$user');";
            $result = mysqli_query($conn, $sql);
            $sql2 = "SELECT * FROM `users` WHERE username='$user';";
            $result2 = mysqli_query($conn, $sql2);
            $response2 = mysqli_fetch_assoc($result2);
            $_SESSION['loggedin'] = true;
            $_SESSION['userid'] = $response2['id'];
            $_SESSION['username'] = $response2['username'];
            echo "Successfully loggedin: ".json_encode(['userid' => $response2['id'], 'username' => $response2['username']]);
        }
    }
}

// Logout Function
function logout()
{
    if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
        session_start();
        unset($_SESSION['loggedin']);
        echo "Successfully logged out!";
    }
}

// Game Creation Function
function game_create($conn, $id)
{
    $admin = fetch_user($conn, $id, "id");
    $admin_id = $admin['id'];
    $players = json_encode([$admin['id']]);
    $state = json_encode(['message' => 'waiting for other players...', 'card' => 52]);
    $joining_code = rand(1000, 9999);
    $sql = "INSERT INTO `games` (`players`, `admin`, `state`, `joining_code`) VALUES ('$players', '$admin_id', '$state', '$joining_code');";
    $result = mysqli_query($conn, $sql);
    $game = fetch_game($conn, $joining_code);
    $game_id = $game['id'];
    $sql2 = "UPDATE `users` SET game_id='$game_id' WHERE id=$admin_id;";
    $result2 = mysqli_query($conn, $sql2);
    $_SESSION['game_created'] = true;
    $_SESSION['game_joined_id'] = $game_id;
    echo "Game created: ".json_encode(['admin' => $admin['username'], 'state' => json_encode(['message' => 'waiting for other palyers...', 'card' => 52]), 'joining_code' => $joining_code]);
}

// Join Through Joining Code
function join_game($conn, $joining_code, $user_id)
{
    $game = fetch_game($conn, $joining_code);
    $players = json_decode($game['players']);
    $total = sizeof($players);
    if($total < 4){
        $game = fetch_game($conn, $joining_code);
        $game_id = $game['id'];
        $user = fetch_user($conn, $user_id, "id");
        $user_id = $user['id'];
        $players = json_decode($game['players']);
        $total_players = sizeof($players);
        array_push($players, $user_id);
        $new_players = json_encode($players);
        $state = json_decode($game['state']);
        $new_message = $state->message = $user['username']." has joined the game, waiting for another player...";
        $new_state = json_encode(['message' => $new_message, 'card' => $state->card]);
        $sql = "UPDATE `games` SET players='$new_players', state='$new_state' WHERE id='$game_id';";
        // print_r($new_players);
        $result = mysqli_query($conn, $sql);
        $game = fetch_game($conn, $joining_code);
        $players = json_decode($game['players']);
        $total_players = sizeof($players);
        if($total_players == 4){
            $game = fetch_game($conn, $joining_code);
            $players = json_decode($game['players']);
            $total_players = sizeof($players);
            // print_r($players);
            $input= [
                "King", "Jack", "Ace", "2", "Queen", "9", "9", "5", "3", "7", "Ace", "7", "5", "Queen",
                "3", "4", "6", "7", "Jack", "10", "3", "King", "2", "4", "8", "9", "Jack", "7",
                "8", "10", "6", "Ace", "10", "King", "10", "5", "9", "King", "6", "2", "Queen",
                "Queen", "3", "5", "4", "8", "2", "8", "Ace", "6", "4", "Jack"
            ];
        
            shuffle($input);
            $cards = array_chunk($input, 13);
            $card = [];
            for($i=0; $i<$total_players; $i++){
                
            $count_values = array();
            foreach ($cards[$i] as $a) {
                @$count_values[$a]++;
            }
                $card[$players[$i]] = $count_values;
            }
            $game = fetch_game($conn, $joining_code);
            // print_r($card);
            // Recognizing Admin of the Game
            $game_admin_id = $game['admin'];
            $admin_turn = fetch_user($conn, $game_admin_id, "id");
            $admin_turn_name = $admin_turn['username'];
            $admin_turn_id = $admin_turn['id'];
            $_SESSION['player_turn'] = $admin_turn_name;
            $_SESSION['player_turn_id'] = $admin_turn_id;
            echo $admin_turn_id;
            // 
            $state = json_decode($game['state']);
            $new_message = $state->message = $user['username'].' has joined the game, Game has been started. Its '.$_SESSION["player_turn"].' turn!';
            $new_state = json_encode(['message' => $new_message, 'card' => $card]);
            // print_r($new_state);
            $sql = "UPDATE `games` SET players='$new_players', state='$new_state', turn='$admin_turn_id' WHERE id='$game_id';";
            $result = mysqli_query($conn, $sql);
            $_SESSION['game_started'] = true;
        }
        $_SESSION['game_joined_id'] = $game_id;
        echo "Game joined successfully!";
    }else{
        echo "The game has reached its maximum number of players!";
    }
}

// Put Card Function
function put_card($conn, $card, $user_message)
{
    $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
    $game = fetch_game($conn, $game['joining_code']);
    $state = json_decode($game['state']);
    $user_cards = $state->card;
    $user_cards = $user_cards->{$_SESSION['userid']};
    foreach($user_cards as $cards => $value){
        // Managing Cards
        if($card == $cards){
            $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
            $thrown_cards = $game['thrown_cards'];
            if($thrown_cards == "{}" || $thrown_cards == ""){
                $thrown_cards = json_encode([$card]);
                set_thrown_cards($conn, $_SESSION['game_joined_id'], $thrown_cards);
            }else{
                $thrown_cards = json_decode($thrown_cards);
                array_push($thrown_cards, $card);
                $thrown_cards = json_encode($thrown_cards);
                set_thrown_cards($conn, $_SESSION['game_joined_id'], $thrown_cards);
            }

            $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
            $game_state = json_decode($game['state']);
            $game_cards = $game_state->card;

            if($game_cards->{$_SESSION['userid']}->{$card} == 1){
                unset($game_cards->{$_SESSION['userid']}->{$card});
            }else{
                $game_cards->{$_SESSION['userid']}->{$card} = $game_cards->{$_SESSION['userid']}->{$card} - 1;
            }
            $new_message = $game_state->message;
            $new_message = 
            $new_state = json_encode(['message' => $new_message, 'card' => $game_state->card]);
            set_state($conn, $_SESSION['game_joined_id'], $new_state);

            // Updating Previous Card
            set_previous_card($conn, $_SESSION['game_joined_id'], $card);

            // Updating Turns
            $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
            $game_players = $game['players'];
            $game_players = json_decode($game_players);
            foreach($game_players as $item => $key){
                $turn = get_turn($conn, $_SESSION['game_joined_id']);
                if($key == $turn){
                    $game_id = $_SESSION['game_joined_id'];
                    $sql = "SELECT * FROM `games` WHERE id='$game_id';";
                    $result = mysqli_query($conn, $sql);
                    $response = mysqli_fetch_assoc($result);
                    $check_players = json_decode($response['players']);
                    $check_total_players = sizeof($check_players);
                    $new_total_players = $check_total_players - 1;
                    if($item == $new_total_players){
                        $new_current_turn_index = $item - $new_total_players;
                        $new_current_turn = $game_players[$new_current_turn_index];
                        $current_turn = get_turn($conn, $_SESSION['game_joined_id']);
                        // setting previous player turn
                        set_previous_turn($conn, $_SESSION['game_joined_id'], $current_turn);
                        // setting previous player turn
                        set_turn($conn, $_SESSION['game_joined_id'], $new_current_turn);
                        break;
                    }else{
                        $new_current_turn_index = $item + 1;
                        $check_game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
                        $check_positions = json_decode($check_game['positions']);
                        $new_current_turn = $game_players[$new_current_turn_index];
                        $current_turn = get_turn($conn, $_SESSION['game_joined_id']);
                        // setting previous player turn
                        set_previous_turn($conn, $_SESSION['game_joined_id'], $current_turn);
                        // setting previous player turn
                        set_turn($conn, $_SESSION['game_joined_id'], $new_current_turn);
                        break;
                    }
                }
            }

            // Getting Previous User
            $previous_player_id = get_previous_turn($conn, $_SESSION['game_joined_id']);
            $previous_player = fetch_user($conn, $previous_player_id, "id");
            $previous_player_name = $previous_player['username'];
            // Getting Current user
            $current_player_id = get_turn($conn, $_SESSION['game_joined_id']);
            $current_player = fetch_user($conn, $current_player_id, "id");
            $current_player_name = $current_player['username'];
            // Updating State
            $state = get_state($conn, $_SESSION['game_joined_id']);
            $state = json_decode($state);
            $new_message = $previous_player_name." has played his card.<br>Its now ".$current_player_name." turn!";
            if($user_message != ""){
                $new_message = $previous_player_name." has played his card. <br>Message from ".$previous_player_name." : ".$user_message." <br>Its now ".$current_player_name." turn!";
            }
            $new_state = json_encode(['message' => $new_message, 'card' => $state->card]);
            set_state($conn, $_SESSION['game_joined_id'], $new_state);
        }
    }

    // Checking if the player has still any card
    $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
    $game_state = json_decode($game['state']);
    $game_cards = $game_state->card;
    $user_cards = $game_cards->{$_SESSION['userid']};
    $user_cards = get_object_vars($user_cards);
    $total_cards = 0;
    foreach($user_cards as $card => $key){
        $total_cards = $key + $total_cards;
    }
    
    // If player has no cards left
    if($total_cards == 0){
        $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
        $game_players = json_decode($game['players']);
        foreach($game_players as $player => $key){
            if($key == $_SESSION['userid']){
                unset($game_players[$player]);

                $new_players = [];
                foreach($game_players as $player => $key){
                    array_push($new_players, $key);
                }
                // Updating Players
                $new_players = json_encode($new_players);
                $game_id = $_SESSION['game_joined_id'];
                $sql = "UPDATE `games` SET players='$new_players' WHERE id='$game_id';";
                $result = mysqli_query($conn, $sql);
                // Checking Positions
                $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
                $game_positions = $game['positions'];
                if(gettype($game_positions) == "NULL" || $game_positions == "{}"){
                    // First Postion
                    $first_position = json_encode(['first' => $_SESSION['userid']]);
                    $sql = "UPDATE `games` SET positions='$first_position';";
                    $result = mysqli_query($conn, $sql);
                    // Updating State
                    $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
                    $game_state = json_decode($game['state']);
                    // Fetching the winning player
                    $user = fetch_user($conn, $_SESSION['userid'], "id");
                    $user_name = $user['username'];
                    // Updating state message
                    $game_message = $game_state->message."<br>Announcement : ".$user_name." has gained 1st position!";
                    $new_state = json_encode(['message' => $game_message, 'card' => $game_state->card]);
                    set_state($conn, $_SESSION['game_joined_id'], $new_state);
                    echo "Congratulations! You have gained first position.";
                    exit;
                }
                
                $game_positions = json_decode($game_positions);

                if(isset($game_positions->{'second'})){
                    // Third Position
                    $game_positions->{'third'} = $_SESSION['userid'];
                    $game_positions = json_encode($game_positions);
                    set_position($conn, $_SESSION['game_joined_id'], $game_positions);
                    // Updating State
                    $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
                    $game_state = json_decode($game['state']);
                    // Fetching the winning player
                    $user = fetch_user($conn, $_SESSION['userid'], "id");
                    $user_name = $user['username'];
                    // Updating state message
                    $game_message = $game_state->message."<br>Announcement : ".$user_name." has gained 3rd position!";
                    $new_state = json_encode(['message' => $game_message, 'card' => $game_state->card]);
                    set_state($conn, $_SESSION['game_joined_id'], $new_state);
                    echo "Congratulations! You have gained third position.";
                    // --------------------------Fourth Position---------------------------------------------
                    $game_positions = json_decode($game_positions);
                    $game_positions->{'fourth'} = $_SESSION['userid'];
                    $game_positions = json_encode($game_positions);
                    set_position($conn, $_SESSION['game_joined_id'], $game_positions);
                    // Updating State
                    $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
                    $game_state = json_decode($game['state']);
                    // Fetching the winning player
                    $previous_user = get_turn($conn, $_SESSION['game_joined_id']);
                    $user = fetch_user($conn, $previous_user, "id");
                    $user_name = $user['username'];
                    // Updating state message
                    $game_message = $game_state->message."<br>Announcement : ".$user_name." has gained 4th position!";
                    $new_state = json_encode(['message' => $game_message, 'card' => $game_state->card]);
                    set_state($conn, $_SESSION['game_joined_id'], $new_state);
                    $game_id = $_SESSION['game_joined_id'];
                    $sql = "UPDATE `games` SET status='closed' WHERE id='$game_id';";
                    $result = mysqli_query($conn, $sql);
                }else{
                    // Second Position
                    $game_positions->{'second'} = $_SESSION['userid'];
                    $game_positions = json_encode($game_positions);
                    set_position($conn, $_SESSION['game_joined_id'], $game_positions);
                    // Updating State
                    $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
                    $game_state = json_decode($game['state']);
                    // Fetching the winning player
                    $user = fetch_user($conn, $_SESSION['userid'], "id");
                    $user_name = $user['username'];
                    // Updating state message
                    $game_message = $game_state->message."<br>Announcement : ".$user_name." has gained 2nd position!";
                    $new_state = json_encode(['message' => $game_message, 'card' => $game_state->card]);
                    set_state($conn, $_SESSION['game_joined_id'], $new_state);
                    echo "Congratulations! You have gained second position.";
                }
            }
        }
    }

}
// Skip Turn Function
function skip_turn($conn)
{
    // Updating Turns
    $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
    $game_players = $game['players'];
    $game_players = json_decode($game_players);
    foreach($game_players as $item => $key){
        $turn = get_turn($conn, $_SESSION['game_joined_id']);
        if($key == $turn){
            if($item == 3){
                $new_current_turn_index = $item - 3;
                $new_current_turn = $game_players[$new_current_turn_index];
                $current_turn = get_turn($conn, $_SESSION['game_joined_id']);
                // setting previous player turn
                set_previous_turn($conn, $_SESSION['game_joined_id'], $current_turn);
                // setting previous player turn
                set_turn($conn, $_SESSION['game_joined_id'], $new_current_turn);
                break;
            }else{
                $new_current_turn_index = $item + 1;
                $new_current_turn = $game_players[$new_current_turn_index];
                $current_turn = get_turn($conn, $_SESSION['game_joined_id']);
                // setting previous player turn
                set_previous_turn($conn, $_SESSION['game_joined_id'], $current_turn);
                // setting previous player turn
                set_turn($conn, $_SESSION['game_joined_id'], $new_current_turn);
                break;
            }
        }
    }

    // Getting Previous User
    $previous_player_id = get_previous_turn($conn, $_SESSION['game_joined_id']);
    $previous_player = fetch_user($conn, $previous_player_id, "id");
    $previous_player_name = $previous_player['username'];

    // Getting Current user
    $current_player_id = get_turn($conn, $_SESSION['game_joined_id']);
    $current_player = fetch_user($conn, $current_player_id, "id");
    $current_player_name = $current_player['username'];

    // Updating State
    $state = get_state($conn, $_SESSION['game_joined_id']);
    $state = json_decode($state);
    $new_message = $previous_player_name." skipped his turn.<br>Its ".$current_player_name." turn!";
    $new_state = json_encode(['message' => $new_message, 'card' => $state->card]);
    set_state($conn, $_SESSION['game_joined_id'], $new_state);
}

// Challenge Function
function challenge($conn, $params, $user_message)
{
    $previous_card = get_previous_card($conn, $_SESSION['game_joined_id']);
    $previous_player = get_previous_turn($conn, $_SESSION['game_joined_id']);
    $current_player = get_turn($conn, $_SESSION['game_joined_id']);
    
    if($previous_card == $params){
        // Fetching All The Thrown Cards
        $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
        $thrown_cards = $game['thrown_cards'];
        $thrown_cards = json_decode($thrown_cards);
        $game_state = json_decode($game['state']);
        $game_cards = $game_state->card;
        // print_r($game_cards->{$previous_player});
        foreach($thrown_cards as $card){
            if(isset($game_cards->{$previous_player}->{$card})){
                $game_cards->{$previous_player}->{$card} = $game_cards->{$previous_player}->{$card} + 1;
            }else{
                $game_cards->{$previous_player}->{$card} = 1;
            }
        }
        // Getting Previous User
        $previous_player_id = get_previous_turn($conn, $_SESSION['game_joined_id']);
        $previous_player = fetch_user($conn, $previous_player_id, "id");
        $previous_player_name = $previous_player['username'];

        // Getting Current user
        $current_player_id = get_turn($conn, $_SESSION['game_joined_id']);
        $current_player = fetch_user($conn, $current_player_id, "id");
        $current_player_name = $current_player['username'];

        // Updating State
        $new_message = $current_player_name." has challenged ".$previous_player_name.".<br>Result : ".$current_player_name." has won the challenge against ".$previous_player_name.". All the card have been transfered to ".$previous_player_name."!<br>Its again ".$current_player_name." turn!";
        if($user_message != ""){
            $new_message = $current_player_name." has challenged ".$previous_player_name.".<br>Message from ".$current_player_name." : ".$user_message."<br>Result : ".$current_player_name." has won the challenge against ".$previous_player_name.". All the card have been transfered to ".$previous_player_name."!<br>Its again ".$current_player_name." turn!";
        }
        
        $new_state = json_encode(['message' => $new_message, 'card' => $game_cards]);
        set_state($conn, $_SESSION['game_joined_id'], $new_state);
        $thrown_cards = "{}";
        set_thrown_cards($conn, $_SESSION['game_joined_id'], $thrown_cards);
    }else{
        // Fetching All The Thrown Cards
        $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
        $thrown_cards = $game['thrown_cards'];
        $thrown_cards = json_decode($thrown_cards);
        $game_state = json_decode($game['state']);
        $game_cards = $game_state->card;
        // print_r($game_cards->{$previous_player});
        foreach($thrown_cards as $card){
            if(isset($game_cards->{$current_player}->{$card})){
                $game_cards->{$current_player}->{$card} = $game_cards->{$current_player}->{$card} + 1;
            }else{
                $game_cards->{$current_player}->{$card} = 1;
            }
        }

        // Getting Previous User
        $previous_player_id = get_previous_turn($conn, $_SESSION['game_joined_id']);
        $previous_player = fetch_user($conn, $previous_player_id, "id");
        $previous_player_name = $previous_player['username'];

        // Getting Current user
        $current_player_id = get_turn($conn, $_SESSION['game_joined_id']);
        $current_player = fetch_user($conn, $current_player_id, "id");
        $current_player_name = $current_player['username'];

        // Updating State
        $new_message = $current_player_name." has challenged ".$previous_player_name.".<br>Result : ".$previous_player_name." has won the challenge against ".$current_player_name.". All the card have been transfered to ".$current_player_name."!";
        if($user_message != ""){
            $new_message = $current_player_name." has challenged ".$previous_player_name.".<br>Message from ".$current_player_name." : ".$user_message."<br>Result : ".$previous_player_name." has won the challenge against ".$current_player_name.". All the card have been transfered to ".$current_player_name."!";
        }
        
        $new_state = json_encode(['message' => $new_message, 'card' => $game_cards]);
        set_state($conn, $_SESSION['game_joined_id'], $new_state);
        $thrown_cards = "{}";
        set_thrown_cards($conn, $_SESSION['game_joined_id'], $thrown_cards);

        
        // Updating Turn
        $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
        $game_players = $game['players'];
        $game_players = json_decode($game_players);
        foreach($game_players as $item => $key){
            $turn = get_turn($conn, $_SESSION['game_joined_id']);
            if($key == $turn){
                if($item == 3){
                    $new_current_turn_index = $item - 3;
                    $new_current_turn = $game_players[$new_current_turn_index];
                    $current_turn = get_turn($conn, $_SESSION['game_joined_id']);
                    // setting previous player turn
                    set_previous_turn($conn, $_SESSION['game_joined_id'], $current_turn);
                    // setting previous player turn
                    set_turn($conn, $_SESSION['game_joined_id'], $new_current_turn);
                    break;
                }else{
                    $new_current_turn_index = $item + 1;
                    $new_current_turn = $game_players[$new_current_turn_index];
                    $current_turn = get_turn($conn, $_SESSION['game_joined_id']);
                    // setting previous player turn
                    set_previous_turn($conn, $_SESSION['game_joined_id'], $current_turn);
                    // setting previous player turn
                    set_turn($conn, $_SESSION['game_joined_id'], $new_current_turn);
                    break;
                }
            }
        }

        // Updating State
        $current_player_id = get_turn($conn, $_SESSION['game_joined_id']);
        $current_player = fetch_user($conn, $current_player_id, "id");
        $current_player_name = $current_player['username'];
        $game = fetch_game_by_id($conn, $_SESSION['game_joined_id']);
        $game_state = json_decode($game['state']);
        $new_message = $game_state->message."<br>Its now ".$current_player_name." turn!";
        $new_state = json_encode(['message' => $new_message, 'card' => $game_state->card]);
        set_state($conn, $_SESSION['game_joined_id'], $new_state);
    }
}


// Helper Functions
    // User Fetch Funtion
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

    // Game Fetch Through Joining Code Funtion
    function fetch_game($conn, $joining_code)
    {
        $sql = "SELECT * FROM `games` WHERE joining_code='$joining_code';";
        $result = mysqli_query($conn, $sql);
        $response = mysqli_fetch_assoc($result);
        return $response;
    }

    // Game Fetch Through Id Funtion
    function fetch_game_by_id($conn, $id)
    {
        $sql = "SELECT * FROM `games` WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
        $response = mysqli_fetch_assoc($result);
        return $response;
    }

    // Get Turn Function
    function get_turn($conn, $id)
    {
        $sql = "SELECT * FROM `games` WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
        $response = mysqli_fetch_assoc($result);
        return $response['turn'];
    }

    // Get Previous Turn Function
    function get_previous_turn($conn, $id)
    {
        $sql = "SELECT * FROM `games` WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
        $response = mysqli_fetch_assoc($result);
        return $response['previous_turn'];
    }

    // Get Next Turn Function
    function get_next_turn($conn, $id)
    {
        $sql = "SELECT * FROM `games` WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
        $response = mysqli_fetch_assoc($result);
        return $response['next_turn'];
    }

    // Get Thrown Cards Function
    function get_thorwn_cards($conn, $id)
    {
        $sql = "SELECT * FROM `games` WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
        $response = mysqli_fetch_assoc($result);
        return $response['thrown_cards'];
    }

    // Get State Function
    function get_state($conn, $id)
    {
        $sql = "SELECT * FROM `games` WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
        $response = mysqli_fetch_assoc($result);
        return $response['state'];
    }

    // Get Previous Card
    function get_previous_card($conn, $id)
    {
        $sql = "SELECT * FROM `games` WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
        $response = mysqli_fetch_assoc($result);
        return $response['previous_card'];
    }

    // Set Turn Function
    function set_turn($conn, $id, $param)
    {
        $sql = "UPDATE `games` SET turn='$param' WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
    }

    // Set Previous Turn Function
    function set_previous_turn($conn, $id, $param)
    {
        
        $sql = "UPDATE `games` SET previous_turn='$param' WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
    }

    // Set Next Turn Function
    function set_next_turn($conn, $id, $param)
    {
        $sql = "UPDATE `games` SET next_turn='$param' WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
    }
    // Set Next Turn Function
    function set_thrown_cards($conn, $id, $param)
    {
        $sql = "UPDATE `games` SET thrown_cards='$param' WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
    }

    // Set State Function
    function set_state($conn, $id, $param)
    {
        $sql = "UPDATE `games` SET state='$param' WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
    }
    // Set State Function
    function set_previous_card($conn, $id, $param)
    {
        $sql = "UPDATE `games` SET previous_card='$param' WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
    }
    // Set Positions
    function set_position($conn, $id, $params){
        $sql = "UPDATE `games` SET positions='$params' WHERE id='$id';";
        $result = mysqli_query($conn, $sql);
    }
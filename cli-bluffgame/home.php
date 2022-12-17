<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <!-- Custom Css -->
    <link rel="stylesheet" href="style.css">
    <title>Bluff | Game</title>
</head>

<body>

    <div class="container border pt-3 text-white" style="min-height:100vh; background:black; over-flow:hidden;">
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <h4>Cli Bluff Game</h4>
                <label for="">Command</label> <br>
                <input type="text" name="command"> <br>
                <label for="">Parameters</label> <br>
                <input type="text" name="params"> <br>
                <button class="mt-2" type="submit" onclick="submit()">submit</button> <br>
            </div>
            <div class="col-md-6 col-sm-12 right-section">
                <h4>Positions</h4>
                <p class="mt-2" id="position-section">Player positions will be listed!</p>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6 col-sm-12">
                <h4>Cards</h4>
                <div class="result3">

                </div>
            </div>
            <div class="col-md-6 col-sm-12 right-section">
                <h4>Placed Cards</h4>
                <div class="placed-cards">

                </div>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6 col-sm-12">
                <h4>Game State</h4>
                <div class="result2">

                </div>
                <div class="result4">

                </div>
                <div class="rule-section">

                </div>
            </div>
            <div class="col-md-6 col-sm-12 right-section">
                <h4 class="mt-3">Response</h4>
                <div class="result">

                </div>
            </div>
        </div>

    </div>


    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- JQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <!-- Custom -->
    <script>
        // Main Game Modules Get and Set
        const submit = () => {
            let command = $("input[name='command']").val();
            let params = $("input[name='params']").val();
            let message = $("input[name='message']").val();

            var request = $.ajax({
                url: "index.php?command=" + command + "&&params=" + params + "&&message=" + message,
                type: "GET",
                //dataType: "json"
            });

            request.done(function(response) {
                console.log(response);
                $(".result:eq(0)").append("<p>" + response + "</p>");
            });

            request.fail(function(jqXHR, textStatus) {
                console.log("Request failed: " + textStatus);
            });
        }

        // Get Game State
        setInterval(() => {
            var request = $.ajax({
                url: "gameState.php",
                type: "GET",
                //dataType: "json"
            });

            request.done(function(response) {
                $(".result2:eq(0)").html(response);
            });

            request.fail(function(jqXHR, textStatus) {
                console.log("Request failed: " + textStatus);
            });
        }, 1000);

        // Get User Cards
        setInterval(() => {
            var request = $.ajax({
                url: "card.php",
                type: "GET",
                //dataType: "json"
            });

            request.done(function(response) {
                $(".result3:eq(0)").html(response);
            });

            request.fail(function(jqXHR, textStatus) {
                console.log("Request failed: " + textStatus);
            });
        }, 1000);

        // Get User Turn
        setInterval(() => {
            var request = $.ajax({
                url: "turn.php",
                type: "GET",
                //dataType: "json"
            });

            request.done(function(response) {
                $(".result4:eq(0)").html(response);
            });

            request.fail(function(jqXHR, textStatus) {
                console.log("Request failed: " + textStatus);
            });
        }, 1000);

        // Get Placed Cards
        setInterval(() => {
            var request = $.ajax({
                url: "placedCards.php",
                type: "GET",
                //dataType: "json"
            });

            request.done(function(response) {
                $(".placed-cards:eq(0)").html(response);
            });

            request.fail(function(jqXHR, textStatus) {
                console.log("Request failed: " + textStatus);
            });
        }, 1000);

        // Get Positions
        setInterval(() => {
            var request = $.ajax({
                url: "position.php",
                type: "GET",
                //dataType: "json"
            });

            request.done(function(response) {
                $("#position-section").html(response);
            });

            request.fail(function(jqXHR, textStatus) {
                console.log("Request failed: " + textStatus);
            });
        }, 1000);

        // Get Game Rule
        setInterval(() => {
            var request = $.ajax({
                url: "rule.php",
                type: "GET",
                //dataType: "json"
            });

            request.done(function(response) {
                $(".rule-section:eq(0)").html(response);
            });

            request.fail(function(jqXHR, textStatus) {
                console.log("Request failed: " + textStatus);
            });
        }, 1000);
    </script>
</body>

</html>
<?php
$user = [];
class WordGameController {
    
    private $command;

    public function __construct($command) {
        $this->command = $command;
    }

    public function run() {
        switch($this->command) {
            case "question":
                $this->question();
                break;
            case "logout":
                $this->destroyCookies();
            case "login":
            default:
                $this->login();
                break;
        }
    }

    // Clear all the cookies that we've set
    private function destroyCookies() {          
        setcookie("correct", "", time() - 3600);
        setcookie("name", "", time() - 3600);
        setcookie("email", "", time() - 3600);
        setcookie("score", "", time() - 3600);
    }
    

    // Display the login page (and handle login logic)
    public function login() {
        if (isset($_POST["email"]) && !empty($_POST["email"])) { /// validate the email coming in
            setcookie("name", $_POST["name"], time() + 3600);
            setcookie("email", $_POST["email"], time() + 3600);
            setcookie("score", 0, time() + 3600);
            header("Location: ?command=question");
            return;
        }

        include "login.php";
    }

    // Load a question from the API
    private function loadQuestion() {
        $wordVar = file_get_contents("wordlist.txt");
        $wordBank = explode("\n",$wordVar);
        $randIndex = rand(0, count($wordBank));

        $toGuess = $wordBank[$randIndex];
        $progress = "________";
        // for ($i = 0; $i <= strlen($toGuess); $i++)
        //     $progress .= "-";
        $question = [
            "question" => $progress,
            "correct_answer" => $toGuess
        ];
        
        return $question;
        // $triviaData = json_decode(
        //     file_get_contents("https://opentdb.com/api.php?amount=1&category=26&difficulty=easy&type=multiple")
        //     , true);
        // // Return the question
        // return $triviaData["results"][0];
    }

    // Display the question template (and handle question logic)
    public function question() {
        // set user information for the page from the cookie
        global $user; 
        $user = [
            "name" => $_COOKIE["name"],
            "score" => "0",
        ];
        if(!isset($user)){
            $user = 'Variable name is not set';
            }
        $user = [
            "name" => $_COOKIE["name"],
            "email" => $_COOKIE["email"],
            "score" => $_COOKIE["score"]
        ];

        // load the question
        $question = $this->loadQuestion();


        $guess;

        
        if ($question == null) {
            die("No questions available");
        }

        $message = "";
        $arr = [];

        // if the user submitted an answer, check it
        if (isset($_POST["answer"])) {
            $answer = $_POST["answer"];
            $arr[] = $answer;
            $guess = implode(', ', $arr);
            
            if ($_COOKIE["answer"] === $answer) {
                // user answered correctly -- perhaps we should also be better about how we
                // verify their answers, perhaps use strtolower() to compare lower case only.
                $message = "<div class='alert alert-success'><b>$answer</b> was correct!</div>";

                // Update the score
                $user["score"] += 10;  
                // Update the cookie: won't be available until next page load (stored on client)
                setcookie("score", $_COOKIE["score"] + 10, time() + 3600);
            } else { 

                $correct_position = 0;
                $in_word = false;
                $count_in_word = 0;
                $present = [];
                $present_implode = implode(", ", $present);

                // // say how many characters in their guess were in the correct position
                // for ($i = 0; $i <= strlen($answer); $i++) { // iterate through correct word
                //     for ($j = 0; $j <= strlen($_COOKIE["answer"]); $j++) {  // iterate through user guess
                        if ($answer[1] === $_COOKIE["answer"][1]) {
                            $in_word = true;
                        }
                    // }
                    if ($in_word) {
                        $count_in_word += 1;
                        $present[] = $answer[1];
                    }
                // }

                // compare guess character length to answer
                $length = "";
                if (strlen($_COOKIE["answer"]) === strlen($answer)) {
                    $length = "correct";
                }
                elseif (strlen($_COOKIE["answer"]) > strlen($answer)) {
                    $length = "too short";
                }
                elseif (strlen($_COOKIE["answer"]) < strlen($answer)) {
                    $length = "too long";
                }

                
                // $message = "<div class='alert alert-danger'><b>$answer</b> was incorrect! Your word length is <b>$length</b>!</div>";
                $message = "<div class='alert alert-danger'><b>$answer</b> was incorrect! The letters <b>$count_in_word</b> in your guess are present in the correct answer! Your word length is <b>$length</b>!</div>";
                // The answer was: {$_COOKIE["answer"]}
            }
            setcookie("correct", "", time() - 3600);
        }

        // update the question information in cookies
        setcookie("answer", $question["correct_answer"], time() + 3600);

        include("question.php");
    }
}
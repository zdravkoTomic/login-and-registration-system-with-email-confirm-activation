<?php

/* HELPER FUNCTIONS, SIMPLE FUNCTION USED ALOT*/
function clean($string)
{
    return htmlentities($string);
}

function redirect($location)
{
    header("Location: {$location}");
}

function setMessage($message)
{
    if(!empty($message)) {
        $_SESSION['message'] = $message;
    } else {
        $message = "";
    }
}

function displayMessage()
{
    if(isset($_SESSION['message'])) {
        echo $_SESSION['message'];
        unset($_SESSION['message']);
    }
}

function tokenGenerator()
{
    $token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));
    return $token;
}

function validationErrors($errorMessage)
{
    $errorMessage = <<<DELIMITER
    
                    <div class="alert alert-danger alert-dismissible" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <strong>Warning!</strong> $errorMessage
                </div>
DELIMITER;

    RETURN $errorMessage;
}

function emailExist($email)
{
    $sql = "SELECT id FROM users WHERE email = '$email'";

    $result = query($sql);

    if(rowCount($result) == 1) {
        return true;
    } else {
        return false;
    }
}

function usernameExist($username)
{
    $sql = "SELECT id FROM users WHERE username = '$username'";

    $result = query($sql);

    if(rowCount($result) == 1) {
        return true;
    } else {
        return false;
    }
}

function sendEmail($email, $subject, $msg, $headers)
{
    return mail($email, $subject, $msg, $headers);

}


/******* VALIDATION FUNCTION  */

function validateUserRegistration()
{

    $errors = [];
    $min = 3;
    $max = 20;

    if(isset($_POST['register-submit'])) {
        $firstName          = clean($_POST['first_name']);
        $lastName           = clean($_POST['last_name']);
        $username           = clean($_POST['username']);
        $email              = clean($_POST['email']);
        $password           = clean($_POST['password']);
        $confirmPassword    = clean($_POST['confirm_password']);

        if(strlen($firstName) < $min) {
            $errors[] = "Your first name can not be less than {$min} characters";
        }
        if(strlen($lastName) < $min) {
            $errors[] = "Your last name can not be less than {$min} characters";
        }
        if(strlen($username) < $min) {
            $errors[] = "Your username can not be less than {$min} characters";
        }
        if(strlen($firstName) > $max) {
            $errors[] = "Your first name can not be more than {$max} characters";
        }
        if(strlen($lastName) > $max) {
            $errors[] = "Your last name can not be more than {$max} characters";
        }
        if(strlen($username) > $max) {
            $errors[] = "Your username can not be more than {$max} characters";
        }
        if(usernameExist($username)){
            $errors[] = "Sorry that username already is registered";
        }
        if(emailExist($email)){
            $errors[] = "Sorry that email already is registered";
        }
        if(strlen($email) < $min) {
            $errors[] = "Your email can not be less than {$min} characters";
        }
        if($password != $confirmPassword) {
            $errors[] = "Your password fields do not match";
        }

        if(!empty($errors)){
            foreach($errors as $error) {
                echo validationErrors($error);
            }
        } else {
            if(register_user($firstName, $lastName, $username, $email, $password)){
                setMessage("<p class='bg-succes text-center'>
                                Please check your email or spam folder for an activation link
                            </p>");
                redirect("index.php");
            } else {
                setMessage("<p class='bg-succes text-center'>
                                Sorry we could not register the user
                            </p>");
                redirect("index.php");
            }
        }
    }
}


/*************************** REGISTER USER **************************/
function register_user($firstName, $lastName, $username, $email, $password)
{
    $firstName  = escape($firstName);
    $lastName   = escape($lastName);
    $username   = escape($username);
    $email      = escape($email);
    $password   = escape($password);

    if(emailExist($email)){
        return false;
    } else if(usernameExist($username)) {
        return false;
    } else {
        $password   = md5($password);
        $validationCode = md5($username, microtime());
        $sql = "INSERT INTO users(first_name, last_name, username, email, password, validation_code, active)
                VALUES ('$firstName', '$lastName', '$username', '$email', '$password', '$validationCode', 0)";
        $result = query($sql);
        confirm($result);

        $subject    = "Activate account";
        $msg        = " Please click the link below to activate your account
                http://localhost/login/activate.php?email=$email&code=$validationCode
        ";

        $headers = "From: noreply@yourwebsite.com";
        sendEmail($email, $subject, $msg, $headers);

        return true;
    }
}

/***********************ACTIVATE USER FUNCTION */

function activateUser()
{
    if($_SERVER['REQUEST_METHOD'] == "GET") {
        if($_GET['email']) {
            $email = $_GET['email'];
            $validationCode = $_GET['code'];

            $sql = "SELECT id 
                    FROM users 
                    WHERE email = '". escape($_GET['email'])."' AND validation_code = '". escape($_GET['code'])."'  ";

            $result = query($sql);
            confirm($result);

            if(rowCount($result) == 1) {

                $sql2 = "UPDATE users 
                        SET active = 1,
                            validation_code = 0
                        WHERE email = '$email'
                        AND validation_code = '$validationCode'";

                $result2 = query($sql2);
                confirm($result2);

                setMessage( "<p class='bg-succes'>
                       Your acount has been activated, please login 
                    </p>");

                redirect("login.php");
            } else {

                setMessage( "<p class='bg-danger'>
                       Sorry, your account could not be activated 
                    </p>");

                redirect("login.php");
            }
        }
    }
}

function validateUserLogin()
{

    $errors = [];
    $min = 3;
    $max = 20;

    if($_SERVER['REQUEST_METHOD'] == 'POST') {

        $email              = clean($_POST['email']);
        $password           = clean($_POST['password']);
        $remember           = isset($_POST['remember']) ? $_POST['remember'] : null;

        if(empty($email)) {
            $errors[] = 'Email field cannot be empty';
        }
        if(empty($password)) {
            $errors[] = 'password field cannot be empty';
        }

        if(!empty($errors)){
            foreach($errors as $error) {
                echo validationErrors($error);
            }
        } else {
            if(loginUser($email, $password, $remember)) {
                redirect("admin.php");
            } else {
                echo validationErrors("Your cedentials are not correct");
            }
        }
    }

}

/**************************  USER LOGIN FUNCTIONS */

function loginUser($email, $password, $remember)
{
    $sql = "SELECT password, id 
            FROM users
            WHERE email = '".escape($email)."'
            AND active = 1";

    $result = query($sql);

    if(rowCount($result) == 1) {

        $row = fetch_array($result);

        $dbPassword = $row['password'];

        if(md5($password) == $dbPassword) {

            if($remember == "on") {
                setcookie('email', $email, time() + 86400);
            }

            $_SESSION['email'] = $email;

            return true;
        } else {

            return false;
        }

        return true;
    } else {
        return false;
    }

}

/**************************  LOGGED IN FUNCTIONS *************************/

function logged_in()
{
    if(isset($_SESSION['email']) || isset($_COOKIE['email'])) {
        return true;
    } else {
        return false;
    }
}

/*********************** RECOVER PASSWORD *********************/

function recover_password()
{
    if($_SERVER['REQUEST_METHOD'] == "POST") {
        if(isset($_SESSION['token']) && $_POST['token'] == $_SESSION['token']) {

            $email = clean($_POST['email']);

            if(emailExist($email)) {

                $validationCode = md5($email);

                setcookie('temp_access_code', $validationCode, time()+ 22200);

                $sql = "UPDATE users SET validation_code = '".escape($validationCode)."' WHERE email = '".$email."' ";
                $result = query($sql);
                confirm($result);

                $subject = "Please reset your password";
                $message = " here is your password reset code: {$validationCode}
                
                Click here to reset your password http://localhost/code.php?email=$email&code=$validationCode
            
                ";

                $headers = "From: noreply@yourwebsite.com";
                if(!sendEmail($email, $subject, $message, $headers)) {
                    echo validationErrors("email can now be sent");
                }

                setMessage("<p class='bg-success'>Please check your email or spam folder for a passwword reset code</p>");
                redirect("index.php");
            } else {
                echo validationErrors("this email does not exists");
            }
        } else {
            redirect('index.php');
        }

        if(isset($_POST['cancel_submit'])){
            redirect("login.php");
        }

    }
}

/************************ CODE VALIDATION  ***********************/

function validationCode()
{
    if(isset($_COOKIE['temp_access_code'])) {

            if(!isset($_GET['email']) && !isset($_GET['code'])) {
                redirect("index.php");

            } else if(empty($_GET['email']) || empty($_GET['code'])){
                redirect("index.php");
            } else {
                if(isset($_POST['code'])){
                    $email = clean($_GET['email']);
                    $validationCode = clean($_POST['code']);

                    $sql = "SELECT id FROM users WHERE validation_code = '$validationCode' AND email = '$email'";
                    $result = query($sql);

                    confirm($result);

                    if(rowCount($result) == 1){
                        setcookie('temp_access_code', $validationCode, time()+ 11100);
                        redirect("reset.php?email=$email&code=$validationCode");
                    } else {
                        echo validationErrors("Sorry, wrong validation code");
                    }
                }
            }


    } else {
        setMessage("<p class='bg-danger'>Sorry, your validation cokie has expired</p>");
        redirect("recover.php");
    }
}

/************************ PASSWORD RESET FUNCTION ***********************/

function passwordReset()
{
    if (isset($_COOKIE['temp_access_code'])) {
        if (isset($_GET['email']) && isset($_GET['code'])) {

            if (isset($_SESSION['token']) && isset($_POST['token']))  {

                if($_POST['token'] == $_SESSION['token']){

                    if($_POST['password'] === $_POST['confirm_password']) {

                        $updatedPassword = md5($_POST['password']);

                        $sql = "UPDATE users SET password = '". escape($updatedPassword) ."', validation_code = 0 WHERE email = '". escape($_GET['email']) ."' ";
                        $result = query($sql);
                        confirm($result);

                        setMessage("<p class='bg-success'>Your password has been updated, please login</p>");

                        redirect("login.php");

                    }

                }
            }
        }
    } else {
        setMessage("<p class='bg-danger'>Sorry your time has expired</p>");
        redirect("recover.php");
    }
}
<?php

/*
 * API USAGE
 * ===
 *
 * In the following documentation you will find many entries called '%baseurl'.
 * You have to replace '%baseurl' with 'http://your_easy_club_url.com/account/auth.php' in your url.
 *
 * Normal Authentication (with username and password)
 * URL:
 * %baseurl?auth=true&auth_user=USERNAME&auth_password=PASSWORD_HASH
 * Response:
 * {"error": 0, "message": "Passed."} -> Valid account, valid password, logged in.
 * {"error": 1, "message": "Authentication failed. Wrong user.name or user.password."} -> On value is wrong, wrong username or wrong password
 *
 * Normal Authentication (with email and password)
 * URL:
 * %baseurl?auth=true&auth_emai=EMAIL&auth_password=PASSWORD_HASH
 * Response:
 * {"error": 0, "message": "Passed."} -> Valid account, valid password, logged in.
 * {"error": 1, "message": "Authentication failed. Wrong user.name or user.password."} -> On value is wrong, wrong email or wrong password
 *
 *
 *
 * Tokens:
 * ===
 *
 * Token creation
 * URL:
 * %baseurl?create_token=true&auth_user=USERNAME&auth_password=PASSWORD_HASH
 * Response:
 * {"error": 0, "message": "Success.", "token": "YOUR CREATED TOKEN"} -> Everything worked fine.
 * {"error": 1, "message": "MySQL Error: can`t connect!"} -> Intern MySQL error, contact support
 * {"error": 1, "message": "Authentication failed. Wrong user.name or user.password."} -> The value auth_user and/or auth_password is wrong.
 *
 * Token deletion:
 * URL:
 * %baseurl?delete_token=true&auth_user=USERNAME&auth_password=PASSWORD_HASH&token=TOKEN
 * Response:
 * {"error": 0, "message": "Deleted token."} -> Everything worked fine.
 * {"error": 1, "message": "Error while deleting token."} -> Intern MySQL error, contact support
 * {"error": 1, "message": "Your account is not valid!"} -> The account is not active or the url contains the wrong login creditianals
 * {"error": 1, "message": "Not permitted."} -> You do not have permission to do this.
 *
 * Getting data with tokens:
 * URL:
 * %baseurl?auth_method=token&data=%data&token=TOKEN
 * Instead of the %data section you have to enter the data you want to get.
 * Valid %data (separat with ','):
 * 'name' -> returns you the owner of the token.
 * Response:
 * {"error": 0, "response": {}} -> Everything worked fine.
 * {"error": 1, "message": "The token is not valid!"} -> Your token is not valid.
 *
 * Account Linking:
 * ===
 *
 * Discord Account
 * URL:
 * %baseurl?link_dc_account=true&auth_user=USERNAME&auth_password=PASSWORD_HASH&dc_account_name=DISCORD_ACC_NAME
 * Response:
 * {"error": 0, "message": "Linked accounts."} -> Everything worked fine
 * {"error": 1, "message": "Failed."} -> Failed. Contact dev@mctzock.de
 *
 *
 * In every part of the API which contains auth_user/auth_email and auth_password you can get this error:
 * {"error": 1, "message": "No value for user.name, user.email or user.password given."}
 * which means that your URL is not complete.
 *
 * ===
 * Copyright (c) 2020 Craftions.net, Ben Siebert. All rights reserved.
 */

$config = require('../config/database.php'); # This File does not exists! See ../config/info.php

// Headers for CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$pdo = new PDO('mysql:host='.$config->host.';dbname='. $config->database, $config->user, $config->password);

$sql = "SELECT * FROM accounts";

if(isset($_GET['create_account']) && $_GET['create_account'] == 'true'){
    if(isset($_GET['name']) && isset($_GET['password_hash']) && isset($_GET['email'])){
        $sql_q = "select * from accounts;";
        $stmt = $pdo->prepare($sql_q);
        $stmt->execute();
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ){
            if($row['name'] == $_GET['name']){
                die('{"error": 1, "message": "An account with that name already exitst!"}');
            }
        }
        $sql_s = 'insert into accounts (name, email, password_hash, active) values ("'.$_GET['name'].'", "'.$_GET['email'].'", "'.$_GET['password_hash'].'", "1")';
        if($pdo->exec($sql_s)){
            die('{"error": 0, "message": "Successful created new Account!"}');
        }else {
            die('{"error": 1, "message": "Intern MySQL error, contact support."}');
        }
    }else {
        die('{"error": 1, "message": "No value for user.name, user.email or user.password given."}');
    }
}

if(isset($_GET['delete_account']) && $_GET['delete_account'] == 'true'){
    if(isset($_GET['name']) && isset($_GET['password_hash'])){
        $sql = 'select * from accounts';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ){
            if($row['name'] == $_GET['name']){
                $sql_s = 'delete from accounts where name = "'.$_GET['name'].'";';
                $stmt_s = $pdo->prepare($sql_s);
                if($stmt_s->execute()){
                    die('{"error": 0, "message": "Successful deleted."}');
                }else{
                    die('{"error": 1, "message": "Intern MySQL error, contact support."}');
                }
            }
        }
        die('{"error": 1, "message": "User not found or wrong creditianals."}');
    }else {
        die('{"error": 1, "message": "No value for user.name, user.email or user.password given."}');
    }
}

# normal auth
if(isset($_GET['auth']) && $_GET['auth'] == 'true'){
    if(isset($_GET['auth_user']) && isset($_GET['auth_password'])){
        if(validateAccountByName($pdo)){
            die('{"error": 0, "message": "Passed."}');
        }else {
            die('{"error": 1, "message": "Authentication failed. Wrong user.name or user.password."}');
        }
    }else if(isset($_GET['auth_email']) && isset($_GET['auth_password'])){
        if(validateAccountByEmail($pdo)){
            die('{"error": 0, "message": "Passed."}');
        }else {
            die('{"error": 1, "message": "Authentication failed. Wrong user.name or user.password."}');
        }
    }else {
        die('{"error": 1, "message": "No value for user.name, user.email or user.password given."}');
    }
}
# create token
if(isset($_GET['create_token']) && $_GET['create_token'] == 'true'){
    if(isset($_GET['auth_user']) && isset($_GET['auth_password'])){
        if(validateAccountByName($pdo)){
            $tmp_b = false;
            $tk = "";
            while(!$tmp_b){
                $token = generateRandomString(256);
                $sql = "select * from account.tokens where 'token' = '".$token."'";
                $i = 0;
                foreach ($pdo->query($sql) as $row){
                    $i++;
                }
                if($i == 0){
                    $tmp_b = true;
                    $tk = $token;
                }

            }
            if($pdo->exec('INSERT INTO account.tokens (token, account_name) values ("'.$token.'", "'.$_GET['auth_user'].'")')){
                die('{"error": 0, "message": "Success.", "token": "'.$token.'"}');
            }else {
                die('{"error": 1, "message": "MySQL Error: can`t connect!"}');
            }
        }else {
            die('{"error": 1, "message": "Authentication failed. Wrong user.name or user.password."}');
        }
    }else {
        die('{"error": 1, "message": "No value for user.name, user.email or user.password given."}');
    }
}
# delete token
if(isset($_GET['delete_token']) && $_GET['delete_token'] == 'true' && isset($_GET['token'])){
    if(isset($_GET['auth_user']) && isset($_GET['auth_password'])){
        $sql = "select * from account.tokens where 'token' = '".$_GET['token']."' ";
        if($pdo->query($sql)){
            if(validateAccountByName($pdo)){
                $sql = "select * from account.tokens";
                $b = false;
                foreach ($pdo->query($sql) as $row){
                    if($row['account_name'] == $_GET['auth_user'] && $row['token'] == $_GET['token']){
                        $b = true;
                        $sql = "delete from account.tokens where token = '".$_GET['token']."';";
                        $stmt = $pdo->prepare($sql);
                        if($stmt->execute()){
                            die('{"error": 0, "message": "Deleted token."}');
                        }else {
                            die('{"error": 1, "message": "Error while deleting token."}');
                        }
                    }
                }
                if(!$b){
                    die('{"error": 1, "message": "Not permitted."}');
                }
            }else{
                die('{"error": 1, "message": "Your account is not valid!"}');
            }
        }else {
            die('{"error": 1, "message": "Not permitted."}');
        }
    }
}
# token login
if(isset($_GET['auth_method']) && $_GET['auth_method'] == 'token' && isset($_GET['data'], $_GET['token'])){
    # token validation
    if(validateToken($pdo, $_GET['token'])){
        # data split
        $data = explode(",", $_GET['data']);
        # parse data array
        $s = 0;
        echo '{"error": 0, "response": {';
        for( $i=0; $i < sizeof($data); $i++ ){
            if($data[$i] == 'name'){
                $sql = "select * from tokens";
                $result = $pdo->prepare($sql);
                $result->execute();
                while( $row = $result->fetch(PDO::FETCH_ASSOC) ){
                    if($_GET['token'] == $row['token']){
                        $name = $row['account_name'];
                        if($s == 0){
                            echo '"name": "'.$name.'"';
                        }else {
                            echo ',"name": "'.$name.'"';
                        }
                    }
                }
                $s++;
            }
        }
        echo '}}';
    }else {
        die('{"error": 1, "message": "The token is not valid!"}');
    }
}

function validateAccountByName($pdo){
    $sql = "SELECT * FROM accounts";
    $i=0;
    $result = $pdo->prepare($sql);
    $result->execute();
    while( $row = $result->fetch(PDO::FETCH_ASSOC) ){
        if($_GET['auth_user'] == $row['name'] && $_GET['auth_password'] == $row['password_hash']){
            $i=1;
        }
    }
    if($i==0){
        return false;
    }else {
        return true;
    }
}

function validateAccountByEmail($pdo){
    $sql = "SELECT * FROM accounts";
    $i=0;
    $result = $pdo->prepare($sql);
    $result->execute();
    while( $row = $result->fetch(PDO::FETCH_ASSOC)){
        if($_GET['auth_email'] == $row['email'] && $_GET['auth_password'] == $row['password_hash']){
            $i=1;
        }
    }
    if($i==0){
        return false;
    }else {
        return true;
    }
}

function validateToken($pdo, $token){
    $sql = "SELECT * FROM tokens";
    $i=0;
    $result = $pdo->prepare($sql);
    $result->execute();
    while( $row = $result->fetch(PDO::FETCH_ASSOC)){
        if($row['token'] == $token){
            $i++;
        }
    }
    if($i==0){
        return false;
    }else {
        return true;
    }
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
?>

<?php
require dirname(dirname(__FILE__)).'../config/RSA_DersJx8t8F.php';
function getPublickey(){
    return RSA_public;
}
function getPrivatekey(){
    return RSA_private;
}
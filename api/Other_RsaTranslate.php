<?php
function  RsaTranslate ($data,$type){
    if ($type=='encode') {
        $return=openssl_pkey_get_public(RSA_public);//检查公钥是否可用
        if(!＄return){
            echo("公钥不可用");
        }
        openssl_public_encrypt($data,$crypted,$return);//使用公钥加密数据
        $crypted=base64_encode($crypted);
        return $crypted;
    }
    if ($type=='decode') {
        $private_is_use=openssl_pkey_get_private(RSA_private);//检查私钥是否可用
        if(!$private_is_use){
            echo("私钥不可用");
        }
        //对私钥进行解密
        openssl_private_decrypt(base64_decode($data),$decrypted,$private_is_use);
        return($decrypted);
    }
}

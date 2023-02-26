<?php
/**
 * class名：JsonDisposalTool（JSON处理工具）
 * 简介：用于解析、加密、打包json的工具
 * 功能：
 * 1.解析json数据
 *  如果解析成功则返回解析后的数据(数组格式)
 *  如果解析失败则返回false
 * 2.打包数据
 *  如果打包成功则返回json(数组形式的)（字符串类型）
 *  如果失败则返回false
*/

class JsonDisposalTool
{
    /**解析json数据
     * @param $value string
     * @return false|mixed
     */
    function jsonParse($value=''){
        try {
            return json_decode($value,true);
        }catch (Exception $e){
            return false;
        }
    }
    /**打包数据
     * @param array $value
     * @return false|string
     */
    function jsonPack($value=[]){
        try {
            return json_encode($value,true);
        }catch (Exception $e){
            return false;
        }
    }
}
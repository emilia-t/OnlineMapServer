<?php
/**
class名：QualityInspectionRoom（质检间）-用于对用户上传的数据进行安全性检查
 *可选参数：
 * 1.feedback[true/false(default)]是否输出详细的错误报告而不是仅检查是否合格
 * 开启feedback后要想单独获取检测结果请使用  ...Check($value)->state 以获取
 *
 *简述：通用的数据检测工具
 *功能：
 * 1.json格式检测工具
 *  检测输入数据是否为正确的json格式
 *  如果能够解析则返回解析后的json对象
 *  否则返回false
 * 2.质检报告生成工具
 * 生成规范统一的质检报告，便于理解数据的真实情况(需要开启feedback)
 * 3.数组属性检测工具
 *  检测传入的数组是否存在某一个属性
 *  如果存在则返回该属性的值
 *  否则返回false
 * 4.数字数学检查
 *  检查输入是否为数字
 *  如果不是则返回false
 *  否则返回true
 * 4.检查数据格式并输出格式名称
 *  将检测的结果以字符串的格式返回
 *  如果检测失败则返回false
 * 5.检查16进制颜色格式(不可省略)
 *  若格式错误，则返回false
 *  若格式正确，则返回true
 * 6.检测普通键值格式(a-z0-9A-Z_\u4e00-\u9fa5),允许替换正则表达式
 *  若格式错误，则返回false
 *  若格式正确，则返回true
 * 7.检测非法字符([\[\]{}#`'"]|(-){2}|(\/){2}|(%){2}|\/\*),允许替换正则表达式
 *  若格式错误，则返回false
 *  若格式正确，则返回true
*/
class QualityInspectionRoom
{
    public $feedback=false;
    public function __construct($feedback=false){
        $this->feedback=$feedback;
    }
    /**json格式检测工具
     * @param $value
     * @return false|mixed
     */
    function jsonDecodeCheck($value) {
        $res = json_decode($value, true);
        $error = json_last_error();
        if (!empty($error)) {//出错
            if($this->feedback){
                $details=['description'=>'Json format error,error location:'."$error"];
                return $this->qualityInspectionReport('jsonDecodeCheck',false,$details);
            }else{
                return false;
            }
        }
        //没有错误
        return $res;
    }
    /**质检报告生成工具
     * @param $name string
     * @param $state bool
     * @param $details array
     * @return array
     */
    function qualityInspectionReport($name,$state,$details){
        return [
            'name'=>$name,
            'state'=>$state,
            'details'=>$details
        ];
    }
    /**数组属性检测工具
     * @param $key string
     * @param $array array
     * @return array|bool
     */
    function arrayPropertiesCheck($key,$array){
        if(!array_key_exists($key,$array)){
            if($this->feedback){
                $details=['description'=>'The key name cannot be found:'."$key"];
                return $this->qualityInspectionReport('arrayPropertiesCheck',false,$details);
            }else{
                return false;
            }
        }
        return true;
    }
    /**数字数学检查
     * @param $value string|int|bool|float
     * @return array|bool
     */
    function digitalCheck($value){
        if(!is_numeric($value)){
            if($this->feedback){
                $details=['description'=>'The value is not a numeric type:'."$value"];
                return $this->qualityInspectionReport('arrayPropertiesCheck',false,$details);
            }else{
                return false;
            }
        }
        return true;
    }
    /**检查数据格式并输出格式名称
     * @param $value
     * @return array|bool
     */
    function getDataType($value){
        try {
            return gettype($value);
        }catch (Exception $e){
            if($this->feedback){
                $details=['description'=>'Failed to detect data type'];
                return $this->qualityInspectionReport('arrayPropertiesCheck',false,$details);
            }else{
                return false;
            }
        }
    }
    /**检查16进制颜色格式
     * @param $value string
     * @return array|bool
     */
    function color16Check($value){
        try {
            $Exp="/^[0-9A-F]{6}$/i";
            if(!preg_match($Exp,$value)){
                if($this->feedback){
                    $details=['description'=>'Hex color format error:'.$value];
                    return $this->qualityInspectionReport('color16Check',false,$details);
                }else{
                    return false;
                }
            }
            return true;
        }catch (Exception $e){
            if($this->feedback){
                $details=['description'=>'Failed to detect hexadecimal color format'];
                return $this->qualityInspectionReport('color16Check',false,$details);
            }else{
                return false;
            }
        }
    }
    /**检测普通键值格式
     * @param string $value
     * @param string $Exp
     * @return array|bool
     */
    function commonKeyNameCheck($value,$Exp="/[^a-z0-9A-Z_\x{4e00}-\x{9fa5}]/su"){
        try{
            if(preg_match($Exp,$value)!==0){
                if($this->feedback){
                    $details=['description'=>'Common key name format error:'.$value];
                    return $this->qualityInspectionReport('commonKeyNameCheck',false,$details);
                }else{
                    return false;
                }
            }
            return true;
        }catch (Exception $e){
            if($this->feedback){
                $details=['description'=>'Failed to detect common key name,Exp:'.$Exp.'. value:'.$value];
                return $this->qualityInspectionReport('commonKeyNameCheck',false,$details);
            }else{
                return false;
            }
        }
    }
    /**检测非法字符
     * @param $value string
     * @param string $Exp
     * @return array|bool
     */
    function illegalCharacterCheck($value,$Exp="/[\[\]{}#`'\"]|(-){2}|(\/){2}|(%){2}|\/\*/m"){
        try{
            if(preg_match($Exp,$value)){
                if($this->feedback){
                    $details=['description'=>'Illegal characters detected:'.$value];
                    return $this->qualityInspectionReport('illegalCharacterCheck',false,$details);
                }else{
                    return false;
                }
            }
            return true;
        }catch (Exception $e){
            if($this->feedback){
                $details=['description'=>'Failed to detect illegal character,Exp:'.$Exp.'. value:'.$value];
                return $this->qualityInspectionReport('illegalCharacterCheck',false,$details);
            }else{
                return false;
            }
        }
    }

    /**检测图层的结构
     * @param $structure
     * @return void
     */
    function layerStructureCheck($structure){
        //1结构中不允许重复出现同一个index, 每个数组第一位必须为字符串，第二位必须为对象(不得为空数组)，其他位必须为数组或者数字
        //print_r($structure);//
    }

    /**检测图层的成员
     * @param $structure
     * @return void
     */
    function layerMembersCheck($structure){
        //1成员中不允许存在重复成员，所有成员均需要通过 格式检测 数字_数字
    }

    /**检测图层的结构以及成员
     * @param $structure
     * @return void
     */
    function layerStructureMembersCheck($structure){
        //通过layerStructureCheck和layerMembersCheck的质检的前提下，还要检测结构中是否存在index大于成员数量的
    }
}
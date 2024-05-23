<?php
/**图层数据的编辑类
 *
 *
 */
class LayerDataEdit
{
    private $language='english';
    private $layerData=[
        /*  以下为示例：
                23=>[//图层id作为键名
                    'id'=>23,//图层id
                    'type'=>'group',//图层类型
                    'members'=>[//图层成员
                        127=>1,//成员键名为元素id键值为元素类型1=point，2=line，3=area，4=curve
                        128=>1,
                    ],
                    'structure'=>[//图层结构数据
                        'name1',//图层名称
                        [//图层custom数据
                            'template'=>[//图层custom.template数据
                                //模板数据内容详细的太多不展示了
                            ],
                        ],
                        127,//图层的成员id
                        128,//图层的成员id
                    ],
                    'phase'=>1,//图层的生命周期0=>1=>2 | 初始=>存护=>删除
                    'hasChange'=>false,//图层修改状态，如果为true则会在renewalLayer阶段向数据库进行更新数据并在更新成功后修改为false
                ]
        */
    ];
    private $templateLink=[
        /*  以下为示例：
                'id12345678'=>[
                    'layerId'=>23,//此模板的所属图层id
                    'route'=>['name1','name2'],//如果是嵌套的分组图层则从前往后分别对应此模板的组名路径
                ],
        */
    ];
    private $typeNumber=[
        'point'=>1,
        'line'=>2,
        'area'=>3,
        'curve'=>4
    ];
    private $lastLayerId=0;
    private $layerDataCache=null;
    public function __construct($language){
        $this->language=$language;
    }
    /**
     * templateCheck模板校验工具__Version(1.0)
     * tmp conversion start
     **/
    /*
     * 字符串数据类型
     */
    function Text($str=''){
        $str=is_string($str)?$str:'';
        return '☍t'.$str;
    }
    function List_($str=''){
        $str=is_string($str)?$str:'';
        return '☍l'.$str;
    }
    function Date($str=''){
        $str=is_string($str)?$str:'';
        return '☍d'.$str;
    }
    function Time($str=''){
        $str=is_string($str)?$str:'';
        return '☍m'.$str;
    }
    function Datetime($str=''){
        $str=is_string($str)?$str:'';
        return '☍e'.$str;
    }
    function Percent($str=''){
        $str=is_string($str)?$str:'';
        return '☍p'.$str;
    }
    function initialData($type){//type string
          switch ($type){
              case 'text':return $this->Text();
              case 'list':return $this->List_();
              case 'percent':return $this->Percent();
              case 'datetime':return $this->Datetime();
              case 'date':return $this->Date();
              case 'time':return $this->Time();
              case 'number':return null;
              case 'bool':return false;
              default:return $this->Text();
          }
        }
    function GetContent($value){//获取数据的数据内容
          if(is_bool($value))return $value;
          if(is_int($value))return $value;
          if(is_float($value))return $value;
          return substr($value,2);
    }
    function GetType($value){//获取数据的数据类型
        if(is_bool($value))return 'bool';
        if(is_int($value))return 'number';
        if(is_float($value))return 'number';
        $tag=substr($value,0,2);
        if        ($tag==='☍t'){return 'text';}
        else if($tag==='☍l'){return 'list';}
        else if($tag==='☍d'){return 'date';}
        else if($tag==='☍m'){return 'time';}
        else if($tag==='☍e'){return 'datetime';}
        else if($tag==='☍p'){return 'percent';}
        else {return 'text';}//异常的没有数据符号的数据
    }
    /*
     * 转化相关函数
     */
    function datetimeToDate($datetime){
        if($this->isDatetime($datetime)){
            return substr($datetime,0,10);
        }else{
            return '';
        }
    }
    function datetimeToTime($datetime){
        if($this->isDatetime($datetime)){
            return substr($datetime,0,11);
        }else{
            return '';
        }
    }
    function dateToDatetime($date){
        if($this->isDate($date)){
            return $date.'T00:00:00';
        }else {
            return '';
        }
    }
    function dateToTime($date){
        if($this->isDate($date)){
            return '00:00:00';
        }else{
            return '';
        }
    }
    function timeToDatetime(){
        return '';
    }
    function timeToDate(){
        return '';
    }
    /**
     * 数据转化
     * @param $value | String|Number|Boolean|Null
     * @param $type | String
     * @return {Object,String,Number,Boolean,Null}
     **/
    function conversion($value,$type){
        /**
         * 特殊情况处理
         * (number类型的值可以为null)
         * 若要转化为字符串类型的数据则返回对应空值
         * 若要转化为number则返回源值
         * 若要转化为bool则返回false
         **/
        if($value===null){
            switch($type){
                case 'text':{return $this->Text();}
                case 'datetime':{return $this->Datetime();}
                case 'date':{return $this->Date();}
                case 'time':{return $this->Time();}
                case 'list':{return $this->List_();}
                case 'percent':{return $this->Percent();}
                case 'bool':{return false;}
                case 'number':{return $value;}
                default:{return $value;}
            }
        }
        /**
         * 数据类型检测
         **/
        $fail=['state'=>false,'message'=>'fail'];
        $jsTypes=['string','number','boolean'];
        $dtTypes=['text','datetime','date','time','list','percent','bool','number'];
        $jsType='';//旧数据的js数据类型
        if(is_bool($value)){$jsType='boolean';}
        elseif(is_int($value) || is_float($value)){$jsType='number';}
        elseif(is_string($value)){$jsType='string';}
        else{$jsType='unknown';}
        $oldType='';//源数据类型
        $tag='';//数据符号类型
        $content='';//数据内容
        if(!in_array($jsType,$jsTypes))return $fail;
        if(!in_array($type,$dtTypes))return $fail;
        if($jsType==='string'){
            $tag=mb_substr($value,0,2,'UTF-8');
            if        ($tag==='☍t'){$oldType='text';$content=mb_substr($value,2,null,'UTF-8');}
            else if($tag==='☍l'){$oldType='list';$content=mb_substr($value,2,null,'UTF-8');}
            else if($tag==='☍d'){$oldType='date';$content=mb_substr($value,2,null,'UTF-8');}
            else if($tag==='☍m'){$oldType='time';$content=mb_substr($value,2,null,'UTF-8');}
            else if($tag==='☍e'){$oldType='datetime';$content=mb_substr($value,2,null,'UTF-8');}
            else if($tag==='☍p'){$oldType='percent';$content=mb_substr($value,2,null,'UTF-8');}
            else {$oldType='text';$content=$value;}//异常的没有数据符号的字符串数据
        }
        else if($jsType==='number'){
            $oldType='number';
        }
        else if($jsType==='boolean'){
            $oldType='bool';
        }
        /**
         * 转化内容
         **/
        switch ($oldType){
            case 'text':{
                switch ($type){
                    case 'text':{
                        return $value;//返回原值
                    }
                    case 'datetime':{
                        if($this->isDatetime($content)){
                            return $this->Datetime($content);
                        }else{
                            return $this->Datetime();
                        }
                    }
                    case 'date':{
                        if($this->isDate($content)){
                            return $this->Date($content);
                        }else{
                            return $this->Date();
                        }
                    }
                    case 'time':{
                        if($this->isTime($content)){
                            return $this->Time($content);
                        }else{
                            return $this->Time();
                        }
                    }
                    case 'list':{
                        return $this->List_($content);//保留源内容
                    }
                    case 'percent':{
                        if($this->isPercent($content)){
                            return $this->Percent($content);//保留源内容
                        }
                        $number=$this->Number($content)*100;
                        if($this->isNumber($number)){
                            return $this->Percent($number.'%');
                        }
                        return $this->Percent('0%');
                    }
                    case 'bool':{
                        return $content === '1' || $content === '100%';
                    }
                    case 'number':{
                        if($this->isPercent($content)){
                            $number1=floatval($content)/100;//末尾包含%不能使用Number(content)
                            if($this->isNumber($number1)){
                                return $number1;
                            }else{
                                return 0;
                            }
                        }
                        $number2=$this->Number($content);
                        if($this->isNumber($number2)){
                            return $number2;
                        }
                        return 0;
                    }
                    default:{return $this->Text();}
                }
            }
            case 'datetime':{
                switch($type){
                    case 'text':{return $this->Text($content);}
                    case 'datetime':{return $value;}//返回源值
                    case 'date':{return $this->Date($this->datetimeToDate($content));}
                    case 'time':{return $this->Time($this->datetimeToTime($content));}
                    case 'list':{return $this->List_($content);}
                    case 'percent':{return $this->Percent();}
                    case 'bool':{return false;}
                    case 'number':{return 0;}
                    default:{return $this->Datetime();}
                }
            }
            case 'date':{
                switch($type){
                    case 'text':{return $this->Text($content);}
                    case 'datetime':{return $this->Datetime($this->dateToDatetime($content));}
                    case 'date':{return $value;}//返回源值
                    case 'time':{return $this->Time($this->dateToTime($content));}
                    case 'list':{return $this->List_($content);}
                    case 'percent':{return $this->Percent();}
                    case 'bool':{return false;}
                    case 'number':{return 0;}
                    default:{return $this->Date();}
                }
            }
            case 'time':{
                switch($type){
                    case 'text':{return $this->Text($content);}
                    case 'datetime':{return $this->Datetime($this->timeToDatetime());}
                    case 'date':{return $this->Date($this->timeToDate());}
                    case 'time':{return $value;}//返回源值
                    case 'list':{return $this->List_($content);}
                    case 'percent':{return $this->Percent();}
                    case 'bool':{return false;}
                    case 'number':{return 0;}
                    default:{return $this->Time();}
                }
            }
            case 'list':{
                switch ($type){
                    case 'text':{
                        return $this->Text($content);//保留源内容
                    }
                    case 'datetime':{
                        if($this->isDatetime($content)){
                            return $this->Datetime($content);
                        }else{
                            return $this->Datetime();
                        }
                    }
                    case 'date':{
                        if($this->isDate($content)){
                            return $this->Date($content);
                        }else{
                            return $this->Date();
                        }
                    }
                    case 'time':{
                        if($this->isTime($content)){
                            return $this->Time($content);
                        }else{
                            return $this->Time();
                        }
                    }
                    case 'list':{
                        return $value;//返回原值
                    }
                    case 'percent':{
                        if($this->isPercent($content)){
                            return $this->Percent($content);//保留源内容
                        }
                        $number=$this->Number($content)*100;
                        if($this->isNumber($number)){
                            return $this->Percent($number.'%');
                        }
                        return $this->Percent('0%');
                    }
                    case 'bool':{
                        return $content === '1' || $content === '100%';
                    }
                    case 'number':{
                        if($this->isPercent($content)){
                            $number1=floatval($content)/100;//末尾包含%不能使用Number(content)
                            if($this->isNumber($number1)){
                                return $number1;
                            }else{
                                return 0;
                            }
                        }
                        $number2=$this->Number($content);
                        if($this->isNumber($number2)){
                            return $number2;
                        }
                        return 0;
                    }
                    default:{return $this->List_();}
                  }
            }
            case 'percent':{
                switch($type){
                    case 'text':{return $this->Text($content);}
                    case 'datetime':{return $this->Datetime();}
                    case 'date':{return $this->Date();}
                    case 'time':{return $this->Time();}
                    case 'list':{return $this->List_($content);}
                    case 'percent':{return $value;}//返回源值
                    case 'bool':{return $content === '100%';}
                    case 'number':{
                        $number=floatval($content)/100;
                        if($this->isNumber($number)){
                            return $number;
                        }else {
                            return 0;
                        }
                    }
                    default:{return $this->Percent();}
                }
            }
            /**
             * number bool 无 tag 和 content 值
             **/
            case 'number':{//数字转其他类型
                /**
                 * 特殊情况
                 * NaN \ Infinity \ -Infinity
                 * 全部视作0
                 **/
                if(is_nan($value) || $value===INF || $value===-INF)$value=0;
                switch($type){
                    case 'text':{
                        return $this->Text($value.'');
                    }
                    case 'datetime':{
                        return $this->Datetime();
                    }
                    case 'date':{
                        return $this->Date();
                    }
                    case 'time':{
                        return $this->Time();
                    }
                    case 'list':{
                        return $this->List_($value.'');
                    }
                    case 'percent':{
                        $number=$value*100;
                        if($number!==INF){
                            return $this->Percent($number.'%');
                        }else {
                            return $this->Percent('0%');
                        }
                    }
                    case 'bool':{
                        return $value===1;
                    }
                    case 'number':{
                        return $value;
                    }
                    default:{return 0;}
                }
            }
            case 'bool':{//布尔转其他类型
                switch ($type){
                    case 'text':{
                        return $value?$this->Text('1'):$this->Text('0');
                    }
                    case 'datetime':{
                        return $this->Datetime();
                    }
                    case 'date':{
                        return $this->Date();
                    }
                    case 'time':{
                        return $this->Time();
                    }
                    case 'list':{
                        return $value?$this->List_('1'):$this->List_('0');
                    }
                    case 'percent':{
                        return $value?$this->Percent('100%'):$this->Percent('0%');
                    }
                    case 'number':{
                        return $value?1:0;
                    }
                    case 'bool':{
                        return $value;
                    }
                    default:{return false;}
                }
            }
        }
    }
    /*
     * check function 检查函数
     */
    function isValidTime($str){//检测字符串是否是时间格式-正确则返回true
        $reg = '/^☍m([0-1]?[0-9]|2[0-3]):([0-5]?[0-9])(:([0-5]?[0-9]))?$/';
        return preg_match($reg,$str)===1;
    }
    function isTime($str){//isValidTime的变体，不检查类型符号
        $reg = '/^([0-1]?[0-9]|2[0-3]):([0-5]?[0-9])(:([0-5]?[0-9]))?$/';
        return preg_match($reg,$str)===1;
    }
    function isValidDate($str){//检查一个字符串是否符合标准日期格式
        $reg = '/^☍d\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/';
        return preg_match($reg,$str)===1;
    }
    function isDate($str){//isValidDate的变体，不检查类型符号
        $reg = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/';
        return preg_match($reg,$str)===1;
    }
    function isValidDatetime($str){//检查一个字符串是否符合标准时间格式
        $reg = '/^☍e\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])T([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/';
        return preg_match($reg,$str)===1;
    }
    function isDatetime($str){//isValidDatetime的变体，不检查类型符号
        $reg = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])T([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/';
        return preg_match($reg,$str)===1;
    }
    function isAllowPercent($value){//检测字符串是否是百分数
        $reg = '/^☍p-?\d+(\.\d+)?%$/';
        return preg_match($reg,$value)===1;
    }
    function isPercent($value){//isAllowPercent的变体，不检查类型符号
        $reg = '/^-?\d+(\.\d+)?%$/';
        return preg_match($reg,$value)===1;
    }
    function isAllowList($value){//检测list字符串是否正确-正确则返回true
        $reg = '/^☍l(?!.*,.*,)(?=.*[^,]$)/';
        return preg_match($reg,$value)===1;
    }
    function isAllowId($id){//检测模板id是否正确-正确则返回true
        $reg = '/^[0-9a-zA-Z]{8,14}$/';
        return preg_match($reg,$id)===1;
    }
    function isIntegerP($value){//判断一个数字是否为正整数P:positive
        return is_int($value) && $value>0;
    }
    function isNumber($value){//判断一个值是否为数字(不包含无限和NaN)
        if(!is_int($value) && !is_float($value)){// 检查是否为数字
            return false;
        }
        if(is_nan($value) || is_infinite($value)){// 检查是否为无穷大或NaN
            return false;
        }
        return true;
    }
    function isColor16($color){//检测一个字符串是否是标准的16进制颜色-正确则返回true
        $regex='/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/';
        return preg_match($regex,$color)===1;
    }
    function isAllowValueTyp($type,$value){//依据type检测value(或default)是否是正确的type类型-正确则返回true
        switch ($type){
            case 'text':{
                return $this->typeof($value)==='string';
            }
            case 'number':{
                return $this->typeof($value)==='number';
            }
            case 'datetime':{
                if($this->typeof($value)!=='string')return false;
                return $this->isValidDatetime($value);
            }
            case 'data':{
                if($this->typeof($value)!=='string')return false;
                return $this->isValidDate($value);
            }
            case 'time':{
                if($this->typeof($value)!=='string')return false;
                return $this->isValidTime($value);
            }
            case 'bool':{
                return $this->typeof($value)==='boolean';
            }
            case 'list':{
                if($this->typeof($value)!=='string')return false;
                    return $this->isAllowList($value);
            }
            case 'percent':{
                if($this->typeof($value)!=='string')return false;
                    return $this->isAllowPercent($value);
            }
        }
    }
    function isAllowMethod($type,$method){//判断method是否为type允许使用的方法-正确则返回true
        switch ($type){
            case 'number':{
                return in_array($method,['equ','nequ','gre','greq','les','lesq','mod0','nmod0']);
            }
            case 'time':
            case 'date':
            case 'datetime':
            case 'percent':{
                return in_array($method,['equ','nequ','gre','greq','les','lesq']);
            }
            case 'list':
            case 'text':
            case 'bool':{
                return in_array($method,['equ','nequ']);
            }
            default:{
                return false;
            }
        }
    }
    function isAllowValueTypL($type,$value){//依据type检测value(或default)是否是type类型的数据以及长度是否合理-正确则返回true
        switch ($type){
            case 'text':{
                if($this->typeof($value)!=='string')return false;
                if(substr($value,0,2)!=='☍t')return false;
                return mb_strlen($value,'UTF-8') <= 2002;
            }
            case 'list':{
                if($this->typeof($value)!=='string')return false;
                return $this->isAllowList($value);
            }
            case 'date':{
                if($this->typeof($value)!=='string')return false;
                return $this->isValidDate($value);
            }
            case 'time':{
                if($this->typeof($value)!=='string')return false;
                return $this->isValidTime($value);
            }
            case 'datetime':{
                if($this->typeof($value)!=='string')return false;
                return $this->isValidDatetime($value);
            }
            case 'percent':{
                if($this->typeof($value)!=='string')return false;
                return $this->isAllowPercent($value);
            }
            case 'number':{
                if($this->typeof($value)!=='number')return false;
                return !($value===INF || $value===-INF) ;
            }
            case 'bool':{
                return $this->typeof($value) === 'boolean';
            }
        }
    }
    function isDetailsType($str){//检测str是否为模板属性规定以内的类型-正确则返回true
        return in_array($str,['text','number','datetime','date','time','bool','list','percent']);
    }
    function isNameDetails($obj){//检测是否obj是默认的name属性-正确则返回true
        $obj1=[
            'set'=>false,
            'name'=>'name',
            'default'=>'☍tunknown',
            'type'=>'text'
          ];
        $keys1 = array_keys($obj1);
        $keys2 = array_keys($obj);
        if (count($keys1) !== count($keys2)) {
            return false;
        }
        foreach ($keys1 as $key) {
            if (!array_key_exists($key, $obj) || $obj1[$key] !== $obj[$key]) {
                return false;
            }
        }
        return true;
    }
    /**isAllowBasis检查某个规则依据是否正确
     * @param $name string
     * @param $type string
     * @param $details array
     * @return boolean
     */
    function isAllowBasis($name,$type,$details){
        $len=count($details);
        for($i=0;$i<$len;$i++){
            if($name===$details[$i]['name'] && $type===$details[$i]['type']){
                return true;
            }
        }
        return false;
    }
    function codeExplain($code){//错误代码的解释
        $english=$this->language==='english';
        $A=$code%100;//在第N项
        $B=($code-$A);
        switch ($B){
            case 500:{return $english?'Template is null':'模板为空';}
            case 1000:{return $english?'Template is not an object':'模板不是一个对象';}
            case 2000:{return $english?'Template missing id attribute':'模板缺失id属性';}//A layer property check
            case 2100:{return $english?'Template id value type error':'模板id值类型错误';}
            case 2200:{return $english?'Template id value cannot be empty':'模板id值不能为空字符';}
            case 2300:{return $english?'Template id does not comply with regulations':'模板id值不符合标准';}

            case 4000:{return $english?'Template missing name attribute':'模板缺失name属性';}
            case 4100:{return $english?'Template name value type error':'模板名称值类型错误';}
            case 4200:{return $english?'Template name value cannot be empty':'模板名称值不能为空字符';}

            case 6000:{return $english?'Template missing creator attribute':'模板缺失creator属性';}
            case 6100:{return $english?'Template creator value type error':'模板创建者值类型错误';}
            case 6200:{return $english?'Template creator value cannot be empty':'模板创建者值不能为空字符';}

            case 8000:{return $english?'Template missing modify attribute':'模板缺少modify属性';}
            case 8100:{return $english?'Template modify value type error':'模板编辑日期值类型错误';}
            case 8200:{return $english?'Template modify value cannot be empty':'模板编辑日期值不能为空';}
            case 8300:{return $english?'Template modify does not comply with regulations':'模板编辑日期值不符合标准';}

            case 10000: { return $english ? 'Template missing locked attribute' : '模板缺失locked属性'; }
            case 10100: { return $english ? 'Template locked value type error' : '模板locked值类型错误'; }

            case 12000: { return $english ? 'Template missing explain attribute' : '模板缺失explain属性'; }
            case 12100: { return $english ? 'Template explain value type error' : '模板描述信息值类型错误'; }

            case 14000: { return $english ? 'Template missing typeRule attribute' : '模板缺失typeRule属性'; } //typeRule property check
            case 15000: { return $english ? 'Template typeRule is not an object' : '模板typeRule不是一个对象'; }

            case 16000: { return $english ? 'TypeRule missing point attribute' : '类型规则缺失point属性'; }
            case 17000: { return $english ? 'TypeRule point value type error' : '类型规则point值类型错误'; }

            case 18000: { return $english ? 'TypeRule missing line attribute' : '类型规则缺失line属性'; }
            case 19000: { return $english ? 'TypeRule line value type error' : '类型规则line值类型错误'; }

            case 20000: { return $english ? 'TypeRule missing area attribute' : '类型规则缺失area属性'; }
            case 21000: { return $english ? 'TypeRule area value type error' : '类型规则area值类型错误'; }

            case 22000: { return $english ? 'TypeRule missing curve attribute' : '类型规则缺失curve属性'; }
            case 23000: { return $english ? 'TypeRule curve value type error' : '类型规则curve值类型错误'; }
            case 24000: { return $english ? 'TypeRule at least one must be allowed' : '类型规则至少需要允许一个'; }

            case 30000: { return $english ? 'Template missing detailsRule attribute' : '模板缺失detailsRule属性'; } //detailsRule property check
            case 30100: { return $english ? 'Template detailsRule is not an array' : '模板detailsRule不是一个数组'; }
            case 30200: { return $english ? 'Template detailsRule length cannot = 0' : '模板detailsRule长度不能为0'; }
            case 30300: { return $english ? 'Template detailsRule length cannot > 90' : '模板detailsRule长度不能大于90'; }
            case 30400: { return $english ? 'Template detailsRule first element type error' : '模板detailsRule第一个元素类型错误'; }
            case 30500: { return $english ? 'Template detailsRule first element value error' : '模板detailsRule第一个元素值错误'; }

            case 31000: { return $english ? 'Template detailsRule type error in:' . $A . ' item' : '模板属性规则类型错误，在：' . $A . '项'; }
            case 32000: { return $english ? 'DetailsRule missing set attribute in:' . $A . ' item' : '属性规则缺失set属性，在：' . $A . '项'; }
            case 32100: { return $english ? 'DetailsRule set value type error in:' . $A . ' item' : '属性规则set值类型错误，在：' . $A . '项'; }

            case 33000: { return $english ? 'DetailsRule missing name attribute in:' . $A . ' item' : '属性规则缺失name属性，在：' . $A . '项'; }
            case 33100: { return $english ? 'DetailsRule name value type error in:' . $A . ' item' : '属性规则name值类型错误，在：' . $A . '项'; }
            case 33200: { return $english ? 'DetailsRule name value cannot be empty in:' . $A . ' item' : '属性规则name值不能为空，在：' . $A . '项'; }
            case 33300: { return $english ? 'DetailsRule name value length cannot > 40 in:' . $A . ' item' : '属性规则name值长度不能大于40，在：' . $A . '项'; }
            case 33400: { return $english ? 'DetailsRule name cannot duplicated:'.$A.' item': '属性规则name值不能重复，在：' . $A . '项';}


            case 35000: { return $english ? 'DetailsRule missing type attribute in:' .$A. ' item' : '属性规则缺失type属性，在：' .$A. '项'; }
            case 35100: { return $english ? 'DetailsRule type value type error in:' .$A. ' item' : '属性规则type值类型错误，在：' .$A. '项'; }
            case 35200: { return $english ? 'DetailsRule type value undefined in:' .$A. ' item' : '属性规则type值未定义，在：' .$A. '项'; }


            case 37000: { return $english ? 'DetailsRule missing default attribute in:' .$A. ' item' : '属性规则缺失default属性，在：' .$A. '项'; }
            case 37100: { return $english ? 'DetailsRule default value type error in:' .$A. ' item' : '属性规则default值类型错误，在：' .$A. '项'; }

            case 40000: { return $english ? 'Template missing colorRule attribute' : '模板缺失colorRule属性'; } //colorRule property check
            case 40100: { return $english ? 'Template colorRule is not an object' : '模板颜色规则不是一个对象'; }
            case 40200: { return $english ? 'ColorRule missing basis attribute' : '颜色规则缺失basis属性'; }
            case 40300: { return $english ? 'ColorRule basis value type error' : '颜色规则basis值类型错误'; }
            case 40400: { return $english ? 'ColorRule missing type attribute' : '颜色规则缺失type属性'; }
            case 40500: { return $english ? 'ColorRule type value type error' : '颜色规则type值类型错误'; }
            case 40600: { return $english ? 'The attribute on which the color rule is based is invalid' : '颜色规则所依据的属性无效'; }
            case 40700: { return $english ? 'ColorRule missing condition attribute' : '颜色规则缺失condition属性'; }
            case 40800: { return $english ? 'ColorRule condition is not an array' : '颜色规则condition不是一个数组'; }
            case 40900: { return $english ? 'If the basis is empty, the type must be empty' : '如果basis为空，则type必须为空'; }
            case 41000: { return $english ? 'If the basis is empty, the condition must be an empty array' : '如果basis为空，则condition必须是一个空数组'; }
            case 41100: { return $english ? 'If the basis is not empty, the type cannot be empty' : '如果basis不为空，则type不能为空'; }
            case 41200: { return $english ? 'ColorRule type value not allowed' : '颜色规则type值不允许'; }
            case 41300: { return $english ? 'ColorRule item length cannot > 90' : '颜色规则数量不能大于90'; }

            case 42000: { return $english ? 'ColorRule item is not an object in:' .$A. ' item' : '此条颜色规则不是一个对象，在：' .$A. '项'; }
            case 42100: { return $english ? 'ColorRule item missing set attribute in:' .$A. ' item' : '此条颜色规则缺失set属性，在：' .$A. '项'; }
            case 42200: { return $english ? 'ColorRule item set value type error in:'.$A.' item': '此条颜色规则set值类型错误，在：' .$A. '项';}

            case 43000: { return $english ? 'ColorRule item missing color attribute in:' .$A. ' item' : '此条颜色规则缺失color属性，在：' .$A. '项'; }
            case 43100: { return $english ? 'ColorRule item color value type error in:' .$A. ' item' : '此条颜色规则color值类型错误，在：' .$A. '项'; }
            case 43200: { return $english ? 'ColorRule item color value format error in:' .$A. ' item' : '此条颜色规则color值格式错误，在：' .$A. '项'; }

            case 44000: { return $english ? 'ColorRule item missing method attribute in:' .$A. ' item' : '此条颜色规则缺失method属性，在：' .$A. '项'; }
            case 44100: { return $english ? 'ColorRule item method value type error in:' .$A. ' item' : '此条颜色规则的method值类型错误，在：' .$A. '项'; }
            case 44200: { return $english ? 'ColorRule item method not allowed in:' .$A. ' item' : '此条颜色规则的method不合理，在：' .$A. '项'; }

            case 45000: { return $english ? 'ColorRule item missing value attribute in:' .$A. ' item' : '此条颜色规则缺失value属性，在：' .$A. '项'; }
            case 45100: { return $english ? 'ColorRule item value type not allowed in:' .$A. ' item' : '此条颜色规则的value类型不合理，在：' .$A. '项'; }
            case 45200: { return $english ? 'ColorRule item value length cannot > 100 in:' .$A. ' item' : '此条颜色规则的value字符长度不能大于100，在：' .$A. '项'; }

            case 50000: { return $english ? 'Template missing widthRule attribute' : '模板缺失widthRule属性'; } //widthRule property check
            case 50100: { return $english ? 'Template widthRule is not an object' : '模板widthRule不是一个对象'; }
            case 50200: { return $english ? 'WidthRule missing basis attribute' : '宽度规则缺失basis属性'; }
            case 50300: { return $english ? 'WidthRule basis value type error' : '宽度规则basis值类型错误'; }
            case 50400: { return $english ? 'WidthRule missing type attribute' : '宽度规则缺失type属性'; }
            case 50500: { return $english ? 'WidthRule type value type error' : '宽度规则type值类型错误'; }
            case 50600: { return $english ? 'The attribute on which the width rule is based is invalid' : '宽度规则所依据的属性无效'; }
            case 50700: { return $english ? 'WidthRule missing condition attribute' : '宽度规则缺失condition属性'; }
            case 50800: { return $english ? 'WidthRule condition is not an array' : '宽度规则condition不是一个数组'; }
            case 50900: { return $english ? 'If the basis is empty, the type must be empty' : '宽度规则中，如果basis为空，则type必须为空'; }
            case 51000: { return $english ? 'If the basis is empty, the condition must be an empty array' : '宽度规则中，如果basis为空，则condition必须是一个空数组'; }
            case 51100: { return $english ? 'If the basis is not empty, the type cannot be empty' : '宽度规则中，如果basis不为空，则type不能为空'; }
            case 51200: { return $english ? 'WidthRule type value not allowed' : '宽度规则type值不合理'; }
            case 51300: { return $english ? 'WidthRule item length cannot > 90' : '宽度规则数量不能大于90'; }

            case 52000: { return $english ? 'WidthRule item is not an object in:' .$A. ' item' : '此条宽度规则不是一个对象，在：' .$A. '项'; }
            case 52100: { return $english ? 'WidthRule item missing set attribute in:' .$A. ' item' : '此条宽度规则缺失set属性，在：' .$A. '项'; }
            case 52200: { return $english ? 'WidthRule item set value type error in:'.$A.' item' : '此条宽度规则set值类型错误，在：' .$A. '项';}

            case 53000: { return $english ? 'WidthRule item missing width attribute in:' .$A. ' item' : '此条宽度规则缺失width属性，在：' .$A. '项'; }
            case 53100: { return $english ? 'WidthRule item width value type error in:' .$A. ' item' : '此条宽度规则width值类型错误，在：' .$A. '项'; }
            case 53200: { return $english ? 'WidthRule item width value must be integer in:' .$A. ' item' : '此条宽度规则width值必须为整数，在：' .$A. '项'; }

            case 54000: { return $english ? 'WidthRule item missing method attribute in:' .$A. ' item' : '此条宽度规则缺失method属性，在：' .$A. '项'; }
            case 54100: { return $english ? 'WidthRule item method value type error in:' .$A. ' item' : '此条宽度规则的method值类型错误，在：' .$A. '项'; }
            case 54200: { return $english ? 'WidthRule item method not allowed in:' .$A. ' item' : '此条宽度规则的method不合理，在：' .$A. '项'; }

            case 55000: { return $english ? 'WidthRule item missing value attribute in:' .$A. ' item' : '此条宽度规则缺失value属性，在：' .$A. '项'; }
            case 55100: { return $english ? 'WidthRule item value type not allowed in:' .$A. ' item' : '此条宽度规则的value类型不合理，在：' .$A. '项'; }
            case 55200: { return $english ? 'WidthRule item value length cannot > 100 in:' .$A. ' item' : '此条宽度规则的value字符长度不能大于100，在：' .$A. '项'; }
        }
    }
    /**
     * 模板检查
     * @param $template | array
     * @return {boolean,number}
     */
    function tpCheck($template){

    }
    /**
     * tmp conversion end
     **/
    function randomNumber6(){
        $min = 100000;$max = 999999;return mt_rand($min, $max);
    }
    function createTemplateId(){
        $validChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $length = mt_rand(8, 14); // 生成一个介于 8 到 14 之间的随机数
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $byte = random_int(0, 255); // 生成一个介于 0 到 255 之间的随机数
            $result .= $validChars[$byte % strlen($validChars)];
        }
        return $result;
    }
    function getFormattedDate(){
        $now = new DateTime();
        $year = $now->format('Y');
        $month = $now->format('m');
        $day = $now->format('d');
        $hours = $now->format('H');
        $minutes = $now->format('i');
        $seconds = $now->format('s');
        return "{$year}-{$month}-{$day}T{$hours}:{$minutes}:{$seconds}";
    }
    function layerNameCheck($name){//检查名称是否重复-重复则返回false
        foreach($this->layerData as $key=>$value){
            if($value['type']==='order'){continue;}//order图层跳过
            if($value['phase']!=1){continue;}//如果此图层已经被删除则跳过判断
            if($value['structure'][0]==$name){return false;}//如果图层名称一致则返回false
        }
        return true;
    }

    /**更新缓存
     * @return void
     */
    function updateCache(){
        $filePath='./cache/layerDataCache.json';
        if(file_exists($filePath)){//检查是否存在文件
            $json=[];
            foreach($this->layerData as $key=>$item){
                if($item['hasChange']){$json[$key]=$item;}
            }
            if(count($json)===0){
                $status=file_put_contents($filePath,'');//清空缓存
            }else{
                $JSON=json_encode($json,true);
                $status=file_put_contents($filePath,$JSON);//更新缓存
                echo $status?"\nupdate layer cache succeed.\n":"\nupdate layer cache fail.\n";
            }
        }else{
            echo "\nupdate layer cache fail, Because the cache file does not exist.\n";
        }
    }

    /**重命名图层名称
     * @param $id | number
     * @param $name | string
     * @return bool
     */
    function renameLayer($id,$name){
        if(!is_numeric($id))return false;
        if(!is_string($name))return false;
        if(array_key_exists($id,$this->layerData)){
            $old=$this->layerData[$id]['structure'][0];
            if($old===$name){//新名称与旧名称重复
                return false;
            }
            elseif(!$this->layerNameCheck($name)){//新名称与其他图层名称重复
                return false;
            }
            else{//检查通过重命名
                $this->layerData[$id]['structure'][0]=$name;
                $this->layerData[$id]['hasChange']=true;//修改状态
                $this->updateCache();
                return true;
            }
        }else{
            return false;
        }
    }

    /**删除一个分组图层以及其成员-phase更新为2
     * @param $id | number
     * @return bool | array [id,members,order]
     */
    function deleteLayerAndMembers($id){
        global $newMDBE;
        if(array_key_exists($id,$this->layerData)){
            if($this->layerData[$id]['type']==='order'){//如果要删除的图层是order图层则返回false
                return false;
            }else{
                $this->layerData[$id]['phase']=2;//修改生命周期
                $this->layerData[$id]['hasChange']=true;//修改状态
                $members=[];
                foreach($this->layerData[$id]['members'] as $key=>$value){//循环遍历删除图层包含的元素
                    $newMDBE->updateElementPhase($key,2);
                    $members[$key]=$value;
                }
                $this->removeOrderMember($id);
                $order=$this->getOrderMembers();
                $this->updateCache();
                return [
                    'id'=>$id,
                    'members'=>$members,
                    'order'=>$order
                ];
            }
        }else{
            return false;
        }
    }

    /**创建一个分组图层
     * @param $creator
     * @return array
     */
    function createGroupLayer($creator){
        global $newQIR,$newJDT;
        $layerId=++$this->lastLayerId;
        $layerType='group';
        $layerMembers=['0'=>0];
        $layerPhase=1;

        $str='layer ';
        $number=0;
        do{$number=$this->randomNumber6();}
        while(!$this->layerNameCheck($str.$number));
        $layerName=$str.$number;
        $tmpId='';
        do{$tmpId=$this->createTemplateId();}
        while($newQIR->arrayPropertiesCheck($tmpId, $this->templateLink));
        $time=$this->getFormattedDate();
        $template=[
            'id'=>$tmpId,
            'name'=>'template',
            'creator'=>$creator,
            'modify'=>$time,
            'locked'=>false,
            'explain'=>'none',
            'typeRule'=>[
                'point'=>true,
                'line'=>true,
                'area'=>true,
                'curve'=>true
            ],
            'detailsRule'=>[
                [
                    'set'=>false,
                    'name'=>'name',
                    'default'=>'☍tunknown',
                    'type'=>'text'
                ]
            ],
            'colorRule'=>[
                'basis'=>'',
                'type'=>'',
                'condition'=>[]
            ],
            'widthRule'=>[
                'basis'=>'',
                'type'=>'',
                'condition'=>[]
            ]
        ];
        $layerStructure=[
            $layerName,
            [
                'template'=>$template
            ]
        ];

        $templateLink=[
            "$tmpId"=>[
                "layerId"=>$layerId,
                "route"=>[$layerName]
            ]
        ];
        $layerData=[
            $layerId=>[
                'id'=>$layerId,
                'type'=>$layerType,
                'members'=>$layerMembers,
                'structure'=>$layerStructure,
                'phase'=>$layerPhase,
                'hasChange'=>true//初始创建时此属性为true
            ]
        ];
        $this->templateLink[$tmpId]=$templateLink[$tmpId];//新建模板链接
        $this->layerData[$layerId]=$layerData[$layerId];//新建图层数据
        $this->addOrderMember($layerId);//更新排序图层

        $groupLayer=['id'=>$layerId,'type'=>'group','phase'=>1];//要返回的新增图层数据
        $groupLayer['members']=$newJDT->btoa($newJDT->jsonPack($layerData[$layerId]['members']));
        $groupLayer['structure']=$newJDT->btoa($newJDT->jsonPack($layerData[$layerId]['structure']));
        $orderLayer=$this->getOrderMembers();
        $this->updateCache();
        return [
            "groupLayer"=>$groupLayer,
            "orderLayer"=>$orderLayer
        ];
    }

    /**依据属性规则转化元素的属性
     * @param $details array
     * @param $detailsRule array
     * @return array | bool 转化失败或无变化则返回false否则返回新的属性
     */
    function detailsTransform($details,$detailsRule){
        $ref=[];
        $attrListR=[];//规则中允许的属性
        $attrListS=[];//源属性中的属性
        $hasRemove=false;
        $hasAppend=false;
        foreach($detailsRule as $rule){//获取属性规则允许的属性列表
            array_push($attrListR,$rule['name']);
        }
        /*
         * 删除规则中不存在的属性
         */
        foreach($details as $index=>$detail){
            if(!in_array($detail['key'],$attrListR)){
                unset($details[$index]);
                $hasRemove=true;
            }
        }
        if($hasRemove){
            $details=array_values($details);//重新索引数组
        }
        /*
         * 增加源属性未拥有的属性
         */
        foreach($details as $detail){//获取源属性拥有的属性列表
            array_push($attrListS,$detail['key']);
        }
        foreach($detailsRule as $rule){
            if(!in_array($rule['name'],$attrListS)){
                $newDetail=[
                    'key'=>$rule['name'],
                    'value'=>$rule['default']
                ];
                array_push($details,$newDetail);
                $hasAppend=true;
            }
        }
        /*
         *  $details:            [ [key=>value]  ...   ]
         *  $detailsRule:    [ [set , name , type , default ]  ... ]
         */
        /*
         * 属性值类型转化
         */
        foreach($details as $detail){
            $newType=null;
            $newValue=null;
            foreach($detailsRule as $rule){
                if($rule['name']===$detail['key']){
                    $newType=$rule['type'];
                }
            }
            if($newType!==null){
                $newValue=$this->conversion($detail['value'],$newType);
            }
        }
        return $ref;
    }

    /**调整数组排序
     * @param $array
     * @param $elementA
     * @param $elementB
     * @param $method
     * @return array | bool 如果调整失败或者无变化则返回false
     */
    function arrayReorder($array,$elementA,$elementB,$method){
        $indexA=array_search($elementA,$array);// 查找 $elementA 和 $elementB 的索引
        $indexB=array_search($elementB,$array);
        if($indexA===false || $indexB===false){// 如果任意一个元素不存在于数组中，返回false
            return false;
        }
        if($method==='up'){
            if($indexB!==2){//用于判断B的前面是否是A
                if($array[$indexB-1]!==$elementA){
                    array_splice($array,$indexA,1);// 移除 $elementA 元素
                    $indexB=array_search($elementB,$array);//重新搜索
                    array_splice($array,$indexB,0,$elementA);// 插入到 $elementB 的前面
                }else{//由于B的前面就是A所以无变化
                    return false;
                }
            }else{//将A插入到最前面
                array_splice($array,$indexA,1);// 移除 $elementA 元素
                $indexB=array_search($elementB,$array);//重新搜索
                array_splice($array,$indexB,0,$elementA);// 插入到 $elementB 的前面
            }
        }elseif($method==='down'){
            if($indexB!==(count($array)-1)){//用于判断B的后面是否是A
                if($array[$indexB+1]!==$elementA){
                    array_splice($array,$indexA,1);// 移除 $elementA 元素
                    $indexB=array_search($elementB,$array);//重新搜索
                    array_splice($array,$indexB + 1,0, $elementA);// 插入到 $elementB 的后面
                }else{//由于B的后面就是A所以无变化
                    return false;
                }
            }else{//将A插入到最后面
                array_splice($array,$indexA,1);// 移除 $elementA 元素
                $indexB=array_search($elementB,$array);//重新搜索
                array_splice($array,$indexB + 1, 0,$elementA);// 插入到 $elementB 的后面
            }
        }
        return $array;
    }

    /**调整元素排序
     * @param $elementA
     * @param $elementB
     * @param $templateA
     * @param $templateB
     * @param $method
     * @return array Affected layers id
     */
    function adjustElementOrder($elementA,$elementB,$templateA,$templateB,$method){
        global $newMDBE;
        $ref=[
            'layers'=>[],//变动的图层id
            'elements'=>[]//变动的元素id
        ];
        $layerA=null;
        $layerB=null;
        if(array_key_exists($templateA,$this->templateLink)){
        $layerA=$this->templateLink[$templateA]['layerId'];
        }else{return [];}
        if(array_key_exists($templateB,$this->templateLink)){
        $layerB=$this->templateLink[$templateB]['layerId'];
        }else{return [];}
        $cross=$layerA===$layerB;
        if($cross){//本组内进行调整顺序
            $newStructure=$this->arrayReorder($this->layerData[$layerA]['structure'],$elementA,$elementB,$method);
            if($newStructure!==false){
                $this->layerData[$layerA]['structure']=$newStructure;
                $this->layerData[$layerA]['hasChange']=true;//修改状态
                array_push($ref['layers'],$layerA);//变动图层
            }
        }else{//跨组进行调整顺序
            //1.需要考虑B组的模板（允许的类型（前端先设置阻碍后端二次检查）、属性规则（后端进行变更））
            if($method==='join'){//A element leave the A layer then A element join to B layer on the top
                $bLayerId=null;//int
                $bTemplateData=null;//array
                $aElementData=null;//array
                $aDetails=null;//array
                $aType=null;//array
                if(array_key_exists($templateB,$this->templateLink)){
                    $bLayerId=$this->templateLink[$templateB]['layerId'];
                    $bTemplateData=$this->layerData[$bLayerId]['structure'][1]['template'];
                }
                $aElementData=$newMDBE->getElementById($elementA);
                if($aElementData===false){return [];}//查询数据失败返回空
                $aDetails=json_decode(base64_decode($aElementData['details']));
                if($aDetails===null){return [];}//解析失败返回空
                $aType=$aElementData['type'];
                if($bTemplateData['typeRule'][$aType]!==true){
                    return [];//b图层不允许a元素类型加入则返回空
                }
                //a属性转化成b属性
                $newDetails=$this->detailsTransform($aDetails,$bTemplateData['detailsRule']);
            }else{
                echo "\n其他方式\n";
            }
        }
        return $ref;
    }

    /**获取排序图层顺序
     * @return array
     */
    function getOrderMembers(){
        foreach ($this->layerData as $key=>$value){
            if($value['type']==='order'){
                return $this->layerData[$key]['members'];
            }
        }
        return [];
    }

    /**获取一个图层的成员和结构数据
     * @param $id | layer id
     * @param $pack | true of false default=true
     * @return array | bool
     */
    function getLayerMembersStructure($id,$pack=true){//获取某个图层的成员和结构数据
        global $newJDT;
        if(array_key_exists($id,$this->layerData)){
            if($pack===true){
                return [
                    'members'=>$newJDT->btoa($newJDT->jsonPack($this->layerData[$id]['members'])),
                    'structure'=>$newJDT->btoa($newJDT->jsonPack($this->layerData[$id]['structure']))
                ];
            }else{
                return [
                    'members'=>$this->layerData[$id]['members'],
                    'structure'=>$this->layerData[$id]['structure']
                ];
            }
        }else{
            return false;
        }
    }

    /**移除排序图层成员
     * @param $id int
     * @return bool
     */
    function removeOrderMember($id){//排序图层移除图层id
        foreach($this->layerData as $key=>$value){
            if($value['type']==='order'){
                $newMembers=array_filter(
                    $value['members'],
                    function($item)use($id){return $item!==$id;}
                );
                if(count($newMembers)!==count($value['members'])){//新成员数组与旧成员数组长度不同时
                    $newMembers=array_values($newMembers);//重新索引
                    $this->layerData[$key]['members']=$newMembers;
                    $this->layerData[$key]['hasChange']=true;//修改状态
                    $this->updateCache();
                }
                break;
            }
        }
        return true;
    }

    /**添加新的成员到排序图层
     * @param $id int
     * @return bool
     */
    function addOrderMember($id){//排序图层新增图层id
        foreach($this->layerData as $key=>$value){
            if($value['type']==='order'){
                if(!in_array($id,$this->layerData[$key]['members'])){//不存在则加入
                    array_push($this->layerData[$key]['members'],$id);
                    $this->layerData[$key]['hasChange']=true;//修改状态
                    $this->updateCache();
                }
                break;
            }
        }
        return true;
    }

    /**获取插入图层数据sql语句
     * @param $id
     * @param $type
     * @param $members
     * @param $structure
     * @param $phase
     * @param $mysql_public_layer_name
     * @return string
     */
    function getInsertLayerDataSql($id,$type,$members,$structure,$phase,$mysql_public_layer_name){
        return "INSERT INTO " . $mysql_public_layer_name . " 
VALUES ({$id},'{$type}',{$members},{$structure},{$phase})";
    }

    /**获取更新图层数据sql语句
     * @param $id
     * @param $members
     * @param $structure
     * @param $phase
     * @param $mysql_public_layer_name
     * @return string
     */
    function getUpdateLayerDataSql($id,$members,$structure,$phase,$mysql_public_layer_name){
        return "UPDATE " . $mysql_public_layer_name . " 
SET members=" . $members . ",
       structure=" . $structure . ",
       phase=" . $phase . "
WHERE id=" . $id;
    }

    /**提取图层的模板链接
     * @param $structure | array 图层的结构
     * @param $layerId | number 图层id
     * @param array $route No need to pass in parameters 不需要传入此参数
     * @return array $links 模板与图层的关系链接包含从属图层id和模板路由
     */
    function extractTemplateLink($structure,$layerId,$route=[]){
        $links=[];
        foreach($structure as $item){
            if(is_array($item)){
                if(isset($item['template'])){
                    $templateId=$item['template']['id'];
                    if (isset($item[0])){//将当前图层的名称添加到路由数组中
                        $currentRoute=$route;
                        $currentRoute[]=$item[0];
                    }else{
                        $currentRoute=$route;
                    }
                    $links[$templateId]=[
                        'layerId'=>$layerId,
                        'route'=>$currentRoute
                    ];
                }else{
                    $subRoute=$route;
                    if(isset($item[0])){
                        $subRoute[]=$item[0];
                    }
                    $links=array_merge($links,$this->extractTemplateLink($item,$layerId,$subRoute));
                }
            }else{
                if(count($route)===0){//初始状态将根图层名称加入路由
                    array_push($route,$structure[0]);
                }
            }
        }
        return $links;
    }

    /**增加一个图层的元素
     * @param $id int element id
     * @param $tmpId string template id
     * @param $type string element type
     * @return int layer id or -1
     */
    function appendElement($id,$tmpId,$type){
        if(array_key_exists($tmpId,$this->templateLink)){
            $layerId=$this->templateLink[$tmpId]['layerId'];
            if(!array_key_exists($id,$this->layerData[$layerId]['members'])){//避免重复添加相同成员
                $this->layerData[$layerId]['members'][$id]=$this->typeNumber[$type];//图层成员添加
                array_push($this->layerData[$layerId]['structure'],(int)$id);//图层结构添加
                $this->layerData[$layerId]['hasChange']=true;//修改状态
                $this->updateCache();
                return $layerId;
            }
        }
        return -1;
    }

    /**移除一个图层的元素
     * @param $id int element id
     * @param $tmpId string template id
     * @return int layer id or -1
     */
    function removeElement($id,$tmpId){
        if(!array_key_exists($tmpId,$this->templateLink)){return -1;}//缺失此模板链接
        $layerId=$this->templateLink[$tmpId]['layerId'];
        $hasChange=false;
        if(array_key_exists($id,$this->layerData[$layerId]['members'])){//检查是否存在此成员
            unset($this->layerData[$layerId]['members'][$id]);//删除此成员
            $hasChange=true;
        }
        $newArray=array_filter(
            $this->layerData[$layerId]['structure'],
            function($item)use($id){return $item!==$id;}
        );
        if(count($newArray)!==count($this->layerData[$layerId]['structure'])){//结构存在变动
            $newArray=array_values($newArray);//重新索引数组
            $this->layerData[$layerId]['structure']=$newArray;//重新设置图层结构
            $hasChange=true;
        }
        if($hasChange){
            $this->layerData[$layerId]['hasChange']=true;//修改状态
            $this->updateCache();
        }
        return $hasChange?$layerId:-1;//存在变动则返回变动的图层id
    }

    /**更新图层数据到数据库(定期运行定时运行)
     * @return bool
     */
    function renewalLayerData(){
        global $newJDT,$mysql_public_server_address,$mysql_root_password,$mysql_public_layer_name,$mysql_public_db_name;
        $conn=mysqli_connect($mysql_public_server_address,'root',$mysql_root_password,$mysql_public_db_name);
        if(!$conn){
            echo "\n[自动任务]无法更新图层数据，因为root连接数据库失败\n";
            mysqli_close($conn);
            return false;
        }
        foreach($this->layerData as $key=>$item){
            if($item['hasChange']===false){continue;}
            $id=$item['id'];
            $type=$item['type'];
            $members=$item['members'];
            $structure=$item['structure'];
            $phase=$item['phase'];
            if($type!=='order'){//普通图层
                $members="'".$newJDT->btoa($newJDT->jsonPack($members))."'";
                $structure="'".$newJDT->btoa($newJDT->jsonPack($structure))."'";
            }else{
                $members="'".$newJDT->jsonPack($members)."'";
                $structure='""';
            }
            $searchSql="SELECT id FROM {$mysql_public_layer_name} WHERE id={$id}";
            $search=mysqli_query($conn,$searchSql);//用于检查是否已经存在此图层，存在则更新否则插入
            if($search){
                $row=mysqli_num_rows($search);
                if($row!=0){//更新数据
                    $sql=$this->getUpdateLayerDataSql($id,$members,$structure,$phase,$mysql_public_layer_name);//type自创建之初即不可改变
                    if(mysqli_query($conn,$sql)){
                        $this->layerData[$key]['hasChange']=false;
                        $this->updateCache();
                        echo "\n[自动任务]更新图层(".$type.$id.")数据成功\n";
                    }else{
                        echo "\n[自动任务]更新图层(".$type.$id.")数据失败\n";
                    }
                }
                else{//插入数据
                    $sql=$this->getInsertLayerDataSql($id,$type,$members,$structure,$phase,$mysql_public_layer_name);
                    if(mysqli_query($conn,$sql)){
                        $this->layerData[$key]['hasChange']=false;
                        $this->updateCache();
                        echo "\n[自动任务]新增图层(".$type.$id.")数据成功\n";
                    }else{
                        echo "\n[自动任务]新增图层(".$type.$id.")数据失败\n";
                    }
                }
            }
        }
        mysqli_close($conn);
        return true;
    }

    /**构建图层数据
     * @return bool
     */
    function buildLayerData(){
        global $newMDBE,$newJDT;
        $layers=$newMDBE->getAllLayerData();
        $maxId=0;
        if($layers!==false){
            $len=count($layers);
            for($i=0;$i<$len;$i++){
                if($layers[$i]['id']>$maxId){//获取最大的id
                    $maxId=$layers[$i]['id'];
                }
                if($layers[$i]['phase']==2){//对于删除的图层跳过
                    continue;
                }
                $key=$layers[$i]['id'];
                $type=$layers[$i]['type'];
                if($type==='order'){
                    $layers[$i]['members']=$newJDT->jsonParse($layers[$i]['members']);//解析
                    $this->layerData[$key]=$layers[$i];
                    $this->layerData[$key]['hasChange']=false;
                }else{
                    $layers[$i]['members']=$newJDT->jsonParse($newJDT->atob($layers[$i]['members']));//解析
                    $layers[$i]['structure']=$newJDT->jsonParse($newJDT->atob($layers[$i]['structure']));//解析
                    $this->layerData[$key]=$layers[$i];
                    $this->layerData[$key]['hasChange']=false;
                }
            }
            $this->lastLayerId=$maxId;
            return true;
        }
        return false;
    }

    /**构建模板与图层关系链接
     * @return bool
     */
    function buildTemplateLink(){
        foreach ($this->layerData as $key=>$value) {
            $id=$key;
            $structure=$value['structure'];
            $type=$value['type'];
            if($type!=='order'){
                $links=$this->extractTemplateLink($structure,$id);
                if(is_array($links)){
                    $this->templateLink=array_merge($this->templateLink,$links);
                }
            }
        }
        //print_r($this->templateLink);
        return true;
    }
    /**
     * 辅助函数 Helper functions
     * */
    function is__nan($value){
        return is_float($value) && is_nan($value);
    }
    function is__infinite($value){
        return is_float($value) && ($value===INF || $value===-INF);
    }
    function typeof($var){
        if (is_null($var)){
            return "null";
        } elseif (is_bool($var)) {
            return "boolean";
        } elseif (is_int($var) || is_float($var)) {
            return "number";
        } elseif (is_string($var)) {
            return "string";
        } elseif (is_array($var)) {
            return "object";
        } elseif (is_object($var)) {
            return "object";
        } else {
            return "unknown";
        }
    }
    function Number($value){
        if(is_numeric($value)){return $value+0;}else{return 0;}
    }
}

<?php


class FileOperation
{
    /**读取并转化png和jpg
     * @param $image_file string
     * @return false | string
     */
    function imageToBase64($image_file)
    {
        $image_info = getimagesize($image_file);
        $image_data = file_get_contents($image_file);

        switch($image_info[2]) {
            case IMAGETYPE_PNG:
                $image_data = base64_encode($image_data);
                $image_base64 = 'data:image/png;base64,' . $image_data;
                break;
            case IMAGETYPE_JPEG:
                $image_data = base64_encode($image_data);
                $image_base64 = 'data:image/jpeg;base64,' . $image_data;
                break;
            default:
                return false;
        }
        return $image_base64;
    }
    /**读取文件返回最后修改时间
     * @param $file
     * @return false | string
     */
    function getFileModifiedTime($file)
    {
        if (file_exists($file)) {
            $mtime = filemtime($file);
            return date('Y-m-d H:i:s', $mtime);
        } else {
            return false;
        }
    }
    /**对比文件新旧返回最后一个（最近编辑日期）如果两者一支或其中一种格式错误则返回false
     * @param $timeA
     * @param $timeB
     * @return string | boolean
     */
    function compareTime($timeA, $timeB)
    {
        $format = 'Y-m-d H:i:s';
        $a_parts = date_parse_from_format($format, $timeA);
        $b_parts = date_parse_from_format($format, $timeB);

        if ($a_parts === false || $b_parts === false) {
            return false;
        }
        if($timeA===$timeB){
            return false;
        }
        if ($a_parts['year'] > $b_parts['year']) {
            return $timeA;
        } elseif ($a_parts['year'] < $b_parts['year']) {
            return $timeB;
        }
        if ($a_parts['year'] > $b_parts['year']) {
            return $timeA;
        } elseif ($a_parts['year'] < $b_parts['year']) {
            return $timeB;
        } else {
            if ($a_parts['month'] > $b_parts['month']) {
                return $timeA;
            } elseif ($a_parts['month'] < $b_parts['month']) {
                return $timeB;
            } else {
                if ($a_parts['day'] > $b_parts['day']) {
                    return $timeA;
                } elseif ($a_parts['day'] < $b_parts['day']) {
                    return $timeB;
                } else {
                    if ($a_parts['hour'] > $b_parts['hour']) {
                        return $timeA;
                    } elseif ($a_parts['hour'] < $b_parts['hour']) {
                        return $timeB;
                    } else {
                        if ($a_parts['minute'] > $b_parts['minute']) {
                            return $timeA;
                        } else {
                            return $timeB;
                        }
                    }
                }
            }
        }
    }
}
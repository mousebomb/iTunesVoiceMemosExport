<?php
/**
 * Created by PhpStorm.
 * User: rhett
 * Date: 2019/10/10
 * Time: 8:17
 */
$lib = "/Users/rhett/Music/iTunes/iTunes Library.xml";
$to = "/Users/rhett/Music/语音备忘录/";
//如果iTunes库索引存在，但文件已经丢失的话，从备胎（TimeMachine或其他备份路径内找同名原始文件）/ 我就手贱用Music重新检查了一下文件，只要Cmd+I查看属性，他就会帮你移动到 `/Users/rhett/Music/iTunes/iTunes Media/Music/Mousebomb-iPhone/语音备忘录/` 但这样文件名匹配不到的，需要时光机里的档案才行。
$otherBackupFolder = "/Volumes/RHETT_HD/Voice Memos/";


# 读取lib 分析备忘录数组

$content = file_get_contents($lib);
$dom = DOMDocument::loadXML($content);
$targetLiPath="/plist/dict/dict/dict[";

$elements = $dom->getElementsByTagName("dict");
#先找到 /plist/dict/dict/dict
for($i = 0;$i<$elements->length;$i++)
{
    $dict =  $elements->item($i);
    $progress = $i .'/' .$elements->length ."> ";
//    wLog("搜索:".$i . " ".$dict->getNodePath() ."\n" );
    if ( strpos($dict->getNodePath(), $targetLiPath ) !==false)
    {
//        wLog("找到了列表节点");
        if ( checkLiIsVoiceMemoNode($dict) )
        {
//            wLog("找到了语音备忘录");
            $vmVO = parseDict($dict);

            $location = $vmVO["Location"];
            $location = urldecode($location);
            if (strpos($location,"file://")!==false)
            {
                $location = substr($location,7);
            }

            $rposS = strrpos($location ,"/");
            $rposD = strrpos($location ,".");
            if ($rposD===false || $rposS===false)
            {
                wLog("[错误] 非法文件名" . $location);
                print_r($vmVO);
//                continue;
                die();
            }
            $basename = substr($location,$rposS+1, $rposD-$rposS-1);
            //许多name是非法文件名，所以要修正
            $name = fixNewFileName($vmVO['Name']);
            $newFileName = $to.$basename. '-'.$name.'.m4a';

            if (file_exists($location))
            {
                if ( file_exists($newFileName))
                {
                    wLog($progress.  $location ." -> " . $newFileName." 已存在,跳过");
                }else{
                    wLog($progress. $location ." -> " . $newFileName);
                    copy($location,$newFileName);
                }
            }else{
                $locationFromTM =                 $otherBackupFolder.$basename.'.m4a';
                if (file_exists($locationFromTM))
                {
                    wLog($progress. $locationFromTM ." -> " . $newFileName);
                    copy($locationFromTM,$newFileName);
                }else{
                    wLog($progress.  $location ." -> " . $newFileName. " iTunes库记载的旧文件丢失,跳过");
                }
            }

        }
    }
}

die("搞定了");


function fixNewFileName( $oldName )
{
    $end=$oldName;
    $end = str_replace("/","-",$end);
    $end = str_replace("?","-",$end);
    $end = str_replace("*","-",$end);
    $end = str_replace(":","-",$end);
    $end = str_replace(" ","-",$end);
    return $end;
}
function parseDict ($dictNode)
{
    $end = array();
    $domList = $dictNode->childNodes;
    $key=null;$val=null;

    for ($i = 0;$i< $domList->length;$i++)
    {
        $eachElement = $domList->item($i);
        if ( $eachElement->localName == "key"  )
        {
            $key = $eachElement->textContent;
        }else if(!empty($eachElement->localName ) ){
            $val = $eachElement->textContent;
        }
        if ( !empty($key) && !empty($val))
        {
            $end[$key] = $val;
            $key = null;$val=null;
        }

    }
    return $end;
}

function checkLiIsVoiceMemoNode ( $dictNode )
{
    $domList = $dictNode->childNodes;

    for ($i = 0;$i< $domList->length;$i++)
    {
        $eachElement = $domList->item($i);
        if ( $eachElement->localName == "key"  && $eachElement->textContent=="Album")
        {
            $val = $domList->item($i+1)->textContent;
//            wLog($val);
            if ($val =="语音备忘录")
            {
                return true;
            }
        }
//        wLog ( $eachElement->localName );
//        wLog( $domList->item($i)->getNodePath() );
    }
    return false;
}

function wLog($str)
{
    echo $str ."\n";
}

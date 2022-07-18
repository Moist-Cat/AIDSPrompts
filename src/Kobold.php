<?php
$IDprompt =  isset($_GET['ID']) ? $_GET['ID'] : "10";
require 'class/db.php';
// Connection to MYSQL database
$db = new db();
$querryPrompt = "SELECT Distinct * FROM prompts where CorrelationID=?";
$prompts = $db->query($querryPrompt, array($IDprompt));
//If the URL parameter for the CorrelationID is not in the prompts table this mean the prompt don't exist.
if ($prompts->numRows() == 0)
    die("Bad Request");
$i = 1;
$promptInfos = $prompts->fetchArray();
if (!isset($promptInfos['PublishDate']))
    die("Bad Request");
    if (is_null($promptInfos['NovelAIScenario']))
    $promptInfos['NovelAIScenario'] = 'null';


if (is_null($promptInfos['ParentID']))
    $promptInfos['ParentID'] = 'null';

$nsfw = 'false';
if($promptInfos['Nsfw']==1)
$nsfw = 'true';
$Winfos = $db->worldInfos($promptInfos['Id']);
$Subs =  $db->subScenarios($IDprompt);
$tags = preg_split("/\,/", $promptInfos['Tags']);
$kobold = '{"authorsNote":"'. str_replace('"', '\"', str_replace("\n", "\\n", $promptInfos['AuthorsNote'])) . '","children":[';
if ($Subs!=0)
foreach ($Subs as $Sub)
{
    $kobold .= '{"id":"' . $Sub['CorrelationID'] . ',"title":"' . $Sub['Title'] . '"},';
}
$kobold =  substr($kobold, 0, -1);
$kobold.= '],"dateCreated":"' . $promptInfos['DateCreated'] . '","description":"' . 
    str_replace('"', '\"', str_replace("\n", "\\n", $promptInfos['Description'])) . '","hasScriptFile":false,"id":' . $IDprompt . ',"isDraft":false,"memory":"' . str_replace('"', '\"', str_replace("\n", "\\n", $promptInfos['Memory'])). '","nsfw":' . $nsfw . ',"ownerId":1,"parentId":' 
    . $promptInfos['ParentID'] . ',"promptContent":"' . str_replace('"', '\"', str_replace("\n", "\\n", $promptInfos['PromptContent'])) . '","promptTags":[';

    foreach ($tags as $tag)
    {
       $kobold .= '{"id":' . $i . ',"name":"' . $tag.'"},';
       $i++;
    }
    $kobold =  substr($kobold, 0, -1);
    $kobold.='],"publishDate":"'.$promptInfos['PublishDate']. '","quests":"","title":"'. $promptInfos['Title'] . '","worldInfos":[';
    if ($Winfos != 0)
    foreach ($Winfos as $WI)
    {
        $kobold.='{"entry":"'.  str_replace('"', '\"', str_replace("\n", "\\n",$WI['Entry'])) . '","id":' . $WI['Id'] . ',"keys":"' . $WI['WKeys'] . '","keysList":["';
        $keys = preg_split("/\,/", $WI['WKeys'] );
        foreach ($keys as $key)
        $kobold.= $key . '",';
        $kobold =  substr($kobold, 0, -1);
        $kobold.= ']}';
    }
    $kobold.= '],"novelAiScenario":'.  $promptInfos['NovelAIScenario'] .',"holoAiScenario":null}' ;
    echo $kobold;

<?php
// Class for secure MYSQL queries : Info on Connection on config.php
require 'class/db.php';



// When the submit or the draft button is clicked. We begin the procedure to insert the data.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // We get all the information from the form (Title, number of WI, prompt etc.)
    $cwi = (int)$_POST['Command_CWI'];
    $title = $_POST['Command_Title'];
    $description = $_POST['Command_Description'];
    $file = $_POST['Command_File'];
    $prompt = $_POST['Command_PromptContent'];
    $searchCode = $_POST['Command_SearchCode'];
    //Subscenario don't have tags
    if (!isset($_POST["Command_Parent"]))
        $tag = preg_replace('!\s+!', ' ', $_POST['Command_PromptTags']);
    else
        $tag = "";
    $memory =  $_POST['Command_Memory'];
    $author = $_POST['Command_AuthorsNote'];
    //Only the first parent have an edit code, his sons share the same with him.
    if (!isset($_POST["Command_Parent"]))
        $editcode = $_POST['Command_GenerateCode'];
    $dateC = (string)date("Y-m-d H:i:s");
    $nsfw = isset($_POST['Command_Nsfw']) ? 1 : 0;

    // Connection to MYSQL database
    $db = new db();
    // To create the CorrelationID we need the Max of what actually exist on the prompts table + 1
    $querryMaxCID =  "Select MAX(CorrelationID) as Max from prompts";
    $newCorrelationID = ($db->query($querryMaxCID)->fetchArray()['Max']) + 1;
    // Same for the CorrelationID of the World info
    $querryMaxWID = "Select MAX(CorrelationID) as Max from worldinfos";
    $newWorldCorrelationID = ($db->query($querryMaxWID)->fetchArray()['Max']) + 1;
    // If no .scenario was uploaded we need to create one for the db
    if ($file == "") {
        $file = '{"scenarioVersion": 0, "tags": ["' . str_replace(', ', '", "', str_replace('"', '\"', $tag)) . '"], "title": "' . str_replace('"', '\"', $title) . '", "description":"' . str_replace('"', '\"', str_replace(PHP_EOL, "\\n", $description)) . '","prompt":"' .  str_replace('"', '\"', str_replace(PHP_EOL, "\\n", $prompt)) . '", "context":[{"text":"' . str_replace('"', '\"', str_replace(PHP_EOL, "\\n", $memory)) . '","contextConfig":{"prefix":"","suffix":"\n","tokenBudget":2048,"reservedTokens":0,"budgetPriority":800,"trimDirection":"trimBottom","insertionType":"token","insertionPosition":0}},{"text":"' . str_replace('"', '\"', str_replace(PHP_EOL, "\\n", $author)) . '","contextConfig":{"prefix":"","suffix":"\n","tokenBudget":2048,"reservedTokens":2048,"budgetPriority":-400,"trimDirection":"trimBottom","insertionType":"newline","insertionPosition":-4}}],
    "lorebook": {
        "lorebookVersion": 1,
        "entries": [';

        for ($i = 0; $i <= $cwi; $i++) {
            if ($_POST["Command__WIK" . $i] != "" || $_POST["Command__WI" . $i] != "") {
                $key =   preg_replace('!\s+!', ' ', str_replace('"', '\"', str_replace(PHP_EOL, "\\n", $_POST["Command__WIK" . $i])));
                $file = $file . '{"text": "' . str_replace('"', '\"', str_replace(PHP_EOL, "\\n", $_POST["Command__WI" . $i])) . '", "keys": ["' . $key . '"], "displayName":"' .  preg_split("/\,/", $key)[0] . '" }';
                if (($i + 1) <= $cwi && $_POST["Command__WIK" . ($i + 1)] != "" && $_POST["Command__WI" . ($i + 1)] != "")
                    $file = $file . ',';
            }
        }
        $file = $file . "]}}";
    }


    if (!isset($_POST["Command_Parent"])) {
        $alltags = preg_split("/\,/", $tag);
        $alltags= array_intersect_key(
            $alltags,
            array_unique( array_map( "strtolower", array_map('trim', $alltags)))
        );
        $tag = "";
        // We remove 'nsfw' since it is managed by it's own column in the database.
        foreach ($alltags as $st) {
            $st = trim($st);
            if (strtoupper($st) == "NSFW")
                $nsfw = 1;
            else if ($st != "")
                $tag .= $st . ',';
        }
        $tag = substr($tag, 0, -1);
    }

    //List of variable for MySQLi Prepared Statements
    $sqlparams = array(
        $author,
        $description,
        $memory,
        $nsfw,
        $prompt,
        $tag,
        $title,
        $newCorrelationID,
        $dateC,
        $file
    );


    // If not a draft we add the PublishDate (Submit button was pressed)
    if (isset($_POST['subPrompts'])) {

        if (!isset($_POST["Command_Parent"])) {
            $sql = "INSERT INTO prompts
    (AuthorsNote, Description, Memory, Nsfw, PromptContent, Tags, Title, CorrelationID, DateCreated, NovelAIScenario, PublishDate)
    VALUES(?, ?, ?,?, ?,?,?, ?, ?,?, ?);";
            $sqlparams[] =   $dateC;
        }
        //If it's a subscenario we add the ParentID
        else {
            $sql = "INSERT INTO prompts
            (AuthorsNote, Description, Memory, Nsfw, PromptContent, Tags, Title, CorrelationID, DateCreated, NovelAIScenario, PublishDate, ParentID)
            VALUES(?, ?, ?,?, ?,?,?, ?, ?,?, ?, ?);";
            array_push($sqlparams, $dateC, (int)$_POST["Command_Parent"]);
        }
    }
    // If draft the PublishDate is null
    else
        $sql = "INSERT INTO prompts
    (AuthorsNote, Description, Memory, Nsfw, PromptContent, Tags, Title, CorrelationID, DateCreated, NovelAIScenario)
    VALUES(?, ?, ?,?, ?,?,?, ?, ?,?);";


    $insert = $db->query($sql, $sqlparams);

    // Once the insert is done we can get the Id of the created prompt 
    $sql = "Select Id from prompts where CorrelationID=? limit 1";
    $idNow = $db->query($sql, $newCorrelationID)->fetchArray()['Id'];

    // For each WI we insert into the worldinfos table
    for ($i = 0; $i <= $cwi; $i++) {
        $key = $_POST["Command__WIK" . $i];
        $entry = $_POST["Command__WI" . $i];
        $newWorldCorrelationID = $newWorldCorrelationID + $i;
        if ($key != "" || $entry != "") {
            $sql = "INSERT INTO worldinfos
    (Entry, WKeys, PromptId, CorrelationId, DateCreated)
    VALUES(?, ?, ?, ?, ?);";
            $insert = $db->query($sql, array($entry, $key, $idNow, $newWorldCorrelationID, $dateC));
        }
    }
    // We insert the edit code into the editcode table if it's not a subscenario
    if (!isset($_POST["Command_Parent"])) {

        $sql = "INSERT INTO editcode (PromptID, CodeEdit, SearchCode) VALUES(?,?,?);";
        $insert = $db->query($sql, array($idNow, password_hash($editcode,  PASSWORD_DEFAULT), $searchCode));
    }

    //Session to have right to view if draft


    $db->close();
    session_start();
    // We redirect to the Created prompt.
    if (!isset($_POST["Command_Parent"])) {

        $_SESSION['CodeEdit'] = $editcode;
    }
    if ($searchCode != "")
        $_SESSION['SearchCode'] = $searchCode;
    header('Location: Prompt.php?ID=' . $newCorrelationID);
    exit();
}
//We know by url parameter if we are creating a subScenario or not
else if (isset($_GET['IDParent'])) {
    //We start the session to get the EditCode
    session_start();
    $db = new db();
    //If no EditCode was entered by the user (User played with the url) we display nothing
    if (!isset($_SESSION['CodeEdit']))
        die("Bad Request");
    //We get the ParentID in the url
    $Parent = $_GET['IDParent'];
    //We get the informations on the parent
    $querryPrompt = "SELECT Distinct Id, ParentID FROM prompts where CorrelationID=?";
    $nprompts = $db->query($querryPrompt, array($Parent));
    //If the parent don't exist in the database (User played with the url)
    if ($nprompts->numRows() == 0)
        die("Bad Request");
    $rprompt = $nprompts->fetchArray();
    //We get the EditCode of the first parent to see if it is the one the user entered.
    if (is_null($rprompt['ParentID']))
        $EditCode = $db->EditCode($rprompt['Id']);
    else
        $EditCode =  $db->firstParentEditCode($rprompt['ParentID']);
    $db->close();
    if (!password_verify($_SESSION['CodeEdit'], $EditCode))
        die("Bad Request");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap Form Validation</title>
    <link rel="stylesheet" href="css/bootstrap.dark.css">
    <link rel="stylesheet" href="css/spage.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    </script>
    <script src="js/form.js">
    </script>
</head>

<body>
    <!--Header never change-->
    <?php include('header.php'); ?>
    <!--Structure is the same as old club-->
    <div class="container">
        <main role="main" class="pb-3">
            <?php if (isset($_GET['IDParent'])) { ?>
                <div class="alert alert-primary">
                    You are creating a sub scenario. <a href="<?php echo "Edit.php?ID=" . $_GET['IDParent']; ?>">Click here to return to the parent without saving.</a>
                </div> <?php } ?>
            <h2>Create Prompt</h2>
            <br />
            <div class="card mb-4 p-2">
                <details>
                    <summary>Import Options</summary>
                    <div class="mt-2 p-2">
                        <h4>NAI</h4>
                        <div class="d-flex mb-3">
                            <div>
                                <input type="file" name="scenarioFile" accept=".scenario" id="fileInput" />
                            </div>
                            <div class="ml-auto">
                                <button type="button" id="upfile" class="btn btn-outline-light">Upload NAI Scenario</button>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
            <form enctype="multipart/form-data" method="post" action="Create.php" class="needs-validation" novalidate>
                <?php if (isset($_GET['IDParent'])) { ?> <input class="form-control" type="hidden" id="Command_Parent" name="Command.Parent" value="<?php echo $_GET['IDParent'] ?> " /> <?php } ?>
                <input class="form-control" type="hidden" id="Command_CWI" name="Command.CWI" value="0" />
                <input class="form-control" type="hidden" id="Command_File" name="Command.File" value="" />
                <div class="form-group required">
                    <label for="Command_Title">Title<span class="text-danger">*</span></label>
                    <input class="form-control" type="text" id="Command_Title" name="Command.Title" value="" required />
                    <div class="invalid-feedback">The Title field is required.</div>
                </div>
                <div class="form-group">
                    <label for="Command_Description">Description</label>
                    <textarea class="form-control" id="Command_Description" name="Command.Description"></textarea>

                </div>
                <div class="form-group required">
                    <label for="Command_PromptContent">Prompt<span class="text-danger">*</span></label>
                    <textarea class="form-control" id="Command_PromptContent" name="Command.PromptContent" required></textarea>
                    <div class="invalid-feedback">The Prompt field is required.</div>
                </div>
                <?php if (!isset($_GET['IDParent'])) { ?>
                    <div class="form-group required">
                        <label for="Command_PromptTags">Tags (comma delimited)<span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="Command_PromptTags" name="Command.PromptTags" value="" required />
                        <div class="invalid-feedback">The Tags (comma delimited) field is required.</div>
                    </div>
                <?php } ?>
                <div class="form-group">
                    <label for="Command_Memory">Memory</label>
                    <textarea class="form-control" id="Command_Memory" name="Command.Memory"></textarea>

                </div>
                <div class="form-group">
                    <label for="Command_AuthorsNote">Author&#x27;s Note</label>
                    <textarea class="form-control" id="Command_AuthorsNote" name="Command.AuthorsNote"></textarea>

                </div>
                <?php if (!isset($_GET['IDParent'])) { ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" data-val="true" data-val-required="The NSFW? field is required." id="Command_Nsfw" name="Command.Nsfw" value="true">
                        <label class="form-check-label" for="Command_Nsfw">NSFW?</label>
                    </div>
                <?php } ?>
                </br>
                <div class="card">
                    <div aria-expanded="true" aria-controls="world-info-body" data-toggle="collapse" data-target="#world-info-body" class="card-header">
                        <h4>World Info</h4>
                    </div>
                    <div id="world-info-body" class="collapse show card-body">
                        <div>
                            <p>Any World Info left blank will be automatically removed on submission.</p>
                            <div id="anchorWI">
                                <div id="world-info-card-0" class="card mb-4">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="Command_WIK0" id="labk0">Keys</label>
                                            <input class="form-control" type="text" id="Command__WIK0" name="Command._WIK0" value="" />
                                        </div>
                                        <div class="form-group">
                                            <label for="Command_WI0" id="lab0">Information</label>
                                            <textarea class="form-control" id="Command__WI0" name="Command._WI0"></textarea>

                                        </div>
                                        <div class="d-flex" id="p">
                                            <button type="button" id="Delete_0" class="world-info-delete-btn ml-auto btn btn-outline-danger" value="0">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button id="add-wi" type="button" class="btn btn-secondary">Add Another</button>
                        </div>
                    </div>
                </div>
                <br />
                <br>
                <div class="d-flex">
                    <?php if (!isset($_GET['IDParent'])) { ?>

                        <label for="Command_GenerateCode" id="lab0">Edit Code :</label>
                        <input type="text" class="form-floating mb-3" id="Command_GenerateCode" name="Command.GenerateCode" value="" required />
                        <button type="button" style="margin-left: 1.2rem;" class="btn btn-primary btn-sm mb-3" id="Generate">Generate</button>


                        <label for="Command_SearchCode" style="margin-left: 1.2rem;" id="lab0">Search Code :</label>
                        <input type="text" class="form-floating mb-3" id="Command_SearchCode" name="Command.SearchCode" value="" />
                </div>
                <div class="d-flex">
                    <button id="save-draft" name="subDraft" type="submit" style="margin-right: 1.2rem;" class="btn ml-auto btn-lg btn-outline-warning formDB2">Save Draft</button>
                <?php } ?>
                <button type="submit" id="submitBtn" name="subPrompts" class="btn ml-right btn-lg btn-primary formDB">
                    Submit
                </button>
                </div>



    </div>
</body>

</html>

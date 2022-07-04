<?php
// Start the session for the Edit Code
session_start();
// Use URL parameter to get the CorrelationID of the prompt to Edit.
$IDprompt =  isset($_GET['ID']) ? $_GET['ID'] : "10";

// Php Class for all that is database related, use prepared statements to protect against injection. Info Connection on config.php
require 'class\db.php';
// Connection to MYSQL database
$db = new db();

//used for foreach with all worldinfos.
$iWI = 0;

//Select to have all the information on the prompt
$querryPrompt = "SELECT Distinct * FROM prompts where CorrelationID=?";
$prompts = $db->query($querryPrompt, array($IDprompt));

//If the URL parameter for the CorrelationID is not in the prompts table this mean the prompt don't exist.
if ($prompts->numRows() == 0)
    die("Bad Request");
$promptInfos = $prompts->fetchArray();

//When user delete a subscenario
if (isset($_POST['DeleteBtn'])) {
    //Function to delete the subscenario and his own subscenarios if he had some.
    $db->deleteSub((int)$_POST['Command_SubID'], (int)$_POST['Command_SubCID']);
    $url = "Edit.php?ID=" . $IDprompt;
    header("Location: $url");
    exit();
}

// We get the Edit code of the prompt, if the function return 0 it mean we are in a subscenario. So we get the Edit Code of the first parent.
$CodeEdit = $db->EditCode($promptInfos['Id']);
if ($CodeEdit == "")
    $CodeEdit =  $db->firstParentEditCode($promptInfos['ParentID']);
// If no session for Edit Code or bad Code we don't give access to the page
if (!isset($_SESSION['CodeEdit']))
    die("Bad Request");
else if (!password_verify($_SESSION['CodeEdit'], $CodeEdit))
    die("Bad Request");

// We get all the Worldinfos and Subscenarios of the selected prompt.
$NbWI = $db->NbworldInfos($promptInfos['Id']);
$Winfos = $db->worldInfos($promptInfos['Id']);
$Subs =  $db->subScenarios($IDprompt);

// If Post by the draft or submit button
if (isset($_POST['subPrompts']) || isset($_POST['subDraft'])) {
    // We get all the information from the form (Title, number of WI, prompt etc.)
    $cwi = (int)$_POST['Command_CWI'];
    $title = $_POST['Command_Title'];
    $description = $_POST['Command_Description'];
    $file = $_POST['Command_File'];
    $prompt = $_POST['Command_PromptContent'];
    $memory =  $_POST['Command_Memory'];
    $author = $_POST['Command_AuthorsNote'];
    $dateC = (string)date("Y-m-d H:i:s");
    $nsfw = isset($_POST['Command_Nsfw']) ? 1 : 0;

    // Querry to determine CorrelationID for the WIs we will insert.
    $querryMaxWID = "Select MAX(CorrelationId) as Max from worldinfos";
    $newWorldCorrelationID = ($db->query($querryMaxWID)->fetchArray()['Max']) + 1;

    // We manage tags only if it is not a subscenario, same as old club.
    if (is_null($promptInfos['ParentID'])) {
        $tag = preg_replace('!\s+!', ' ', $_POST['Command_PromptTags']);
        $alltags = preg_split("/\,/", $tag);
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
    // Default if subscenario, same as old club.
    else {
        $tag = "";
        $nsfw = 0;
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
        $dateC,
    );

    // Update querry for prompts table
    $sql = "UPDATE prompts
  SET AuthorsNote=?, Description=?, Memory=?, Nsfw=?, PromptContent=?, Tags=?, Title=?, DateEdited=?";
    // If a file was uploaded we update it into the database. Right now I don't update using only the field. Update soon tm.
    if ($file != "") {
        $sql = $sql . ", NovelAIScenario=?";
        $sqlparams[] = $file;
    }
    // If it is a draft there is no PublishDate
    if (isset($_POST['subDraft']))
        $sql = $sql . ",PublishDate=NULL";
    // Else if a publish date don't exist we insert today.
    else if (!isset($promptInfos['PublishDate'])) {
        $sql = $sql . ",PublishDate=?";
        $sqlparams[] = $dateC;
    }
    $sql = $sql . " WHERE CorrelationId=?;";
    $sqlparams[] = $IDprompt;
    $update = $db->query($sql, $sqlparams);

    //We delete all the existing world infos of the prompt before inserting the new updated one.
    $sql = "DELETE FROM worldinfos WHERE PromptId =?";
    $delete = $db->query($sql, $promptInfos['Id']);

    for ($i = 0; $i <= $cwi; $i++) {
        $key = $_POST["Command__WIK" . $i];
        $entry = $_POST["Command__WI" . $i];
        $newWorldCorrelationID = $newWorldCorrelationID + $i;
        if ($key != "" && $entry != "") {
            $sql = "INSERT INTO worldinfos
(Entry, WKeys, PromptId, CorrelationId, DateCreated)
VALUES(?, ?, ?, ?, ?);";
            $insert = $db->query($sql, array($entry, $key, $promptInfos['Id'], $newWorldCorrelationID, $dateC));
        }
    }

    // We go to the edited prompt
    $db->close();
    header('Location: Prompt.php?ID=' . $promptInfos['CorrelationID']);

    exit();
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js">
    </script>
    <script src="js/form.js">
    </script>
</head>

<body>

    <?php //The header never Change. 
    include('header.php'); ?>
    <div class="container">
        <main role="main" class="pb-3">
            <?php if (!is_null($promptInfos['ParentID'])) { ?>
                <div class="alert alert-primary">
                    You are editing a sub scenario. <a href="<?php echo "Edit.php?ID=" . $promptInfos['ParentID']; ?>">Click here to return to the parent without change.</a>
                </div> <?php } ?>
            <h2> <?php if (!isset($promptInfos['PublishDate'])) { ?>
                    <span class="mr-2 badge badge-warning">Draft</span>
                <?php } ?> Edit Prompt
            </h2>
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
            <form enctype="multipart/form-data" method="post" action="Edit.php?ID=<?php echo $IDprompt ?>" class="needs-validation" novalidate>

                <input class="form-control" type="hidden" id="Command_CWI" name="Command.CWI" value="<?php echo ($NbWI - 1) ?>" />
                <input class="form-control" type="hidden" id="Command_File" name="Command.File" value="" />
                <div class="form-group required">
                    <label for="Command_Title">Title<span class="text-danger">*</span></label>
                    <input class="form-control" type="text" id="Command_Title" name="Command.Title" value="<?php echo $promptInfos['Title']; ?>" required />
                    <div class="invalid-feedback">The Title field is required.</div>
                </div>
                <div class="form-group">
                    <label for="Command_Description">Description</label>
                    <textarea class="form-control" id="Command_Description" name="Command.Description"><?php echo $promptInfos['Description']; ?></textarea>

                </div>
                <div class="form-group required">
                    <label for="Command_PromptContent">Prompt<span class="text-danger">*</span></label>
                    <textarea class="form-control" id="Command_PromptContent" name="Command.PromptContent" required><?php echo $promptInfos['PromptContent']; ?></textarea>
                    <div class="invalid-feedback">The Prompt field is required.</div>
                </div>
                <?php if (is_null($promptInfos['ParentID'])) { ?>
                    <div class="form-group required">
                        <label for="Command_PromptTags">Tags (comma delimited)<span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="Command_PromptTags" name="Command.PromptTags" value="<?php echo $promptInfos['Tags']; ?>" required />
                        <div class="invalid-feedback">The Tags (comma delimited) field is required.</div>
                    </div>
                <?php } ?>
                <div class="form-group">
                    <label for="Command_Memory">Memory</label>
                    <textarea class="form-control" id="Command_Memory" name="Command.Memory"><?php echo $promptInfos['Memory']; ?></textarea>

                </div>
                <div class="form-group">
                    <label for="Command_AuthorsNote">Author&#x27;s Note</label>
                    <textarea class="form-control" id="Command_AuthorsNote" name="Command.AuthorsNote"><?php echo $promptInfos['AuthorsNote']; ?></textarea>

                </div>
                <?php if (is_null($promptInfos['ParentID'])) { ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" data-val="true" data-val-required="The NSFW? field is required." id="Command_Nsfw" name="Command.Nsfw" <?php if ($promptInfos['Nsfw'] > 0) echo "checked"; ?> value="true">
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

                                <?php if ($Winfos != 0)  foreach ($Winfos as $WInf) : ?>
                                    <div id="world-info-card-<?php echo $iWI; ?>" class="card mb-4">
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="Command_WIK<?php echo $iWI; ?>">Keys</label>
                                                <input class="form-control" type="text" id="Command__WIK<?php echo $iWI; ?>" name="Command._WIK<?php echo $iWI; ?>" value="<?php echo $WInf['WKeys']; ?>" />
                                            </div>
                                            <div class="form-group">
                                                <label for="Command_WI<?php echo $iWI; ?>" id="lab0">Information</label>
                                                <textarea class="form-control" id="Command__WI<?php echo $iWI; ?>" name="Command._WI<?php echo $iWI; ?>"><?php echo $WInf['Entry']; ?></textarea>

                                            </div>
                                            <div class="d-flex" id="p">
                                                <button type="button" id="<?php echo "Delete_" . $iWI; ?>" class="world-info-delete-btn ml-auto btn btn-outline-danger" value="<?php echo $iWI; ?>">Delete</button>
                                            </div>
                                        </div>
                                    </div>

                                <?php
                                    $iWI++;
                                endforeach;
                                ?>
                            </div>
                            <button id="add-wi" type="button" class="btn btn-secondary">Add Another</button>
                        </div>
                    </div>
                </div>
                <br>

                <div class="card" id="sub-scenario-container">
                    <div aria-expanded="true" aria-controls="sub-scenario-body" data-toggle="collapse" data-target="#sub-scenario-body" class="card-header">
                        <h4>Sub Scenarios</h4>
                    </div>

                    <div id="sub-scenario-body" class="collapse show card-body">
                        <?php if ($Subs != 0)  foreach ($Subs as $Sub) : ?>
                            <div class="card mb-4">
                                <div class="card-body d-flex">
                                    <h5 class="align-self-center mb-0 flex-grow-1 flex-shrink-1">
                                        <?php echo  htmlspecialchars($Sub['Title']); ?>
                                    </h5>
                                    <div class="ml-auto">
                                        <a class="btn btn-outline-success" href="<?php echo "Edit.php?ID=" . $Sub['CorrelationID']; ?>">Edit</a>

                                    </div>
                                    <div class="ml-2">
                                        <input class="form-control" type="hidden" id="Command_SubCID" name="Command.SubCID" value="<?php echo $Sub['CorrelationID'] ?>" />
                                        <input class="form-control" type="hidden" id="Command_SubID" name="Command.SubID" value="<?php echo $Sub['Id'] ?>" />
                                        <button type="submit" id="DeleteBtn" name="DeleteBtn" class="btn btn-outline-danger btn-delete">Delete</button>
                                    </div>
                                </div>

                            </div> <?php endforeach; ?> <a class="btn btn-secondary" href="<?php echo "Create.php?IDParent=" . $IDprompt; ?>">Add Scenario</a>
                    </div>
                </div>

                <br>
                <div class="d-flex">
                    <?php if (is_null($promptInfos['ParentID'])) { ?>
                        <button id="save-draft" name="subDraft" type="submit" class="ml-auto btn btn-lg btn-outline-warning formDB">Save Draft</button> <?php } ?>
                    <button type="submit" id="submitBtn" style="margin-left:1.2rem ;" name="subPrompts" class="ml-<?php if (is_null($promptInfos['ParentID'])) echo 'right';
                                                                                                                    else echo 'auto'; ?> btn btn-lg btn-primary formDB">

                        Submit
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>

</html>

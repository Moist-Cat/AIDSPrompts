<?php
// Start the session for the Edit Code
session_start();
/*We use url parameter to get the CorrelationID of the prompt we want to display.
Don't know how clubanon did for 'dynamic' URL*/
$IDprompt =  isset($_GET['ID']) ? $_GET['ID'] : "10";
// Php Class for all that is database related, use prepared statements to protect against injection. Info Connection on config.php
require 'class\db.php';
// Connection to MYSQL database
$db = new db();

//Select to have all the information on the prompt
$querryPrompt = "SELECT Distinct * FROM prompts where CorrelationID=?";
$prompts = $db->query($querryPrompt, array($IDprompt));

//If the URL parameter for the CorrelationID is not in the prompts table this mean the prompt don't exist.
if ($prompts->numRows() == 0)
    die("Bad Request");
$promptInfos = $prompts->fetchArray();

// If the prompt is not a subscenario we get is EditCode and we manage the tags.
if (is_null($promptInfos['ParentID'])) {
    $EditCode = $db->EditCode($promptInfos['Id']);
    $tags = preg_split("/\,/", $promptInfos['Tags']);
} else
    //Else we get the EditCode of the very First Parent
    $EditCode =  $db->firstParentEditCode($promptInfos['ParentID']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // We set the session and redirect to the Edit page.
    if (password_verify($_POST["Command_GenerateCode"], $EditCode)) {
        $_SESSION['CodeEdit'] = $_POST["Command_GenerateCode"];
        $url = "Edit.php?ID=" . $IDprompt;
        header("Location: $url");
        exit();
    } else {
        $url = "Prompt.php?ID=" . $IDprompt . "&FalsePw=true";
        header("Location: $url");
        exit();
    }
}



//We get all the Worldinfos and Subscenarios of the selected prompt.
$Winfos = $db->worldInfos($promptInfos['Id']);
$Subs =  $db->subScenarios($IDprompt);


//Some old prompts don't have .scenario in the database so we need to make it.
if (is_null($promptInfos['NovelAIScenario'])) {
    $scenario = '{"scenarioVersion": 0, "tags": ["' . str_replace(', ', '", "', str_replace('"', '\"', $promptInfos['Tags'])) . '"], "title": "' . str_replace('"', '\"', $promptInfos['Title']) . '", "description":"' . str_replace('"', '\"', str_replace("\n", "\\n", $promptInfos['Description'])) . '","prompt":"' .  str_replace('"', '\"', str_replace("\n", "\\n", $promptInfos['PromptContent'])) . '", "context":[{"text":"' . str_replace('"', '\"', str_replace("\n", "\\n", $promptInfos['Memory'])) . '","contextConfig":{"prefix":"","suffix":"\n","tokenBudget":2048,"reservedTokens":0,"budgetPriority":800,"trimDirection":"trimBottom","insertionType":"token","insertionPosition":0}},{"text":"' . str_replace('"', '\"', str_replace("\n", "\\n", $promptInfos['AuthorsNote'])) . '","contextConfig":{"prefix":"","suffix":"\n","tokenBudget":2048,"reservedTokens":2048,"budgetPriority":-400,"trimDirection":"trimBottom","insertionType":"newline","insertionPosition":-4}}],
"lorebook": {
    "lorebookVersion": 1,
    "entries": [';

    if ($Winfos != 0) {
        foreach ($Winfos as $WInf) {

            $key =   preg_replace('!\s+!', ' ', str_replace('"', '\"', str_replace("\n", "\\n", $WInf['WKeys'])));
            $scenario .=  '{"text": "' . str_replace('"', '\"', str_replace("\n", "\\n", $WInf['Entry'])) . '", "keys": ["' . $key . '"], "displayName":"' .  preg_split("/\,/", $key)[0] . '" },';
        }
        $scenario =  substr($scenario, 0, -1);
    }

    $scenario .=  "]}}";
} else
    $scenario = $promptInfos['NovelAIScenario'];



// We need the PublishDate to know if the prompt is a draft or not.
$publish = $promptInfos['PublishDate'];

// If subscenario we get the PublishDate of the first Parent. SubScenario are managed as draft if the first parent is draft and as publish if the first parent is published.
$publishParent = $db->firstParentPublishDate($promptInfos['ParentID']);
if ($publishParent != "" || is_null($publishParent)) {
    $publish = $publishParent;
}

// If no EditCode in session and the prompt is a draft then you can't display it.
if ($EditCode != "") {
    if (is_null($publish)) {
        if (!isset($_SESSION['CodeEdit']))
            die("Bad Request");
        else if (!password_verify($_SESSION['CodeEdit'], $EditCode))
            die("Bad Request");
    }
}

//If the good CodeEdit is already in session you don't need to re-enter it to edit the prompt.
if (isset($_SESSION['CodeEdit'])) {
    if (password_verify($_SESSION['CodeEdit'], $EditCode))
        $goodCode = "true";
}

$db->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Prompt</title>
    <link rel="stylesheet" href="css/spage.css">
    <link rel="stylesheet" href="css/bootstrap.dark.css">
    <link rel="stylesheet" href="css/style.css">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js">
    </script>


</head>

<body>
    <!--Header never change-->
    <?php include('header.php'); ?>
    <div class="container">
        <main role="main" class="pb-3">
            <?php if (!is_null($promptInfos['ParentID'])) { ?>
                <div class="alert alert-primary">
                    You are viewing a sub scenario. <a href="<?php echo "Prompt.php?ID=" . $promptInfos['ParentID']; ?>">Click here to return to the parent.</a>
                </div> <?php } ?>
            <div class="d-flex">
                <div>
                    <h3>
                        <?php if (!isset($promptInfos['PublishDate'])) { ?>
                            <span class="mr-2 badge badge-warning">Draft</span>
                        <?php } ?>
                        <?php echo htmlspecialchars($promptInfos['Title']); ?>
                    </h3>
                    <code class="card-text pre-line" id="PNovelScen" hidden><?php echo htmlspecialchars($scenario) ?></code>

                    <p> <?php echo "Published on ", substr($promptInfos['PublishDate'], 0, 10); ?></p>
                </div>
                <div class="ml-auto d-flex">
                    <?php if ($EditCode != "" && !isset($goodCode)) { ?>
                        <div>

                            <form method="POST" name="PEdit" id="EditForm" action="<?php echo 'Prompt.php?ID=' . $IDprompt ?>" class="needs-validation" novalidate>

                                <label for="Command_GenerateCode" class="floating-label">Edit Code :</label>

                                <input type="text" class="form-floating view mb-3" id="Command_GenerateCode" name="Command.GenerateCode" value="" required /> <br>
                                <div class="invalid-feedback" id="invalidEdit"><?php if (isset($_GET['FalsePw'])) echo "Wrong Code!" ?></div>
                        </div>
                        <div>
                            <button type="submit" id="submitBtn" name="sub2" class="btn btn-outline-success mr-2 px-3">
                                Edit
                            </button>


                            </form>

                        </div> <?php } ?>

                    <div>
                        <?php if (isset($goodCode)) { ?>
                            <a class="btn btn-outline-success mr-2 px-3" href="<?php echo "Edit.php?ID=" . $IDprompt; ?>">Edit</a> <?php } ?>
                        <?php if (isset($promptInfos['PublishDate'])) { ?>
                            <a class="btn btn-outline-warning">Report</a> <?php } ?>
                    </div>
                </div>
            </div>
            <?php if (isset($tags)) { ?>
                <div class="d-flex">
                    <div class="mr-auto">
                        <p> Tags:
                            <?php if ($promptInfos['Nsfw'] > 0) { ?>
                                <a class="badge badge-danger" href="\AIDSprompts?NsfwSetting=2">NSFW</a>
                            <?php } ?>
                            <?php foreach ($tags as $t) if (!empty($t)) { { ?>
                                    <a class="badge badge-primary" href="<?php echo "\AIDSprompts?Tags=" . $t . "&MatchExact=true"; ?>"> <?php echo htmlspecialchars($t); ?> </a>
                            <?php }
                            } ?>
                        </p>
                    </div>
                </div> <?php  } ?>
            <div class="card mb-4 p-2">
                <details>
                    <summary>Export Options</summary>
                    <div class="mt-2 p-2">
                        <h4>NAI</h4>
                        <div class="d-flex flex-column flex-sm-row">
                            <div>

                                <button id="dscenar" type="button" class="btn btn-outline-light">Download .scenario</button>
                            </div>
                            <div class="js-only mt-3 mt-sm-0 ml-sm-3">
                                <button data-id="3809" id="get-nai-json" class="btn btn-secondary">Copy to clipboard</button>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
            <?php if (isset($promptInfos['Description'])) if ($promptInfos['Description'] != "") { ?>
                <h5>Description</h5>
                <div class="card mb-4">
                    <div class="card-body">
                        <code class="card-text pre-line"> <?php echo htmlspecialchars($promptInfos['Description']) ?></code>
                    </div>
                </div>
            <?php } ?>
            <h5>Prompt</h5>
            <div class="card mb-1">
                <div class="card-body">
                    <code class="card-text pre-line"> <?php echo htmlspecialchars($promptInfos['PromptContent']); ?></code>
                </div>
            </div>
            <div class="d-flex mb-4">
                <span class="ml-auto text-muted"> <?php echo strlen($promptInfos['PromptContent']) . " Characters"; ?></span>
            </div>
            <?php
            if ($promptInfos['Memory'] != "") { ?>
                <h5>Memory</h5>
                <div class="card mb-1">
                    <div class="card-body">
                        <code class="card-text pre-line"><?php echo  htmlspecialchars($promptInfos['Memory']); ?></code>
                    </div>
                </div>
                <div class="d-flex mb-4">
                    <span class="ml-auto text-muted"> <?php echo strlen($promptInfos['Memory']) . " Characters"; ?></span>
                </div>
            <?php } ?>
            <?php
            if ($promptInfos['AuthorsNote'] != "") { ?>
                <h5>Author&#x27;s Note</h5>
                <div class="card mb-1">
                    <div class="card-body">
                        <code class="card-text pre-line"><?php echo htmlspecialchars($promptInfos['AuthorsNote']); ?></code>
                    </div>
                </div>
                <div class="d-flex mb-4">
                    <span class="ml-auto text-muted"> <?php echo strlen($promptInfos['AuthorsNote']) . " Characters"; ?></span>
                </div>
            <?php } ?>
            <?php if ($Winfos != 0) {

            ?>
                <div class="d-flex mb-2">
                    <h5 class="mt-auto mb-0 card-title mr-auto">World Info</h5>
                </div>

                <div class="row">
                    <?php $i = 0;
                    foreach ($Winfos as $WInf) { ?>
                        <div class="col-sm-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Keys</h5>
                                    <code class="card-text pre-line"> <?php echo htmlspecialchars($WInf['WKeys']); ?></code>
                                    <hr />
                                    <h5>Entry</h5>
                                    <code class="card-text pre-line"><?php echo htmlspecialchars($WInf['Entry']); ?></code>
                                </div>
                            </div>
                        </div>
                        <br>
                    <?php if (++$i == 300) break;
                    } ?>
                </div>

            <?php } ?>
            <?php if ($Subs != 0) { ?>
                <h5>Sub Scenarios</h5>
                <?php foreach ($Subs as $Sub) { ?>
                    <div class="card mb-4">

                        <div class="card-body d-flex">
                            <a class="w-100 text-info m-auto" href="<?php echo "Prompt.php?ID=" . $Sub['CorrelationID']; ?>">
                                <h5 class="m-0"><?php echo  htmlspecialchars($Sub['Title']); ?> </h5>
                            </a>
                            <div class="m-auto">
                                <a class="btn btn btn-info" href="<?php echo "Prompt.php?ID=" . $Sub['CorrelationID']; ?>">
                                    View
                                </a>
                            </div>
                        </div>


                    </div>

            <?php }
            } ?>
        </main>
    </div>
    <script>
        function download(filename, text) {

            var element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
            element.setAttribute('download', filename);

            element.style.display = 'none';
            document.body.appendChild(element);

            element.click();

            document.body.removeChild(element);
        }


        $(document).ready(function() {

            $("#dscenar").click(function() {
                download("<?php print(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\-]/', '', $promptInfos['Title']))); ?>" + ".scenario", $("#PNovelScen").html());

            });
        });
    </script>
</body>

</html>
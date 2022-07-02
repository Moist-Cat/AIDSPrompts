<?php

// Class for secure MYSQL queries : Info Connection on config.php
require 'class\db.php';
session_start();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST["CodeEdit"])) {
        $_SESSION['CodeEdit'] = $_POST["CodeEdit"];
    }
    else
    {if(isset(($_SESSION['CodeEdit'])))
    unset($_SESSION['CodeEdit']);}
    $url = "Index.php?Query=" . $_POST["Query"] . "&NsfwSetting=" . $_POST["NsfwSetting"]. "&Tags=" . $_POST["Tags"]. "&MatchExact=" . $_POST["MatchExact"] . "&TagJoin=" . $_POST["TagJoin"];
    header("Location: $url");
    exit();

}

if (empty(array_diff($_GET, ['']))) {
    unset($_SESSION['CodeEdit']);
}

// Get all the parameters in URL for search function (title, tags, nsfw etc.)
$by_title = isset($_GET['Query']) ? $_GET['Query'] : null;
$by_tags =  isset($_GET['Tags']) ? $_GET['Tags'] : null;
$NsfwSetting = isset($_GET['NsfwSetting']) ? $_GET['NsfwSetting'] : "0";
// Same for seach option (Match Exact tags etc.)
$exact_tags =  isset($_GET['MatchExact']) ? $_GET['MatchExact'] : "false";
$tag_join =  isset($_GET['TagJoin']) ? $_GET['TagJoin'] : "0";

// Put them in a table to pass more easily when page is changed
$data = array(
    'Query' => $by_title,
    'Tags' => $by_tags,
    'MatchExact' => $exact_tags,
    'TagJoin' => $tag_join,
    'NsfwSetting' => $NsfwSetting
);

//List of variable for MySQLi Prepared Statements
$sqlparams = array();
$code ="";
// Declare a variable for our current page. If no page is set, the default is page 1
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;

// Declare numbers of prompts by page
$limit = 10;

// Declare an offset based on our current page (if we're not on page 1).
if (!empty($current_page) && $current_page > 1) {
    $offset = ($current_page * $limit) - $limit;
} else {
    $offset = 0;
}

// Limit the result of the Select used for pagination
$start_from = ($current_page  - 1) * $limit;

// Connection to MYSQL database
$db = new db();

// Select used to diplay the prompts on the page

$queryList = 'SELECT Distinct *, 1 FROM prompts where ParentID is Null and PublishDate is not null ';
If  (isset($_SESSION['CodeEdit'])) {
$code = $_SESSION['CodeEdit'];
$queryList = 'SELECT Distinct *  FROM prompts , editcode where ParentID is Null and BINARY CodeEdit =? and PromptID = prompts.Id ';
$sqlparams[] = $_SESSION['CodeEdit']; 
}

// If filter for the Title is actived we add the condition
if (!empty($by_title)) {
    $queryList = $queryList . "and Title like ?";
    $sqlparams[] = "%$by_title%";
}

// Same for Tags
if (!empty($by_tags)) {

    // We retrieve each tag by splitting at ','
    $alltags = preg_split("/\,/", $by_tags);

    // Could be done more cleanly ? 

    // If match exactly for tags
    if ($exact_tags == "true") {

        switch ($tag_join) {
                // Result must include all tags
            case "0":
                foreach ($alltags as $t) {
                    $t = trim($t);
                    $queryList = $queryList . " and (Tags LIKE ? or Tags LIKE ? or Tags LIKE ?)";
                    array_push($sqlparams, "%, $t,%", "%, $t", "$t,%");
                }

                break;
                //Result can include any tags
            case "1":
                $t = trim($alltags[0]);
                $queryList = $queryList . " and ((Tags LIKE ? or Tags LIKE ? or Tags LIKE ?)";
                array_push($sqlparams, "%, $t,%", "%, $t", "$t,%");
                foreach (array_slice($alltags, 1) as $t) {
                    $t = trim($t);
                    $queryList = $queryList . " or (Tags LIKE ? or Tags LIKE ? or Tags LIKE ?)";
                    array_push($sqlparams, "%, $t,%", "%, $t", "$t,%");
                }
                $queryList = $queryList . ")";


                break;
                // Result exclude all tags
            case "2":
                foreach ($alltags as $t) {
                    $t = trim($t);
                    $queryList = $queryList . " and (Tags NOT LIKE ? and Tags NOT LIKE ? and Tags NOT LIKE ?)";
                    array_push($sqlparams, "%, $t,%", "%, $t", "$t,%");
                }
                break;
        }
        // If match exactly unchecked for tags
    } else {
        switch ($tag_join) {
                // Result must include all tags
            case "0":
                foreach ($alltags as $t) {
                    $t = trim($t);
                    foreach ($alltags as $t) {
                        $queryList = $queryList . " and (Tags LIKE ?)";
                        $sqlparams[] = "%$t%"; 
                    }
                }

                break;
                //Result can include any tags
            case "1":
                $queryList = $queryList . " and ((Tags LIKE ?)";
                $t = trim($alltags[0]);
                $sqlparams[] = "%$t%"; 
                foreach (array_slice($alltags, 1) as $t) {
                    $t = trim($t);
                    $queryList = $queryList . " or (Tags LIKE ?)";
                    $sqlparams[] = "%$t%";
                }
                $queryList = $queryList . ")";


                break;
                // Result exclude all tags
            case "2":
                foreach ($alltags as $t) {
                    $t = trim($t);
                    $queryList = $queryList . " and (Tags NOT LIKE ?)";
                    $sqlparams[] = "%$t%";
                }
                break;
        }
    }
}

// NSFW Filter
if (!empty($NsfwSetting) && $NsfwSetting != "0") {
    $queryList = $queryList . " and NSFW = ?";
    $sqlparams[] = (int)$NsfwSetting - 1;
}


// We send the query without the Limit to count the number of prompt to display
if (empty($sqlparams))
    $totalcount = $db->query($queryList);
else
    $totalcount = $db->query($queryList, $sqlparams);
$totalcount = $totalcount->numRows();

// We use Limit to return only the $limit of prompt to display on current page
$queryList = $queryList . " order by CorrelationID desc LIMIT ?,?";
array_push($sqlparams, $start_from, $limit);
$prompts = $db->query($queryList, $sqlparams)->fetchAll();
$db->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Home</title>
    <link rel="stylesheet" href="css/bootstrap.dark.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .droite {
   margin-left: 1rem;
}
.droite2 
{
   margin-left: 2.25rem;
}
</style>
</head>

<body>
    <!--Header never change-->
    <?php include('header.php'); ?>
    <!--Structure is the same as old club-->
    <div class="container">
        <main role="main" class="pb-3">
        <form method="post" action="Index.php">
                <div class="row mb-4">
                    <div class="col-sm-9 col-md-10">
                        <div class="row">
                            <div class="col-sm-6 mb-4">
                                <input class="form-control" placeholder="Search Title" type="text" id="Query" name="Query" value="<?php echo $by_title?>" />
                            </div>
                            <div class="col-sm-6 mb-4">
                                <select class="form-control" data-val="true" data-val-required="The NsfwSetting field is required." id="NsfwSetting" name="NsfwSetting">
                                    <option <?php if($NsfwSetting==0) echo "selected ";?> value="0">SFW &amp; NSFW</option>
                                    <option  <?php if($NsfwSetting==1) echo "selected ";?> value="1">SFW only</option>
                                    <option  <?php if($NsfwSetting==2) echo "selected ";?> value="2">NSFW only</option>
                                </select>
                            </div>
                            <div class="col-sm-6 mb-4">
                                <input class="form-control" placeholder="Tags (comma delimited)" type="text" id="Tags" name="Tags" value="<?php echo $by_tags?>" />
                            </div>
                            <div class="col-sm-6 mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" checked="checked" data-val="true" data-val-required="The Match Tags Exactly field is required." id="MatchExact" name="MatchExact" value="true" />
                                    <label class="form-check-label" for="MatchExact">Match Tags Exactly</label>
                                    <input class="form-check-input droite" type="checkbox" data-val="true" data-val-required="The Reverse Results field is required." id="Reverse" name="Reverse" value="true" />
                                    <label class="form-check-label droite2" for="Reverse">Reverse Results</label>
                                </div>
                              
                                   
                           
                            </div>
                            <div class="col-sm-6 mb-4">
                                <select class="form-control" data-val="true" data-val-required="The Inclusive/Exclusive Tags field is required." id="TagJoin" name="TagJoin">
                                    <option <?php if($tag_join==0) echo "selected ";?> value="0" >Results must include all tags</option>
                                    <option <?php if($tag_join==1) echo "selected ";?> value="1">Results can include any tag</option>
                                    <option <?php if($tag_join==2) echo "selected ";?> value="2">Results exclude all tags</option>
                                </select>
                            </div>
                            <div class="col-sm-6 mb-4">
                   
                     
                        <input class="form-control" placeholder="CodeEdit" type="text" id="CodeEdit" name="CodeEdit" value="<?php echo $code?>" />
                         
                            </div>
                   
                        </div>
                    </div>
                    <div class="col-sm-3 col-md-2 mb-4 d-flex flex-row-reverse flex-sm-column">
                        <div class="d-flex flex-sm-row-reverse">
                            <button class="btn btn-lg btn-primary">Search</button>
                        </div>
                        <div class="d-flex flex-sm-row-reverse mt-sm-auto mr-auto mr-sm-0">

                            <button class="btn btn-outline-secondary" formaction="/home/random">Random</button>
                           
                        </div>
                    </div>
                </div>

            </form>

            <div class="row">
                <?php
                foreach ($prompts as $prompt) {
                    
                    $tags = preg_split("/\,/", $prompt['Tags']);
                ?>
                    <div class="col-sm-12 col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex">
                                    <a class="w-100" href="<?php echo "Prompt.php?ID=" . $prompt['CorrelationID']; ?>">
                                        <h5 class="card-title"><?php echo htmlspecialchars($prompt['Title']); ?></h5>

                                    </a>
                                    <?php if(!isset($prompt['PublishDate'])) { ?>
                                    <h4>
						                <span class="badge badge-warning">Draft</span>
					                </h4>
                                    <?php } ?>
                                </div>
                                <p tabindex="-1" class="tags truncated">
                                    <?php echo "Created: ", substr($prompt['DateCreated'], 0, 10); ?>
                                    <br />
                                    Tags:
                                    <?php
                                    if ($prompt['Nsfw'] > 0) { ?>
                                        <a class="badge badge-danger"  href="\AIDSprompts?NsfwSetting=2">NSFW</a> <?php } ?>
                                    <?php

                                    foreach ($tags as $t) {   if(!empty($t)) {?>
                                        <a class="badge badge-primary" href="<?php echo "?Tags=" . $t . "&MatchExact=true"; ?>"> <?php echo htmlspecialchars($t); ?> </a>

                                    <?php }} ?>
                                </p>
                                
                                <p class="card-text pre-line truncated"><?php if($prompt['Description']!="") echo htmlspecialchars($prompt['Description']); else echo htmlspecialchars($prompt['PromptContent'])  ?></p>
                            </div>
                            <div class="card-footer bg-transparent d-flex border-0">
                                <div class="ml-auto"></div>
                               <?php If  (isset($_SESSION['CodeEdit'])) { ?>
                                <a class="btn btn-outline-success mr-2 px-3" href="<?php echo "Edit.php?ID=".$prompt['CorrelationID']; ?>">Edit</a>
                                <?php }?>
                                <a class="align-self-end btn btn-primary" href="/3871">View Prompt</a>

                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <?php
            $total_pages = ceil($totalcount / $limit);
            if ($total_pages > 1) { ?>

                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center" max-size="10">

                        <?php
                        // When we're not on the first page, we'll have a paginator back to the beginning
                        if ($current_page > 1) { ?>

                            <li class="page-item"><a class="page-link" href="<?php echo '?page=1&' . http_build_query($data); ?>">First</a></li>

                            <?php
                        }

                        // Loop through page numbers
                        for ($page_in_loop = 1; $page_in_loop <= $total_pages; $page_in_loop++) {
                            // if the total pages is more than 2, we can limit the pagination. We'll also give the current page some classes to disable and style it in css
                            // if the page in the loop is more between 

                            if ($total_pages > 3) {
                                if (($page_in_loop >= $current_page - 5 && $page_in_loop <= $current_page)  || ($page_in_loop <= $current_page + 5 && $page_in_loop >= $current_page)) {  ?>

                                    <li class="page-item <?php echo $page_in_loop == $current_page ? 'active disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo '?page=' . $page_in_loop . "&" . http_build_query($data); ?> "><?php echo $page_in_loop; ?></a>
                                    </li>

                                <?php }
                            }
                            // if the total pages doesn't look ugly, we can display all of them
                            else { ?>

                                <li class="page-item <?php echo $page_in_loop == $current_page ? 'active disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo '?page=' . $page_in_loop . "&" . http_build_query($data); ?> "><?php echo $page_in_loop; ?></a>
                                </li>

                            <?php } // End if   
                            ?>

                        <?php } // end for loop

                        // and the last page
                        if ($current_page < $total_pages) { ?>

                            <li class="page-item"><a class="page-link" href="<?php echo '?page=' . $total_pages . "&" . http_build_query($data); ?>">Last</a></li>

                        <?php } ?>
                    </ul>
                </nav>

            <?php } // End if total pages more than 1 
            ?>
        </main>
    </div>

     </body>
</html>
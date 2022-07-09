<?php

// Class for secure MYSQL queries : Info Connection on config.php
require 'class/db.php';
session_start();

if (isset($_GET['Random'])) {
    $db = new db();
    $rand = $db->promptRandom();
    $db->close();
    header("Location: Prompt.php?ID=" . $rand);
    exit();
}

if (empty(array_diff($_GET, ['']))) {
    unset($_SESSION['SearchCode']);
}

// Get all the parameters in URL for search function (title, tags, nsfw etc.)
$by_title = isset($_GET['Query']) ? $_GET['Query'] : null;
$by_tags =  isset($_GET['Tags']) ? $_GET['Tags'] : null;
$searchCode = isset($_GET['SearchCode']) ? $_GET['SearchCode'] : null;
$NsfwSetting = isset($_GET['NsfwSetting']) ? $_GET['NsfwSetting'] : "0";
// Same for seach option (Match Exact tags etc.)
$exact_tags =  isset($_GET['MatchExact']) ? $_GET['MatchExact'] : "false";
$tag_join =  isset($_GET['TagJoin']) ? $_GET['TagJoin'] : "0";
$reverse = isset($_GET['Reverse']) ? $_GET['Reverse'] : "false";

// Put them in a table to pass more easily when page is changed
$data = array(
    'Query' => $by_title,
    'Tags' => $by_tags,
    'MatchExact' => $exact_tags,
    'TagJoin' => $tag_join,
    'NsfwSetting' => $NsfwSetting,
    'SearchCode' => $searchCode
);

//List of variable for MySQLi Prepared Statements
$sqlparams = array();
$prompts = array();
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
if (!empty($searchCode)) {
    $_SESSION['SearchCode'] = $searchCode;
    $queryList = 'SELECT Distinct *  FROM prompts , editcode where ParentID is Null and PromptID = prompts.Id and SearchCode = ?';
    $sqlparams[] = $searchCode;
} else {

    unset($_SESSION['SearchCode']);
}

// If filter for the Title is actived we add the condition
if (!empty($by_title)) {
    $queryList .= "and Title like ?";
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
                    $queryList .= " and (Tags LIKE ? or Tags LIKE ? or Tags LIKE ?)";
                    array_push($sqlparams, "%, $t,%", "%, $t", "$t,%");
                }

                break;
                //Result can include any tags
            case "1":
                $t = trim($alltags[0]);
                $queryList .= " and ((Tags LIKE ? or Tags LIKE ? or Tags LIKE ?)";
                array_push($sqlparams, "%, $t,%", "%, $t", "$t,%");
                foreach (array_slice($alltags, 1) as $t) {
                    $t = trim($t);
                    $queryList .= " or (Tags LIKE ? or Tags LIKE ? or Tags LIKE ?)";
                    array_push($sqlparams, "%, $t,%", "%, $t", "$t,%");
                }
                $queryList .= ")";


                break;
                // Result exclude all tags
            case "2":
                foreach ($alltags as $t) {
                    $t = trim($t);
                    $queryList .= " and (Tags NOT LIKE ? and Tags NOT LIKE ? and Tags NOT LIKE ?)";
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
                        $queryList .= " and (Tags LIKE ?)";
                        $sqlparams[] = "%$t%";
                    }
                }

                break;
                //Result can include any tags
            case "1":
                $queryList .= " and ((Tags LIKE ?)";
                $t = trim($alltags[0]);
                $sqlparams[] = "%$t%";
                foreach (array_slice($alltags, 1) as $t) {
                    $t = trim($t);
                    $queryList .= " or (Tags LIKE ?)";
                    $sqlparams[] = "%$t%";
                }
                $queryList = $queryList . ")";


                break;
                // Result exclude all tags
            case "2":
                foreach ($alltags as $t) {
                    $t = trim($t);
                    $queryList .= " and (Tags NOT LIKE ?)";
                    $sqlparams[] = "%$t%";
                }
                break;
        }
    }
}

// NSFW Filter
if (!empty($NsfwSetting) && $NsfwSetting != "0") {
    $queryList .= " and NSFW = ?";
    $sqlparams[] = (int)$NsfwSetting - 1;
}


// We send the query without the Limit to count the number of prompt to display

if (empty($sqlparams))
    $totalcount = $db->query($queryList);
else
    $totalcount = $db->query($queryList, $sqlparams);

$totalcount = $totalcount->numRows();

// We use Limit to return only the $limit of prompt to display on current page
if (!$prompts) {
    $queryList .= " order by CorrelationID ";
    if ($reverse == "false")
        $queryList .= "desc ";
    $queryList .= "LIMIT ?,?";
    array_push($sqlparams, $start_from, $limit);
    $prompts = $db->query($queryList, $sqlparams)->fetchAll();
} else {
    $prompts = array_splice($prompts, $start_from, $limit);
}
$db->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Home</title>
    <link rel="stylesheet" href="css/bootstrap.dark.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!--Header never change-->
    <?php include('header.php'); ?>
    <!--Structure is the same as old club-->
    <div class="container">
        <main role="main" class="pb-3">
            <form method="get" action="Index.php">
                <div class="row mb-4">
                    <div class="col-sm-9 col-md-10">
                        <div class="row">
                            <div class="col-sm-6 mb-4">
                                <input class="form-control" placeholder="Search Title" type="text" id="Query" name="Query" value="<?php echo $by_title ?>" />
                            </div>
                            <div class="col-sm-6 mb-4">
                                <select class="form-control" data-val="true" data-val-required="The NsfwSetting field is required." id="NsfwSetting" name="NsfwSetting">
                                    <option <?php if ($NsfwSetting == 0) echo "selected "; ?> value="0">SFW &amp; NSFW</option>
                                    <option <?php if ($NsfwSetting == 1) echo "selected "; ?> value="1">SFW only</option>
                                    <option <?php if ($NsfwSetting == 2) echo "selected "; ?> value="2">NSFW only</option>
                                </select>
                            </div>
                            <div class="col-sm-6 mb-4">
                                <input class="form-control" placeholder="Tags (comma delimited)" type="text" id="Tags" name="Tags" value="<?php echo $by_tags ?>" />
                            </div>
                            <div class="col-sm-6 mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" <?php if ($exact_tags != "false" || empty(array_diff($_GET, ['']))) echo 'checked="checked"' ?> data-val="true" data-val-required="The Match Tags Exactly field is required." id="MatchExact" name="MatchExact" value="true" />
                                    <label class="form-check-label" for="MatchExact">Match Tags Exactly</label>
                                    <input class="form-check-input droite" type="checkbox" <?php if ($reverse != "false") echo 'checked="checked"' ?> data-val="true" data-val-required="The Reverse Results field is required." id="Reverse" name="Reverse" value="true" />
                                    <label class="form-check-label droite2" for="Reverse">Reverse Results</label>
                                </div>



                            </div>
                            <div class="col-sm-6 mb-4">
                                <select class="form-control" data-val="true" data-val-required="The Inclusive/Exclusive Tags field is required." id="TagJoin" name="TagJoin">
                                    <option <?php if ($tag_join == 0) echo "selected "; ?> value="0">Results must include all tags</option>
                                    <option <?php if ($tag_join == 1) echo "selected "; ?> value="1">Results can include any tag</option>
                                    <option <?php if ($tag_join == 2) echo "selected "; ?> value="2">Results exclude all tags</option>
                                </select>
                            </div>
                            <div class="col-sm-6 mb-4">


                                <input class="form-control" placeholder="SearchCode" type="text" id="SearchCode" name="SearchCode" value="<?php echo $searchCode ?>" />

                            </div>

                        </div>
                    </div>
                    <div class="col-sm-3 col-md-2 mb-4 d-flex flex-row-reverse flex-sm-column">
                        <div class="d-flex flex-sm-row-reverse">
                            <button class="btn btn-lg btn-primary">Search</button>
                        </div>
                        <div class="d-flex flex-sm-row-reverse mt-sm-auto mr-auto mr-sm-0">

                            <button class="btn btn-outline-secondary" name="Random">Random</button>

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
                                    <?php if (!isset($prompt['PublishDate'])) { ?>
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
                                        <a class="badge badge-danger" href="?NsfwSetting=2">NSFW</a> <?php } ?>
                                    <?php

                                    foreach ($tags as $t) {
                                        if (!empty($t)) { ?>
                                            <a class="badge badge-primary" href="<?php echo "?Tags=" . $t . "&MatchExact=true"; ?>"> <?php echo htmlspecialchars($t); ?> </a>

                                    <?php }
                                    } ?>
                                </p>

                                <p class="card-text pre-line truncated"><?php if ($prompt['Description'] != "") echo htmlspecialchars($prompt['Description']);
                                                                        else echo htmlspecialchars($prompt['PromptContent'])  ?></p>
                            </div>
                            <div class="card-footer bg-transparent d-flex border-0">
                                <div class="ml-auto"></div>
                                <a class="align-self-end btn btn-primary" href="<?php echo "Prompt.php?ID=" . $prompt['CorrelationID']; ?>">View Prompt</a>
                            </div>
                        </div>
                    </div>
                <?php }
                ?>
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

            <?php }
            ?>
        </main>
    </div>

</body>

</html>
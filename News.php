<?php
require 'class\db.php';
$db = new db();
//News are in the table news. Each return to line is morphed into a <li>
$querryNews = "SELECT Distinct * FROM news";
$news = $db->query($querryNews)->fetchAll();
?>
<!DOCTYPE html>
<html>

<head>
    <title>News</title>
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

            <h2>What's New?</h2>
            <br />
            <div class="row">
                <?php foreach ($news as $new) { ?>
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4"><?php echo htmlspecialchars($new['Date']) . " - " . htmlspecialchars($new['Title']) ?> </h5>
                                <ul>
                                    <li class="mb-3">
                                        <?php echo str_replace("\n", ' </li> <li class="mb-3">', $new['Content']) ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </main>
    </div>
</body>
<html>
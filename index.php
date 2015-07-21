<?php
require_once "functions.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Leaderboard</title>

        <!-- Bootstrap -->
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
        <style>
            .table >tbody>tr:nth-child(odd)>td, 
            .table>tbody>tr:nth-child(odd)>th {
                background-color: #FFF6C7;
            }
            .table >tbody>tr:nth-child(even)>td, 
            .table>tbody>tr:nth-child(even)>th {
                background-color: #FFFBDF;
            }
            .container{
                width:80%;
                margin: 80px auto;
            }
            .header{
                margin-left:30%;
                margin-bottom:80px;
            }
            img{
                margin-right:20px;
            }
            .display-date{
                font-weight: bold;
            }
            .load-button{
                margin-left:40%;
            }
            .danger{
                color: red;
                font-size: 20px;
            }
        </style>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
        <script>
            //Submit form when there is a change in the selector
            $(document).ready(function() {
                $('#selectForm').change(function() {
                    durationSelector.submit();
                });

                //"Load more" Click Ajax request to get additional rows
                $('.loadmore').click(function() {
                    $(this).text('Loading...');
                    var ele = $(this).parent('div');
                    $.ajax({
                        url: 'loadmore.php',
                        type: 'POST',
                        data: {
                            duration: $('#selectForm').val(),
                            rowCount: $('.table tr').length

                        },
                        success: function(response) {
                            if (response) {
                                if ($(response).last().attr('name') === "displayLoad" && $(response).last().val() == 'hide')
                                {
                                    ele.hide();
                                }
                                $(".table").append(response);
                            }
                        }});
                });
            });
        </script>
    </head>
    <body>
        <div class ='container'>
            <?php
            if (isset($GLOBALS['voicestormAccessToken']) && !empty($GLOBALS['voicestormAccessToken']) && isset($GLOBALS['voicestormTokenSecret']) && !empty($GLOBALS['voicestormTokenSecret']) && isset($GLOBALS['voicestormBaseUrl']) && !empty($GLOBALS['voicestormBaseUrl']))
            {
                ?>
                <div class="row header">
                    <form action=<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?> method="get" name="durationSelector" class="form-inline col-md-3">
                        <div class="form-group">
                            <select class="form-control" id="selectForm" name="duration">
                                <option value="currentweek">Last 7 Days</option>
                                <option value="currentmonth" selected="selected">Last 30 Days</option>
                                <option value="prevweek">Last Week</option>
                                <option value="prevmonth">Last Month</option>
                                <option value="thisyear">This Year</option>
                            </select>                  
                        </div>  
                    </form>
                    <?php
                    $endDate = gmdate('c');
                    $startDate = gmdate('c', strtotime("-1 month"));
                    $duration = 'currentmonth';
                    $requestServer = true;
                    $fc = new FileCache($GLOBALS['userCache']);
                    if (!empty($_GET) && isset($_GET["duration"]))
                    {
                        //Compute the start and end date based on selector value

                        $getValue = htmlspecialchars($_GET["duration"]);
                        switch ($getValue)
                        {
                            case "currentweek":
                                $endDate = gmdate('c');
                                $startDate = gmdate('c', strtotime("-1 week"));
                                $duration = $getValue;
                                break;
                            case "prevweek":
                                $startDate = gmdate('c', strtotime('last week monday'));
                                $endDate = gmdate('c', strtotime('last week sunday'));
                                $duration = $getValue;
                                break;
                            case "prevmonth":
                                $startDate = gmdate('c', strtotime('first day of last month'));
                                $endDate = gmdate('c', strtotime('last day of last month'));
                                $duration = $getValue;
                                break;
                            case "thisyear":
                                $endDate = gmdate('c');
                                $startDate = gmdate('c', strtotime('-1 year'));
                                $duration = $getValue;
                                break;
                        }
                    }
                    ?>
                    <!--Retain the select value after form submission-->
                    <script>$('#selectForm').val('<?php echo $duration; ?>');</script> 
                    <span class='col-md-9 display-date'><?php
                        echo date('M d, Y', strtotime($startDate));
                        echo ' to ' . date('M d, Y', strtotime($endDate));
                        ?></span>
                </div>
                <?php
                //Make server call for Reports only if the file is older than hour
                if (file_exists($GLOBALS['reportCache'] . '/' . $duration . '.csv') && time() - filemtime($GLOBALS['reportCache'] . '/' . $duration . '.csv') < 1 * 3600)
                {
                    $response = "Success";
                }
                else
                {
                    $response = voicestormApiRequest("GET", "/reports/csv", array("ReportType" => "Member", "StartDate" => $startDate, "EndDate" => $endDate), $duration);
                }

                if ($response == "Success")
                {
                    $userIdColumn = 0;

                    $f = fopen($GLOBALS['reportCache'] . '/' . $duration . '.csv', "r");
                    $row = 0;

                    //Loop through csv and get all UserIds
                    $userIdList = [];
                    while (($line = fgetcsv($f)) !== false)
                    {
                        if ($row > 0)
                        {
                            if ($row == 1)
                            {
                                $column = 0;
                                foreach ($line as $cell)
                                {
                                    if ($line[$column] == 'UserId')
                                    {
                                        $userIdColumn = $column;
                                    }
                                    $column++;
                                }
                            }
                            else
                            {
                                //Request the users that are not in the filecache or if the user cache is older than an hour

                                if ($fc->get($line[$userIdColumn]) && time() - filemtime($fc->getPath($line[$userIdColumn])) < 1 * 3600)
                                {
                                    $row++;
                                    continue;
                                }
                                else
                                {
                                    $userIdList[] = $line[$userIdColumn];
                                }
                            }
                        }
                        $row++;
                    }
                    fclose($f);
                    if (count($userIdList) > 0)
                    {
                        //Request User Information from the server
                        //If the UserIds are too many please make sure to send it in batches rather than making single call

                        $userResponse = voicestormApiRequest("GET", "/users", array("ids" => json_encode($userIdList), "include" => "images"));

                        if (array_key_exists('users', $userResponse))
                        {
                            foreach ($userResponse['users'] as $user)
                            {
                                $arr['displayName'] = array_key_exists('displayName', $user) && !empty(trim($user['displayName'])) ? $user['displayName'] : $user['userName'];
                                $arr['profilePictureImages'] = array_key_exists('profilePictureImages', $user) ? $user['profilePictureImages'] : [];
                                $fc->add($user['id'], $arr);
                            }
                        }
                    }
                    $result = createRows($duration, 0);
                    echo $result;
                }
            }
            else
            {
                ?>
                <p class="danger">Please open config.php and set the required variables</p>
            <?php } ?>


        </div>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    </body>
</html>

<?php

/* !!!! Configure the following !!!! */

// Change the following to the url of your Onlyoffice server
$baseURL = 'http://your.onlyoffice.portal.com';

// The following adds to the OO server url the path to the API
$baseurlAPI = $baseURL.'/API/2.0';

// Create a file in the same location as this .php script and place
// the auth token you created.
// Instructions are here: https://api.onlyoffice.com/portals/auth
// The name of the file needs to match the following:
$token = stripslashes(trim(file_get_contents("authtoken.txt")));

/* How to use:

Show all the projects in your browser:
http://serverwherethislives/projectlist.php

Filter for a tag called stuff:
http://serverwherethislives/projectlist.php?tag=stuff

*/

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Projects Summary</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <link href="theme.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="container theme-showcase" role="main">

<?php           

            
$tag = '';
if (isset($_GET['tag'])) { $tag = '/tag/'.$_GET['tag']; }
$sortBy = '';
if (isset($_GET['sortBy'])) { 
    $sortBy = 'sortBy='.$_GET['$sortBy']; 
} else {
    $sortBy = 'sortBy=title';
}
            
// Get List of All Projects
$projectList = curlGetData($baseurlAPI.'/project'.$tag.'?'.$sortBy,$token);
$numProjects = $projectList['count'];
    
// Get List of All Tags
$tagList = curlGetData($baseurlAPI.'/project/tag',$token);
            
echo '<div class="row">';
    echo '<div class="col-md-2">';
        echo '<h3><span class="label label-default">Project count: '.$numProjects.'</span></h3>';
    echo '</div>';
    echo '<div class="col-md-3">';
        if ($tag) {echo '<h3><span class="label label-default">Filtered by tag: '.$_GET['tag'].'</span></h3>';}
            else {echo '';}
    echo '</div>';
    echo '<div class="col-md-7">';
        // TODO: Show list of tags to allow filtering by clicking
    echo '</div>';
echo '</div>';
            
foreach ($projectList['response'] as $project) {
    $projectDetailResponse = curlGetData($baseurlAPI.'/project/'.$project['id'],$token);
    $projectDetail = $projectDetailResponse['response'];

    switch ($projectDetail['status']) {
        case 0:
            $projectStatus = '<H4><span class="label label-warning">Open</span></H4>';
            break;
        case 1:
            $projectStatus = '<H4><span class="label label-success">Closed</span></H4>';
            break;
        case 2:
            $projectStatus = '<H4><span class="label label-primary">Paused</span></H4>';
            break;
    }
?>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <?php echo '<h3 class="panel-title"><a href="'.$baseURL.'/products/projects/tasks.aspx?prjID='.$project['id'].'" target="_blank">'.$project['title'].'</a></h3>'.$projectStatus; ?>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php echo '<strong>Project Manager</strong><BR>'.$project['responsible']['displayName']; ?>
                        </div>
                        <div class="col-md-4">
                            <?php echo '<strong>Created</strong><BR>'.cleanDateString($projectDetail['created'],TRUE).' <strong>:</strong> '.$projectDetail['createdBy']['displayName']; ?>
                        </div>
                        <div class="col-md-4">
                            <?php echo '<strong>Last Update</strong><BR>'.cleanDateString($projectDetail['updated'],TRUE).' <strong>:</strong> '.$projectDetail['updatedBy']['displayName']; ?>
                        </div>
                    </div> 

                    <?php

                    // ----- Description -----
                    if ($project['description']) {
                    ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Description</h4>
                                <?php echo $project['description']; ?>
                            </div>
                        </div>
                    <?php
                    }

                    // ----- Project Team -----
                    if ($projectDetail['participantCount']) {
                        /*
                        TODO for Project Team
                        1. Seperate by groups
                        2. Links to people info
                        */
                        $teamList = curlGetData($baseurlAPI.'/project/'.$project['id'].'/team?sortBy=lastName',$token);
                        $teamListString = makeListString($teamList['response'],'displayName')
                    ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Project Team</h4>
                            </div>
                            <div class="col-md-12">
                                <?php echo $teamListString;?>
                            </div>
                        </div>
                    <?php
                    }
    
                    // ----- Milestones and Tasks -----
                    /*
                        TODO
                        - Add responsibles for each using makeListString()
                        - Last updated? In "days ago" or "today" or "yesterday"
                    */
    
                    if ($projectDetail['taskCount']) {
                        $milestoneArray = array();
                        $nonMilestoneTasks = '';    
                        
                        // Milestones
                        // Build array with milestones HTML. Index of array is milestone ID.        
                        $milestoneList = curlGetData($baseurlAPI.'/project/'.$project['id'].'/milestone',$token);
                        
                        foreach ($milestoneList['response'] as $milestoneDetail) {
                            $thisRow = '';
                            $thisRow .= '<tr class="milestone">';
                                $thisRow .= '<td><img src="https://png.icons8.com/milestone-filled/ios7/25" title="Milestone Filled" width="25" height="25"></td>';
                                $thisRow .= '<td><span class="label '.($milestoneDetail['status'] ? 'label-success">closed milestone' : 'label-warning">open milestone' ).'</span></td>'; // need to do if open/close
                                $thisRow .= '<td nowrap><a href="'.$baseURL.'/products/projects/milestones.aspx?prjID='.$project['id'].'#sortBy=deadline&sortOrder=ascending" target="_blank">'.$milestoneDetail['title'].'</a></td>';
                                $thisRow .= '<td>'.$milestoneDetail['description'].'</td>';
                                $thisRow .= '<td> </td>';
                                $thisRow .= '<td> </td>';
                                if ($milestoneDetail['status']) {
                                    $thisRow .= '<td class="center">closed</td>';
                                } else {
                                    $thisRow .= '<td nowrap class="center">'.($milestoneDetail['deadline'] ? deadline($milestoneDetail['deadline']):'').'</td>';
                                }
                            $thisRow .= '</tr>';   
                            $milestoneArray[$milestoneDetail['id']]['milestoneHTML'] = $thisRow;
                        }
                        // Tasks
                        // First do open tasks
                        $taskList = curlGetData($baseurlAPI.'/project/'.$project['id'].'/task/open?sortBy=deadline',$token);  
                        foreach ($taskList['response'] as $task) {
                            // get comment count for this task
                            $taskDetail = curlGetData($baseurlAPI.'/project/task/'.$task['id'],$token);
                            $commentCount = ($taskDetail['response']['commentsCount'] ? $taskDetail['response']['commentsCount'] : '');
                            if ($task['milestoneId']) {
                                // build and add the HTML of the row to an array with the milestone id as the index
                                $thisRow = '';
                                $thisRow .= '<tr class="task">';
                                    $thisRow .= '<td></td>';
                                    $thisRow .= '<td class=taskstatus><span class="label label-warning">open task</span></td>';
                                    $thisRow .= '<td><a href="'.$baseURL.'/products/projects/tasks.aspx?prjID='.$project['id'].'&id='.$task['id'].'" target="_blank">'.$task['title'].'</a></td>';
                                    $thisRow .= '<td>'.$task['description'].'</td>';
                                    $thisRow .= '<td class="center"><a href="'.$baseURL.'/products/projects/tasks.aspx?prjID='.$project['id'].'&id='.$task['id'].'" target="_blank"><span class="badge">'.$commentCount.'</span></a></td>';
                                    $thisRow .= '<td class="center">'.($task['priority'] ? '<span class="label label-danger">high</span>' : '<span class="label label-info">normal</span>').'</td>';
                                    $thisRow .= '<td nowrap class="center">'.($task['deadline'] ? deadline($task['deadline']):'').'</td>';
                                $thisRow .= '</tr>';   
                                
                                $milestoneArray[$task['milestoneId']]['taskHTML'] .= $thisRow;

                                // if this task has subtasks, build those rows and attach the array too
                                // subtasks would go under the assicated task row, indented and maybe with icon to signify
                                foreach ($task['subtasks'] as $subtask) {  
                                    $milestoneArray[$task['milestoneId']]['taskHTML'] .= buildSubtaskRow($subtask, $project['id'], $task['id'], TRUE, $baseURL);
                                }
                            } else {
                                // build and add the HTML of the row to an array where all do not have milestones
                                $nonMilestoneTasks .= '<tr class="task">';
                                    $nonMilestoneTasks .= '<td class="taskstatus"><span class="label label-warning">open task</span></td>';
                                    $nonMilestoneTasks .= '<td><a href="'.$baseURL.'/products/projects/tasks.aspx?prjID='.$project['id'].'&id='.$task['id'].'" target="_blank">'.$task['title'].'</a></td>';
                                    $nonMilestoneTasks .= '<td>'.$task['description'].'</td>';
                                    $nonMilestoneTasks .= '<td class="center"><a href="'.$baseURL.'/products/projects/tasks.aspx?prjID='.$project['id'].'&id='.$task['id'].'" target="_blank"><span class="badge">'.$commentCount.'</span></a></td>';
                                    $nonMilestoneTasks .= '<td class="center">'.($task['priority'] ? '<span class="label label-danger">high</span>' : '<span class="label label-info">normal</span>').'</td>';
                                    $nonMilestoneTasks .= '<td nowrap class="center">'.($task['deadline'] ? deadline($task['deadline']):'').'</td>';
                                $nonMilestoneTasks .= '</tr>';                                             

                                // if this task has subtasks, build those rows and attach the array too
                                // subtasks would go under the assicated task row, indented and maybe with icon to signify  
                                foreach ($task['subtasks'] as $subtask) {
                                    $nonMilestoneTasks .= buildSubtaskRow($subtask, $project['id'], $task['id'], FALSE, $baseURL);
                                }                                    
                            }
                        }   
                        // Do it again for closed tasks
                        $taskList = curlGetData($baseurlAPI.'/project/'.$project['id'].'/task/closed?sortBy=deadline',$token);  
                        
                        
                        foreach ($taskList['response'] as $task) {
                            $taskDetail = curlGetData($baseurlAPI.'/project/task/'.$task['id'],$token);
                            $commentCount = ($taskDetail['response']['commentsCount'] ? $taskDetail['response']['commentsCount'] : '');
                            if ($task['milestoneId']) {
                                // build and add the HTML of the row to an array with the milestone id as the index
                                $thisRow = '';
                                $thisRow .= '<tr class="task">';
                                    $thisRow .= '<td> </td>';
                                    $thisRow .= '<td class="taskstatus"><span class="label label-success">closed task</span></td>';
                                    $thisRow .= '<td><a href="'.$baseURL.'/products/projects/tasks.aspx?prjID='.$project['id'].'&id='.$task['id'].'" target="_blank">'.$task['title'].'</a></td>';
                                    $thisRow .= '<td>'.$task['description'].'</td>';
                                    $thisRow .= '<td class="center"><a href="'.$baseURL.'/products/projects/tasks.aspx?prjID='.$project['id'].'&id='.$task['id'].'" target="_blank"><span class="badge">'.$commentCount.'</span></a></td>';
                                    $thisRow .= '<td class="center">'.($task['priority'] ? '<span class="label label-danger">high</span>' : '<span class="label label-info">normal</span>').'</td>';
                                    $thisRow .= '<td class="center">closed</td>';
                                $thisRow .= '</tr>';   

                                $milestoneArray[$task['milestoneId']]['taskHTML'] .= $thisRow;

                                // if this task has subtasks, build those rows and attach the array too
                                // subtasks would go under the assicated task row, indented and maybe with icon to signify
                                foreach ($task['subtasks'] as $subtask) {
                                    $milestoneArray[$task['milestoneId']]['taskHTML'] .= buildSubtaskRow($subtask, $project['id'], $task['id'], TRUE, $baseURL);
                                }                                    
                            } else {
                                // build and add the HTML of the row to an array where all do not have milestones
                                $nonMilestoneTasks .= '<tr class="task">';
                                    $nonMilestoneTasks .= '<td class="taskstatus"><span class="label label-success">closed task</span></td>';
                                    $nonMilestoneTasks .= '<td><a href="'.$baseURL.'/products/projects/tasks.aspx?prjID='.$project['id'].'&id='.$task['id'].'" target="_blank">'.$task['title'].'</a></td>';
                                    $nonMilestoneTasks .= '<td>'.$task['description'].'</td>';
                                    $nonMilestoneTasks .= '<td class="center"><a href="'.$baseURL.'/products/projects/tasks.aspx?prjID='.$project['id'].'&id='.$task['id'].'" target="_blank"><span class="badge">'.$commentCount.'</span></a></td>';
                                    $nonMilestoneTasks .= '<td class="center">'.($task['priority'] ? '<span class="label label-danger">high</span>' : '<span class="label label-info">normal</span>').'</td>';
                                    $nonMilestoneTasks .= '<td class="center">closed</td>';
                                $nonMilestoneTasks .= '</tr>';                                             

                                // if this task has subtasks, build those rows and attach the array too
                                // subtasks would go under the assicated task row, indented and maybe with icon to signify 
                                foreach ($task['subtasks'] as $subtask) {
                                    $nonMilestoneTasks .= buildSubtaskRow($subtask, $project['id'], $task['id'], FALSE, $baseURL);
                                }                                       
                            }
                        }   

                        // OUTPUT Milestones and Tasks
                        if ($milestoneArray) {  
                        ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Milestones and Tasks</h4>
                            <div class="col-md-12">
                                <table class="table table-condensed">
                                    <thead>
                                      <tr>
                                        <th></th>
                                        <th></th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th class="center">Comments</th>
                                        <th class="center">Priority</th>
                                        <th class="center">Due Date</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($milestoneArray as $milestoneID=>$theseMilestones) {                                        
                                                echo $theseMilestones['milestoneHTML'];
                                                echo $theseMilestones['taskHTML'];
                                        }
                                        ?>
                                    </tbody>
                              </table>
                            </div>
                            </div>
                        </div>  
                        <?php
                        }
                        if ($nonMilestoneTasks) {
                        ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Tasks Without Milestones</h4>
                            <div class="col-md-12">
                                <table class="table table-condensed">
                                    <thead>
                                      <tr>
                                        <th></th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th class="center">Comments</th>
                                        <th class="center">Priority</th>
                                        <th class="center">Due Date</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                        <?php echo $nonMilestoneTasks; ?>
                                    </tbody>
                              </table>
                            </div>
                            </div>
                        </div>                         
                        <?php 
                        }
                    }

                    // ----- Discussions -----
                    if ($projectDetail['discussionCount']) {
                        /*
                        TODO for Discussions
                        1. Make into table with created by and created on and last reply by and total reply count
                        */
                        $discussionList = curlGetData($baseurlAPI.'/project/'.$project['id'].'/message',$token);
                        //$discussionListString = makeListString($discussionList['response'],'title');
                        ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Discussions</h4>
                            </div>
                            <div class="col-md-12">
                              <table class="table">
                                <thead>
                                  <tr>
                                    <th>Title</th>
                                    <th>Comments</th>
                                    <th>Last Update</th>
                                  </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($discussionList['response'] as $discussion) {
                                    echo '<tr>';
                                        echo '<td><a href="'.$baseURL.'/products/projects/messages.aspx?prjID='.$project['id'].'&id='.$discussion['id'].'" target="_blank">'.$discussion['title'].'</a></td>';
                                        echo '<td>'.$discussion['commentsCount'].'</td>';
                                        echo '<td>'.discussLastUpdate($discussion, $baseurlAPI, $token).'</td>'; 
                                    echo '</tr>';
                                    
                                }
                                ?>
                                </tbody>
                              </table>
                            </div>
                        </div>
                    <?php
                    }
    
                    // ----- Files and Folders -----
                    if ($projectDetail['documentsCount']) {
                        $fileList = curlGetData($baseurlAPI.'/project/'.$project['id'].'/files',$token);                            
                        $listHTML = '';

                        // build file list HTML
                        foreach ($fileList['response']['files'] as $fileDetail) {
                            $listHTML = $listHTML.'<li class="document"><a href="'.$baseURL.$fileDetail['webUrl'].'" target="_blank">'.$fileDetail['title'].'</a></li>';
                        }

                        // build folder list HTML
                        foreach ($fileList['response']['folders'] as $folderDetail) {
                            $filesCount = $folderDetail['filesCount'];
                            $filesCountHTML= '';
                            if ($filesCount == 1) {
                                $filesCountHTML = '<span class="badge">1 file</span>';
                            }
                            elseif ($filesCount > 1) {
                                $filesCountHTML = '<span class="badge">'.$filesCount.' files</span>';
                            }

                            $foldersCount = $folderDetail['foldersCount'];
                            $foldersCountHTML= '';
                            if ($foldersCount == 1) {
                                $foldersCountHTML = '<span class="badge">1 folder</span>';
                            }
                            elseif ($foldersCount > 1) {
                                $foldersCountHTML = '<span class="badge">'.$foldersCount.' folders</span>';
                            }

                            $listHTML = $listHTML.'<li class="folder"><a href="'.$baseURL.'/products/projects/tmdocs.aspx?prjID='.$project['id'].'#'.$folderDetail['id'].'" target="_blank">'.$folderDetail['title'].' '.$filesCountHTML.$foldersCountHTML.'</a></li>';
                        }

                        ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Files and Folders</h4>
                            </div>
                            <div class="col-md-6">
                                <ul>
                                    <?php echo $listHTML; ?>
                                </ul>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>
</div> <!-- /container -->      
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</body>
</html>     

<?php

function discussLastUpdate($discussion, $baseurlAPI, $token) {    
    if (!$discussion['commentsCount']) {
        $latest = cleanDateString($discussion['updated'],TRUE);
        $latestby = $discussion['createdBy']['displayName'];
        //$latestHTML = $latest.' : '.$latestby;
        return $latest.' : '.$latestby;
    } else {
        $latest = cleanDateString($discussion['updated'],TRUE);
        $latestby = $discussion['createdBy']['displayName'];
        $commentList = curlGetData($baseurlAPI.'/project/message/'.$discussion['id'].'/comment',$token);
        foreach ($commentList['response'] as $comment) {
            $thisUpdate = cleanDateString($comment['updated'],TRUE);
            $tu = new DateTime($thisUpdate);
            $l = new DateTime($latest);
            if ($l < $tu) {
                $latest = $thisUpdate;
                $latestby = $comment['createdBy']['displayName'];
            } 
        }
        return $latest.' : '.$latestby;
        
    }
}
    
function deadline($messyDateString) {
    $event = cleanDateString($messyDateString,FALSE);
    $date = new DateTime($event);
    $now = new DateTime();

    if ($date < $now) {
        return  $event.' <span class="label label-danger">past due</span>'; }
    else { 
        return $event; }
}
    
function buildSubtaskRow($subtask, $projectid, $taskid, $hasMilestone, $baseURL){
    $thisRow = '';
    $thisRow .= '<tr class="subtask">';
    if ($hasMilestone) { $thisRow .= '<td> </td>';}
    $thisRow .= '<td> </td>';
    $thisRow .= '<td><span class="label '.($subtask['status'] == 2 ? 'label-success">closed subtask' : 'label-warning">open subtask' ).'</span></td>';
    $thisRow .= '<td><a href="'.$baseURL.'/products/projects/tasks.aspx?prjID='.$projectid.'&id='.$taskid.'" target="_blank">'.$subtask['title'].'</a></td>';
    $thisRow .= '<td> </td>';
    $thisRow .= '<td> </td>';
    $thisRow .= '<td> </td>';
    $thisRow .= '</tr>';      
    
    return $thisRow;
}
            
function makeListString($alist, $dataField) {
    $tempArray = array();
    foreach($alist as $listElement)
    {
        $tempArray[] = $listElement[$dataField];
    }
    
    return implode(', ',$tempArray);
}
            
function curlGetData($url,$token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization:$token", 'Accept: application/json,application/xml', 'Accept-Encoding: gzip, deflate'));
    $json = curl_exec($ch);
    curl_close($ch);
    return json_decode($json, true);    
}

function cleanDateString($messyDateString, $includeTime) {
    $pd = date_parse($messyDateString);        
    if ($includeTime) {
        $newDateString = date('Y-m-d H:i:s', mktime($pd['hour'], $pd['minute'], $pd['second'], $pd['month'], $pd['day'], $pd['year']));    
    }
    else {
        $newDateString = date('Y-m-d', mktime($pd['hour'], $pd['minute'], $pd['second'], $pd['month'], $pd['day'], $pd['year']));    
    }
    
    return $newDateString;    
}

?>

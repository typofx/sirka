<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks</title>
    <link rel="stylesheet" href="https://www.typofx.ie/.scr/dataTables.min.css">
    <link rel="stylesheet" href="https://www.typofx.ie/.scr/all.min.css">
    <link rel="stylesheet" href="tasks.styles.css">
    <script src="https://www.typofx.ie/.scr/jquery.min.js"></script>
    <script src="https://www.typofx.ie/.scr/jquery.dataTables.min.js"></script>
    <script src="tasks.js"></script>
</head>

<body>

    <a href="<?php echo defined('BASE_URL') ? BASE_URL . 'auth/menu' : '../panel.menu'; ?>">[Control Panel]</a>
    <a href="javascript:window.location.reload(true)">[Refresh]</a>
    <a href="tasks.form">[Add New Record]</a>

    <h1>Tasks</h1>
    <table id="traineeTasksTable" class="display" style="width:100%" data-can-edit="true" data-folder="tasks">
        <thead>
            <tr>
                <th>#</th>
                <th>Task</th>
                <th>Deployed</th>
                <th>Assignment</th>
                <th>Status</th>
                <th>Person</th>
                <th>Type</th>
                <th>Data Links</th>
                <th>Languages</th>
                <th>Updated</th>
                <th>HRS</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>1001</td>
                <td>01/05/26</td>
                <td>Example task</td>
                <td>done</td>
                <td>Paulo Alves</td>
                <td>script</td>
                <td>
                    <div class="links-wrapper">
                        <a href="https://example.com" target="_blank" title="Open Link"><i class="fa-solid fa-external-link-alt"></i></a>
                        <a href="https://github.com/example" target="_blank" title="Open Github"><i class="fa-brands fa-github"></i></a>
                        <i class="fa-brands fa-youtube lang-inactive"></i>
                    </div>
                </td>
                <td>
                    <div class="lang-icons-wrapper">
                        <i class="fa-brands fa-php lang-active lang-icon-php" title="PHP"></i>
                        <i class="fa-solid fa-database lang-active lang-icon-sql" title="SQL"></i>
                    </div>
                </td>
                <td>10/05/26</td>
                <td>3</td>
                <td>
                    <i class="fa-solid fa-plus expand-btn" data-task-code="1001" title="Expand/Collapse Activities"></i>
                    <form action="tasks.form" method="POST" class="action-form">
                        <input type="hidden" name="id" value="1001">
                        <button type="submit" title="Edit Task"><i class="fa-solid fa-pen-to-square"></i></button>
                    </form>
                    <form action="tasks.delete" method="POST" class="action-form" onsubmit="return confirm('Are you sure?')">
                        <input type="hidden" name="id" value="1001">
                        <input type="hidden" name="token" value="TOKEN">
                        <button type="submit" title="Delete Task" style="color: red;"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>

    <center>v 0.1.0</center>
</body>

</html>

        $(document).ready(function () {
            $('#assetsTable').dataTable({
                "lengthMenu": [[25, 50, 75, -1], [25, 50, 75, "All"]],
                "pageLength": 50
            });
        });

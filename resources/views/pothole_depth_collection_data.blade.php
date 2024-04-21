<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASIL PENGAMBILAN DATA KEDALAMAN</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="text-center">HASIL PENGAMBILAN DATA KEDALAMAN</h1>
                <table id="example" class="table table-striped table-bordered text-center" style="width:100%">
                    <thead>
                        <tr>
                            <th>DEPTH 1</th>
                            <th>DEPTH 2</th>
                            <th>DEPTH 3</th>
                            <th>DEPTH 4</th>
                            <th>LATITUDE</th>
                            <th>LONGITUDE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            <tr>
                                <td>{{ $item->pothole_depth_1 }} cm</td>
                                <td>{{ $item->pothole_depth_2 }} cm</td>
                                <td>{{ $item->pothole_depth_3 }} cm</td>
                                <td>{{ $item->pothole_depth_4 }} cm</td>
                                <td>{{ $item->pothole_depth_latitude }}</td>
                                <td>{{ $item->pothole_depth_longitude }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#example').DataTable({
                ordering: false
            });
        });
    </script>

</body>

</html>

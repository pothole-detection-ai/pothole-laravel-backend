<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HASIL PENGAMBILAN DATA KEDALAMAN</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .custom-switch-input {
            width: 180px;
            /* Set desired width for the switch */
            height: 40px;
            /* Set desired height for the switch */
            font-size: 36px;
            /* Set font size for the switch label */
            /* Customize other properties as needed */
        }

        .custom-switch-label {
            font-size: 36px;
            /* Match font size with input for better alignment */
            /* Add more styles as needed */
        }

        /* Define a CSS class for red background */
        @keyframes changeBackground {
            0% {
                background-color: red;
            }

            100% {
                background-color: inherit;
                /* Change back to default */
            }
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-center"> <!-- Center content horizontally -->
                <div class="form-check form-switch">
                    <input class="form-check-input custom-switch-input saklar" type="checkbox" role="switch"
                        id="flexSwitchCheckDefault" @if ($saklar->saklar_status == 1) checked @endif>
                    <label class="form-check-label custom-switch-label saklar-label" for="flexSwitchCheckDefault">
                        @if ($saklar->saklar_status == 1)
                            ON
                        @else
                            OFF
                        @endif
                    </label>
                </div>
            </div>
            <div class="col-lg-12 col-sm-12 text-center">
                <div id="location-info">
                    Latitude: <span id="latitude">{{ $location->latitude }}</span><br>
                    Longitude: <span id="longitude">{{ $location->longitude }}</span><br>
                    Time Last: <span id="time-last">{{ $location->updated_at }}</span><br>
                    Time Now: <span id="time-now"></span><br>
                </div>
            </div>
            <div class="col-lg-12">
                <h1 class="text-center">HASIL PENGAMBILAN DATA KEDALAMAN</h1>
                <table id="example" class="table table-striped table-bordered text-center" style="width:100%">
                    <thead>
                        <tr>
                            <th>Depth 1</th>
                            <th>Depth 2</th>
                            <th>Depth 3</th>
                            <th>Depth 4</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Created At</th>
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
                                <td>{{ $item->created_at }}</td>
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
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#example').DataTable({
                ordering: false,
                "responsive": true
            });

            $('.saklar').on('click', function() {
                if ($(this).is(':checked')) {
                    $('.saklar-label').text('ON');
                } else {
                    $('.saklar-label').text('OFF');
                }
            });

            var lat, long;

            function showPosition(position) {
                // Retrieve latitude and longitude
                lat = position.coords.latitude.toFixed(10);
                long = position.coords.longitude.toFixed(10);

                // Update latitude and longitude elements
                document.getElementById('latitude').innerText = lat;
                document.getElementById('longitude').innerText = long;

                // Get the element to animate (e.g., body or a specific element)
                const elementToAnimate = document.getElementById(
                    'latitude'); // Change this to target a specific element if needed
                const elementToAnimate2 = document.getElementById(
                    'longitude'); // Change this to target a specific element if needed

                // Apply the animation class
                elementToAnimate.style.animation = 'changeBackground 0.5s';
                elementToAnimate2.style.animation = 'changeBackground 0.5s';

                // Remove the animation class after 500ms (0.5 seconds)
                setTimeout(() => {
                    elementToAnimate.style.animation = '';
                    elementToAnimate2.style.animation = '';
                }, 500);
            }

            // FUNCTION TO HIT API SAKLAR TO TURN ON/OFF BY CLICKING THE SWITCH AND GET THE VALUE
            $('.saklar').on('click', function() {
                var saklar = $(this).is(':checked') ? 1 : 0;

                navigator.geolocation.getCurrentPosition(showPosition);

                // Send AJAX request with CSRF token included
                setTimeout(function() {
                    $.ajax({
                        url: '/api/saklar',
                        type: 'POST',
                        data: {
                            saklar: saklar,
                            latitude: lat,
                            longitude: long
                        },
                        success: function(response) {
                            console.log(response);
                            // Update switch label based on response
                            $('.saklar-label').text(response.label);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error toggling switch:', error);
                        }
                    });
                }, 500);
            });

            // realtime time-now => Y-m-d H:i:s
            setInterval(function() {
                var now = new Date();
                var year = now.getFullYear();
                var month = now.getMonth() + 1; // Get month (1-12)
                var day = now.getDate();
                var hours = now.getHours();
                var minutes = now.getMinutes();
                var seconds = now.getSeconds();

                // Format month to remove leading zero for 1-9
                var formattedMonth = (month < 10) ? '0' + month : month;

                // Create date and time strings
                var date = year + '-' + formattedMonth + '-' + day;
                var time = hours + ':' + minutes + ':' + seconds;
                var dateTime = date + ' ' + time;

                // Update the text content
                $('#time-now').text(dateTime);
            }, 1000);




        });
    </script>

</body>

</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="download.png">
    <meta name="author" content="Ebrahim Shafiei (EbraSha)">
    <meta content="Prof.Shafiei@Gmail.com" name="Email" />
    <title>Abdal PHP Remote Downloader ver 5.2</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        .progress {
            height: 30px;
        }
        .progress-bar {
            font-size: 1.1em;
        }
        .form-inline-custom {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .form-inline-custom .form-group {
            flex: 1;
            margin-right: 10px;
        }
        .form-inline-custom button {
            margin-left: 10px;
        }
        .container {
            max-width: 800px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-12 text-center">
            <img class="mb-2" src="download.png" height="75" width="75" alt="Downloader">
            <h1 title="version 5.2">Abdal PHP Remote Downloader</h1>
            <form id="downloadForm" class="form-inline-custom">
                <div class="form-group mb-2">
                    <label for="fileUrl" class="sr-only">File URL</label>
                    <input type="url" class="form-control" id="fileUrl" name="fileUrl" placeholder="Enter file URL" required>
                </div>
                <button type="submit" class="btn btn-primary mb-2">Download</button>
            </form>
            <div class="progress mt-3">
                <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <div id="progress-text" class="mt-3 badge bg-warning  text-white"></div>

            <div class="footer mt-2">
                <div>Programmer: Ebrahim Shafiei (EbraSha)</div>
                <div>Email : Prof.Shafiei@Gmail.com</div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script>
    $(document).ready(function(){
        $('#downloadForm').on('submit', function(e){
            e.preventDefault();
            var fileUrl = $('#fileUrl').val();
            $('#progress-bar').css('width', '0%').attr('aria-valuenow', 0).text('0%');
            $('#progress-text').text('');
            downloadFile(fileUrl, 0, 0); // Initialize with start = 0 and totalSize = 0
        });

        function downloadFile(url, start, totalSize) {
            $.ajax({
                url: 'download.php',
                type: 'POST',
                data: {fileUrl: url, start: start, totalSize: totalSize},
                dataType: 'json',
                success: function(response) {
                    console.log(response); // Log the response for debugging
                    if (response.error) {
                        $('#progress-text').html('Error: ' + response.error);
                    } else {
                        totalSize = response.totalSize || totalSize;
                        var downloaded = response.downloaded;
                        var progress = (downloaded / totalSize) * 100;
                        $('#progress-bar').css('width', progress + '%').attr('aria-valuenow', progress).text(progress.toFixed(2) + '%');

                        if (response.continue) {
                            $('#progress-text').html('Downloaded ' + formatBytes(downloaded) + ' of ' + formatBytes(totalSize) + ' so far...');
                            downloadFile(url, downloaded, totalSize);
                        } else {
                            $('#progress-text').html('Download complete: <a href="' + response.file + '">Download File</a>');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    $('#progress-text').html('Error: ' + error);
                    console.error(xhr.responseText); // Log the response text for debugging
                }
            });
        }

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    });
</script>
</body>
</html>

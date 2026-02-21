<?php
/**
 * The json file has this format:
 * {
 *   "book": "book name",
 *   "chapter": "chapter name",
 *   "position": "position in the chapter"
 * }
 */

try {
    // if($_POST) {
    //     file_put_contents(dirname(__DIR__,1) . '/logs/debug.txt', print_r($_POST,true), FILE_APPEND);
    // }
    if($_POST['note']) {
        $note = $_POST['note'];
        // Prepend chapter and position to the note
        $chapterInfo = "Chapter: " . $_POST['chapter'] . ", Position: " . $_POST['position'] . "\n";
        $noteWithHeader = $chapterInfo . $note . "\n";
        $noteFilePath = __DIR__ . '/books/' . rawurlencode($_POST['book']) . '/' . rawurlencode($_POST['chapter']) . '.txt';
        file_put_contents($noteFilePath, $noteWithHeader, FILE_APPEND);
    }

    $progressFile = __DIR__ . '/books/progress.json';

    if($_POST['position_update']) {
        $jsonContent = file_get_contents($progressFile);
        $progressData = json_decode($jsonContent, true);
        $progressData['position'] = $_POST['position_update'];
        file_put_contents($progressFile, json_encode($progressData));
        exit();
    }


    if($_POST['new_chapter']) {
        $jsonContent = file_get_contents($progressFile);
        $progressData = json_decode($jsonContent, true);
        $progressData['chapter'] = $_POST['new_chapter'];
        file_put_contents($progressFile, json_encode($progressData));
    }

    if (!file_exists($progressFile)) {
        throw new Exception('Progress file not found');
    }
    $jsonContent = file_get_contents($progressFile);
    $progressData = json_decode($jsonContent, true);

    if ($progressData !== null && isset($progressData['book'], $progressData['chapter'], $progressData['position'])) {
        $bookName = $progressData['book'];
        $chapter = $progressData['chapter'];
        $chapter_file = 'chapter_' . sprintf('%02d', $chapter) . '_complete.wav';//'chapter_06_complete.wav'
        $position = $progressData['position'];
        
    } else {
        throw new Exception('Invalid or incomplete progress data');
    }
    $audioBrowserPath = "/books/" . rawurlencode($bookName) . "/" . rawurlencode($chapter_file);
    $noteFilePath = __DIR__ . '/books/' . rawurlencode($bookName) . '/' . rawurlencode($chapter) . '.txt';
    $noteContent = '';
    if (file_exists($noteFilePath)) {
        $noteContent = file_get_contents($noteFilePath);
    }

    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audio Notes - ' . htmlspecialchars($bookName) . ' | CH ' . htmlspecialchars($chapter) . '</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between">
                <h4 class="mb-0">' . htmlspecialchars($bookName) . ' - CH ' . htmlspecialchars($chapter) . '</h4>
                <form method="post" action="" class="d-flex align-items-center ms-3">
                    <label for="chapterSelect" class="me-2 fw-bold mb-0">Chapter:</label>
                    <select id="chapterSelect" name="new_chapter" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                        ' . 
                        (function() use ($bookName, $chapter) {
                            $bookDir = __DIR__ . '/books/' . rawurlencode($bookName);
                            $options = '';
                            if (is_dir($bookDir)) {
                                $files = scandir($bookDir);
                                foreach ($files as $file) {
                                    // filenames are like chapter_06_complete.wav
                                    if (preg_match('/^chapter_(\d+)_complete\.wav$/', $file, $matches)) {
                                        $ch = $matches[1];
                                        $selected = ($ch == $chapter) ? 'selected' : '';
                                        $options .= '<option value="' . htmlspecialchars($ch) . '" ' . $selected . '>CH ' . htmlspecialchars($ch) . '</option>';
                                    }
                                }
                            }
                            return $options;
                        })()
                        .'
                    </select>
                    <input type="hidden" name="book" value="' . htmlspecialchars($bookName) . '">
                </form>
            </div>
        </div>
        <div class="card-body text-center">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem;">
                <button type="button" class="btn btn-outline-secondary" id="skipBackBtn" title="Skip back 10 seconds" aria-label="Skip back 10 seconds">
                    &#x23EA; 10s
                </button>
                <audio id="audioPlayer" controls autoplay style="width:100%;" data-position="' . htmlspecialchars($position) . '">
                    <source src="' . htmlspecialchars($audioBrowserPath) . '" type="audio/wav">
                    Your browser does not support the audio element.
                </audio>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        var audio = document.getElementById("audioPlayer");
                        var position = audio.getAttribute("data-position");
                        if (position) {
                            var seconds;
                            var parts = position.split(":");
                            if (parts.length === 3) {
                                // "HH:MM:SS" format
                                seconds = (+parts[0]) * 3600 + (+parts[1]) * 60 + (+parts[2]);
                            } else {
                                // Decimal seconds (e.g. from progress.json)
                                seconds = parseFloat(position);
                            }
                            if (!isNaN(seconds) && seconds >= 0) {
                                audio.addEventListener("loadedmetadata", function() {
                                    if (!isNaN(audio.duration) && seconds > audio.duration) {
                                        audio.currentTime = 0;
                                    } else {
                                        audio.currentTime = seconds;
                                    }
                                });
                            }
                        }
                    });
                </script>
                <button type="button" class="btn btn-outline-secondary" id="skipAheadBtn" title="Skip ahead 10 seconds" aria-label="Skip ahead 10 seconds">
                    10s &#x23E9;
                </button>
            </div>
            <script>
                function updatePosition() {
                    var audio = document.getElementById("audioPlayer");
                    if (!audio) return;
                    var position = audio.currentTime.toFixed(3);

                    // Send the current position as \'position_update\' via AJAX to the server-side script
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "AudioNotes.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.send(
                        "position_update=" + encodeURIComponent(position)
                    );
                }
                document.addEventListener("DOMContentLoaded", function() {
                    var audio = document.getElementById("audioPlayer");
                    updatePosition();
                    const skipBackBtn = document.getElementById("skipBackBtn");
                    const skipAheadBtn = document.getElementById("skipAheadBtn");
                    
                    audio.play().catch(function() {});
                    function startOnFirstClick() {
                        audio.play().catch(function() {});
                        document.removeEventListener("click", startOnFirstClick);
                    }
                    document.addEventListener("click", startOnFirstClick);
                    
                    skipBackBtn.addEventListener("click", function() {
                        if (audio.currentTime > 10) {
                            audio.currentTime -= 10;
                            updatePosition();
                        } else {
                            audio.currentTime = 0;
                            updatePosition();
                        }
                    });

                    skipAheadBtn.addEventListener("click", function() {
                        if (audio.duration) {
                            if (audio.currentTime + 10 < audio.duration) {
                                audio.currentTime += 10;
                                updatePosition();
                            } else {
                                audio.currentTime = audio.duration;
                                updatePosition();
                            }
                        } else {
                            audio.currentTime += 10;
                            updatePosition();
                        }
                    });

                    audio.addEventListener("ended", function() {
                        var chapterSelect = document.getElementById("chapterSelect");
                        var options = chapterSelect.options;
                        var currentIndex = chapterSelect.selectedIndex;
                        if (currentIndex >= 0 && currentIndex < options.length - 1) {
                            chapterSelect.selectedIndex = currentIndex + 1;
                            chapterSelect.form.submit();
                            updatePosition();
                        }
                    });
                });
            setInterval(function() {
                updatePosition();
            }, 10000);
            </script>
            <p class="mt-3 mb-0"><strong>Position:</strong> ' . htmlspecialchars($position) . '</p>
        </div>
    </div>
</div>


<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="noteTextarea" class="form-label"><strong>Add a Note</strong></label>
                            <textarea class="form-control" id="noteTextarea" name="note" rows="4" placeholder="Type your note here..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <input type="hidden" name="chapter" value="' . htmlspecialchars($chapter) . '">
                        <input type="hidden" name="book" value="' . htmlspecialchars($bookName) . '">
                        <input type="hidden" name="position" id="positionInput" value="' . htmlspecialchars($position) . '">
                        <script>
                            // Ensure this script runs after the audio element and form are loaded
                            document.addEventListener("DOMContentLoaded", function() {
                                var audio = document.getElementById("audioPlayer");
                                var form = document.querySelector(\'form[action=""]\');
                                var positionInput = document.getElementById("positionInput");

                                if (form && audio && positionInput) {
                                    form.addEventListener("submit", function() {
                                        positionInput.value = audio.currentTime.toFixed(3);
                                    });
                                }
                            });
                        </script>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container mt-3">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="alert alert-secondary" role="alert" style="white-space: pre-wrap;">
                <strong>Current Notes:</strong><br>' . htmlspecialchars($noteContent) . '
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS bundle (via CDN) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
';
    echo $html;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
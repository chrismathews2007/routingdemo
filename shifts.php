<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Table</title>
    <style>
    .shifttable select {
        min-height: 445px;
        padding-top: 10px;
        padding-bottom: 10px;
        overflow: hidden;
        background-color: #f0f0f0;
        border: 1px solid #ccc;
        min-width: 80px;
    }

    .col-md-12 table {
        width: 100%;
        table-layout: fixed;
    }

    .hoursinput {
        border: 0;
        max-width: 50px;
        background: transparent;
        text-align: center;
    }

    option {
        text-align: center;
        padding: 5px 0
    }

    select:focus {
        border-color: #007bff;
    }

    select:-internal-list-box option:checked {
        background-color: red !important
    }

    option:selected {
        background-color: #007bff;
        color: #fff;
    }

    select[multiple] option:checked {
        background-color: #009879;
        color: #fff;
    }
    </style>
</head>

<body> <?php

    $json_data = '{
        "data":{
            "shifts":[
                
               ]
        }
    }';

    $data = json_decode($json_data, true);

    if (empty($data['data']['shifts'])) {
        // Use the alternative array
        $json_data = '{
            "data":{
                "shifts":[
                    {"dow":"MON","availability":{"start_time":"00:00","duration":"0"}},
                    {"dow":"TUE","availability":{"start_time":"00:00","duration":"0"}},
                    {"dow":"WED","availability":{"start_time":"00:00","duration":"0"}},
                    {"dow":"THU","availability":{"start_time":"00:00","duration":"0"}},
                    {"dow":"FRI","availability":{"start_time":"00:00","duration":"0"}},
                    {"dow":"SAT","availability":{"start_time":"00:00","duration":"0"}},
                    {"dow":"SUN","availability":{"start_time":"00:00","duration":"0"}}
                ]
            }
        }';
        
        // Update $data with the alternative array
        $data = json_decode($json_data, true);
    }

    // Initialize variables to store selected hours
    $selectedHours = [];
    $totalHours = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selectedHours = $_POST['availability'] ?? [];

        // Calculate total hours for each day
        foreach ($selectedHours as $dow => $hours) {
            $totalHours[$dow] = calculateTotalHours($hours);
        }
    }

    function calculateTotalHours($selectedOptions)
    {
        return count($selectedOptions);
    }

    function constructPayload($selectedHours)
{
    $payload = [
        "tech_obj_guid" => "fc9e77f7-7d65-2866-840a-a1963ed0fdae",
        "shifts" => [],
        "action" => "Crew::modify_doli_tech_shift",
        "SID" => "81c2980a74f4871263c330befebd458f"
    ];

    $daysOfWeek = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];

    foreach ($daysOfWeek as $dow) {
        // Check if the day is in the selected hours array
        if (isset($selectedHours[$dow])) {
            $hours = $selectedHours[$dow];
            $startTime = reset($hours);
            $payload["shifts"][] = [
                "dow" => $dow,
                "availability" => [
                    "start_time" => $startTime ?? "",
                    "duration" => count($hours)
                ]
            ];
        } else {
            // If the day is not selected, add an entry with empty availability
            $payload["shifts"][] = [
                "dow" => $dow,
                "availability" => [
                    "start_time" => "00:00",
                    "duration" => 0
                ]
            ];
        }
    }

    return $payload;
}

    if (isset($_POST['update'])) {
        $payload = constructPayload($selectedHours);
        echo '<script>';
        echo 'console.log(' . json_encode($payload) . ');';
        echo '</script>';
    }

    ?> <div class="col-md-12 my-5">
        <form method="post" action="">
            <div class="d-flex justify-content-between">
                <h3>Technician Recurring Weekly Shift</h3>
                <div class="ml-3">
                    <button class="btn btn-primary" type="button" name="edit" onclick="toggleButtons()">Edit</button>
                    <button class="btn btn-success" type="button" name="update" onclick="updateShifts()"
                        style="display: none;">Update</button>
                </div>
            </div>
            <table border="0" class="styled-table shifttable">
                <thead>
                    <tr>
                        <th>MON</th>
                        <th>TUE</th>
                        <th>WED</th>
                        <th>THU</th>
                        <th>FRI</th>
                        <th>SAT</th>
                        <th>SUN</th>
                        <th>Total Week hours</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="vertical-align: top;">
                        <?php foreach (['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $day) : ?> <td>
                            <?php if (empty($data['data']['shifts'])) : ?> <select name="availability[<?= $day; ?>][]"
                                multiple onchange="updateTotalHours(this, '<?= $day; ?>')" disabled>
                                <?php for ($i = 6; $i <= 21; $i++) : ?> <?php $time_option = sprintf('%02d:00', $i); ?>
                                <option value="<?= $time_option ?>"><?= $time_option ?></option> <?php endfor; ?>
                            </select> <?php else : ?> <?php foreach ($data['data']['shifts'] as $shift) : ?>
                            <?php if ($shift['dow'] === $day) : ?> <select name="availability[<?= $day; ?>][]" multiple
                                onchange="updateTotalHours(this, '<?= $day; ?>')" disabled> <?php
                                                $start_time = $shift['availability']['start_time'];
                                                $duration = $shift['availability']['duration'];
                                                $end_time = date('H:i', strtotime("$start_time +$duration hours"));

                                                for ($i = 6; $i <= 21; $i++) :
                                                    $time_option = sprintf('%02d:00', $i);
                                                    $selected = ($time_option >= $start_time && $time_option <= $end_time) ? 'selected' : '';
                                                ?> <option value="<?= $time_option ?>" <?= $selected ?>>
                                    <?= $time_option ?></option> <?php endfor; ?> </select> <?php endif; ?>
                            <?php endforeach; ?> <?php endif; ?> </td> <?php endforeach; ?> </tr>
                    <tr> <?php
                        $totalWeeklyHours = 0;
                        foreach ($data['data']['shifts'] as $shift) :
                            $dow = $shift['dow'];
                            $totalWeeklyHours += $totalHours[$dow] ?? 0;
                        ?> <td>
                            <input type="text" name="total_hours[<?= $dow; ?>]" value="<?= $totalHours[$dow] ?? 0 ?>"
                                readonly class="hoursinput" data-dow="<?= $dow; ?>">
                        </td> <?php endforeach; ?> <td>
                            <input type="text" name="total_weekly_hours" value="<?= $totalWeeklyHours ?>" readonly
                                class="hoursinput">
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
    <script>
    const payload = <?= json_encode(constructPayload($selectedHours)); ?>;
    console.log("PayloadLLL: ", payload)

    function updateTotalHoursOnLoad() {
        var selects = document.querySelectorAll('select[multiple]');
        var totalWeeklyHoursInput = document.querySelector('input[name="total_weekly_hours"]');
        selects.forEach(function(select) {
            var dow = select.getAttribute('name').match(/\[(.*?)\]/)[1];
            var totalHoursInput = document.querySelector('input[name="total_hours[' + dow + ']"]');
            if (totalHoursInput) {
                var selectedOptions = select.selectedOptions;
                totalHoursInput.value = selectedOptions.length;
            }
        });
        var totalWeeklyHours = 0;
        selects.forEach(function(select) {
            var selectedOptions = select.selectedOptions;
            totalWeeklyHours += selectedOptions.length;
        });
        if (totalWeeklyHoursInput) {
            totalWeeklyHoursInput.value = totalWeeklyHours;
        }
    }

    function updateTotalHours(selectElement, dow) {
        var totalHoursInput = document.querySelector('input[name="total_hours[' + dow + ']"]');
        if (totalHoursInput) {
            var selectedOptions = selectElement.selectedOptions;
            totalHoursInput.value = selectedOptions.length;
        }
    }

    function toggleButtons() {
        var editButton = document.querySelector('button[name="edit"]');
        var updateButton = document.querySelector('button[name="update"]');
        var selects = document.querySelectorAll('select[multiple]');
        if (editButton && updateButton) {
            editButton.style.display = 'none';
            updateButton.style.display = 'block';
            selects.forEach(function(select) {
                select.removeAttribute('disabled');
            });
        }
    }

    function updateShifts() {
        // Call the PHP function to construct the payload
        <?php
            echo 'var payload = ' . json_encode(constructPayload($selectedHours)) . ';';
            ?>
        console.log("payload UPDATE 265: ", payload);
    }
    // Call the function when the page is loaded
    window.addEventListener('DOMContentLoaded', function() {
        updateTotalHoursOnLoad();
    });
    </script>
</body>

</html>
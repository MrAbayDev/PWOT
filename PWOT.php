<!DOCTYPE html>
<html lang="eng">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWOT Work Of Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">PWOT Work Of Tracker</h2>
    <form method="POST" class="mb-5">
        <div class="mb-3">
            <label for="arrived_at" class="form-label">Kelgan vaqtingiz:</label>
            <input type="datetime-local" id="arrived_at" name="arrived_at" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="leaved_at" class="form-label">Ketgan vaqtingiz:</label>
            <input type="datetime-local" id="leaved_at" name="leaved_at" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
    <?php
    date_default_timezone_set("Asia/Tashkent");
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=daily", "root", '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Bog'lanish xatosi: " . $e->getMessage());
    }
    class Daily
    {
        const WORK_DURATION = 9;
        private $pdo;
        public function __construct(PDO $pdo)
        {
            $this->pdo = $pdo;
        }
        public function calculate(DateTime $arrivedAt, DateTime $leavedAt)
        {
            return $arrivedAt->diff($leavedAt);
        }
        public function calculateRemainingTime(DateInterval $working_hours, $working_duration = self::WORK_DURATION)
        {
            $standardSeconds = $working_duration * 3600;
            $workedSeconds = ($working_hours->h * 3600) + ($working_hours->i * 60) + $working_hours->s;
            $remainingSeconds = $standardSeconds - $workedSeconds;
            return $remainingSeconds;
        }
        public function getWorkTable()
        {
            $query = $this->pdo->query("SELECT * FROM daily");
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['arrived_at']) && !empty($_POST['leaved_at'])) {
            $arrivedAt = new DateTime($_POST['arrived_at']);
            $leavedAt = new DateTime($_POST['leaved_at']);
            $daily = new Daily($pdo);
            $duration = $daily->calculate($arrivedAt, $leavedAt);
            $workedSeconds = ($duration->h * 3600) + ($duration->i * 60) + $duration->s;
            $remainingSeconds = $daily->calculateRemainingTime($duration);
            $requiredWorkOff = $remainingSeconds > 0 ? gmdate("H:i:s", $remainingSeconds) : '00:00:00';
            echo "<div class='alert alert-info'>Ishladingiz: " . $duration->format('%h soat %i minut') . "<br>";
            echo "Qarzdorlik: " . gmdate("H:i:s", $remainingSeconds) . "</div>";

            try {
                $stmt = $pdo->prepare("INSERT INTO daily (arrived_at, leaved_at, required_work_off) VALUES (:arrived_at, :leaved_at, :required_work_off)");
                $stmt->bindParam(':arrived_at', $_POST['arrived_at']);
                $stmt->bindParam(':leaved_at', $_POST['leaved_at']);
                $stmt->bindParam(':required_work_off', $requiredWorkOff);
                $stmt->execute();
            } catch (PDOException $e) {
                echo "<div class='alert alert-danger'>XATO: " . $e->getMessage() . "</div>";
            }
        }
    }
    $daily = new Daily($pdo);
    $workData = $daily->getWorkTable();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['toggle_worked_off']) && isset($_POST['work_id'])) {
            $workId = $_POST['work_id'];
            $workOff = $_POST['toggle_worked_off'] ? 1 : 0;
            try {
                $stmt = $pdo->prepare("UPDATE daily SET worked_off = :work_off WHERE id = :work_id");
                $stmt->bindParam(':work_off', $workOff, PDO::PARAM_INT);
                $stmt->bindParam(':work_id', $workId, PDO::PARAM_INT);
                $stmt->execute();
            } catch (PDOException $e) {
                echo "<div class='alert alert-danger'>XATO: " . $e->getMessage() . "</div>";
            }
        }
    }
    $totalWorkOffSeconds = 0;
    if (!empty($workData)) {
        echo "<h3>Ro'yxat:</h3>";
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>ID</th><th>Arrived At</th><th>Leaved At</th><th>Rent Time</th><th>worked_off</th></tr></thead><tbody>";
        foreach ($workData as $key => $row) {
            $rowClass = $row['work_off'] ? 'table-success' : '';
            echo "<tr class='$rowClass'>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['arrived_at']) . "</td>";
            echo "<td>" . htmlspecialchars($row['leaved_at']) . "</td>";
            echo "<td>" . htmlspecialchars($row['required_work_off']) . "</td>";
            echo "<td>";
            echo "<form method='POST'>";
            echo "<input type='checkbox' name='toggle_worked_off[$key]' value='1' " . ($row['work_off'] ? 'checked' : '') . ">";
            echo "<input type='hidden' name='work_id' value='" . htmlspecialchars($row['id']) . "'>";
            echo "<input type='submit' value='Save'>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
            $workOffTimeParts = explode(':', $row['required_work_off']);
            $totalWorkOffSeconds += ($workOffTimeParts[0] * 3600) + ($workOffTimeParts[1] * 60) + $workOffTimeParts[2];
            if (isset($_POST['toggle_worked_off'][$key])) {
                $selectedWorkOffTimeParts = explode(':', $row['required_work_off']);
                $totalWorkOffSeconds -= ($selectedWorkOffTimeParts[0] * 3600) + ($selectedWorkOffTimeParts[1] * 60) + $selectedWorkOffTimeParts[2];
        }
        }
        echo "</tbody></table>";

        $totalWorkOffFormatted = gmdate("H:i:s", $totalWorkOffSeconds);
        echo "<div class='alert alert-info'>Umumiy qarzdorlik vaqti: $totalWorkOffFormatted</div>";
    }
    ?>
    <script>
        function toggleWorkOff(workId) {
            var form = document.getElementById('form_' + workId);
            form.submit();
        }
    </script>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once 'db_connect.php';

$isSuccess = false;
$headline = "Access Denied";
$message = "Invalid request payload.";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $name = trim($_POST['client_name']);
    $address = trim($_POST['client_address']);
    $phone = trim($_POST['client_phone']);
    $license = trim($_POST['car_license']);
    $engine = trim($_POST['car_engine']);
    $date = trim($_POST['appointment_date']);
    $time_slot = trim($_POST['time_slot']);
    $mechanic_id = (int)$_POST['mechanic_id'];

    if (empty($name) || empty($address) || empty($phone) || empty($license) || empty($engine) || empty($date) || empty($time_slot) || empty($mechanic_id)) {
        $message = "System error: All parameters must be provided to secure an appointment.";
    } else {
        try {
            $stmtClient = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE car_license_number = ? AND appointment_date = ?");
            $stmtClient->execute([$license, $date]);
            
            if ($stmtClient->fetchColumn() > 0) {
                $headline = "Schedule Conflict";
                $message = "This vehicle is already scheduled for diagnostics on the selected date.";
            } else {
                $stmtMechanic = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE mechanic_id = ? AND appointment_date = ?");
                $stmtMechanic->execute([$mechanic_id, $date]);
                
                if ($stmtMechanic->fetchColumn() >= 4) {
                    $headline = "Capacity Reached";
                    $message = "This mechanic has reached maximum capacity (4 active operations) for this date.";
                } else {
                    $insertStmt = $pdo->prepare("INSERT INTO appointments (client_name, client_address, client_phone, car_license_number, car_engine_number, appointment_date, time_slot, mechanic_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insertStmt->execute([$name, $address, $phone, $license, $engine, $date, $time_slot, $mechanic_id]);
                    
                    $isSuccess = true;
                    $headline = "Operation Confirmed";
                    $message = "Your vehicle has been logged for $time_slot diagnostics. The assigned mechanic is reserved.";
                }
            }
        } catch (PDOException $e) {
            $headline = "Database Fault";
            $message = "System exception during execution: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status | Workshop</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; background-color: #0c0e12; color: #e2e8f0; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .status-card { background: #161a22; padding: 40px; border-radius: 8px; box-shadow: 0 15px 35px rgba(0,0,0,0.8); max-width: 420px; width: 100%; text-align: center; border-top: 4px solid <?php echo $isSuccess ? '#10b981' : '#ef4444'; ?>; }
        h1 { margin-top: 0; font-size: 26px; text-transform: uppercase; color: <?php echo $isSuccess ? '#10b981' : '#ef4444'; ?>; }
        p { color: #94a3b8; line-height: 1.6; margin-bottom: 35px; font-size: 15px; }
        .btn { display: inline-block; color: #e2e8f0; text-decoration: none; padding: 12px 28px; border: 1px solid #334155; border-radius: 4px; transition: all 0.3s ease; }
        .btn:hover { background-color: #1e293b; border-color: #475569; }
    </style>
</head>
<body>
    <div class="status-card">
        <h1><?php echo htmlspecialchars($headline); ?></h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="index.php" class="btn">Return to Terminal</a>
    </div>
</body>
</html>
<?php
if (isset($_GET['action']) && $_GET['action'] == 'get_slots' && isset($_GET['date'])) {
    require_once 'db_connect.php';
    $date = trim($_GET['date']);
    $mechanics = [];
    
    try {
        $stmt = $pdo->query("SELECT id, name FROM mechanics");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE mechanic_id = ? AND appointment_date = ?");
            $countStmt->execute([$row['id'], $date]);
            $booked = $countStmt->fetchColumn();
            
            $free = max(0, 4 - $booked); 
            
            $mechanics[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'free_slots' => $free
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($mechanics);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RF - Autos | Appointment</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(rgba(12, 14, 18, 0.85), rgba(12, 14, 18, 0.85)), 
                        url('bg-garage.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #e2e8f0;
            display: flex;
            justify-content: center;
            padding: 50px 20px;
            margin: 0;
            min-height: 100vh;
            align-items: center;
        }
        
        .form-container {
            background-color: rgba(22, 26, 34, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.8);
            width: 100%;
            max-width: 550px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .brand-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .brand-header img {
            max-width: 120px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #f8fafc;
        }
        
        .tagline {
            color: #94a3b8;
            font-size: 14px;
            font-style: italic;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            color: #cbd5e1;
        }
        
        input[type="text"], input[type="date"], select, textarea {
            width: 100%;
            padding: 12px;
            background-color: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
            border-radius: 4px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #10b981;
        }
        
        .error-message {
            color: #ef4444;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
            cursor: pointer;
            margin-top: 15px;
            transition: opacity 0.3s ease, transform 0.1s;
        }
        
        button:hover {
            opacity: 0.9;
        }
        
        button:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="brand-header">
        <img src="rf-logo.jpg" alt="RF Autos Logo">
        <h2>RF - Autos</h2>
        <div class="tagline">Where your vehicles rests.</div>
    </div>
    
    <form id="appointmentForm" action="process_appointment.php" method="POST">
        
        <div class="form-group">
            <label for="client_name">Full Name</label>
            <input type="text" id="client_name" name="client_name">
        </div>

        <div class="form-group">
            <label for="client_address">Address</label>
            <textarea id="client_address" name="client_address" rows="2"></textarea>
        </div>

        <div class="form-group">
            <label for="client_phone">Phone Number (Numbers only)</label>
            <input type="text" id="client_phone" name="client_phone">
        </div>

        <div class="form-group">
            <label for="car_license">Car License Number</label>
            <input type="text" id="car_license" name="car_license">
        </div>

        <div class="form-group">
            <label for="car_engine">Car Engine Number (Numbers only)</label>
            <input type="text" id="car_engine" name="car_engine">
        </div>

        <div class="form-group">
            <label for="appointment_date">Appointment Date</label>
            <input type="date" id="appointment_date" name="appointment_date">
        </div>
        <div class="form-group">
            <label for="time_slot">Preferred Time</label>
            <select id="time_slot" name="time_slot">
                <option value="Morning">Morning (8:00 AM - 12:00 PM)</option>
                <option value="Evening">Evening (1:00 PM - 5:00 PM)</option>
                <option value="Night">Night (6:00 PM - 10:00 PM)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="mechanic_id">Select Mechanic</label>
            <select id="mechanic_id" name="mechanic_id">
                <option value="">-- Select a Date First --</option>
            </select>
        </div>

        <div id="form_error" class="error-message"></div>

        <button type="submit">Submit Appointment</button>
    </form>
</div>

<script>
    const dateInput = document.getElementById('appointment_date');
    const mechanicSelect = document.getElementById('mechanic_id');

    dateInput.addEventListener('change', function() {
        const selectedDate = this.value;
        
        if (!selectedDate) {
            mechanicSelect.innerHTML = '<option value="">-- Select a Date First --</option>';
            return;
        }

        mechanicSelect.innerHTML = '<option value="">Loading mechanics...</option>';

        fetch(`index.php?action=get_slots&date=${selectedDate}`)
            .then(response => response.json())
            .then(data => {
                mechanicSelect.innerHTML = '<option value="">-- Choose a Mechanic --</option>';
                
                data.forEach(mechanic => {
                    const option = document.createElement('option');
                    option.value = mechanic.id;
                    
                    if (mechanic.free_slots > 0) {
                        option.textContent = `${mechanic.name} (${mechanic.free_slots} slots free)`;
                    } else {
                        option.textContent = `${mechanic.name} (Full - 0 slots free)`;
                        option.disabled = true;
                    }
                    
                    mechanicSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error fetching mechanics:', error);
                mechanicSelect.innerHTML = '<option value="">Error loading mechanics</option>';
            });
    });

    document.getElementById('appointmentForm').addEventListener('submit', function(event) {
        const name = document.getElementById('client_name').value.trim();
        const address = document.getElementById('client_address').value.trim();
        const phone = document.getElementById('client_phone').value.trim();
        const license = document.getElementById('car_license').value.trim();
        const engine = document.getElementById('car_engine').value.trim();
        const date = document.getElementById('appointment_date').value.trim();
        
        const mechanic = document.getElementById('mechanic_id').value;
        const errorDiv = document.getElementById('form_error');
        
        errorDiv.style.display = 'none';
        errorDiv.innerText = '';

        if (!name || !address || !phone || !license || !engine || !date || !timeSlot || !mechanic) {
            event.preventDefault(); 
            errorDiv.innerText = 'System Error: All parameters must be provided.';
            errorDiv.style.display = 'block';
            return;
        }

        const numberRegex = /^\d+$/; 
        if (!numberRegex.test(phone) || !numberRegex.test(engine)) {
            event.preventDefault();
            errorDiv.innerText = 'System Error: Phone and Engine values must be strictly numeric.';
            errorDiv.style.display = 'block';
            return;
        }
    });
</script>

</body>
</html>
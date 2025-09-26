<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking an Appointment</title>
    <link rel="stylesheet" href="appointment.css?v=2">
</head>
<body>
    <div class="container">
        <h1>Book an Appointment</h1>

        <div id="appointment-general" class="error-message"></div>

        <form id="appointmentForm" action="backend/appointment_handler.php" method="POST" novalidate>
            <div id="appointment-name" class="error-message"></div>
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>

            <div id="appointment-email" class="error-message"></div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <div id="appointment-phone" class="error-message"></div>
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" required>

            <div id="appointment-datetime" class="error-message"></div>
            <label for="appointment_datetime">Appointment Date & Time</label>
            <select id="appointment_datetime" name="appointment_datetime" required>
                <option value="" disabled selected>Select a Date and Time</option>
            </select>

            <div id="appointment-purpose" class="error-message"></div>
            <label for="purpose">Purpose of Visit</label>
            <select id="purpose" name="purpose" required>
                <option value="" disabled selected>Select a Reason</option>
                <option value="Consultation">Consultation</option>
                <option value="Service">Service</option>
                <option value="Inquiry">Inquiry</option>
                <option value="Other">Other</option>
            </select>

            <div id="appointment-message" class="error-message"></div>
            <label for="message">Message (Optional)</label>
            <textarea id="message" name="message"></textarea>

            <button type="submit">Proceed</button>
        </form>
    </div>

    <script src="backend/scripts/shedule.js"></script>
</body>
</html>
// appointments.js

document.addEventListener('DOMContentLoaded', () => {
    // Get references to DOM elements
    const addAppointmentBtn = document.getElementById('addAppointmentBtn');
    const addAppointmentModal = document.getElementById('addAppointmentModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelAppointmentBtn = document.getElementById('cancelAppointmentBtn');
    const appointmentForm = document.getElementById('appointmentForm');
    const appointmentsTableBody = document.getElementById('appointmentsTableBody');
    const totalAppointmentsDisplay = document.getElementById('totalAppointments');
    const pendingAppointmentsDisplay = document.getElementById('pendingAppointments');

    let totalAppointments = 3; // Initialize with existing appointments
    let pendingAppointments = 2; // Initialize with existing pending appointments

    // Function to update metric displays
    const updateMetrics = () => {
        totalAppointmentsDisplay.textContent = totalAppointments;
        pendingAppointmentsDisplay.textContent = pendingAppointments;
    };

    // Initial update of metrics
    updateMetrics();

    // Event listener to open the modal
    addAppointmentBtn.addEventListener('click', () => {
        addAppointmentModal.classList.add('active');
        // Set default date to today
        document.getElementById('appointmentDate').valueAsDate = new Date();
    });

    // Event listener to close the modal using the 'x' button
    closeModalBtn.addEventListener('click', () => {
        addAppointmentModal.classList.remove('active');
        appointmentForm.reset(); // Clear form fields on close
    });

    // Event listener to close the modal using the 'Cancel' button
    cancelAppointmentBtn.addEventListener('click', () => {
        addAppointmentModal.classList.remove('active');
        appointmentForm.reset(); // Clear form fields on cancel
    });

    // Event listener for form submission
    appointmentForm.addEventListener('submit', (event) => {
        event.preventDefault(); // Prevent default form submission

        // Get form values
        const appointmentDate = document.getElementById('appointmentDate').value;
        const appointmentTime = document.getElementById('appointmentTime').value;
        const customerName = document.getElementById('customerName').value;
        const serviceType = document.getElementById('serviceType').value;
        const appointmentStatus = document.getElementById('appointmentStatus').value;

        // Create a new table row
        const newRow = document.createElement('tr');

        // Determine status class for styling
        let statusClass = '';
        if (appointmentStatus === 'New') {
            statusClass = 'status-new';
        } else if (appointmentStatus === 'Confirmed') {
            statusClass = 'status-in-progress';
        } else if (appointmentStatus === 'Completed') {
            statusClass = 'status-resolved';
        } else {
            statusClass = 'status-new'; // Default for other statuses
        }

        newRow.innerHTML = `
            <td>${appointmentDate}</td>
            <td>${appointmentTime}</td>
            <td>${customerName}</td>
            <td>${serviceType}</td>
            <td><span class="${statusClass}">${appointmentStatus}</span></td>
            <td>
                <button class="action-btn edit-btn">Edit</button>
                <button class="action-btn delete-btn">Delete</button>
            </td>
        `;

        // Add the new row to the table body
        appointmentsTableBody.appendChild(newRow);

        // Update metrics
        totalAppointments++;
        if (appointmentStatus === 'New' || appointmentStatus === 'Confirmed') { // Assuming pending are New or Confirmed
            pendingAppointments++;
        }
        updateMetrics();

        // Close the modal and reset the form
        addAppointmentModal.classList.remove('active');
        appointmentForm.reset();

        // In a real application, you would send this data to a server using fetch()
        // Example:
        // fetch('handle_appointment.php', {
        //     method: 'POST',
        //     headers: {
        //         'Content-Type': 'application/json',
        //     },
        //     body: JSON.stringify({
        //         date: appointmentDate,
        //         time: appointmentTime,
        //         customer: customerName,
        //         service: serviceType,
        //         status: appointmentStatus
        //     }),
        // })
        // .then(response => response.json())
        // .then(data => {
        //     console.log('Success:', data);
        //     // Add to table after successful server response
        // })
        // .catch((error) => {
        //     console.error('Error:', error);
        // });
    });

    // You can add event listeners for edit and delete buttons here
    // For example, using event delegation on the table body:
    appointmentsTableBody.addEventListener('click', (event) => {
        if (event.target.classList.contains('delete-btn')) {
            // Logic to delete an appointment
            const row = event.target.closest('tr');
            if (row) {
                const statusElement = row.querySelector('.status-new, .status-in-progress');
                if (statusElement) {
                    pendingAppointments--; // Decrement pending if it was new or in progress
                }
                totalAppointments--;
                row.remove();
                updateMetrics();
            }
        } else if (event.target.classList.contains('edit-btn')) {
            // Logic to edit an appointment (e.g., populate modal with existing data)
            console.log('Edit button clicked');
            // This would involve getting the data from the row, populating the modal,
            // and then handling an update submission instead of an add submission.
        }
    });
});

export function showError(divId, message) {
    const errorDiv = document.getElementById(divId);
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = "block";
    }
}

export function clearErrors() {
    document.querySelectorAll(".error-message").forEach(div => {
        div.textContent = "";
        div.style.display = "none";
    });
}

export function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

export function validatePhone(phone) {
    const re = /^[0-9]{11}$/;
    return re.test(phone);
}

export function validateMessageLength(message, maxLength = 500) {
    return message.length <= maxLength;
}
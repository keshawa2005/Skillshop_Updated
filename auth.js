// JavaScript for handling authentication (sign in and sign up)
function createAccount() {
  // Get form values
  var fname = document.getElementById("signup-firstname").value;
  var lname = document.getElementById("signup-lastname").value;
  var email = document.getElementById("signup-email").value;
  var password = document.getElementById("signup-password").value;
  var cpassword = document.getElementById("signup-confirm-password").value;
  var accountTypeSeller = document.getElementById("account-type-seller");
  var accountTypeBuyer = document.getElementById("account-type-buyer");
  var termsCheckbox = document.getElementById("signup-terms");
  var errorMessage = document.getElementById("signup-message");
  errorMessage.classList.remove("hidden");

  //   Client-side validation
  if (!fname || !lname || !email || !password || !cpassword) {
    errorMessage.classList.remove("hidden");
    errorMessage.innerHTML = "All fields are required.";
  } else if (password.length < 8) {
    errorMessage.innerHTML = "Password must be at least 8 characters long.";
  } else if (password !== cpassword) {
    errorMessage.innerHTML = "Passwords do not match.";
  } else if (!accountTypeSeller.checked && !accountTypeBuyer.checked) {
    errorMessage.innerHTML = "Please select an account type.";
  } else if (!termsCheckbox.checked) {
    errorMessage.innerHTML = "You must agree to the terms and conditions.";
  } else {
    errorMessage.classList.add("hidden");

    // Prepare form data for submission (this is just a placeholder, actual submission logic will depend on your backend setup)
    var form = new FormData();
    form.append("fname", fname);
    form.append("lname", lname);
    form.append("email", email);
    form.append("password", password);
    form.append("cpassword", cpassword);
    form.append(
      "accountType",
      accountTypeSeller.checked
        ? accountTypeSeller.value
        : accountTypeBuyer.value,
    );
    form.append("terms", termsCheckbox.checked);

    // Send form data to the server (this is just a placeholder, actual submission logic will depend on your backend setup)
    var r = new XMLHttpRequest();

    // Handle server response
    r.onreadystatechange = function () {
      if (r.readyState == 4) {
        errorMessage.classList.remove("hidden");

        if (r.status === 200) {
          if (r.responseText === "success") {
            errorMessage.classList.remove("text-red-600");
            errorMessage.classList.add("text-green-600");
            errorMessage.innerHTML = "Account created successfully!";
            setTimeout(() => {
              window.location.reload(); // Reload the page after 2 seconds to reflect changes (e.g., redirect to login or dashboard)
            }, 2000);
          } else {
            errorMessage.innerHTML = r.responseText; // Display server response as error message (if any)
          }
        } else {
          errorMessage.innerHTML = "Request Failed! : " + r.responseText; // Display error message if request fails
        }
      }
    };

    r.open("POST", "process/createAccountProcess.php", true);
    r.send(form);
  }
}

// JavaScript function to handle sign in
function signIn() {
  var email = document.getElementById("signin-email").value;
  var password = document.getElementById("signin-password").value;
  var rememberMe = document.getElementById("remember");
  var signIn_errorMessage = document.getElementById("signin-message");
  signIn_errorMessage.classList.remove("hidden");

  // Client-side validation
  if (!email || !password) {
    signIn_errorMessage.innerHTML = "Email and password are required.";
  } else if (!validateEmail(email)) {
    signIn_errorMessage.innerHTML = "Invalid email address.";
  } else {
    signIn_errorMessage.classList.add("hidden");

    // Prepare form data for submission
    var form = new FormData();
    form.append("email", email);
    form.append("password", password);
    form.append("rememberMe", rememberMe.checked ? "true" : "false");

    // Send form data to the server
    var r = new XMLHttpRequest();

    r.onreadystatechange = function () {
      if (r.readyState == 4) {
        signIn_errorMessage.classList.remove("hidden");

        if (r.status === 200) {
          if (r.responseText === "success") {
            signIn_errorMessage.classList.remove("text-red-600");
            signIn_errorMessage.classList.add("text-green-600");
            signIn_errorMessage.innerHTML =
              "Sign in successful! Redirecting...";
            setTimeout(() => {
              window.location.href = "home.php"; // Redirect to dashboard after successful sign in
            }, 2000);
          } else {
            signIn_errorMessage.innerHTML = r.responseText; // Display server response as error message (if any)
          }
        } else {
          signIn_errorMessage.innerHTML = "Request Failed! : " + r.responseText; // Display error message if request fails
        }
      }
    };

    r.open("POST", "process/signInProcess.php", true);
    r.send(form);
  }
}

// Utility function to validate email format
function validateEmail(email) {
  var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// JavaScript function to handle forgot password
function forgotPassword() {
  var email = document.getElementById("forgot-email").value;
  var forgotMessage = document.getElementById("forgot-message");
  forgotMessage.classList.remove("hidden");
  var sendButton = document.getElementById("forgot-password-send-code-button");

  // Client-side validation
  if (!email || !validateEmail(email)) {
    forgotMessage.className = "text-red-600 text-sm rounded-lg mb-2 p-2";
    forgotMessage.innerHTML = "Invalid email address.";
  } else {
    var form = new FormData();
    form.append("email", email);

    forgotMessage.className = "text-blue-600 text-sm rounded-lg mb-2 p-2";
    forgotMessage.innerHTML =
      "Sending verification code... <span class='inline-block animate-spin'>⏳</span>";
    sendButton.disabled = true; // Disable the send button while processing
    sendButton.style.opacity = "0.6"; // Reduce opacity to indicate it's disabled

    var r = new XMLHttpRequest();

    r.open("POST", "process/forgotPasswordProcess.php", true);
    r.onload = () => {
      sendButton.disabled = false;
      sendButton.style.opacity = "1"; // Restore opacity after processing
      var response = r.responseText;
      forgotMessage.classList.remove("hidden");
      forgotMessage.className =
        "text-sm rounded-lg mb-2 p-2" +
        (response === "success" ? " text-green-600" : " text-red-600");
      forgotMessage.innerHTML =
        response === "success"
          ? "✓ Verification code sent successfully!"
          : response; // Display server response as message

      if (response === "success")
        setTimeout(() => {
          document.getElementById("forgot-step-1").classList.add("hidden");
          document.getElementById("forgot-step-2").classList.remove("hidden");
          document.getElementById("verify-message").classList.add("hidden"); // Hide any previous messages in step 2
        }, 1500); // Move to step 2 after a short delay to allow user to read the message
    };
    r.onerror = () => {
      sendButton.disabled = false;
      sendButton.style.opacity = "1";
      forgotMessage.classList.remove("hidden");
      forgotMessage.className = "text-red-600 text-sm rounded-lg mb-2 p-2";
      forgotMessage.innerHTML = "Network error! Please try again.";
    };
    r.send(form);
  }
}

// Verify Code
function verifyCode() {
    let code = document.getElementById("verify-code").value;
    let email = document.getElementById("forgot-email").value;
    let msg = document.getElementById("verify-message");
    let verifyBtn = document.getElementById("verify-code-button");
    
    msg.classList.remove("hidden");
    
    if (code.length !== 6 || !/^\d+$/.test(code)) {
        msg.className = "mb-4 p-3 rounded-lg text-sm text-red-500";
        msg.innerHTML = "Enter exactly 6 digits!";
    } else {
        msg.className = "mb-4 p-3 rounded-lg text-sm text-blue-500";
        msg.innerHTML = "Verifying... <span class='inline-block animate-spin'>⏳</span>";
        verifyBtn.disabled = true;
        verifyBtn.style.opacity = "0.6";
        
        let form = new FormData();
        form.append("email", email);
        form.append("code", code);
        form.append("action", "verify");
        
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "process/resetPasswordProcess.php", true);
        xhr.onload = () => {
            verifyBtn.disabled = false;
            verifyBtn.style.opacity = "1";
            let response = xhr.responseText.trim();
            msg.classList.remove("hidden");
            msg.className = "mb-4 p-3 rounded-lg text-sm " + (response == "success" ? "text-green-500" : "text-red-500");
            msg.innerHTML = response == "success" ? "✓ Code verified!" : response;
            if (response == "success") setTimeout(() => {
                document.getElementById("forgot-step-2").classList.add("hidden");
                document.getElementById("forgot-step-3").classList.remove("hidden");
                document.getElementById("reset-message").classList.add("hidden");
            }, 1500);
        };
        xhr.onerror = () => {
            verifyBtn.disabled = false;
            verifyBtn.style.opacity = "1";
            msg.classList.remove("hidden");
            msg.className = "mb-4 p-3 rounded-lg text-sm text-red-500";
            msg.innerHTML = "Network error. Please try again.";
        };
        xhr.send(form);
    }
}

// Reset Password
function resetPassword() {
    let pwd = document.getElementById("reset-password").value;
    let confirm = document.getElementById("reset-password-confirm").value;
    let email = document.getElementById("forgot-email").value;
    let msg = document.getElementById("reset-message");
    let resetBtn = document.querySelector("#forgot-step-3 button[onclick='resetPassword();']");
    
    msg.classList.remove("hidden");
    
    if (!pwd || !confirm) {
        msg.className = "mb-4 p-3 rounded-lg text-sm text-red-500";
        msg.innerHTML = "All fields required!";
    } else if (pwd.length < 8) {
        msg.className = "mb-4 p-3 rounded-lg text-sm text-red-500";
        msg.innerHTML = "Password must be 8+ characters!";
    } else if (pwd !== confirm) {
        msg.className = "mb-4 p-3 rounded-lg text-sm text-red-500";
        msg.innerHTML = "Passwords don't match!";
    } else {
        msg.className = "mb-4 p-3 rounded-lg text-sm text-blue-500";
        msg.innerHTML = "Resetting... <span class='inline-block animate-spin'>⏳</span>";
        resetBtn.disabled = true;
        resetBtn.style.opacity = "0.6";
        
        let form = new FormData();
        form.append("email", email);
        form.append("password", pwd);
        form.append("confirm_password", confirm);
        form.append("action", "reset");
        
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "process/resetPasswordProcess.php", true);
        xhr.onload = () => {
            resetBtn.disabled = false;
            resetBtn.style.opacity = "1";
            let response = xhr.responseText.trim();
            msg.classList.remove("hidden");
            msg.className = "mb-4 p-3 rounded-lg text-sm " + (response == "success" ? "text-green-500" : "text-red-500");
            msg.innerHTML = response == "success" ? "✓ Password reset successfully!" : response;
            if (response == "success") setTimeout(() => {
                closeForgotPasswordModal();
                document.getElementById("reset-password").value = "";
                document.getElementById("reset-password-confirm").value = "";
            }, 2000);
        };
        xhr.onerror = () => {
            resetBtn.disabled = false;
            resetBtn.style.opacity = "1";
            msg.classList.remove("hidden");
            msg.className = "mb-4 p-3 rounded-lg text-sm text-red-500";
            msg.innerHTML = "Network error. Please try again.";
        };
        xhr.send(form);
    }
}


// Functions to handle forgot password modal
function openForgotPasswordModal() {
  document.getElementById("forgot-password-modal").classList.remove("hidden");
  // Reset to step 1 and hide other steps when opening the modal
  document.getElementById("forgot-step-1").classList.remove("hidden");
  document.getElementById("forgot-step-2").classList.add("hidden");
  document.getElementById("forgot-step-3").classList.add("hidden");

  document.getElementById("forgot-email").focus(); // Focus on the email input field when modal opens
}

// Function to close the forgot password modal and reset all fields and messages
function closeForgotPasswordModal() {
  document.getElementById("forgot-password-modal").classList.add("hidden");

  // Reset all input fields and messages when closing the modal
  document.getElementById("forgot-email").value = "";
  document.getElementById("verify-code").value = "";
  document.getElementById("reset-password").value = "";
  document.getElementById("reset-confirm-password").value = "";

  document.getElementById("forgot-message").classList.add("hidden");
  document.getElementById("verify-message").classList.add("hidden");
  document.getElementById("reset-message").classList.add("hidden");
}

// Function to go back to email entry step from code verification step
function backToEmail() {
  document.getElementById("forgot-step-2").classList.add("hidden");
  document.getElementById("forgot-step-1").classList.remove("hidden");
  document.getElementById("verify-code").value = ""; // Clear the verification code input field
  document.getElementById("verify-message").classList.add("hidden");
}

const resetForm = document.getElementById("register-form");
resetForm.addEventListener("submit", registerForm);

function showError(message) {
  document.getElementById("responseToCall").innerHTML = `
        <div class="uk-alert-danger" uk-alert>
            <a href class="uk-alert-close" uk-close></a>
            <p class="uk-text-center uk-margin-right uk-margin-left">${message}</p>
        </div>`;
}

function registerForm(e) {
  e.preventDefault();
  const username = document.getElementById("username-create-account").value;
  const email = document.getElementById("email-create-account").value;

  fetch("/api/register", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ username, email }),
    credentials: "include",
  })
    .then((response) => {
      if (response.status === 201) {
        response.json().then((data) => {
          location = `/change-password?h=${data["changePasswordLink"]}`;
        });
      } else {
        response.json().then((data) => {
          showError(data["message"]);
        });
      }
    })
    .catch((error) => {
      showError("Something went wrong (details in console)");
      console.log(error);
    });
}

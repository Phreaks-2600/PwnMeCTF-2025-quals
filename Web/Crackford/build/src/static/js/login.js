const loginForm = document.getElementById("login-form");
loginForm.addEventListener("submit", loginFormSubmit);

function showError(message) {
  document.getElementById("responseToCall").innerHTML = `
        <div class="uk-alert-danger" uk-alert>
            <a href class="uk-alert-close" uk-close></a>
            <p class="uk-text-center uk-margin-right uk-margin-left">${message}</p>
        </div>`;
}

function loginFormSubmit(e) {
  e.preventDefault();
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  fetch("/api/login", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ email, password }),
    credentials: "include",
  })
    .then((response) => {
      if (response.status === 200) {
        location.reload();
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

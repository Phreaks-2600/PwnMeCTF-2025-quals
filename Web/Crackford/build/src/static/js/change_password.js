const form = document.getElementById("change-password-form");
form.addEventListener("submit", handleForm);

function showError(message) {
  document.getElementById("responseToCall").innerHTML = `
          <div class="uk-alert-danger" uk-alert>
              <a href class="uk-alert-close" uk-close></a>
              <p class="uk-text-center uk-margin-right uk-margin-left">${message}</p>
          </div>`;
}

function handleForm(e) {
  e.preventDefault();
  const password = document.getElementById("password-change-password").value;

  fetch(`/api/change-password${location.search}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ password }),
    credentials: "include",
  })
    .then((response) => {
      if (response.status === 200) {
        response.json().then((data) => {
          document.getElementById("responseToCall").innerHTML = `
                <div class="uk-alert-success" uk-alert>
                    <a href class="uk-alert-close" uk-close></a>
                    <div class="uk-text-center uk-margin-right uk-margin-left">${data["message"]} <a href="/">Go back to login</a></div>
                </div>`;
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

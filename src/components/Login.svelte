<script>
  import { onMount } from "svelte";
  import { authToken, isLogged } from "../stores";
  import Loader from "./Loader.svelte";

  let password = "";
  let email = "";
  let error = "";
  let isLoading = true;
  let session_code = "";

  onMount(async () => {
    const response = await fetch(API_URL + "/get-session-code", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    })
      .then((response) => response.json())
      .catch((e) => {
        error = e;
      });

    isLoading = false;
    if (response.session_code) {
      session_code = response.session_code;
    } else {
      error = response.error;
    }
  });

  const handleLogin = async () => {
    isLoading = true;

    const response = await fetch(API_URL + "/login", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ email, password, session_code }),
    })
      .then((response) => response.json())
      .catch((e) => {
        error = e;
      });

    isLoading = false;
    if (response?.token) {
      $authToken = response.token;
      $isLogged = true;
    } else {
      session_code = response?.session_code || "";
      error = response?.error || error;
      $isLogged = false;
    }
  };
</script>

<div class="container">
  <div class="row">
    <div class="col-12 col-md-7 col-lg-5 mx-auto">
      <div class="card register--box">
        <div class="card-body">
          <h1 class="card-title">Logowanie</h1>
          {#if error}
            <div class="alert alert-danger" role="alert">{error}</div>
          {/if}
          {#if isLoading}
            <Loader />
          {:else}
            <form on:submit|preventDefault={handleLogin} method="post">
              <div class="mb-3">
                <label for="email">Adres email:</label>
                <input
                  type="text"
                  bind:value={email}
                  id="email"
                  class="form-control"
                  required
                />
              </div>
              <div class="mb-3">
                <label for="password">Hasło:</label>
                <input
                  type="password"
                  bind:value={password}
                  id="password"
                  class="form-control"
                  required
                />
              </div>
              <div class="mb-3">
                <button type="submit" class="btn btn-block btn-success"
                  >Zaloguj</button
                >
              </div>
            </form>
          {/if}
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .register--box {
    margin: 50px 0;
    box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
    background-color: white;
  }
</style>

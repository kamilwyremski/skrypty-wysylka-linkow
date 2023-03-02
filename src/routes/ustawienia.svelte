<script>
	import { onMount } from 'svelte';
	import {
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader
  } from 'sveltestrap';
	import { authToken, isLogged } from "../stores";
	import Loader from "../components/Loader.svelte";

	let logs = [];
	let users = [];
	let error = '';
	let success = '';
	let isLoading = true;
	let newEmail = '';
	let newPassword = '';
	let newRepeatPassword = '';
	let newUserEmail = '';
	let newUserPassword = '';
	let newUserRepeatPassword = '';
	let removeUserEmail = '';
	let removeUserId = 0;

  onMount(() => {
		loadUsers();
		loadLogs();
	});

	const loadUsers = async () => {
		isLoading = true;
		const response = await fetch(API_URL+'/get-users', {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
				'Authorization': 'Bearer ' + $authToken
			},
		})
		.then(response => response.json())
    .catch((e) => {
      error = e;
    });

		isLoading = false;
    if (response.users) {
      users = response.users;
    } else {
			error = response.error || error;
    }
	};

	let openModalRemoveUser = false;
  const toggleModalRemoveUser = (index) => {
		if(users[index]){
			removeUserEmail = users[index].email
			removeUserId = users[index].id
		}
		openModalRemoveUser = !openModalRemoveUser
	}

	const loadLogs = async () => {
		isLoading = true;
		const response = await fetch(API_URL+'/get-logs-admin', {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
				'Authorization': 'Bearer ' + $authToken
			},
		})
		.then(response => response.json())
    .catch((e) => {
      error = e;
    });

		isLoading = false;
    if (response.logs) {
      logs = response.logs;
    } else {
			error = response.error || error;
    }
	};

	const handleChangeUser = async () => {
		if(newPassword != newRepeatPassword){
			error = 'Podane hasła są różne!';
			return false;
		}
		isLoading = true;
		success = 'Aktualizuje dane na serwerze...';
		const response = await fetch(API_URL+'/change-user', {
			method: "POST",
			headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
				'Authorization': 'Bearer ' + $authToken
			},
			body: JSON.stringify({ 'email': newEmail, 'password': newPassword }),
		})
		.then(response => response.json())
    .catch((e) => {
      error = e;
    });

		isLoading = false;
		if (response.status) {
			error = '';
			success = 'Dane zostały poprawnie zaktualizowane';
			newPassword = '';
			newRepeatPassword = '';
			loadUsers();
		} else {
			error = response.error || error;
			success = ''
		}
	};

	const handleAddUser = async () => {
		if(newUserPassword != newUserRepeatPassword){
			error = 'Podane hasła są różne!';
			return false;
		}
		isLoading = true;
		success = 'Wysyłam dane o użytkowniku na serwer...';
		const response = await fetch(API_URL+'/add-user', {
			method: "POST",
			headers: {
        "Content-Type": "application/json",
				"Accept": "application/json",
				'Authorization': 'Bearer ' + $authToken
			},
			body: JSON.stringify({ 'email': newUserEmail, 'password': newUserPassword }),
		})
		.then(response => response.json())
    .catch((e) => {
      error = e;
    });

		isLoading = false;
		if (response.status) {
			error = '';
			success = 'Użytkownik został poprawnie dodany';
			newUserEmail = '';
			newUserPassword = '';
			newUserRepeatPassword = '';
			loadUsers();
		} else {
			error = response.error || error;
			success = ''
		}
	};

	let openModallogoutAll = false;
	const toggleModallogoutAll = () => (openModallogoutAll = !openModallogoutAll);

	const handlelogoutAllLink = async () => {
		const response = await fetch(API_URL+'/logout-all', {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
				'Authorization': 'Bearer ' + $authToken
			},
		})

		$authToken = '';
    $isLogged = false;
	};

	const handleRemoveUser = async () => {
		isLoading = true;
		openModalRemoveUser = false;
		success = 'Usuwam użytkownika na serwerze...';
		const response = await fetch(API_URL+'/remove-user', {
			method: "POST",
			headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
				'Authorization': 'Bearer ' + $authToken
			},
			body: JSON.stringify({ 'id': removeUserId }),
		})
		.then(response => response.json())
    .catch((e) => {
      error = e;
    });

		isLoading = false;
		if (response.status) {
			error = '';
			success = 'Użytkownik został poprawnie usunięty';
			loadUsers();
		} else {
			error = response.error || error;
			success = '';
		}
	};
</script>

<svelte:head>
	<title>Ustawienia</title>
</svelte:head>

<div class="container mt-5 mb-5">
	<h1 class="mb-3">Ustawienia</h1>

	{#if error}
		<h5 class="text-danger">{error}</h5>
	{/if}

	{#if success}
		<h5 class="text-success">{success}</h5>
	{/if}

	{#if isLoading}
		<Loader/>
	{/if}

	<div class="row mt-3">
		<div class="col-md-6">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Zmień dane dostępowe do panelu admina</h5>
					<form on:submit|preventDefault="{handleChangeUser}" method="post">
						<div class="form-group">
							<label for="new_email">Nowy adres email</label>
							<input type="text" class="form-control" bind:value="{newEmail}" id="new_email" required title="Wpisz swój nowy adres email" maxlength="64">
						</div>
						<div class="form-group">
							<label for="new_password">Nowe hasło</label>
							<input type="password" class="form-control" bind:value="{newPassword}" id="new_password" required title="Wpisz swoje nowe hasło">
						</div>
						<div class="form-group">
							<label for="repeat_new_password">Powtórz nowe hasło</label>
							<input type="password" class="form-control" bind:value="{newRepeatPassword}"  id="repeat_new_password" required title="Wpisz swoje nowe hasło">
						</div>
						<input type="submit" class="btn btn-success btn-block" value="Zmień dane!">
					</form>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Dodaj użytkownika</h5>
					<form on:submit|preventDefault="{handleAddUser}" method="post">
						<div class="form-group">
							<label for="new_user_email">Email</label>
							<input type="text" class="form-control" bind:value="{newUserEmail}" id="new_user_email" required title="Wpisz login" maxlength="32">
						</div>
						<div class="form-group">
							<label for="new_user_password">Hasło</label>
							<input type="password" class="form-control" bind:value="{newUserPassword}" id="password" required title="Wpisz hasło">
						</div>
						<div class="form-group">
							<label for="new_user_repeat_password">Powtórz hasło</label>
							<input type="password" class="form-control" bind:value="{newUserRepeatPassword}" id="new_user_repeat_password" required title="Powtórz hasło">
						</div>
						<input type="submit" class="btn btn-primary btn-block" value="Dodaj użytkownika">
					</form>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Lista użytkowników</h5>
					{#if users.length}
						<div class="table-responsive">
							<table class="table table-striped table-hover table-sm">
								{#each users as user, index}
									<tr>
										<td>{index+1}</td>
										<td>{user.email}</td>
										<td><button type="button" class="btn btn-link text-danger" on:click={() => toggleModalRemoveUser(index)}><i class="fas fa-trash"></i></button></td>
									</tr>
								{/each}
							</table>
						</div>

						<Modal isOpen={openModalRemoveUser} {toggleModalRemoveUser}>
							<ModalHeader {toggleModalRemoveUser}>Usuń skrypt</ModalHeader>
							<form on:submit|preventDefault="{handleRemoveUser}" method="post">
								<ModalBody>
									<p>Na pewno chcesz usunąć użytkownika {removeUserEmail}?</p>
								</ModalBody>
								<ModalFooter>
									<input type="button" class="btn btn-secondary" on:click={toggleModalRemoveUser} value="Anuluj">
									<input type="submit" class="btn btn-danger" value="Usuń">
								</ModalFooter>
							</form>
						</Modal>
					{/if}
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Sesje użytkowników</h5>
					<p>Wyloguj wszystkich użytkowników</p>
					<button type="button" class="btn btn-danger" on:click={toggleModallogoutAll}>Wyloguj</button>
				</div>
			</div>
		</div>
	</div>
	{#if logs.length}
		<div class="card">
			<div class="card-body">
				<h5 class="card-title">Logi logowania</h5>
				<div class="table-responsive">
					<table class="table table-striped table-hover table-sm">
						<tr>
							<th></th>
							<th>Email</th>
							<th class="text-center">Zalogowany</th>
							<th>IP</th>
							<th class="text-nowrap">Data</th>
						</tr>
						{#each logs as log, index}
							<tr class:table-danger={log.logged!=1}>
								<td>{index+1}</td>
								<td>{log.email}</td>
								<td class="text-center">{#if log.logged==1}Tak{:else}Nie{/if}</td>
								<td>{log.ip}</td>
								<td class="text-nowrap">{log.date}</td>
							</tr>
						{/each}
					</table>
				</div>
			</div>
		</div>
	{/if}

	<Modal isOpen={openModallogoutAll} {toggleModallogoutAll}>
		<ModalHeader {toggleModallogoutAll}>Wyloguj wszystkich</ModalHeader>
		<form on:submit|preventDefault="{handlelogoutAllLink}" method="post">
			<ModalBody>
				<p>Spowoduje to usunięcie wszystkich sesji użytkowników z bazy danych</p>
			</ModalBody>
			<ModalFooter>
				<input type="button" class="btn btn-secondary" on:click={toggleModallogoutAll} value="Anuluj">
				<input type="submit" class="btn btn-danger" value="Wyloguj">
			</ModalFooter>
		</form>
	</Modal>

</div>
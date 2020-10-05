<script>
	import { onMount } from 'svelte';
	import {
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader
  } from 'sveltestrap';
	import { authToken } from "../stores";
	import Loader from "../components/Loader.svelte";

	let scripts = [];
	let links = [];
	let error = '';
	let success = '';
	let isLoading = true;
	let addLinkId = 0;
	let addLinkScript = 0;
	let addLinkEmail = '';
	let addLinkLicenseNumber = 0;
	let d = new Date();
	d.setDate(new Date().getDate()+7);
	let addLinkDateFinish = d.getFullYear()+'-'+(d.getMonth()+1)+'-'+ ("0" + d.getDate()).slice(-2) ;
	let addLinkDownloadLimit = 5;
	let SendMailLinkSubject = '';
	let SendMailLinkContent = '';

	let openModalAdd = false;
	const toggleModalAdd = () => {
		addLinkId = 0;
		addLinkEmail = '';
		addLinkLicenseNumber = 0;
		openModalAdd = !openModalAdd
	};
	
  const toggleModalEdit = (index) => {
		if(links[index]){
			addLinkId = links[index].id
			addLinkScript = links[index].script_id
			addLinkEmail = links[index].email
			addLinkLicenseNumber = links[index].license_number
			addLinkDateFinish = links[index].date_finish
			addLinkDownloadLimit = links[index].download_limit
		}
		openModalAdd = !openModalAdd
	}

	let openModalRemove = false;
  const toggleModalRemove = (index) => {
		if(links[index]){
			addLinkId = links[index].id
		}
		openModalRemove = !openModalRemove
	}

	let openModalSendMail = false;
  const toggleModalSendMail = (index) => {
		if(links[index]){
			addLinkId = links[index].id
			SendMailLinkSubject = 'Skrypt '+links[index].script_name+' jest już dostępny do pobrania!'
			SendMailLinkContent = "Witaj!\nAby pobrać skrypt "+links[index].script_name+" kliknij w link "+links[index].link+"\nLink będzie aktywny do "+links[index].date_finish+"\nSkrypt możesz pobrać "+links[index].download_limit+" razy\nProsimy zapoznać się koniecznie z instrukcją instalacji i konfiguracji: https://blog.wyremski.pl/instrukcja-instalacji-i-konfiguracji-skryptow/\nPozdrawiamy\nZespół IT Works Better"
		}
		openModalSendMail = !openModalSendMail
	}

  onMount(() => {
		loadScripts();
		loadLinks();
	});
	
	const loadScripts = async () => {
		const response = await fetch(API_URL+'/get-scripts', {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
			},
			body: JSON.stringify({ 'token': $authToken }),
		});
		const parsed = await response.json();
    if (parsed.scripts) {
      scripts = parsed.scripts;
    }
	};

	const loadLinks = async () => {
		isLoading = true;
		const response = await fetch(API_URL+'/get-links', {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
			},
			body: JSON.stringify({ 'token': $authToken }),
		});
		const parsed = await response.json();
		isLoading = false;
    if (parsed.links) {
      links = parsed.links;
    } else {
			error = parsed.error;
			success = ''
    }
	};

	const handleAddLink = async () => {
		isLoading = true;
		openModalAdd = false;
		let body = { 'token': $authToken, 'script_id': addLinkScript, 'email': addLinkEmail, 'license_number': addLinkLicenseNumber, 'date_finish': addLinkDateFinish, 'download_limit': addLinkDownloadLimit };
		let url = API_URL+'/add-link'
		if(addLinkId){
			url = API_URL+'/edit-link'
			body.id = addLinkId
			success = 'Aktualizuje link na serwerze...';
		}else{
			success = 'Wysyłam link na serwer...';
		}
		const response = await fetch(url, {
			method: "POST",
			headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
			},
			body: JSON.stringify(body),
		})
		const parsed = await response.json();
		isLoading = false;
		addLinkScript = '';
		addLinkEmail = '';
		if (parsed.status) {
			success = 'Link został poprawnie dodany. Nie zapomnij wysłać maila!';
			loadLinks();
		} else {
			error = parsed.error;
			success = ''
		}
	};

	const handleRemoveLink = async () => {
		isLoading = true;
		openModalRemove = false;
		success = 'Usuwam link na serwerze...';
		const response = await fetch(API_URL+'/remove-link', {
			method: "POST",
			headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
			},
			body: JSON.stringify({ 'token': $authToken, 'id': addLinkId }),
		})
		const parsed = await response.json();
		isLoading = false;
		addLinkId = 0;
		if (parsed.status) {
			success = 'Link został poprawnie usunięty';
			loadLinks();
		} else {
			error = parsed.error;
			success = ''
		}
	};

	const handleSendMailLink = async () => {
		isLoading = true;
		openModalSendMail = false;
		success = 'Wysyłam mail...';
		const response = await fetch(API_URL+'/send-mail', {
			method: "POST",
			headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
			},
			body: JSON.stringify({ 'token': $authToken, 'id': addLinkId, 'subject': SendMailLinkSubject, 'content': SendMailLinkContent }),
		})
		const parsed = await response.json();
		isLoading = false;
		addLinkId = 0;
		if (parsed.status) {
			success = 'Mail został poprawnie wysłany';
		} else {
			error = parsed.error;
			success = ''
		}
	};

</script>

<svelte:head>
	<title>Linki do pobrania skryptów</title>
</svelte:head>

<div class="container mt-5 mb-5">
	<h1 class="mb-4">Linki do pobrania skryptów</h1>

	{#if error}
		<h5 class="text-danger">{error}</h5>
	{/if}
	{#if success}
		<h5 class="text-success">{success}</h5>
	{/if}

	<button type="button" class="btn btn-primary mt-2 mb-4" on:click={toggleModalAdd}>Dodaj nowy link</button>

	<Modal isOpen={openModalAdd} {toggleModalAdd}>
		<ModalHeader {toggleModalAdd}>Dodaj nowy link</ModalHeader>
		<form on:submit|preventDefault="{handleAddLink}" method="post">
			<ModalBody>
				<div class="form-group">
					<label for="script">Skrypt <span class="text-danger">*</span></label>
					<select bind:value={addLinkScript} required class="form-control">
						<option value="0">-- wybierz --</option>
						{#each scripts as script}
							<option value={script.id}>{script.name}</option>
						{/each}
					</select>
				</div>
				<div class="form-group">
					<label for="name">Adres email <span class="text-danger">*</span></label>
					<input type="email" class="form-control" bind:value="{addLinkEmail}" required maxlength="128">
				</div>
				<div class="form-group">
					<label for="license_number">Numer licencji</label>
					<input type="number" class="form-control" bind:value="{addLinkLicenseNumber}" min="0">
				</div>
				<div class="form-group">
					<label for="date_finish">Data końca <span class="text-danger">*</span></label>
					<input type="date" class="form-control" bind:value="{addLinkDateFinish}" required>
				</div>
				<div class="form-group">
					<label for="download_limit">Limit pobrań <span class="text-danger">*</span></label>
					<input type="number" class="form-control" bind:value="{addLinkDownloadLimit}" required min="0">
				</div>
			</ModalBody>
			<ModalFooter>
				<input type="button" class="btn btn-secondary" on:click={toggleModalAdd} value="Anuluj">
				<input type="submit" class="btn btn-primary" value="Dodaj">
			</ModalFooter>
		</form>
	</Modal>

	{#if isLoading}
		<Loader/>
	{/if}

	{#if links.length}
		<div class="table-responsive mt-2">
			<table class="table table-striped table-hover table-sm">
				<tr>
					<th>ID</th>
					<th>Skrypt</th>
					<th>Email</th>
					<th>Numer licencji</th>
					<th>Link</th>
					<th>Data końca</th>
					<th>Pozostało pobrań</th>
					<th>Data</th>
					<th>Edytuj</th>
					<th>Usuń</th>
				</tr>
				{#each links as link, index}
					<tr class:table-danger={!link.active}>
						<td>{link.id}</td>
						<td>{link.script_name}</td>
						<td>
							<p class="mb-1"><a href="mailto:{link.email}">{link.email}</a></p>
							<button type="button" class="btn btn-secondary btn-sm" on:click={() => toggleModalSendMail(index)}>Wyślij mail</button>
						</td>
						<td>{link.license_number}</td>
						<td><a href="{link.link}" target="_blank">{link.link}</a></td>
						<td>{link.date_finish}</td>
						<td>{link.download_limit}</td>
						<td>{link.date}</td>
						<td><button type="button" class="btn btn-link" on:click={() => toggleModalEdit(index)}><i class="fas fa-edit"></i></button></td>
						<td><button type="button" class="btn btn-link text-danger" on:click={() => toggleModalRemove(index)}><i class="fas fa-trash"></i></button></td>
					</tr>
				{/each}
			</table>
		</div>

		<Modal isOpen={openModalSendMail} {toggleModalSendMail} size="lg">
			<ModalHeader {toggleModalSendMail}>Wyślij mail z linkiem</ModalHeader>
			<form on:submit|preventDefault="{handleSendMailLink}" method="post">
				<ModalBody>
					<div class="form-group">
						<label for="subject">Temat <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="subject" bind:value="{SendMailLinkSubject}" required>
					</div>
					<div class="form-group">
						<label for="content">Treść <span class="text-danger">*</span></label>
						<textarea class="form-control" name="content" style="min-height:150px" required bind:value="{SendMailLinkContent}"></textarea>
					</div>
				</ModalBody>
				<ModalFooter>
					<input type="button" class="btn btn-secondary" on:click={toggleModalSendMail} value="Anuluj">
					<input type="submit" class="btn btn-primary" value="Wyślij">
				</ModalFooter>
			</form>
		</Modal>

		<Modal isOpen={openModalRemove} {toggleModalRemove}>
			<ModalHeader {toggleModalRemove}>Usuń link</ModalHeader>
			<form on:submit|preventDefault="{handleRemoveLink}" method="post">
				<ModalBody>
					<p>Na pewno chcesz usunąć link?</p>
				</ModalBody>
				<ModalFooter>
					<input type="button" class="btn btn-secondary" on:click={toggleModalRemove} value="Anuluj">
					<input type="submit" class="btn btn-danger" value="Usuń">
				</ModalFooter>
			</form>
		</Modal>

	{/if}
</div>
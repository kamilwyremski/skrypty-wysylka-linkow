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
	let error = '';
	let success = '';
	let isLoading = true;
	let newScriptName = "";
	let editScriptName = "";
	let editScriptFilename = "";
	let editScriptId = 0;
	let removeScriptName = "";
	let removeScriptId = 0;
  let files = [];

	let openModalAdd = false;
	const toggleModalAdd = () => (openModalAdd = !openModalAdd);
	
	let openModalEdit = false;
  const toggleModalEdit = (index) => {
		if(scripts[index]){
			editScriptName = scripts[index].name
			editScriptFilename = scripts[index].filename
			editScriptId = scripts[index].id
		}
		openModalEdit = !openModalEdit
	}

	let openModalRemove = false;
  const toggleModalRemove = (index) => {
		if(scripts[index]){
			removeScriptName = scripts[index].name
			removeScriptId = scripts[index].id
		}
		openModalRemove = !openModalRemove
	}

  onMount(() => {
		loadScripts();
	});
	
	const loadScripts = async () => {
		isLoading = true;
		const response = await fetch(API_URL+'/get-scripts', {
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
    if (response.scripts) {
      scripts = response.scripts;
    } else {
			error = response.error;
			success = ''
		}
	};

	const handleAddScript = async () => {
		isLoading = true;
		openModalAdd = false;
		success = 'Wysyłam skrypt na serwer...';

		const formData = new FormData();
		formData.append('name', newScriptName);
    formData.append('file', files[0]);
		const response = await fetch(API_URL+'/add-script', {
			method: "POST",
			headers: {
				'Authorization': 'Bearer ' + $authToken
			},
			body: formData
		})
		.then(response => response.json())
    .catch((e) => {
      error = e;
    });

		isLoading = false;
		newScriptName = '';
		if (response.status) {
			success = 'Skrypt został poprawnie wysłany';
			loadScripts();
		} else {
			error = response.error || error;
			success = ''
		}
	};

	const handleEditScript = async () => {
		isLoading = true;
		openModalEdit = false;
		success = 'Aktualizuje skrypt na serwerze...';

		const formData = new FormData();
		formData.append('name', editScriptName);
		formData.append('filename', editScriptFilename);
		formData.append('id', editScriptId);
		if(files.length){
			formData.append('file', files[0]);
		}
		const response = await fetch(API_URL+'/edit-script', {
			method: "POST",
			headers: {
				'Authorization': 'Bearer ' + $authToken
			},
			body: formData
		})	
		.then(response => response.json())
    .catch((e) => {
      error = e;
    });

		isLoading = false;
		if (response.status) {
			success = 'Skrypt '+editScriptName+' został poprawnie zaktualizowany';
			loadScripts();
		} else {
			error = response.error || error;
			success = ''
		}
	};

	const handleRemoveScript = async () => {
		isLoading = true;
		openModalRemove = false;
		success = 'Usuwam skrypt na serwerze...';

		const formData = new FormData();
		formData.append('id', removeScriptId);
		const response = await fetch(API_URL+'/remove-script', {
			method: "POST",
			headers: {
				'Authorization': 'Bearer ' + $authToken
			},
			body: formData
		})
		.then(response => response.json())
    .catch((e) => {
      error = e;
    });

		isLoading = false;
		if (response.status) {
			success = 'Skrypt został poprawnie usunięty';
			scripts = scripts.filter((value) => value.id != removeScriptId);
		} else {
			error = response.error || error;
			success = ''
		}
	};

</script>

<svelte:head>
	<title>Skrypty stron www</title>
</svelte:head>

<div class="container mt-5 mb-5">
	<h1 class="mb-4">Skrypty stron www</h1>

	{#if error}
		<h5 class="text-danger">{error}</h5>
	{/if}
	{#if success}
		<h5 class="text-success">{success}</h5>
	{/if}

	<button type="button" class="btn btn-primary mt-2 mb-2" on:click={toggleModalAdd}>Dodaj nowy skrypt</button>

	<Modal isOpen={openModalAdd} {toggleModalAdd}>
		<ModalHeader {toggleModalAdd}>Dodaj nowy skrypt</ModalHeader>
		<form on:submit|preventDefault="{handleAddScript}" method="post" enctype="multipart/form-data">
			<ModalBody>
				<div class="mb-3">
					<label for="name">Nazwa <span class="text-danger">*</span></label>
					<input type="text" class="form-control" bind:value="{newScriptName}" required maxlength="128">
				</div>
				<div class="mb-3">
					<label for="file">Załaduj plik <span class="text-danger">*</span></label>
					<input type="file" class="form-control" bind:files required>
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

	{#if scripts.length}
		<div class="table-responsive mt-2">
			<table class="table table-striped table-hover">
				<thead>
					<tr>
						<th>ID</th>
						<th>Nazwa</th>
						<th>Nazwa pliku</th>
						<th>Plik</th>
						<th>Rozmiar</th>
						<th class="text-nowrap">Data aktualizacji</th>
						<th>Edytuj</th>
						<th>Usuń</th>
					</tr>
				</thead>
				<tbody>
					{#each scripts as script, index}
						<tr>
							<td>{script.id}</td>
							<td>{script.name}</td>
							<!-- svelte-ignore missing-declaration -->
							<td><a href="{API_URL}/download.php?token={$authToken}&script_id={script.id}" download target="_blank">{script.filename}</a></td>
							<!-- svelte-ignore missing-declaration -->
							<td><a href="{API_URL}/upload/{script.file}" download target="_blank">{script.file}</a></td>
							<td>{script.filesize}</td>
							<td class="text-nowrap">{script.date}</td>
							<td>
								<button type="button" class="btn btn-link" on:click={() => toggleModalEdit(index)}><i class="fas fa-edit"></i></button>
							</td>
							<td><button type="button" class="btn btn-link text-danger" on:click={() => toggleModalRemove(index)}><i class="fas fa-trash"></i></button></td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>

		<Modal isOpen={openModalEdit} {toggleModalEdit}>
			<ModalHeader {toggleModalEdit}>Edytuj skrypt</ModalHeader>
			<form on:submit|preventDefault="{handleEditScript}" method="post" enctype="multipart/form-data">
				<ModalBody>
					<div class="mb-3">
						<label for="name">Nazwa <span class="text-danger">*</span></label>
						<input type="text" class="form-control" bind:value="{editScriptName}" required maxlength="128">
					</div>
					<div class="mb-3">
						<label for="name">Zmień nazwę pliku <span class="text-danger">*</span></label>
						<input type="text" class="form-control" bind:value="{editScriptFilename}" required maxlength="128">
					</div>
					<div class="mb-3">
						<label for="file">LUB załaduj nowy plik</label>
						<input type="file" class="form-control" bind:files>
					</div>
				</ModalBody>
				<ModalFooter>
					<input type="button" class="btn btn-secondary" on:click={toggleModalEdit} value="Anuluj">
					<input type="submit" class="btn btn-primary" value="Zapisz">
				</ModalFooter>
			</form>
		</Modal>

		<Modal isOpen={openModalRemove} {toggleModalRemove}>
			<ModalHeader {toggleModalRemove}>Usuń skrypt</ModalHeader>
			<form on:submit|preventDefault="{handleRemoveScript}" method="post">
				<ModalBody>
					<p>Na pewno chcesz usunąć plik {removeScriptName}?</p>
				</ModalBody>
				<ModalFooter>
					<input type="button" class="btn btn-secondary" on:click={toggleModalRemove} value="Anuluj">
					<input type="submit" class="btn btn-danger" value="Usuń">
				</ModalFooter>
			</form>
		</Modal>
	{/if}
</div>


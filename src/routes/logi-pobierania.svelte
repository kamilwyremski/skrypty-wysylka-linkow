<script>
	import { onMount } from 'svelte';
	import { authToken } from "../stores";
	import Loader from "../components/Loader.svelte";

	let logs = [];
	let error = '';
	let isLoading = true;

  onMount(async () => {
		isLoading = true;
		const response = await fetch(API_URL+'/get-logs-download', {
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
	});
</script>

<svelte:head>
	<title>Logi pobrania skryptów</title>
</svelte:head>

<div class="container mt-5 mb-5">
	<h1 class="mb-4">Logi pobrania skryptów</h1>

	{#if error}
		<h5 class="text-danger">{error}</h5>
	{/if}

	{#if isLoading}
		<Loader/>
	{/if}

	{#if logs.length}
		<div class="table-responsive mt-2">
			<table class="table table-striped table-hover table-sm">
				<thead>
					<tr>
						<th></th>
						<th>Link</th>
						<th class="text-center">Prawidłowy</th>
						<th>Link ID</th>
						<th>Skrypt</th>
						<th>IP</th>
						<th class="text-nowrap">Data</th>
					</tr>
				</thead>
				<tbody>
					{#each logs as log, index}
						<tr class:table-danger={log.correct!=1}>
							<td>{index+1}</td>
							<td>{log.link}</td>
							<td class="text-center">{#if log.correct==1}Tak{:else}Nie{/if}</td>
							<td>{log.link_id}</td>
							<td>{log.script_name}</td>
							<td>{log.ip}</td>
							<td class="text-nowrap">{log.date}</td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
	{/if}
</div>
<script>
	import { onMount } from 'svelte';
	import { authToken, isLogged } from "../stores";
	import Nav from '../components/Nav.svelte';
	import Login from '../components/Login.svelte';

	export let segment;

	onMount(async () => {
		if($authToken){
			const response = await fetch(API_URL+'/check-token', {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
					Accept: "application/json",
				},
				body: JSON.stringify({ token: $authToken }),
			});
			const parsed = await response.json();
			if (parsed.status) {
				$isLogged = true;
			}
		}
  });
</script>

{#if $isLogged}
	<Nav {segment}/>
	<main>
		<slot></slot>
	</main>
{:else}
	<main>
		<Login/>
	</main>
{/if}
<script>
    import {
    Collapse,
    Navbar,
    NavbarToggler
  } from 'sveltestrap';

  import { authToken, isLogged } from "../stores";

  const logOut = () => {
    $authToken = '';
    $isLogged = false;
	};

  let isOpen = false;

  function handleUpdate(event) {
    isOpen = event.detail.isOpen;
  }

	export let segment;
</script>

<Navbar color="light" light expand="md">
  <a class="navbar-brand" href=".">Skrypty</a>
  <NavbarToggler on:click={() => (isOpen = !isOpen)} />
  <Collapse {isOpen} navbar expand="md" on:update={handleUpdate}>
    <div class="navbar-nav">
      <a class="nav-link" on:click={() => (isOpen = !isOpen)} class:active={segment === undefined} aria-current="{segment === undefined ? 'page' : undefined}" href=".">Home{#if segment === undefined} <span class="sr-only">(current)</span>{/if}</a>
      <a class="nav-link" on:click={() => (isOpen = !isOpen)} class:active={segment === 'skrypty'} aria-current="{segment === 'skrypty' ? 'page' : undefined}" href="skrypty">Skrypty{#if segment === 'skrypty'} <span class="sr-only">(current)</span>{/if}</a>
      <a class="nav-link" on:click={() => (isOpen = !isOpen)} class:active={segment === 'linki'} aria-current="{segment === 'linki' ? 'page' : undefined}" href="linki">Linki{#if segment === 'linki'} <span class="sr-only">(current)</span>{/if}</a>
      <a class="nav-link" on:click={() => (isOpen = !isOpen)} class:active={segment === 'logi-pobierania'} aria-current="{segment === 'logi-pobierania' ? 'page' : undefined}" href="logi-pobierania">Logi pobierania{#if segment === 'logi-pobierania'} <span class="sr-only">(current)</span>{/if}</a>
      <a class="nav-link" on:click={() => (isOpen = !isOpen)} class:active={segment === 'ustawienia'} aria-current="{segment === 'ustawienia' ? 'page' : undefined}" href="ustawienia">Ustawienia{#if segment === 'ustawienia'} <span class="sr-only">(current)</span>{/if}</a>
    </div>
    <div class="navbar-nav ms-auto">
      <button type="button" class="btn btn-link nav-link" on:click={logOut}>Wyloguj</button>
    </div>
  </Collapse>
</Navbar>